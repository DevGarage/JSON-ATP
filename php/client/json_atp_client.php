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

require_once 'jsonatpclient.php';

$json = new JsonAtpClient();

$json->setHeadKey("hkey");
$json->setDataKey("dkey");

$json->setAlgoritm("aes-256-cbc");

$data = $_SERVER;

$r = $json->json_atp_encode($data);

file_put_contents("request", $r);

var_dump($r);

$answ = $json->json_atp_decode($r);

var_dump(json_decode($answ));


