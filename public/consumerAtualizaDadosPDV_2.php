<?php
require_once "Consumer.php";

$consumer = new Consumer('AtualizaDadosPDV', 1);
$consumer->consume();
