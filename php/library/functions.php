<?php

function get_timezone($timezone = '8')
{
    $timezones = array(
        '-12' => 'Pacific/Kwajalein',
        '-11' => 'Pacific/Samoa',
        '-10' => 'Pacific/Honolulu',
        '-9' => 'America/Anchorage',
        '-8' => 'America/Los_Angeles',
        '-7' => 'America/Denver',
        '-6' => 'America/Mexico_City',
        '-5' => 'America/New_York',
        '-4' => 'America/Caracas',
        '-3.5' => 'America/St_Johns',
        '-3' => 'America/Argentina/Buenos_Aires',
        '-2' => 'America/Noronha',
        '-1' => 'Atlantic/Azores',
        '0' => 'UTC',
        '1' => 'Europe/Paris',
        '2' => 'Europe/Helsinki',
        '3' => 'Europe/Moscow',
        '3.5' => 'Asia/Tehran',
        '4' => 'Asia/Baku',
        '4.5' => 'Asia/Kabul',
        '5' => 'Asia/Karachi',
        '5.5' => 'Asia/Calcutta', //Asia/Colombo
        '6' => 'Asia/Dhaka',
        '6.5' => 'Asia/Rangoon',
        '7' => 'Asia/Bangkok',
        '8' => 'Asia/Shanghai',
        '9' => 'Asia/Tokyo',
        '9.5' => 'Australia/Darwin',
        '10' => 'Pacific/Guam',
        '11' => 'Asia/Magadan',
        '12' => 'Asia/Kamchatka'
    );
    if (empty($timezone)) $timezone = '8';
    return $timezones[$timezone];
}

/**
 * @param \Exception $e
 * @return string
 */
function error_trace($e)
{
    $str = '<pre>' . $e->getTraceAsString() . '</pre>';
    $str = str_replace(realpath(__DIR__ . '/../') . DIRECTORY_SEPARATOR, '', $str);
    return $str;
}

function path_format($path, $encode = false, $split = '/')
{

    $items = explode($split, $path);
    $result = [];

    foreach ($items as $item) {
        if (empty($item)) continue;

        if ($item === '.') continue;

        if ($item === '..') {
            if (count($result) > 0) {
                array_pop($result);
                continue;
            }
        }
        if ($encode && strpos($path, '%') === FALSE) {

            $item = urlencode($item);
            $item = str_replace('+', '%20', $item);
            array_push($result, $item);


        } else {
            array_push($result, $item);
        }
    }
    return $split . join($split, $result);
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
    return date('Y-m-d H:i:s', strtotime($ISO . " UTC"));
}

/**
 * curl
 * @param string $url
 * @param string|int $method
 * @param bool $data
 * @param array $headers
 * @param null $status
 * @return bool|string
 * @throws \Exception
 */
function curl($url, $method = 0, $data = null, $headers = [], &$status = null)
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

    if ($data !== null || $method === 1) {
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    }

    if (is_string($method)) {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    }

    global $config;
    if (!empty($config['proxy'])) {
        $proxy = $config['proxy'];
        if (strpos($proxy, 'socks4://')) {
            $proxy = str_replace('socks4://', $proxy, $proxy);
            $proxy_type = CURLPROXY_SOCKS4;
        } elseif (strpos($proxy, 'socks4a://')) {
            $proxy = str_replace('socks4a://', $proxy, $proxy);
            $proxy_type = CURLPROXY_SOCKS4A;
        } elseif (strpos($proxy, 'socks5://')) {
            $proxy = str_replace('socks5://', $proxy, $proxy);
            $proxy_type = CURLPROXY_SOCKS5_HOSTNAME;
        } else {
            $proxy = str_replace('http://', $proxy, $proxy);
            $proxy = str_replace('https://', $proxy, $proxy);
            $proxy_type = CURLPROXY_HTTP;
        }
        curl_setopt($ch, CURLOPT_PROXY, $proxy); // '121.1.1.1:58082'
        curl_setopt($ch, CURLOPT_PROXYTYPE, $proxy_type); // 默认值: CURLPROXY_HTTP
    }

    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $sendHeaders);
    $response = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if (curl_errno($ch)) {
        throw new \Exception(curl_error($ch), 0);
    }
    curl_close($ch);
    return $response;
}