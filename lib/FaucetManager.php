<?php

class Manager {
    private $db;
    private $account;

    public $btcAddr;
    public $config = [
        "baseAmt" => 50,
        "maxBonusAmt" => 2000,
        "bonusChance" => 8000,
        "spinInterval" => 600,
        "maxSpins" => 3,
        "referralReward" => 0.1,
        "paytoshiApiKey" => "5yv5hbjulxthon78tgs6jq2d87q6ukbblhv7nbhuh8b383bi4l",
        "faucetBoxApiKey" => "AN5BvrbqHul2ARpXQRflZYM6Tiloh",
    ];

    function __construct($btcAddress, $config = array()) {
//        $mongo = new MongoClient();
        $mongo = new MongoClient('mongodb://admin:W-blx9dMT3xk@5550e877e0b8cd8cfa00016a-ssttevee.rhcloud.com:61276/');
        $this->db = $mongo->btcfaucet;
        $this->btcAddr = $btcAddress;

        foreach ($config as $key => $value)
            $this->config[$key] = $value;

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
            else if(in_array($prop, ["curve"])) $this->$prop = "radical";
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
        $lastSpin = $this->lastSpin;
        if(empty($lastSpin) || $lastSpin["time"] < time() - $this->config["spinInterval"]) return $this->config["maxSpins"];
        else return $this->config["maxSpins"] - $lastSpin["tries"];
    }

    function getWaitTime() {
        return $this->lastSpin["time"] - (time() - $this->config["spinInterval"]);
    }

    function spin($curve) {
        $lastSpin = $this->lastSpin;

        if(empty($lastSpin)) {
            $lastSpin["tries"] = 0;
        } else if($lastSpin["time"] > time() - $this->config["spinInterval"] && $lastSpin["tries"] >= $this->config["maxSpins"] ||
            $lastSpin["time"] > time() - $this->config["spinInterval"] && $lastSpin["number"] == null) {
            return array("spin" => null, "tries" => $this->config["maxSpins"] - $lastSpin["tries"]);
        } else if($lastSpin["time"] < time() - $this->config["spinInterval"]) {
            $lastSpin["tries"] = 0;
        }

        $lastSpin["number"] = mt_rand() / mt_getrandmax() * $this->config["bonusChance"];
        $lastSpin["time"] = time();
        $lastSpin["tries"]++;
        $lastSpin["curve"] = $curve;

        $this->lastSpin = $lastSpin;

        return array("spin" => $lastSpin["number"] | 0, "tries" => $this->config["maxSpins"] - $lastSpin["tries"]);
    }

    function claimSpin() {
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

            $amount = eval($formulas[$lastSpin["curve"]]);
            $this->addBalance($amount);
            return array("added" => $amount, "balance" => $this->getBalance());
        }
    }

    function payout($service) {
        if(isset($_SERVER['HTTP_CF_CONNECTING_IP'])) $_SERVER['REMOTE_ADDR'] = $_SERVER['HTTP_CF_CONNECTING_IP'];
        if(!in_array($service, ["paytoshi", "faucetbox"])) return ["success" => false, "message" => "no payment service specified"];
        if($this->satbalance < 1) return ["success" => false, "message" => "account balance is empty"];

        if($service == 'paytoshi') {
            $paytoshi = new Paytoshi();
            $res = $paytoshi->faucetSend($this->config["paytoshiApiKey"], $this->btcAddr, ($this->satbalance | 0), $_SERVER['REMOTE_ADDR']);
            if (isset($res['error'])) return $res;
        } else if($service == 'faucetbox') {
            $faucetbox = new FaucetBOX($this->config["faucetBoxApiKey"]);

            $res = $faucetbox->send($this->btcAddr, ($this->satbalance | 0));
            if(!$res["success"]) return ["success" => false, "message" => $res["message"]];
        }

        if($this->refbalance >= 1) {
            if ($service == 'paytoshi') {
                $res = $paytoshi->faucetSend($this->config["paytoshiApiKey"], $this->btcAddr, ($this->refbalance | 0), $_SERVER['REMOTE_ADDR'], true);
                if (isset($res['error'])) return $res;
            } else if ($service == 'faucetbox') {
                $res = $faucetbox->send($this->btcAddr, ($this->refbalance | 0), "true");
                if (!$res["success"]) return ["success" => false, "message" => $res["message"]];
            }
        }

        $satoshi_sent = $this->satbalance | 0;
        $rewards_sent = $this->refbalance | 0;

        $this->satwithdrawn += $satoshi_sent + $rewards_sent;
        $this->satbalance -= $satoshi_sent;
        $this->refbalance -= $rewards_sent;

        $service_check_url = [
            'paytoshi' => "<a ng-href=\"https://paytoshi.org/{{btcAddress}}/balance\">Paytoshi</a>",
            'faucetbox' => "<a ng-href=\"https://faucetbox.com/en/check/{{btcAddress}}\">FaucetBOX</a>",
        ];

        if($service == 'paytoshi' && isset($res["error"]) && $res["error"]) return ["success" => false, "message" => $res["message"]];
        else if($service == 'faucetbox' && !$res["success"]) return ["success" => false, "message" => $res["message"]];
        else return ["success" => true, "message" => ($satoshi_sent + $rewards_sent) . " satoshi was sent to your " . $service_check_url[$service] . " account!"];
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
            $ref->refbalance += $amount;
            $ref->alltimeref += $amount;
        }
    }
}