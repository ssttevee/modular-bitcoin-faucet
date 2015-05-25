<?php
ob_start();
require_once __DIR__ . "/vendor/autoload.php";

use AllTheSatoshi\Util\Config;

if(!array_key_exists("action", $_GET)) _respond("Action was not specified.");
$action = $_GET["action"];

if($action == "login") {
    if(!array_key_exists("btcAddress", $_POST)) _respond("Bitcoin address was not specified.");
    if(\LinusU\Bitcoin\AddressValidator::isValid($_POST['btcAddress'])) {
        try {
            $mgr = new \AllTheSatoshi\FaucetManager($_POST['btcAddress']);
            _respond("Login successful", true);
        } catch(Exception $e) {
            _respond($e->getMessage());
        }
    } else {
        _respond("Bitcoin address is invalid.");
    }
}

if(!array_key_exists("btcAddress", $_COOKIE)) _respond("You're not logged in.");

$mgr = \AllTheSatoshi\FaucetManager::_($_COOKIE['btcAddress']);

$_POST['is_human'] = false;
if(array_key_exists('captcha_challenge', $_POST)) {
    verifyCaptcha($_POST['captcha_challenge'], $_POST['captcha_response']);
    $_POST['is_human'] = true;
}

if(array_key_exists("game", $_GET)) {
    $faucet = null;
    switch($_GET["game"]) {
        case "random-number":
            $faucet = new \AllTheSatoshi\Faucet\SpinnerFaucet($mgr->address);
            break;
        case "lucky-joker":
            $faucet = new \AllTheSatoshi\Faucet\CardsFaucet($mgr->address);
            break;
        default:
            _respond("Faucet does not exist.");
    }
    $output = $faucet->ajax($action, $_POST);
    _respond(empty($output) ? "Action not allowed" : $output);
} else {
    if($action == "payout") {
        if (!isset($_POST['utransserv'])) _respond("Payment method was not given.");
        if(!$_POST['is_human']) _respond("not_human");
        _respond($mgr->payout($_POST['utransserv']));
    }
}

_respond("Something went wrong.");


function _respond($response, $success = false) {
    if($response == "not_human") $response = "Human verification missing.";
    if(is_string($response)) $response = ["message" => $response];
    if(!isset($response["success"])) $response["success"] = $success;
    header('Content-Type: application/json');

    $ob = ob_get_clean();
    if(!empty($ob)) $response["debug"] = $ob;

    die(json_encode($response));
}

function verifyCaptcha($challenge, $response) {
    $solvemedia = new \AllTheSatoshi\Util\SolveMedia(Config::ini("captcha_services", "solveMediaPrivateKey"), Config::ini("captcha_services", "solveMediaHashKey"));
    $response = $solvemedia->verify($challenge, $response);
    if(!$response['success']) _respond($response);
}
?>