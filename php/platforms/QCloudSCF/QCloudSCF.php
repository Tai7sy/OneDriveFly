<?php

namespace Platforms\QCloudSCF;

use Platforms\PlatformInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class QCloudSCF implements PlatformInterface
{
    public static function request($event, $context)
    {
        $event = json_decode(json_encode($event), true);
        foreach ($event['headers'] as $header => $value) {
            $_SERVER['HTTP_' . str_replace('-', '_', strtoupper($header))] = $value;
        }
        $_SERVER['SERVER_NAME'] = $_SERVER['HTTP_HOST'];
        $_SERVER['SERVER_ADDR'] = '127.0.0.1';
        $_SERVER['SERVER_PORT'] = strpos($_SERVER['SERVER_NAME'], ':') === FALSE ? 80 : (int)explode(':', $_SERVER['SERVER_NAME'])[1];
        $_SERVER['REMOTE_ADDR'] = $event['requestContext']['sourceIp'];
        $_SERVER['DOCUMENT_ROOT'] = '/tmp/';
        $_SERVER['REQUEST_SCHEME'] = isset($_SERVER['HTTP_ORIGIN']) && strpos($_SERVER['HTTP_ORIGIN'], 'https://') !== FALSE ? 'https' : 'http';
        $_SERVER['SERVER_ADMIN'] = 'https://github.com/Tai7sy/OneDriveFly';
        $_SERVER['DOCUMENT_ROOT'] = dirname(__FILE__);
        $_SERVER['SCRIPT_FILENAME'] = __FILE__;
        $_SERVER['REDIRECT_URL'] = $event['requestContext']['path'] === '/' ? $event['path'] : substr($event['path'], strlen($event['requestContext']['path']));
        $_SERVER['SERVER_PROTOCOL'] = 'HTTP/1.1';
        $_SERVER['REQUEST_METHOD'] = $event['httpMethod'];
        $_SERVER['QUERY_STRING'] = http_build_query($event['queryString']);
        $_SERVER['REQUEST_URI'] = $_SERVER['REDIRECT_URL'] . ($_SERVER['QUERY_STRING'] ? '?' : '') . $_SERVER['QUERY_STRING'];

        $_POST = [];
        if ($event['headers']['content-type'] === 'application/x-www-form-urlencoded') {
            $posts = explode('&', $event['body']);
            foreach ($posts as $post) {
                $pos = strpos($post, '=');
                $_POST[urldecode(substr($post, 0, $pos))] = urldecode(substr($post, $pos + 1));
            }
        } elseif (substr($event['headers']['content-type'], 0, 19) === 'multipart/form-data') {
            // improve like this
            // https://gist.github.com/jas-/5c3fdc26fedd11cb9fb5#file-class-stream-php
            throw new \Exception('unsupported [multipart/form-data]');
        }

        $_COOKIE = [];
        $cookies = explode('; ', $event['headers']['cookie']);
        foreach ($cookies as $cookie) {
            $pos = strpos($cookie, '=');
            $_COOKIE[urldecode(substr($cookie, 0, $pos))] = urldecode(substr($cookie, $pos + 1));
        }

        return new Request(
            isset($event['queryString']) ? $event['queryString'] : [],
            $_POST,
            [],
            $_COOKIE,
            [],
            $_SERVER
        );
    }

    /**
     * @param Response $response
     * @return array|mixed
     */
    public static function response($response)
    {
        return [
            'isBase64Encoded' => false,
            'statusCode' => $response->getStatusCode(),
            'headers' => array_map(function ($values) {
                return $values[0];
            }, $response->headers->all()),
            'body' => $response->getContent()
        ];
    }
}