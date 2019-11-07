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

function config_oauth()
{
    global $oauth;
    if (getenv('Onedrive_ver')=='MS') {
        // MS 默认（支持商业版与个人版）
        // https://portal.azure.com
        $oauth['client_id'] = '4da3e7f2-bf6d-467c-aaf0-578078f0bf7c';
        $oauth['client_secret'] = '7/+ykq2xkfx:.DWjacuIRojIaaWL0QI6';
        $oauth['oauth_url'] = 'https://login.microsoftonline.com/common/oauth2/v2.0/';
        $oauth['api_url'] = 'https://graph.microsoft.com/v1.0/me/drive/root';
        $oauth['scope'] = 'https://graph.microsoft.com/Files.ReadWrite.All offline_access';
    }
    if (getenv('Onedrive_ver')=='CN') {
        // CN 世纪互联
        // https://portal.azure.cn
        $oauth['client_id'] = '04c3ca0b-8d07-4773-85ad-98b037d25631';
        $oauth['client_secret'] = 'h8@B7kFVOmj0+8HKBWeNTgl@pU/z4yLB';
        $oauth['oauth_url'] = 'https://login.partner.microsoftonline.cn/common/oauth2/v2.0/';
        $oauth['api_url'] = 'https://microsoftgraph.chinacloudapi.cn/v1.0/me/drive/root';
        $oauth['scope'] = 'https://microsoftgraph.chinacloudapi.cn/Files.ReadWrite.All offline_access';
    }

    $oauth['client_secret'] = urlencode($oauth['client_secret']);
    $oauth['scope'] = urlencode($oauth['scope']);
}

function get_refresh_token()
{
    global $oauth;
    global $config;

    if (getenv('SecretId')=='' || getenv('SecretKey')=='') return message('Please <a href="https://console.cloud.tencent.com/cam/capi" target="_blank">create SecretId & SecretKey</a> and add them in the environments First!<br>', 'Error', 500);
    $url = path_format($_SERVER['PHP_SELF'] . '/');

    if ($_GET['authorization_code'] && isset($_GET['code'])) {
        $ret = json_decode(curl_request($oauth['oauth_url'] . 'token', 'client_id=' . $oauth['client_id'] .'&client_secret=' . $oauth['client_secret'] . '&grant_type=authorization_code&requested_token_use=on_behalf_of&redirect_uri=' . $oauth['redirect_uri'] .'&code=' . $_GET['code']), true);
        if (isset($ret['refresh_token'])) {
            $tmptoken=$ret['refresh_token'];
            $str = '
        refresh_token :<br>';
            for ($i=1;strlen($tmptoken)>0;$i++) {
                $t['t' . $i] = substr($tmptoken,0,128);
                $str .= '
            t' . $i . ':<textarea readonly style="width: 95%">' . $t['t' . $i] . '</textarea><br><br>';
                $tmptoken=substr($tmptoken,128);
            }
            $str .= '
        Please add t1-t'.--$i.' to environments.
        <script>
            var texta=document.getElementsByTagName(\'textarea\');
            for(i=0;i<texta.length;i++) {
                texta[i].style.height = texta[i].scrollHeight + \'px\';
            }
        </script>';
            if (getenv('SecretId')!='' && getenv('SecretKey')!='') {
                updataEnvironment($config['function_name'], $config['Region'], $t);
            //return output('', 302, [ 'Location' => $url ]);
            //$str .= '            location.href = "' . $url . '";';
                $str .= '
            <meta http-equiv="refresh" content="5;URL=' . $url . '">';
            }
            return message($str, 'Wait 5s jump to index page');
        }
        return message('<pre>' . json_encode($ret, JSON_PRETTY_PRINT) . '</pre>', 500);
    }

    if ($_GET['install2']) {
        if (getenv('Onedrive_ver')=='MS' || getenv('Onedrive_ver')=='CN') {
            return message('
    Go to OFFICE <a href="" id="a1">Get a refresh_token</a>
    <script>
        url=location.protocol + "//" + location.host + "'.$url.'";
        url="'. $oauth['oauth_url'] .'authorize?scope='. $oauth['scope'] .'&response_type=code&client_id='. $oauth['client_id'] .'&redirect_uri='. $oauth['redirect_uri'] . '&state=' .'"+encodeURIComponent(url);
        document.getElementById(\'a1\').href=url;
        //window.open(url,"_blank");
        location.href = url;
    </script>
    ', 'Wait 1s', 201);
        }
    }

    if ($_GET['install1']) {
        echo $_POST['Onedrive_ver'];
        if ($_POST['Onedrive_ver']=='MS' || $_POST['Onedrive_ver']=='CN') {
            $tmp['Onedrive_ver'] = $_POST['Onedrive_ver'];
            $response = json_decode(updataEnvironment($config['function_name'], $config['Region'], $tmp), true)['Response'];
            //getfunctioninfo($config['function_name'], $config['Region']);
            sleep(2);
            if (getenv('Onedrive_ver')=='MS') {
                $title = '国际版（支持商业版与个人版）';
            } elseif (getenv('Onedrive_ver')=='CN') {
                $title = '国内世纪互联版';
            } else $title = '环境变量Onedrive_ver应该已经写入，等待更新';
            $html = '稍等3秒<meta http-equiv="refresh" content="3;URL=' . $url . '?install2">';
            if (isset($response['Error'])) {
                $html = $response['Error']['Code'] . '<br>
' . $response['Error']['Message'] . '<br><br>
function_name:' . $config['function_name'] . '<br>
Region:' . $config['Region'];
                $title = 'Error';
            }
            return message($html, $title, 201);
        }
    }

    $html = '
    <form action="?install1" method="post">
        <input type="radio" name="Onedrive_ver" value="MS" checked>MS:默认（支持商业版与个人版） <input type="radio" name="Onedrive_ver" value="CN">CN:世纪互联
        <br>
        <input type="submit" value="确认">
    </form>';
    return message($html, '请选择Onedrive版本：', 201);    
}

function get_timezone($timezone = '8')
{
    $timezones = array( 
        '-12'=>'Pacific/Kwajalein', 
        '-11'=>'Pacific/Samoa', 
        '-10'=>'Pacific/Honolulu', 
        '-9'=>'America/Anchorage', 
        '-8'=>'America/Los_Angeles', 
        '-7'=>'America/Denver', 
        '-6'=>'America/Mexico_City', 
        '-5'=>'America/New_York', 
        '-4'=>'America/Caracas', 
        '-3.5'=>'America/St_Johns', 
        '-3'=>'America/Argentina/Buenos_Aires', 
        '-2'=>'America/Noronha',
        '-1'=>'Atlantic/Azores', 
        '0'=>'UTC', 
        '1'=>'Europe/Paris', 
        '2'=>'Europe/Helsinki', 
        '3'=>'Europe/Moscow', 
        '3.5'=>'Asia/Tehran', 
        '4'=>'Asia/Baku', 
        '4.5'=>'Asia/Kabul', 
        '5'=>'Asia/Karachi', 
        '5.5'=>'Asia/Calcutta', //Asia/Colombo
        '6'=>'Asia/Dhaka',
        '6.5'=>'Asia/Rangoon', 
        '7'=>'Asia/Bangkok', 
        '8'=>'Asia/Shanghai', 
        '9'=>'Asia/Tokyo', 
        '9.5'=>'Australia/Darwin', 
        '10'=>'Pacific/Guam', 
        '11'=>'Asia/Magadan', 
        '12'=>'Asia/Kamchatka'
    );
    if ($timezone=='') $timezone = '8';
    return $timezones[$timezone];
}

function output($body, $statusCode = 200, $headers = ['Content-Type' => 'text/html'], $isBase64Encoded = false)
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

function clearbehindvalue($path,$page1,$maxpage,$pageinfocache)
{
    for ($page=$page1+1;$page<$maxpage;$page++) {
        $pageinfocache['nextlink_' . $path . '_page_' . $page] = '';
    }
    return $pageinfocache;
}

function encode_str_replace($str)
{
    $str = str_replace('&','&amp;',$str);
    $str = str_replace('+','%2B',$str);
    $str = str_replace('#','%23',$str);
    return $str;
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

function passhidden($path)
{
    global $config;
    $path = str_replace('+','%2B',$path);
    $path = str_replace('&amp;','&', path_format(urldecode($path)));
    if ($config['passfile'] != '') {
        if (substr($path,-1)=='/') $path=substr($path,0,-1);
        $hiddenpass=gethiddenpass($path,$config['passfile']);
        if ($hiddenpass != '') {
            return comppass($hiddenpass);
        } else {
            return 1;
        }
    } else {
        return 0;
    }
    return 4;
}

function gethiddenpass($path,$passfile)
{
    $ispassfile = fetch_files(spurlencode(path_format($path . '/' . $passfile),'/'));
    //echo $path . '<pre>' . json_encode($ispassfile, JSON_PRETTY_PRINT) . '</pre>';
    if (isset($ispassfile['file'])) {
        $passwordf=explode("\n",curl_request($ispassfile['@microsoft.graph.downloadUrl']));
        $password=$passwordf[0];
        $password=md5($password);
        return $password;
    } else {
        if ($path !== '' ) {
            $path = substr($path,0,strrpos($path,'/'));
            return gethiddenpass($path,$passfile);
        } else {
            return '';
        }
    }
    return '';
}

function comppass($pass) {
    if ($_POST['password1'] !== '') if (md5($_POST['password1']) === $pass ) {
        date_default_timezone_set('UTC');
        $_SERVER['Set-Cookie'] = 'password='.$pass.'; expires='.date(DATE_COOKIE,strtotime('+1hour'));
        date_default_timezone_set(get_timezone($_COOKIE['timezone']));
        return 2;
    }
    if ($_COOKIE['password'] !== '') if ($_COOKIE['password'] === $pass ) return 3;
    return 4;
}
