<?php
class reCaptcha {
    function __construct($secret) {
        $this->secret = $secret;
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