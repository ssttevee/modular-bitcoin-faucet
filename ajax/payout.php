<?php
require "../lib/Paytoshi.php";
require "../lib/FaucetManager.php";

if(!$_COOKIE['btcAddress']) die(json_encode(array("error"=>"not logged in")));

$mgr = new Manager($_COOKIE['btcAddress']);
die(json_encode($mgr->payout()));