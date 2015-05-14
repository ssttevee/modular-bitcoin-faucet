<?php

namespace AllTheSatoshi;

class FaucetManager {
    private $account;

    public $db;
    public $address;
    public $config = [
        "localtesting" => false,
        "referralReward" => 0.1,
        "paytoshiApiKey" => "5yv5hbjulxthon78tgs6jq2d87q6ukbblhv7nbhuh8b383bi4l",
        "faucetBoxApiKey" => "AN5BvrbqHul2ARpXQRflZYM6Tiloh",
    ];

    function __construct($btcAddress, $config = array()) {
//        $mongo = new \MongoClient();
        $mongo = new \MongoClient('mongodb://admin:W-blx9dMT3xk@5550e877e0b8cd8cfa00016a-ssttevee.rhcloud.com:61276/');
        $this->db = $mongo->btcfaucet;
        $this->address = $btcAddress;

        foreach ($config as $key => $value)
            $this->config[$key] = $value;

        $this->account = $this->getAccount();

        // refresh cookies
        setCookie('btcAddress', $this->address, time()+3600, '/');
        setCookie('satBalance', $this->getBalance(), time()+3600, '/');
    }

    public function __destruct() {
        $this->db->users->update(["address" => $this->address], $this->account);
    }

    public function __get($prop) {
        if(isset($this->account[$prop])) return $this->account[$prop];
        else if(in_array($prop, ["address", "referrer"])) $this->$prop = "";
        else if(in_array($prop, ["curve"])) $this->$prop = "radical";
        else if(in_array($prop, ["lastSpin"])) $this->$prop = [];
        else $this->$prop = 0;
        return $this->__get($prop);
    }

    public function __set($prop, $val) {
        $this->account[$prop] = $val;
    }

    private function getAccount() {
        $acc = $this->db->users->findOne(['address' => $this->address]);
        return empty($acc) ? $this->createAccount() : $acc;
    }

    private function createAccount() {
        if(\LinusU\Bitcoin\AddressValidator::isValid($_POST['btcAddress'])) {
            $newUser = [
                "address" => $this->address,
                "created" => time(),
                "refbalance" => 0,
                "satbalance" => 0,
                "alltimeref" => 0,
                "alltimebal" => 0,
                "satspent" => 0,
                "satwithdrawn" => 0,
                "referrer" => isset($_COOKIE['ref']) || $_COOKIE['ref'] == $this->address ? $_COOKIE['ref'] : '',
            ];
            if ($this->db->users->insert($newUser)) return $newUser;
            die("failed to add user");
        } else {
            die("invalid bitcoin address");
        }
    }

    function getBalance() {
        return ($this->satbalance + $this->refbalance) | 0;
    }

    function payout($service, $referral = false) {
        if(isset($_SERVER['HTTP_CF_CONNECTING_IP'])) $_SERVER['REMOTE_ADDR'] = $_SERVER['HTTP_CF_CONNECTING_IP'];
        if(!in_array($service, ["paytoshi", "faucetbox"])) return ["success" => false, "message" => "Payment service was not given."];
        if($this->satbalance < 1 && $this->refbalance < 1) return ["success" => false, "message" => "Your account balance is zero."];

        $amount_to_send = ($referral ? $this->refbalance : $this->satbalance) | 0;
        if(!$this->config["localtesting"] && /*$this->address != '1AjefAG7Nibj8HzY3syMSN5iHWDkwZa5KN' &&*/ $amount_to_send > 0) {
            if ($service == 'paytoshi') {
                $paytoshi = new Payment\Paytoshi();
                $res = $paytoshi->faucetSend($this->config["paytoshiApiKey"], $this->address, $amount_to_send, $_SERVER['REMOTE_ADDR'], $referral);
                if (isset($res['error'])) return array_merge($res, ["success" => false]);
            } else if ($service == 'faucetbox') {
                $faucetbox = new Payment\FaucetBOX($this->config["faucetBoxApiKey"]);
                $res = $faucetbox->send($this->address, $amount_to_send, (string) $referral);
                if (!$res["success"]) return ["success" => false, "message" => $res["message"]];
            }
        } else $res = ["success" => true];

        $satoshi_sent = $amount_to_send;

        $this->satwithdrawn += $satoshi_sent;
        if($referral) $this->refbalance -= $satoshi_sent;
        else $this->satbalance -= $satoshi_sent;

        if($satoshi_sent > 0) {
            $this->db->selectCollection('payouts')->insert(["address" => $this->address, "amount" => $satoshi_sent, "service" => $service, "referral" => $referral, "time" => time()]);
        }

        if (!$referral && $this->refbalance >= 1) {
            $refres = $this->payout($service, true);
            if($refres["success"]) {
                $satoshi_sent += $refres["amount"];
            }
        }

        $service_check_url = [
            'paytoshi' => "<a ng-href=\"https://paytoshi.org/{{btcAddress}}/balance\" target=\"_blank\">Paytoshi</a>",
            'faucetbox' => "<a ng-href=\"https://faucetbox.com/en/check/{{btcAddress}}\" target=\"_blank\">FaucetBOX</a>",
        ];

        if($this->config["localtesting"]) return ["success" => true, "message" => ($satoshi_sent) . " satoshi was sent to your " . $service_check_url[$service] . " account!"];
        else if($service == 'paytoshi' && isset($res["error"]) && $res["error"]) return ["success" => false, "message" => $res["message"]];
        else if($service == 'faucetbox' && !$res["success"]) return ["success" => false, "message" => $res["message"]];
        else return ["success" => true, "message" => ($satoshi_sent) . " satoshi was sent to your " . $service_check_url[$service] . " account!", "amount" => $satoshi_sent];
    }

    function addBalance($amount) {
        $this->satbalance += $amount;
        $this->alltimebal += $amount;
        $this->rewardReferrer($this->referrer, $amount);
    }

    function rewardReferrer($referrer, $amount) {
        $amount *= $this->config["referralReward"];
        if(isset($referrer) && strlen($referrer) > 0) {
            $ref = new FaucetManager($referrer);
            $ref->refbalance += $amount;
            $ref->alltimeref += $amount;
        }
    }
}