<?php

namespace PumaAPI\Controller;

use PumaAPI\Model\Certificate;
use PumaAPI\Model\Contract;
use PumaAPI\Model\Rawr;
use PumaAPI\Model\Request;


class Director {

    /** @var $Request Request */
    private $Request;
    /** @var $Contract Contract */
    private $Contract;

    public function __construct() {
        try {
            $this
                ->_parseRequest()
                ->_getCorrespondingContract()
                ->_validateRequest()
                ->_authenticateRequest();
        } catch (Rawr $e) {
            $e->handleException();
        }
        $this->_issueCertificate()->_sendResponse();
    }

    private function _parseRequest(): Director {
        $this->Request = new Request();
        return $this;
    }

    private function _getCorrespondingContract(): Director {
        $this->Contract = new Contract();
        return $this;
    }

    /**
     * @throws Rawr
     */
    private function _validateRequest(): Director {
        try {
            $this->Contract->validate($this->Request);
        } catch (Rawr $e) {
            throw new Rawr($e->getMessage(), $e->getCode());
        }
        return $this;
    }

    /**
     * @throws Rawr
     */
    private function _authenticateRequest() {
        try {
            $this->Contract->authenticate($this->Request);
        } catch (Rawr $e) {
            throw new Rawr($e->getMessage(), $e->getCode());
        }
    }

    private function _issueCertificate(): Director {
        $Certificate = new Certificate($this->Contract->getSterilizedContractFor($this->Request));
        return $this;
    }

    private function _sendResponse() {

    }


}