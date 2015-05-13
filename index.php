<?php
//error_reporting(E_ALL);
//ini_set('display_errors', 1);

require "./lib/AdManager.php";
require "./lib/FaucetManager.php";

if(!isset($_GET["page"])) $_GET["page"] = "index";

if(isset($_GET['r'])) if((new Manager($_GET['r']))->claimed > 0) setcookie('ref', $_GET['r'], time() + 3600, '/');
if(isset($_COOKIE['btcAddress'])) $mgr = new Manager($_COOKIE['btcAddress']);

include "template/header.inc";
if(!isset($_COOKIE['btcAddress'])) include "pages/login.inc";
else include "pages/" . $_GET["page"] . ".inc";
include "template/footer.inc";

?>