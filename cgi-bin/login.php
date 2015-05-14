<?php
require '../vendor/autoload.php';

if(isset($_POST['btcAddress'])) {
    if(\LinusU\Bitcoin\AddressValidator::isValid($_POST['btcAddress'])) {
        $mgr = new \AllTheSatoshi\FaucetManager($_POST['btcAddress']);
    }
}

header("Location: ../");