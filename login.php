<?php
require 'vendor/autoload.php';
require 'FaucetManager.php';

use \LinusU\Bitcoin\AddressValidator;

if(isset($_POST['btcAddress'])) {
    if(AddressValidator::isValid($_POST['btcAddress'])) {
        $mgr = new Manager($_POST['btcAddress']);
        setCookie('btcAddress', $mgr->getAddress(), time()+3600);
        setCookie('satBalance', $mgr->getBalance(), time()+3600);
    }
}

header("Location: ./");