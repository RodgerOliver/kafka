<?php
require_once "Consumer.php";

$consumer = new Consumer('AtualizaDadosPDV', 2);
$consumer->consume();
