<?php /** @noinspection PhpUnused */

namespace PumaAPI\Model;

use DateTime;
use Exception;

class Tokenizer {


    public $ServiceConfig;

    /**
     * @throws Rawr
     */
    public function __construct($alternativePathToConfig = false) {
        if ($alternativePathToConfig) {
            $file = $alternativePathToConfig . DIRECTORY_SEPARATOR . 'service.ini';
        } else {
            $file = realpath('.') . DIRECTORY_SEPARATOR . '__manifest' . DIRECTORY_SEPARATOR . 'service.ini';
        }
        $this->ServiceConfig = parse_ini_file($file, true);
        if (!$this->ServiceConfig) {
            throw new Rawr('service.ini file no found', Rawr::INTERNAL_ERROR);
        }
    }

    public function generateNewToken(string $Issuer, array $Head, array $Body): string {
        $head = self::base64_encode_url(json_encode($Head));
        $body = self::base64_encode_url(json_encode($Body));
        $key = $this->ServiceConfig['auth'][$Issuer] ?? '';
        $signature = self::base64_encode_url(hash_hmac('SHA256', $head . '.' . $body, $key, true));
        return implode('.', [$head, $body, $signature]);
    }

    public function generateSignatureFor(string $Issuer, array $Head, array $Body): string {
        $head = self::base64_encode_url(json_encode($Head));
        $body = self::base64_encode_url(json_encode($Body));
        $key = $this->ServiceConfig['auth'][$Issuer] ?? '';
        return self::base64_encode_url(hash_hmac('SHA256', $head . '.' . $body, $key, true));
    }

    public function getCurrentIssuer() {
        return $this->ServiceConfig['ident']['iss'] ?? '';
    }

    public function getCurrentAlgorithm() {
        return $this->ServiceConfig['token']['head']['alg'] ?? '';
    }

    public function getCurrentTokenType() {
        return $this->ServiceConfig['token']['head']['typ'] ?? '';
    }

    public function isValidAlgorithm(string $Alg): bool {
        return isset($this->ServiceConfig['token']['head']['alg']) and $this->ServiceConfig['token']['head']['alg'] == $Alg;
    }

    public function isValidType(string $Typ): bool {
        return isset($this->ServiceConfig['token']['head']['typ']) and $this->ServiceConfig['token']['head']['typ'] == $Typ;
    }

    public function isValidIssuer(string $Iss): bool {
        return isset($this->ServiceConfig['auth'][$Iss]);
    }

    public function isProperlySigned(string $Token, string $Signature, string $Issuer): bool {
        $key = $this->ServiceConfig['auth'][$Issuer] ?? '';
        $PumaHash = self::base64_encode_url(hash_hmac('SHA256', $Token, $key, true));
        return $PumaHash === $Signature;
    }

    public static function base64_encode_url(string $string) {
        return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($string));
    }

    public static function base64_decode_url(string $string) {
        return base64_decode(str_replace(['-', '_'], ['+', '/'], $string));
    }

    public function isAuthentic(array $TokenContent, $Issuer): bool {
        $signature = $this->generateSignatureFor($Issuer, $TokenContent[0], $TokenContent[1]);
        if ($signature === $TokenContent[2]) {
            return true;
        }
        return false;
    }

    public function validExpiryDate($TokenHead): bool {
        if (isset($TokenHead['exp'])) {
            try {
                $expiry = new DateTime('@' . $TokenHead['exp']);
                $now = new DateTime();
                if ($now < $expiry) {
                    return true;
                }
            } catch (Exception $e) {
                return false;
            }
        }
        return false;
    }

    public static function extractJWT($RequestHeaders): array {
        if (isset($RequestHeaders['Authorization']) and is_string($RequestHeaders['Authorization'])) {
            $JWT = str_replace('Bearer ', '', $RequestHeaders['Authorization']);
            $JWT = explode('.', $JWT);
            if (count($JWT) == 3) {
                return [
                    'Head'      => json_decode(Tokenizer::base64_decode_url($JWT[0])),
                    'Payload'   => json_decode(Tokenizer::base64_decode_url($JWT[1])),
                    'Signature' => $JWT[2],
                ];
            }
        }
        return [
            'Head'      => [],
            'Payload'   => [],
            'Signature' => '',
        ];
    }

}