<?php

namespace AllTheSatoshi\Util;

class Module {

    public $name;
    public $dir;
    public $class;

    function __construct($dir, $name, $class) {
        $this->dir = $dir;
        $this->name = $name;
        $this->class = $class;
    }

    function getUrlSlug() {
        if(isset($this->slug)) return $this->slug;
        return str_replace(" ", "-", strtolower($this->name));
    }

    function getFaucetInstance($btcAddress) {
        if(isset($this->faucet)) return $this->faucet;

        $cls = $this->class;
        require_once $this->dir . "/" . $cls . ".php";
        return $this->faucet = new $cls($btcAddress);
    }

    function getViewFilePath($file) {
        $file = $this->dir . "/view/" . $file;
        if(file_exists($file)) return $file;
        else return null;
    }

    function getViewFile($file) {
        $file = $this->getViewFilePath($file);
        if(isset($file)) return file_get_contents($file);
        else return null;
    }

    function getPageResources() {
        $resources = [];
        foreach(scandir($this->dir . "/view") as $file) {
            if(in_array(pathinfo($file, PATHINFO_EXTENSION), ["js", "css"])) $resources[] = $file;
        }
        return $resources;
    }

}