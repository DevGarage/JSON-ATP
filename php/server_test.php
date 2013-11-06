<?php


require_once('server/json_atp_server.php');

$head_key = 'super-key-for-server';
$data_key = 'data-mega-key';

$atps = new JsonAtpServer();
$atps->setFlag(JsonAtpServer::FLAG_CLEAR_TEXT);

//$atps->setCipher('aes-256-cbc');
$atps->setKey($head_key,$data_key);

var_dump($atps->useCompression());
var_dump($atps->useEncryption());

$encode = $atps->encode($_SERVER['HTTP_USER_AGENT']);
var_dump(array('encode' => $encode));

var_dump($atps);

var_dump('-- ENCODE --');

$atp_decode = new JsonAtpServer();
$atp_decode->setKey($head_key,$data_key);
$atp_decode->decode($encode);

var_dump($atp_decode);