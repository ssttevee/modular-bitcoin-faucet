<?php

namespace AllTheSatoshi\Util;

class ReCaptcha {
    function __construct($secret) {
        $this->secret = $secret;
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

    function verify($response) {
        //set POST variables
        $url = 'https://www.google.com/recaptcha/api/siteverify';
        $data = array(
            'secret' => $this->secret,
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
        $result = file_get_contents($url, false, $context);

        return json_decode($result, true);
    }
}