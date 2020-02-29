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

    public static function print_input($event, $context)
    {
        if (strlen(json_encode($event['body'])) > 500)
            $event['body'] = substr($event['body'], 0,
                    strpos($event['body'], 'base64') + 30) . '...Too Long!...' . substr($event['body'], -50);
        echo urldecode(json_encode($event, JSON_PRETTY_PRINT)) . "\r\n\r\n" .
            urldecode(json_encode($context, JSON_PRETTY_PRINT));
    }


    function GetGlobalVariable($event)
    {
        $_GET = $event['queryString'];
        $postbody = explode("&", $event['body']);
        foreach ($postbody as $postvalues) {
            $pos = strpos($postvalues, "=");
            $_POST[urldecode(substr($postvalues, 0, $pos))] = urldecode(substr($postvalues, $pos + 1));
        }
        $cookiebody = explode("; ", $event['headers']['cookie']);
        foreach ($cookiebody as $cookievalues) {
            $pos = strpos($cookievalues, "=");
            $_COOKIE[urldecode(substr($cookievalues, 0, $pos))] = urldecode(substr($cookievalues, $pos + 1));
        }
    }

    function GetPathSetting($event, $context)
    {
        $_SERVER['function_name'] = $context['function_name'];
        $host_name = $event['headers']['host'];
        $serviceId = $event['requestContext']['serviceId'];
        $public_path = path_format(getenv('public_path'));
        $private_path = path_format(getenv('private_path'));
        $domain_path = getenv('domain_path');
        $tmp_path = '';
        if ($domain_path != '') {
            $tmp = explode("|", $domain_path);
            foreach ($tmp as $multidomain_paths) {
                $pos = strpos($multidomain_paths, ":");
                $tmp_path = path_format(substr($multidomain_paths, $pos + 1));
                if (substr($multidomain_paths, 0, $pos) == $host_name) $private_path = $tmp_path;
            }
        }
        // public_path is not Parent Dir of private_path. public_path 不能是 private_path 的上级目录。
        if ($tmp_path != '') if ($public_path == substr($tmp_path, 0, strlen($public_path))) $public_path = $tmp_path;
        if ($public_path == substr($private_path, 0, strlen($public_path))) $public_path = $private_path;
        if ($serviceId === substr($host_name, 0, strlen($serviceId))) {
            $_SERVER['base_path'] = '/' . $event['requestContext']['stage'] . '/' . $_SERVER['function_name'] . '/';
            $_SERVER['list_path'] = $public_path;
            $_SERVER['Region'] = substr($host_name, strpos($host_name, '.') + 1);
            $_SERVER['Region'] = substr($_SERVER['Region'], 0, strpos($_SERVER['Region'], '.'));
            $path = substr($event['path'], strlen('/' . $_SERVER['function_name'] . '/'));
        } else {
            $_SERVER['base_path'] = $event['requestContext']['path'];
            $_SERVER['list_path'] = $private_path;
            $_SERVER['Region'] = getenv('Region');
            $path = substr($event['path'], strlen($event['requestContext']['path']));
        }
        if (substr($path, -1) == '/') $path = substr($path, 0, -1);
        if (empty($_SERVER['list_path'])) {
            $_SERVER['list_path'] = '/';
        } else {
            $_SERVER['list_path'] = spurlencode($_SERVER['list_path'], '/');
        }
        $_SERVER['is_imgup_path'] = is_imgup_path($path);
        $_SERVER['PHP_SELF'] = path_format($_SERVER['base_path'] . $path);
        $_SERVER['REMOTE_ADDR'] = $event['requestContext']['sourceIp'];
        $_SERVER['ajax'] = 0;
        if ($event['headers']['x-requested-with'] == 'XMLHttpRequest') {
            $_SERVER['ajax'] = 1;
        }
        /*
            $referer = $event['headers']['referer'];
            $tmpurl = substr($referer,strpos($referer,'//')+2);
            $refererhost = substr($tmpurl,0,strpos($tmpurl,'/'));
            if ($refererhost==$host_name) {
                // Guest only upload from this site. 仅游客上传用，referer不对就空值，无法上传
                $_SERVER['current_url'] = substr($referer,0,strpos($referer,'//')) . '//' . $host_name.$_SERVER['PHP_SELF'];
            } else {
                $_SERVER['current_url'] = '';
            }
        */
        return $path;
    }
}