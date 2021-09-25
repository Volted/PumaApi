<?php

namespace PumaAPI\Model;


class Certificate {

    private $ContractData;
    private $ReadyToSendHeaders;
    private $ReadyToSendBody;

    public function __construct($ContractData) {
        error_log(print_r($ContractData, true));
    }

    public function issue() {

    }

}