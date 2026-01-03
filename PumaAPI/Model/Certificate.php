<?php

namespace PumaAPI\Model;

class Certificate {

    private array $Request;
    private array $JWT;
    private array $Response;

    public function __construct(array $SanitizedRequest,array $ContractedResponse) {
        $this->Request = [
            'Method'   => $SanitizedRequest['Method'],
            'Root'     => $SanitizedRequest['Root'],
            'Resource' => $SanitizedRequest['Resource'],
            'Headers'  => $SanitizedRequest['Headers'],
            'Body'     => $SanitizedRequest['Body'],
        ];
        $this->JWT = [
            'Head'    => $SanitizedRequest['JWTHeader'],
            'Payload' => $SanitizedRequest['JWTPayload'],
        ];
        $this->Response = $ContractedResponse;
        return $this;
    }

    public function getRequestedMethod():string {
        return $this->Request['Method'];
    }

    public function getRequestedRoot():string {
        return $this->Request['Root'];
    }

    public function getRequestedResource():string {
        return $this->Request['Resource'];
    }

    public function getRequestHeaders():array {
        return $this->Request['Headers'];
    }

    public function getRequestBody():array {
        return $this->Request['Body'];
    }

    public function getRequestedJWTPayload():array {
        return $this->JWT['Payload'];
    }

    public function getRequestedJWTHead():array {
        return $this->JWT['Head'];
    }

    public function getResponseContract(): array {
        return $this->Response;
    }

}