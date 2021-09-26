<?php

namespace PumaAPI\Model;

class Tokenizer {

    public $Head;
    public $Payload;
    public $Signature;
    public $ServiceConfig;

    public function __construct($pathToConfig) {
        $this->ServiceConfig = parse_ini_file($pathToConfig . DIRECTORY_SEPARATOR . 'service.ini', true) ?? [];
    }

    public function isValidAlgorithm($Alg) :bool{
        return isset($this->ServiceConfig['token']['head']['alg']) and $this->ServiceConfig['token']['head']['alg'] == $Alg;
    }

    public function isValidType($Typ) :bool{
        return isset($this->ServiceConfig['token']['head']['typ']) and $this->ServiceConfig['token']['head']['typ'] == $Typ;
    }

    public function isValidIssuer($Iss) :bool{
        return isset($this->ServiceConfig['auth'][$Iss]);
    }

    public function isProperlySigned($Token, $Signature, $Issuer) :bool{
        $key = $this->ServiceConfig['auth'][$Issuer] ?? '';
        $PumaHash = self::base64_encode_url(hash_hmac('SHA256', $Token, $key, true));
        return $PumaHash === $Signature;
    }

    public static function base64_encode_url($string) {
        return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($string));
    }

    public static function base64_decode_url($string) {
        return base64_decode(str_replace(['-', '_'], ['+', '/'], $string));
    }

}