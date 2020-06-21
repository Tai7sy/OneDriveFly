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
            if(self::$request->get('s')){
                $new_uri = self::$request->getBaseUrl() . self::$request->get('s');
                if(!empty($_SERVER['UNENCODED_URL'])){
                    $_SERVER['UNENCODED_URL'] = $new_uri;
                }else{
                    $_SERVER['REQUEST_URI'] = $new_uri;
                }
                self::$request = Request::createFromGlobals();
            }
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