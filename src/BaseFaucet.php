<?php

namespace AllTheSatoshi\Faucet;

use AllTheSatoshi\FaucetManager;
use AllTheSatoshi\Util\Config;

abstract class BaseFaucet {

    public $address;
    public $name;

    protected $col;

    function __construct($faucetName, $btcAddress) {
        $this->name = $faucetName;
        $this->address = $btcAddress;
        $this->col = Config::getCollection("users");
    }

    function __get($prop) {
        $r = $this->col->findOne(["address" => $this->address], [$this->name.".".$prop]);

        if(empty($r[$this->name])) return null;
        $nested = $r[$this->name];

        if(empty($nested[$prop])) return null;

        return $nested[$prop];
    }

    function __set($prop, $val) {
        $this->col->update(["address" => $this->address], ['$set' => [$this->name.".".$prop => $val]]);
    }

    abstract function ajax($action, $post);
    abstract function satoshi();

    protected function dispense($amount) {
        FaucetManager::_($this->address)->addBalance($amount);
    }

    function generateNonce($length = 10) {
        $charset = "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
        $nonce = "";
        for($i = 0; $i < $length; $i++) {
            $nonce .= substr($charset, mt_rand(0, strlen($charset) - 1), 1);
        }
        return $nonce;
    }
}