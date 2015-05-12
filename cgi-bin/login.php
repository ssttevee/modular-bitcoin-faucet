<?php
require '../vendor/autoload.php';
require '../lib/FaucetManager.php';

use \LinusU\Bitcoin\AddressValidator;

if(isset($_POST['btcAddress'])) {
    if(AddressValidator::isValid($_POST['btcAddress'])) {
        $mgr = new Manager($_POST['btcAddress']);
    }
}

header("Location: ../");