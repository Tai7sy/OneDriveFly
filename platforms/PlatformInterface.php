<?php


namespace Platforms;


interface PlatformInterface
{
    /**
     * @return \Symfony\Component\HttpFoundation\Request
     */
    public static function request();

    /**
     * @param \Symfony\Component\HttpFoundation\Response $response
     * @return mixed
     */
    public static function response($response);
}