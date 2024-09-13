<?php
require_once "Consumer.php";

$consumer = new Consumer('subirXMLNota', 1);
$consumer->consume();

