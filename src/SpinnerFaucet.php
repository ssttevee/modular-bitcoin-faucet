<?php

namespace AllTheSatoshi\Faucet;

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
            return ["success" => false, "message" => "You have run out of tries. You can claim your current number or wait 10 minutes before spinning again.", "spin" => null, "tries" => $this->config["maxSpins"] - $lastSpin["tries"]];
        }

        $lastSpin["number"] = mt_rand() / mt_getrandmax() * $this->config["bonusChance"];
        $lastSpin["time"] = time();
        $lastSpin["tries"]++;
        $lastSpin["curve"] = $curve;

        $this->fm->lastSpin = $lastSpin;

        return ["success" => true, "message" => "You got " . ($lastSpin["number"] | 0) . "!", "spin" => $lastSpin["number"] | 0, "tries" => $this->config["maxSpins"] - $lastSpin["tries"]];
    }

    function claim() {
        $base = $this->config["baseAmt"];
        $max = $this->config["maxBonusAmt"];
        $chance = $this->config["bonusChance"];

        $formulas = array(
            "fractal" => 'return $base + ($max + $max/$chance)/($x/25 + 1) - $max/$chance;',
            "radical" => '$max /= 20;return $base - sqrt($max*$max/$chance*$x) + $max;',
        );

        $lastSpin = $this->fm->lastSpin;

        if(empty($lastSpin) || $lastSpin["number"] == null) {
            return ["success" => false, "amount" => 0, "message" => "no satoshi to claim"];
        } else {
            $x = $lastSpin["number"];

            $collection = $this->fm->db->selectCollection('spinner.history');
            $collection->insert(["address" => $this->fm->address, "time" => time(), "number" => $lastSpin["number"], "curve" => $lastSpin["curve"], "tries" => $lastSpin["tries"]]);

            $lastSpin["number"] = null;
            $lastSpin["tries"] = $this->config["maxSpins"];
            $lastSpin["claims"] += 1;

            $this->fm->lastSpin = $lastSpin;

            $amount = eval($formulas[$lastSpin["curve"]]);
            $this->fm->addBalance($amount);
            return ["success" => true, "amount" => $amount, "message" => "Successfully added " . $amount . " satoshi to your balance!"];
        }
    }
}