<?php

$src = realpath(dirname(__FILE__) . DIRECTORY_SEPARATOR . '..') . DIRECTORY_SEPARATOR;


$config_path = $src . 'config.php';
$vendor_path = $src . 'dist' . DIRECTORY_SEPARATOR . 'vendor.php';
$index_path = $src . 'index.php';


$final = file_get_contents($config_path)
    . "?>\r\n"
    . file_get_contents($index_path)
    . "?>\r\n"
    . file_get_contents($vendor_path);

// unlink($vendor_path);

file_put_contents($src . 'dist' . DIRECTORY_SEPARATOR . 'index.php', $final);
