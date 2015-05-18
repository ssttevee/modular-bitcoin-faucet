<?php

require_once __DIR__ . "/vendor/autoload.php";

$allowed_actions = ["spin", "claim_spin", "payout", "login"];

if($_GET["action"] == "login") {
    if(!isset($_POST['btcAddress'])) _respond(["message" => "Bitcoin address was not given."]);
    if(\LinusU\Bitcoin\AddressValidator::isValid($_POST['btcAddress'])) {
        try {
            $mgr = new \AllTheSatoshi\FaucetManager($_POST['btcAddress']);
            _respond(["message" => "Login successful"], true);
        } catch(Exception $e) {
            _respond(["message" => $e->getMessage()]);
        }
    } else {
        _respond(["message" => "Given bitcoin address is invalid."]);
    }
}

if(!isset($_COOKIE['btcAddress'])) _respond(["error" => "You're not logged in."]);
else if(!isset($_GET["action"])) _respond(["message" => "Action was not given."]);
else if(!in_array($_GET["action"], $allowed_actions)) _respond(["message" => "Given action is not allowed."]);


$action = $_GET["action"];
$mgr = new \AllTheSatoshi\FaucetManager($_COOKIE['btcAddress']);

if($action == "spin") {
    _respond((new \AllTheSatoshi\Faucet\SpinnerFaucet($mgr))->spin($_POST['curve']));
} else if($action == "claim_spin") {
    verifyCaptcha($_POST['captcha_challenge'], $_POST['captcha_response']);
    _respond((new \AllTheSatoshi\Faucet\SpinnerFaucet($mgr))->claim());
} else if($action == "payout") {
    if(!isset($_POST['utransserv'])) _respond(["message" => "Payment method was not given."]);
    verifyCaptcha($_POST['captcha_challenge'], $_POST['captcha_response']);
    _respond($mgr->payout($_POST['utransserv']));
}


_respond(["message" => "Something went wrong."]);


function _respond($response, $success = false) {
    if(!isset($response["success"])) $response["success"] = $success;
    header('Content-Type: application/json');
    die(json_encode($response));
}

function verifyCaptcha($challenge, $response) {
    $solvemedia = new \AllTheSatoshi\Util\SolveMedia('V6LOykA9eYCp4Avz38pUBmgekTkhKhPe', 'vw14n7mAbalPHj-7X29.xpwE1yCHaXku');
    $response = $solvemedia->verify($challenge, $response);
    if(!$response['success']) _respond($response);
}
?>