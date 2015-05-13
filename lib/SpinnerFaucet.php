<?php

class SpinnerFaucet {

    public $config = [
        "baseAmt" => 50,
        "maxBonusAmt" => 2000,
        "bonusChance" => 8000,
        "spinInterval" => 600,
        "maxSpins" => 3,
    ];

    private $fm;

    function __construct($faucet_manager) {
        $this->fm = $faucet_manager;
    }

    function __get($prop) {
        if($prop == 'lastNumber') return isset($this->fm->lastSpin["number"]) ? $this->fm->lastSpin["number"] : 'null';
        if($prop == 'curve') return isset($this->fm->lastSpin["curve"]) ? $this->fm->lastSpin["curve"] : 'fractal';
        if($prop == 'tries_left') return $this->getRemainingTries();
    }

    function getRemainingTries() {
        $lastSpin = $this->fm->lastSpin;
        if(empty($lastSpin) || $lastSpin["time"] < time() - $this->config["spinInterval"]) return $this->config["maxSpins"];
        else return $this->config["maxSpins"] - $lastSpin["tries"];
    }

    function getWaitTime() {
        return $this->fm->lastSpin["time"] - (time() - $this->config["spinInterval"]);
    }

    function spin($curve) {
        $lastSpin = $this->fm->lastSpin;

        if(empty($lastSpin) || $lastSpin["time"] < time() - $this->config["spinInterval"]) {
            $lastSpin["tries"] = 0;
        } else if($lastSpin["time"] > time() - $this->config["spinInterval"] && $lastSpin["tries"] >= $this->config["maxSpins"] ||
            $lastSpin["time"] > time() - $this->config["spinInterval"] && $lastSpin["number"] == null) {
            return array("spin" => null, "tries" => $this->config["maxSpins"] - $lastSpin["tries"]);
        }

        $lastSpin["number"] = mt_rand() / mt_getrandmax() * $this->config["bonusChance"];
        $lastSpin["time"] = time();
        $lastSpin["tries"]++;
        $lastSpin["curve"] = $curve;

        $this->fm->lastSpin = $lastSpin;

        return array("spin" => $lastSpin["number"] | 0, "tries" => $this->config["maxSpins"] - $lastSpin["tries"]);
    }

    function claim() {
        $base = $this->config["baseAmt"];
        $max = $this->config["maxBonusAmt"];
        $chance = $this->config["bonusChance"];

        $formulas = array(
            "fractal" => 'return $base + ($max + $max/$chance)/($x/5 + 1) - $max/$chance;',
            "radical" => '$max /= 20;return $base - sqrt($max*$max/$chance*$x) + $max;',
        );

        $lastSpin = $this->fm->lastSpin;

        if(empty($lastSpin) || $lastSpin["number"] == null) {
            return array("added" => null, "balance" => $this->fm->getBalance());
        } else {
            $x = $lastSpin["number"];

            $lastSpin["number"] = null;
            $lastSpin["tries"] = $this->config["maxSpins"];
            $lastSpin["claims"] += 1;

            $this->fm->lastSpin = $lastSpin;

            $amount = eval($formulas[$lastSpin["curve"]]);
            $this->fm->addBalance($amount);
            return array("added" => $amount, "balance" => $this->fm->getBalance());
        }
    }
}