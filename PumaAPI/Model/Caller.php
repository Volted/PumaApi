<?php

namespace PumaAPI\Model;

class Caller {


    const POST = 'POST';
    const GET = 'GET';
    const PUT = 'PUT';
    const DELETE = 'DELETE';
    
    private $URL;
    private $Method;
    private $Headers;
    private $JWT;
    private $Body;

    public function __construct($Method,$URL, $Headers, $JWT, $Body) {
        $this->Method = $Method;
        $this->URL = $URL;
        $this->Headers = $Headers;
        $this->JWT = $JWT;
        $this->Body = $Body;
        return $this;
    }

    public function initiateRequest() {

    }

    public function captureResponse() {
        
    }





}