<?php
require "../lib/Paytoshi.php";
require "../lib/FaucetManager.php";
include "../lib/reCaptcha.php";

if(!isset($_COOKIE['btcAddress'])) die(json_encode(array("error"=>"not logged in")));
if(!isset($_POST['g-recaptcha-response'])) die(json_encode(array("error"=>"missing captcha")));

$rc = new reCaptcha('6LdzugYTAAAAAAfTBw3_BqbRpeAywMkpL-NzdEp9');
$res = $rc->verify($_POST['g-recaptcha-response']);

if(!$res['success']) die(json_encode($res));

$mgr = new Manager($_COOKIE['btcAddress']);
die(json_encode($mgr->payout()));