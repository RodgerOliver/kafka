<?php
require_once "Consumer.php";

$consumer = new Consumer('AtualizaDadosPDV', 0);
$consumer->consume();
