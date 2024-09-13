<?php
require_once "Consumer.php";

class ConsumerErrors extends Consumer {

    protected function sendSoap($data) {
        if(!isset($data['endpoint_error'])) {
            $this->logMsg('ERROR', 'Endpoint error not sent');
            return false;
        }

        $this->logMsg('INFO', var_export($data, true));

        $endpoint_error = preg_replace('/^https/', 'http', $data['endpoint_error']);
        $soapClient = new SoapClient($endpoint_error, [
            'location' => $endpoint_error,
            'soap_version' => SOAP_1_1,
            'encoding' => 'ISO-8859-1',
            'charset' => 'ISO-8859-1',
        ]);

        $reprocessado = 0;
        if(isset($data['reprocessado'])) {
            $reprocessado = (int)$data['reprocessado'];
        }

        $soapReturn = $soapClient->__soapCall('enviarErroKafka', [
            'funcao' => $data['funcao'],
            'dth_erro' => $data['dth_erro'],
            'erro' => $data['erro'],
            'dados' => serialize($data['dados']),
            'flg_integracao_01' => $reprocessado,
        ]);

        if($soapReturn == false) {
            $this->logMsg('ERROR', '----- SOAP ERROR -----');
            $this->logError($data, $soapReturn);
            return false;
        }

        return $soapReturn;

    }

    protected function logError($data, $error) {
        $date = new DateTime();
        $data['erro'] = $error;
        $data['dth_erro'] = $date->format("d-m-Y H:i:s");

        /* Produce error */
        $conf = new RdKafka\Conf();
        $conf->set('metadata.broker.list', $this->ini['broker_list']);
        $conf->set('transactional.id', 'manager_kafka_errors');

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
