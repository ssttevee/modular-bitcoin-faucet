<?php

namespace AllTheSatoshi\Faucet;

use AllTheSatoshi\FaucetManager;
use AllTheSatoshi\Util\Config as _c;

class SpinnerFaucet extends BaseFaucet {

    function __construct($btcAddress) {
        parent::__construct("rng_spinner", $btcAddress);
        $this->migrateDB();
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
        } else if ($action == "claim") {
            if(!$post["is_human"]) return "not_human";
            return $this->claim();
        }
        return "Action not allowed.";
    }

    function migrateDB() {
        $r = _c::getCollection('users')->findOne(["address" => $this->address], ["lastSpin"]);
        if(isset($r["lastSpin"])) {
            $r["lastSpin"]["time"] = new \MongoDate(0);
            $r["lastSpin"]["tries"] = 0;
            _c::getCollection("users")->update(["address" => $this->address], ['$unset' => ["lastSpin" => ""], '$set' => [$this->name => $r["lastSpin"]]]);
        }
    }

    function getRemainingTries() {
        return $this->_cfg("maxSpins") - $this->tries;
    }

    function isReady() {
        return $this->getWaitTime() <= 0;
    }

    function getWaitTime() {
        $time = $this->time;
        if(empty($time)) return 0;
        return $time->sec - (time() - _c::ini("general","dispenseInterval"));
    }

    function spin($curve) {
        if($this->getWaitTime() > 0) {
            return ["message" => "You have already collected satoshi from this minigame, try again in 10 minutes.", "spin" => null, "tries" => $this->getRemainingTries()];
        } else if($this->getRemainingTries() < 1) {
            return ["message" => "You have run out of tries, please collect your satoshi.", "spin" => null, "tries" => $this->getRemainingTries()];
        }

        $this->number = mt_rand() / mt_getrandmax() * $this->_cfg("bonusChance");
        $this->tries++;
        $this->curve = $curve;

        return ["success" => true, "message" => "You got " . ($this->number | 0) . "!", "spin" => $this->number | 0, "tries" => $this->getRemainingTries()];
    }

    function satoshi() {
        $base = $this->_cfg("baseAmt");
        $max = $this->_cfg("maxBonusAmt");
        $chance = $this->_cfg("bonusChance");
        $x = $this->number;

        if($x == null) return 0;

        $formulas = array(
            "fractal" => 'return $base + ($max + $max/$chance)/($x/25 + 1) - $max/$chance;',
            "radical" => '$max /= 20;return $base - sqrt($max*$max/$chance*$x) + $max;',
        );

        return eval($formulas[$this->curve]);
    }

    function claim() {
        if($this->number == null) {
            return ["success" => false, "amount" => 0, "message" => "no satoshi to claim"];
        } else {
            $amount = $this->satoshi();

            $collection = _c::getCollection('spinner.history');
            $collection->insert(["address" => $this->address, "time" => new \MongoDate(), "number" => $this->number, "curve" => $this->curve, "tries" => $this->tries]);

            $this->number = null;
            $this->tries = 0;
            $this->claims += 1;
            $this->time = new \MongoDate();

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