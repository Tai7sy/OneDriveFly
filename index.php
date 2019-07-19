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
        $path = substr($event['path'], 1+strlen($function_name));
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
    $_SERVER['PHP_SELF'] = $config['base_path'] . $path;
    $_POSTbody = explode("&",$event1['body']);
    foreach ($_POSTbody as $postvalues){
        $tmp=explode("=",$postvalues);
        $_POST[$tmp[0]]=urldecode($tmp[1]);
    }
    $cookiebody = explode(";",$event1['headers']['cookie']);
    foreach ($cookiebody as $cookievalues){
        $tmp=explode("=",$cookievalues);
        $_COOKIE[$tmp[0]]=$tmp[1];
    }

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
            $str .= 't' . $i . ':<textarea readonly style="width: 100%;height: 45px">' . substr($tmptoken,0,128) . '</textarea>';
            $tmptoken=substr($tmptoken,128);
        }
        return '<table width=100%><tr><td width=50%>refresh_token:<textarea readonly style="width: 100%;height: 500px">' . $ret['refresh_token'] . '</textarea></td><td>' . $str . '</td></tr></table>';
    }
    return '<pre>' . json_encode($ret, JSON_PRETTY_PRINT) . '</pre>';
}

function fetch_files($path = '/')
{
    global $config;
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
            $cache->save('access_token', $access_token, $ret['expires_in'] - 60);
        }

        // https://docs.microsoft.com/en-us/graph/api/driveitem-get?view=graph-rest-1.0
        // https://developer.microsoft.com/zh-cn/graph/graph-explorer

        $url = 'https://graph.microsoft.com/v1.0/me/drive/root';
        if ($path !== '/') {
                    $url .= ':' . $path;
                    if (substr($url,-1)=='/') $url=substr($url,0,-1);
                }
        $url .= '?expand=children(select=name,size,file,folder,parentReference,lastModifiedDateTime)';
        $files = json_decode(curl_request($url, false, ['Authorization' => 'Bearer ' . $access_token]), true);
        // echo $url . '<br><pre>' . json_encode($files, JSON_PRETTY_PRINT) . '</pre>';

        if (isset($files['folder']) and $files['folder']['childCount']>200 ) {
            // files num > 200 , then get nextlink
            if (isset($_POST['nextlink'])) {
                $url = $_POST['nextlink'];
            } else {
                $url = 'https://graph.microsoft.com/v1.0/me/drive/root';
                if ($path !== '/') {
                    $url .= ':' . $path;
                    if (substr($url,-1)=='/') $url=substr($url,0,-1);
                    $url .= ':/children';
                } else {
                    $url .= '/children';
                }
                #if ($config['pagesplitnum']!='') $url .= '?$top=' . $config['pagesplitnum'];
                #?$top=5&orderby=name%20DESC
            }
            $children = json_decode(curl_request($url, false, ['Authorization' => 'Bearer ' . $access_token]), true);
            $files['children'] = $children['value'];
            $files['@odata.nextLink'] = $children['@odata.nextLink'];
            //if (!isset($_POST['nextlink'])) $cache->save('path_' . $path, $files, 60);
        }
    }
    return $files;
}

function list_files($path)
{
    global $event1;
    global $config;
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

function spurlencode($str) {
    $tmparr=explode("/",$str);
    #echo count($tmparr);
    $tmp='';
    for($x=0;$x<count($tmparr);$x++) {
        $tmp .= '/' . urlencode($tmparr[$x]);
    }
    return $tmp;
}
function passhidden($path)
{
    global $config;
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
    $path = str_replace('&','&amp;', path_format(urldecode($path))) ;
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
            .list-header-container .table-header{margin:0;border:0 none;padding:30px 20px;text-align:center;font-weight:400;color:#000;background-color:#f7f7f9}
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
        <a href="<?php echo $config['base_path']; ?>"><?php echo $config['sitename'];?></a>
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
                            <th class="updated_at" width="5%">序号</th>
                            <th class="file" width="55%">文件</th>
                            <th class="updated_at" width="25%">修改时间</th>
                            <th class="size" width="15%">大小</th>
                        </tr>
                        <!-- Dirs -->
                        <?php
                        $filenum = $_POST['filenum'];
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
                                        <td class="updated_at"><?php $filenum++; echo $filenum;?></td>
                                        <td class="file">
                                            <ion-icon name="folder"></ion-icon>
                                            <a href="<?php echo path_format($config['base_path'] . '/' . $path . '/' . str_replace('&','&amp;', $file['name'])); ?>">
                                                <?php echo str_replace('&','&amp;', $file['name']); ?>
                                            </a>
                                        </td>
                                        <td class="updated_at"><?php echo ISO_format($file['lastModifiedDateTime']); ?></td>
                                        <td class="size"><?php echo size_format($file['size']); ?></td>
                                    </tr>
                                <?php }
                            }
                            foreach ($files['children'] as $file) {
                                // Files
                                if (isset($file['file'])) {
                                    if ($file['name'] !== $config['passfile'] and $file['name'] !== ".".$config['passfile'].'.swp' and $file['name'] !== ".".$config['passfile'].".swx") {
                                    if (strtolower($file['name']) === 'readme.md') $readme = $file;
                                    if (strtolower($file['name']) === 'index.html') {
                                        $html = curl_request(fetch_files(spurlencode(path_format($path . '/' .$file['name'])))['@microsoft.graph.downloadUrl']);
                                        $html .= '<!--' . urldecode(json_encode($event1)) . '-->';
                                        return output($html,200);
                                    } ?>
                                    <tr data-to>
                                        <td class="updated_at"><?php $filenum++; echo $filenum;?></td>
                                        <td class="file">
                                            <ion-icon name="document"></ion-icon>
                                            <a href="<?php echo path_format($config['base_path'] . '/' . $path . '/' . str_replace('&','&amp;', $file['name'])); ?>?preview" target=_blank>
                                                <?php echo str_replace('&','&amp;', $file['name']); ?>
                                            </a>
                                            <a href="<?php echo path_format($config['base_path'] . '/' . $path . '/' . str_replace('&','&amp;', $file['name']));?>">
                                                <ion-icon name="download"></ion-icon>
                                            </a>
                                        </td>
                                        <td class="updated_at"><?php echo ISO_format($file['lastModifiedDateTime']); ?></td>
                                        <td class="size"><?php echo size_format($file['size']); ?></td>
                                    </tr>
                                <?php }
                                }
                            }
                            if (isset($files['@odata.nextLink']) || isset($_POST['nextlink'])) {
                                $prepagenext = '<tr>
                                    <td></td>
                                    <td align=center>';
                                if (isset($_POST['nextlink'])) $prepagenext .= '<a href="javascript:history.back(-1)">上一页</a>';
                                $prepagenext .= '</td>
                                    <td></td>
                                    <td align=center>';
                                if (isset($files['@odata.nextLink'])) $prepagenext .= '
                                <form action="" method="POST" id="nextpageform">
                                    <input type="hidden" name="filenum" value="'.$filenum .'">
                                    <input type="hidden" name="nextlink" value="'.$files['@odata.nextLink'].'">
                                    <a href="javascript:void(0);" onclick="document.getElementById(\'nextpageform\').submit();">下一页</a>
                                </form>';
                                $prepagenext .= '</td></tr>';
                                echo $prepagenext;
                            }
                        } ?>
                    </table>
                    <?php
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
    <!--<?php echo urldecode(json_encode($event1));?>-->
    </html>
    <?php
    unset($files['@odata.nextLink']);
    unset($_POST);
    unset($_GET);
    unset($_COOKIE);
    $html=ob_get_clean();
    return output($html,$statusCode);
}
