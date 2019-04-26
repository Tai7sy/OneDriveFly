<?php

function curl_request($url, $data = false, $headers = [])
{
    if (!isset($headers['Accept'])) $headers['Accept'] = '*/*';
    if (!isset($headers['Referer'])) $headers['Referer'] = $url;
    if (!isset($headers['Content-Type'])) $headers['Content-Type'] = 'application/x-www-form-urlencoded';
    $sendHeaders = array();
    foreach ($headers as $headerName => $headerVal) {
        $sendHeaders[] = $headerName . ': ' . $headerVal;
    }

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);

    if ($data !== false) {
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    }

    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // 返回获取的输出文本流
    curl_setopt($ch, CURLOPT_HEADER, 0);         // 将头文件的信息作为数据流输出
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $sendHeaders);
    $response = curl_exec($ch);
    curl_close($ch);

    return $response;
}

function path_format($path)
{
    $path = '/' . $path;
    while (strpos($path, '//') !== FALSE) {
        $path = str_replace('//', '/', $path);
    }
    return $path;
}

function size_format($byte)
{
    $i = 0;
    while (abs($byte) >= 1024) {
        $byte = $byte / 1024;
        $i++;
        if ($i == 3) break;
    }
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    $ret = round($byte, 2);
    return ($ret . ' ' . $units[$i]);
}

function ISO_format($ISO)
{
    $ISO = str_replace('T', ' ', $ISO);
    $ISO = str_replace('Z', ' ', $ISO);
    return $ISO;
}