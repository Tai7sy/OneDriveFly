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

function time_format($ISO)
{
    $ISO = str_replace('T', ' ', $ISO);
    $ISO = str_replace('Z', ' ', $ISO);
    //return $ISO;
    return date('Y-m-d H:i:s',strtotime($ISO . " UTC"));
}

function get_timezone($timezone = ' 0800')
{
    $timezones = array( 
        '-1200'=>'Pacific/Kwajalein', 
        '-1100'=>'Pacific/Samoa', 
        '-1000'=>'Pacific/Honolulu', 
        '-0900'=>'America/Anchorage', 
        '-0800'=>'America/Los_Angeles', 
        '-0700'=>'America/Denver', 
        '-0600'=>'America/Mexico_City', 
        '-0500'=>'America/New_York', 
        '-0400'=>'America/Caracas', 
        '-0330'=>'America/St_Johns', 
        '-0300'=>'America/Argentina/Buenos_Aires', 
        '-0200'=>'America/Noronha',
        '-0100'=>'Atlantic/Azores', 
        ' 0000'=>'UTC', 
        ' 0100'=>'Europe/Paris', 
        ' 0200'=>'Europe/Helsinki', 
        ' 0300'=>'Europe/Moscow', 
        ' 0330'=>'Asia/Tehran', 
        ' 0400'=>'Asia/Baku', 
        ' 0430'=>'Asia/Kabul', 
        ' 0500'=>'Asia/Karachi', 
        ' 0530'=>'Asia/Calcutta', //Asia/Colombo
        ' 0600'=>'Asia/Dhaka',
        ' 0630'=>'Asia/Rangoon', 
        ' 0700'=>'Asia/Bangkok', 
        ' 0800'=>'Asia/Shanghai', 
        ' 0900'=>'Asia/Tokyo', 
        ' 0930'=>'Australia/Darwin', 
        ' 1000'=>'Pacific/Guam', 
        ' 1100'=>'Asia/Magadan', 
        ' 1200'=>'Asia/Kamchatka'
    ); 
    return $timezones[$timezone];
}

function output($body, $statusCode = 200, $isBase64Encoded = false, $headers = ['Content-Type' => 'text/html'])
{
    //$headers['Access-Control-Allow-Origin']='*';
    return [
        'isBase64Encoded' => $isBase64Encoded,
        'statusCode' => $statusCode,
        'headers' => $headers,
        'body' => $body
    ];
}

function message($message, $title = 'Message', $statusCode = 200)
{
    return output('<html><meta charset=utf-8><body><h1>' . $title . '</h1><p>' . $message . '</p></body></html>', $statusCode);
}

function spurlencode($str,$splite='') {
    $str = str_replace(' ', '%20',$str);
    $tmp='';
    if ($splite!='') {
        $tmparr=explode($splite,$str);
        for($x=0;$x<count($tmparr);$x++) {
            if ($tmparr[$x]!='') $tmp .= $splite . urlencode($tmparr[$x]);
        }
    } else {
        $tmp = urlencode($str);
    }
    $tmp = str_replace('%2520', '%20',$tmp);
    return $tmp;
}
