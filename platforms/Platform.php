<?php

namespace Platforms;

use Platforms\Normal\Normal;
use Platforms\QCloudSCF\QCloudSCF;

class Platform implements PlatformInterface
{

    const PLATFORM_NORMAL = 0;
    const PLATFORM_QCLOUD_SCF = 1;

    /**
     * @return \Symfony\Component\HttpFoundation\Request
     */
    public static function request()
    {
        global $config;
        switch ($config['platform']) {
            default:
            case self::PLATFORM_NORMAL:
                return Normal::request();
            case self::PLATFORM_QCLOUD_SCF:
                return QCloudSCF::request();
        }
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Response $response
     * @return mixed
     */
    public static function response($response)
    {
        global $config;
        switch ($config['platform']) {
            default:
            case self::PLATFORM_NORMAL:
                return Normal::response($response);
            case self::PLATFORM_QCLOUD_SCF:
                return QCloudSCF::response($response);
        }
    }
}