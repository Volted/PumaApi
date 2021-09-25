<?php

namespace PumaAPI\Model;

trait Config {

    private $Config;

    private function _getConfig($ManifestPath): void {
        $this->Config = parse_ini_file($ManifestPath . DIRECTORY_SEPARATOR . 'service.ini', true) ?? [];
    }

    public static function base64_encode_url($string) {
        return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($string));
    }

    public static function base64_decode_url($string) {
        return base64_decode(str_replace(['-', '_'], ['+', '/'], $string));
    }
}