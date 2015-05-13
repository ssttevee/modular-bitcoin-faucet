<?php
require "../lib/Paytoshi.php";
require "../lib/faucetbox.php";
require "../lib/FaucetManager.php";
include "../lib/reCaptcha.php";

if(!isset($_COOKIE['btcAddress'])) die(json_encode(["success" => false, "message" => "not logged in"]));
if(!isset($_POST['utransserv'])) die(json_encode(["success" => false, "message" => "missing payment service"]));
if(!isset($_POST['g-recaptcha-response'])) die(json_encode(["success" => false, "message" => "missing captcha"]));

$rc = new reCaptcha('6LdzugYTAAAAAAfTBw3_BqbRpeAywMkpL-NzdEp9');
$res = $rc->verify($_POST['g-recaptcha-response']);

if(!$res['success']) die(json_encode(["success" => false, "message" => reCaptcha::getMessage($res['error_codes'][0])]));

$mgr = new Manager($_COOKIE['btcAddress']);
die(json_encode($mgr->payout($_POST['utransserv'])));