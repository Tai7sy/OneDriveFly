<?php


namespace Platforms;


interface PlatformInterface
{
    /**
     * @param \Symfony\Component\HttpFoundation\Response $response
     * @return mixed
     */
    public static function response($response);
}