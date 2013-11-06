<?php


require_once('server/json_atp_server.php');

$atps = new JsonAtpServer();

#$atps->setCipher('aes-256-cbc');

$atps->setKey('123','456');

var_dump($atps->useCompression());
var_dump($atps->useEncryption());

var_dump($atps->encode('HELLO WORLD'));

var_dump($atps);