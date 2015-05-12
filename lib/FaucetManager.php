<?php

class Manager {
    private $db;

    public $btcAddr;
    public $config;

    function __construct($btcAddress, $config = array("baseAmt" => 50, "maxBonusAmt" => 2000, "bonusChance" => 8000, "spinInterval" => 600, "maxSpins" => 3, "paytoshiApiKey" => "5yv5hbjulxthon78tgs6jq2d87q6ukbblhv7nbhuh8b383bi4l")) {
//        $mongo = new MongoClient();
        $mongo = new MongoClient('mongodb://admin:W-blx9dMT3xk@5550e877e0b8cd8cfa00016a-ssttevee.rhcloud.com:61276/');
        $this->db = $mongo->btcfaucet;
        $this->btcAddr = $btcAddress;
        $this->config = $config;
        $this->filter = array('address' => $this->btcAddr);

        // refresh cookies
        setCookie('btcAddress', $this->btcAddr, time()+3600, '/');
        setCookie('satBalance', $this->getBalance(), time()+3600, '/');
    }

    private function createAccount() {
        $newUser = array(
            "address" => $this->btcAddr,
            "created" => time(),
            "satbalance" => 0,
            "alltimebal" => 0,
            "satspent" => 0,
            "satwithdrawn" => 0,
            "referrer" => isset($_COOKIE['ref']) ? $_COOKIE['ref'] : '',
        );
        if($this->db->users->insert($newUser)) return $newUser;
        die("failed to add new user");
    }

    function getBalance() {
        $cursor = $this->db->users->findOne($this->filter, array('satbalance'));

        if($cursor == null) {
            $this->createAccount();
            return $this->getBalance();
        } else {
            return $cursor['satbalance'] | 0;
        }
    }

    function getRemainingTries() {
        $cursor = $this->db->users->findOne($this->filter, array('lastSpin'));
        if(!isset($cursor["lastSpin"]) || $cursor["lastSpin"]["time"] < time() - $this->config["spinInterval"]) return $this->config["maxSpins"];
        else return $this->config["maxSpins"] - $cursor["lastSpin"]["tries"];
    }

    function getWaitTime() {
        $cursor = $this->db->users->findOne($this->filter, array('lastSpin.time'));
        return $cursor["lastSpin"]["time"] - (time() - $this->config["spinInterval"]);
    }

    function getLastSpin() {
        $cursor = $this->db->users->findOne($this->filter, array('lastSpin.number'));
        return !isset($cursor["lastSpin"]) ? 'null' : $cursor["lastSpin"]["number"] == null ? 'null' : $cursor["lastSpin"]["number"];
    }

    function spin() {
        $cursor = $this->db->users->findOne($this->filter, array('lastSpin'));

        if(!isset($cursor["lastSpin"])) {
            $cursor["lastSpin"]["tries"] = 0;
        } else if($cursor["lastSpin"]["time"] > time() - $this->config["spinInterval"] && $cursor["lastSpin"]["tries"] >= $this->config["maxSpins"] ||
            $cursor["lastSpin"]["time"] > time() - $this->config["spinInterval"] && $cursor["lastSpin"]["number"] == null) {
            return array("spin" => null, "tries" => $this->config["maxSpins"] - $cursor["lastSpin"]["tries"]);
        } else if($cursor["lastSpin"]["time"] < time() - $this->config["spinInterval"]) {
            $cursor["lastSpin"]["tries"] = 0;
        }

        $cursor["lastSpin"]["number"] = mt_rand(0, $this->config["bonusChance"]);
        $cursor["lastSpin"]["time"] = time();
        $cursor["lastSpin"]["tries"]++;
        $this->db->users->update($this->filter, array('$set' => array("lastSpin" => $cursor["lastSpin"])));

        return array("spin" => $cursor["lastSpin"]["number"], "tries" => $this->config["maxSpins"] - $cursor["lastSpin"]["tries"]);
    }

    function claimSpin($curve) {
        $base = $this->config["baseAmt"];
        $max = $this->config["maxBonusAmt"];
        $chance = $this->config["bonusChance"];

        $formulas = array(
            "fractal" => 'return $base + ($max + $max/$chance)/($x/5 + 1) - $max/$chance;',
            "radical" => '$max /= 20;return $base - sqrt($max*$max/$chance*$x) + $max;',
        );
        $cursor = $this->db->users->findOne($this->filter, array('lastSpin.number'));
        if(!isset($cursor["lastSpin"]) || $cursor["lastSpin"]["number"] == null) return array("added" => null, "balance" => $this->getBalance());
        else {
            $x = $cursor["lastSpin"]["number"];
            $this->db->users->update($this->filter, array('$set' => array('lastSpin.number' => null,'lastSpin.tries' => $this->config["maxSpins"])));
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

        $cursor = $this->db->users->findOne($this->filter, array('satbalance','satwithdrawn'));
        $cursor["satbalance"] -= $res["amount"];
        $cursor["satwithdrawn"] += $res["amount"];
        $this->db->users->update($this->filter, array('$set' => array("satbalance" => $cursor["satbalance"], "satwithdrawn" => $cursor["satwithdrawn"])));

        return $res;
    }

    function addBalance($amount) {
        $cursor = $this->db->users->findOne($this->filter, array('satbalance','alltimebal'));
        $cursor["satbalance"] += $amount;
        $cursor["alltimebal"] += $amount;
        $this->db->users->update($this->filter, array('$set' => array("satbalance" => $cursor["satbalance"], "alltimebal" => $cursor["alltimebal"])));
    }
}