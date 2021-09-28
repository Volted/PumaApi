<?php /** @noinspection PhpUnused */

namespace PumaAPI\Model;

use DateTime;
use Exception;

class Validator {

    const END_RULE = '>>';
    const BEGIN_RULE = '<<';

    private $ConfigPath;
    /** @var bool|Tokenizer */
    private $Tokenizer = false;

    public function __construct($configPath) {
        $this->ConfigPath = $configPath;
    }

    /**
     * @throws Rawr
     */
    public function validate($input, $command, $parameterName) {

        if (is_array($command)) {
            if (!is_array($input)) {
                throw new Rawr("$parameterName must be array", Rawr::BAD_REQUEST);
            } else {
                return;
            }
        }

        $rule = self::extractRule($command);
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
        if (!$this->Tokenizer) {
            $this->Tokenizer = new Tokenizer($this->ConfigPath);
        }
        return $this->Tokenizer->isValidAlgorithm($Input);
    }

    public function validTokenType($Input): bool {
        if (!$this->Tokenizer) {
            $this->Tokenizer = new Tokenizer($this->ConfigPath);
        }
        return $this->Tokenizer->isValidType($Input);
    }

    public function validIssuer($Input): bool {
        if (!$this->Tokenizer) {
            $this->Tokenizer = new Tokenizer($this->ConfigPath);
        }
        return $this->Tokenizer->isValidIssuer($Input);
    }

    public function validUnixTimestamp($Input): bool {
        try {
            new DateTime('@' . $Input);
        } catch (Exception $e) {
            return false;
        }
        return true;
    }

    public function integer($Input): bool {
        return is_integer($Input);
    }

    public function signatureMatches($Signature, $Token, $Issuer): bool {
        if (!$this->Tokenizer) {
            $this->Tokenizer = new Tokenizer($this->ConfigPath);
        }
        return $this->Tokenizer->isProperlySigned($Token, $Signature, $Issuer);
    }

    //========================
    //------- Utility --------
    //========================
    public static function camelBackToSentence($camelString): string {
        return strtolower(implode(' ', preg_split(
            '/(^[^A-Z]+|[A-Z][^A-Z]+)/', $camelString, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE
        )));
    }

    public static function extractRule($command) {
        if (strpos($command, self::BEGIN_RULE) == 0 and strpos($command, self::END_RULE) !== false) {
            $rule = str_replace(self::BEGIN_RULE, '', $command);
            return str_replace(self::END_RULE, '', $rule);
        }
        return false;
    }

}