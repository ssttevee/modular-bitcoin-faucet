<?php

class AdManager {

    static private $ip_blacklist = array(
        '127.0.0.1',
        '23.16.160.33',
    );

    static function insertGoogleAd($pubid, $slot, $ignoreblacklist = false) {
        if(!$ignoreblacklist && in_array($_SERVER['REMOTE_ADDR'], self::$ip_blacklist)) return;
        print <<<EOF
<script async src="//pagead2.googlesyndication.com/pagead/js/adsbygoogle.js"></script>
<ins class="adsbygoogle" style="display:inline-block;width:336px;height:280px" data-ad-client="$pubid" data-ad-slot="$slot"></ins>
<script> (adsbygoogle = window.adsbygoogle || []).push({}); </script>
EOF;
    }

    static function insertAdbitAd($slot, $ignoreblacklist = false) {
        if(!$ignoreblacklist && in_array($_SERVER['REMOTE_ADDR'], self::$ip_blacklist)) return;
        print <<<EOF
<iframe scrolling="no" frameborder="0" src="//adbit.co/adspace.php?a=$slot" style="overflow:hidden;width:468px;height:60px;"></iframe>
EOF;
    }

    static function insertBitClixAd($slot, $ignoreblacklist = false) {
        if(!$ignoreblacklist && in_array($_SERVER['REMOTE_ADDR'], self::$ip_blacklist)) return;
        print <<<EOF
        <iframe scrolling="no" style="border: 0; width: 728px; height: 90px;" src="//ads.bcsyndication.com/get.php?s=$slot"></iframe>
EOF;
    }

}