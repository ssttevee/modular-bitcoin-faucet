<?php

class AdManager {

    static public $__ADBIT = 'adbit';
    static public $__ADSENSE = 'adsense';
    static public $BITCLIX = 'bitclix';

    static private $ip_blacklist = array(
        '127.0.0.1',
        '23.16.160.33',
    );

    static private $net_codes = array(
        'a-ads' => 'return "<iframe data-aa=\"".$slot."\" src=\"https://ad.a-ads.com/".$slot."?size=".$width."x".$height."\" scrolling=\"no\" style=\"width: ".$width."px; height: ".$height."px; border:0px; padding:0;overflow:hidden;\" allowtransparency=\"true\" frameborder=\"0\"></iframe>";',
        'adbit' => 'return "<iframe scrolling=\"no\" frameborder=\"0\" src=\"//adbit.co/adspace.php?a=".$slot."\" style=\"overflow:hidden;width:".$width."px;height:".$height."px;\"></iframe>";',
        'adsense' => 'return "<script async src=\"//pagead2.googlesyndication.com/pagead/js/adsbygoogle.js\"></script>" . "<ins class=\"adsbygoogle\" style=\"display:inline-block;width:".$width."px;height:".$height."px;\" data-ad-client=\"".self::$ad_slots[$network][$slot]["pubid"]."\" data-ad-slot=\"".$slot."\"></ins>" . "<script> (adsbygoogle = window.adsbygoogle || []).push({}); </script>";',
        'bitclix' => 'return "<iframe scrolling=\"no\" style=\"border: 0; width: ".$width."px; height: ".$height."px;\" src=\"//ads.bcsyndication.com/get.php?s=".$slot."\"></iframe>";',
    );

    static private $ad_slots = [
        'a-ads' => [
            '69468' => [
                'width' => 336,
                'height' => 280,
            ],
        ],
        'adsense' => [
            '8882945326' => [
                'pubid' => 'ca-pub-5885519961820058',
                'width' => 300,
                'height' => 600,
            ],
            '1220077720' => [
                'pubid' => 'ca-pub-5885519961820058',
                'width' => 300,
                'height' => 600,
            ],
            '5929478925' => [
                'pubid' => 'ca-pub-5885519961820058',
                'width' => 336,
                'height' => 280,
            ],
        ],
        'adbit' => [
            'TU5BRHOMMS3FI' => [
                'width' => 468,
                'height' => 60,
            ],
            '1VSG0O1G1JA3P' => [
                'width' => 468,
                'height' => 60,
            ],
        ],
        'bitclix' => [
            '11719' => [
                'width' => 728,
                'height' => 90,
            ],
            '11724' => [
                'width' => 468,
                'height' => 60,
            ],
        ],
    ];

    static function insert($network, $slot, $ignoreblacklist = false) {
        if(!$ignoreblacklist && (in_array($_SERVER['REMOTE_ADDR'], self::$ip_blacklist) || in_array($_SERVER['HTTP_CF_CONNECTING_IP'], self::$ip_blacklist))) return;

        $width = self::$ad_slots[$network][$slot]['width'];
        $height = self::$ad_slots[$network][$slot]['height'];

        echo eval(self::$net_codes[$network]);
    }

}