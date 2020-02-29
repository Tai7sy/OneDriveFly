<?php

/**
 * OneDriveFly
 * @author 风铃
 * @see https://github.com/Tai7sy/OneDriverFly
 */

class Config
{
    public static $config = [
        'name' => 'yes',
        'platform' => 'Normal',
        'multi' => 0,
        'accounts' => [
            [
                'name' => 'disk_1',
                'path' => '',
                'path_image' => [],
                'oauth' => [
                    'redirect_uri' => 'http://localhost',
                    'client_id' => '298004f7-c751-4d56-aba3-b058c0154fd2',
                    'client_secret' => '-^(!BpF-l9/z#[+*5t)alg;[V@;;)_];)@j#^E;T(&^4uD;*&?#2)>H?'
                ],
                'refresh_token' => '',
            ],
        ],
        'debug' => true,
        'proxy' => '',
        'password_file' => 'password',
        'admin_password' => '123456',
    ];
}