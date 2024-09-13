<?php
require_once "Consumer.php";

$consumer = new Consumer('GravaBinarioMovimento', 0, 'S');
$consumer->consume();
