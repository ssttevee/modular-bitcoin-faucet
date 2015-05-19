<?php

namespace AllTheSatoshi;

use AllTheSatoshi\Util\Config as _c;

class FaucetManager {
    private $account;

    public $address;

    function __construct($btcAddress) {
        $this->address = $btcAddress;
        $this->account = $this->getAccount();

        // refresh cookies
        setCookie('btcAddress', $this->address, time()+3600, '/');
        setCookie('satBalance', $this->getBalance(), time()+3600, '/');
    }

    public function __destruct() {
        _c::getCollection('users')->update(["address" => $this->address], $this->account);
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
        $acc = _c::getCollection('users')->findOne(['address' => $this->address]);
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
            if (_c::getCollection('users')->insert($newUser)) return $newUser;
            throw new \Exception("Failed to add user.");
        } else {
            throw new \Exception("Bitcoin address is invalid.");
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
        if($amount_to_send > 0) {
            if ($service == 'paytoshi') {
                $paytoshi = new Payment\Paytoshi();
                $res = $paytoshi->faucetSend(_c::ini("payout_services", "paytoshiApiKey"), $this->address, $amount_to_send, $_SERVER['REMOTE_ADDR'], $referral);
                if (isset($res['error'])) return array_merge($res, ["success" => false]);
            } else if ($service == 'faucetbox') {
                $faucetbox = new Payment\FaucetBOX(_c::ini("payout_services", "faucetboxApiKey"));
                $res = $faucetbox->send($this->address, $amount_to_send, (string) $referral);
                if (!$res["success"]) return ["success" => false, "message" => $res["message"]];
            }
        } else $res = ["success" => true];

        $satoshi_sent = $amount_to_send;

        $this->satwithdrawn += $satoshi_sent;
        if($referral) $this->refbalance -= $satoshi_sent;
        else $this->satbalance -= $satoshi_sent;

        if($satoshi_sent > 0) {
            _c::getCollection('payouts')->insert(["address" => $this->address, "amount" => $satoshi_sent, "service" => $service, "referral" => $referral, "time" => time()]);
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

        if($service == 'paytoshi' && isset($res["error"]) && $res["error"]) return ["success" => false, "message" => $res["message"]];
        else if($service == 'faucetbox' && !$res["success"]) return ["success" => false, "message" => $res["message"]];
        else return ["success" => true, "message" => ($satoshi_sent) . " satoshi was sent to your " . $service_check_url[$service] . " account!", "amount" => $satoshi_sent];
    }

    function addBalance($amount) {
        $this->satbalance += $amount;
        $this->alltimebal += $amount;
        $this->rewardReferrer($this->referrer, $amount);
    }

    function rewardReferrer($referrer, $amount) {
        $amount *= _c::ini("general", "referralReward");
        if(isset($referrer) && strlen($referrer) > 0) {
            $ref = new FaucetManager($referrer);
            $ref->refbalance += $amount;
            $ref->alltimeref += $amount;
        }
    }

    function __stats() {
        $stats = [];
        $stats["user_count"] = _c::getCollection('users')->count();
        $stats["paytoshi_payouts"] = _c::getCollection('payouts')->count(["service" => "paytoshi"]);
        $stats["faucetbox_payouts"] = _c::getCollection('payouts')->count(["service" => "faucetbox"]);
        $users = _c::getCollection('users')->find([], ["address", "alltimeref", "alltimebal", "satspent", "satwithdrawn", "referrer"]);
        $payouts = _c::getCollection('payouts')->find([], ["address", "amount", "referral", "time"]);

        $referrals = [];
        $stats["total_dispensed"] = 0;
        $stats["total_withdrawn"] = 0;
        $stats["total_spent"] = 0;
        $stats["total_referral_reward"] = 0;
        $stats["total_referred_users"] = 0;
        foreach($users as $user) {
            $stats["total_dispensed"] += $user["alltimebal"];
            $stats["total_withdrawn"] += $user["satwithdrawn"];
            $stats["total_spent"] += $user["satspent"];
            if(isset($user["alltimeref"])) $stats["total_referral_reward"] += $user["alltimeref"];
            if(!empty($user["referrer"])) {
                $stats["total_referred_users"]++;
                if(empty($referrals[$user["address"]])) $referrals[$user["address"]] = 0;
                $referrals[$user["address"]]++;
            }
        }

        $stats["top_referrer"] = "";
        $stats["top_referred"] = 0;
        foreach($referrals as $addr => $refs) {
            if($refs > $stats["top_referred"]) {
                $stats["top_referrer"] = $addr;
                $stats["top_referred"] = $refs;
            }
        }

        $user_payouts = [];
        $stats["avg_payout_amount"] = 0;
        $stats["non_referral_payouts"] = 0;
        $stats["referral_payouts"] = 0;
        foreach($payouts as $payout) {
            $stats["avg_payout_amount"] = ($stats["avg_payout_amount"] + $payout["amount"]) / ($stats["avg_payout_amount"] == 0 ? 1 : 2);
            if(empty($stats["referral"])) $stats["non_referral_payouts"]++;
            else $stats["referral_payouts"]++;
            if(empty($user_payouts[$payout["address"]])) $user_payouts[$payout["address"]] = 0;
            $user_payouts[$payout["address"]] += $payout["amount"];
        }

        $stats["top_paid_address"] = "";
        $stats["top_paid_amount"] = 0;
        foreach($user_payouts as $addr => $amount) {
            if($amount > $stats["top_paid_amount"]) {
                $stats["top_paid_address"] = $addr;
                $stats["top_paid_amount"] = $amount;
            }
        }

        return $stats;
    }

    function getStats() {
        return array_merge($this->__stats(), ["spinner" => (new Faucet\SpinnerFaucet($this))->__stats()]);
    }
}