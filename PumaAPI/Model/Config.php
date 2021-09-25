<?php

namespace PumaAPI\Model;

trait Config {

    private $Config;

    private function _getConfig(): void {
        $this->Config = parse_ini_file(__DIR__ . DIRECTORY_SEPARATOR . '__model.ini', true) ?? [];
    }

    public static function base64_encode_url($string) {
        return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($string));
    }

    public static function base64_decode_url($string) {
        return base64_decode(str_replace(['-', '_'], ['+', '/'], $string));
    }
}