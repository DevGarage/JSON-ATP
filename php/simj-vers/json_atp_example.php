<?php


require_once('json_atp.php');

$head_key = 'super-key-for-server1';
$data_key = 'data-mega-key1';

$jsonatp = new JsonAtp('user-1','super');

var_dump($jsonatp);

$data = 'Super Secret Message';

//$jsonatp->addExtra();

$edata = $jsonatp->encode(null);
var_dump($jsonatp);
var_dump($edata);

$ddata = $jsonatp->decode($edata);
var_dump($jsonatp);
var_dump($ddata);

$jjj = new JsonAtp($head_key);

if($jjj->parseHead($edata))
    var_dump($jjj->getToken());
