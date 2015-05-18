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
        'adsense' => 'return "<script type=\"text/javascript\">\n    google_ad_client = \"".self::$ad_slots[$network][$slot]["pubid"]."\";\n    google_ad_slot = \"".$slot."\";\n    google_ad_width = ".$width.";\n    google_ad_height = ".$height.";\n</script>\n<!-- ".self::$ad_slots[$network][$slot]["name"]." -->\n<script type=\"text/javascript\"\nsrc=\"//pagead2.googlesyndication.com/pagead/show_ads.js\">\n</script>";',
        'bitclix' => 'return "<iframe scrolling=\"no\" style=\"border: 0; width: ".$width."px; height: ".$height."px;\" src=\"//ads.bcsyndication.com/get.php?s=".$slot."\"></iframe>";',
    );

    static private $ad_slots = [
        'a-ads' => [
            '69629' => [
                'width' => 336,
                'height' => 280,
            ],
            '69632' => [ // leaderboard
                'width' => 728,
                'height' => 90,
            ],
        ],
        'adsense' => [
            '8882945326' => [
                'pubid' => 'ca-pub-5885519961820058',
                'name' => 'AllTheSatoshi Left Skyscraper',
                'width' => 300,
                'height' => 600,
            ],
            '1220077720' => [
                'pubid' => 'ca-pub-5885519961820058',
                'name' => 'AllTheSatoshi Right Skyscraper',
                'width' => 300,
                'height' => 600,
            ],
            '5929478925' => [
                'pubid' => 'ca-pub-5885519961820058',
                'name' => 'AllTheSatoshi Middle',
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
        if(isset($_GET["show_ads"])) $ignoreblacklist = $_GET["show_ads"];
        if(!$ignoreblacklist && (in_array($_SERVER['REMOTE_ADDR'], self::$ip_blacklist) || in_array($_SERVER['HTTP_CF_CONNECTING_IP'], self::$ip_blacklist))) return;

        $width = self::$ad_slots[$network][$slot]['width'];
        $height = self::$ad_slots[$network][$slot]['height'];

        echo eval(self::$net_codes[$network]);
    }

}