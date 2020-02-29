<?php

$src = realpath(dirname(__FILE__) . DIRECTORY_SEPARATOR . '..') . DIRECTORY_SEPARATOR;
$dist = realpath($src . '..' . DIRECTORY_SEPARATOR . 'dist') . DIRECTORY_SEPARATOR;

$config_path = $src . 'config.example.php';
$vendor_path = $dist . 'vendor.php';
$functions_path = $src . 'library' . DIRECTORY_SEPARATOR . 'functions.php';
$index_path = $src . 'index.php';

$final = "<?php\r\nnamespace {\r\n" . substr(file_get_contents($config_path), 7) . "\r\n}"
    . "?>\r\n"
    . "<?php\r\nnamespace {\r\n" . substr(php_strip_whitespace($functions_path), 7) . "\r\n}"
    . "?>\r\n"
    . php_strip_whitespace($vendor_path)
    . "?>\r\n"
    . "<?php\r\nnamespace {\r\n" . substr(file_get_contents($index_path), 50) . "\r\n}";

unlink($vendor_path);

file_put_contents($dist . 'index.php', $final);
