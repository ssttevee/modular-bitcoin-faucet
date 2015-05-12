<?php

/**
 * Paytoshi PHP Library
 * 
 * Contact: info@paytoshi.org
 * 
 * @author: Looptribe
 * @link: https://paytoshi.org
 * @package looptribe/paytoshi-library-php
 */

class Paytoshi
{
    const PAYTOSHI_BASE_URL = "http://paytoshi.org/api/v1";
    
    private $isCurl;
    private $verifyPeer;
    
    public function __construct($isCurl = true, $verifyPeer = true)
    {
        $this->isCurl = $isCurl;
        $this->verifyPeer = $verifyPeer;
    }
    
    private function getBaseUrl()
    {
        return self::PAYTOSHI_BASE_URL;
    }
    
    private function isCurl()
    {
        return $this->isCurl && function_exists('curl_version');
    }
    
    protected function getUrl($api, $params = array())
    {
        $url = $this->getBaseUrl() . '/' . $api;
        if (!empty($params))
            $url .= '?' . http_build_query($params);
        return $url;
    }
    
    private function runCurl($url, $data)
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, array(
            CURLOPT_SSL_VERIFYPEER => $this->verifyPeer,
            CURLOPT_RETURNTRANSFER => true
        ));
        
        if (!empty($data))
        {
            curl_setopt_array($ch, array(
                CURLOPT_POST => TRUE,
                CURLOPT_POSTFIELDS => $data
            ));
        }

        $response = curl_exec($ch);
        curl_close($ch);

        return $response;
    }
    
    private function runFileGetContents($url, $data)
    {
        $opts = array(
            'ssl' => array('verify_peer' => $this->verifyPeer)
        );
        if (!empty($data))
        {
            $opts['http'] = array(
                'method' => 'POST',
                'header' => "Content-type: application/x-www-form-urlencoded\r\n",
                'content' => $data
            );
        }
        else
            $opts['http'] = array('method' => 'GET');
        
        $context = stream_context_create($opts);
        $response = file_get_contents($url, false, $context);
        return $response;
    }
    
    private function run($url, $data = array())
    {
        if ($this->isCurl())
            $response = $this->runCurl($url, $data);
        else
            $response = $this->runFileGetContents($url, $data);
        
        return $response;
    }
    
    protected function get($api, $params = array())
    {
        $url = $this->getUrl($api, $params);
        
        $response = $this->run($url);
        if (!$response)
            return null;
        
        return json_decode($response, true);
    }
    
    protected function post($api, $postData = array(), $params = array())
    {
        $url = $this->getUrl($api, $params);
        $data = http_build_query($postData);
        
        $response = $this->run($url, $data);
        if (!$response)
            return null;

        return json_decode($response, true);
    }
    
    public function faucetSend($faucetApikey, $address, $amount, $ip, $referral = false)
    {
        return $this->post('faucet/send', array(
            'address' => $address,
            'amount' => $amount,
            'ip' => $ip,
            'referral' => $referral,
            ), array('apikey' => $faucetApikey));
    }
}
