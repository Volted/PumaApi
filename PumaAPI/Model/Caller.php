<?php

namespace PumaAPI\Model;

class Caller {

    use Config;

    const POST = 'POST';
    const GET = 'GET';
    const PUT = 'PUT';
    const DELETE = 'DELETE';


    private $URL;
    private $Method;
    private $Headers;
    private $JWTHead;
    private $JWTPayload;
    private $Body;

    public function __construct($URL, $Method, $Headers, $JWTHead, $JWTPayload, $Body) {
        $this->_getConfig();
        $this->URL = $URL;
        $this->Method = $Method;
        $this->Headers = $Headers;
        $this->JWTHead = $JWTHead;
        $this->JWTPayload = $JWTPayload;
        $this->Body = $Body;
        return $this;
    }

    public function getResponse() {

    }





}