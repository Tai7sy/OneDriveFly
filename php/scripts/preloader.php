<?php

// Create a preloader for vendor
require __DIR__ . '/../vendor/classpreloader/classpreloader/src/ClassLoader.php';

use ClassPreloader\ClassLoader;

$config = ClassLoader::getIncludes(function (ClassLoader $loader) {
    require __DIR__ . '/../vendor/autoload.php';
    $loader->register();

    require __DIR__ . '/../index.php';
    global $config;
    $config['multi'] = 0;
    $config['accounts'][0]['refresh_token'] = '';

    try {
        new \Library\Ext();
        new \Library\Lang();
        new \Library\OneDrive(null);

        new \Platforms\Normal\Normal();
        new \Platforms\QCloudSCF\QCloudSCF();
        new \Platforms\AliyunSC\AliyunSC();

        new \Doctrine\Common\Cache\FilesystemCache(sys_get_temp_dir(),'.delete_me');
        new \Doctrine\Common\Cache\VoidCache();
        new \Symfony\Component\HttpFoundation\Request();
        new \Symfony\Component\HttpFoundation\Response();

        @ob_start();
        @cgi_entry();
    } finally {
        @ob_clean();
    }
    @ob_clean();

});

// Add a regex filter that requires all classes to match the regex.
// $config->addInclusiveFilter('/Foo/');

// Add a regex filter that requires that a class does not match the filter.
// $config->addExclusiveFilter('/Foo/');


$config->addExclusiveFilter('/config\.php/');
return $config;