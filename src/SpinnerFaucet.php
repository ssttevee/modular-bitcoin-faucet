<?php

namespace AllTheSatoshi\Faucet;

use AllTheSatoshi\Util\Config as _c;

class SpinnerFaucet {

    private $fm;

    function __construct($faucet_manager) {
        $this->fm = $faucet_manager;
    }

    function __get($prop) {
        if($prop == 'lastNumber') return isset($this->fm->lastSpin["number"]) ? $this->fm->lastSpin["number"] : 'null';
        if($prop == 'curve') return isset($this->fm->lastSpin["curve"]) ? $this->fm->lastSpin["curve"] : 'fractal';
        if($prop == 'tries_left') return $this->getRemainingTries();
    }
    
    function _cfg($property) {
        return _c::ini("spinner_faucet", $property);
    }

    function getRemainingTries() {
        $lastSpin = $this->fm->lastSpin;
        if(empty($lastSpin) || $lastSpin["time"] < time() - $this->_cfg("spinInterval")) return $this->_cfg("maxSpins");
        else return $this->_cfg("maxSpins") - $lastSpin["tries"];
    }

    function getWaitTime() {
        return $this->fm->lastSpin["time"] - (time() - $this->_cfg("spinInterval"));
    }

    function spin($curve) {
        $lastSpin = $this->fm->lastSpin;

        if(empty($lastSpin) || $lastSpin["time"] < time() - $this->_cfg("spinInterval")) {
            $lastSpin["tries"] = 0;
        } else if($lastSpin["time"] > time() - $this->_cfg("spinInterval") && $lastSpin["tries"] >= $this->_cfg("maxSpins") ||
            $lastSpin["time"] > time() - $this->_cfg("spinInterval") && $lastSpin["number"] == null) {
            return ["success" => false, "message" => "You have run out of tries. You can claim your current number or wait 10 minutes before spinning again.", "spin" => null, "tries" => $this->_cfg("maxSpins") - $lastSpin["tries"]];
        }

        $lastSpin["number"] = mt_rand() / mt_getrandmax() * $this->_cfg("bonusChance");
        $lastSpin["time"] = time();
        $lastSpin["tries"]++;
        $lastSpin["curve"] = $curve;

        $this->fm->lastSpin = $lastSpin;

        return ["success" => true, "message" => "You got " . ($lastSpin["number"] | 0) . "!", "spin" => $lastSpin["number"] | 0, "tries" => $this->_cfg("maxSpins") - $lastSpin["tries"]];
    }

    function claim() {
        $base = $this->_cfg("baseAmt");
        $max = $this->_cfg("maxBonusAmt");
        $chance = $this->_cfg("bonusChance");

        $formulas = array(
            "fractal" => 'return $base + ($max + $max/$chance)/($x/25 + 1) - $max/$chance;',
            "radical" => '$max /= 20;return $base - sqrt($max*$max/$chance*$x) + $max;',
        );

        $lastSpin = $this->fm->lastSpin;

        if(empty($lastSpin) || $lastSpin["number"] == null) {
            return ["success" => false, "amount" => 0, "message" => "no satoshi to claim"];
        } else {
            $x = $lastSpin["number"];

            $collection = _c::getCollection('spinner.history');
            $collection->insert(["address" => $this->fm->address, "time" => time(), "number" => $lastSpin["number"], "curve" => $lastSpin["curve"], "tries" => $lastSpin["tries"]]);

            $lastSpin["number"] = null;
            $lastSpin["tries"] = $this->_cfg("maxSpins");
            $lastSpin["claims"] += 1;

            $this->fm->lastSpin = $lastSpin;

            $amount = eval($formulas[$lastSpin["curve"]]);
            $this->fm->addBalance($amount);
            return ["success" => true, "amount" => $amount, "message" => "Successfully added " . $amount . " satoshi to your balance!"];
        }
    }

    function __stats() {
        $stats = [];

        $collection = _c::getCollection('spinner.history');
        $stats["fractal_count"] = $collection->count(["curve" => "fractal"]);
        $stats["radical_count"] = $collection->count(["curve" => "radical"]);
        $cursor = $collection->find([], ["address", "number", "tries", "time", "curve"]);

        $addrs = [];
        $stats["lowest_number"] = 8888;
        $stats["avg_number"] = 0;
        $stats["avg_fractal_number"] = 0;
        $stats["avg_radical_number"] = 0;
        $stats["avg_tries"] = 0;
        $stats["latest_dispense_time"] = 0;
        foreach($cursor as $entry) {
            if(!isset($addrs[$entry["address"]])) $addrs[$entry["address"]] = 0;
            $addrs[$entry["address"]]++;

            if($entry["number"] < $stats["lowest_number"]) {
                $stats["lowest_number"] = $entry["number"];
                $stats["lowest_number_addr"] = $entry["address"];
                $stats["lowest_number_curve"] = $entry["curve"];
            }
            $stats["avg_number"] = ($stats["avg_number"] + $entry["number"]) / ($stats["avg_number"] == 0 ? 1 : 2);
            $stats["avg_" . $entry["curve"] . "_number"] = ($stats["avg_" . $entry["curve"] . "_number"] + $entry["number"]) / ($stats["avg_" . $entry["curve"] . "_number"] == 0 ? 1 : 2);
            $stats["avg_tries"] = ($stats["avg_tries"] + $entry["tries"]) / ($stats["avg_tries"] == 0 ? 1 : 2);
            if($entry["time"] > $stats["latest_dispense_time"]) $stats["latest_dispense_time"] = $entry["time"];
        }

        $stats["most_active_count"] = 0;
        foreach($addrs as $addr => $count) {
            if($count > $stats["most_active_count"]) {
                $stats["most_active_addr"] = $addr;
                $stats["most_active_count"] = $count;
            }
        }

        return $stats;
    }
}