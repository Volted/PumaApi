<?php /** @noinspection PhpUnused */

namespace PumaAPI\Controller;

use PumaAPI\Model\Certificate;
use PumaAPI\Model\Contract;
use PumaAPI\Model\Rawr;
use PumaAPI\Model\Request;


class API {

    private $ManifestPath;
    /** @var $Request Request */
    private $Request;
    /** @var $Contract Contract */
    private $Contract;

    public function __construct($alternativeManifestPath = false) {
        if($alternativeManifestPath){
            $this->ManifestPath = $alternativeManifestPath;
        }else{
            $this->ManifestPath = realpath('.') . DIRECTORY_SEPARATOR . '__manifest';
        }
        try {
            $this
                ->_parseRequest()
                ->_getCorrespondingContract()
                ->_validateRequest()
                ->_authenticateRequest();
        } catch (Rawr $e) {
            $e->handleException();
        }
    }

    private function _parseRequest(): API {
        $this->Request = new Request();
        return $this;
    }

    /**
     * @throws Rawr
     */
    private function _getCorrespondingContract(): API {
        try {
            $this->Contract = new Contract($this->ManifestPath);
        } catch (Rawr $e) {
            throw new Rawr($e->getMessage(), $e->getCode());
        }
        return $this;
    }

    /**
     * @throws Rawr
     */
    private function _validateRequest(): API {
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

    public function getCertificate(): Certificate {
        return (
        new Certificate(
            $this->Contract->getSterilized($this->Request),
            $this->Contract->getContractedResponse()
        ));
    }


}