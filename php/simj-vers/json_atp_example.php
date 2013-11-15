<?php


require_once('json_atp.php');

$head_key = 'super-key-for-server1';
$data_key = 'data-mega-key1';

$jsonatp = new JsonAtp(562);

var_dump($jsonatp);

$data = 'Super Secret Message';

//$jsonatp->addExtra();

$edata = $jsonatp->encode($data);
var_dump($jsonatp);
var_dump($edata);

$ddata = $jsonatp->decode($edata,true);
var_dump($jsonatp);
var_dump($ddata);
var_dump($jsonatp->getExtra());

$ddata = $jsonatp->decode($edata);
var_dump($jsonatp);
var_dump($ddata);

$jjj = new JsonAtp($head_key);