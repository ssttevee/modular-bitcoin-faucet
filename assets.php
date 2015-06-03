<?php

if(isset($_GET["module"]) && isset($_GET["file"])) {
    include "autoload.php";

    $module = get_module($_GET["module"]);
    if (isset($module)) {
        $file = $module->getViewFilePath($_GET["file"]);
        if(isset($file)) {
            $ext = pathinfo($_GET["file"], PATHINFO_EXTENSION);
            $mime = "text/plain";
            switch($ext) {
                case "js": $mime = "application/javascript"; break;
                case "css": $mime = "text/css"; break;
                case "map": $mime = "text/css"; break;
                case "scss": $mime = "text/scss"; break;
                case "php": header("HTTP/1.0 404 Not Found"); $file = "404 File Not Found."; break;
            }
            header("Content-Type: " . $mime);
            if(file_exists($file)) include($file);
            die(file_exists($file) ? "" : $file);
        }
    }
}


header("HTTP/1.0 404 Not Found");
header("Content-Type: text/plain");
die("404 File Not Found.");