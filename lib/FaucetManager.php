<?php

class Manager {
    private $db;
    private $account;

    public $btcAddr;
    public $config;

    function __construct($btcAddress, $config = array("baseAmt" => 50, "maxBonusAmt" => 2000, "bonusChance" => 8000, "spinInterval" => 600, "maxSpins" => 3, "referralReward" => 0.1, "paytoshiApiKey" => "5yv5hbjulxthon78tgs6jq2d87q6ukbblhv7nbhuh8b383bi4l")) {
//        $mongo = new MongoClient();
        $mongo = new MongoClient('mongodb://admin:W-blx9dMT3xk@5550e877e0b8cd8cfa00016a-ssttevee.rhcloud.com:61276/');
        $this->db = $mongo->btcfaucet;
        $this->btcAddr = $btcAddress;
        $this->config = $config;

        $this->account = $this->getAccount();

        // refresh cookies
        setCookie('btcAddress', $this->btcAddr, time()+3600, '/');
        setCookie('satBalance', $this->getBalance(), time()+3600, '/');
    }

    function __destruct() {
        $this->db->users->update(["address" => $this->btcAddr], $this->account);
    }

    public function &__get($prop) {
        if(isset($this->$prop) || property_exists($this, $prop)) {
            return $this->$prop;
        } else {
            if(isset($this->account[$prop])) return $this->account[$prop];
            else if(in_array($prop, ["address", "referrer"])) $this->$prop = "";
            else if(in_array($prop, ["lastSpin"])) $this->$prop = [];
            else $this->$prop = 0;
            return $this->$prop;
        }
    }

    public function __set($prop, $val) {
        if(in_array($prop, ["db", "btcAddr", "config"])) $this->$prop = $val;
        $this->account[$prop] = $val;
    }

    private function getAccount() {
        $acc = $this->db->users->findOne(['address' => $this->btcAddr]);
        return empty($acc) ? $this->createAccount() : $acc;
    }

    private function createAccount() {
        $newUser = [
            "address" => $this->btcAddr,
            "created" => time(),
            "refbalance" => 0,
            "satbalance" => 0,
            "alltimeref" => 0,
            "alltimebal" => 0,
            "satspent" => 0,
            "satwithdrawn" => 0,
            "claimed" => 0,
            "referrer" => isset($_COOKIE['ref']) ? $_COOKIE['ref'] : '',
        ];
        if($this->db->users->insert($newUser)) return $newUser;
        die("failed to add user");
    }

    function getBalance() {
        return ($this->satbalance + $this->refbalance) | 0;
    }

    function getRemainingTries() {
        if(empty($this->lastSpin) || $this->lastSpin["time"] < time() - $this->config["spinInterval"]) return $this->config["maxSpins"];
        else return $this->maxSpins - $this->lastSpin["tries"];
    }

    function getWaitTime() {
        return $this->lastSpin["time"] - (time() - $this->config["spinInterval"]);
    }

    function getLastSpin() {
        return empty($this->lastSpin) ? 'null' : $this->lastSpin["number"] == null ? 'null' : $this->lastSpin["number"];
    }

    function spin() {
        $lastSpin = $this->lastSpin;

        if(empty($lastSpin)) {
            $lastSpin["tries"] = 0;
        } else if($lastSpin["time"] > time() - $this->config["spinInterval"] && $lastSpin["tries"] >= $this->config["maxSpins"] ||
            $lastSpin["time"] > time() - $this->config["spinInterval"] && $lastSpin["number"] == null) {
            return array("spin" => null, "tries" => $this->config["maxSpins"] - $lastSpin["tries"]);
        } else if($lastSpin["time"] < time() - $this->config["spinInterval"]) {
            $lastSpin["tries"] = 0;
        }

        $lastSpin["number"] = mt_rand(0, $this->config["bonusChance"]);
        $lastSpin["time"] = time();
        $lastSpin["tries"]++;

        $this->lastSpin = $lastSpin;

        return array("spin" => $this->lastSpin["number"], "tries" => $this->config["maxSpins"] - $this->lastSpin["tries"]);
    }

    function claimSpin($curve) {
        $base = $this->config["baseAmt"];
        $max = $this->config["maxBonusAmt"];
        $chance = $this->config["bonusChance"];

        $formulas = array(
            "fractal" => 'return $base + ($max + $max/$chance)/($x/5 + 1) - $max/$chance;',
            "radical" => '$max /= 20;return $base - sqrt($max*$max/$chance*$x) + $max;',
        );

        $lastSpin = $this->lastSpin;

        if(empty($lastSpin) || $lastSpin["number"] == null) {
            return array("added" => null, "balance" => $this->getBalance());
        } else {
            $x = $lastSpin["number"];

            $this->claims++;
            $lastSpin["number"] = null;
            $lastSpin["tries"] = $this->config["maxSpins"];

            $this->lastSpin = $lastSpin;

            $amount = eval($formulas[$curve]);
            $this->addBalance($amount);
            return array("added" => $amount, "balance" => $this->getBalance());
        }
    }

    function payout() {
        $paytoshi = new Paytoshi();
        $res = $paytoshi->faucetSend(
            $this->config["paytoshiApiKey"], //Faucet Api key
            $this->btcAddr, //Bitcoin address
            $this->getBalance(), //Amount
            $_SERVER['REMOTE_ADDR'] //Recipient ip
        );

        if(isset($res['error'])) return $res;

        $this->satbalance -= $res["amount"];
        $this->satwithdrawn += $res["amount"];

        return $res;
    }

    function addBalance($amount) {
        $this->satbalance += $amount;
        $this->alltimebal += $amount;
        $this->rewardReferrer($this->referrer, $amount);
    }

    function rewardReferrer($referrer, $amount) {
        $amount *= $this->config["referralReward"];
        if(isset($referrer) && strlen($referrer) > 0) {
            $ref = new Manager($referrer);
            $this->refbalance += $amount;
            $this->alltimeref += $amount;
        }
    }
}