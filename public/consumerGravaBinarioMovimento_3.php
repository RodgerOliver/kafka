<?php
require_once "Consumer.php";

$consumer = new Consumer('GravaBinarioMovimento', 2, 'S');
$consumer->consume();
