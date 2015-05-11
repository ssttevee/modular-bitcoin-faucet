<?php
require 'vendor/autoload.php';
use \LinusU\Bitcoin\AddressValidator;
if(isset($_POST['btcAddress'])) {
    if(AddressValidator::isValid($_POST['btcAddress'])) {
        setCookie('btcAddress', $_POST['btcAddress'], time()+3600);
    }
}

header("Location: ./");