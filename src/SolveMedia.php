<?php

namespace AllTheSatoshi\Util;

class SolveMedia {
    function __construct($secret, $hashkey = '') {
        $this->secret = $secret;
        $this->hashkey = $hashkey;
    }

    static function getMessage($error_code) {
        $arr = [
            'missing-input-secret' => 'The secret parameter is missing.',
            'invalid-input-secret' => 'The secret parameter is invalid or malformed.',
            'missing-input-response' => 'The response parameter is missing.',
            'invalid-input-response' => 'The response parameter is invalid or malformed.'
        ];
        return $arr[$error_code];
    }

    function verify($challenge, $response) {
        //set POST variables
        $url = 'http://verify.solvemedia.com/papi/verify';
        $data = array(
            'privatekey' => $this->secret,
            'challenge' => $challenge,
            'response' => $response,
            'remoteip' => $_SERVER['REMOTE_ADDR']
        );

        $options = array(
            'http' => array(
                'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
                'method'  => 'POST',
                'content' => http_build_query($data),
            ),
        );

        $context  = stream_context_create($options);
        $result = explode("\n", file_get_contents($url, false, $context));

        $hash = sha1( $result[0] . $challenge . $this->secret );

        if( $hash != $result[2] ) {
            return ["success" => false, "message" => "Hash verification failed.  Maybe there was an attack?"];
        }

        return ["success" => $result[0], "message" => $result[1]];
    }
}