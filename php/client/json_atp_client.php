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

$json->setHeadKey("qwertyasdfg");
$json->setDataKey("hfdgdfgdfgewffFASCAC");

$json->setAlgoritm("CAST5-OFB");
$json->setCompression(3);

$data = [
	1=> openssl_get_cipher_methods(),
	2=> $_SERVER
];

$r = $json->json_atp_encode($data);

file_put_contents("request", $r);
//$r = "011C0eyJjbGllbnQtaWRzIjoxMzM5LCJzZXJ2ZXItaWRzIjozOTkyNzQyLCJwcm90b2NvbCI6MSwicmVxdWVzdCI6IjUyN2EyNGU3NjMxYjIiLCJ0aW1lIjoxMzgzNzM2NTUxLCJzaWduYXR1cmUiOiI2MGY5NjBhZDg3OTMxOTY4YWYxMTk5N2NiNGM4YzZmOGQ2NDljMzk0NWZiZDIwNGVjNDdlZmM1YzExZDYwNWQ4IiwibGVuZ3RoIjoxNiwiY2lwaGVyIjoiYWVzLTE5Mi1jYmMifQ==PT1IZWxsbz09V29ybGQ9PQ==";

var_dump($r);

$answ = $json->json_atp_decode($r);

//var_dump(openssl_get_cipher_methods());

var_dump(json_decode($answ));

