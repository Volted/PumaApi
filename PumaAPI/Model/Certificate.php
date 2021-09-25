<?php /** @noinspection PhpUnused */

namespace PumaAPI\Model;

class Certificate {

    private $Request;
    private $JWT;
    private $Response;

    public function __construct($SanitizedRequest, $ContractedResponse) {
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

    public function getRequestedMethod() {
        return $this->Request['Method'];
    }

    public function getRequestedRoot() {
        return $this->Request['Root'];
    }

    public function getRequestedResource() {
        return $this->Request['Resource'];
    }

    public function getRequestHeaders() {
        return $this->Request['Headers'];
    }

    public function getRequestBody() {
        return $this->Request['Body'];
    }

    public function getRequestedJWTPayload() {
        return $this->JWT['Payload'];
    }

    public function getRequestedJWTHead() {
        return $this->JWT['Head'];
    }

    public function getResponseContract() {
        return $this->Response;
    }

}