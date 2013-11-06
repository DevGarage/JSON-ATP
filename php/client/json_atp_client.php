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

header('Content-type: text/html; charset=utf-8');

$json = new JsonAtpClient();

$json->setHeadKey("qwertyasdfg");
$json->setDataKey("hfdgdfgdfgewffFASCAC");

$json->setAlgoritm("CAST5-OFB");
$json->setCompression(3);

$data = "Українцям без картоплі, як без сала – не прожить!
Не бува її занадто ні уранці, ні в обід,
І вечеря без картоплі – не вечеря – бог зна що!
Рис миршавий китайоський проти бульби «Тьху! Ніщо!»

Ми її їмо на свято, в будній день її їмо
Ми жуєм її з завзяттям! Хай там як! І хай там що!
Заміня вона нам зразу «снікерс», «баунті» і «твікс»
Бо арахіс проти бульби і до пупа не доріс!

Хай «данісімо» гуляє по канавах за селом
Поки з кількою мундьорку ми за дві щоки їмо!
Дві калорії в «тік-таку»: це ж позор який і срам!
А картоплю лиш понюхай – і вдихнеш їх більше ста!

Сенс який пів дня жувати супер-м΄ятний «стіморо́л»,
Імітуючи рогатих ремигаючих коров,
Коли можна тихо сісти і картопельки поїсти,
А не гуми шмат дебелий, очі вирячивши, гризти!

Заграничні викрутаси не для «нашинської» раси!
Ми – фанати картопляні! Заявляємо всім зразу!
Тож, панове, не мудруйте; і картопельку шануйте,
Запашні її принади з насолодою смакуйте!";

$r = $json->json_atp_encode($data, 2);

file_put_contents("request", $r);

var_dump($r);

$answ = $json->json_atp_decode($r);

var_dump($answ);

/*$json->setHeadKey("qwertyasdfg");
$json->setDataKey("hfdgdfgdfgewffFASCAC");
$r = "";
$answ = $json->json_atp_decode($r);
var_dump($answ);*/

