<?php
include 'vendor/autoload.php';
include 'functions.php';
global $event1;
global $config;
$config = [
    'sitename' => getenv('sitename'),
    'passfile' => getenv('passfile'),
    'refresh_token' => '',
];
//在环境变量添加：
/*
sitename：       网站的名称，不添加会显示为‘请在环境变量添加sitename’
public_path：    使用API长链接访问时，网盘里公开的路径，不设置时默认为'/'
private_path：   使用私人域名访问时，网盘的路径（可以一样），不设置时默认为'/'
passfile：       自定义密码文件的名字，可以是'.password'，也可以是'aaaa.txt'等等；
        　       密码是这个文件的内容，可以空格、可以中文；列目录时不会显示，只有知道密码才能查看或下载此文件。
t1,t2,t3,t4,t5,t6,t7：把refresh_token按128字节切开来放在环境变量，不想再出现ctrl+c、ctrl+v把token也贴到github的事了
*/

function main_handler($event, $context)
{
    global $event1;
    global $config;
    
    $event = json_decode(json_encode($event), true);
    $context = json_decode(json_encode($context), true);
    $function_name = $context['function_name'];
    $event1 = $event;
    $host_name = $event['headers']['host'];
    $serviceId = $event['requestContext']['serviceId'];
    if ( $serviceId === substr($host_name,0,strlen($serviceId)) ) {
        $config['base_path'] = '/'.$event['requestContext']['stage'].'/'.$function_name.'/';
        $config['list_path'] = getenv('public_path');
        $path = substr($event['path'], strlen('/'.$function_name));
    } else {
        $config['base_path'] = getenv('base_path');
        if (empty($config['base_path'])) $config['base_path'] = '/';
        $config['list_path'] = getenv('private_path');
        $path = substr($event['path'], strlen($event['requestContext']['path']));
    }
    if (empty($config['list_path'])) {
        $config['list_path'] = '/';
    } else {
        $config['list_path'] = spurlencode($config['list_path']) ;
    }
    if (empty($config['sitename'])) $config['sitename'] = '请在环境变量添加sitename';
    $_GET = $event['queryString'];
    $_SERVER['PHP_SELF'] = path_format($config['base_path'] . $path);
    $_POSTbody = explode("&",$event1['body']);
    foreach ($_POSTbody as $postvalues){
        $tmp=explode("=",$postvalues);
        $_POST[urldecode($tmp[0])]=urldecode($tmp[1]);
    }
    $cookiebody = explode(";",$event1['headers']['cookie']);
    foreach ($cookiebody as $cookievalues){
        $tmp=explode("=",$cookievalues);
        $_COOKIE[$tmp[0]]=$tmp[1];
    }
    $config['function_name'] = $function_name;

    if (!$config['base_path']) {
        return message('Missing env <code>base_path</code>');
    }
    if (!$config['refresh_token']) $config['refresh_token'] = getenv('t1').getenv('t2').getenv('t3').getenv('t4').getenv('t5').getenv('t6').getenv('t7');
    if (!$config['refresh_token']) {
        if (strpos($path, '/authorization_code') !== FALSE && isset($_GET['code'])) {
            return message(get_refresh_token($_GET['code']));
        }
        return message('
Please set a <code>refresh_token</code> in environments<br>
<a target="_blank" href="https://login.microsoftonline.com/common/oauth2/authorize?response_type=code&client_id=298004f7-c751-4d56-aba3-b058c0154fd2&redirect_uri=http://localhost/authorization_code">Get a refresh_token</a><br><br>
When redirected, replace <code>http://localhost</code> with current host', 'Error', 500);
    }
    if (getenv('admin')!='') {
    if ($_COOKIE[$function_name]==md5(getenv('admin'))) {
        $config['admin']=1;
    } else {
        $config['admin']=0;
    }
    if ($_GET['admin']) {
        if ($_POST['password1']==getenv('admin')) return adminform($function_name,md5($_POST['password1']),$_SERVER['PHP_SELF']);
        return adminform();
    }}

    return list_files($path);
}

function get_refresh_token($code)
{
    $ret = json_decode(curl_request(
        'https://login.microsoftonline.com/common/oauth2/token',
        'client_id=298004f7-c751-4d56-aba3-b058c0154fd2&client_secret=-%5E%28%21BpF-l9%2Fz%23%5B%2B%2A5t%29alg%3B%5BV%40%3B%3B%29_%5D%3B%29%40j%23%5EE%3BT%28%26%5E4uD%3B%2A%26%3F%232%29%3EH%3F&grant_type=authorization_code&resource=https://graph.microsoft.com/&redirect_uri=http://localhost/authorization_code&code=' . $code), true);
    if (isset($ret['refresh_token'])) {
        $tmptoken=$ret['refresh_token'];
        $str = 'split:<br>';
        for ($i=1;strlen($tmptoken)>0;$i++) {
            $str .= 't' . $i . ':<textarea readonly style="width: 95%;height: 45px">' . substr($tmptoken,0,128) . '</textarea>';
            $tmptoken=substr($tmptoken,128);
        }
        return '<table width=100%><tr><td width=50%>refresh_token:<textarea readonly style="width: 100%;height: 500px">' . $ret['refresh_token'] . '</textarea></td><td>' . $str . '</td></tr></table>';
    }
    return '<pre>' . json_encode($ret, JSON_PRETTY_PRINT) . '</pre>';
}

function fetch_files($path = '/')
{
    global $config;
    $path1 = path_format($path);
    $path = path_format($config['list_path'] . path_format($path));
    $cache = null;
    $cache = new \Doctrine\Common\Cache\FilesystemCache(sys_get_temp_dir(), '.qdrive');
    if (!($files = $cache->fetch('path_' . $path))) {

        if (!($access_token = $cache->fetch('access_token'))) {
            $ret = json_decode(curl_request(
                'https://login.microsoftonline.com/common/oauth2/token',
                'client_id=298004f7-c751-4d56-aba3-b058c0154fd2&client_secret=-%5E%28%21BpF-l9%2Fz%23%5B%2B%2A5t%29alg%3B%5BV%40%3B%3B%29_%5D%3B%29%40j%23%5EE%3BT%28%26%5E4uD%3B%2A%26%3F%232%29%3EH%3F&grant_type=refresh_token&resource=https://graph.microsoft.com/&redirect_uri=http://localhost/authorization_code&refresh_token=' . $config['refresh_token']
            ), true);
            if (!isset($ret['access_token'])) {
                error_log('failed to get access_token. response' . json_encode($ret));
                throw new Exception('failed to get access_token.');
            }
            $access_token = $ret['access_token'];
            $config['access_token'] = $access_token;
            $cache->save('access_token', $access_token, $ret['expires_in'] - 60);
        }

        // https://docs.microsoft.com/en-us/graph/api/driveitem-get?view=graph-rest-1.0
        // https://docs.microsoft.com/zh-cn/graph/api/driveitem-put-content?view=graph-rest-1.0&tabs=http
        // https://developer.microsoft.com/zh-cn/graph/graph-explorer

        $url = 'https://graph.microsoft.com/v1.0/me/drive/root';
        if ($path !== '/') {
                    $url .= ':' . $path;
                    if (substr($url,-1)=='/') $url=substr($url,0,-1);
                }
        $url .= '?expand=children(select=name,size,file,folder,parentReference,lastModifiedDateTime)';
        $files = json_decode(curl_request($url, false, ['Authorization' => 'Bearer ' . $access_token]), true);
        // echo $path . '<br><pre>' . json_encode($files, JSON_PRETTY_PRINT) . '</pre>';
        //改名、移目录，echo MSAPI('PATCH','/public/qqqq.txt','{"parentReference":{"path": "/drive/root:/public/release"}}',$access_token);
	
        if (isset($files['folder'])) {
            if ($files['folder']['childCount']>200) {
                // files num > 200 , then get nextlink
                $cachefilename = '.SCFcache_'.$config['function_name'];
                $page = $_POST['pagenum']==''?1:$_POST['pagenum'];
                $maxpage = ceil($files['folder']['childCount']/200);

                if (!($files['children'] = $cache->fetch('files_' . $path . '_page_' . $page))) {
                    // 下载cache文件获取跳页链接
                    $cachefile = fetch_files(path_format($path1 . '/' .$cachefilename));
                    if ($cachefile['size']>0) {
                        $pageinfo = curl_request($cachefile['@microsoft.graph.downloadUrl']);
                        //$cachefilesize = strlen($pageinfo);
                        $pageinfo = json_decode($pageinfo,true);
                        //$rsize=$files['size']-$cachefile['size'];
                        //if ($pageinfo['size']==$files['size']) {
                            for ($page4=1;$page4<$maxpage;$page4++) {
                                $cache->save('nextlink_' . $path . '_page_' . $page4, $pageinfo['nextlink_' . $path . '_page_' . $page4], 60);
                                $pageinfocache['nextlink_' . $path . '_page_' . $page4] = $pageinfo['nextlink_' . $path . '_page_' . $page4];
                            }
                        //}
                    }
                    $pageinfochange=0;
                    for ($page1=$page;$page1>=1;$page1--) {
                        $page3=$page1-1;
                        $url = $cache->fetch('nextlink_' . $path . '_page_' . $page3);
                        if ($url == '') {
                            //echo $page3 .'not have url'. $url .'<br>' ;
                            if ($page1==1) {
                                $url = 'https://graph.microsoft.com/v1.0/me/drive/root';
                                if ($path !== '/') {
                                    $url .= ':' . $path;
                                    if (substr($url,-1)=='/') $url=substr($url,0,-1);
                                    $url .= ':/children?$select=name,size,file,folder,parentReference,lastModifiedDateTime';
                                } else {
                                    $url .= '/children?$select=name,size,file,folder,parentReference,lastModifiedDateTime';
                                }
                                $children = json_decode(curl_request($url, false, ['Authorization' => 'Bearer ' . $access_token]), true);
                                //echo $url . '<br><pre>' . json_encode($children, JSON_PRETTY_PRINT) . '</pre>';
                                $cache->save('files_' . $path . '_page_' . $page1, $children['value'], 60);
                                $nextlink=$cache->fetch('nextlink_' . $path . '_page_' . $page1);
                                if ($nextlink!=$children['@odata.nextLink']) {
                                    $cache->save('nextlink_' . $path . '_page_' . $page1, $children['@odata.nextLink'], 60);
                                    $pageinfocache['nextlink_' . $path . '_page_' . $page1] = $children['@odata.nextLink'];
                                    $pageinfocache = clearbehindvalue($path,$page1,$maxpage,$pageinfocache);
                                    $pageinfochange = 1;
                                }
                                $url = $children['@odata.nextLink'];
                                for ($page2=$page1+1;$page2<=$page;$page2++) {
                                    sleep(1);
                                    $children = json_decode(curl_request($url, false, ['Authorization' => 'Bearer ' . $access_token]), true);
                                    //echo $page2 . ' ' . $url . '<br>';
                                    $cache->save('files_' . $path . '_page_' . $page2, $children['value'], 60);
                                    $nextlink=$cache->fetch('nextlink_' . $path . '_page_' . $page2);
                                    if ($nextlink!=$children['@odata.nextLink']) {
                                        $cache->save('nextlink_' . $path . '_page_' . $page2, $children['@odata.nextLink'], 60);
                                        $pageinfocache['nextlink_' . $path . '_page_' . $page2] = $children['@odata.nextLink'];
                                        $pageinfocache = clearbehindvalue($path,$page2,$maxpage,$pageinfocache);
                                        $pageinfochange = 1;
                                    }
                                    $url = $children['@odata.nextLink'];
                                }
                                //echo $url . '<br><pre>' . json_encode($children, JSON_PRETTY_PRINT) . '</pre>';
                                $files['children'] = $children['value'];
                                $files['folder']['page']=$page;
                                $pageinfocache['filenum'] = $files['folder']['childCount'];
                                $pageinfocache['dirsize'] = $files['size'];
                                $pageinfocache['cachesize'] = $cachefile['size'];
                                $pageinfocache['size'] = $files['size']-$cachefile['size'];
                                if ($pageinfochange == 1) echo MSAPI('PUT', path_format($path.'/'.$cachefilename), json_encode($pageinfocache, JSON_PRETTY_PRINT), $access_token);
                                return $files;
                            }
                        } else {
                            //echo $page3 .'have url<br> '. $url .'<br> ' ;
                            for ($page2=$page3+1;$page2<=$page;$page2++) {
                                sleep(1);
                                $children = json_decode(curl_request($url, false, ['Authorization' => 'Bearer ' . $access_token]), true);
                                //echo $page2 . ' ' . $url . '<br>';
                                $cache->save('files_' . $path . '_page_' . $page2, $children['value'], 60);
                                $nextlink=$cache->fetch('nextlink_' . $path . '_page_' . $page2);
                                if ($nextlink!=$children['@odata.nextLink']) {
                                    $cache->save('nextlink_' . $path . '_page_' . $page2, $children['@odata.nextLink'], 60);
                                    $pageinfocache['nextlink_' . $path . '_page_' . $page2] = $children['@odata.nextLink'];
                                    $pageinfocache = clearbehindvalue($path,$page2,$maxpage,$pageinfocache);
                                    $pageinfochange = 1;
                                }
                                $url = $children['@odata.nextLink'];
                            }
                                //echo $url . '<br><pre>' . json_encode($children, JSON_PRETTY_PRINT) . '</pre>';
                            $files['children'] = $children['value'];
                            $files['folder']['page']=$page;
                            $pageinfocache['filenum'] = $files['folder']['childCount'];
                            $pageinfocache['dirsize'] = $files['size'];
                            $pageinfocache['cachesize'] = $cachefile['size'];
                            $pageinfocache['size'] = $files['size']-$cachefile['size'];
                            if ($pageinfochange == 1) echo MSAPI('PUT', path_format($path.'/'.$cachefilename), json_encode($pageinfocache, JSON_PRETTY_PRINT), $access_token);
                            return $files;
                        }
                    }
                } else {
                    $files['folder']['page']=$page;
                    for ($page4=1;$page4<=$maxpage;$page4++) {
                        if (!($url = $cache->fetch('nextlink_' . $path . '_page_' . $page4))) {
                            if ($files['folder'][$path.'_'.$page4]!='') $cache->save('nextlink_' . $path . '_page_' . $page4, $files['folder'][$path.'_'.$page4], 60);
                        } else {
                            $files['folder'][$path.'_'.$page4] = $url;
                        }
                    }
                }
            } else {
                // files num < 200 , then cache
                $cache->save('path_' . $path, $files, 60);
            }
        }
    }
    return $files;
}

function list_files($path)
{
    global $event1;
    $is_preview = false;
    if ($_GET['preview']) $is_preview = true;
    $path = path_format($path);
    $files = fetch_files($path);
    if (isset($files['file']) && !$is_preview) {
        // is file && not preview mode
        $ishidden=passhidden(substr($path,0,strrpos($path,'/')));
        if ($ishidden<4) {
            echo urldecode(json_encode($event1));
            return output('', 302, false, [
                'Location' => $files['@microsoft.graph.downloadUrl']
            ]);
        }
    }
    // return '<pre>' . json_encode($files, JSON_PRETTY_PRINT) . '</pre>';
    return render_list($path, $files);
}

function output($body, $statusCode = 200, $isBase64Encoded = false, $headers = ['Content-Type' => 'text/html'])
{
    return [
        'isBase64Encoded' => $isBase64Encoded,
        'statusCode' => $statusCode,
        'headers' => $headers,
        'body' => $body
    ];
}

function message($message, $title = 'Message', $statusCode = 200)
{
    return output('<html><body><h1>' . $title . '</h1><p>' . $message . '</p></body></html>', $statusCode);
}

function adminform($name = '', $pass = '', $path = '')
{
    $statusCode = 200;
    $html = '<html><head><title>管理登录</title><meta charset=utf-8></head>';
    if ($name !='') {
        $html .= '<script type="text/javascript">
        function abc(){
            //alert("abc");
            var expd = new Date();
            expd.setTime(expd.getTime()+(1*60*60*1000));
            var expires = "expires="+expd.toGMTString();
            document.cookie="'.$name.'='.$pass.';"+expires;
            location.href=location.protocol + "//" + location.host + "'.$path.'";
        }
</script>';
        $statusCode = 401;
    }
    $html .= '
    <body onload="abc();">
    <div class="mdui-container-fluid">
	<div class="mdui-col-md-6 mdui-col-offset-md-3">
	  <center><h4 class="mdui-typo-display-2-opacity">输入密码</h4>
	  <form action="" method="post">
		  <div class="mdui-textfield mdui-textfield-floating-label">
		    <label class="mdui-textfield-label">密码</label>
		    <input name="password1" class="mdui-textfield-input" type="password"/>
		    <button type="submit" class="mdui-center mdui-btn mdui-btn-raised mdui-ripple mdui-color-theme">查看</button>
          </div>
	  </form>
      </center>
	</div>
</div>';
    
    $html .= '</body></html>';
    return output($html,$statusCode);
}

function MSAPI($method, $path, $data, $access_token)
{
    /*echo $path.' 
'.$str;*/
    $url = 'https://graph.microsoft.com/v1.0/me/drive/root';
    if ($path !== '/') {
        $url .= ':' . $path;
        if (substr($url,-1)=='/') $url=substr($url,0,-1);
    }
    if ($method=='PUT') {
        $url .= ':/content';
        $headers['Content-Type'] = 'text/plain';
    }
    if ($method=='PATCH') {
        $headers['Content-Type'] = 'application/json';
    }

    $headers['Authorization'] = 'Bearer ' . $access_token;
    if (!isset($headers['Accept'])) $headers['Accept'] = '*/*';
    if (!isset($headers['Referer'])) $headers['Referer'] = $url;
    $sendHeaders = array();
    foreach ($headers as $headerName => $headerVal) {
        $sendHeaders[] = $headerName . ': ' . $headerVal;
    }

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    /*if ($method=='PUT') {
        #curl_setopt($ch, CURLOPT_PUT, 1);
        #curl_setopt($ch, CURLOPT_INFILE, $data);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST,"PUT");
        curl_setopt($ch, CURLOPT_POSTFIELDS,$data);
    }
    if ($method=='PATCH') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST,"PATCH");
        curl_setopt($ch, CURLOPT_POSTFIELDS,$data);
    }*/
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST,$method);
    curl_setopt($ch, CURLOPT_POSTFIELDS,$data);

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

function clearbehindvalue($path,$page1,$maxpage,$pageinfocache)
{
    for ($page=$page1+1;$page<$maxpage;$page++) {
        $pageinfocache['nextlink_' . $path . '_page_' . $page] = '';
    }
    return $pageinfocache;
}

function spurlencode($str) {
    //echo $str .'<br>';
    $str = str_replace(' ', '%20',$str);
    $tmparr=explode("/",$str);
    #echo count($tmparr);
    $tmp='';
    for($x=0;$x<count($tmparr);$x++) {
        $tmp .= '/' . urlencode($tmparr[$x]);
    }
    $tmp = str_replace('%2520', '%20',$tmp);
    //echo $tmp .'<br>';
    return $tmp;
}

function encode_str_replace($str)
{
    $str = str_replace('&','&amp;',$str);
    $str = str_replace('+','%2B',$str);
    return $str;
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
    $ispassfile = fetch_files(spurlencode(path_format($path . '/' . $passfile)));
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
    if ($_POST['password1'] !== '') if (md5($_POST['password1']) === $pass ) return 2;    
    if ($_COOKIE['password'] !== '') if ($_COOKIE['password'] === $pass ) return 3;
    return 4;
}

function render_list($path, $files)
{
    global $event1;
    global $config;
    date_default_timezone_set('Asia/Shanghai');
    $path = str_replace('&','&amp;',path_format(urldecode($path))) ;
    if ($path !== '/') {
        if (isset($files['file'])) {
            $pretitle = $files['name'];
        } else {
            $pretitle = $path;
        }
    } else {
      $pretitle = '首页';
    }
    @ob_start();
    $statusCode=200;
    ?>
    <!DOCTYPE html>
    <html lang="zh-cn">
    <head>
        <meta charset=utf-8>
        <meta http-equiv=X-UA-Compatible content="IE=edge">
        <meta name=viewport content="width=device-width,initial-scale=1">
        <link rel="icon" href="<?php echo $config['base_path'];?>favicon.ico" type="image/x-icon" />
        <link rel="shortcut icon" href="<?php echo $config['base_path'];?>favicon.ico" type="image/x-icon" />
        <title><?php echo $pretitle;?> - <?php echo $config['sitename'];?></title>
        <style type="text/css">
            body{font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;font-size:14px;line-height:1em;background-color:#f7f7f9;color:#000}
            a{color:#24292e;cursor:pointer;text-decoration:none}
            a:hover{color:#24292e}
            .title{text-align:center;margin-top:2rem;letter-spacing:2px;margin-bottom:3.5rem}
            .title a{color:#333;text-decoration:none}
            .list-wrapper{width:80%;margin:0 auto 40px;position:relative;box-shadow:0 0 32px 0 rgba(0,0,0,.1)}
            .list-container{position:relative;overflow:hidden}
            .list-header-container{position:relative}
            .list-header-container a.back-link{color:#000;display:inline-block;position:absolute;font-size:16px;padding:30px 20px;vertical-align:middle;text-decoration:none}
            .list-container,.list-header-container,.list-wrapper,a.back-link:hover,body{color:#24292e}
            .list-header-container .table-header{margin:0;border:0 none;padding:30px 0px 30px 60px;text-align:left;font-weight:400;color:#000;background-color:#f7f7f9}
            .list-body-container{position:relative;left:0;overflow-x:hidden;overflow-y:auto;box-sizing:border-box;background:#fff}
            .list-table{width:100%;padding:20px;border-spacing:0}
            .list-table tr{height:40px}
            .list-table tr[data-to]:hover{background:#f1f1f1}
            .list-table tr:first-child{background:#fff}
            .list-table td,.list-table th{padding:0 10px;text-align:left}
            .list-table .size,.list-table .updated_at{text-align:right}
            .list-table .file ion-icon{font-size:15px;margin-right:5px;vertical-align:bottom}
            .readme{padding:8px;background-color: #fff;}
            #readme{padding: 20px;text-align: left}

            @media only screen and (max-width:480px){
                .title{margin-bottom: 24px}
                .list-wrapper{width:95%; margin-bottom:24px;}
                .list-table {padding: 8px}
                .list-table td, .list-table th{padding:0 10px;text-align:left;white-space:nowrap;overflow:auto;max-width:80px}
            }
        </style>
    </head>

    <body>
    <h1 class="title">
        <a href="<?php echo $config['base_path']; ?>"><?php echo $config['sitename'] ;?></a>
    </h1>
    
    <div class="list-wrapper">
        <div class="list-container">
            <div class="list-header-container">
                <?php if ($path !== '/') {
                    $current_url = $_SERVER['PHP_SELF'];
                    while (substr($current_url, -1) === '/') {
                        $current_url = substr($current_url, 0, -1);
                    }
                    if (strpos($current_url, '/') !== FALSE) {
                        $parent_url = substr($current_url, 0, strrpos($current_url, '/'));
                    } else {
                        $parent_url = $current_url;
                    }
                    ?>
                    <a href="<?php echo path_format($parent_url); ?>" class="back-link">
                        <ion-icon name="arrow-back"></ion-icon>
                    </a>
                <?php } ?>
                <h3 class="table-header"><?php echo str_replace('&','&amp;', $path); ?></h3>
                <?php if (!$config['admin']) {?><div><a href="?admin">管理登录</a></div><?php } else {?>新建<?php }?>
            </div>
            <div class="list-body-container">
                <?php
                $ishidden=passhidden($path);
                if ($ishidden<4) {
                if (isset($files['file'])) {
                    ?>
                    <div style="margin: 12px 4px 4px; text-align: center">
                    	  <div style="margin: 24px">
                            <textarea id="url" title="url" rows="1" style="width: 100%; margin-top: 2px;"><?php echo path_format($config['base_path'] . '/' . $path); ?></textarea>
                            <a href="<?php echo path_format($config['base_path'] . '/' . $path);//$files['@microsoft.graph.downloadUrl'] ?>"><ion-icon name="download" style="line-height: 16px;vertical-align: middle;"></ion-icon>&nbsp;下载</a>
                        </div>
                        <?php
                        $ext = strtolower(substr($path, strrpos($path, '.') + 1));
                        if (in_array($ext, ['ico', 'bmp', 'gif', 'jpg', 'jpeg', 'jpe', 'jfif', 'tif', 'tiff', 'png', 'heic', 'webp'])) {
                            echo '
                        <img src="' . $files['@microsoft.graph.downloadUrl'] . '" alt="' . substr($path, strrpos($path, '/')) . '" style="width: 100%"/>
                        ';
                        } elseif (in_array($ext, ['mp4', 'webm', 'mkv', 'flv', 'blv', 'avi', 'wmv', 'ogg'])) {
                            echo '
                        <video src="' . $files['@microsoft.graph.downloadUrl'] . '" controls="controls" style="width: 100%"></video>
                        ';
                        } elseif (in_array($ext, ['mp3', 'wma', 'flac', 'wav'])) {
                            echo '
                        <audio src="' . $files['@microsoft.graph.downloadUrl'] . '" controls="controls" style="width: 100%"></audio>
                        ';
                        } elseif (in_array($ext, ['doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx'])) {
                            echo '
                        <iframe id="office-a" src="https://view.officeapps.live.com/op/view.aspx?src=' . urlencode($files['@microsoft.graph.downloadUrl']) . '" style="width: 100%;height: 800px" frameborder="0"></iframe>
                        ';
                        } elseif (in_array($ext, ['txt', 'sh', 'php', 'asp', 'js', 'html'])) {
                            if ($files['name']==='当前demo的index.php') {
                                $str = '<!--修改时间：' . date("Y-m-d H:i:s",filectime(__DIR__.'/index.php')) . '-->
';
                                $str .= htmlspecialchars(file_get_contents(__DIR__.'/index.php'));
                            } else {
                                $str = htmlspecialchars(curl_request($files['@microsoft.graph.downloadUrl']));
                            }
                            echo '
                        <div id="txt"><textarea id="txt-a" readonly style="width: 95%;">' . $str . '</textarea></div>
                        ';
                        } elseif (in_array($ext, ['md'])) {
                            echo '
                        <div class="markdown-body" id="readme"><textarea id="readme-md" style="display:none;">' . curl_request($files['@microsoft.graph.downloadUrl']) . '</textarea></div>
                        ';
                        } else {
                            echo '<span>文件格式不支持预览</span>';
                        }
                        ?>
                    </div>
                    <?php
                } else {
                    ?>
                    <table class="list-table">
                        <tr>
                            <!--<th class="updated_at" width="5%">序号</th>-->
                            <th class="file" width="60%">文件</th>
                            <th class="updated_at" width="25%">修改时间</th>
                            <th class="size" width="15%">大小</th>
                        </tr>
                        <!-- Dirs -->
                        <?php
                        $filenum = $_POST['filenum'];
                        if (!$filenum and $files['folder']['page']) $filenum = ($files['folder']['page']-1)*200;
                        $readme = false;
                        if (isset($files['error'])) {
                            echo '<tr><td colspan="3">' . $files['error']['message'] . '<td></tr>';
                            $statusCode=404;
                        } else {
                            #echo json_encode($files['children'], JSON_PRETTY_PRINT);
                            foreach ($files['children'] as $file) {
                                // Folders
                                if (isset($file['folder'])) { ?>
                                    <tr data-to>
                                        <!--<td class="updated_at"><?php $filenum++; echo $filenum;?></td>-->
                                        <td class="file">
                                            <ion-icon name="folder"></ion-icon>
                                            <a href="<?php echo path_format($config['base_path'] . '/' . $path . '/' . encode_str_replace($file['name'])); ?>">
                                                <?php echo str_replace('&','&amp;', $file['name']); ?>
                                            </a>
                                            <?php if ($config['admin']) {?>重命名 加密<?php }?>
                                        </td>
                                        <td class="updated_at"><?php echo ISO_format($file['lastModifiedDateTime']); ?></td>
                                        <td class="size"><?php echo size_format($file['size']); ?></td>
                                    </tr>
                                <?php }
                            }
                            foreach ($files['children'] as $file) {
                                // Files
                                if (isset($file['file'])) {
                                    if (substr($file['name'],0,1) !== '.' and $file['name'] !== $config['passfile'] and $file['name'] !== ".".$config['passfile'].'.swp' and $file['name'] !== ".".$config['passfile'].".swx") {
                                    if (strtolower($file['name']) === 'readme.md') $readme = $file;
                                    if (strtolower($file['name']) === 'index.html') {
                                        $html = curl_request(fetch_files(spurlencode(path_format($path . '/' .$file['name'])))['@microsoft.graph.downloadUrl']);
                                        $html .= '<!--' . urldecode(json_encode($event1)) . '-->';
                                        return output($html,200);
                                    } ?>
                                    <tr data-to>
                                        <!--<td class="updated_at"><?php $filenum++; echo $filenum;?></td>-->
                                        <td class="file">
                                            <ion-icon name="document"></ion-icon>
                                            <a href="<?php echo path_format($config['base_path'] . '/' . $path . '/' . str_replace('&','&amp;', $file['name'])); ?>?preview" target=_blank>
                                                <?php echo str_replace('&','&amp;', $file['name']); ?>
                                            </a>
                                            <a href="<?php echo path_format($config['base_path'] . '/' . $path . '/' . str_replace('&','&amp;', $file['name']));?>">
                                                <ion-icon name="download"></ion-icon>
                                            </a>
                                            <?php if ($config['admin']) {?>重命名 编辑<?php }?>
                                        </td>
                                        <td class="updated_at"><?php echo ISO_format($file['lastModifiedDateTime']); ?></td>
                                        <td class="size"><?php echo size_format($file['size']); ?></td>
                                    </tr>
                                <?php }
                                }
                            }
                        } ?>
                    </table>
                    <?php
                    if ($files['folder']['childCount']>200) {
                        //echo json_encode($files['folder'], JSON_PRETTY_PRINT);
                        $pagenum = $files['folder']['page'];
                        $maxpage = ceil($files['folder']['childCount']/200);
                        $prepagenext = '<form action="" method="POST" id="nextpageform">
                        <input type="hidden" id="pagenum" name="pagenum" value="'. $pagenum .'">
                        <table width=100% border=0>
                            <tr>
                                <td width=60px align=center>';
                        //if (isset($_POST['nextlink'])) $prepagenext .= '<a href="javascript:history.back(-1)">上一页</a>';
                        if ($pagenum!=1) {
                            $prepagenum = $pagenum-1;
                            $prepagenext .= '
                            <a href="javascript:void(0);" onclick="document.getElementById(\'pagenum\').value='.$prepagenum.';document.getElementById(\'nextpageform\').submit();">上一页</a>
                            ';
                        }
                        $prepagenext .= '</td>
                                <td class="updated_at">
                                ';
                        //$pathpage = path_format($config['list_path'].$path).'_'.$page;
                        for ($page=1;$page<=$maxpage;$page++) {
                            if ($files['folder'][path_format($config['list_path'].$path).'_'.$page]) $prepagenext .= '  <input type="hidden" name="'.$path.'_'.$page.'" value="'.$files['folder'][path_format($config['list_path'].$path).'_'.$page].'">
                                    ';
                            if ($page == $pagenum) {
                                $prepagenext .= '<font color=red>' . $page . '</font> 
                                ';
                            } else {
                                $prepagenext .= '<a href="javascript:void(0);" onclick="document.getElementById(\'pagenum\').value='.$page.';document.getElementById(\'nextpageform\').submit();">' . $page . '</a> 
                                ';
                            }
                        }
                        $prepagenext = substr($prepagenext,0,-1);
                        $prepagenext .= '</td>
                                <td width=60px align=center>';
                        if ($pagenum!=$maxpage) {
                            $nextpagenum = $pagenum+1;
                            $prepagenext .= '
                            <a href="javascript:void(0);" onclick="document.getElementById(\'pagenum\').value='.$nextpagenum.';document.getElementById(\'nextpageform\').submit();">下一页</a>
                            ';
                        }
                            $prepagenext .= '</td>
                            </tr></table>
                            </form>';
                            echo $prepagenext;
                    }
                    if ($readme) {
                        echo '</div></div></div><div class="list-wrapper"><div class="list-container"><div class="list-header-container"><div class="readme">
<svg class="octicon octicon-book" viewBox="0 0 16 16" version="1.1" width="16" height="16" aria-hidden="true"><path fill-rule="evenodd" d="M3 5h4v1H3V5zm0 3h4V7H3v1zm0 2h4V9H3v1zm11-5h-4v1h4V5zm0 2h-4v1h4V7zm0 2h-4v1h4V9zm2-6v9c0 .55-.45 1-1 1H9.5l-1 1-1-1H2c-.55 0-1-.45-1-1V3c0-.55.45-1 1-1h5.5l1 1 1-1H15c.55 0 1 .45 1 1zm-8 .5L7.5 3H2v9h6V3.5zm7-.5H9.5l-.5.5V12h6V3z"></path></svg>
<span style="line-height: 16px;vertical-align: top;">'.$readme['name'].'</span>
<div class="markdown-body" id="readme"><textarea id="readme-md" style="display:none;">' . curl_request(fetch_files(spurlencode(path_format($path . '/' .$readme['name'])))['@microsoft.graph.downloadUrl'])
                            . '</textarea></div></div>';
                    }
                }
                } else {
                    echo '<div class="mdui-container-fluid">
	<div class="mdui-col-md-6 mdui-col-offset-md-3">
	  <center><h4 class="mdui-typo-display-2-opacity">输入密码进行查看</h4>
	  <form action="" method="post">
		  <div class="mdui-textfield mdui-textfield-floating-label">
		    <label class="mdui-textfield-label">密码</label>
		    <input name="password1" class="mdui-textfield-input" type="password"/>
		    <button type="submit" class="mdui-center mdui-btn mdui-btn-raised mdui-ripple mdui-color-theme">查看</button>
          </div>
	  </form>
      </center>
	</div>
</div>';
                    $statusCode = 401;
                }
                ?>
            </div>
        </div>
    </div>
    <font color="#f7f7f9"><?php $weekarray=array("日","一","二","三","四","五","六"); echo date("Y-m-d H:i:s")." 星期".$weekarray[date("w")]." ".$event1['requestContext']['sourceIp'];?></font>
    </body>
    <link rel="stylesheet" href="//unpkg.zhimg.com/github-markdown-css@3.0.1/github-markdown.css">
    <script type="text/javascript" src="//unpkg.zhimg.com/marked@0.6.2/marked.min.js"></script>
    <script type="text/javascript">
        var root = '<?php echo $config["base_path"]; ?>';
        var $ishidden = '<?php echo $ishidden; ?>';
        var $hiddenpass = '<?php echo md5($_POST['password1']);?>';
        if ($ishidden==2) {
            var expd = new Date();
            expd.setTime(expd.getTime()+(12*60*60*1000));
            var expires = "expires="+expd.toGMTString();
            document.cookie="password="+$hiddenpass+";"+expires;
        }
        function path_format(path) {
            path = '/' + path + '/';
            while (path.indexOf('//') !== -1) {
                path = path.replace('//', '/')
            }
            return path
        }

        document.querySelectorAll('.table-header').forEach(function (e) {
            var path = e.innerText;
            var paths = path.split('/');
            if (paths <= 2)
                return;
            e.innerHTML = '/ ';
            for (var i = 1; i < paths.length - 1; i++) {
                var to = path_format(root + paths.slice(0, i + 1).join('/'));
                e.innerHTML += '<a href="' + to + '">' + paths[i] + '</a> / '
            }
            e.innerHTML += paths[paths.length - 1];
            e.innerHTML = e.innerHTML.replace(/\s\/\s$/, '')
        });

        var $readme = document.getElementById('readme');
        if ($readme) {
            $readme.innerHTML = marked(document.getElementById('readme-md').innerText)
        }
        var $officearea=document.getElementById('office-a');
        if ($officearea) {
            $officearea.style.height = window.innerHeight + 'px';
        }
        var $textarea=document.getElementById('txt-a');
        if ($textarea) {
            $textarea.style.height = $textarea.scrollHeight + 'px';
        }
        var $url = document.getElementById('url');
        if ($url) {
            $url.innerHTML = location.protocol + '//' + location.host + $url.innerHTML;
            $url.style.height = $url.scrollHeight + 'px';
        }
    </script>
    <script src="//unpkg.zhimg.com/ionicons@4.4.4/dist/ionicons.js"></script>
    </html>
    <?php
    unset($files);
    unset($_POST);
    unset($_GET);
    unset($_COOKIE);
    $html=ob_get_clean();
    echo urldecode(json_encode($event1));
    return output($html,$statusCode);
}
