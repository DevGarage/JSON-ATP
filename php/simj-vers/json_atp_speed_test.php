<?php
/**
 * Created by PhpStorm.
 * User: Администратор
 * Date: 25.11.13
 * Time: 18:23
 */

require_once('json_atp.php');

## SPEED TEST | JUST TEXT ##


$message    = 'Super secret message';
$count      = 10000;
$timer      = 0;

echo sprintf("ATP Speed Test </br>");
echo sprintf("Count %d</br>",$count);

######################## JUST TEXT ##

$timer = microtime(true);

$atp = new JsonAtp('speedtest-1',null,null,JsonAtp::FLAG_CLEAR_TEXT);

for($i=0; $i < $count; $i++){
    $msg = $atp->encode($message);
}

$timer = microtime(true) - $timer;

echo sprintf("Just text %0.4f </br>",$timer);

################################## DEFAULT ##

$timer = microtime(true);

$atp = new JsonAtp('speedtest-1');

for($i=0; $i < $count; $i++){
    $msg = $atp->encode($message);
}

$timer = microtime(true) - $timer;

echo sprintf("Default flag %0.4f </br>",$timer);
