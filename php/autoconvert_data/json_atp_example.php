<?php
/**
 * DevGar
 * Ukraine, Odessa
 * Created by PhpStorm.
 * User: Victor Murzak
 * email: mv@bel.net.ua
 * GitHub: https://github.com/DevGarage/JSON-ATP.git
 * Date: 06.11.13
 * Time: 9:06
 */

require_once 'json_atp.php';

header('Content-type: text/html; charset=utf-8');

$json = new JsonAtpClient();

$json->setHeadKey("qwertyasdfg");
$json->setDataKey("hfdgdfgdfgewffFASCAC");

$json->setAlgoritm("aes-256-cbc");
$json->setCompression(6);

$json->setHeadUsrId("qwerty");
$json->setHeadReqId(uniqid());

$arr = [
	"qwerty" => "hfdgdfgdfgewffFASCAC",
	"asdasdas" => "asdasdas"
];

$data = "123";


$r = $json->json_atp_encode($data);

var_dump($r);

$answ = $json->json_atp_decode($r, $arr);

var_dump($answ);

var_dump(json_decode($answ));

