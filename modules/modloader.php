<?php

function get_module($module) {
    $module = __DIR__ . "/" . $module . "/" . $module . ".php";
    if(file_exists($module)) return include($module);
    return null;
}

function get_modules() {
    $modules = [];
    foreach(scandir("modules") as $dir) {
        if($dir != "." && $dir != ".." && is_dir("modules/" . $dir)) {
            $modules[] = $dir;
        }
    }
    return $modules;
}