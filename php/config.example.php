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
        'multi' => 0,
        'accounts' => [
            [
                'name' => 'disk_1',
                'path' => '',
                'path_image' => ['/some_public/image'],
                'refresh_token' => '',
            ],
        ],
        'password_file' => 'password',
        'admin_password' => '123456',
        'debug' => true,
        'proxy' => '',
        'cache' => [
            'driver' => 'file',
            'life_time' => 120,
        ]
    ];
}