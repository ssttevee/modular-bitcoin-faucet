<?php

namespace AllTheSatoshi\Faucet;

use AllTheSatoshi\FaucetManager;
use AllTheSatoshi\Util\Config as _c;

abstract class BaseFaucet {

    public $address;
    public $name;

    function __construct($faucetName, $btcAddress) {
        $this->name = $faucetName;
        $this->address = $btcAddress;
    }

    function __get($prop) {
        $r = _c::getCollection('users')->findOne(["address" => $this->address], [$this->name.".".$prop]);

        if(empty($r[$this->name])) return null;
        $nested = $r[$this->name];

        if(empty($nested[$prop])) return null;

        return $nested[$prop];
    }

    function __set($prop, $val) {
        _c::getCollection('users')->update(["address" => $this->address], ['$set' => [$this->name.".".$prop => $val]]);
    }

    abstract function ajax($action, $post);

    function dispense($amount) {
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