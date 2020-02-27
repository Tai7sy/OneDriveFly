<?php

namespace Platforms\QCloudSCF;

use Platforms\PlatformInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class QCloudSCF implements PlatformInterface
{
    private static $request;

    public static function request()
    {
        if (!self::$request) {
            self::$request = new Request(
                $_GET,
                $_POST,
                [],
                $_COOKIE,
                $_FILES,
                $_SERVER
            );
        }
        return self::$request;
    }

    public static function response($response)
    {
        return [
            'isBase64Encoded' => false,
            'statusCode' => $response->getStatusCode(),
            'headers' => $response->headers->all(),
            'body' => $response->getContent()
        ];
    }
}