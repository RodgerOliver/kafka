<?php
require_once "ConsumerErrors.php";

$consumer = new ConsumerErrors('KafkaErrors', 0);
$consumer->consume();
