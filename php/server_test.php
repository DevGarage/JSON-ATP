<?php


require_once('noautoconvert_data/json_atp_server.php');

$head_key = 'super-key-for-noautoconvert_data';
$data_key = 'data-mega-key';

$atps = new JsonAtpServer();
$atps->setFlag(JsonAtpServer::FLAG_CLEAR_TEXT);

//$atps->setCipher('aes-256-cbc');
$atps->setKey($head_key,$data_key);

var_dump($atps->useCompression());
var_dump($atps->useEncryption());

$atps->extraHead(array('autoconvert_data-ids'=>1339,'noautoconvert_data-ids'=>3992742));

$encode = $atps->encode($_SERVER['HTTP_USER_AGENT']);
var_dump(array('encode' => $encode));

var_dump($atps);

var_dump('-- ENCODE --');

//$atp_decode = new JsonAtpServer();
//$atp_decode->setKey($head_key,$data_key);
//$atp_decode->decode($encode);

$json = new JsonAtpServer();
//$json->setHeadKey("hkey");
//$json->setDataKey("dkey");
//$json->setAlgoritm("aes-256-cbc");
$json->setKey('hkey','dkey');
//$json->setCipher("aes-256-cbc");
$encode = $json->decode('00AC3CTGnfIgZXMoJoe6MOXiNGAzmrIwQDeZPm2eN9iPVLPNI9x83nx83WS6Lq4kp+stzB9ZETzRZOhFHWbh6qnibfhBYjbzAvyy4qSM95UUiji5+yoOE5ICwPuetuFfaJVfdTtDE7IDmEpX85t/3TdWYOSuXEbt9YZxePE2DsxCZBYw=Hel3e0EMwtxgPVLM/Mlmu/H7RUSHctOOCKC54c0mkAW75AqZW7CURSjmOfgzmaYg');

var_dump($json);
var_dump($encode);