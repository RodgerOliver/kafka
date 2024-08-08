<?php

$consumer = new \RdKafka\Consumer();
$consumer->addBrokers("kafka:9092");

$topicConf = new \RdKafka\TopicConf();
$topicConf->set('auto.commit.interval.ms', 100);
$topicConf->set('auto.offset.reset', 'earliest');

$queue = $consumer->newQueue();

$topic = $consumer->newTopic("AtualizaDadosPDV", $topicConf);
$start_offset = RD_KAFKA_OFFSET_STORED < 0 ? RD_KAFKA_OFFSET_END : RD_KAFKA_OFFSET_STORED;
$topic->consumeQueueStart(1, $start_offset, $queue);

$ini = parse_ini_file("kafka.ini");

logMsg('INFO', 'Consumer ready...', false);
while (true) {

    // The only argument is the timeout.
    $msg = $queue->consume(1500);

    // Constant check required by librdkafka 0.11.6. Newer librdkafka versions will return NULL instead.
    if (null === $msg || $msg->err === RD_KAFKA_RESP_ERR__PARTITION_EOF) {
        if(null !== $msg) {
            logMsg('ERROR 1', $msg->errstr());
            logMsg('ERROR 1', var_export($msg, true));
        }

    } elseif ($msg->err) {
        logMsg('ERROR 2', $msg->errstr());
        logMsg('ERROR 2', var_export($msg, true));

    } else {

        logMsg('SUCCESS', 'Message received');
        logMsg('INFO', var_export($msg, true));
        try {
            $data = unserialize($msg->payload);

            if(gettype($data) != 'array') {
                logMsg('ERROR', 'Wrong data type');
                continue;
            }
            if(sizeof($data) == 0) {
                logMsg('ERROR', 'Empty array');
                continue;
            }
            if(!array_key_exists('funcao', $data) || !array_key_exists('dados', $data)) {
                logMsg('ERROR', 'Invalid array');
                continue;
            }
            if(!isset($data['endpoint'])) {
                logMsg('ERROR', 'Endpoint not sent');
                continue;
            }

            $data['dados'] = unserialize($data['dados']);
            $data['dados']['Kafka'] = "1";

            logMsg('INFO', var_export($data, true));

            $soapClient = new SoapClient($data['endpoint'], [
                'location' => $data['endpoint'],
                'soap_version' => SOAP_1_1,
                'encoding' => 'ISO-8859-1',
                'charset' => 'ISO-8859-1',
            ]);

            $soapReturn = $soapClient->__soapCall($data['funcao'], $data['dados']);

            if($soapReturn == false) {
                logMsg('ERROR', '----- SOAP ERROR -----');
                logError($data, $soapReturn);
                continue;
            }

            logMsg('INFO', 'SOAP: '. var_export($soapReturn, true));
            logMsg('SUCCESS', 'Message sent');

        } catch (Exception $e) {
            logMsg('ERROR', 'Failed try: '. $e->getMessage());
            logError($data, $e->getMessage());
        }
    }
}
function logMsg($type, $msg, $log_on_file = true) {
    global $ini;
    $line = "[$type] $msg \n";
    if($log_on_file && $ini['log'] == 'S') {
        file_put_contents('kafka.log', $line, FILE_APPEND);
    }
    echo $line;
}

function logError($data, $error) {
    $date = new DateTime();
    $data['erro'] = $error;
    $data['data'] = $date->format("d-m-Y H:i:s");
    $data['dados']['AXML'] = str_replace(array("\r", "\n"), '', $data['dados']['AXML']);
    unset($data['dados']['Kafka']);
    $line = serialize($data). "\n";

    $pasta_erro = "errors/". $date->format("Ymd");
    if(!is_dir($pasta_erro)) {
        mkdir($pasta_erro, 0777, true);
    }
    file_put_contents($pasta_erro. "/atualizaDadosPDV.log", $line, FILE_APPEND);
}
