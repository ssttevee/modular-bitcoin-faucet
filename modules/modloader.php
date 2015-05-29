<?php

function get_module($module) {
    $module = __DIR__ . "/" . $module . "/" . $module . ".php";
    if(file_exists($module)) return include($module);
    return null;
}