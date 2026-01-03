<?php

namespace PumaAPI\Model;

class Contract {

    private array $AvailableMethods = [];
    private array $AvailableRoots = [];
    private array $AvailableResources = [];
    private array $ContractBody = [];
    /** @var $Validator Validator */
    private Validator $Validator;
    private string $ManifestPath;

    /**
     * @throws Rawr
     */
    public function __construct(string $ManifestPath) {
        $this->ManifestPath = $ManifestPath;
        $this->_setAvailableMethods();
        if (empty($this->AvailableMethods)) {
            throw new Rawr('No methods allowed', Rawr::BAD_REQUEST);
        }
    }

    private function _setAvailableMethods(): void {
        $allowed = ['get', 'post', 'put', 'delete'];
        $dir = scandir($this->ManifestPath);
        foreach ($dir as $item) {
            if (in_array($item, $allowed)) {
                $this->AvailableMethods[$item] = true;
            }
        }
    }

    /**
     * @param Request $Request
     * @throws Rawr
     */
    public function validate(Request $Request): void {

        list($method, $root, $resource) = $Request->getMethodRootResource();

        try {
            $this
                ->_validateMethod($method)
                ->_getAvailableRootsFor($method)
                ->_validateRoot($root)
                ->_getResourcesFor($method, $root)
                ->_validateResource($resource)
                ->_loadContractOf($method, $root, $resource);

            list(
                $contractHeaders,
                $contractBody,
                $contractJWTHeader,
                $contractJWTPayload
                ) = $this->_getContractDetails();

            $this->Validator = new Validator($this->ManifestPath);

            $this
                ->_validateRequestHeader($contractHeaders, $Request->getRequestHeaders())
                ->_validateRequestBody($contractBody, $Request->getRequestBody())
                ->_validateJWTHeader($contractJWTHeader, $Request->getJWTHeader())
                ->_validateJWTBody($contractJWTPayload, $Request->getJWTPayload());

        } catch (Rawr $e) {
            throw new Rawr('failed to validate request: ' . $e->getMessage(), $e->getCode());
        }
    }

    private function _getContractDetails(): array {
        return [
            $this->ContractBody['Request']['Headers'] ?? [],
            $this->ContractBody['Request']['Body'] ?? [],
            $this->ContractBody['Request']['Headers']['Authorization']['Header'] ?? [],
            $this->ContractBody['Request']['Headers']['Authorization']['Payload'] ?? [],
        ];
    }

    /**
     * @throws Rawr
     */
    private function _validateMethod($Method): Contract {
        if (!isset($this->AvailableMethods[$Method])) {
            throw new Rawr('method not allowed', Rawr::METHOD_NOT_ALLOWED);
        }
        return $this;
    }

    private function _getAvailableRootsFor($Method): Contract {
        $dir = scandir($this->ManifestPath . DIRECTORY_SEPARATOR . $Method);
        foreach ($dir as $item) {
            if ($item == '.' or $item == '..') continue;
            $this->AvailableRoots[$item] = true;
        }
        return $this;
    }

    /**
     * @throws Rawr
     */
    private function _validateRoot($Root): Contract {
        if (!isset($this->AvailableRoots[$Root])) {
            throw new Rawr('Root not found', Rawr::NOT_FOUND);
        }
        return $this;
    }

    private function _getResourcesFor($Method, $Root): Contract {
        $dir = scandir($this->ManifestPath . DIRECTORY_SEPARATOR . $Method . DIRECTORY_SEPARATOR . $Root);
        foreach ($dir as $item) {
            if ($item == '.' or $item == '..') continue;
            $this->AvailableResources[pathinfo($item, PATHINFO_FILENAME)] = true;
        }
        return $this;
    }

    /**
     * @throws Rawr
     */
    private function _validateResource($Resource): Contract {
        if (!isset($this->AvailableResources[$Resource])) {
            throw new Rawr('Resource not found', Rawr::NOT_FOUND);
        }
        return $this;
    }

    /**
     * @param $method
     * @param $root
     * @param $resource
     * @throws Rawr
     */
    private function _loadContractOf($method, $root, $resource): void {
        $path = implode(DIRECTORY_SEPARATOR, [
            $this->ManifestPath, $method, $root, $resource . '.json',
        ]);

        $file = @file_get_contents($path);
        if (!$file) {
            throw new Rawr("contract file not found in $path", Rawr::INTERNAL_ERROR);
        }
        $contract = json_decode($file, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            $this->ContractBody = $contract;
        } else {
            throw new Rawr('failed to parse contract JSON', Rawr::INTERNAL_ERROR);
        }
    }

    /**
     * @throws Rawr
     */
    private function _validateRequestHeader($ContractHeader, $RequestHeader): Contract {
        foreach ($ContractHeader as $param => $rule) {
            if (!isset($RequestHeader[$param])) {
                throw new Rawr('header ' . $param . ' is missing', Rawr::BAD_REQUEST);
            }
            if ($param == 'Authorization') continue;
            $this->Validator->validate($RequestHeader[$param], $rule, $param);
        }
        return $this;
    }

    /**
     * @throws Rawr
     */
    private function _validateRequestBody($ContractBody, $RequestBody): Contract {
        foreach ($ContractBody as $param => $rule) {
            if (!isset($RequestBody[$param])) {
                throw new Rawr('body variable "' . $param . '" is missing', Rawr::BAD_REQUEST);
            }
            $this->Validator->validate($RequestBody[$param], $rule, $param);
        }
        return $this;
    }

    /**
     * @throws Rawr
     */
    private function _validateJWTHeader($ContractJWTHeader, $RequestJWTHeader): Contract {
        foreach ($ContractJWTHeader as $param => $rule) {
            if (!isset($RequestJWTHeader[$param])) {
                throw new Rawr('JWT header variable "' . $param . '" is missing', Rawr::UNAUTHORIZED);
            }
            $this->Validator->validate($RequestJWTHeader[$param], $rule, $param);
        }
        return $this;
    }

    /**
     * @throws Rawr
     */
    private function _validateJWTBody($ContractJWTPayload, $RequestJWTPayload): void {
        foreach ($ContractJWTPayload as $param => $rule) {
            if (!isset($RequestJWTPayload[$param])) {
                throw new Rawr('JWT body variable "' . $param . '" is missing', Rawr::UNAUTHORIZED);
            }
            $this->Validator->validate($RequestJWTPayload[$param], $rule, $param);
        }
    }

    /**
     * @throws Rawr
     */
    public function authenticate(Request $Request): void {
        if (!$this->Validator->signatureMatches(
            $Request->getJWTSignature(),
            $Request->getJWTDocument(),
            $Request->getIssuer())) {
            throw new Rawr('failed to authenticate request', Rawr::UNAUTHORIZED);
        }
    }

    public function getSterilized(Request $Request): array {
        list($method, $root, $resource) = $Request->getMethodRootResource();
        list(
            $contractHeaders,
            $contractBody,
            $contractJWTHeader,
            $contractJWTPayload
            ) = $this->_getContractDetails();

        $contract = [
            'Method'   => $method,
            'Root'     => $root,
            'Resource' => $resource,
        ];
        $contract['Headers'] = [];
        foreach ($contractHeaders as $name => $value) {
            $contract['Headers'][$name] = $Request->getRequestHeaders()[$name];
        }
        $contract['Body'] = [];
        foreach ($contractBody as $name => $value) {
            $contract['Body'][$name] = $Request->getRequestBody()[$name];
        }
        $contract['JWTHeader'] = [];
        foreach ($contractJWTHeader as $name => $value) {
            $contract['JWTHeader'][$name] = $Request->getJWTHeader()[$name];
        }
        $contract['JWTPayload'] = [];
        foreach ($contractJWTPayload as $name => $value) {
            $contract['JWTPayload'][$name] = $Request->getJWTPayload()[$name];
        }
        return $contract;
    }

    public function getContractedResponse(): array {
        return $this->ContractBody['Response'] ?? [];
    }


}