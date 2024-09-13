<?php
require_once "Consumer.php";

$consumer = new Consumer('GravaBinarioMovimento', 1, 'S');
$consumer->consume();
