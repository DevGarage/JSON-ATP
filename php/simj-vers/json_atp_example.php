<?php


require_once('json_atp.php');

## Server key for HEAD ##
$head_key = 'super-key-for-server';

## Data key ##
$data_key = 'secret-key-for-client-1';

## Create client-1 instance to transfer message to server ##
$encode = new JsonAtp('client-1',$head_key,$data_key);

## Secret message ##
$data = 'Super Secret Message';

## Encode date ##
$edata = $encode->encode($data);

## Show coder status ##
var_dump($encode);

## Show message ##
var_dump($edata);


## Create Decode instance, Without token (for Server)
$decode = new JsonAtp(null,$head_key);
$client_token = $decode->decode($edata,true);

## Show client token ##
var_dump($client_token);

## Search in Client DB for data key for this client ##
$client_key = 'secret-key-for-client-1';
$decode->setDataKey($client_key);

## Decode message ##
$message = $decode->decode($edata);

## Show message ##
var_dump($message);
