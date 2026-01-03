<?php

namespace PumaAPI\Model;

class Request {

    private bool $isSecure;
    private string $IssuerIp;

    private array $JWTHeader;
    private array $JWTPayload;
    private string $JWTSignature;
    private string $JWTDocument;

    private string $RequestMethod;
    private array $RequestHeaders;
    private array $RequestBody;
    private ?string $RequestedRoot = null;
    private ?string $RequestedResource = null;
    private array $RequestParameters;

    /**
     * @throws Rawr
     */
    public function __construct() {
        $this->_setIssuerIP();
        $this->_setMethod();
        $this->_setSecurityProtocol();
        $this->_setResourceRoot();
        $this->_setResource();
        $this->_setRequestHeaders();
        $this->_setRequestParameters();
        try {
            $this->_setRequestBody();
            $this->_setJWT();
        } catch (Rawr $e) {
            throw new Rawr("failed to parse request " . $e->getMessage(), Rawr::BAD_REQUEST);
        }
        return $this;
    }

    private function _setIssuerIP(): void {
        $this->IssuerIp = $_SERVER['REMOTE_ADDR'];
    }

    private function _setMethod(): void {
        $this->RequestMethod = strtolower($_SERVER['REQUEST_METHOD']) ?? '__unknown__';
    }

    private function _setSecurityProtocol(): void {
        $this->isSecure = false;
        if (isset($_SERVER['HTTPS']) and strtolower($_SERVER['HTTPS']) == 'on') {
            $this->isSecure = true;
        } elseif (
            (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) and strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) == 'https')
            or
            (!empty($_SERVER['HTTP_X_FORWARDED_SSL']) and strtolower($_SERVER['HTTP_X_FORWARDED_SSL']) == 'on')) {
            $this->isSecure = true;
        }
    }

    private function _setResourceRoot(): void {
        if (isset($_GET['url'])) {
            $this->RequestedRoot = array_filter(explode('/', $_GET['url']))[0] ?? '__unknown__';
        }
    }

    private function _setResource(): void {
        if (isset($_GET['url'])) {
            $parts = array_filter(explode('/', $_GET['url']));
            unset($parts[0]);
            $this->RequestedResource = implode('/', $parts);
        }
    }

    private function _setRequestHeaders(): void {
        $this->RequestHeaders = getallheaders();
    }

    /**
     * @throws Rawr
     */
    private function _setRequestBody(): void {
        $content = file_get_contents('php://input');
        $json = json_decode($content);
        if (json_last_error() === JSON_ERROR_NONE) {
            $this->RequestBody = (array)$json;
        } else {
            throw new Rawr('request body is not JSON', Rawr::BAD_REQUEST);
        }
    }

    private function _setRequestParameters(): void {
        unset($_GET['url']);
        $this->RequestParameters = array_merge($_GET, $_POST);
    }

    /**
     * @throws Rawr
     */
    private function _setJWT(): void {
        if (!isset($this->RequestHeaders['Authorization'])) {
            throw new Rawr('Authorization token not set', Rawr::BAD_REQUEST);
        }
        $token = $this->RequestHeaders['Authorization'];

        if (!str_starts_with($token, 'Bearer ')) {
            throw new Rawr('Unacceptable Auth type', Rawr::BAD_REQUEST);
        }
        $token = substr($token, 7);
        $parts = ['JWTHeader', 'JWTPayload', 'JWSignature'];
        $document = [];
        foreach (explode('.', $token) as $index => $content) {
            if ($parts[$index] == 'JWSignature') {
                $this->JWTSignature = $content;
            } else {
                $document[] = $content;
                $decode = Tokenizer::base64_decode_url($content);
                if ($decode) {
                    $json = json_decode($decode);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        $this->{$parts[$index]} = (array)$json;
                    } else {
                        throw new Rawr($parts[$index] . ' is not JSON', Rawr::BAD_REQUEST);
                    }
                } else {
                    throw new Rawr('failed to decode ' . $parts[$index], Rawr::BAD_REQUEST);
                }
            }
        }
        $this->JWTDocument = implode('.', $document);
        $this->RequestHeaders['Authorization'] = true;
    }

    public function getMethodRootResource(): array {
        return [$this->RequestMethod, $this->RequestedRoot, $this->RequestedResource];
    }

    public function getRequestHeaders(): array {
        return $this->RequestHeaders;
    }

    public function getRequestBody(): array {
        return $this->RequestBody;
    }

    public function getJWTHeader(): array {
        return $this->JWTHeader;
    }

    public function getJWTPayload(): array {
        return $this->JWTPayload;
    }

    public function getJWTSignature():string {
        return $this->JWTSignature;
    }

    public function getJWTDocument():string {
        return $this->JWTDocument;
    }

    public function getIssuer() {
        return $this->JWTPayload['iss'] ?? '';
    }




}