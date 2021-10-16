<?php /** @noinspection PhpUnused */

namespace PumaAPI\Model;

class Caller {

    const POST = 'POST';
    const GET = 'GET';
    const PUT = 'PUT';
    const DELETE = 'DELETE';

    static $AcceptableMethods = [
        self::POST,
        self::GET,
        self::PUT,
        self::DELETE,
    ];

    private $URL;
    private $Method;
    private $Headers;
    private $JWT;
    private $Body;

    private $ResponseCode;
    private $ResponseHeaders;
    private $ResponseBody;

    /**
     * @param       $Method
     * @param       $URL
     * @param       $Headers
     * @param       $JWT
     * @param array $Body
     */
    public function __construct($Method, $URL, $Headers, $JWT, array $Body = []) {
        $this->Method = $Method;
        $this->URL = $URL;
        $this->Headers = $Headers;
        $this->JWT = $JWT;
        $this->Body = $Body;
        return $this;
    }

    /**
     * @throws Rawr
     */
    public function initRequest() {
        $channel = curl_init($this->URL);
        curl_setopt($channel, CURLOPT_URL, $this->URL);
        curl_setopt($channel, CURLOPT_CUSTOMREQUEST, $this->Method);
        curl_setopt($channel, CURLOPT_RETURNTRANSFER, true);
        if (defined('PUMA_API_VALIDATE_SSL')) {
            curl_setopt($channel, CURLOPT_SSL_VERIFYPEER, false);
        }
        $Headers = [];
        foreach ($this->Headers as $header => $value) {
            $Headers[] = $header . ': ' . $value;
        }
        $Headers[] = "Authorization: Bearer " . $this->JWT;
        curl_setopt($channel, CURLOPT_HTTPHEADER, $Headers);
        curl_setopt($channel, CURLOPT_POSTFIELDS, json_encode($this->Body));

        $acceptedHeaders = [];
        curl_setopt($channel, CURLOPT_HEADERFUNCTION,
            function ($curl, $header) use (&$acceptedHeaders) {
                $len = strlen($header);
                $header = explode(':', $header, 2);
                if (count($header) < 2) return $len;
                $acceptedHeaders[trim($header[0])] = trim($header[1]);
                return $len;
            }
        );
        if (curl_errno($channel)) {
            throw new Rawr('Curl error: ' . curl_error($channel), Rawr::INTERNAL_ERROR);
        } else {
            $this->ResponseBody = curl_exec($channel);
            $this->ResponseHeaders = $acceptedHeaders;
            $this->ResponseCode = curl_getinfo($channel, CURLINFO_HTTP_CODE);
        }
        curl_close($channel);
    }

    public function getJWTResponse(): array {
        if (isset($this->ResponseHeaders['Authorization']) and is_string($this->ResponseHeaders['Authorization'])) {
            $JWT = str_replace('Bearer ', '', $this->ResponseHeaders['Authorization']);
            $JWT = explode('.', $JWT);
            if (count($JWT) == 3) {
                return [
                    'Head'      => json_decode(Tokenizer::base64_decode_url($JWT[0]), true),
                    'Payload'   => json_decode(Tokenizer::base64_decode_url($JWT[1]), true),
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

    public function getResponse(): array {
        return [
            'Code'    => $this->ResponseCode,
            'Headers' => $this->ResponseHeaders,
            'Body'    => $this->ResponseBody,
        ];
    }


}