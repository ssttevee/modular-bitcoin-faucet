<?php

namespace AllTheSatoshi\Faucet;

use AllTheSatoshi\FaucetManager;
use AllTheSatoshi\Util\Config as _c;

class SpinnerFaucet extends BaseFaucet {

    function __construct($btcAddress) {
        parent::__construct("lastSpin", $btcAddress);
    }

    function __get($prop) {
        if($prop == 'tries_left') return $this->getRemainingTries();

        $val = parent::__get($prop);

        if($val == null) {
            if ($prop == 'curve') return 'fractal';
        }

        return $val;
    }
    
    function _cfg($property) {
        return _c::ini("spinner_faucet", $property);
    }

    function ajax($action, $post) {
        if ($action == "spin") {
            if(array_key_exists("curve", $post)) return $this->spin($post['curve']);
            else return "Curve was not specified.";
        } else if ($action == "claim_spin") {
            if(!$post["is_human"]) return "not_human";
            return $this->claim();
        }
        return "Action not allowed.";
    }

    function getRemainingTries() {
        if($this->time == null || $this->time < time() - $this->_cfg("spinInterval")) return $this->_cfg("maxSpins");
        else return $this->_cfg("maxSpins") - $this->tries;
    }

    function getWaitTime() {
        return $this->time - (time() - $this->_cfg("spinInterval"));
    }

    function spin($curve) {
        if($this->time == null || $this->time < time() - $this->_cfg("spinInterval")) {
            $this->tries = 0;
        } else if($this->time > time() - $this->_cfg("spinInterval") && $this->tries >= $this->_cfg("maxSpins") ||
            $this->time > time() - $this->_cfg("spinInterval") && $this->number == null) {
            return ["success" => false, "message" => "You have run out of tries. You can claim your current number or wait 10 minutes before spinning again.", "spin" => null, "tries" => $this->_cfg("maxSpins") - $this->tries];
        }

        $this->number = mt_rand() / mt_getrandmax() * $this->_cfg("bonusChance");
        $this->time = time();
        $this->tries++;
        $this->curve = $curve;

        return ["success" => true, "message" => "You got " . ($this->number | 0) . "!", "spin" => $this->number | 0, "tries" => $this->_cfg("maxSpins") - $this->tries];
    }

    function claim() {
        $base = $this->_cfg("baseAmt");
        $max = $this->_cfg("maxBonusAmt");
        $chance = $this->_cfg("bonusChance");

        $formulas = array(
            "fractal" => 'return $base + ($max + $max/$chance)/($x/25 + 1) - $max/$chance;',
            "radical" => '$max /= 20;return $base - sqrt($max*$max/$chance*$x) + $max;',
        );

        if($this->number == null) {
            return ["success" => false, "amount" => 0, "message" => "no satoshi to claim"];
        } else {
            $x = $this->number;

            $collection = _c::getCollection('spinner.history');
            $collection->insert(["address" => $this->address, "time" => time(), "number" => $this->number, "curve" => $this->curve, "tries" => $this->tries]);

            $this->number = null;
            $this->tries = $this->_cfg("maxSpins");
            $this->claims += 1;

            $amount = eval($formulas[$this->curve]);
            FaucetManager::_($this->address)->addBalance($amount);
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