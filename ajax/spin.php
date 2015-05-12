<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');

include "../lib/FaucetManager.php";
header('Content-Type: application/json');
if(!$_COOKIE['btcAddress']) die(json_encode(array("error"=>"not logged in")));

$mgr = new Manager($_COOKIE['btcAddress']);

if(!$_POST['claim']) {
    die(json_encode($mgr->spin()));
} else {
    if(!isset($_POST['curve'])) die(json_encode(array("error"=>"missing curve")));
    die(json_encode($mgr->claimSpin($_POST['curve'])));
}