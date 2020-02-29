<?php


namespace Platforms\Normal;


use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class Normal
{

    private static $request;

    /**
     * @return Request
     */
    public static function request()
    {
        if (!self::$request) {
            self::$request = Request::createFromGlobals();
        }
        return self::$request;
    }

    /**
     * @param Response $response
     * @return mixed
     */
    public static function response($response)
    {
        return $response->send();
    }
}