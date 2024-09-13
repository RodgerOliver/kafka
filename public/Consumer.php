<?php

class Consumer {
    protected $topic;
    protected $partition;
    protected $soap_kafka_arg;
    protected $ini;
    protected $log_file;
    protected $error_id;
    protected $error_topic;

    public function __construct($topic, $partition, $soap_kafka_arg = '1') {
        $this->topic = $topic;
        $this->partition = $partition;
        $this->soap_kafka_arg = $soap_kafka_arg;
        $this->ini = parse_ini_file("kafka.ini");
        $this->log_file = 'kafka.log';
        $this->error_id = 'manager_kafka_errors';
        $this->error_topic = 'KafkaErrors';
    }

    public function consume() {
        $consumer = new \RdKafka\Consumer();
        $consumer->addBrokers($this->ini['broker_list']);

        $topicConf = new \RdKafka\TopicConf();
        $topicConf->set('auto.commit.interval.ms', 100);
        $topicConf->set('auto.offset.reset', 'earliest');

        $queue = $consumer->newQueue();

        $topic = $consumer->newTopic($this->topic, $topicConf);
        $start_offset = RD_KAFKA_OFFSET_STORED < 0 ? RD_KAFKA_OFFSET_END : RD_KAFKA_OFFSET_STORED;
        $topic->consumeQueueStart($this->partition, $start_offset, $queue);


        $this->logMsg('INFO', 'Consumer ready...', false);
        while (true) {

            // The only argument is the timeout.
            $msg = $queue->consume(1500);

            // Constant check required by librdkafka 0.11.6. Newer librdkafka versions will return NULL instead.
            if (null === $msg || $msg->err === RD_KAFKA_RESP_ERR__PARTITION_EOF) {
                if(null !== $msg) {
                    $this->logMsg('ERROR 1', $msg->errstr());
                    $this->logMsg('ERROR 1', var_export($msg, true));
                }

            } elseif ($msg->err) {
                $this->logMsg('ERROR 2', $msg->errstr());
                $this->logMsg('ERROR 2', var_export($msg, true));

            } else {

                $this->logMsg('SUCCESS', 'Message received');
                $this->logMsg('INFO', var_export($msg, true));
                try {
                    $data = unserialize($msg->payload);

                    if(gettype($data) != 'array') {
                        $this->logMsg('ERROR', 'Wrong data type');
                        continue;
                    }
                    if(sizeof($data) == 0) {
                        $this->logMsg('ERROR', 'Empty array');
                        continue;
                    }
                    if(!isset($data['endpoint'])) {
                        $this->logMsg('ERROR', 'Endpoint not sent');
                        continue;
                    }

                    $soapReturn = $this->sendSoap($data);
                    if(!$soapReturn) {
                        continue;
                    }

                    $this->logMsg('INFO', 'SOAP: '. var_export($soapReturn, true));
                    $this->logMsg('SUCCESS', 'Message sent');

                } catch (Exception $e) {
                    $this->logMsg('ERROR', 'Failed try: '. $e->getMessage());
                    $this->logError($data, $e->getMessage());
                }
            }
        }
    }

    protected function sendSoap($data) {
        if(!array_key_exists('funcao', $data) || !array_key_exists('dados', $data)) {
            $this->logMsg('ERROR', 'Invalid array');
            return false;
        }

        $data['dados'] = unserialize($data['dados']);
        $data['dados']['Kafka'] = $this->soap_kafka_arg;

        $this->logMsg('INFO', var_export($data, true));

        $soapClient = new SoapClient($data['endpoint'], [
            'location' => $data['endpoint'],
            'soap_version' => SOAP_1_1,
            'encoding' => 'ISO-8859-1',
            'charset' => 'ISO-8859-1',
            'keep_alive' => false,
            'stream_context'=> stream_context_create(
                array(
                    'http'=> array(
                        'protocol_version'=>'1.1',
                        'header' => 'Connection: Close',
                        'user_agent' => 'PHPSoapClient'
                    ),
                    'ssl' => array(
                        'verify_peer' => false,
                        'verify_peer_false' => false,
                    ),
                )
            ),
        ]);

        $soapReturn = $soapClient->__soapCall($data['funcao'], $data['dados']);

        $soapReturnXML = false;
        if($soapReturn !== true) {
            $soapReturnXML = simplexml_load_string($soapReturn);
        }
        $this->logMsg('INFO', var_export($soapReturnXML, true));
        if ($soapReturn === false || ($soapReturnXML !== false && $soapReturnXML->cod_situacao != 0)) {
            $this->logMsg('ERROR', '----- SOAP ERROR -----');
            $this->logError($data, $soapReturn);
            return false;
        }

        return $soapReturn;

    }

    protected function logMsg($type, $msg, $log_on_file = true) {
        $line = "[$type] $msg \n";
        if($log_on_file && $this->ini['log'] == 'S') {
            file_put_contents($this->log_file, $line, FILE_APPEND);
        }
        echo $line;
    }

    protected function logError($data, $error) {
        $date = new DateTime();
        $data['erro'] = $error;
        $data['dth_erro'] = $date->format("d-m-Y H:i:s");

        /* Produce error */
        $conf = new RdKafka\Conf();
        $conf->set('metadata.broker.list', $this->ini['broker_list']);
        $conf->set('transactional.id', $this->error_id);

        $producer = new RdKafka\Producer($conf);

        $producer->initTransactions(10000);
        $producer->beginTransaction();

        $topic = $producer->newTopic($this->error_topic);

        if (!$producer->getMetadata(false, $topic, 5000)) {
            echo "Failed to get metadata, is broker down?\n";
            return false;
        }

        $this->logMsg('PRODUCE ERROR', '_DATA: '. var_export($data, true));
        $topic->produce(RD_KAFKA_PARTITION_UA, 0, serialize($data));
        $producer->poll(0);
        $error = $producer->commitTransaction(10000);

        if (RD_KAFKA_RESP_ERR_NO_ERROR !== $error) {
            //check what kind of error it was e.g. $error->isFatal(), etc. and act accordingly (retry, abort, etc.)
            $this->logMsg('ERROR', var_export($error, true));
            return false;

        } else {
            $this->logMsg('INFO', 'Message published');
            return true;
        }
    }
}

