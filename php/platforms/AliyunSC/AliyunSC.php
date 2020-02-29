<?php


namespace Platforms\AliyunSC;

use Platforms\PlatformInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class AliyunSC implements PlatformInterface
{
    public static function request($request, $context)
    {
        $headers = $request->getHeaders();
        $headers = array_column(array_map(function ($k, $v) {
            return [strtolower($k), is_array($v) ? $v[0] : $v];
        }, array_keys($headers), $headers), 1, 0);

        foreach ($headers as $header => $value) {
            $_SERVER['HTTP_' . str_replace('-', '_', strtoupper($header))] = $value;
        }
        $_SERVER['SERVER_NAME'] = $_SERVER['HTTP_HOST'];
        $_SERVER['SERVER_ADDR'] = '127.0.0.1';
        $_SERVER['SERVER_PORT'] = strpos($_SERVER['SERVER_NAME'], ':') === FALSE ? 80 : (int)explode(':', $_SERVER['SERVER_NAME'])[1];
        $_SERVER['REMOTE_ADDR'] = $request->getAttribute('clientIP');
        $_SERVER['DOCUMENT_ROOT'] = '/tmp/';
        $_SERVER['REQUEST_SCHEME'] = isset($_SERVER['HTTP_ORIGIN']) && strpos($_SERVER['HTTP_ORIGIN'], 'https://') !== FALSE ? 'https' : 'http';
        $_SERVER['SERVER_ADMIN'] = 'https://github.com/Tai7sy/OneDriveFly';
        $_SERVER['DOCUMENT_ROOT'] = dirname(__FILE__);
        $_SERVER['SCRIPT_FILENAME'] = __FILE__;
        $_SERVER['REDIRECT_URL'] = path_format($request->getAttribute('path'), true);
        $_SERVER['SERVER_PROTOCOL'] = 'HTTP/1.1';
        $_SERVER['REQUEST_METHOD'] = $request->getMethod();
        $_SERVER['QUERY_STRING'] = http_build_query($request->getQueryParams());
        $_SERVER['REQUEST_URI'] = $_SERVER['REDIRECT_URL'] . ($_SERVER['QUERY_STRING'] ? '?' : '') . $_SERVER['QUERY_STRING'];

        $_POST = [];
        if ($headers['content-type'] === 'application/x-www-form-urlencoded') {
            $posts = explode('&', $request->getBody()->getContents());
            foreach ($posts as $post) {
                $pos = strpos($post, '=');
                $_POST[urldecode(substr($post, 0, $pos))] = urldecode(substr($post, $pos + 1));
            }
        } elseif (substr($headers['content-type'], 0, 19) === 'multipart/form-data') {
            // improve like this
            // https://gist.github.com/jas-/5c3fdc26fedd11cb9fb5#file-class-stream-php
            throw new \Exception('unsupported [multipart/form-data]');
        }

        $_COOKIE = [];
        if (isset($headers['cookie'])) {
            $cookies = explode('; ', $headers['cookie']);
            foreach ($cookies as $cookie) {
                $pos = strpos($cookie, '=');
                $_COOKIE[urldecode(substr($cookie, 0, $pos))] = urldecode(substr($cookie, $pos + 1));
            }
        }

        return new Request(
            $request->getQueryParams(),
            $_POST,
            [],
            $_COOKIE,
            [],
            $_SERVER,
            $request->getBody()->getContents()
        );
    }

    /**
     * @param Response $response
     * @return array|mixed
     */
    public static function response($response)
    {
        return new \RingCentral\Psr7\Response(
            $response->getStatusCode(),
            $response->headers->all(),
            $response->getContent()
        );
    }
}