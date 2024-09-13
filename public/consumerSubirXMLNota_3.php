<?php
require_once "Consumer.php";

$consumer = new Consumer('subirXMLNota', 2);
$consumer->consume();

