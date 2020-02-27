<?php

// Create a preloader for vendor
require __DIR__ . '/../vendor/classpreloader/classpreloader/src/ClassLoader.php';

use ClassPreloader\ClassLoader;

$config = ClassLoader::getIncludes(function (ClassLoader $loader) {
    require __DIR__ . '/../vendor/autoload.php';
    $loader->register();

    \Platforms\Platform::request();
    \Platforms\Normal\Normal::request();
    \Platforms\QCloudSCF\QCloudSCF::request();

    \Library\OneDrive::files();


});

// Add a regex filter that requires all classes to match the regex.
// $config->addInclusiveFilter('/Foo/');

// Add a regex filter that requires that a class does not match the filter.
// $config->addExclusiveFilter('/Foo/');

return $config;