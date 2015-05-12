<?php
//error_reporting(E_ALL);
//ini_set('display_errors', '1');

include "../lib/FaucetManager.php";
include "../lib/reCaptcha.php";
header('Content-Type: application/json');
if(!$_COOKIE['btcAddress']) die(json_encode(array("error"=>"not logged in")));

$mgr = new Manager($_COOKIE['btcAddress']);

if(!isset($_POST['claim']) || !$_POST['claim']) {
    die(json_encode($mgr->spin()));
} else {
    if(!isset($_POST['g-recaptcha-response'])) die(json_encode(array("error"=>"missing captcha")));
    if(!isset($_POST['curve'])) die(json_encode(array("error"=>"missing curve")));

    $rc = new reCaptcha('6LdzugYTAAAAAAfTBw3_BqbRpeAywMkpL-NzdEp9');
    $res = $rc->verify($_POST['g-recaptcha-response']);

    if(!$res['success']) die(json_encode($res));

    die(json_encode($mgr->claimSpin($_POST['curve'])));
}