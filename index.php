<?php
/*
    帖子 ： https://www.hostloc.com/thread-561971-1-1.html
    github ： https://github.com/qkqpttgf/OneDrive_SCF
*/
//有选择地添加以下某些环境变量来做设置：
/*
sitename       ：网站的名称，不添加会显示为‘请在环境变量添加sitename’。  
admin          ：管理密码，不添加时不显示登录页面且无法登录。  
adminloginpage ：管理登录的页面不再是'?admin'，而是此设置的值。如果设置，登录按钮及页面隐藏。  
public_path    ：使用API长链接访问时，显示网盘文件的路径，不设置时默认为根目录；  
           　　　不能是private_path的上级（public看到的不能比private多，要么看到的就不一样）。  
private_path   ：使用自定义域名访问时，显示网盘文件的路径，不设置时默认为根目录。  
domain_path    ：格式为a1.com=/dir/path1&b1.com=/path2，比private_path优先。  
imgup_path     ：设置图床路径，不设置这个值时该目录内容会正常列文件出来，设置后只有上传界面，不显示其中文件（登录后显示）。  
passfile       ：自定义密码文件的名字，可以是'.password'，也可以是'aaaa.txt'等等；  
        　       密码是这个文件的内容，可以空格、可以中文；列目录时不会显示，只有知道密码才能查看或下载此文件。  
t1,t2,t3,t4,t5,t6,t7：把refresh_token按128字节切开来放在环境变量，方便更新版本。  
*/
include 'vendor/autoload.php';
include 'functions.php';
global $oauth;
global $config;
$oauth='';
$config='';
$oauth = [
    'onedrive_ver' => 0, // 0:默认（支持商业版与个人版） 1:世纪互联
    'redirect_uri' => 'https://scfonedrive.github.io',
    'refresh_token' => '',
];
$config = [
    'sitename' => getenv('sitename'),
    'passfile' => getenv('passfile'),
    'imgup_path' => getenv('imgup_path'),
];

function main_handler($event, $context)
{
    global $oauth;
    global $config;
    $event = json_decode(json_encode($event), true);
    $context = json_decode(json_encode($context), true);
    $event1 = $event;
    if (strlen(json_encode($event1['body']))>500) $event1['body']=substr($event1['body'],0,strpos($event1['body'],'base64')+10) . '...Too Long!...' . substr($event1['body'],-50);
    echo urldecode(json_encode($event1)) . '
 
' . urldecode(json_encode($context)) . '
 
';
    unset($event1);
    unset($_POST);
    unset($_GET);
    unset($_COOKIE);
    unset($_SERVER);
    date_default_timezone_set(get_timezone($_COOKIE['timezone']));
    $function_name = $context['function_name'];
    $config['function_name'] = $function_name;
    $host_name = $event['headers']['host'];
    $serviceId = $event['requestContext']['serviceId'];
    $public_path = path_format(getenv('public_path'));
    $private_path = path_format(getenv('private_path'));
    $domain_path = getenv('domain_path');
    $tmp_path='';
    if ($domain_path!='') {
        $tmp = explode("&",$domain_path);
        foreach ($tmp as $multidomain_paths){
            $pos = strpos($multidomain_paths,"=");
            $tmp_path = path_format(substr($multidomain_paths,$pos+1));
            if (substr($multidomain_paths,0,$pos)==$host_name) $private_path=$tmp_path;
        }
    }
    // public_path 不能是 private_path 的上级目录。
    if ($tmp_path!='') if ($public_path == substr($tmp_path,0,strlen($public_path))) $public_path=$tmp_path;
    if ($public_path == substr($private_path,0,strlen($public_path))) $public_path=$private_path;
    if ( $serviceId === substr($host_name,0,strlen($serviceId)) ) {
        $config['base_path'] = '/'.$event['requestContext']['stage'].'/'.$function_name.'/';
        $config['list_path'] = $public_path;
        $path = substr($event['path'], strlen('/'.$function_name.'/'));
    } else {
        $config['base_path'] = $event['requestContext']['path'];
        $config['list_path'] = $private_path;
        $path = substr($event['path'], strlen($event['requestContext']['path']));
    }
    if (substr($path,-1)=='/') $path=substr($path,0,-1);
    if (empty($config['list_path'])) {
        $config['list_path'] = '/';
    } else {
        $config['list_path'] = spurlencode($config['list_path'],'/') ;
    }
    if (empty($config['sitename'])) $config['sitename'] = '请在环境变量添加sitename';
    $_GET = $event['queryString'];
    $_SERVER['PHP_SELF'] = path_format($config['base_path'] . $path);
    $_SERVER['REMOTE_ADDR'] = $event['requestContext']['sourceIp'];
    $_POSTbody = explode("&",$event['body']);
    foreach ($_POSTbody as $postvalues) {
        $pos = strpos($postvalues,"=");
        $_POST[urldecode(substr($postvalues,0,$pos))]=urldecode(substr($postvalues,$pos+1));
    }
    $cookiebody = explode("; ",$event['headers']['cookie']);
    foreach ($cookiebody as $cookievalues) {
        $pos = strpos($cookievalues,"=");
        $_COOKIE[urldecode(substr($cookievalues,0,$pos))]=urldecode(substr($cookievalues,$pos+1));
    }
    $referer = $event['headers']['referer'];
    $tmpurl = substr($referer,strpos($referer,'//')+2);
    $refererhost = substr($tmpurl,0,strpos($tmpurl,'/'));
    if ($refererhost==$host_name) {
        // 仅游客上传用，referer不对就空值，无法上传
        $config['current_url'] = substr($referer,0,strpos($referer,'//')) . '//' . $host_name.$_SERVER['PHP_SELF'];
    } else {
        $config['current_url'] = '';
    }

    config_oauth();
    if (!$config['base_path']) {
        return message('Missing env <code>base_path</code>');
    }
    if (!$oauth['refresh_token']) $oauth['refresh_token'] = getenv('t1').getenv('t2').getenv('t3').getenv('t4').getenv('t5').getenv('t6').getenv('t7');
    if (!$oauth['refresh_token']) {
        if ($path=='authorization_code' && isset($_GET['code'])) {
            return message(get_refresh_token($_GET['code']));
        }
        return message('Please set the <code>refresh_token</code> in environments<br>
    <a href="" id="a1">Get a refresh_token</a>
    <br><code>allow javascript</code>
    <script>
        url=window.location.href;
        if (url.substr(-1)!="/") url+="/";
        url="'. $oauth['oauth_url'] .'authorize?scope='. $oauth['scope'] .'&response_type=code&client_id='. $oauth['client_id'] .'&redirect_uri='. $oauth['redirect_uri'] . '&state=' .'"+encodeURIComponent(url);
        document.getElementById(\'a1\').href=url;
        window.open(url,"_blank");
    </script>
    ', 'Error', 500);
    }

    if (getenv('adminloginpage')=='') {
        $adminloginpage = 'admin';
    } else {
        $adminloginpage = getenv('adminloginpage');
    }
    if ($_GET[$adminloginpage]) {
        if ($_GET['preview']) {
            $url = $_SERVER['PHP_SELF'] . '?preview';
        } else {
            $url = path_format($_SERVER['PHP_SELF'] . '/');
        }
        if (getenv('admin')!='') {
            if ($_POST['password1']==getenv('admin')) {
                return adminform($function_name.'admin',md5($_POST['password1']),$url);
            } else return adminform();
        } else {
            return output('', 302, [ 'Location' => $url ]);
        }
    }
    if (getenv('admin')!='') if ($_COOKIE[$function_name.'admin']==md5(getenv('admin')) || $_POST['password1']==getenv('admin') ) {
        $config['admin']=1;
    } else {
        $config['admin']=0;
    }

    $config['ajax']=0;
    if ($event['headers']['x-requested-with']=='XMLHttpRequest') {
        $config['ajax']=1;
    }

    return list_files($path);
}

function fetch_files($path = '/')
{
    global $oauth;
    global $config;
    $path1 = path_format($path);
    $path = path_format($config['list_path'] . path_format($path));
    $cache = null;
    $cache = new \Doctrine\Common\Cache\FilesystemCache(sys_get_temp_dir(), '.qdrive');
    if (!($files = $cache->fetch('path_' . $path))) {

        // https://docs.microsoft.com/en-us/graph/api/driveitem-get?view=graph-rest-1.0
        // https://docs.microsoft.com/zh-cn/graph/api/driveitem-put-content?view=graph-rest-1.0&tabs=http
        // https://developer.microsoft.com/zh-cn/graph/graph-explorer

        $url = $oauth['api_url'];
        if ($path !== '/') {
                    $url .= ':' . $path;
                    if (substr($url,-1)=='/') $url=substr($url,0,-1);
                }
        $url .= '?expand=children(select=name,size,file,folder,parentReference,lastModifiedDateTime)';
        $files = json_decode(curl_request($url, false, ['Authorization' => 'Bearer ' . $config['access_token']]), true);
        // echo $path . '<br><pre>' . json_encode($files, JSON_PRETTY_PRINT) . '</pre>';

        if (isset($files['folder'])) {
            if ($files['folder']['childCount']>200) {
                // files num > 200 , then get nextlink
                $page = $_POST['pagenum']==''?1:$_POST['pagenum'];
                $files=fetch_files_children($files, $path, $page, $cache);
            } else {
                // files num < 200 , then cache
                $cache->save('path_' . $path, $files, 60);
            }
        }
    }
    return $files;
}

function fetch_files_children($files, $path, $page, $cache)
{
    global $oauth;
    global $config;
    $cachefilename = '.SCFcache_'.$config['function_name'];
    $maxpage = ceil($files['folder']['childCount']/200);

    if (!($files['children'] = $cache->fetch('files_' . $path . '_page_' . $page))) {
                    // 下载cache文件获取跳页链接
        $cachefile = fetch_files(path_format($path1 . '/' .$cachefilename));
        if ($cachefile['size']>0) {
            $pageinfo = curl_request($cachefile['@microsoft.graph.downloadUrl']);
            $pageinfo = json_decode($pageinfo,true);
            for ($page4=1;$page4<$maxpage;$page4++) {
                $cache->save('nextlink_' . $path . '_page_' . $page4, $pageinfo['nextlink_' . $path . '_page_' . $page4], 60);
                $pageinfocache['nextlink_' . $path . '_page_' . $page4] = $pageinfo['nextlink_' . $path . '_page_' . $page4];
            }
        }
        $pageinfochange=0;
        for ($page1=$page;$page1>=1;$page1--) {
            $page3=$page1-1;
            $url = $cache->fetch('nextlink_' . $path . '_page_' . $page3);
            if ($url == '') {
                if ($page1==1) {
                    $url = $oauth['api_url'];
                    if ($path !== '/') {
                        $url .= ':' . $path;
                        if (substr($url,-1)=='/') $url=substr($url,0,-1);
                        $url .= ':/children?$select=name,size,file,folder,parentReference,lastModifiedDateTime';
                    } else {
                        $url .= '/children?$select=name,size,file,folder,parentReference,lastModifiedDateTime';
                    }
                    $children = json_decode(curl_request($url, false, ['Authorization' => 'Bearer ' . $config['access_token']]), true);
                               // echo $url . '<br><pre>' . json_encode($children, JSON_PRETTY_PRINT) . '</pre>';
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
                        $children = json_decode(curl_request($url, false, ['Authorization' => 'Bearer ' . $config['access_token']]), true);
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
                    if ($pageinfochange == 1) echo MSAPI('PUT', path_format($path.'/'.$cachefilename), json_encode($pageinfocache, JSON_PRETTY_PRINT), $config['access_token'])['body'];
                    return $files;
                }
            } else {
                for ($page2=$page3+1;$page2<=$page;$page2++) {
                    sleep(1);
                    $children = json_decode(curl_request($url, false, ['Authorization' => 'Bearer ' . $config['access_token']]), true);
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
                if ($pageinfochange == 1) echo MSAPI('PUT', path_format($path.'/'.$cachefilename), json_encode($pageinfocache, JSON_PRETTY_PRINT), $config['access_token'])['body'];
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
    return $files;
}

function list_files($path)
{
    global $oauth;
    global $config;
    $is_preview = false;
    if ($_GET['preview']) $is_preview = true;
    $path = path_format($path);
    $cache = null;
    $cache = new \Doctrine\Common\Cache\FilesystemCache(sys_get_temp_dir(), '.qdrive');
    if (!($access_token = $cache->fetch('access_token'))) {
        $ret = json_decode(curl_request(
            $oauth['oauth_url'] . 'token',
            'client_id='. $oauth['client_id'] .'&client_secret='. $oauth['client_secret'] .'&grant_type=refresh_token&requested_token_use=on_behalf_of&refresh_token=' . $oauth['refresh_token']
        ), true);
        if (!isset($ret['access_token'])) {
            error_log('failed to get access_token. response' . json_encode($ret));
            throw new Exception('failed to get access_token.');
        }
        $access_token = $ret['access_token'];
        $config['access_token'] = $access_token;
        $cache->save('access_token', $config['access_token'], $ret['expires_in'] - 60);
    }

    if ($config['ajax']&&$_POST['action']=='del_upload_cache'&&substr($_POST['filename'],-4)=='.tmp') {
        // 无需登录即可删除.tmp后缀文件
        $tmp = MSAPI('DELETE',path_format(path_format($config['list_path'] . path_format($path)) . '/' . spurlencode($_POST['filename']) ),'',$access_token);
        return output($tmp['body'],$tmp['stat']);
    } 
    if ($config['admin']) {
        $tmp = adminoperate($path);
        if ($tmp['statusCode'] > 0) {
            $path1 = path_format($config['list_path'] . path_format($path));
            $cache->save('path_' . $path1, json_decode('{}',true), 1);
            return $tmp;
        }
    } else {
        if ($config['ajax']) return output('请<font color="red">刷新</font>页面后重新登录',401);
        if (path_format('/'.path_format(urldecode($config['list_path'].$path)).'/')==path_format('/'.path_format($config['imgup_path']).'/')&&$config['imgup_path']!='') {
            $html = guestupload($path);
            if ($html!='') return $html;
        }
    }
    $config['ishidden'] = 4;
    $config['ishidden'] = passhidden($path);
    if (path_format('/'.path_format(urldecode($config['list_path'].$path)).'/')==path_format('/'.path_format($config['imgup_path']).'/')&&$config['imgup_path']!=''&&!$config['admin']) {
        // 是图床目录且不是管理
        $files = json_decode('{"folder":{}}', true);
    } elseif ($config['ishidden']==4) {
        $files = json_decode('{"folder":{}}', true);
    } else {
        if ($_GET['thumbnails']) if (in_array(strtolower(substr($path, strrpos($path, '.') + 1)), ['ico', 'bmp', 'gif', 'jpg', 'jpeg', 'jpe', 'jfif', 'tif', 'tiff', 'png', 'heic', 'webp'])) {
            return get_thumbnails_url($path);
        } else return output('ico,bmp,gif,jpg,jpeg,jpe,jfif,tif,tiff,png,heic,webp',500);
        $files = fetch_files($path);
    }
    if (isset($files['file']) && !$is_preview) {
        // is file && not preview mode
        if ($config['ishidden']<4) {
            return output('', 302, [ 'Location' => $files['@microsoft.graph.downloadUrl'] ]);
        }
    }
    return render_list($path, $files);
}

function adminform($name = '', $pass = '', $path = '')
{
    $statusCode = 401;
    $html = '<html><head><title>管理登录</title><meta charset=utf-8></head>';
    if ($name!=''&&$pass!='') {
        /*$html .= '<script type="text/javascript">
            var expd = new Date();
            expd.setTime(expd.getTime()+(1*60*60*1000));
            var expires = "expires="+expd.toGMTString();
            document.cookie="'.$name.'='.$pass.';"+expires;
            //path='.$path.';
            location.href=location.protocol + "//" + location.host + "'.$path.'";
</script>';*/
        $html .= '<body>登录成功，正在跳转</body></html>';
        $statusCode = 302;
        date_default_timezone_set('UTC');
        $header = [
            'Set-Cookie' => $name.'='.$pass.'; expires='.date(DATE_COOKIE,strtotime('+1hour')),
            'Location' => $path,
            'Content-Type' => 'text/html'
        ];
        return output($html,$statusCode,$header);
        // return $name.'='.$pass.'; expires='.date(DATE_COOKIE,strtotime('+1hour'));
    }
    $html .= '
    <body>
	<div>
	  <center><h4>输入管理密码</h4>
	  <form action="" method="post">
		  <div>
		    <label>密码</label>
		    <input name="password1" type="password"/>
		    <input type="submit" value="查看">
          </div>
	  </form>
      </center>
	</div>
';
    $html .= '</body></html>';
    return output($html,$statusCode);
}

function guestupload($path)
{
    global $config;
    $path1 = path_format($config['list_path'] . path_format($path));
    if (substr($path1,-1)=='/') $path1=substr($path1,0,-1);
    if ($_POST['guest_upload_filecontent']!=''&&$_POST['upload_filename']!='') if ($config['current_url']!='') {
        $data = substr($_POST['guest_upload_filecontent'],strpos($_POST['guest_upload_filecontent'],'base64')+strlen('base64,'));
        $data = base64_decode($data);
            // 重命名为MD5加后缀
        $filename = spurlencode($_POST['upload_filename']);
        $ext = strtolower(substr($filename, strrpos($filename, '.')));
        $tmpfilename = "/tmp/".date("Ymd-His")."-".$filename;
        $tmpfile=fopen($tmpfilename,'wb');
        fwrite($tmpfile,$data);
        fclose($tmpfile);
        $filename = md5_file($tmpfilename) . $ext;
        $locationurl = $config['current_url'] . '/' . $filename . '?preview';
        $response=MSAPI('createUploadSession',path_format($path1 . '/' . $filename),'{"item": { "@microsoft.graph.conflictBehavior": "fail"  }}',$config['access_token']);
        $responsearry=json_decode($response['body'],true);
        if (isset($responsearry['error'])) return message($responsearry['error']['message']. '<hr><a href="' . $locationurl .'">' . $filename . '</a><br><a href="javascript:history.back(-1)">上一页</a>','错误',$response['stat']);
        $uploadurl=$responsearry['uploadUrl'];
        $result = MSAPI('PUT',$uploadurl,$data,$config['access_token'])['body'];
        echo $result;
        $resultarry = json_decode($result,true);
        if (isset($resultarry['error'])) return message($resultarry['error']['message']. '<hr><a href="javascript:history.back(-1)">上一页</a>','错误',403);
        return output('', 302, [ 'Location' => $locationurl ]);
    } else {
        return message('Please upload from source site!');
    }
}

function adminoperate($path)
{
    global $config;
    $path1 = path_format($config['list_path'] . path_format($path));
    if (substr($path1,-1)=='/') $path1=substr($path1,0,-1);
    $tmparr['statusCode'] = 0;
    if ($_POST['upbigfilename']!=''&&$_POST['filesize']>0) {
        $fileinfo['name'] = $_POST['upbigfilename'];
        $fileinfo['size'] = $_POST['filesize'];
        $fileinfo['lastModified'] = $_POST['lastModified'];
        $filename = spurlencode( $fileinfo['name'] );
        $cachefilename = '.' . $fileinfo['lastModified'] . '_' . $fileinfo['size'] . '_' . $filename . '.tmp';
        $getoldupinfo=fetch_files(path_format($path . '/' . $cachefilename));
        //echo json_encode($getoldupinfo, JSON_PRETTY_PRINT);
        if (isset($getoldupinfo['file'])&&$getoldupinfo['size']<5120) {
            $getoldupinfo_j = curl_request($getoldupinfo['@microsoft.graph.downloadUrl']);
            $getoldupinfo = json_decode($getoldupinfo_j , true);
            //微软的过期时间只有20分钟，其实不用看过期时间，我过了14个小时，用昨晚的链接还可以接着继续上传，微软临时文件只要还在就可以续
            if ( json_decode( curl_request($getoldupinfo['uploadUrl']), true)['@odata.context']!='' ) return output($getoldupinfo_j);
        }
        $response=MSAPI('createUploadSession',path_format($path1 . '/' . $filename),'{"item": { "@microsoft.graph.conflictBehavior": "fail"  }}',$config['access_token']);
        $responsearry = json_decode($response['body'],true);
        if (isset($responsearry['error'])) return output($response['body'], $response['stat']);
        $fileinfo['uploadUrl'] = $responsearry['uploadUrl'];
        echo MSAPI('PUT', path_format($path1 . '/' . $cachefilename), json_encode($fileinfo, JSON_PRETTY_PRINT), $config['access_token'])['body'];
        return output($response['body'], $response['stat']);
    }
    if ($_POST['rename_newname']!=$_POST['rename_oldname'] && $_POST['rename_newname']!='') {
        // 重命名
        $oldname = spurlencode($_POST['rename_oldname']);
        $oldname = path_format($path1 . '/' . $oldname);
        $data = '{"name":"' . $_POST['rename_newname'] . '"}';
                //echo $oldname;
        $result = MSAPI('PATCH',$oldname,$data,$config['access_token']);
        return output($result['body'], $result['stat']);
    }
    if ($_POST['delete_name']!='') {
        // 删除
        $filename = spurlencode($_POST['delete_name']);
        $filename = path_format($path1 . '/' . $filename);
                //echo $filename;
        $result = MSAPI('DELETE', $filename, '', $config['access_token']);
        return output($result['body'], $result['stat']);
    }
    if ($_POST['operate_action']=='加密') {
        // 加密
        if ($config['passfile']=='') return message('先在环境变量设置passfile才能加密','',403);
        if ($_POST['encrypt_folder']=='/') $_POST['encrypt_folder']=='';
        $foldername = spurlencode($_POST['encrypt_folder']);
        $filename = path_format($path1 . '/' . $foldername . '/' . $config['passfile']);
                //echo $foldername;
        $result = MSAPI('PUT', $filename, $_POST['encrypt_newpass'], $config['access_token']);
        return output($result['body'], $result['stat']);
    }
    if ($_POST['move_folder']!='') {
        // 移动
        $moveable = 1;
        if ($path == '/' && $_POST['move_folder'] == '/../') $moveable=0;
        if ($_POST['move_folder'] == $_POST['move_name']) $moveable=0;
        if ($moveable) {
            $filename = spurlencode($_POST['move_name']);
            $filename = path_format($path1 . '/' . $filename);
            $foldername = path_format('/'.urldecode($path1).'/'.$_POST['move_folder']);
            $data = '{"parentReference":{"path": "/drive/root:'.$foldername.'"}}';
            $result = MSAPI('PATCH', $filename, $data, $config['access_token']);
            return output($result['body'], $result['stat']);
        } else {
            return output('{"error":"无法移动"}', 403);
        }
    }
    if ($_POST['editfile']!='') {
        // 编辑
        $data = $_POST['editfile'];
        /*TXT一般不会超过4M，不用二段上传
        $filename = $path1 . ':/createUploadSession';
        $response=MSAPI('POST',$filename,'{"item": { "@microsoft.graph.conflictBehavior": "replace"  }}',$config['access_token']);
        $uploadurl=json_decode($response,true)['uploadUrl'];
        echo MSAPI('PUT',$uploadurl,$data,$config['access_token']);*/
        $result = MSAPI('PUT', $path1, $data, $config['access_token'])['body'];
        echo $result;
        $resultarry = json_decode($result,true);
        if (isset($resultarry['error'])) return message($resultarry['error']['message']. '<hr><a href="javascript:history.back(-1)">上一页</a>','错误',403);
    }
    if ($_POST['create_name']!='') {
        // 新建
        if ($_POST['create_type']=='file') {
            $filename = spurlencode($_POST['create_name']);
            $filename = path_format($path1 . '/' . $filename);
            $result = MSAPI('PUT', $filename, $_POST['create_text'], $config['access_token']);
        }
        if ($_POST['create_type']=='folder') {
            $data = '{ "name": "' . $_POST['create_name'] . '",  "folder": { },  "@microsoft.graph.conflictBehavior": "rename" }';
            $result = MSAPI('children', $path1, $data, $config['access_token']);
        }
        return output($result['body'], $result['stat']);
    }
    return $tmparr;
}

function MSAPI($method, $path, $data = '', $access_token)
{
    global $oauth;
    if (substr($path,0,7) == 'http://' or substr($path,0,8) == 'https://') {
        $url=$path;
        $lenth=strlen($data);
        $headers['Content-Length'] = $lenth;
        $lenth--;
        $headers['Content-Range'] = 'bytes 0-' . $lenth . '/' . $headers['Content-Length'];
    } else {
        $url = $oauth['api_url'];
        if ($path=='' or $path=='/') {
            $url .= '/';
        } else {
            $url .= ':' . $path;
            if (substr($url,-1)=='/') $url=substr($url,0,-1);
        }
        if ($method=='PUT') {
            if ($path=='' or $path=='/') {
                $url .= 'content';
            } else {
                $url .= ':/content';
            }
            $headers['Content-Type'] = 'text/plain';
        } elseif ($method=='PATCH') {
            $headers['Content-Type'] = 'application/json';
        } elseif ($method=='POST') {
            $headers['Content-Type'] = 'application/json';
        } elseif ($method=='DELETE') {
            $headers['Content-Type'] = 'application/json';
        } else {
            if ($path=='' or $path=='/') {
                $url .= $method;
            } else {
                $url .= ':/' . $method;
            }
            $method='POST';
            $headers['Content-Type'] = 'application/json';
        }
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
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST,$method);
    curl_setopt($ch, CURLOPT_POSTFIELDS,$data);

    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // 返回获取的输出文本流
    curl_setopt($ch, CURLOPT_HEADER, 0);         // 将头文件的信息作为数据流输出
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $sendHeaders);
    $response['body'] = curl_exec($ch);
    $response['stat'] = curl_getinfo($ch,CURLINFO_HTTP_CODE);
    curl_close($ch);
    echo $response['stat'].'
';
    return $response;
}

function get_thumbnails_url($path = '/')
{
    global $oauth;
    global $config;
    $path1 = path_format($path);
    $path = path_format($config['list_path'] . path_format($path));
    $url = $oauth['api_url'];
    if ($path !== '/') {
        $url .= ':' . $path;
        if (substr($url,-1)=='/') $url=substr($url,0,-1);
    }
    $url .= ':/thumbnails/0/medium';
    $files = json_decode(curl_request($url, false, ['Authorization' => 'Bearer ' . $config['access_token']]), true);
    if (isset($files['url'])) return output($files['url']);
    return output('', 404);
}

function render_list($path, $files)
{
    global $config;
    @ob_start();

    $path = str_replace('%20','%2520',$path);
    $path = str_replace('+','%2B',$path);
    $path = str_replace('&','&amp;',path_format(urldecode($path))) ;
    $path = str_replace('%20',' ',$path);
    $path = str_replace('#','%23',$path);
    $p_path='';
    if ($path !== '/') {
        if (isset($files['file'])) {
            $pretitle = str_replace('&','&amp;', $files['name']);
            $n_path=$pretitle;
        } else {
            $pretitle = substr($path,-1)=='/'?substr($path,0,-1):$path;
            $n_path=substr($pretitle,strrpos($pretitle,'/')+1);
            $pretitle = substr($pretitle,1);
        }
        if (strrpos($path,'/')!=0) {
            $p_path=substr($path,0,strrpos($path,'/'));
            $p_path=substr($p_path,strrpos($p_path,'/')+1);
        }
    } else {
      $pretitle = '首页';
      $n_path=$pretitle;
    }
    $n_path=str_replace('&amp;','&',$n_path);
    $p_path=str_replace('&amp;','&',$p_path);
    $pretitle = str_replace('%23','#',$pretitle);
    $statusCode=200;
?>
<!DOCTYPE html>
<html lang="zh-cn">
<head>
    <title><?php echo $pretitle;?> - <?php echo $config['sitename'];?></title>
    <!--
        帖子 ： https://www.hostloc.com/thread-561971-1-1.html
        github ： https://github.com/qkqpttgf/OneDrive_SCF
    -->
    <meta charset=utf-8>
    <meta http-equiv=X-UA-Compatible content="IE=edge">
    <meta name=viewport content="width=device-width,initial-scale=1">
    <meta name="keywords" content="<?php echo $n_path;?>,<?php if ($p_path!='') echo $p_path.','; echo $config['sitename'];?>,OneDrive_SCF,auth_by_逸笙">
    <link rel="icon" href="<?php echo $config['base_path'];?>favicon.ico" type="image/x-icon" />
    <link rel="shortcut icon" href="<?php echo $config['base_path'];?>favicon.ico" type="image/x-icon" />
    <style type="text/css">
        body{font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;font-size:14px;line-height:1em;background-color:#f7f7f9;color:#000}
        a{color:#24292e;cursor:pointer;text-decoration:none}
        a:hover{color:#24292e}
        .title{text-align:center;margin-top:2rem;letter-spacing:2px;margin-bottom:2rem}
        .title a{color:#333;text-decoration:none}
        .list-wrapper{width:80%;margin:0 auto 40px;position:relative;box-shadow:0 0 32px 0 rgba(0,0,0,.1)}
        .list-container{position:relative;overflow:hidden}
        .list-header-container{position:relative}
        .list-header-container a.back-link{color:#000;display:inline-block;position:absolute;font-size:16px;margin:20px 10px;padding:10px 10px;vertical-align:middle;text-decoration:none}
        .list-container,.list-header-container,.list-wrapper,a.back-link:hover,body{color:#24292e}
        .list-header-container .table-header{margin:0;border:0 none;padding:30px 60px;text-align:left;font-weight:400;color:#000;background-color:#f7f7f9}
        .login{display: inline-table;position: absolute;font-size:16px;padding:30px 20px;vertical-align:middle;right:0px;top:0px}
        .list-body-container{position:relative;left:0;overflow-x:hidden;overflow-y:auto;box-sizing:border-box;background:#fff}
        .list-table{width:100%;padding:20px;border-spacing:0}
        .list-table tr{height:40px}
        .list-table tr[data-to]:hover{background:#f1f1f1}
        .list-table tr:first-child{background:#fff}
        .list-table td,.list-table th{padding:0 10px;text-align:left}
        .list-table .size,.list-table .updated_at{text-align:right}
        .list-table .file ion-icon{font-size:15px;margin-right:5px;vertical-align:bottom}
<?php if ($config['admin']) { ?>
        .operate{display: inline-table;margin:0;list-style:none;}
        .operate ul{position: absolute;display: none;background: #fff;border:1px #f7f7f7 solid;border-radius: 5px;margin:-17px 0 0 0;padding: 0;color:#205D67;}
        .operate:hover ul{position: absolute;display:inline-table;}
        .operate ul li{padding:1px;list-style:none;}
        .operatediv_close{position: absolute;right: 3px;top:3px;}
<?php } ?>
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
<?php
    if ($path !== '/') {
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
                <a href="<?php echo $parent_url.'/'; ?>" class="back-link">
                    <ion-icon name="arrow-back"></ion-icon>
                </a>
<?php } ?>
                <h3 class="table-header"><?php echo str_replace('%23', '#', str_replace('&','&amp;', $path)); ?></h3>
                <div class="login">
<?php
    if (getenv('admin')!='') if (!$config['admin']) {
        if (getenv('adminloginpage')=='') { ?>
                    <a onclick="login();">登录</a>
<?php   }
    } else { ?>
                    <li class="operate">管理<ul style="margin:-17px 0 0 -66px;">
                    <li><a onclick="logout()">登出</a></li>
<?php   if (isset($files['folder'])) { ?>
                    <li><a onclick="showdiv(event,'create','');">新建</a></li>
                    <li><a onclick="showdiv(event,'encrypt','');">加密</a></li>
                    </ul></li>
<?php   }
    } ?>
                </div>
            </div>
            <div class="list-body-container">
<?php
    if (path_format('/'.path_format(urldecode($config['list_path'].$path)).'/')==path_format('/'.path_format($config['imgup_path']).'/')&&$config['imgup_path']!=''&&!$config['admin']) { ?>
                <div id="upload_div" style="margin:10px">
                <center>
                    <form action="" method="POST">
                    <input id="upload_content" type="hidden" name="guest_upload_filecontent">
                    <input id="upload_file" type="file" name="upload_filename" onchange="base64upfile()">
                    <input type="submit" value="上传">文件大小<4M，不然传输失败！
                    </form>
                <center>
                </div>
<?php } else { 
        if ($config['ishidden']<4) {
            if (isset($files['error'])) {
                    echo '<div style="margin:8px;">' . $files['error']['message'] . '</div>';
                    $statusCode=404;
            } else {
                if (isset($files['file'])) {
?>
                <div style="margin: 12px 4px 4px; text-align: center">
                    <div style="margin: 24px">
                        <textarea id="url" title="url" rows="1" style="width: 100%; margin-top: 2px;" readonly><?php echo str_replace('%2523', '%23', str_replace('%26amp%3B','&amp;',spurlencode(path_format($config['base_path'] . '/' . $path), '/'))); ?></textarea>
                        <a href="<?php echo path_format($config['base_path'] . '/' . $path);//$files['@microsoft.graph.downloadUrl'] ?>"><ion-icon name="download" style="line-height: 16px;vertical-align: middle;"></ion-icon>&nbsp;下载</a>
                    </div>
                    <div style="margin: 24px">
<?php               $ext = strtolower(substr($path, strrpos($path, '.') + 1));
                    $DPvideo='';
                    if (in_array($ext, ['ico', 'bmp', 'gif', 'jpg', 'jpeg', 'jpe', 'jfif', 'tif', 'tiff', 'png', 'heic', 'webp'])) {
                        echo '
                        <img src="' . $files['@microsoft.graph.downloadUrl'] . '" alt="' . substr($path, strrpos($path, '/')) . '" onload="if(this.offsetWidth>document.getElementById(\'url\').offsetWidth) this.style.width=\'100%\';" />
';
                    } elseif (in_array($ext, ['mp4', 'webm', 'mkv', 'flv', 'blv', 'avi', 'wmv', 'ogg'])) {
                    //echo '<video src="' . $files['@microsoft.graph.downloadUrl'] . '" controls="controls" style="width: 100%"></video>';
                        $DPvideo=$files['@microsoft.graph.downloadUrl'];
                        echo '<div id="video-a0"></div>';
                    } elseif (in_array($ext, ['mp3', 'wma', 'flac', 'wav'])) {
                        echo '
                        <audio src="' . $files['@microsoft.graph.downloadUrl'] . '" controls="controls" style="width: 100%"></audio>
';
                    } elseif (in_array($ext, ['pdf'])) {
                        echo '
                        <embed src="' . $files['@microsoft.graph.downloadUrl'] . '" type="application/pdf" width="100%" height=800px">
';
                    } elseif (in_array($ext, ['doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx'])) {
                        echo '
                        <iframe id="office-a" src="https://view.officeapps.live.com/op/view.aspx?src=' . urlencode($files['@microsoft.graph.downloadUrl']) . '" style="width: 100%;height: 800px" frameborder="0"></iframe>
';
                    } elseif (in_array($ext, ['txt', 'sh', 'php', 'asp', 'js', 'html', 'c'])) {
                        $txtstr = htmlspecialchars(curl_request($files['@microsoft.graph.downloadUrl']));
?>
                        <div id="txt">
<?php                   if ($config['admin']) { ?>
                        <form id="txt-form" action="" method="POST">
                            <a onclick="enableedit(this);" id="txt-editbutton">点击后编辑</a>
                            <a id="txt-save" style="display:none">保存</a>
<?php                   } ?>
                            <textarea id="txt-a" name="editfile" readonly style="width: 100%; margin-top: 2px;" <?php if ($config['admin']) echo 'onchange="document.getElementById(\'txt-save\').onclick=function(){document.getElementById(\'txt-form\').submit();}"';?> ><?php echo $txtstr;?></textarea>
<?php                   if ($config['admin']) echo '</form>'; ?>
                        </div>
<?php               } elseif (in_array($ext, ['md'])) {
                        echo '
                        <div class="markdown-body" id="readme">
                            <textarea id="readme-md" style="display:none;">' . curl_request($files['@microsoft.graph.downloadUrl']) . '</textarea>
                        </div>
';
                    } else {
                        echo '<span>文件格式不支持预览</span>';
                    } ?>
                    </div>
                </div>
<?php           } elseif (isset($files['folder'])) {
                    $filenum = $_POST['filenum'];
                    if (!$filenum and $files['folder']['page']) $filenum = ($files['folder']['page']-1)*200;
                    $readme = false; ?>
                <div id="thumbnailsbutton" style="position:absolute;"><input type="button" value="图片缩略图" onclick="showthumbnails(this);"></div>
                <table class="list-table" id="list-table">
                    <tr id="tr0">
                        <!--<th class="updated_at" width="5%">序号</th>-->
                        <th class="file" width="60%" onclick="sortby('a');">文件</th>
                        <th class="updated_at" width="25%" onclick="sortby('time');">修改时间</th>
                        <th class="size" width="15%" onclick="sortby('size');">大小</th>
                    </tr>
                    <!-- Dirs -->
<?php               //echo json_encode($files['children'], JSON_PRETTY_PRINT);
                    foreach ($files['children'] as $file) {
                        // Folders
                        if (isset($file['folder'])) { 
                            $filenum++; ?>
                    <tr data-to id="tr<?php echo $filenum;?>">
                        <!--<td class="updated_at"><?php echo $filenum;?></td>-->
                        <td class="file">
                            <ion-icon name="folder"></ion-icon>
                            <a id="file_a<?php echo $filenum;?>" href="<?php echo path_format($config['base_path'] . '/' . $path . '/' . encode_str_replace($file['name']) . '/'); ?>"><?php echo str_replace('&','&amp;', $file['name']);?></a>
<?php                       if ($config['admin']) {?>
                            &nbsp;&nbsp;&nbsp;
                            <li class="operate">管理
                            <ul>
                                <li><a onclick="showdiv(event,'encrypt',<?php echo $filenum;?>);">加密</a></li>
                                <li><a onclick="showdiv(event, 'rename',<?php echo $filenum;?>);">重命名</a></li>
                                <li><a onclick="showdiv(event, 'move',<?php echo $filenum;?>);">移动</a></li>
                                <li><a onclick="showdiv(event, 'delete',<?php echo $filenum;?>);">删除</a></li>
                            </ul>
                            </li>
<?php                       }?>
                        </td>
                        <td class="updated_at" id="folder_time<?php echo $filenum;?>"><?php echo time_format($file['lastModifiedDateTime']); ?></td>
                        <td class="size" id="folder_size<?php echo $filenum;?>"><?php echo size_format($file['size']); ?></td>
                    </tr>
<?php                   }
                    }
                    foreach ($files['children'] as $file) {
                        // Files
                        if (isset($file['file'])) {
                            if ($config['admin'] or (substr($file['name'],0,1) !== '.' and $file['name'] !== $config['passfile']) ) {
                                if (strtolower($file['name']) === 'readme.md') $readme = $file;
                                if (strtolower($file['name']) === 'index.html') {
                                    $html = curl_request(fetch_files(spurlencode(path_format($path . '/' .$file['name']),'/'))['@microsoft.graph.downloadUrl']);
                                    return output($html,200);
                                }
                                $filenum++; ?>
                    <tr data-to id="tr<?php echo $filenum;?>">
                        <!--<td class="updated_at"><?php echo $filenum;?></td>-->
                        <td class="file">
                            <ion-icon name="document"></ion-icon>
                            <a id="file_a<?php echo $filenum;?>" name="filelist" href="<?php echo path_format($config['base_path'] . '/' . $path . '/' . encode_str_replace($file['name'])); ?>?preview" target=_blank><?php echo str_replace('&','&amp;', $file['name']); ?></a>
                            <a href="<?php echo path_format($config['base_path'] . '/' . $path . '/' . str_replace('&','&amp;', $file['name']));?>"><ion-icon name="download"></ion-icon></a>
<?php                           if ($config['admin']) {?>
                            &nbsp;&nbsp;&nbsp;
                            <li class="operate">管理
                            <ul>
                                <li><a onclick="showdiv(event, 'rename',<?php echo $filenum;?>);">重命名</a></li>
                                <li><a onclick="showdiv(event, 'move',<?php echo $filenum;?>);">移动</a></li>
                                <li><a onclick="showdiv(event, 'delete',<?php echo $filenum;?>);">删除</a></li>
                            </ul>
                            </li>
<?php                           }?>
                        </td>
                        <td class="updated_at" id="file_time<?php echo $filenum;?>"><?php echo time_format($file['lastModifiedDateTime']); ?></td>
                        <td class="size" id="file_size<?php echo $filenum;?>"><?php echo size_format($file['size']); ?></td>
                    </tr>
<?php                       }
                        }
                    } ?>
                </table>
<?php               if ($files['folder']['childCount']>200) {
                        $pagenum = $files['folder']['page'];
                        $maxpage = ceil($files['folder']['childCount']/200);
                        $prepagenext = '
                <form action="" method="POST" id="nextpageform">
                    <input type="hidden" id="pagenum" name="pagenum" value="'. $pagenum .'">
                    <table width=100% border=0>
                        <tr>
                            <td width=60px align=center>';
                        if ($pagenum!=1) {
                            $prepagenum = $pagenum-1;
                            $prepagenext .= '
                                <a onclick="nextpage('.$prepagenum.');">上一页</a>';
                        }
                        $prepagenext .= '
                            </td>
                            <td class="updated_at">';
                        for ($page=1;$page<=$maxpage;$page++) {
                            if ($page == $pagenum) {
                                $prepagenext .= '
                                <font color=red>' . $page . '</font> ';
                            } else {
                                $prepagenext .= '
                                <a onclick="nextpage('.$page.');">' . $page . '</a> ';
                            }
                        }
                        $prepagenext = substr($prepagenext,0,-1);
                        $prepagenext .= '
                            </td>
                            <td width=60px align=center>';
                        if ($pagenum!=$maxpage) {
                            $nextpagenum = $pagenum+1;
                            $prepagenext .= '
                                <a onclick="nextpage('.$nextpagenum.');">下一页</a>';
                        }
                        $prepagenext .= '
                            </td>
                        </tr>
                    </table>
                </form>';
                        echo $prepagenext;
                    }
                    if ($config['admin']) { ?>
                <div id="upload_div" style="margin:0 0 16px 0">
                <center>
                    <input id="upload_file" type="file" name="upload_filename" multiple="multiple">
                    <input id="upload_submit" onclick="preup();" value="上传" type="button">
                </center>
                </div>
<?php               }
                } else {
                    $statusCode=500;
                    echo 'Unknown path or file.';
                    echo json_encode($files, JSON_PRETTY_PRINT);
                }
                if ($readme) {
                    echo '
            </div>
        </div>
    </div>
    <div class="list-wrapper">
        <div class="list-container">
            <div class="list-header-container">
                <div class="readme">
                    <svg class="octicon octicon-book" viewBox="0 0 16 16" version="1.1" width="16" height="16" aria-hidden="true"><path fill-rule="evenodd" d="M3 5h4v1H3V5zm0 3h4V7H3v1zm0 2h4V9H3v1zm11-5h-4v1h4V5zm0 2h-4v1h4V7zm0 2h-4v1h4V9zm2-6v9c0 .55-.45 1-1 1H9.5l-1 1-1-1H2c-.55 0-1-.45-1-1V3c0-.55.45-1 1-1h5.5l1 1 1-1H15c.55 0 1 .45 1 1zm-8 .5L7.5 3H2v9h6V3.5zm7-.5H9.5l-.5.5V12h6V3z"></path></svg>
                    <span style="line-height: 16px;vertical-align: top;">'.$readme['name'].'</span>
                    <div class="markdown-body" id="readme">
                        <textarea id="readme-md" style="display:none;">' . curl_request(fetch_files(spurlencode(path_format($path . '/' .$readme['name']),'/'))['@microsoft.graph.downloadUrl']). '
                        </textarea>
                    </div>
                </div>
';
                }
            }
        } else {
            echo '
                <div style="padding:20px">
	            <center>
                    <h4>输入密码进行查看</h4>
	                <form action="" method="post">
		            <label>密码</label>
		            <input name="password1" type="password"/>
		            <input type="submit" value="查看">
	                </form>
                </center>
                </div>';
            $statusCode = 401;
        }
    } ?>
            </div>
        </div>
    </div>
    <div id="mask" style="position:absolute;display:none;left:0px;top:0px;width:100%;background-color:#000;filter:alpha(opacity=50);opacity:0.5"></div>
<?php
    if ($config['admin']&&!$_GET['preview']) { ?>
    <div>
        <div id="rename_div" name="operatediv" style="position: absolute;border: 10px #CCCCCC;background-color: #FFFFCC; display:none">
            <div style="margin:16px">
                <label id="rename_label"></label><br><br><a onclick="operatediv_close('rename')" class="operatediv_close">关闭</a>
                <form id="rename_form" onsubmit="return submit_operate('rename');">
                <input id="rename_sid" name="rename_sid" type="hidden" value="">
                <input id="rename_hidden" name="rename_oldname" type="hidden" value="">
                <input id="rename_input" name="rename_newname" type="text" value="">
                <input name="operate_action" type="submit" value="重命名">
                </form>
            </div>
        </div>
        <div id="delete_div" name="operatediv" style="position: absolute;border: 10px #CCCCCC;background-color: #FFFFCC; display:none">
            <div style="margin:16px">
                <br><a onclick="operatediv_close('delete')" class="operatediv_close">关闭</a>
                <label id="delete_label"></label>
                <form id="delete_form" onsubmit="return submit_operate('delete');">
                <label id="delete_input"></label>
                <input id="delete_sid" name="delete_sid" type="hidden" value="">
                <input id="delete_hidden" name="delete_name" type="hidden" value="">
                <input name="operate_action" type="submit" value="确定删除">
                </form>
            </div>
        </div>
        <div id="encrypt_div" name="operatediv" style="position: absolute;border: 10px #CCCCCC;background-color: #FFFFCC; display:none">
            <div style="margin:16px">
                <label id="encrypt_label"></label><br><br><a onclick="operatediv_close('encrypt')" class="operatediv_close">关闭</a>
                <form id="encrypt_form" onsubmit="return submit_operate('encrypt');">
                <input id="encrypt_sid" name="encrypt_sid" type="hidden" value="">
                <input id="encrypt_hidden" name="encrypt_folder" type="hidden" value="">
                <input id="encrypt_input" name="encrypt_newpass" type="text" value="" placeholder="输入想要设置的密码">
                <?php if (getenv('passfile')!='') {?><input name="operate_action" type="submit" value="加密"><?php } else { ?><br><label>先在环境变量设置passfile才能加密</label><?php } ?>
                </form>
            </div>
        </div>
        <div id="move_div" name="operatediv" style="position: absolute;border: 10px #CCCCCC;background-color: #FFFFCC; display:none">
            <div style="margin:16px">
                <label id="move_label"></label><br><br><a onclick="operatediv_close('move')" class="operatediv_close">关闭</a>
                <form id="move_form" onsubmit="return submit_operate('move');">
                <input id="move_sid" name="move_sid" type="hidden" value="">
                <input id="move_hidden" name="move_name" type="hidden" value="">
                <select id="move_input" name="move_folder">
<?php   if ($path != '/') { ?>
                    <option value="/../">上一级目录</option>
<?php   }
        if (isset($files['children'])) foreach ($files['children'] as $file) {
            if (isset($file['folder'])) { ?>
                    <option value="<?php echo str_replace('&','&amp;', $file['name']);?>"><?php echo str_replace('&','&amp;', $file['name']);?></option>
<?php       }
        } ?>
                </select>
                <input name="operate_action" type="submit" value="移动">
                </form>
            </div>
        </div>
        <div id="create_div" name="operatediv" style="position: absolute;border: 1px #CCCCCC;background-color: #FFFFCC; display:none">
            <div style="margin:50px">
                <label id="create_label"></label><br><a onclick="operatediv_close('create')" class="operatediv_close">关闭</a>
                <form id="create_form" onsubmit="return submit_operate('create');">
                <input id="create_sid" name="create_sid" type="hidden" value="">
                <input id="create_hidden" type="hidden" value="">
                　　　<input id="create_type_folder" name="create_type" type="radio" value="folder" onclick="document.getElementById('create_text_div').style.display='none';">文件夹
                <input id="create_type_file" name="create_type" type="radio" value="file" onclick="document.getElementById('create_text_div').style.display='';" checked>文件<br>
                名字：<input id="create_input" name="create_name" type="text" value=""><br>
                <div id="create_text_div">内容：<textarea id="create_text" name="create_text" rows="6" cols="40"></textarea><br></div>
                <input name="operate_action" type="submit" value="新建">
                </form>
            </div>
        </div>
    </div>
<?php
    } else {
        if (getenv('admin')!='') if (getenv('adminloginpage')=='') { ?>
    <div id="login_div" style="position: absolute;border: 1px #CCCCCC;background-color: #FFFFCC; display:none">
        <div style="margin:50px">
            <a onclick="operatediv_close('login')" style="position: absolute;right: 10px;top:5px;">关闭</a>
	        <center>
                <h4>输入管理密码</h4>
	            <form action="<?php echo $_GET['preview']?'?preview&':'?';?>admin" method="post">
		        <label>密码</label>
		        <input id="login_input" name="password1" type="password"/>
		        <input type="submit" value="查看">
	            </form>
            </center>
        </div>
	</div>
<?php   }
    } ?>
    <font color="#f7f7f9"><?php $weekarray=array("日","一","二","三","四","五","六"); echo date("Y-m-d H:i:s")." 星期".$weekarray[date("w")]." ".$_SERVER['REMOTE_ADDR'];?></font>
</body>

<link rel="stylesheet" href="//unpkg.zhimg.com/github-markdown-css@3.0.1/github-markdown.css">
<script type="text/javascript" src="//unpkg.zhimg.com/marked@0.6.2/marked.min.js"></script>
<script type="text/javascript">
    var root = '<?php echo $config["base_path"]; ?>';
    var sort=0;
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
        if (paths <= 2) return;
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
    function showthumbnails(obj) {
        var files=document.getElementsByName('filelist');
        for ($i=0;$i<files.length;$i++) {
            str=files[$i].innerText;
            if (str.substr(-1)==' ') str=str.substr(0,str.length-1);
            if (!str) return;
            strarry=str.split('.');
            ext=strarry[strarry.length-1];
            images = ['ico', 'bmp', 'gif', 'jpg', 'jpeg', 'jpe', 'jfif', 'tif', 'tiff', 'png', 'heic', 'webp'];
            if (images.indexOf(ext)>-1) get_thumbnails_url(str, files[$i]);
        }
        obj.disabled='disabled';
    }
    function get_thumbnails_url(str, filea) {
        if (!str) return;
        var nurl=window.location.href;
        if (nurl.substr(-1)!="/") nurl+="/";
        var xhr = new XMLHttpRequest();
        xhr.open("GET", nurl+str+'?thumbnails', true);
                //xhr.setRequestHeader('x-requested-with','XMLHttpRequest');
        xhr.send('');
        xhr.onload = function(e){
            if (xhr.status==200) {
                if (xhr.responseText!='') filea.innerHTML='<img src="'+xhr.responseText+'" alt="'+str+'">';
            } else console.log(xhr.status+'\n'+xhr.responseText);
        }
    }
    function sortby(string) {
        if (string=='a') if (sort!=0) {
            for (i = 1; i <= <?php echo $filenum?$filenum:0;?>; i++) document.getElementById('tr'+i).parentNode.insertBefore(document.getElementById('tr'+i),document.getElementById('tr'+(i-1)).nextSibling);
            sort=0;
            return;
        } else return;
        // if (string=='time' && sort==1) return;
        // if (string=='size' && sort==2) return;
        sort1=sort;
        sortby('a');
        sort=sort1;
        var a=[];
        for (i = 1; i <= <?php echo $filenum?$filenum:0;?>; i++) {
            a[i]=i;
            if (!!document.getElementById('folder_'+string+i)) {
                var td1=document.getElementById('folder_'+string+i);
                for (j = 1; j < i; j++) {
                    if (!!document.getElementById('folder_'+string+a[j])) {
                        var c=false;
                        if (string=='time') if (sort==-1) {
                            c=(td1.innerText < document.getElementById('folder_'+string+a[j]).innerText);
                        } else {
                            c=(td1.innerText > document.getElementById('folder_'+string+a[j]).innerText);
                        }
                        if (string=='size') if (sort==2) {
                            c=(size_reformat(td1.innerText) < size_reformat(document.getElementById('folder_'+string+a[j]).innerText));
                        } else {
                            c=(size_reformat(td1.innerText) > size_reformat(document.getElementById('folder_'+string+a[j]).innerText));
                        }
                        if (c) {
                            document.getElementById('tr'+i).parentNode.insertBefore(document.getElementById('tr'+i),document.getElementById('tr'+a[j]));
                            for (k = i; k > j; k--) {
                                a[k]=a[k-1];
                            }
                            a[j]=i;
                            break;
                        }
                    }
                }
            }
            if (!!document.getElementById('file_'+string+i)) {
                var td1=document.getElementById('file_'+string+i);
                for (j = 1; j < i; j++) {
                    if (!!document.getElementById('file_'+string+a[j])) {
                        var c=false;
                        if (string=='time') if (sort==-1) {
                            c=(td1.innerText < document.getElementById('file_'+string+a[j]).innerText);
                        } else {
                            c=(td1.innerText > document.getElementById('file_'+string+a[j]).innerText);
                        }
                        if (string=='size') if (sort==2) {
                            c=(size_reformat(td1.innerText) < size_reformat(document.getElementById('file_'+string+a[j]).innerText));
                        } else {
                            c=(size_reformat(td1.innerText) > size_reformat(document.getElementById('file_'+string+a[j]).innerText));
                        }
                        if (c) {
                            document.getElementById('tr'+i).parentNode.insertBefore(document.getElementById('tr'+i),document.getElementById('tr'+a[j]));
                            for (k = i; k > j; k--) {
                                a[k]=a[k-1];
                            }
                            a[j]=i;
                            break;
                        }
                    }
                }
            }
        }
        if (string=='time') if (sort==-1) {
            sort=1;
        } else {
            sort=-1;
        }
        if (string=='size') if (sort==2) {
            sort=-2;
        } else {
            sort=2;
        }
    }
    function size_reformat(str) {
        if (str.substr(-1)==' ') str=str.substr(0,str.length-1);
        if (str.substr(-2)=='GB') num=str.substr(0,str.length-3)*1024*1024*1024;
        if (str.substr(-2)=='MB') num=str.substr(0,str.length-3)*1024*1024;
        if (str.substr(-2)=='KB') num=str.substr(0,str.length-3)*1024;
        if (str.substr(-2)==' B') num=str.substr(0,str.length-2);
        return num;
    }
<?php
    /*if ($config['ishidden']==2) { //有密码写目录密码 ?>
    var $ishidden = '<?php echo $config['ishidden']; ?>';
    var $hiddenpass = '<?php echo md5($_POST['password1']);?>';
    if ($ishidden==2) {
        var expd = new Date();
        expd.setTime(expd.getTime()+(2*60*60*1000));
        var expires = "expires="+expd.toGMTString();
        document.cookie="password="+$hiddenpass+";"+expires;
    }
<?php }*/
    if ($_COOKIE['timezone']=='') { //无时区写时区 ?>
    var nowtime= new Date();
    var timezone = 0-nowtime.getTimezoneOffset()/60;
    var expd = new Date();
    expd.setTime(expd.getTime()+(2*60*60*1000));
    var expires = "expires="+expd.toGMTString();
    document.cookie="timezone="+timezone+";"+expires;
    if (timezone!='8') {
        alert('Your timezone is '+timezone+', reload local timezone.');
        location.href=location.protocol + "//" + location.host + "<?php echo path_format($config['base_path'] . '/' . $path );?>" ;
    }
<?php }
    if ($files['folder']['childCount']>200) { //有下一页 ?>
    function nextpage(num) {
        document.getElementById('pagenum').value=num;
        document.getElementById('nextpageform').submit();
    }
<?php }
    if ($_GET['preview']) { //在预览时处理 ?>
    var $url = document.getElementById('url');
    if ($url) {
        $url.innerHTML = location.protocol + '//' + location.host + $url.innerHTML;
        $url.style.height = $url.scrollHeight + 'px';
    }
    var $officearea=document.getElementById('office-a');
    if ($officearea) {
        $officearea.style.height = window.innerHeight + 'px';
    }
    var $textarea=document.getElementById('txt-a');
    if ($textarea) {
        $textarea.style.height = $textarea.scrollHeight + 'px';
    }
<?php   if (!!$DPvideo) { ?>
    function loadResources(type, src, callback) {
        let script = document.createElement(type);
        let loaded = false;
        if (typeof callback === 'function') {
            script.onload = script.onreadystatechange = () => {
                if (!loaded && (!script.readyState || /loaded|complete/.test(script.readyState))) {
                    script.onload = script.onreadystatechange = null;
                    loaded = true;
                    callback();
                }
            }
        }
        if (type === 'link') {
            script.href = src;
            script.rel = 'stylesheet';
        } else {
            script.src = src;
        }
        document.getElementsByTagName('head')[0].appendChild(script);
    }
    function addVideos(videos) {
        let host = 'https://s0.pstatp.com/cdn/expire-1-M';
        let unloadedResourceCount = 4;
        let callback = (() => {
            return () => {
                if (!--unloadedResourceCount) {
                    createDplayers(videos);
                }
            };
        })(unloadedResourceCount, videos);
        loadResources(
            'link',
            host + '/dplayer/1.25.0/DPlayer.min.css',
            callback
        );
        loadResources(
            'script',
            host + '/dplayer/1.25.0/DPlayer.min.js',
            callback
        );
        loadResources(
            'script',
            host + '/hls.js/0.12.4/hls.light.min.js',
            callback
        );
        loadResources(
            'script',
            host + '/flv.js/1.5.0/flv.min.js',
            callback
        );
    }
    function createDplayers(videos) {
        for (i = 0; i < videos.length; i++) {
            console.log(videos[i]);
            new DPlayer({
                container: document.getElementById('video-a' + i),
                screenshot: true,
                video: {
                    url: videos[i]
                }
            });
        }
    }
    addVideos(['<?php echo $DPvideo;?>']);
<?php   } 
    }
    if (getenv('admin')!='') { //有登录或操作，需要关闭DIV时 ?>
    function operatediv_close(operate) {
        document.getElementById(operate+'_div').style.display='none';
        document.getElementById('mask').style.display='none';
    }
<?php }
    if (path_format('/'.path_format(urldecode($config['list_path'].$path)).'/')==path_format('/'.path_format($config['imgup_path']).'/')&&$config['imgup_path']!=''&&!$config['admin']) { //当前是图床目录时 ?>
    function base64upfile() {
        var $file=document.getElementById('upload_file').files[0];
        var $reader = new FileReader();
        $reader.onloadend=function(e) {
            var $data=$reader.result;
            document.getElementById('upload_content').value=$data;
        }
        $reader.readAsDataURL($file);
    }
<?php }
    if ($config['admin']) { //管理登录后 ?>
    function logout() {
        var expd = new Date();
        expd.setTime(expd.getTime()-(60*1000));
        var expires = "expires="+expd.toGMTString();
        document.cookie="<?php echo $config['function_name'];?>='';"+expires;
        location.href=location.protocol + "//" + location.host + "<?php echo path_format($config['base_path'].str_replace('&amp;','&',$path));?>";
    }
    function enableedit(obj) {
        document.getElementById('txt-a').readOnly=!document.getElementById('txt-a').readOnly;
        //document.getElementById('txt-editbutton').innerHTML=(document.getElementById('txt-editbutton').innerHTML=='取消编辑')?'点击后编辑':'取消编辑';
        obj.innerHTML=(obj.innerHTML=='取消编辑')?'点击后编辑':'取消编辑';
        document.getElementById('txt-save').style.display=document.getElementById('txt-save').style.display==''?'none':'';
    }
<?php   if (!$_GET['preview']) {?>
    function showdiv(event,action,num) {
        var $operatediv=document.getElementsByName('operatediv');
        for ($i=0;$i<$operatediv.length;$i++) {
            $operatediv[$i].style.display='none';
        }
        document.getElementById('mask').style.display='';
        //document.getElementById('mask').style.width=document.documentElement.scrollWidth+'px';
        document.getElementById('mask').style.height=document.documentElement.scrollHeight<window.innerHeight?window.innerHeight:document.documentElement.scrollHeight+'px';
        if (num=='') {
            var str='';
        } else {
            var str=document.getElementById('file_a'+num).innerText;
            if (str=='') {
                str=document.getElementById('file_a'+num).getElementsByTagName("img")[0].alt;
                if (str=='') {
                    alert('获取文件名失败！');
                    operatediv_close(action);
                    return;
                }
            }
            if (str.substr(-1)==' ') str=str.substr(0,str.length-1);
        }
        document.getElementById(action + '_div').style.display='';
        document.getElementById(action + '_label').innerText=str;//.replace(/&/,'&amp;');
        document.getElementById(action + '_sid').value=num;
        document.getElementById(action + '_hidden').value=str;
        if (action=='rename') document.getElementById(action + '_input').value=str;

        var $e = event || window.event;
        var $scrollX = document.documentElement.scrollLeft || document.body.scrollLeft;
        var $scrollY = document.documentElement.scrollTop || document.body.scrollTop;
        var $x = $e.pageX || $e.clientX + $scrollX;
        var $y = $e.pageY || $e.clientY + $scrollY;
        if (action=='create') {
            document.getElementById(action + '_div').style.left=(document.body.clientWidth-document.getElementById(action + '_div').offsetWidth)/2 +'px';
            document.getElementById(action + '_div').style.top=(window.innerHeight-document.getElementById(action + '_div').offsetHeight)/2+$scrollY +'px';
        } else {
            if ($x + document.getElementById(action + '_div').offsetWidth > document.body.clientWidth) {
                document.getElementById(action + '_div').style.left=document.body.clientWidth-document.getElementById(action + '_div').offsetWidth+'px';
            } else {
                document.getElementById(action + '_div').style.left=$x+'px';
            }
            document.getElementById(action + '_div').style.top=$y+'px';
        }
        document.getElementById(action + '_input').focus();
    }
    function submit_operate(str) {
        var num=document.getElementById(str+'_sid').value;
        var xhr = new XMLHttpRequest();
        xhr.open("POST", '', true);
        xhr.setRequestHeader('x-requested-with','XMLHttpRequest');
        xhr.send(serializeForm(str+'_form'));
        xhr.onload = function(e){
            var html;
            if (xhr.status<300) {
                if (str=='rename') {
                    html=JSON.parse(xhr.responseText);
                    var file_a = document.getElementById('file_a'+num);
                    file_a.innerText=html.name;
                    file_a.href = (file_a.href.substr(-8)=='?preview')?(html.name.replace(/#/,'%23')+'?preview'):(html.name.replace(/#/,'%23')+'/');
                }
                if (str=='move'||str=='delete') document.getElementById('tr'+num).parentNode.removeChild(document.getElementById('tr'+num));
                if (str=='create') {
                    html=JSON.parse(xhr.responseText);
                    addelement(html);
                }
            } else alert(xhr.status+'\n'+xhr.responseText);
            document.getElementById(str+'_div').style.display='none';
            document.getElementById('mask').style.display='none';
        }
        return false;
    }
    function addelement(html) {
        var tr1=document.createElement('tr');
        tr1.setAttribute('data-to',1);
        var td1=document.createElement('td');
        td1.setAttribute('class','file');
        var a1=document.createElement('a');
        a1.href=html.name.replace(/#/,'%23');
        a1.innerText=html.name;
        a1.target='_blank';
        var td2=document.createElement('td');
        td2.setAttribute('class','updated_at');
        td2.innerText=html.lastModifiedDateTime.replace(/T/,' ').replace(/Z/,'');
        var td3=document.createElement('td');
        td3.setAttribute('class','size');
        td3.innerText=size_format(html.size);
        if (!!html.folder) {
            a1.href+='/';
            document.getElementById('tr0').parentNode.insertBefore(tr1,document.getElementById('tr0').nextSibling);
        }
        if (!!html.file) {
            a1.href+='?preview';
            a1.name='filelist';
            document.getElementById('tr0').parentNode.appendChild(tr1);
        }
        tr1.appendChild(td1);
        td1.appendChild(a1);
        tr1.appendChild(td2);
        tr1.appendChild(td3);
    }
    //获取指定form中的所有的<input>对象 
    function getElements(formId) { 
        var form = document.getElementById(formId); 
        var elements = new Array(); 
        var tagElements = form.getElementsByTagName('input'); 
        for (var j = 0; j < tagElements.length; j++){ 
            elements.push(tagElements[j]); 
        } 
        var tagElements = form.getElementsByTagName('select'); 
        for (var j = 0; j < tagElements.length; j++){ 
            elements.push(tagElements[j]); 
        } 
        var tagElements = form.getElementsByTagName('textarea'); 
        for (var j = 0; j < tagElements.length; j++){ 
            elements.push(tagElements[j]); 
        }
        return elements; 
    } 
    //组合URL 
    function serializeElement(element) { 
        var method = element.tagName.toLowerCase(); 
        var parameter; 
        if (method == 'select') {
            parameter = [element.name, element.value]; 
        }
        switch (element.type.toLowerCase()) { 
            case 'submit': 
            case 'hidden': 
            case 'password': 
            case 'text':
            case 'date':
            case 'textarea': 
                parameter = [element.name, element.value];
                break;
            case 'checkbox': 
            case 'radio': 
                if (element.checked){
                    parameter = [element.name, element.value]; 
                }
                break;    
        } 
        if (parameter) { 
            var key = encodeURIComponent(parameter[0]); 
            if (key.length == 0) return; 
            if (parameter[1].constructor != Array) parameter[1] = [parameter[1]]; 
            var values = parameter[1]; 
            var results = []; 
            for (var i = 0; i < values.length; i++) { 
                results.push(key + '=' + encodeURIComponent(values[i])); 
            } 
            return results.join('&'); 
        } 
    } 
    //调用方法  
    function serializeForm(formId) { 
        var elements = getElements(formId); 
        var queryComponents = new Array(); 
        for (var i = 0; i < elements.length; i++) { 
            var queryComponent = serializeElement(elements[i]); 
            if (queryComponent) {
                queryComponents.push(queryComponent); 
            } 
        } 
        return queryComponents.join('&'); 
    } 
    function uploadbuttonhide() {
        document.getElementById('upload_submit').disabled='disabled';
        document.getElementById('upload_file').disabled='disabled';
        document.getElementById('upload_submit').style.display='none';
        document.getElementById('upload_file').style.display='none';
    }
    function uploadbuttonshow() {
        document.getElementById('upload_file').disabled='';
        document.getElementById('upload_submit').disabled='';
        document.getElementById('upload_submit').style.display='';
        document.getElementById('upload_file').style.display='';
    }
    function preup() {
        uploadbuttonhide();
        var files=document.getElementById('upload_file').files;
        var table1=document.createElement('table');
        document.getElementById('upload_div').appendChild(table1);
        table1.setAttribute('class','list-table');
        var timea=new Date().getTime();
        var i=0;
        getuplink(i);
        function getuplink(i) {
            var file=files[i];
            var tr1=document.createElement('tr');
            table1.appendChild(tr1);
            tr1.setAttribute('data-to',1);
            var td1=document.createElement('td');
            tr1.appendChild(td1);
            td1.setAttribute('style','width:30%');
            td1.setAttribute('id','upfile_td1_'+timea+'_'+i);
            td1.innerHTML=file.name+'<br>'+size_format(file.size);
            var td2=document.createElement('td');
            tr1.appendChild(td2);
            td2.setAttribute('id','upfile_td2_'+timea+'_'+i);
            td2.innerHTML='获取链接 ...';
            if (file.size>15*1024*1024*1024) {
                td2.innerHTML='<font color="red">大于15G，终止上传。</font>';
                uploadbuttonshow();
                return;
            }
            var xhr1 = new XMLHttpRequest();
            xhr1.open("POST", '');
            xhr1.setRequestHeader('x-requested-with','XMLHttpRequest');
            xhr1.send('upbigfilename='+ encodeURIComponent(file.name) +'&filesize='+ file.size +'&lastModified='+ file.lastModified);
            xhr1.onload = function(e){
                td2.innerHTML='<font color="red">'+xhr1.responseText+'</font>';
                if (xhr1.status==200) {
                    var html=JSON.parse(xhr1.responseText);
                    if (!html['uploadUrl']) {
                        td2.innerHTML='<font color="red">'+xhr1.responseText+'</font><br>';
                        uploadbuttonshow();
                    } else {
                        td2.innerHTML='开始上传 ...';
                        binupfile(file,html['uploadUrl'],timea+'_'+i);
                    }
                }
                if (i<files.length-1) {
                    i++;
                    getuplink(i);
                }
            }
        }
    }
    function size_format(num) {
        if (num>1024) {
            num=num/1024;
        } else {
            return num.toFixed(2) + ' B';
        }
        if (num>1024) {
            num=num/1024;
        } else {
            return num.toFixed(2) + ' KB';
        }
        if (num>1024) {
            num=num/1024;
        } else {
            return num.toFixed(2) + ' MB';
        }
        return num.toFixed(2) + ' GB';
    }
    function binupfile(file,url,tdnum){
        var label=document.getElementById('upfile_td2_'+tdnum);
        var reader = new FileReader();
        var StartStr='';
        var MiddleStr='';
        var StartTime;
        var EndTime;
        var newstartsize = 0;
        if(!!file){
            var asize=0;
            var totalsize=file.size;
            var xhr2 = new XMLHttpRequest();
            xhr2.open("GET", url);
                    //xhr2.setRequestHeader('x-requested-with','XMLHttpRequest');
            xhr2.send(null);
            xhr2.onload = function(e){
                if (xhr2.status==200) {
                    var html=JSON.parse(xhr2.responseText);
                    var a=html['nextExpectedRanges'][0];
                    asize=Number( a.slice(0,a.indexOf("-")) );
                    StartTime = new Date();
                    newstartsize = asize;
                    if (asize==0) {
                        StartStr='开始于：' +StartTime.toLocaleString()+'<br>' ;
                    } else {
                        StartStr='上次上传'+size_format(asize)+ '<br>本次开始于：' +StartTime.toLocaleString()+'<br>' ;
                    }
                    var chunksize=5*1024*1024; // 每小块上传大小，最大60M，微软建议10M
                    if (totalsize>200*1024*1024) chunksize=10*1024*1024;
                    function readblob(start) {
                        var end=start+chunksize;
                        var blob = file.slice(start,end);
                        reader.readAsArrayBuffer(blob);
                    }
                    readblob(asize);
                    reader.onload = function(e){
                        var binary = this.result;
                        var xhr = new XMLHttpRequest();
                        xhr.open("PUT", url, true);
                        //xhr.setRequestHeader('x-requested-with','XMLHttpRequest');
                        bsize=asize+e.loaded-1;
                        xhr.setRequestHeader('Content-Range', 'bytes ' + asize + '-' + bsize +'/'+ totalsize);
                        xhr.upload.onprogress = function(e){
                            if (e.lengthComputable) {
                                var tmptime = new Date();
                                var tmpspeed = e.loaded*1000/(tmptime.getTime()-C_starttime.getTime());
                                var remaintime = (totalsize-asize-e.loaded)/tmpspeed;
                                label.innerHTML=StartStr+'已经上传 ' +size_format(asize+e.loaded)+ ' / '+size_format(totalsize) + ' = ' + ((asize+e.loaded)*100/totalsize).toFixed(2) + '% 平均速度：'+size_format((asize+e.loaded-newstartsize)*1000/(tmptime.getTime()-StartTime.getTime()))+'/s<br>即时速度 '+size_format(tmpspeed)+'/s 预计还要 '+remaintime.toFixed(1)+'s';
                            }
                        }
                        var C_starttime = new Date();
                        xhr.onload = function(e){
                            if (xhr.status<500) {
                            var response=JSON.parse(xhr.responseText);
                            if (response['size']>0) {
                                // 有size说明是最终返回，上传结束
                                var xhr3 = new XMLHttpRequest();
                                xhr3.open("POST", '');
                                xhr3.setRequestHeader('x-requested-with','XMLHttpRequest');
                                xhr3.send('action=del_upload_cache&filename=.'+file.lastModified+ '_' +file.size+ '_' +encodeURIComponent(file.name)+'.tmp');
                                xhr3.onload = function(e){
                                    console.log(xhr3.responseText+','+xhr3.status);
                                }
                                EndTime=new Date();
                                MiddleStr = '结束于：'+EndTime.toLocaleString()+'<br>';
                                if (newstartsize==0) {
                                    MiddleStr += '平均速度：'+size_format(totalsize*1000/(EndTime.getTime()-StartTime.getTime()))+'/s<br>';
                                } else {
                                    MiddleStr += '本次平均速度：'+size_format((totalsize-newstartsize)*1000/(EndTime.getTime()-StartTime.getTime()))+'/s<br>';
                                }
                                document.getElementById('upfile_td1_'+tdnum).innerHTML='<font color="green">'+document.getElementById('upfile_td1_'+tdnum).innerHTML+'<br>上传完成</font>';
                                label.innerHTML=StartStr+MiddleStr;
                                uploadbuttonshow();
                                addelement(response);
                            } else {
                                if (!response['nextExpectedRanges']) {
                                    label.innerHTML='<font color="red">'+xhr.responseText+'</font><br>';
                                } else {
                                    var a=response['nextExpectedRanges'][0];
                                    asize=Number( a.slice(0,a.indexOf("-")) );
                                    readblob(asize);
                                }
                            } } else readblob(asize);
                        }
                        xhr.send(binary);
                    }
                } else {
                    if (window.location.pathname.indexOf('%23')>0||file.name.indexOf('%23')>0) {
                        label.innerHTML='<font color="red">目录或文件名含有#，上传失败。</font>';
                    } else {
                        label.innerHTML='<font color="red">'+xhr2.responseText+'</font>';
                    }
                    uploadbuttonshow();
                }
            }
        }
    }
<?php   }
    } else if (getenv('admin')!='') if (getenv('adminloginpage')=='') { ?>
    function login() {
        document.getElementById('mask').style.display='';
            //document.getElementById('mask').style.width=document.documentElement.scrollWidth+'px';
        document.getElementById('mask').style.height=document.documentElement.scrollHeight<window.innerHeight?window.innerHeight:document.documentElement.scrollHeight+'px';
        document.getElementById('login_div').style.display='';
        document.getElementById('login_div').style.left=(document.body.clientWidth-document.getElementById('login_div').offsetWidth)/2 +'px';
        document.getElementById('login_div').style.top=(window.innerHeight-document.getElementById('login_div').offsetHeight)/2+document.body.scrollTop +'px';
        document.getElementById('login_input').focus();
    }
<?php } ?>
</script>
<script src="//unpkg.zhimg.com/ionicons@4.4.4/dist/ionicons.js"></script>
</html>
<?php
    $html=ob_get_clean();
    if ($_SERVER['Set-Cookie']!='') return output($html, $statusCode, [ 'Set-Cookie' => $_SERVER['Set-Cookie'], 'Content-Type' => 'text/html' ]);
    return output($html,$statusCode);
}
