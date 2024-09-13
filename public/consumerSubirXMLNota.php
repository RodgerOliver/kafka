<?php
require_once "Consumer.php";

$consumer = new Consumer('subirXMLNota', 0);
$consumer->consume();

