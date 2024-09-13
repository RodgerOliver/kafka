<?php

$conf = new RdKafka\Conf();
$conf->set('metadata.broker.list', 'kafka:9092,kafka_2:9092,kafka_3:9092');
$conf->set('transactional.id', 'manager_kafka');

$producer = new RdKafka\Producer($conf);

$producer->initTransactions(10000);
$producer->beginTransaction();

//$test = $producer->getMetadata(true, null, 1000);

if(!array_key_exists('funcao', $_POST)) {
    http_response_code(503);
    echo "Request does not have 'funcao' key\n";
    exit;
}

$consumer_up = shell_exec("ping -c 1 consumer-{$_POST['funcao']} > /dev/null && echo -n 'OK'");
if($consumer_up != 'OK') {
    http_response_code(503);
    echo "Consumer down\n";
    exit;
}

$topic = $producer->newTopic($_POST['funcao']);

if (!$producer->getMetadata(false, $topic, 5000)) {
    echo "Failed to get metadata, is broker down?\n";
    exit;
}

logMsg('PRODUCER', '_POST: '. var_export($_POST, true));
$topic->produce(RD_KAFKA_PARTITION_UA, 0, serialize($_POST));
$producer->poll(0);
$error = $producer->commitTransaction(10000);

if (RD_KAFKA_RESP_ERR_NO_ERROR !== $error) {
    //check what kind of error it was e.g. $error->isFatal(), etc. and act accordingly (retry, abort, etc.)
    logMsg('ERROR', var_export($error, true));
} else {
    logMsg('INFO', 'Message published');
    echo "Message published\n";
}

function logMsg($type, $msg, $log_on_file = true) {
    $ini = parse_ini_file("../kafka.ini");
    $line = "[$type] $msg \n";
    if($log_on_file && $ini['log'] == 'S') {
        file_put_contents('../kafka.log', $line, FILE_APPEND);
    }
    echo $line;
}
