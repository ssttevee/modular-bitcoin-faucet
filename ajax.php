<?php

require_once __DIR__ . "/vendor/autoload.php";


$allowed_actions = ["spin", "claim_spin", "payout"];


if(!isset($_COOKIE['btcAddress'])) _respond(["error" => "You're not logged in."], false);
else if(!isset($_GET["action"])) _respond(["message" => "Action was not given."], false);
else if(!in_array($_GET["action"], $allowed_actions)) _respond(["message" => "Given action is not allowed."], false);


$action = $_GET["action"];
$mgr = new \AllTheSatoshi\FaucetManager($_COOKIE['btcAddress']);


if($action == "spin") {
    _respond((new \AllTheSatoshi\Faucet\SpinnerFaucet($mgr))->spin($_POST['curve']));
} else if($action == "claim_spin") {
    verifyCaptcha($_POST['g-recaptcha-response']);
    _respond((new \AllTheSatoshi\Faucet\SpinnerFaucet($mgr))->claim());
} else if($action == "payout") {
    if(!isset($_POST['utransserv'])) _respond(["message" => "Payment method was not given."]);
    verifyCaptcha($_POST['g-recaptcha-response']);
    _respond($mgr->payout($_POST['utransserv']));
}


_respond(["message" => "Something went wrong."], false);


function _respond($response, $success = false) {
    if(!isset($response["success"])) $response["success"] = $success;
    header('Content-Type: application/json');
    die(json_encode($response));
}

function verifyCaptcha($input) {
    $recaptcha = new \AllTheSatoshi\Util\ReCaptcha('6LdzugYTAAAAAAfTBw3_BqbRpeAywMkpL-NzdEp9');
    $response = $recaptcha->verify($input);

    if(!$response['success']) _respond(["message" => $recaptcha->getMessage($response["error-codes"][0])]);
}
?>