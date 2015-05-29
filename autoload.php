<?php

require_once("modules/modloader.php");
require_once("src/AdManager.php");
spl_autoload_register(function($class) {
    $namespace = explode("\\", $class);
    require_once(__DIR__ . "/src/" . $namespace[count($namespace) - 1] . ".php");
});