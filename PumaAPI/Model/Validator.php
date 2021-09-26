<?php /** @noinspection PhpUnused */

namespace PumaAPI\Model;

use DateTime;
use Exception;

class Validator {

    use Config;

    const END_RULE = '>>';
    const BEGIN_RULE = '<<';

    private $Config;

    public function __construct($ManifestPath) {
        $this->_getConfig($ManifestPath);
    }

    /**
     * @throws Rawr
     */
    public function validate($input, $command, $parameterName) {
        $rule = $this->_extractRule($command);
        if ($rule) {
            if (method_exists($this, $rule)) {
                if (!$this->$rule($input)) {
                    throw new Rawr("'$parameterName' must be " . self::camelBackToSentence($rule), Rawr::BAD_REQUEST);
                }
            }
        } else {
            if ($input != $command) {
                throw new Rawr("'$parameterName' must be '$command'", Rawr::BAD_REQUEST);
            }
        }
    }

    //========================
    //-------- Rules ---------
    //========================

    public function notEmptyString($Input): bool {
        return is_string($Input) and trim($Input) != '';
    }

    public function validAlgorithm($Input): bool {
        return isset($this->Config['token']['head']['alg']) and $this->Config['token']['head']['alg'] == $Input;
    }

    public function validTokenType($Input): bool {
        return isset($this->Config['token']['head']['typ']) and $this->Config['token']['head']['typ'] == $Input;
    }

    public function validIssuer($Input): bool {
        return isset($this->Config['auth'][$Input]);
    }

    public function validExpiryUnixTimestamp($Input): bool {
        try {
            $date = new DateTime('@' . $Input);
            if ($date < (new DateTime())) {
                return false;
            }
        } catch (Exception $e) {
            return false;
        }
        return true;
    }

    public function signatureMatches($Signature, $Token, $Issuer): bool {
        $key = $this->Config['auth'][$Issuer] ?? '';
        $PumaHash = self::base64_encode_url(hash_hmac('SHA256', $Token, $key, true));
        return $PumaHash === $Signature;
    }

    //========================
    //------- Utility --------
    //========================
    public static function camelBackToSentence($camelString): string {
        return strtolower(implode(' ', preg_split(
            '/(^[^A-Z]+|[A-Z][^A-Z]+)/', $camelString, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE
        )));
    }

    private function _extractRule($command) {
        if (strpos($command, self::BEGIN_RULE) == 0 and strpos($command, self::END_RULE) !== false) {
            $rule = str_replace(self::BEGIN_RULE, '', $command);
            return str_replace(self::END_RULE, '', $rule);
        }
        return false;
    }

}