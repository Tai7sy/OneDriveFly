<?php

/**
 * OneDriveFly
 * @author 风铃
 * @see https://github.com/Tai7sy/OneDriverFly
 */

global $config;
$config = [
    'name' => 'yes',
    'platform' => 'Normal',
    'multi' => 0,
    'accounts' => [
        [
            'name' => '测试',
            'path' => '',
            'path_image' => [],
            'refresh_token' => 'AQABAAAAAABeAFzDwllzTYGDLh_qYbH8RSINlnQph1lIPwBEkhcrlvtjXrSAe1duxCxPlPNzLQmAYeJu8009GDRLMLFSCRCIIHn2dW2BWL2ZzZEHAPTzZTnoSJzIfsHD3InFXF2Dcp6yacV83emsSa6sGQN8LZS80JUvZPzhj-D4oAiCJz0rZiGajfrHsfIBaT5odr0FGBTi6KeLcMR5XhRR1hYlX4Uaj-PSBHdXvcFrCwWW_EpFr6tbP8TriOxk9weAhOfVAsXWCjFr4V01q5_aQnFeA7yFL1ihSbsOzUMi8r0l_ILvNfO2StumIVCefOm6DtI_fUUwqObbuJhlQo_hKObYlM-fY_r6NUwCeMmmIInV6bK20StwIsTQvQJaUIyUl6ZOHH1gzZl2cMjt34IV_O-3Z8acwsaGTz2Ucs3vHclWe_IvXuZhlAc6hz9zQPfmajuyixCjC6--6h0dbJZSmeFM2Pw0h7QrL6DE-ElOZZTRnTT-yaj0i1M1ad_qigB5HFhddMcgfF0F25RDlP8UHgGnwxvmHj0j9hlfg96-CdOo_WTUIXKtSX0oni0kJuRHl7aTUnnggfNWb9KQfSDsL59Wa1pckqR3HK4_ElkEoulDWg8YSP0T1YEGyeHlT02HR2oNrLi8-45nmZUvca2jFScVACpfr0YF_r56UQT2QCrHcMbwXR8lmZRAEktENCJbvmy9wcMQEl8QoVTNKWjCYE7-Kma9COtKiOND9nA-awUr5oSbaxcuor1N3_P_ffAcwwoWkO1lNwE4PzIIWfY8Pt_nJSdka9P_s8e1DZ0T471zxOCTAyAA',
            'oauth' => [
                'client_id' => '298004f7-c751-4d56-aba3-b058c0154fd2',
                'client_secret' => '-^(!BpF-l9/z#[+*5t)alg;[V@;;)_];)@j#^E;T(&^4uD;*&?#2)>H?'
            ]
        ]
    ],
    'debug' => true,
    'proxy' => '127.0.0.1:10809',
    'admin_password' => '123456',
    'password_file' => '.password'
];

?>
<?php

include __DIR__ . '/vendor/autoload.php';
include __DIR__ . '/config.php';
include __DIR__ . '/library/Helper.php';

/**
 * @param \Symfony\Component\HttpFoundation\Request $request
 */
function handler($request)
{
    global $config;

    define('APP_DEBUG', $config['debug']);
    \Library\Lang::init($request->cookies->get('language'));
    date_default_timezone_set(get_timezone($request->cookies->get('timezone')));

    $path = array_filter(
        explode('/', $request->getPathInfo()),
        function ($path) {
            return !empty($path);
        }
    );

    $account = null;
    // multi-account enabled
    if ($config['multi']) {
        if (empty($path[0])) {
            $account = $config['accounts'][0];
            return redirect($request->getUriForPath('/' . $account['name']));
        } else {
            foreach ($config['accounts'] as $i) {
                if ($i['name'] === $path[0]) {
                    $account = $i;
                    break;
                }
            }
            if ($account == null) {
                return message('此账号未找到', 'Error', 500);
            }
        }
        $path = array_shift($path);
    } else {
        $account = $config['accounts'][0];
    }

    if (empty($account['path'])) {
        $account['path'] = '/';
    }

    $path = [
        'relative' => urldecode(join('/', $path)),
        'absolute' => path_format($account['path'] . '/' . join('/', $path))
    ];
    try {
        $account['driver'] = new \Library\OneDrive($account['refresh_token'], @$account['version'] ?? 'MS', @$account['oauth'] ?? []);
        $files = $account['driver']->files($path['absolute'], $request->get('page', 1));
        return render_list($account, $path, $files);
    } catch (\Throwable $e) {
        @ob_clean();
        try {
            $error = [
                'error' => [
                    'message' => $e->getMessage(),
                ]
            ];
            if ($config['debug']) {
                $error['error']['message'] = trace_error($e);
            }
            return render_list($account, $path, $error);
        } catch (\Throwable $e) {
            @ob_clean();
            if ($config['debug']) {
                return message(trace_error($e), 'Error', 500);
            }
            return message($e->getMessage(), 'Error', 500);
        }
    }
}

if (in_array(php_sapi_name(), ['apache2handler', 'cgi-fcgi'])) {
    global $config;
    $config['platform'] = \Platforms\Platform::PLATFORM_NORMAL;
    return \Platforms\Platform::response(
        handler(
            \Platforms\Platform::request()
        )
    );
}

/**
 * QCloud SCF Handler
 * @param array $event
 * @param array $context
 * @return array
 * @throws Exception
 */
function main_handler($event, $context)
{
    global $config;
    $config['platform'] = \Platforms\Platform::PLATFORM_QCLOUD_SCF;

    return \Platforms\Platform::response(
        handler(
            \Platforms\Platform::request()
        )
    );

    global $constStr;
    $event = json_decode(json_encode($event), true);
    $context = json_decode(json_encode($context), true);
    printInput($event, $context);

    unset($_POST);
    unset($_GET);
    unset($_COOKIE);
    unset($_SERVER);
    GetGlobalVariable($event);
    config_oauth();
    $path = GetPathSetting($event, $context);
    $_SERVER['refresh_token'] = getenv('t1') . getenv('t2') . getenv('t3') . getenv('t4') . getenv('t5') . getenv('t6') . getenv('t7');
    if (!$_SERVER['refresh_token']) return get_refresh_token($_SERVER['function_name'], $_SERVER['Region'], $context['namespace']);

    if (getenv('adminloginpage') == '') {
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
        if (getenv('admin') != '') {
            if ($_POST['password1'] == getenv('admin')) {
                return adminform($_SERVER['function_name'] . 'admin', md5($_POST['password1']), $url);
            } else return adminform();
        } else {
            return output('', 302, ['Location' => $url]);
        }
    }
    if (getenv('admin') != '') if ($_COOKIE[$_SERVER['function_name'] . 'admin'] == md5(getenv('admin')) || $_POST['password1'] == getenv('admin')) {
        $is_admin = 1;
    } else {
        $is_admin = 0;
    }

    if ($_GET['setup']) if ($is_admin && getenv('SecretId') != '' && getenv('SecretKey') != '') {
        // setup Environments. 设置，对环境变量操作
        return EnvOpt($_SERVER['function_name'], $_SERVER['Region'], $context['namespace'], $_SERVER['needUpdate']);
    } else {
        $url = path_format($_SERVER['PHP_SELF'] . '/');
        return output('<script>alert(\'' . trans('SetSecretsFirst') . '\');</script>', 302, ['Location' => $url]);
    }
    $_SERVER['retry'] = 0;
    return list_files($path);
}


function message($message, $title, $status = 200, $headers = [])
{
    @ob_start();
    ?>
    <!DOCTYPE html>
    <html lang="zh-CN">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title><?php echo $title; ?></title>

        <!-- Fonts -->
        <link href="https://fonts.googleapis.com/css?family=Raleway:100,600" rel="stylesheet" type="text/css">

        <!-- Styles -->
        <style type="text/css">
            html, body {
                background-color: #fff;
                color: #636b6f;
                font-family: 'Microsoft Yahei UI', 'PingFang SC', 'Raleway', sans-serif;
                font-weight: 100;
                height: 100vh;
                margin: 0
            }

            .full-height {
                height: 100vh
            }

            .flex-center {
                display: flex;
                justify-content: center
            }

            .position-ref {
                position: relative
            }

            .content {
                text-align: center;
                padding-top: 30vh
            }

            .title {
                font-size: 36px;
                padding: 20px
            }
        </style>
    </head>
    <body>
    <div class="flex-center position-ref full-height">
        <div class="content">
            <div class="title"><?php echo $message; ?></div>
        </div>
    </div>
    </body>
    </html>
    <?php
    $html = ob_get_clean();
    return response($html, $status, $headers);
}


function fetch_files($path = '/')
{
    $path1 = path_format($path);
    $path = path_format($_SERVER['list_path'] . path_format($path));
    $cache = null;
    $cache = new \Doctrine\Common\Cache\FilesystemCache(sys_get_temp_dir(), '.qdrive');
    if (!($files = $cache->fetch('path_' . $path))) {

        // https://docs.microsoft.com/en-us/graph/api/driveitem-get?view=graph-rest-1.0
        // https://docs.microsoft.com/zh-cn/graph/api/driveitem-put-content?view=graph-rest-1.0&tabs=http
        // https://developer.microsoft.com/zh-cn/graph/graph-explorer

        $url = $_SERVER['api_url'];
        if ($path !== '/') {
            $url .= ':' . $path;
            if (substr($url, -1) == '/') $url = substr($url, 0, -1);
        }
        $url .= '?expand=children(select=name,size,file,folder,parentReference,lastModifiedDateTime)';
        $files = json_decode(curl_request($url, false, ['Authorization' => 'Bearer ' . $_SERVER['access_token']]), true);
        // echo $path . '<br><pre>' . json_encode($files, JSON_PRETTY_PRINT) . '</pre>';

        if (isset($files['folder'])) {
            if ($files['folder']['childCount'] > 200) {
                // files num > 200 , then get nextlink
                $page = $_POST['pagenum'] == '' ? 1 : $_POST['pagenum'];
                $files = fetch_files_children($files, $path, $page, $cache);
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
    global $exts;
    global $constStr;
    $path = path_format($path);
    $cache = null;
    $cache = new \Doctrine\Common\Cache\FilesystemCache(sys_get_temp_dir(), '.qdrive');
    if (!($_SERVER['access_token'] = $cache->fetch('access_token'))) {
        $ret = json_decode(curl_request(
            $_SERVER['oauth_url'] . 'token',
            'client_id=' . $_SERVER['client_id'] . '&client_secret=' . $_SERVER['client_secret'] . '&grant_type=refresh_token&requested_token_use=on_behalf_of&refresh_token=' . $_SERVER['refresh_token']
        ), true);
        if (!isset($ret['access_token'])) {
            error_log('failed to get access_token. response' . json_encode($ret));
            throw new Exception('failed to get access_token.');
        }
        $_SERVER['access_token'] = $ret['access_token'];
        $cache->save('access_token', $_SERVER['access_token'], $ret['expires_in'] - 60);
    }

    if ($_SERVER['ajax']) {
        if ($_POST['action'] == 'del_upload_cache' && substr($_POST['filename'], -4) == '.tmp') {
            // del '.tmp' without login. 无需登录即可删除.tmp后缀文件
            $tmp = MSAPI('DELETE', path_format(path_format($_SERVER['list_path'] . path_format($path)) . '/' . spurlencode($_POST['filename'])), '', $_SERVER['access_token']);
            return output($tmp['body'], $tmp['stat']);
        }
        if ($_POST['action'] == 'uploaded_rename') {
            // rename .scfupload file without login.
            // 无需登录即可重命名.scfupload后缀文件，filemd5为用户提交，可被构造，问题不大，以后处理
            $oldname = spurlencode($_POST['filename']);
            $pos = strrpos($oldname, '.');
            if ($pos > 0) $ext = strtolower(substr($oldname, $pos));
            $oldname = path_format(path_format($_SERVER['list_path'] . path_format($path)) . '/' . $oldname . '.scfupload');
            $data = '{"name":"' . $_POST['filemd5'] . $ext . '"}';
            //echo $oldname .'<br>'. $data;
            $tmp = MSAPI('PATCH', $oldname, $data, $_SERVER['access_token']);
            if ($tmp['stat'] == 409) echo MSAPI('DELETE', $oldname, '', $_SERVER['access_token'])['body'];
            return output($tmp['body'], $tmp['stat']);
        }
        if ($_POST['action'] == 'upbigfile') return bigfileupload($path);
    }
    if ($is_admin) {
        $tmp = adminoperate($path);
        if ($tmp['statusCode'] > 0) {
            $path1 = path_format($_SERVER['list_path'] . path_format($path));
            $cache->save('path_' . $path1, json_decode('{}', true), 1);
            return $tmp;
        }
    } else {
        if ($_SERVER['ajax']) return output(trans('RefleshtoLogin'), 401);
    }
    $_SERVER['ishidden'] = passhidden($path);
    if ($_GET['thumbnails']) {
        if ($_SERVER['ishidden'] < 4) {
            if (in_array(strtolower(substr($path, strrpos($path, '.') + 1)), $exts['img'])) {
                return get_thumbnails_url($path);
            } else return output(json_encode($exts['img']), 400);
        } else return output('', 401);
    }
    if ($is_image_view && !$is_admin) {
        $files = json_decode('{"folder":{}}', true);
    } elseif ($_SERVER['ishidden'] == 4) {
        $files = json_decode('{"folder":{}}', true);
    } else {
        $files = fetch_files($path);
    }
    if (isset($files['file']) && !$_GET['preview']) {
        // is file && not preview mode
        if ($_SERVER['ishidden'] < 4) return output('', 302, ['Location' => $files['@microsoft.graph.downloadUrl']]);
    }
    if (isset($files['folder']) || isset($files['file'])) {
        return render_list($path, $files);
    } elseif (isset($files['error'])) {
        return output('<div style="margin: 8px; text-align: center">' . $files['error']['message'] . '</div>', 404);
    } else {
        echo 'Error $files' . json_encode($files, JSON_PRETTY_PRINT);
        $_SERVER['retry']++;
        if ($_SERVER['retry'] < 3) return list_files($path);
    }
}

function adminform($name = '', $pass = '', $path = '')
{
    global $constStr;
    $status_code = 401;
    $html = '<html><head><title>' . trans('AdminLogin') . '</title><meta charset=utf-8></head>';
    if ($name != '' && $pass != '') {
        $html .= '<body>' . trans('LoginSuccess') . '</body></html>';
        $status_code = 302;
        $header = [
            'Set-Cookie' => $name . '=' . $pass . '; path=/; expires=' . date(DATE_COOKIE, strtotime('+1hour')),
            'Location' => $path,
            'Content-Type' => 'text/html'
        ];
        return output($html, $status_code, $header);
    }
    $html .= '
    <body>
	<div>
	  <center><h4>' . trans('InputPassword') . '</h4>
	  <form action="" method="post">
		  <div>
		    <input name="password1" type="password"/>
		    <input type="submit" value="' . trans('Login') . '">
          </div>
	  </form>
      </center>
	</div>
';
    $html .= '</body></html>';
    return output($html, $status_code);
}

function bigfileupload($path)
{
    $path1 = path_format($_SERVER['list_path'] . path_format($path));
    if (substr($path1, -1) == '/') $path1 = substr($path1, 0, -1);
    if ($_POST['upbigfilename'] != '' && $_POST['filesize'] > 0) {
        $fileinfo['name'] = $_POST['upbigfilename'];
        $fileinfo['size'] = $_POST['filesize'];
        $fileinfo['lastModified'] = $_POST['lastModified'];
        $filename = spurlencode($fileinfo['name']);
        $cachefilename = '.' . $fileinfo['lastModified'] . '_' . $fileinfo['size'] . '_' . $filename . '.tmp';
        $getoldupinfo = fetch_files(path_format($path . '/' . $cachefilename));
        //echo json_encode($getoldupinfo, JSON_PRETTY_PRINT);
        if (isset($getoldupinfo['file']) && $getoldupinfo['size'] < 5120) {
            $getoldupinfo_j = curl_request($getoldupinfo['@microsoft.graph.downloadUrl']);
            $getoldupinfo = json_decode($getoldupinfo_j, true);
            if (json_decode(curl_request($getoldupinfo['uploadUrl']), true)['@odata.context'] != '') return output($getoldupinfo_j);
        }
        if (!$is_admin) $filename = spurlencode($fileinfo['name']) . '.scfupload';
        $response = MSAPI('createUploadSession', path_format($path1 . '/' . $filename), '{"item": { "@microsoft.graph.conflictBehavior": "fail"  }}', $_SERVER['access_token']);
        $responsearry = json_decode($response['body'], true);
        if (isset($responsearry['error'])) return output($response['body'], $response['stat']);
        $fileinfo['uploadUrl'] = $responsearry['uploadUrl'];
        echo MSAPI('PUT', path_format($path1 . '/' . $cachefilename), json_encode($fileinfo, JSON_PRETTY_PRINT), $_SERVER['access_token'])['body'];
        return output($response['body'], $response['stat']);
    }
    return output('error', 400);
}

function adminoperate($path)
{
    global $constStr;
    $path1 = path_format($_SERVER['list_path'] . path_format($path));
    if (substr($path1, -1) == '/') $path1 = substr($path1, 0, -1);
    $tmparr['statusCode'] = 0;

    if ($_POST['rename_newname'] != $_POST['rename_oldname'] && $_POST['rename_newname'] != '') {
        // rename 重命名
        $oldname = spurlencode($_POST['rename_oldname']);
        $oldname = path_format($path1 . '/' . $oldname);
        $data = '{"name":"' . $_POST['rename_newname'] . '"}';
        //echo $oldname;
        $result = MSAPI('PATCH', $oldname, $data, $_SERVER['access_token']);
        return output($result['body'], $result['stat']);
    }
    if ($_POST['delete_name'] != '') {
        // delete 删除
        $filename = spurlencode($_POST['delete_name']);
        $filename = path_format($path1 . '/' . $filename);
        //echo $filename;
        $result = MSAPI('DELETE', $filename, '', $_SERVER['access_token']);
        return output($result['body'], $result['stat']);
    }
    if ($_POST['operate_action'] == trans('encrypt')) {
        // encrypt 加密
        if (getenv('passfile') == '') return message(trans('SetpassfileBfEncrypt'), '', 403);
        if ($_POST['encrypt_folder'] == '/') $_POST['encrypt_folder'] == '';
        $foldername = spurlencode($_POST['encrypt_folder']);
        $filename = path_format($path1 . '/' . $foldername . '/' . getenv('passfile'));
        //echo $foldername;
        $result = MSAPI('PUT', $filename, $_POST['encrypt_newpass'], $_SERVER['access_token']);
        return output($result['body'], $result['stat']);
    }
    if ($_POST['move_folder'] != '') {
        // move 移动
        $moveable = 1;
        if ($path == '/' && $_POST['move_folder'] == '/../') $moveable = 0;
        if ($_POST['move_folder'] == $_POST['move_name']) $moveable = 0;
        if ($moveable) {
            $filename = spurlencode($_POST['move_name']);
            $filename = path_format($path1 . '/' . $filename);
            $foldername = path_format('/' . urldecode($path1) . '/' . $_POST['move_folder']);
            $data = '{"parentReference":{"path": "/drive/root:' . $foldername . '"}}';
            $result = MSAPI('PATCH', $filename, $data, $_SERVER['access_token']);
            return output($result['body'], $result['stat']);
        } else {
            return output('{"error":"Can not Move!"}', 403);
        }
    }
    if ($_POST['editfile'] != '') {
        // edit 编辑
        $data = $_POST['editfile'];
        /*TXT一般不会超过4M，不用二段上传
        $filename = $path1 . ':/createUploadSession';
        $response=MSAPI('POST',$filename,'{"item": { "@microsoft.graph.conflictBehavior": "replace"  }}',$_SERVER['access_token']);
        $uploadurl=json_decode($response,true)['uploadUrl'];
        echo MSAPI('PUT',$uploadurl,$data,$_SERVER['access_token']);*/
        $result = MSAPI('PUT', $path1, $data, $_SERVER['access_token'])['body'];
        echo $result;
        $resultarry = json_decode($result, true);
        if (isset($resultarry['error'])) return message($resultarry['error']['message'] . '<hr><a href="javascript:history.back(-1)">上一页</a>', 'Error', 403);
    }
    if ($_POST['create_name'] != '') {
        // create 新建
        if ($_POST['create_type'] == 'file') {
            $filename = spurlencode($_POST['create_name']);
            $filename = path_format($path1 . '/' . $filename);
            $result = MSAPI('PUT', $filename, $_POST['create_text'], $_SERVER['access_token']);
        }
        if ($_POST['create_type'] == 'folder') {
            $data = '{ "name": "' . $_POST['create_name'] . '",  "folder": { },  "@microsoft.graph.conflictBehavior": "rename" }';
            $result = MSAPI('children', $path1, $data, $_SERVER['access_token']);
        }
        return output($result['body'], $result['stat']);
    }
    return $tmparr;
}

function MSAPI($method, $path, $data = '', $access_token)
{
    if (substr($path, 0, 7) == 'http://' or substr($path, 0, 8) == 'https://') {
        $url = $path;
        $lenth = strlen($data);
        $headers['Content-Length'] = $lenth;
        $lenth--;
        $headers['Content-Range'] = 'bytes 0-' . $lenth . '/' . $headers['Content-Length'];
    } else {
        $url = $_SERVER['api_url'];
        if ($path == '' or $path == '/') {
            $url .= '/';
        } else {
            $url .= ':' . $path;
            if (substr($url, -1) == '/') $url = substr($url, 0, -1);
        }
        if ($method == 'PUT') {
            if ($path == '' or $path == '/') {
                $url .= 'content';
            } else {
                $url .= ':/content';
            }
            $headers['Content-Type'] = 'text/plain';
        } elseif ($method == 'PATCH') {
            $headers['Content-Type'] = 'application/json';
        } elseif ($method == 'POST') {
            $headers['Content-Type'] = 'application/json';
        } elseif ($method == 'DELETE') {
            $headers['Content-Type'] = 'application/json';
        } else {
            if ($path == '' or $path == '/') {
                $url .= $method;
            } else {
                $url .= ':/' . $method;
            }
            $method = 'POST';
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
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $sendHeaders);
    $response['body'] = curl_exec($ch);
    $response['stat'] = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    echo $response['stat'] . '
';
    return $response;
}

function get_thumbnails_url($path = '/')
{
    $path1 = path_format($path);
    $path = path_format($_SERVER['list_path'] . path_format($path));
    $url = $_SERVER['api_url'];
    if ($path !== '/') {
        $url .= ':' . $path;
        if (substr($url, -1) == '/') $url = substr($url, 0, -1);
    }
    $url .= ':/thumbnails/0/medium';
    $files = json_decode(curl_request($url, false, ['Authorization' => 'Bearer ' . $_SERVER['access_token']]), true);
    if (isset($files['url'])) return output($files['url']);
    return output('', 404);
}

function EnvOpt($function_name, $Region, $namespace = 'default', $needUpdate = 0)
{
    global $constStr;
    $constEnv = [
        //'admin',
        'adminloginpage', 'domain_path', 'imgup_path', 'passfile', 'private_path', 'public_path', 'sitename', 'language'
    ];
    asort($constEnv);
    $html = '<title>SCF ' . trans('Setup') . '</title>';
    if ($_POST['updateProgram'] == trans('updateProgram')) {
        $response = json_decode(updataProgram($function_name, $Region, $namespace), true)['Response'];
        if (isset($response['Error'])) {
            $html = $response['Error']['Code'] . '<br>
' . $response['Error']['Message'] . '<br><br>
function_name:' . $_SERVER['function_name'] . '<br>
Region:' . $_SERVER['Region'] . '<br>
namespace:' . $namespace . '<br>
<button onclick="location.href = location.href;">' . trans('Reflesh') . '</button>';
            $title = 'Error';
        } else {
            $html .= trans('UpdateSuccess') . '<br>
<button onclick="location.href = location.href;">' . trans('Reflesh') . '</button>';
            $title = trans('Setup');
        }
        return message($html, $title);
    }
    if ($_POST['submit1']) {
        foreach ($_POST as $k => $v) {
            if (in_array($k, $constEnv)) {
                $tmp[$k] = $v;
            }
        }
        echo updataEnvironment($tmp, $function_name, $Region, $namespace);
        $html .= '<script>location.href=location.href</script>';
    }
    if ($_GET['preview']) {
        $preurl = $_SERVER['PHP_SELF'] . '?preview';
    } else {
        $preurl = path_format($_SERVER['PHP_SELF'] . '/');
    }
    $html .= '
        <a href="' . $preurl . '">' . trans('Back') . '</a>&nbsp;&nbsp;&nbsp;
        <a href="https://github.com/qkqpttgf/OneDrive_SCF">Github</a><br>';
    if ($needUpdate) {
        $html .= '<pre>' . $_SERVER['github_version'] . '</pre>
        <form action="" method="post">
            <input type="submit" name="updateProgram" value="' . trans('updateProgram') . '">
        </form>';
    } else {
        $html .= trans('NotNeedUpdate');
    }
    $html .= '
    <form action="" method="post">
    <table border=1 width=100%>';
    foreach ($constEnv as $key) {
        if ($key == 'language') {
            $html .= '
        <tr>
            <td><label>' . $key . '</label></td>
            <td width=100%>
                <select name="' . $key . '">';
            foreach (\Library\Lang::all()['languages'] as $key1 => $value1) {
                $html .= '
                    <option value="' . $key1 . '" ' . ($key1 == getenv($key) ? 'selected="selected"' : '') . '>' . $value1 . '</option>';
            }
            $html .= '
                </select>
            </td>
        </tr>';
        } else $html .= '
        <tr>
            <td><label>' . $key . '</label></td>
            <td width=100%><input type="text" name="' . $key . '" value="' . getenv($key) . '" placeholder="' . trans('EnvironmentsDescription.' . $key) . '" style="width:100%"></td>
        </tr>';
    }
    $html .= '</table>
    <input type="submit" name="submit1" value="' . trans('Setup') . '">
    </form>';
    return message($html, trans('Setup'));
}

/**
 * render view
 * @param array $account
 * @param array $path
 * @param array $files
 * @return array|\Symfony\Component\HttpFoundation\Response
 * @throws Exception
 */
function render_list($account, $path, $files)
{
    global $config;

    $request = request();
    $title = $path['relative'];
    $is_image_view = in_array($path['relative'], $account['path_image']);
    $is_admin = $request->cookies->get('admin_password') === $config['admin_password'];
    $base_url = $request->getBaseUrl();
    $status_code = 200;
    @ob_start();
    ?>
    <!DOCTYPE html>
    <html lang="<?php echo \Library\Lang::language(); ?>">
    <head>
        <title><?php echo $title; ?> - <?php echo $config['name']; ?></title>
        <!--
        https://github.com/Tai7sy/OneDriveFly
        -->
        <meta charset=utf-8>
        <meta http-equiv=X-UA-Compatible content="IE=edge">
        <meta name=viewport content="width=device-width,initial-scale=1">
        <meta name="keywords" content="<?php
        echo htmlspecialchars(str_replace('/', ',', $path['relative']) . ',' . $config['name']);
        ?>,OneDrive_SCF,OneDriveFly">
        <link rel="icon" href="<?php echo $base_url ?>/favicon.ico" type="image/x-icon"/>
        <link rel="shortcut icon" href="<?php echo $base_url ?>/favicon.ico" type="image/x-icon"/>
        <style type="text/css">
            body {
                font-family: 'Microsoft Yahei UI', 'PingFang TC', 'Helvetica Neue', Helvetica, Arial, sans-serif;
                font-size: 14px;
                line-height: 1em;
                background-color: #f7f7f9;
                color: #000
            }

            a {
                color: #24292e;
                cursor: pointer;
                text-decoration: none
            }

            a:hover {
                color: #24292e
            }

            .select-language {
                position: absolute;
                right: 5px;
            }

            .title {
                text-align: center;
                margin-top: 1rem;
                letter-spacing: 2px;
                margin-bottom: 2rem
            }

            .title a {
                color: #333;
                text-decoration: none
            }

            .list-wrapper {
                width: 80%;
                margin: 0 auto 40px;
                position: relative;
                box-shadow: 0 0 32px 0 rgb(128, 128, 128);
                border-radius: 15px;
            }

            .list-container {
                position: relative;
                overflow: hidden;
                border-radius: 15px;
            }

            .list-header-container {
                position: relative
            }

            .list-header-container a.back-link {
                color: #000;
                display: inline-block;
                position: absolute;
                font-size: 16px;
                margin: 20px 10px;
                padding: 10px 10px;
                vertical-align: middle;
                text-decoration: none
            }

            .list-container, .list-header-container, .list-wrapper, a.back-link:hover, body {
                color: #24292e
            }

            .list-header-container .table-header {
                margin: 0;
                border: 0 none;
                padding: 30px 60px;
                text-align: left;
                font-weight: 400;
                color: #000;
                background-color: #f7f7f9
            }

            .list-body-container {
                position: relative;
                left: 0;
                overflow-x: hidden;
                overflow-y: auto;
                box-sizing: border-box;
                background: #fff
            }

            .list-table {
                width: 100%;
                padding: 20px;
                border-spacing: 0
            }

            .list-table tr {
                height: 40px
            }

            .list-table tr[data-to]:hover {
                background: #f1f1f1
            }

            .list-table tr:first-child {
                background: #fff
            }

            .list-table td, .list-table th {
                padding: 0 10px;
                text-align: left
            }

            .list-table .size, .list-table .updated_at {
                text-align: right
            }

            .list-table .file ion-icon {
                font-size: 15px;
                margin-right: 5px;
                vertical-align: bottom
            }

            .mask {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
                background-color: #000;
                filter: alpha(opacity=50);
                opacity: 0.5;
                z-index: 2;
            }

            <?php if ($is_admin) { ?>
            .operate {
                display: inline-table;
                margin: 0;
                list-style: none;
            }

            .operate ul {
                position: absolute;
                display: none;
                background: #fffaaa;
                border: 0px #f7f7f7 solid;
                border-radius: 5px;
                margin: -7px 0 0 0;
                padding: 0 7px;
                color: #205D67;
                z-index: 1;
            }

            .operate:hover ul {
                position: absolute;
                display: inline-table;
            }

            .operate ul li {
                padding: 7px;
                list-style: none;
                display: inline-table;
            }

            <?php } ?>
            .operatediv {
                position: absolute;
                border: 1px #CCCCCC;
                background-color: #FFFFCC;
                z-index: 2;
            }

            .operatediv div {
                margin: 16px
            }

            .operatediv_close {
                position: absolute;
                right: 3px;
                top: 3px;
            }

            .readme {
                padding: 8px;
                background-color: #fff;
            }

            #readme {
                padding: 20px;
                text-align: left
            }

            @media only screen and (max-width: 480px) {
                .title {
                    margin-bottom: 24px
                }

                .list-wrapper {
                    width: 95%;
                    margin-bottom: 24px;
                }

                .list-table {
                    padding: 8px
                }

                .list-table td, .list-table th {
                    padding: 0 10px;
                    text-align: left;
                    white-space: nowrap;
                    overflow: auto;
                    max-width: 80px
                }
            }
        </style>
        <script type="text/javascript">
            function setCookie(name, value, expire) {
                if (!name || !value) return;
                if (expire !== undefined) {
                    var expTime = new Date();
                    expTime.setTime(expTime.getTime() + expire);
                    document.cookie = name + '=' + encodeURI(value) + '; expires=' + expTime.toUTCString() + '; path=/'
                } else {
                    document.cookie = name + '=' + encodeURI(value) + '; path=/'
                }
            }

            function getCookie(name) {
                var parts = ('; ' + document.cookie).split('; ' + name + '=');
                if (parts.length >= 2) return parts.pop().split(';').shift();
            }

            (function timezone() {
                if (!getCookie('timezone')) {
                    var now = new Date();
                    var timezone = parseInt(0 - now.getTimezoneOffset() / 60);
                    setCookie('timezone', timezone, 7 * 24 * 3600 * 1000); // 7 days
                    if (timezone !== 8) {
                        alert('Your timezone is ' + timezone + ', reload local timezone.');
                        location.reload();
                    }
                }
            })();
        </script>
    </head>

    <body>
    <?php
    if (getenv('admin') != '') {
        if (!$is_admin) {
            if (getenv('adminloginpage') == '') { ?>
                <a onclick="login();"><?php echo trans('Login'); ?></a>
            <?php }
        } else { ?>
            <li class="operate"><?php echo trans('Operate'); ?>
                <ul>
                    <?php if (isset($files['folder'])) { ?>
                        <li>
                            <a onclick="showdiv(event,'create','');"><?php echo trans('Create'); ?></a>
                        </li>
                        <li>
                            <a onclick="showdiv(event,'encrypt','');"><?php echo trans('encrypt'); ?></a>
                        </li>
                    <?php } ?>
                    <li>
                        <a <?php
                           if (getenv('SecretId') != '' && getenv('SecretKey') != '')
                           {
                           ?>href="<?php echo request()->query->has('preview') ? '?preview&' : '?'; ?>setup"
                           <?php
                           } else {
                           ?>onclick="alert('<?php echo trans('SetSecretsFirst'); ?>');"
                            <?php
                            }
                            ?>
                        >
                            <?php echo trans('Setup'); ?>
                        </a>
                    </li>
                    <li><a onclick="logout()"><?php echo trans('Logout'); ?></a></li>
                </ul>
            </li>
            <?php
        }
    }
    ?>
    <select class="select-language" name="language" onchange="changeLanguage(this.value)">
        <option value="-1">Language</option>
        <?php
        foreach (\Library\Lang::all()['languages'] as $key1 => $value1) {
            echo '<option value="' . $key1 . '" ' . (\Library\Lang::language() === $key1 ? 'selected="true"' : '') . '">' . $value1 . '</option>';
        }
        ?>
    </select>
    <!-- update -->
    <div style='position:absolute; display: none'><span style="color: red"><?php echo trans('NeedUpdate'); ?></span>
    </div>
    <h1 class="title">
        <a href="<?php echo $base_url; ?>"><?php echo $config['name']; ?></a>
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
                    <a href="<?php echo $parent_url . '/'; ?>" class="back-link">
                        <ion-icon name="arrow-back"></ion-icon>
                    </a>
                <?php } ?>
                <h3 class="table-header"><?php echo htmlspecialchars($path['relative']); ?></h3>
            </div>
            <div class="list-body-container">
                <?php
                if ($is_image_view && !$is_admin) { ?>
                    <div id="upload_div" style="margin:10px">
                        <center>
                            <input id="upload_file" type="file" name="upload_filename">
                            <input id="upload_submit" onclick="preup();"
                                   value="<?php echo trans('Upload'); ?>" type="button">
                            <center>
                    </div>
                <?php } else {
                    $folder_password = false;
                    if (!empty($config['password_file']) && !empty($account['driver'])) {
                        $folder_password = $account['driver']->getContent($path['absolute'] . '/' . $config['password_file']);
                    }
                    if (empty($folder_password) || $folder_password === request()->query->get('password')) {
                        if (isset($files['error'])) {
                            echo '<div style="margin: 8px;">' . $files['error']['message'] . '</div>';
                            $status_code = 404;
                        } else {
                            if (isset($files['file'])) {
                                ?>
                                <div style="margin: 12px 4px 4px; text-align: center">
                                    <div style="margin: 24px">
                                        <textarea id="url" title="url" rows="1" style="width: 100%; margin-top: 2px;"
                                                  readonly><?php echo str_replace('%2523', '%23', str_replace('%26amp%3B', '&amp;', spurlencode(path_format($base_url . '/' . $path), '/'))); ?></textarea>
                                        <a href="<?php echo path_format($base_url . '/' . $path);//$files['@microsoft.graph.downloadUrl'] ?>">
                                            <ion-icon name="download"
                                                      style="line-height: 16px;vertical-align: middle;"></ion-icon>&nbsp;<?php echo trans('Download'); ?>
                                        </a>
                                    </div>
                                    <div style="margin: 24px">
                                        <?php $ext = strtolower(substr($path, strrpos($path, '.') + 1));
                                        $DPvideo = '';
                                        if (in_array($ext, \Library\Ext::IMG)) {
                                            echo '
                        <img src="' . $files['@microsoft.graph.downloadUrl'] . '" alt="' . substr($path, strrpos($path, '/')) . '" onload="if(this.offsetWidth>document.getElementById(\'url\').offsetWidth) this.style.width=\'100%\';" />
';
                                        } elseif (in_array($ext, \Library\Ext::VIDEO)) {
                                            //echo '<video src="' . $files['@microsoft.graph.downloadUrl'] . '" controls="controls" style="width: 100%"></video>';
                                            $DPvideo = $files['@microsoft.graph.downloadUrl'];
                                            echo '<div id="video-a0"></div>';
                                        } elseif (in_array($ext, \Library\Ext::MUSIC)) {
                                            echo '
                        <audio src="' . $files['@microsoft.graph.downloadUrl'] . '" controls="controls" style="width: 100%"></audio>
';
                                        } elseif (in_array($ext, ['pdf'])) {
                                            echo '
                        <embed src="' . $files['@microsoft.graph.downloadUrl'] . '" type="application/pdf" width="100%" height=800px">
';
                                        } elseif (in_array($ext, \Library\Ext::OFFICE)) {
                                            echo '
                        <iframe id="office-a" src="https://view.officeapps.live.com/op/view.aspx?src=' . urlencode($files['@microsoft.graph.downloadUrl']) . '" style="width: 100%;height: 800px" frameborder="0"></iframe>
';
                                        } elseif (in_array($ext, \Library\Ext::TXT)) {
                                            $txt_content = htmlspecialchars(curl_request($files['@microsoft.graph.downloadUrl']));
                                            ?>
                                            <div id="txt">
                                                <?php if ($is_admin) { ?>
                                                <form id="txt-form" action="" method="POST">
                                                    <a onclick="enableedit(this);"
                                                       id="txt-editbutton"><?php echo trans('ClicktoEdit'); ?></a>
                                                    <a id="txt-save"
                                                       style="display:none"><?php echo trans('Save'); ?></a>
                                                    <?php } ?>
                                                    <textarea id="txt-a" name="editfile" readonly
                                                              style="width: 100%; margin-top: 2px;" <?php if ($is_admin) echo 'onchange="document.getElementById(\'txt-save\').onclick=function(){document.getElementById(\'txt-form\').submit();}"'; ?> ><?php echo $txt_content; ?></textarea>
                                                    <?php if ($is_admin) echo '</form>'; ?>
                                            </div>
                                        <?php } elseif (in_array($ext, ['md'])) {
                                            echo '
                        <div class="markdown-body" id="readme">
                            <textarea id="readme-md" style="display:none;">' . curl_request($files['@microsoft.graph.downloadUrl']) . '</textarea>
                        </div>
';
                                        } else {
                                            echo '<span>' . trans('FileNotSupport') . '</span>';
                                        } ?>
                                    </div>
                                </div>
                            <?php } elseif (isset($files['folder'])) {
                                $filenum = $files['folder']['childCount'];
                                if (!$filenum and $files['folder']['page']) $filenum = ($files['folder']['page'] - 1) * 200;
                                $readme = false; ?>
                                <table class="list-table" id="list-table">
                                    <tr id="tr0">
                                        <th class="file"
                                            onclick="sortby('a');"><?php echo trans('File'); ?>
                                            &nbsp;&nbsp;&nbsp;<button
                                                    onclick="showthumbnails(this);"><?php echo trans('ShowThumbnails'); ?></button>
                                        </th>
                                        <th class="updated_at" width="25%"
                                            onclick="sortby('time');"><?php echo trans('EditTime'); ?></th>
                                        <th class="size" width="15%"
                                            onclick="sortby('size');"><?php echo trans('Size'); ?></th>
                                    </tr>
                                    <!-- Dirs -->
                                    <?php //echo json_encode($files['children'], JSON_PRETTY_PRINT);
                                    foreach ($files['children'] as $file) {
                                        // Folders
                                        if (isset($file['folder'])) {
                                            $filenum++; ?>
                                            <tr data-to id="tr<?php echo $filenum; ?>">
                                                <td class="file">
                                                    <?php if ($is_admin) { ?>
                                                        <li class="operate"><?php echo trans('Operate'); ?>
                                                            <ul>
                                                                <li>
                                                                    <a onclick="showdiv(event,'encrypt',<?php echo $filenum; ?>);"><?php echo trans('encrypt'); ?></a>
                                                                </li>
                                                                <li>
                                                                    <a onclick="showdiv(event, 'rename',<?php echo $filenum; ?>);"><?php echo trans('Rename'); ?></a>
                                                                </li>
                                                                <li>
                                                                    <a onclick="showdiv(event, 'move',<?php echo $filenum; ?>);"><?php echo trans('Move'); ?></a>
                                                                </li>
                                                                <li>
                                                                    <a onclick="showdiv(event, 'delete',<?php echo $filenum; ?>);"><?php echo trans('Delete'); ?></a>
                                                                </li>
                                                            </ul>
                                                        </li>&nbsp;&nbsp;&nbsp;
                                                    <?php } ?>
                                                    <ion-icon name="folder"></ion-icon>
                                                    <a id="file_a<?php echo $filenum; ?>"
                                                       href="<?php echo path_format($base_url . '/' . $path . '/' . encode_str_replace($file['name']) . '/'); ?>"><?php echo str_replace('&', '&amp;', $file['name']); ?></a>
                                                </td>
                                                <td class="updated_at"
                                                    id="folder_time<?php echo $filenum; ?>"><?php echo time_format($file['lastModifiedDateTime']); ?></td>
                                                <td class="size"
                                                    id="folder_size<?php echo $filenum; ?>"><?php echo size_format($file['size']); ?></td>
                                            </tr>
                                        <?php }
                                    }
                                    // if ($filenum) echo '<tr data-to></tr>';
                                    foreach ($files['children'] as $file) {
                                        // Files
                                        if (isset($file['file'])) {
                                            if ($is_admin or (substr($file['name'], 0, 1) !== '.' and $file['name'] !== getenv('passfile'))) {
                                                if (strtolower($file['name']) === 'readme.md' || strtolower($file['name']) === 'readme') {
                                                    $readme = $file;
                                                }
                                                if (strtolower($file['name']) === 'index.html' || strtolower($file['name']) === 'index.htm') {
                                                    $html = curl_request(fetch_files(spurlencode(path_format($path . '/' . $file['name']), '/'))['@microsoft.graph.downloadUrl']);
                                                    return output($html, 200);
                                                }
                                                $filenum++; ?>
                                                <tr data-to id="tr<?php echo $filenum; ?>">
                                                    <td class="file">
                                                        <?php if ($is_admin) { ?>
                                                            <li class="operate"><?php echo trans('Operate'); ?>
                                                                <ul>
                                                                    <li>
                                                                        <a onclick="showdiv(event, 'rename',<?php echo $filenum; ?>);"><?php echo trans('Rename'); ?></a>
                                                                    </li>
                                                                    <li>
                                                                        <a onclick="showdiv(event, 'move',<?php echo $filenum; ?>);"><?php echo trans('Move'); ?></a>
                                                                    </li>
                                                                    <li>
                                                                        <a onclick="showdiv(event, 'delete',<?php echo $filenum; ?>);"><?php echo trans('Delete'); ?></a>
                                                                    </li>
                                                                </ul>
                                                            </li>&nbsp;&nbsp;&nbsp;
                                                        <?php }
                                                        $ext = strtolower(substr($file['name'], strrpos($file['name'], '.') + 1));
                                                        if (in_array($ext, \Library\Ext::MUSIC)) { ?>
                                                            <ion-icon name="musical-notes"></ion-icon>
                                                        <?php } elseif (in_array($ext, \Library\Ext::VIDEO)) { ?>
                                                            <ion-icon name="logo-youtube"></ion-icon>
                                                        <?php } elseif (in_array($ext, \Library\Ext::IMG)) { ?>
                                                            <ion-icon name="image"></ion-icon>
                                                        <?php } elseif (in_array($ext, \Library\Ext::OFFICE)) { ?>
                                                            <ion-icon name="paper"></ion-icon>
                                                        <?php } elseif (in_array($ext, \Library\Ext::TXT)) { ?>
                                                            <ion-icon name="clipboard"></ion-icon>
                                                        <?php } elseif (in_array($ext, \Library\Ext::ZIP)) { ?>
                                                            <ion-icon name="filing"></ion-icon>
                                                        <?php } elseif ($ext == 'iso') { ?>
                                                            <ion-icon name="disc"></ion-icon>
                                                        <?php } elseif ($ext == 'apk') { ?>
                                                            <ion-icon name="logo-android"></ion-icon>
                                                        <?php } elseif ($ext == 'exe') { ?>
                                                            <ion-icon name="logo-windows"></ion-icon>
                                                        <?php } else { ?>
                                                            <ion-icon name="document"></ion-icon>
                                                        <?php } ?>
                                                        <a id="file_a<?php echo $filenum; ?>" name="filelist"
                                                           href="<?php echo htmlspecialchars($path['relative'] . '/' . $file['name']); ?>?preview"
                                                           target=_blank><?php echo htmlspecialchars(urldecode($file['name'])); ?></a>
                                                        <a href="<?php echo htmlspecialchars($path['relative'] . '/' . $file['name']); ?>">
                                                            <ion-icon name="download"></ion-icon>
                                                        </a>
                                                    </td>
                                                    <td class="updated_at"
                                                        id="file_time<?php echo $filenum; ?>"><?php echo time_format($file['lastModifiedDateTime']); ?></td>
                                                    <td class="size"
                                                        id="file_size<?php echo $filenum; ?>"><?php echo size_format($file['size']); ?></td>
                                                </tr>
                                            <?php }
                                        }
                                    } ?>
                                </table>
                                <?php if ($files['folder']['childCount'] > $files['folder']['perPage']) {
                                    $pageForm = '
                <form action="" method="GET" id="pageForm">
                    <input type="hidden" id="page" name="page" value="' . $files['folder']['currentPage'] . '">
                    <table style="width: 100%; border: none">
                        <tr>
                            <td style="width: 60px; text-align: center">';
                                    if ($files['folder']['currentPage'] !== 1) {
                                        $pageForm .= '
                                <a onclick="goPage(' . ($files['folder']['currentPage'] - 1) . ');">' . trans('PrePage') . '</a>';
                                    }
                                    $pageForm .= '
                            </td>
                            <td style="color: #888">';
                                    for ($page = 1; $page <= $files['folder']['lastPage']; $page++) {
                                        if ($page == $files['folder']['currentPage']) {
                                            $pageForm .= '
                                <span style="color: red">' . $page . '</span>';
                                        } else {
                                            $pageForm .= '
                                <a onclick="goPage(' . $page . ');">' . $page . '</a>';
                                        }
                                    }
                                    $pageForm .= '
                            </td>
                            <td style="width: 60px; text-align: center">';
                                    if ($files['folder']['currentPage'] != $files['folder']['lastPage']) {
                                        $pageForm .= '
                                <a onclick="goPage(' . ($files['folder']['lastPage'] + 1) . ');">' . trans('NextPage') . '</a>';
                                    }
                                    $pageForm .= '
                            </td>
                        </tr>
                    </table>
                </form>';
                                    echo $pageForm;
                                }
                                if ($is_admin) { ?>
                                    <div id="upload_div" style="margin:0 0 16px 0">
                                        <center>
                                            <input id="upload_file" type="file" name="upload_filename"
                                                   multiple="multiple">
                                            <input id="upload_submit" onclick="preup();"
                                                   value="<?php echo trans('Upload'); ?>"
                                                   type="button">
                                        </center>
                                    </div>
                                <?php }
                            } else {
                                $status_code = 500;
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
                    <span style="line-height: 16px;vertical-align: top;">' . $readme['name'] . '</span>
                    <div class="markdown-body" id="readme">
                        <textarea id="readme-md" style="display:none;">' .
                                    $account['driver']->getContent($path['absolute'] . '/' . $readme['name']) . '
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
	                <form action="" method="post">
		            <input name="password1" type="password" placeholder="' . trans('InputPassword') . '">
		            <input type="submit" value="' . trans('Submit') . '">
	                </form>
                </center>
                </div>';
                        $status_code = 401;
                    }
                } ?>
            </div>
        </div>
    </div>
    <div id="mask" class="mask" style="display:none;"></div>
    <?php
    if ($is_admin) {
        if (!$_GET['preview']) { ?>
            <div>
                <div id="rename_div" class="operatediv" style="display:none">
                    <div>
                        <label id="rename_label"></label><br><br><a onclick="operatediv_close('rename')"
                                                                    class="operatediv_close"><?php echo trans('Close'); ?></a>
                        <form id="rename_form" onsubmit="return submit_operate('rename');">
                            <input id="rename_sid" name="rename_sid" type="hidden" value="">
                            <input id="rename_hidden" name="rename_oldname" type="hidden" value="">
                            <input id="rename_input" name="rename_newname" type="text" value="">
                            <input name="operate_action" type="submit"
                                   value="<?php echo trans('Rename'); ?>">
                        </form>
                    </div>
                </div>
                <div id="delete_div" class="operatediv" style="display:none">
                    <div>
                        <br><a onclick="operatediv_close('delete')"
                               class="operatediv_close"><?php echo trans('Close'); ?></a>
                        <label id="delete_label"></label>
                        <form id="delete_form" onsubmit="return submit_operate('delete');">
                            <label id="delete_input"><?php echo trans('Delete'); ?>?</label>
                            <input id="delete_sid" name="delete_sid" type="hidden" value="">
                            <input id="delete_hidden" name="delete_name" type="hidden" value="">
                            <input name="operate_action" type="submit"
                                   value="<?php echo trans('Submit'); ?>">
                        </form>
                    </div>
                </div>
                <div id="encrypt_div" class="operatediv" style="display:none">
                    <div>
                        <label id="encrypt_label"></label><br><br><a onclick="operatediv_close('encrypt')"
                                                                     class="operatediv_close"><?php echo trans('Close'); ?></a>
                        <form id="encrypt_form" onsubmit="return submit_operate('encrypt');">
                            <input id="encrypt_sid" name="encrypt_sid" type="hidden" value="">
                            <input id="encrypt_hidden" name="encrypt_folder" type="hidden" value="">
                            <input id="encrypt_input" name="encrypt_newpass" type="text" value=""
                                   placeholder="<?php echo trans('InputPasswordUWant'); ?>">
                            <?php if (getenv('passfile') != '') { ?><input name="operate_action" type="submit"
                                                                           value="<?php echo trans('encrypt'); ?>"><?php } else { ?>
                                <br>
                                <label><?php echo trans('SetpassfileBfEncrypt'); ?></label><?php } ?>
                        </form>
                    </div>
                </div>
                <div id="move_div" class="operatediv" style="display:none">
                    <div>
                        <label id="move_label"></label><br><br><a onclick="operatediv_close('move')"
                                                                  class="operatediv_close"><?php echo trans('Close'); ?></a>
                        <form id="move_form" onsubmit="return submit_operate('move');">
                            <input id="move_sid" name="move_sid" type="hidden" value="">
                            <input id="move_hidden" name="move_name" type="hidden" value="">
                            <select id="move_input" name="move_folder">
                                <?php if ($path != '/') { ?>
                                    <option value="/../"><?php echo trans('ParentDir'); ?></option>
                                <?php }
                                if (isset($files['children'])) foreach ($files['children'] as $file) {
                                    if (isset($file['folder'])) { ?>
                                        <option value="<?php echo str_replace('&', '&amp;', $file['name']); ?>"><?php echo str_replace('&', '&amp;', $file['name']); ?></option>
                                    <?php }
                                } ?>
                            </select>
                            <input name="operate_action" type="submit"
                                   value="<?php echo trans('Move'); ?>">
                        </form>
                    </div>
                </div>
                <div id="create_div" class="operatediv" style="display:none">
                    <div>
                        <a onclick="operatediv_close('create')"
                           class="operatediv_close"><?php echo trans('Close'); ?></a>
                        <form id="create_form" onsubmit="return submit_operate('create');">
                            <input id="create_sid" name="create_sid" type="hidden" value="">
                            <input id="create_hidden" type="hidden" value="">
                            <table>
                                <tr>
                                    <td></td>
                                    <td><label id="create_label"></label></td>
                                </tr>
                                <tr>
                                    <td>　　　</td>
                                    <td>
                                        <label><input id="create_type_folder" name="create_type" type="radio"
                                                      value="folder"
                                                      onclick="document.getElementById('create_text_div').style.display='none';"><?php echo trans('Folder'); ?>
                                        </label>
                                        <label><input id="create_type_file" name="create_type" type="radio" value="file"
                                                      onclick="document.getElementById('create_text_div').style.display='';"
                                                      checked><?php echo trans('File'); ?>
                                        </label>
                                    <td>
                                </tr>
                                <tr>
                                    <td><?php echo trans('Name'); ?>：</td>
                                    <td><input id="create_input" name="create_name" type="text" value=""></td>
                                </tr>
                                <tr id="create_text_div">
                                    <td><?php echo trans('Content'); ?>：</td>
                                    <td><textarea id="create_text" name="create_text" rows="6" cols="40"></textarea>
                                    </td>
                                </tr>
                                <tr>
                                    <td>　　　</td>
                                    <td><input name="operate_action" type="submit"
                                               value="<?php echo trans('Create'); ?>"></td>
                                </tr>
                            </table>
                        </form>
                    </div>
                </div>
            </div>
        <?php }
    } else {
        if (getenv('admin') != '') if (getenv('adminloginpage') == '') { ?>
            <div id="login_div" class="operatediv" style="display:none">
                <div style="margin:50px">
                    <a onclick="operatediv_close('login')"
                       class="operatediv_close"><?php echo trans('Close'); ?></a>
                    <center>
                        <form action="<?php echo $_GET['preview'] ? '?preview&' : '?'; ?>admin" method="post">
                            <input id="login_input" name="password1" type="password"
                                   placeholder="<?php echo trans('InputPassword'); ?>">
                            <input type="submit" value="<?php echo trans('Login'); ?>">
                        </form>
                    </center>
                </div>
            </div>
        <?php }
    } ?>
    <font color="#f7f7f9"><?php echo date("Y-m-d H:i:s") . " " . trans('Week.' . date('w')) . ' ' . $_SERVER['REMOTE_ADDR']; ?></font>
    </body>

    <link rel="stylesheet" href="//unpkg.zhimg.com/github-markdown-css@3.0.1/github-markdown.css">
    <script type="text/javascript" src="//unpkg.zhimg.com/marked@0.6.2/marked.min.js"></script>
    <?php if (isset($files['folder']) && $is_image_view && !$is_admin) { ?>
        <script type="text/javascript" src="//cdn.bootcss.com/spark-md5/3.0.0/spark-md5.min.js"></script>
    <?php } ?>
    <script type="text/javascript">
        var root = '/';

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

        function goPage(num) {
            document.getElementById('page').value = num;
            document.getElementById('pageForm').submit();
        }

        function changeLanguage(lang) {
            setCookie('language', lang, 7 * 24 * 3600 * 1000);
            location.reload();
        }

        var $readme = document.getElementById('readme');
        if ($readme) {
            $readme.innerHTML = marked(document.getElementById('readme-md').innerText)
        }



        <?php
        if ($request->query->has('preview')) { //is preview mode. 在预览时处理
        ?>
        var $url = document.getElementById('url');
        if ($url) {
            $url.innerHTML = location.protocol + '//' + location.host + $url.innerHTML;
            $url.style.height = $url.scrollHeight + 'px';
        }
        var $officearea = document.getElementById('office-a');
        if ($officearea) {
            $officearea.style.height = window.innerHeight + 'px';
        }
        var $textarea = document.getElementById('txt-a');
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
            var url = '<?php echo str_replace('%2523', '%23', str_replace('%26amp%3B', '&amp;', spurlencode(path_format($base_url . '/' . $path), '/'))); ?>',
                subtitle = url.replace(/\.[^\.]+?(\?|$)/, '.vtt$1');
            var dp = new DPlayer({
                container: document.getElementById('video-a0'),
                autoplay: false,
                screenshot: true,
                hotkey: true,
                volume: 1,
                preload: 'auto',
                mutex: true,
                video: {
                    url: url,
                },
                subtitle: {
                    url: subtitle,
                    fontSize: '25px',
                    bottom: '7%',
                },
            });
            // 防止出现401 token过期
            dp.on('error', function () {
                console.log('获取资源错误，开始重新加载！');
                let last = dp.video.currentTime;
                dp.video.src = url;
                dp.video.load();
                dp.video.currentTime = last;
                dp.play();
            });
            // 如果是播放状态 & 没有播放完 每25分钟重载视频防止卡死
            setInterval(function () {
                if (!dp.video.paused && !dp.video.ended) {
                    console.log('开始重新加载！');
                    let last = dp.video.currentTime;
                    dp.video.src = url;
                    dp.video.load();
                    dp.video.currentTime = last;
                    dp.play();
                }
            }, 1000 * 60 * 25)
        }

        addVideos(['<?php echo $DPvideo;?>']);


        <?php
        }
        }
        else
        { // view folder. 不预览，即浏览目录时?>
        var sort = 0;

        function showthumbnails(obj) {
            var files = document.getElementsByName('filelist');
            for ($i = 0; $i < files.length; $i++) {
                str = files[$i].innerText;
                if (str.substr(-1) == ' ') str = str.substr(0, str.length - 1);
                if (!str) return;
                strarry = str.split('.');
                ext = strarry[strarry.length - 1].toLowerCase();
                images = [<?php foreach (\Library\Ext::IMG as $imgext) echo '\'' . $imgext . '\', '; ?>];
                if (images.indexOf(ext) > -1) get_thumbnails_url(str, files[$i]);
            }
            obj.disabled = 'disabled';
        }

        function get_thumbnails_url(str, filea) {
            if (!str) return;
            var nurl = window.location.href;
            if (nurl.substr(-1) != "/") nurl += "/";
            var xhr = new XMLHttpRequest();
            xhr.open("GET", nurl + str + '?thumbnails', true);
            //xhr.setRequestHeader('x-requested-with','XMLHttpRequest');
            xhr.send('');
            xhr.onload = function (e) {
                if (xhr.status == 200) {
                    if (xhr.responseText != '') filea.innerHTML = '<img src="' + xhr.responseText + '" alt="' + str + '">';
                } else console.log(xhr.status + '\n' + xhr.responseText);
            }
        }

        function sortby(string) {
            if (string == 'a') if (sort != 0) {
                for (i = 1; i <= <?php echo $filenum ? $filenum : 0;?>; i++) document.getElementById('tr' + i).parentNode.insertBefore(document.getElementById('tr' + i), document.getElementById('tr' + (i - 1)).nextSibling);
                sort = 0;
                return;
            } else return;
            sort1 = sort;
            sortby('a');
            sort = sort1;
            var a = [];
            for (i = 1; i <= <?php echo $filenum ? $filenum : 0;?>; i++) {
                a[i] = i;
                if (!!document.getElementById('folder_' + string + i)) {
                    var td1 = document.getElementById('folder_' + string + i);
                    for (j = 1; j < i; j++) {
                        if (!!document.getElementById('folder_' + string + a[j])) {
                            var c = false;
                            if (string == 'time') if (sort == -1) {
                                c = (td1.innerText < document.getElementById('folder_' + string + a[j]).innerText);
                            } else {
                                c = (td1.innerText > document.getElementById('folder_' + string + a[j]).innerText);
                            }
                            if (string == 'size') if (sort == 2) {
                                c = (size_reformat(td1.innerText) < size_reformat(document.getElementById('folder_' + string + a[j]).innerText));
                            } else {
                                c = (size_reformat(td1.innerText) > size_reformat(document.getElementById('folder_' + string + a[j]).innerText));
                            }
                            if (c) {
                                document.getElementById('tr' + i).parentNode.insertBefore(document.getElementById('tr' + i), document.getElementById('tr' + a[j]));
                                for (k = i; k > j; k--) {
                                    a[k] = a[k - 1];
                                }
                                a[j] = i;
                                break;
                            }
                        }
                    }
                }
                if (!!document.getElementById('file_' + string + i)) {
                    var td1 = document.getElementById('file_' + string + i);
                    for (j = 1; j < i; j++) {
                        if (!!document.getElementById('file_' + string + a[j])) {
                            var c = false;
                            if (string == 'time') if (sort == -1) {
                                c = (td1.innerText < document.getElementById('file_' + string + a[j]).innerText);
                            } else {
                                c = (td1.innerText > document.getElementById('file_' + string + a[j]).innerText);
                            }
                            if (string == 'size') if (sort == 2) {
                                c = (size_reformat(td1.innerText) < size_reformat(document.getElementById('file_' + string + a[j]).innerText));
                            } else {
                                c = (size_reformat(td1.innerText) > size_reformat(document.getElementById('file_' + string + a[j]).innerText));
                            }
                            if (c) {
                                document.getElementById('tr' + i).parentNode.insertBefore(document.getElementById('tr' + i), document.getElementById('tr' + a[j]));
                                for (k = i; k > j; k--) {
                                    a[k] = a[k - 1];
                                }
                                a[j] = i;
                                break;
                            }
                        }
                    }
                }
            }
            if (string == 'time') if (sort == -1) {
                sort = 1;
            } else {
                sort = -1;
            }
            if (string == 'size') if (sort == 2) {
                sort = -2;
            } else {
                sort = 2;
            }
        }

        function size_reformat(str) {
            if (str.substr(-1) == ' ') str = str.substr(0, str.length - 1);
            if (str.substr(-2) == 'GB') num = str.substr(0, str.length - 3) * 1024 * 1024 * 1024;
            if (str.substr(-2) == 'MB') num = str.substr(0, str.length - 3) * 1024 * 1024;
            if (str.substr(-2) == 'KB') num = str.substr(0, str.length - 3) * 1024;
            if (str.substr(-2) == ' B') num = str.substr(0, str.length - 2);
            return num;
        }
        <?php
        }
        ?>





        <?php
        if (getenv('admin') != '') { // close div. 有登录或操作，需要关闭DIV时 ?>
        function operatediv_close(operate) {
            document.getElementById(operate + '_div').style.display = 'none';
            document.getElementById('mask').style.display = 'none';
        }
        <?php }
        if (isset($files['folder']) && ($is_image_view || $is_admin)) { // is folder and is admin or guest upload path. 当前是admin登录或图床目录时 ?>
        function uploadbuttonhide() {
            document.getElementById('upload_submit').disabled = 'disabled';
            document.getElementById('upload_file').disabled = 'disabled';
            document.getElementById('upload_submit').style.display = 'none';
            document.getElementById('upload_file').style.display = 'none';
        }

        function uploadbuttonshow() {
            document.getElementById('upload_file').disabled = '';
            document.getElementById('upload_submit').disabled = '';
            document.getElementById('upload_submit').style.display = '';
            document.getElementById('upload_file').style.display = '';
        }

        function preup() {
            uploadbuttonhide();
            var files = document.getElementById('upload_file').files;
            if (files.length < 1) {
                uploadbuttonshow();
                return;
            }
            ;
            var table1 = document.createElement('table');
            document.getElementById('upload_div').appendChild(table1);
            table1.setAttribute('class', 'list-table');
            var timea = new Date().getTime();
            var i = 0;
            getuplink(i);

            function getuplink(i) {
                var file = files[i];
                var tr1 = document.createElement('tr');
                table1.appendChild(tr1);
                tr1.setAttribute('data-to', 1);
                var td1 = document.createElement('td');
                tr1.appendChild(td1);
                td1.setAttribute('style', 'width:30%');
                td1.setAttribute('id', 'upfile_td1_' + timea + '_' + i);
                td1.innerHTML = file.name + '<br>' + size_format(file.size);
                var td2 = document.createElement('td');
                tr1.appendChild(td2);
                td2.setAttribute('id', 'upfile_td2_' + timea + '_' + i);
                td2.innerHTML = '<?php echo trans('GetUploadLink'); ?> ...';
                if (file.size > 100 * 1024 * 1024 * 1024) {
                    td2.innerHTML = '<font color="red"><?php echo trans('UpFileTooLarge'); ?></font>';
                    uploadbuttonshow();
                    return;
                }
                var xhr1 = new XMLHttpRequest();
                xhr1.open("POST", '');
                xhr1.setRequestHeader('x-requested-with', 'XMLHttpRequest');
                xhr1.send('action=upbigfile&upbigfilename=' + encodeURIComponent(file.name) + '&filesize=' + file.size + '&lastModified=' + file.lastModified);
                xhr1.onload = function (e) {
                    td2.innerHTML = '<font color="red">' + xhr1.responseText + '</font>';
                    if (xhr1.status == 200) {
                        var html = JSON.parse(xhr1.responseText);
                        if (!html['uploadUrl']) {
                            td2.innerHTML = '<font color="red">' + xhr1.responseText + '</font><br>';
                            uploadbuttonshow();
                        } else {
                            td2.innerHTML = '<?php echo trans('UploadStart'); ?> ...';
                            binupfile(file, html['uploadUrl'], timea + '_' + i);
                        }
                    }
                    if (i < files.length - 1) {
                        i++;
                        getuplink(i);
                    }
                }
            }
        }

        function size_format(num) {
            if (num > 1024) {
                num = num / 1024;
            } else {
                return num.toFixed(2) + ' B';
            }
            if (num > 1024) {
                num = num / 1024;
            } else {
                return num.toFixed(2) + ' KB';
            }
            if (num > 1024) {
                num = num / 1024;
            } else {
                return num.toFixed(2) + ' MB';
            }
            return num.toFixed(2) + ' GB';
        }

        function binupfile(file, url, tdnum) {
            var label = document.getElementById('upfile_td2_' + tdnum);
            var reader = new FileReader();
            var StartStr = '';
            var MiddleStr = '';
            var StartTime;
            var EndTime;
            var newstartsize = 0;
            if (!!file) {
                var asize = 0;
                var totalsize = file.size;
                var xhr2 = new XMLHttpRequest();
                xhr2.open("GET", url);
                //xhr2.setRequestHeader('x-requested-with','XMLHttpRequest');
                xhr2.send(null);
                xhr2.onload = function (e) {
                    if (xhr2.status == 200) {
                        var html = JSON.parse(xhr2.responseText);
                        var a = html['nextExpectedRanges'][0];
                        newstartsize = Number(a.slice(0, a.indexOf("-")));
                        StartTime = new Date();
                        <?php if ($is_admin) { ?>
                        asize = newstartsize;
                        <?php } ?>
                        if (newstartsize == 0) {
                            StartStr = '<?php echo trans('UploadStartAt'); ?>:' + StartTime.toLocaleString() + '<br>';
                        } else {
                            StartStr = '<?php echo trans('LastUpload'); ?>' + size_format(newstartsize) + '<br><?php echo trans('ThisTime') . trans('UploadStartAt'); ?>:' + StartTime.toLocaleString() + '<br>';
                        }
                        var chunksize = 5 * 1024 * 1024; // chunk size, max 60M. 每小块上传大小，最大60M，微软建议10M
                        if (totalsize > 200 * 1024 * 1024) chunksize = 10 * 1024 * 1024;

                        function readblob(start) {
                            var end = start + chunksize;
                            var blob = file.slice(start, end);
                            reader.readAsArrayBuffer(blob);
                        }

                        readblob(asize);
                        <?php if (!$is_admin) { ?>
                        var spark = new SparkMD5.ArrayBuffer();
                        <?php } ?>
                        reader.onload = function (e) {
                            var binary = this.result;
                            <?php if (!$is_admin) { ?>
                            spark.append(binary);
                            if (asize < newstartsize) {
                                asize += chunksize;
                                readblob(asize);
                                return;
                            }
                            <?php } ?>
                            var xhr = new XMLHttpRequest();
                            xhr.open("PUT", url, true);
                            //xhr.setRequestHeader('x-requested-with','XMLHttpRequest');
                            bsize = asize + e.loaded - 1;
                            xhr.setRequestHeader('Content-Range', 'bytes ' + asize + '-' + bsize + '/' + totalsize);
                            xhr.upload.onprogress = function (e) {
                                if (e.lengthComputable) {
                                    var tmptime = new Date();
                                    var tmpspeed = e.loaded * 1000 / (tmptime.getTime() - C_starttime.getTime());
                                    var remaintime = (totalsize - asize - e.loaded) / tmpspeed;
                                    label.innerHTML = StartStr + '<?php echo trans('Upload'); ?> ' + size_format(asize + e.loaded) + ' / ' + size_format(totalsize) + ' = ' + ((asize + e.loaded) * 100 / totalsize).toFixed(2) + '% <?php echo trans('AverageSpeed'); ?>:' + size_format((asize + e.loaded - newstartsize) * 1000 / (tmptime.getTime() - StartTime.getTime())) + '/s<br><?php echo trans('CurrentSpeed'); ?> ' + size_format(tmpspeed) + '/s <?php echo trans('Expect'); ?> ' + remaintime.toFixed(1) + 's';
                                }
                            }
                            var C_starttime = new Date();
                            xhr.onload = function (e) {
                                if (xhr.status < 500) {
                                    var response = JSON.parse(xhr.responseText);
                                    if (response['size'] > 0) {
                                        // contain size, upload finish. 有size说明是最终返回，上传结束
                                        var xhr3 = new XMLHttpRequest();
                                        xhr3.open("POST", '');
                                        xhr3.setRequestHeader('x-requested-with', 'XMLHttpRequest');
                                        xhr3.send('action=del_upload_cache&filename=.' + file.lastModified + '_' + file.size + '_' + encodeURIComponent(file.name) + '.tmp');
                                        xhr3.onload = function (e) {
                                            console.log(xhr3.responseText + ',' + xhr3.status);
                                        }
                                        <?php if (!$is_admin) { ?>
                                        var xhr4 = new XMLHttpRequest();
                                        xhr4.open("POST", '');
                                        xhr4.setRequestHeader('x-requested-with', 'XMLHttpRequest');
                                        var filemd5 = spark.end();
                                        xhr4.send('action=uploaded_rename&filename=' + encodeURIComponent(file.name) + '&filemd5=' + filemd5);
                                        xhr4.onload = function (e) {
                                            console.log(xhr4.responseText + ',' + xhr4.status);
                                            var filename;
                                            if (xhr4.status == 200) filename = JSON.parse(xhr4.responseText)['name'];
                                            if (xhr4.status == 409) filename = filemd5 + file.name.substr(file.name.indexOf('.'));
                                            if (filename == '') {
                                                alert('<?php echo trans('UploadErrorUpAgain'); ?>');
                                                uploadbuttonshow();
                                                return;
                                            }
                                            var lasturl = location.href;
                                            if (lasturl.substr(lasturl.length - 1) != '/') lasturl += '/';
                                            lasturl += filename + '?preview';
                                            //alert(lasturl);
                                            window.open(lasturl);
                                        }
                                        <?php } ?>
                                        EndTime = new Date();
                                        MiddleStr = '<?php echo trans('EndAt'); ?>:' + EndTime.toLocaleString() + '<br>';
                                        if (newstartsize == 0) {
                                            MiddleStr += '<?php echo trans('AverageSpeed'); ?>:' + size_format(totalsize * 1000 / (EndTime.getTime() - StartTime.getTime())) + '/s<br>';
                                        } else {
                                            MiddleStr += '<?php echo trans('ThisTime') . trans('AverageSpeed'); ?>:' + size_format((totalsize - newstartsize) * 1000 / (EndTime.getTime() - StartTime.getTime())) + '/s<br>';
                                        }
                                        document.getElementById('upfile_td1_' + tdnum).innerHTML = '<font color="green"><?php if (!$is_admin) { ?>' + filemd5 + '<br><?php } ?>' + document.getElementById('upfile_td1_' + tdnum).innerHTML + '<br><?php echo trans('UploadComplete'); ?></font>';
                                        label.innerHTML = StartStr + MiddleStr;
                                        uploadbuttonshow();
                                        <?php if ($is_admin) { ?>
                                        addelement(response);
                                        <?php } ?>
                                    } else {
                                        if (!response['nextExpectedRanges']) {
                                            label.innerHTML = '<font color="red">' + xhr.responseText + '</font><br>';
                                        } else {
                                            var a = response['nextExpectedRanges'][0];
                                            asize = Number(a.slice(0, a.indexOf("-")));
                                            readblob(asize);
                                        }
                                    }
                                } else readblob(asize);
                            }
                            xhr.send(binary);
                        }
                    } else {
                        if (window.location.pathname.indexOf('%23') > 0 || file.name.indexOf('%23') > 0) {
                            label.innerHTML = '<font color="red"><?php echo trans('UploadFail23'); ?></font>';
                        } else {
                            label.innerHTML = '<font color="red">' + xhr2.responseText + '</font>';
                        }
                        uploadbuttonshow();
                    }
                }
            }
        }
        <?php }
        if ($is_admin) { // admin login. 管理登录后 ?>
        function logout() {
            document.cookie = "<?php echo $_SERVER['function_name'] . 'admin';?>=; path=/";
            location.href = location.href;
        }

        function enableedit(obj) {
            document.getElementById('txt-a').readOnly = !document.getElementById('txt-a').readOnly;
            //document.getElementById('txt-editbutton').innerHTML=(document.getElementById('txt-editbutton').innerHTML=='取消编辑')?'点击后编辑':'取消编辑';
            obj.innerHTML = (obj.innerHTML == '<?php echo trans('CancelEdit'); ?>') ? '<?php echo trans('ClicktoEdit'); ?>' : '<?php echo trans('CancelEdit'); ?>';
            document.getElementById('txt-save').style.display = document.getElementById('txt-save').style.display == '' ? 'none' : '';
        }
        <?php   if (!$_GET['preview']) {?>
        function showdiv(event, action, num) {
            var $operatediv = document.getElementsByName('operatediv');
            for ($i = 0; $i < $operatediv.length; $i++) {
                $operatediv[$i].style.display = 'none';
            }
            document.getElementById('mask').style.display = '';
            //document.getElementById('mask').style.width=document.documentElement.scrollWidth+'px';
            document.getElementById('mask').style.height = document.documentElement.scrollHeight < window.innerHeight ? window.innerHeight : document.documentElement.scrollHeight + 'px';
            if (num == '') {
                var str = '';
            } else {
                var str = document.getElementById('file_a' + num).innerText;
                if (str == '') {
                    str = document.getElementById('file_a' + num).getElementsByTagName("img")[0].alt;
                    if (str == '') {
                        alert('<?php echo trans('GetFileNameFail'); ?>');
                        operatediv_close(action);
                        return;
                    }
                }
                if (str.substr(-1) == ' ') str = str.substr(0, str.length - 1);
            }
            document.getElementById(action + '_div').style.display = '';
            document.getElementById(action + '_label').innerText = str;//.replace(/&/,'&amp;');
            document.getElementById(action + '_sid').value = num;
            document.getElementById(action + '_hidden').value = str;
            if (action == 'rename') document.getElementById(action + '_input').value = str;

            var $e = event || window.event;
            var $scrollX = document.documentElement.scrollLeft || document.body.scrollLeft;
            var $scrollY = document.documentElement.scrollTop || document.body.scrollTop;
            var $x = $e.pageX || $e.clientX + $scrollX;
            var $y = $e.pageY || $e.clientY + $scrollY;
            if (action == 'create') {
                document.getElementById(action + '_div').style.left = (document.body.clientWidth - document.getElementById(action + '_div').offsetWidth) / 2 + 'px';
                document.getElementById(action + '_div').style.top = (window.innerHeight - document.getElementById(action + '_div').offsetHeight) / 2 + $scrollY + 'px';
            } else {
                if ($x + document.getElementById(action + '_div').offsetWidth > document.body.clientWidth) {
                    document.getElementById(action + '_div').style.left = document.body.clientWidth - document.getElementById(action + '_div').offsetWidth + 'px';
                } else {
                    document.getElementById(action + '_div').style.left = $x + 'px';
                }
                document.getElementById(action + '_div').style.top = $y + 'px';
            }
            document.getElementById(action + '_input').focus();
        }

        function submit_operate(str) {
            var num = document.getElementById(str + '_sid').value;
            var xhr = new XMLHttpRequest();
            xhr.open("POST", '', true);
            xhr.setRequestHeader('x-requested-with', 'XMLHttpRequest');
            xhr.send(serializeForm(str + '_form'));
            xhr.onload = function (e) {
                var html;
                if (xhr.status < 300) {
                    if (str == 'rename') {
                        html = JSON.parse(xhr.responseText);
                        var file_a = document.getElementById('file_a' + num);
                        file_a.innerText = html.name;
                        file_a.href = (file_a.href.substr(-8) == '?preview') ? (html.name.replace(/#/, '%23') + '?preview') : (html.name.replace(/#/, '%23') + '/');
                    }
                    if (str == 'move' || str == 'delete') document.getElementById('tr' + num).parentNode.removeChild(document.getElementById('tr' + num));
                    if (str == 'create') {
                        html = JSON.parse(xhr.responseText);
                        addelement(html);
                    }
                } else alert(xhr.status + '\n' + xhr.responseText);
                document.getElementById(str + '_div').style.display = 'none';
                document.getElementById('mask').style.display = 'none';
            }
            return false;
        }

        function addelement(html) {
            var tr1 = document.createElement('tr');
            tr1.setAttribute('data-to', 1);
            var td1 = document.createElement('td');
            td1.setAttribute('class', 'file');
            var a1 = document.createElement('a');
            a1.href = html.name.replace(/#/, '%23');
            a1.innerText = html.name;
            a1.target = '_blank';
            var td2 = document.createElement('td');
            td2.setAttribute('class', 'updated_at');
            td2.innerText = html.lastModifiedDateTime.replace(/T/, ' ').replace(/Z/, '');
            var td3 = document.createElement('td');
            td3.setAttribute('class', 'size');
            td3.innerText = size_format(html.size);
            if (!!html.folder) {
                a1.href += '/';
                document.getElementById('tr0').parentNode.insertBefore(tr1, document.getElementById('tr0').nextSibling);
            }
            if (!!html.file) {
                a1.href += '?preview';
                a1.name = 'filelist';
                document.getElementById('tr0').parentNode.appendChild(tr1);
            }
            tr1.appendChild(td1);
            td1.appendChild(a1);
            tr1.appendChild(td2);
            tr1.appendChild(td3);
        }

        function getElements(formId) {
            var form = document.getElementById(formId);
            var elements = new Array();
            var tagElements = form.getElementsByTagName('input');
            for (var j = 0; j < tagElements.length; j++) {
                elements.push(tagElements[j]);
            }
            var tagElements = form.getElementsByTagName('select');
            for (var j = 0; j < tagElements.length; j++) {
                elements.push(tagElements[j]);
            }
            var tagElements = form.getElementsByTagName('textarea');
            for (var j = 0; j < tagElements.length; j++) {
                elements.push(tagElements[j]);
            }
            return elements;
        }

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
                    if (element.checked) {
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
        <?php   }
        } else if (getenv('admin') != '') if (getenv('adminloginpage') == '') { ?>
        function login() {
            document.getElementById('mask').style.display = '';
            //document.getElementById('mask').style.width=document.documentElement.scrollWidth+'px';
            document.getElementById('mask').style.height = document.documentElement.scrollHeight < window.innerHeight ? window.innerHeight : document.documentElement.scrollHeight + 'px';
            document.getElementById('login_div').style.display = '';
            document.getElementById('login_div').style.left = (document.body.clientWidth - document.getElementById('login_div').offsetWidth) / 2 + 'px';
            document.getElementById('login_div').style.top = (window.innerHeight - document.getElementById('login_div').offsetHeight) / 2 + document.body.scrollTop + 'px';
            document.getElementById('login_input').focus();
        }
        <?php } ?>
    </script>
    <script src="//unpkg.zhimg.com/ionicons@4.4.4/dist/ionicons.js"></script>
    </html>
    <?php
    $html = ob_get_clean();
    return response($html, $status_code);
}

?>
<?php
namespace Platforms {
interface PlatformInterface
{
    /**
     * @return \Symfony\Component\HttpFoundation\Request
     */
    public static function request();
    /**
     * @param \Symfony\Component\HttpFoundation\Response $response
     * @return mixed
     */
    public static function response($response);
}
}

namespace Platforms {
use Platforms\Normal\Normal;
use Platforms\QCloudSCF\QCloudSCF;
class Platform implements PlatformInterface
{
    const PLATFORM_NORMAL = 0;
    const PLATFORM_QCLOUD_SCF = 1;
    /**
     * @return \Symfony\Component\HttpFoundation\Request
     */
    public static function request()
    {
        global $config;
        switch ($config['platform']) {
            default:
            case self::PLATFORM_NORMAL:
                return Normal::request();
            case self::PLATFORM_QCLOUD_SCF:
                return QCloudSCF::request();
        }
    }
    /**
     * @param \Symfony\Component\HttpFoundation\Response $response
     * @return mixed
     */
    public static function response($response)
    {
        global $config;
        switch ($config['platform']) {
            default:
            case self::PLATFORM_NORMAL:
                return Normal::response($response);
            case self::PLATFORM_QCLOUD_SCF:
                return QCloudSCF::response($response);
        }
    }
}
}

namespace Platforms\Normal {
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
class Normal
{
    /**
     * @return Request
     */
    public static function request()
    {
        return Request::createFromGlobals();
    }
    /**
     * @param Response $response
     * @return mixed
     */
    public static function response($response)
    {
        return $response;
    }
}
}

namespace Symfony\Component\HttpFoundation {
use Symfony\Component\HttpFoundation\Exception\ConflictingHeadersException;
use Symfony\Component\HttpFoundation\Exception\SuspiciousOperationException;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
/**
 * Request represents an HTTP request.
 *
 * The methods dealing with URL accept / return a raw path (% encoded):
 *   * getBasePath
 *   * getBaseUrl
 *   * getPathInfo
 *   * getRequestUri
 *   * getUri
 *   * getUriForPath
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class Request
{
    const HEADER_FORWARDED = 0b1;
    // When using RFC 7239
    const HEADER_X_FORWARDED_FOR = 0b10;
    const HEADER_X_FORWARDED_HOST = 0b100;
    const HEADER_X_FORWARDED_PROTO = 0b1000;
    const HEADER_X_FORWARDED_PORT = 0b10000;
    const HEADER_X_FORWARDED_ALL = 0b11110;
    // All "X-Forwarded-*" headers
    const HEADER_X_FORWARDED_AWS_ELB = 0b11010;
    // AWS ELB doesn't send X-Forwarded-Host
    /** @deprecated since version 3.3, to be removed in 4.0 */
    const HEADER_CLIENT_IP = self::HEADER_X_FORWARDED_FOR;
    /** @deprecated since version 3.3, to be removed in 4.0 */
    const HEADER_CLIENT_HOST = self::HEADER_X_FORWARDED_HOST;
    /** @deprecated since version 3.3, to be removed in 4.0 */
    const HEADER_CLIENT_PROTO = self::HEADER_X_FORWARDED_PROTO;
    /** @deprecated since version 3.3, to be removed in 4.0 */
    const HEADER_CLIENT_PORT = self::HEADER_X_FORWARDED_PORT;
    const METHOD_HEAD = 'HEAD';
    const METHOD_GET = 'GET';
    const METHOD_POST = 'POST';
    const METHOD_PUT = 'PUT';
    const METHOD_PATCH = 'PATCH';
    const METHOD_DELETE = 'DELETE';
    const METHOD_PURGE = 'PURGE';
    const METHOD_OPTIONS = 'OPTIONS';
    const METHOD_TRACE = 'TRACE';
    const METHOD_CONNECT = 'CONNECT';
    /**
     * @var string[]
     */
    protected static $trustedProxies = [];
    /**
     * @var string[]
     */
    protected static $trustedHostPatterns = [];
    /**
     * @var string[]
     */
    protected static $trustedHosts = [];
    /**
     * Names for headers that can be trusted when
     * using trusted proxies.
     *
     * The FORWARDED header is the standard as of rfc7239.
     *
     * The other headers are non-standard, but widely used
     * by popular reverse proxies (like Apache mod_proxy or Amazon EC2).
     *
     * @deprecated since version 3.3, to be removed in 4.0
     */
    protected static $trustedHeaders = [self::HEADER_FORWARDED => 'FORWARDED', self::HEADER_CLIENT_IP => 'X_FORWARDED_FOR', self::HEADER_CLIENT_HOST => 'X_FORWARDED_HOST', self::HEADER_CLIENT_PROTO => 'X_FORWARDED_PROTO', self::HEADER_CLIENT_PORT => 'X_FORWARDED_PORT'];
    protected static $httpMethodParameterOverride = false;
    /**
     * Custom parameters.
     *
     * @var ParameterBag
     */
    public $attributes;
    /**
     * Request body parameters ($_POST).
     *
     * @var ParameterBag
     */
    public $request;
    /**
     * Query string parameters ($_GET).
     *
     * @var ParameterBag
     */
    public $query;
    /**
     * Server and execution environment parameters ($_SERVER).
     *
     * @var ServerBag
     */
    public $server;
    /**
     * Uploaded files ($_FILES).
     *
     * @var FileBag
     */
    public $files;
    /**
     * Cookies ($_COOKIE).
     *
     * @var ParameterBag
     */
    public $cookies;
    /**
     * Headers (taken from the $_SERVER).
     *
     * @var HeaderBag
     */
    public $headers;
    /**
     * @var string|resource|false|null
     */
    protected $content;
    /**
     * @var array
     */
    protected $languages;
    /**
     * @var array
     */
    protected $charsets;
    /**
     * @var array
     */
    protected $encodings;
    /**
     * @var array
     */
    protected $acceptableContentTypes;
    /**
     * @var string
     */
    protected $pathInfo;
    /**
     * @var string
     */
    protected $requestUri;
    /**
     * @var string
     */
    protected $baseUrl;
    /**
     * @var string
     */
    protected $basePath;
    /**
     * @var string
     */
    protected $method;
    /**
     * @var string
     */
    protected $format;
    /**
     * @var SessionInterface
     */
    protected $session;
    /**
     * @var string
     */
    protected $locale;
    /**
     * @var string
     */
    protected $defaultLocale = 'en';
    /**
     * @var array
     */
    protected static $formats;
    protected static $requestFactory;
    private $isHostValid = true;
    private $isForwardedValid = true;
    private static $trustedHeaderSet = -1;
    /** @deprecated since version 3.3, to be removed in 4.0 */
    private static $trustedHeaderNames = [self::HEADER_FORWARDED => 'FORWARDED', self::HEADER_CLIENT_IP => 'X_FORWARDED_FOR', self::HEADER_CLIENT_HOST => 'X_FORWARDED_HOST', self::HEADER_CLIENT_PROTO => 'X_FORWARDED_PROTO', self::HEADER_CLIENT_PORT => 'X_FORWARDED_PORT'];
    private static $forwardedParams = [self::HEADER_X_FORWARDED_FOR => 'for', self::HEADER_X_FORWARDED_HOST => 'host', self::HEADER_X_FORWARDED_PROTO => 'proto', self::HEADER_X_FORWARDED_PORT => 'host'];
    /**
     * @param array                $query      The GET parameters
     * @param array                $request    The POST parameters
     * @param array                $attributes The request attributes (parameters parsed from the PATH_INFO, ...)
     * @param array                $cookies    The COOKIE parameters
     * @param array                $files      The FILES parameters
     * @param array                $server     The SERVER parameters
     * @param string|resource|null $content    The raw body data
     */
    public function __construct(array $query = [], array $request = [], array $attributes = [], array $cookies = [], array $files = [], array $server = [], $content = null)
    {
        $this->initialize($query, $request, $attributes, $cookies, $files, $server, $content);
    }
    /**
     * Sets the parameters for this request.
     *
     * This method also re-initializes all properties.
     *
     * @param array                $query      The GET parameters
     * @param array                $request    The POST parameters
     * @param array                $attributes The request attributes (parameters parsed from the PATH_INFO, ...)
     * @param array                $cookies    The COOKIE parameters
     * @param array                $files      The FILES parameters
     * @param array                $server     The SERVER parameters
     * @param string|resource|null $content    The raw body data
     */
    public function initialize(array $query = [], array $request = [], array $attributes = [], array $cookies = [], array $files = [], array $server = [], $content = null)
    {
        $this->request = new ParameterBag($request);
        $this->query = new ParameterBag($query);
        $this->attributes = new ParameterBag($attributes);
        $this->cookies = new ParameterBag($cookies);
        $this->files = new FileBag($files);
        $this->server = new ServerBag($server);
        $this->headers = new HeaderBag($this->server->getHeaders());
        $this->content = $content;
        $this->languages = null;
        $this->charsets = null;
        $this->encodings = null;
        $this->acceptableContentTypes = null;
        $this->pathInfo = null;
        $this->requestUri = null;
        $this->baseUrl = null;
        $this->basePath = null;
        $this->method = null;
        $this->format = null;
    }
    /**
     * Creates a new request with values from PHP's super globals.
     *
     * @return static
     */
    public static function createFromGlobals()
    {
        // With the php's bug #66606, the php's built-in web server
        // stores the Content-Type and Content-Length header values in
        // HTTP_CONTENT_TYPE and HTTP_CONTENT_LENGTH fields.
        $server = $_SERVER;
        if ('cli-server' === \PHP_SAPI) {
            if (\array_key_exists('HTTP_CONTENT_LENGTH', $_SERVER)) {
                $server['CONTENT_LENGTH'] = $_SERVER['HTTP_CONTENT_LENGTH'];
            }
            if (\array_key_exists('HTTP_CONTENT_TYPE', $_SERVER)) {
                $server['CONTENT_TYPE'] = $_SERVER['HTTP_CONTENT_TYPE'];
            }
        }
        $request = self::createRequestFromFactory($_GET, $_POST, [], $_COOKIE, $_FILES, $server);
        if (0 === strpos($request->headers->get('CONTENT_TYPE'), 'application/x-www-form-urlencoded') && \in_array(strtoupper($request->server->get('REQUEST_METHOD', 'GET')), ['PUT', 'DELETE', 'PATCH'])) {
            parse_str($request->getContent(), $data);
            $request->request = new ParameterBag($data);
        }
        return $request;
    }
    /**
     * Creates a Request based on a given URI and configuration.
     *
     * The information contained in the URI always take precedence
     * over the other information (server and parameters).
     *
     * @param string               $uri        The URI
     * @param string               $method     The HTTP method
     * @param array                $parameters The query (GET) or request (POST) parameters
     * @param array                $cookies    The request cookies ($_COOKIE)
     * @param array                $files      The request files ($_FILES)
     * @param array                $server     The server parameters ($_SERVER)
     * @param string|resource|null $content    The raw body data
     *
     * @return static
     */
    public static function create($uri, $method = 'GET', $parameters = [], $cookies = [], $files = [], $server = [], $content = null)
    {
        $server = array_replace(['SERVER_NAME' => 'localhost', 'SERVER_PORT' => 80, 'HTTP_HOST' => 'localhost', 'HTTP_USER_AGENT' => 'Symfony/3.X', 'HTTP_ACCEPT' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8', 'HTTP_ACCEPT_LANGUAGE' => 'en-us,en;q=0.5', 'HTTP_ACCEPT_CHARSET' => 'ISO-8859-1,utf-8;q=0.7,*;q=0.7', 'REMOTE_ADDR' => '127.0.0.1', 'SCRIPT_NAME' => '', 'SCRIPT_FILENAME' => '', 'SERVER_PROTOCOL' => 'HTTP/1.1', 'REQUEST_TIME' => time()], $server);
        $server['PATH_INFO'] = '';
        $server['REQUEST_METHOD'] = strtoupper($method);
        $components = parse_url($uri);
        if (isset($components['host'])) {
            $server['SERVER_NAME'] = $components['host'];
            $server['HTTP_HOST'] = $components['host'];
        }
        if (isset($components['scheme'])) {
            if ('https' === $components['scheme']) {
                $server['HTTPS'] = 'on';
                $server['SERVER_PORT'] = 443;
            } else {
                unset($server['HTTPS']);
                $server['SERVER_PORT'] = 80;
            }
        }
        if (isset($components['port'])) {
            $server['SERVER_PORT'] = $components['port'];
            $server['HTTP_HOST'] .= ':' . $components['port'];
        }
        if (isset($components['user'])) {
            $server['PHP_AUTH_USER'] = $components['user'];
        }
        if (isset($components['pass'])) {
            $server['PHP_AUTH_PW'] = $components['pass'];
        }
        if (!isset($components['path'])) {
            $components['path'] = '/';
        }
        switch (strtoupper($method)) {
            case 'POST':
            case 'PUT':
            case 'DELETE':
                if (!isset($server['CONTENT_TYPE'])) {
                    $server['CONTENT_TYPE'] = 'application/x-www-form-urlencoded';
                }
            // no break
            case 'PATCH':
                $request = $parameters;
                $query = [];
                break;
            default:
                $request = [];
                $query = $parameters;
                break;
        }
        $queryString = '';
        if (isset($components['query'])) {
            parse_str(html_entity_decode($components['query']), $qs);
            if ($query) {
                $query = array_replace($qs, $query);
                $queryString = http_build_query($query, '', '&');
            } else {
                $query = $qs;
                $queryString = $components['query'];
            }
        } elseif ($query) {
            $queryString = http_build_query($query, '', '&');
        }
        $server['REQUEST_URI'] = $components['path'] . ('' !== $queryString ? '?' . $queryString : '');
        $server['QUERY_STRING'] = $queryString;
        return self::createRequestFromFactory($query, $request, [], $cookies, $files, $server, $content);
    }
    /**
     * Sets a callable able to create a Request instance.
     *
     * This is mainly useful when you need to override the Request class
     * to keep BC with an existing system. It should not be used for any
     * other purpose.
     *
     * @param callable|null $callable A PHP callable
     */
    public static function setFactory($callable)
    {
        self::$requestFactory = $callable;
    }
    /**
     * Clones a request and overrides some of its parameters.
     *
     * @param array $query      The GET parameters
     * @param array $request    The POST parameters
     * @param array $attributes The request attributes (parameters parsed from the PATH_INFO, ...)
     * @param array $cookies    The COOKIE parameters
     * @param array $files      The FILES parameters
     * @param array $server     The SERVER parameters
     *
     * @return static
     */
    public function duplicate(array $query = null, array $request = null, array $attributes = null, array $cookies = null, array $files = null, array $server = null)
    {
        $dup = clone $this;
        if (null !== $query) {
            $dup->query = new ParameterBag($query);
        }
        if (null !== $request) {
            $dup->request = new ParameterBag($request);
        }
        if (null !== $attributes) {
            $dup->attributes = new ParameterBag($attributes);
        }
        if (null !== $cookies) {
            $dup->cookies = new ParameterBag($cookies);
        }
        if (null !== $files) {
            $dup->files = new FileBag($files);
        }
        if (null !== $server) {
            $dup->server = new ServerBag($server);
            $dup->headers = new HeaderBag($dup->server->getHeaders());
        }
        $dup->languages = null;
        $dup->charsets = null;
        $dup->encodings = null;
        $dup->acceptableContentTypes = null;
        $dup->pathInfo = null;
        $dup->requestUri = null;
        $dup->baseUrl = null;
        $dup->basePath = null;
        $dup->method = null;
        $dup->format = null;
        if (!$dup->get('_format') && $this->get('_format')) {
            $dup->attributes->set('_format', $this->get('_format'));
        }
        if (!$dup->getRequestFormat(null)) {
            $dup->setRequestFormat($this->getRequestFormat(null));
        }
        return $dup;
    }
    /**
     * Clones the current request.
     *
     * Note that the session is not cloned as duplicated requests
     * are most of the time sub-requests of the main one.
     */
    public function __clone()
    {
        $this->query = clone $this->query;
        $this->request = clone $this->request;
        $this->attributes = clone $this->attributes;
        $this->cookies = clone $this->cookies;
        $this->files = clone $this->files;
        $this->server = clone $this->server;
        $this->headers = clone $this->headers;
    }
    /**
     * Returns the request as a string.
     *
     * @return string The request
     */
    public function __toString()
    {
        try {
            $content = $this->getContent();
        } catch (\LogicException $e) {
            if (\PHP_VERSION_ID >= 70400) {
                throw $e;
            }
            return trigger_error($e, E_USER_ERROR);
        }
        $cookieHeader = '';
        $cookies = [];
        foreach ($this->cookies as $k => $v) {
            $cookies[] = $k . '=' . $v;
        }
        if (!empty($cookies)) {
            $cookieHeader = 'Cookie: ' . implode('; ', $cookies) . "\r\n";
        }
        return sprintf('%s %s %s', $this->getMethod(), $this->getRequestUri(), $this->server->get('SERVER_PROTOCOL')) . "\r\n" . $this->headers . $cookieHeader . "\r\n" . $content;
    }
    /**
     * Overrides the PHP global variables according to this request instance.
     *
     * It overrides $_GET, $_POST, $_REQUEST, $_SERVER, $_COOKIE.
     * $_FILES is never overridden, see rfc1867
     */
    public function overrideGlobals()
    {
        $this->server->set('QUERY_STRING', static::normalizeQueryString(http_build_query($this->query->all(), '', '&')));
        $_GET = $this->query->all();
        $_POST = $this->request->all();
        $_SERVER = $this->server->all();
        $_COOKIE = $this->cookies->all();
        foreach ($this->headers->all() as $key => $value) {
            $key = strtoupper(str_replace('-', '_', $key));
            if (\in_array($key, ['CONTENT_TYPE', 'CONTENT_LENGTH'])) {
                $_SERVER[$key] = implode(', ', $value);
            } else {
                $_SERVER['HTTP_' . $key] = implode(', ', $value);
            }
        }
        $request = ['g' => $_GET, 'p' => $_POST, 'c' => $_COOKIE];
        $requestOrder = ini_get('request_order') ?: ini_get('variables_order');
        $requestOrder = preg_replace('#[^cgp]#', '', strtolower($requestOrder)) ?: 'gp';
        $_REQUEST = [];
        foreach (str_split($requestOrder) as $order) {
            $_REQUEST = array_merge($_REQUEST, $request[$order]);
        }
    }
    /**
     * Sets a list of trusted proxies.
     *
     * You should only list the reverse proxies that you manage directly.
     *
     * @param array $proxies          A list of trusted proxies
     * @param int   $trustedHeaderSet A bit field of Request::HEADER_*, to set which headers to trust from your proxies
     *
     * @throws \InvalidArgumentException When $trustedHeaderSet is invalid
     */
    public static function setTrustedProxies(array $proxies)
    {
        self::$trustedProxies = $proxies;
        if (2 > \func_num_args()) {
            @trigger_error(sprintf('The %s() method expects a bit field of Request::HEADER_* as second argument since Symfony 3.3. Defining it will be required in 4.0. ', __METHOD__), E_USER_DEPRECATED);
            return;
        }
        $trustedHeaderSet = (int) func_get_arg(1);
        foreach (self::$trustedHeaderNames as $header => $name) {
            self::$trustedHeaders[$header] = $header & $trustedHeaderSet ? $name : null;
        }
        self::$trustedHeaderSet = $trustedHeaderSet;
    }
    /**
     * Gets the list of trusted proxies.
     *
     * @return array An array of trusted proxies
     */
    public static function getTrustedProxies()
    {
        return self::$trustedProxies;
    }
    /**
     * Gets the set of trusted headers from trusted proxies.
     *
     * @return int A bit field of Request::HEADER_* that defines which headers are trusted from your proxies
     */
    public static function getTrustedHeaderSet()
    {
        return self::$trustedHeaderSet;
    }
    /**
     * Sets a list of trusted host patterns.
     *
     * You should only list the hosts you manage using regexs.
     *
     * @param array $hostPatterns A list of trusted host patterns
     */
    public static function setTrustedHosts(array $hostPatterns)
    {
        self::$trustedHostPatterns = array_map(function ($hostPattern) {
            return sprintf('{%s}i', $hostPattern);
        }, $hostPatterns);
        // we need to reset trusted hosts on trusted host patterns change
        self::$trustedHosts = [];
    }
    /**
     * Gets the list of trusted host patterns.
     *
     * @return array An array of trusted host patterns
     */
    public static function getTrustedHosts()
    {
        return self::$trustedHostPatterns;
    }
    /**
     * Sets the name for trusted headers.
     *
     * The following header keys are supported:
     *
     *  * Request::HEADER_CLIENT_IP:    defaults to X-Forwarded-For   (see getClientIp())
     *  * Request::HEADER_CLIENT_HOST:  defaults to X-Forwarded-Host  (see getHost())
     *  * Request::HEADER_CLIENT_PORT:  defaults to X-Forwarded-Port  (see getPort())
     *  * Request::HEADER_CLIENT_PROTO: defaults to X-Forwarded-Proto (see getScheme() and isSecure())
     *  * Request::HEADER_FORWARDED:    defaults to Forwarded         (see RFC 7239)
     *
     * Setting an empty value allows to disable the trusted header for the given key.
     *
     * @param string $key   The header key
     * @param string $value The header name
     *
     * @throws \InvalidArgumentException
     *
     * @deprecated since version 3.3, to be removed in 4.0. Use the $trustedHeaderSet argument of the Request::setTrustedProxies() method instead.
     */
    public static function setTrustedHeaderName($key, $value)
    {
        @trigger_error(sprintf('The "%s()" method is deprecated since Symfony 3.3 and will be removed in 4.0. Use the $trustedHeaderSet argument of the Request::setTrustedProxies() method instead.', __METHOD__), E_USER_DEPRECATED);
        if ('forwarded' === $key) {
            $key = self::HEADER_FORWARDED;
        } elseif ('client_ip' === $key) {
            $key = self::HEADER_CLIENT_IP;
        } elseif ('client_host' === $key) {
            $key = self::HEADER_CLIENT_HOST;
        } elseif ('client_proto' === $key) {
            $key = self::HEADER_CLIENT_PROTO;
        } elseif ('client_port' === $key) {
            $key = self::HEADER_CLIENT_PORT;
        } elseif (!\array_key_exists($key, self::$trustedHeaders)) {
            throw new \InvalidArgumentException(sprintf('Unable to set the trusted header name for key "%s".', $key));
        }
        self::$trustedHeaders[$key] = $value;
        if (null !== $value) {
            self::$trustedHeaderNames[$key] = $value;
            self::$trustedHeaderSet |= $key;
        } else {
            self::$trustedHeaderSet &= ~$key;
        }
    }
    /**
     * Gets the trusted proxy header name.
     *
     * @param string $key The header key
     *
     * @return string The header name
     *
     * @throws \InvalidArgumentException
     *
     * @deprecated since version 3.3, to be removed in 4.0. Use the Request::getTrustedHeaderSet() method instead.
     */
    public static function getTrustedHeaderName($key)
    {
        if (2 > \func_num_args() || func_get_arg(1)) {
            @trigger_error(sprintf('The "%s()" method is deprecated since Symfony 3.3 and will be removed in 4.0. Use the Request::getTrustedHeaderSet() method instead.', __METHOD__), E_USER_DEPRECATED);
        }
        if (!\array_key_exists($key, self::$trustedHeaders)) {
            throw new \InvalidArgumentException(sprintf('Unable to get the trusted header name for key "%s".', $key));
        }
        return self::$trustedHeaders[$key];
    }
    /**
     * Normalizes a query string.
     *
     * It builds a normalized query string, where keys/value pairs are alphabetized,
     * have consistent escaping and unneeded delimiters are removed.
     *
     * @param string $qs Query string
     *
     * @return string A normalized query string for the Request
     */
    public static function normalizeQueryString($qs)
    {
        if ('' == $qs) {
            return '';
        }
        $parts = [];
        $order = [];
        foreach (explode('&', $qs) as $param) {
            if ('' === $param || '=' === $param[0]) {
                // Ignore useless delimiters, e.g. "x=y&".
                // Also ignore pairs with empty key, even if there was a value, e.g. "=value", as such nameless values cannot be retrieved anyway.
                // PHP also does not include them when building _GET.
                continue;
            }
            $keyValuePair = explode('=', $param, 2);
            // GET parameters, that are submitted from a HTML form, encode spaces as "+" by default (as defined in enctype application/x-www-form-urlencoded).
            // PHP also converts "+" to spaces when filling the global _GET or when using the function parse_str. This is why we use urldecode and then normalize to
            // RFC 3986 with rawurlencode.
            $parts[] = isset($keyValuePair[1]) ? rawurlencode(urldecode($keyValuePair[0])) . '=' . rawurlencode(urldecode($keyValuePair[1])) : rawurlencode(urldecode($keyValuePair[0]));
            $order[] = urldecode($keyValuePair[0]);
        }
        array_multisort($order, SORT_ASC, $parts);
        return implode('&', $parts);
    }
    /**
     * Enables support for the _method request parameter to determine the intended HTTP method.
     *
     * Be warned that enabling this feature might lead to CSRF issues in your code.
     * Check that you are using CSRF tokens when required.
     * If the HTTP method parameter override is enabled, an html-form with method "POST" can be altered
     * and used to send a "PUT" or "DELETE" request via the _method request parameter.
     * If these methods are not protected against CSRF, this presents a possible vulnerability.
     *
     * The HTTP method can only be overridden when the real HTTP method is POST.
     */
    public static function enableHttpMethodParameterOverride()
    {
        self::$httpMethodParameterOverride = true;
    }
    /**
     * Checks whether support for the _method request parameter is enabled.
     *
     * @return bool True when the _method request parameter is enabled, false otherwise
     */
    public static function getHttpMethodParameterOverride()
    {
        return self::$httpMethodParameterOverride;
    }
    /**
     * Gets a "parameter" value from any bag.
     *
     * This method is mainly useful for libraries that want to provide some flexibility. If you don't need the
     * flexibility in controllers, it is better to explicitly get request parameters from the appropriate
     * public property instead (attributes, query, request).
     *
     * Order of precedence: PATH (routing placeholders or custom attributes), GET, BODY
     *
     * @param string $key     The key
     * @param mixed  $default The default value if the parameter key does not exist
     *
     * @return mixed
     */
    public function get($key, $default = null)
    {
        if ($this !== ($result = $this->attributes->get($key, $this))) {
            return $result;
        }
        if ($this !== ($result = $this->query->get($key, $this))) {
            return $result;
        }
        if ($this !== ($result = $this->request->get($key, $this))) {
            return $result;
        }
        return $default;
    }
    /**
     * Gets the Session.
     *
     * @return SessionInterface|null The session
     */
    public function getSession()
    {
        return $this->session;
    }
    /**
     * Whether the request contains a Session which was started in one of the
     * previous requests.
     *
     * @return bool
     */
    public function hasPreviousSession()
    {
        // the check for $this->session avoids malicious users trying to fake a session cookie with proper name
        return $this->hasSession() && $this->cookies->has($this->session->getName());
    }
    /**
     * Whether the request contains a Session object.
     *
     * This method does not give any information about the state of the session object,
     * like whether the session is started or not. It is just a way to check if this Request
     * is associated with a Session instance.
     *
     * @return bool true when the Request contains a Session object, false otherwise
     */
    public function hasSession()
    {
        return null !== $this->session;
    }
    /**
     * Sets the Session.
     *
     * @param SessionInterface $session The Session
     */
    public function setSession(SessionInterface $session)
    {
        $this->session = $session;
    }
    /**
     * Returns the client IP addresses.
     *
     * In the returned array the most trusted IP address is first, and the
     * least trusted one last. The "real" client IP address is the last one,
     * but this is also the least trusted one. Trusted proxies are stripped.
     *
     * Use this method carefully; you should use getClientIp() instead.
     *
     * @return array The client IP addresses
     *
     * @see getClientIp()
     */
    public function getClientIps()
    {
        $ip = $this->server->get('REMOTE_ADDR');
        if (!$this->isFromTrustedProxy()) {
            return [$ip];
        }
        return $this->getTrustedValues(self::HEADER_CLIENT_IP, $ip) ?: [$ip];
    }
    /**
     * Returns the client IP address.
     *
     * This method can read the client IP address from the "X-Forwarded-For" header
     * when trusted proxies were set via "setTrustedProxies()". The "X-Forwarded-For"
     * header value is a comma+space separated list of IP addresses, the left-most
     * being the original client, and each successive proxy that passed the request
     * adding the IP address where it received the request from.
     *
     * If your reverse proxy uses a different header name than "X-Forwarded-For",
     * ("Client-Ip" for instance), configure it via the $trustedHeaderSet
     * argument of the Request::setTrustedProxies() method instead.
     *
     * @return string|null The client IP address
     *
     * @see getClientIps()
     * @see https://wikipedia.org/wiki/X-Forwarded-For
     */
    public function getClientIp()
    {
        $ipAddresses = $this->getClientIps();
        return $ipAddresses[0];
    }
    /**
     * Returns current script name.
     *
     * @return string
     */
    public function getScriptName()
    {
        return $this->server->get('SCRIPT_NAME', $this->server->get('ORIG_SCRIPT_NAME', ''));
    }
    /**
     * Returns the path being requested relative to the executed script.
     *
     * The path info always starts with a /.
     *
     * Suppose this request is instantiated from /mysite on localhost:
     *
     *  * http://localhost/mysite              returns an empty string
     *  * http://localhost/mysite/about        returns '/about'
     *  * http://localhost/mysite/enco%20ded   returns '/enco%20ded'
     *  * http://localhost/mysite/about?var=1  returns '/about'
     *
     * @return string The raw path (i.e. not urldecoded)
     */
    public function getPathInfo()
    {
        if (null === $this->pathInfo) {
            $this->pathInfo = $this->preparePathInfo();
        }
        return $this->pathInfo;
    }
    /**
     * Returns the root path from which this request is executed.
     *
     * Suppose that an index.php file instantiates this request object:
     *
     *  * http://localhost/index.php         returns an empty string
     *  * http://localhost/index.php/page    returns an empty string
     *  * http://localhost/web/index.php     returns '/web'
     *  * http://localhost/we%20b/index.php  returns '/we%20b'
     *
     * @return string The raw path (i.e. not urldecoded)
     */
    public function getBasePath()
    {
        if (null === $this->basePath) {
            $this->basePath = $this->prepareBasePath();
        }
        return $this->basePath;
    }
    /**
     * Returns the root URL from which this request is executed.
     *
     * The base URL never ends with a /.
     *
     * This is similar to getBasePath(), except that it also includes the
     * script filename (e.g. index.php) if one exists.
     *
     * @return string The raw URL (i.e. not urldecoded)
     */
    public function getBaseUrl()
    {
        if (null === $this->baseUrl) {
            $this->baseUrl = $this->prepareBaseUrl();
        }
        return $this->baseUrl;
    }
    /**
     * Gets the request's scheme.
     *
     * @return string
     */
    public function getScheme()
    {
        return $this->isSecure() ? 'https' : 'http';
    }
    /**
     * Returns the port on which the request is made.
     *
     * This method can read the client port from the "X-Forwarded-Port" header
     * when trusted proxies were set via "setTrustedProxies()".
     *
     * The "X-Forwarded-Port" header must contain the client port.
     *
     * If your reverse proxy uses a different header name than "X-Forwarded-Port",
     * configure it via via the $trustedHeaderSet argument of the
     * Request::setTrustedProxies() method instead.
     *
     * @return int|string can be a string if fetched from the server bag
     */
    public function getPort()
    {
        if ($this->isFromTrustedProxy() && ($host = $this->getTrustedValues(self::HEADER_CLIENT_PORT))) {
            $host = $host[0];
        } elseif ($this->isFromTrustedProxy() && ($host = $this->getTrustedValues(self::HEADER_CLIENT_HOST))) {
            $host = $host[0];
        } elseif (!($host = $this->headers->get('HOST'))) {
            return $this->server->get('SERVER_PORT');
        }
        if ('[' === $host[0]) {
            $pos = strpos($host, ':', strrpos($host, ']'));
        } else {
            $pos = strrpos($host, ':');
        }
        if (false !== $pos && ($port = substr($host, $pos + 1))) {
            return (int) $port;
        }
        return 'https' === $this->getScheme() ? 443 : 80;
    }
    /**
     * Returns the user.
     *
     * @return string|null
     */
    public function getUser()
    {
        return $this->headers->get('PHP_AUTH_USER');
    }
    /**
     * Returns the password.
     *
     * @return string|null
     */
    public function getPassword()
    {
        return $this->headers->get('PHP_AUTH_PW');
    }
    /**
     * Gets the user info.
     *
     * @return string A user name and, optionally, scheme-specific information about how to gain authorization to access the server
     */
    public function getUserInfo()
    {
        $userinfo = $this->getUser();
        $pass = $this->getPassword();
        if ('' != $pass) {
            $userinfo .= ":{$pass}";
        }
        return $userinfo;
    }
    /**
     * Returns the HTTP host being requested.
     *
     * The port name will be appended to the host if it's non-standard.
     *
     * @return string
     */
    public function getHttpHost()
    {
        $scheme = $this->getScheme();
        $port = $this->getPort();
        if ('http' == $scheme && 80 == $port || 'https' == $scheme && 443 == $port) {
            return $this->getHost();
        }
        return $this->getHost() . ':' . $port;
    }
    /**
     * Returns the requested URI (path and query string).
     *
     * @return string The raw URI (i.e. not URI decoded)
     */
    public function getRequestUri()
    {
        if (null === $this->requestUri) {
            $this->requestUri = $this->prepareRequestUri();
        }
        return $this->requestUri;
    }
    /**
     * Gets the scheme and HTTP host.
     *
     * If the URL was called with basic authentication, the user
     * and the password are not added to the generated string.
     *
     * @return string The scheme and HTTP host
     */
    public function getSchemeAndHttpHost()
    {
        return $this->getScheme() . '://' . $this->getHttpHost();
    }
    /**
     * Generates a normalized URI (URL) for the Request.
     *
     * @return string A normalized URI (URL) for the Request
     *
     * @see getQueryString()
     */
    public function getUri()
    {
        if (null !== ($qs = $this->getQueryString())) {
            $qs = '?' . $qs;
        }
        return $this->getSchemeAndHttpHost() . $this->getBaseUrl() . $this->getPathInfo() . $qs;
    }
    /**
     * Generates a normalized URI for the given path.
     *
     * @param string $path A path to use instead of the current one
     *
     * @return string The normalized URI for the path
     */
    public function getUriForPath($path)
    {
        return $this->getSchemeAndHttpHost() . $this->getBaseUrl() . $path;
    }
    /**
     * Returns the path as relative reference from the current Request path.
     *
     * Only the URIs path component (no schema, host etc.) is relevant and must be given.
     * Both paths must be absolute and not contain relative parts.
     * Relative URLs from one resource to another are useful when generating self-contained downloadable document archives.
     * Furthermore, they can be used to reduce the link size in documents.
     *
     * Example target paths, given a base path of "/a/b/c/d":
     * - "/a/b/c/d"     -> ""
     * - "/a/b/c/"      -> "./"
     * - "/a/b/"        -> "../"
     * - "/a/b/c/other" -> "other"
     * - "/a/x/y"       -> "../../x/y"
     *
     * @param string $path The target path
     *
     * @return string The relative target path
     */
    public function getRelativeUriForPath($path)
    {
        // be sure that we are dealing with an absolute path
        if (!isset($path[0]) || '/' !== $path[0]) {
            return $path;
        }
        if ($path === ($basePath = $this->getPathInfo())) {
            return '';
        }
        $sourceDirs = explode('/', isset($basePath[0]) && '/' === $basePath[0] ? substr($basePath, 1) : $basePath);
        $targetDirs = explode('/', substr($path, 1));
        array_pop($sourceDirs);
        $targetFile = array_pop($targetDirs);
        foreach ($sourceDirs as $i => $dir) {
            if (isset($targetDirs[$i]) && $dir === $targetDirs[$i]) {
                unset($sourceDirs[$i], $targetDirs[$i]);
            } else {
                break;
            }
        }
        $targetDirs[] = $targetFile;
        $path = str_repeat('../', \count($sourceDirs)) . implode('/', $targetDirs);
        // A reference to the same base directory or an empty subdirectory must be prefixed with "./".
        // This also applies to a segment with a colon character (e.g., "file:colon") that cannot be used
        // as the first segment of a relative-path reference, as it would be mistaken for a scheme name
        // (see https://tools.ietf.org/html/rfc3986#section-4.2).
        return !isset($path[0]) || '/' === $path[0] || false !== ($colonPos = strpos($path, ':')) && ($colonPos < ($slashPos = strpos($path, '/')) || false === $slashPos) ? "./{$path}" : $path;
    }
    /**
     * Generates the normalized query string for the Request.
     *
     * It builds a normalized query string, where keys/value pairs are alphabetized
     * and have consistent escaping.
     *
     * @return string|null A normalized query string for the Request
     */
    public function getQueryString()
    {
        $qs = static::normalizeQueryString($this->server->get('QUERY_STRING'));
        return '' === $qs ? null : $qs;
    }
    /**
     * Checks whether the request is secure or not.
     *
     * This method can read the client protocol from the "X-Forwarded-Proto" header
     * when trusted proxies were set via "setTrustedProxies()".
     *
     * The "X-Forwarded-Proto" header must contain the protocol: "https" or "http".
     *
     * If your reverse proxy uses a different header name than "X-Forwarded-Proto"
     * ("SSL_HTTPS" for instance), configure it via the $trustedHeaderSet
     * argument of the Request::setTrustedProxies() method instead.
     *
     * @return bool
     */
    public function isSecure()
    {
        if ($this->isFromTrustedProxy() && ($proto = $this->getTrustedValues(self::HEADER_CLIENT_PROTO))) {
            return \in_array(strtolower($proto[0]), ['https', 'on', 'ssl', '1'], true);
        }
        $https = $this->server->get('HTTPS');
        return !empty($https) && 'off' !== strtolower($https);
    }
    /**
     * Returns the host name.
     *
     * This method can read the client host name from the "X-Forwarded-Host" header
     * when trusted proxies were set via "setTrustedProxies()".
     *
     * The "X-Forwarded-Host" header must contain the client host name.
     *
     * If your reverse proxy uses a different header name than "X-Forwarded-Host",
     * configure it via the $trustedHeaderSet argument of the
     * Request::setTrustedProxies() method instead.
     *
     * @return string
     *
     * @throws SuspiciousOperationException when the host name is invalid or not trusted
     */
    public function getHost()
    {
        if ($this->isFromTrustedProxy() && ($host = $this->getTrustedValues(self::HEADER_CLIENT_HOST))) {
            $host = $host[0];
        } elseif (!($host = $this->headers->get('HOST'))) {
            if (!($host = $this->server->get('SERVER_NAME'))) {
                $host = $this->server->get('SERVER_ADDR', '');
            }
        }
        // trim and remove port number from host
        // host is lowercase as per RFC 952/2181
        $host = strtolower(preg_replace('/:\\d+$/', '', trim($host)));
        // as the host can come from the user (HTTP_HOST and depending on the configuration, SERVER_NAME too can come from the user)
        // check that it does not contain forbidden characters (see RFC 952 and RFC 2181)
        // use preg_replace() instead of preg_match() to prevent DoS attacks with long host names
        if ($host && '' !== preg_replace('/(?:^\\[)?[a-zA-Z0-9-:\\]_]+\\.?/', '', $host)) {
            if (!$this->isHostValid) {
                return '';
            }
            $this->isHostValid = false;
            throw new SuspiciousOperationException(sprintf('Invalid Host "%s".', $host));
        }
        if (\count(self::$trustedHostPatterns) > 0) {
            // to avoid host header injection attacks, you should provide a list of trusted host patterns
            if (\in_array($host, self::$trustedHosts)) {
                return $host;
            }
            foreach (self::$trustedHostPatterns as $pattern) {
                if (preg_match($pattern, $host)) {
                    self::$trustedHosts[] = $host;
                    return $host;
                }
            }
            if (!$this->isHostValid) {
                return '';
            }
            $this->isHostValid = false;
            throw new SuspiciousOperationException(sprintf('Untrusted Host "%s".', $host));
        }
        return $host;
    }
    /**
     * Sets the request method.
     *
     * @param string $method
     */
    public function setMethod($method)
    {
        $this->method = null;
        $this->server->set('REQUEST_METHOD', $method);
    }
    /**
     * Gets the request "intended" method.
     *
     * If the X-HTTP-Method-Override header is set, and if the method is a POST,
     * then it is used to determine the "real" intended HTTP method.
     *
     * The _method request parameter can also be used to determine the HTTP method,
     * but only if enableHttpMethodParameterOverride() has been called.
     *
     * The method is always an uppercased string.
     *
     * @return string The request method
     *
     * @see getRealMethod()
     */
    public function getMethod()
    {
        if (null !== $this->method) {
            return $this->method;
        }
        $this->method = strtoupper($this->server->get('REQUEST_METHOD', 'GET'));
        if ('POST' !== $this->method) {
            return $this->method;
        }
        $method = $this->headers->get('X-HTTP-METHOD-OVERRIDE');
        if (!$method && self::$httpMethodParameterOverride) {
            $method = $this->request->get('_method', $this->query->get('_method', 'POST'));
        }
        if (!\is_string($method)) {
            return $this->method;
        }
        $method = strtoupper($method);
        if (\in_array($method, ['GET', 'HEAD', 'POST', 'PUT', 'DELETE', 'CONNECT', 'OPTIONS', 'PATCH', 'PURGE', 'TRACE'], true)) {
            return $this->method = $method;
        }
        if (!preg_match('/^[A-Z]++$/D', $method)) {
            throw new SuspiciousOperationException(sprintf('Invalid method override "%s".', $method));
        }
        return $this->method = $method;
    }
    /**
     * Gets the "real" request method.
     *
     * @return string The request method
     *
     * @see getMethod()
     */
    public function getRealMethod()
    {
        return strtoupper($this->server->get('REQUEST_METHOD', 'GET'));
    }
    /**
     * Gets the mime type associated with the format.
     *
     * @param string $format The format
     *
     * @return string|null The associated mime type (null if not found)
     */
    public function getMimeType($format)
    {
        if (null === static::$formats) {
            static::initializeFormats();
        }
        return isset(static::$formats[$format]) ? static::$formats[$format][0] : null;
    }
    /**
     * Gets the mime types associated with the format.
     *
     * @param string $format The format
     *
     * @return array The associated mime types
     */
    public static function getMimeTypes($format)
    {
        if (null === static::$formats) {
            static::initializeFormats();
        }
        return isset(static::$formats[$format]) ? static::$formats[$format] : [];
    }
    /**
     * Gets the format associated with the mime type.
     *
     * @param string $mimeType The associated mime type
     *
     * @return string|null The format (null if not found)
     */
    public function getFormat($mimeType)
    {
        $canonicalMimeType = null;
        if (false !== ($pos = strpos($mimeType, ';'))) {
            $canonicalMimeType = trim(substr($mimeType, 0, $pos));
        }
        if (null === static::$formats) {
            static::initializeFormats();
        }
        foreach (static::$formats as $format => $mimeTypes) {
            if (\in_array($mimeType, (array) $mimeTypes)) {
                return $format;
            }
            if (null !== $canonicalMimeType && \in_array($canonicalMimeType, (array) $mimeTypes)) {
                return $format;
            }
        }
        return null;
    }
    /**
     * Associates a format with mime types.
     *
     * @param string       $format    The format
     * @param string|array $mimeTypes The associated mime types (the preferred one must be the first as it will be used as the content type)
     */
    public function setFormat($format, $mimeTypes)
    {
        if (null === static::$formats) {
            static::initializeFormats();
        }
        static::$formats[$format] = \is_array($mimeTypes) ? $mimeTypes : [$mimeTypes];
    }
    /**
     * Gets the request format.
     *
     * Here is the process to determine the format:
     *
     *  * format defined by the user (with setRequestFormat())
     *  * _format request attribute
     *  * $default
     *
     * @param string|null $default The default format
     *
     * @return string|null The request format
     */
    public function getRequestFormat($default = 'html')
    {
        if (null === $this->format) {
            $this->format = $this->attributes->get('_format');
        }
        return null === $this->format ? $default : $this->format;
    }
    /**
     * Sets the request format.
     *
     * @param string $format The request format
     */
    public function setRequestFormat($format)
    {
        $this->format = $format;
    }
    /**
     * Gets the format associated with the request.
     *
     * @return string|null The format (null if no content type is present)
     */
    public function getContentType()
    {
        return $this->getFormat($this->headers->get('CONTENT_TYPE'));
    }
    /**
     * Sets the default locale.
     *
     * @param string $locale
     */
    public function setDefaultLocale($locale)
    {
        $this->defaultLocale = $locale;
        if (null === $this->locale) {
            $this->setPhpDefaultLocale($locale);
        }
    }
    /**
     * Get the default locale.
     *
     * @return string
     */
    public function getDefaultLocale()
    {
        return $this->defaultLocale;
    }
    /**
     * Sets the locale.
     *
     * @param string $locale
     */
    public function setLocale($locale)
    {
        $this->setPhpDefaultLocale($this->locale = $locale);
    }
    /**
     * Get the locale.
     *
     * @return string
     */
    public function getLocale()
    {
        return null === $this->locale ? $this->defaultLocale : $this->locale;
    }
    /**
     * Checks if the request method is of specified type.
     *
     * @param string $method Uppercase request method (GET, POST etc)
     *
     * @return bool
     */
    public function isMethod($method)
    {
        return $this->getMethod() === strtoupper($method);
    }
    /**
     * Checks whether or not the method is safe.
     *
     * @see https://tools.ietf.org/html/rfc7231#section-4.2.1
     *
     * @param bool $andCacheable Adds the additional condition that the method should be cacheable. True by default.
     *
     * @return bool
     */
    public function isMethodSafe()
    {
        if (!\func_num_args() || func_get_arg(0)) {
            // This deprecation should be turned into a BadMethodCallException in 4.0 (without adding the argument in the signature)
            // then setting $andCacheable to false should be deprecated in 4.1
            @trigger_error('Checking only for cacheable HTTP methods with Symfony\\Component\\HttpFoundation\\Request::isMethodSafe() is deprecated since Symfony 3.2 and will throw an exception in 4.0. Disable checking only for cacheable methods by calling the method with `false` as first argument or use the Request::isMethodCacheable() instead.', E_USER_DEPRECATED);
            return \in_array($this->getMethod(), ['GET', 'HEAD']);
        }
        return \in_array($this->getMethod(), ['GET', 'HEAD', 'OPTIONS', 'TRACE']);
    }
    /**
     * Checks whether or not the method is idempotent.
     *
     * @return bool
     */
    public function isMethodIdempotent()
    {
        return \in_array($this->getMethod(), ['HEAD', 'GET', 'PUT', 'DELETE', 'TRACE', 'OPTIONS', 'PURGE']);
    }
    /**
     * Checks whether the method is cacheable or not.
     *
     * @see https://tools.ietf.org/html/rfc7231#section-4.2.3
     *
     * @return bool True for GET and HEAD, false otherwise
     */
    public function isMethodCacheable()
    {
        return \in_array($this->getMethod(), ['GET', 'HEAD']);
    }
    /**
     * Returns the protocol version.
     *
     * If the application is behind a proxy, the protocol version used in the
     * requests between the client and the proxy and between the proxy and the
     * server might be different. This returns the former (from the "Via" header)
     * if the proxy is trusted (see "setTrustedProxies()"), otherwise it returns
     * the latter (from the "SERVER_PROTOCOL" server parameter).
     *
     * @return string
     */
    public function getProtocolVersion()
    {
        if ($this->isFromTrustedProxy()) {
            preg_match('~^(HTTP/)?([1-9]\\.[0-9]) ~', $this->headers->get('Via'), $matches);
            if ($matches) {
                return 'HTTP/' . $matches[2];
            }
        }
        return $this->server->get('SERVER_PROTOCOL');
    }
    /**
     * Returns the request body content.
     *
     * @param bool $asResource If true, a resource will be returned
     *
     * @return string|resource The request body content or a resource to read the body stream
     *
     * @throws \LogicException
     */
    public function getContent($asResource = false)
    {
        $currentContentIsResource = \is_resource($this->content);
        if (\PHP_VERSION_ID < 50600 && false === $this->content) {
            throw new \LogicException('getContent() can only be called once when using the resource return type and PHP below 5.6.');
        }
        if (true === $asResource) {
            if ($currentContentIsResource) {
                rewind($this->content);
                return $this->content;
            }
            // Content passed in parameter (test)
            if (\is_string($this->content)) {
                $resource = fopen('php://temp', 'r+');
                fwrite($resource, $this->content);
                rewind($resource);
                return $resource;
            }
            $this->content = false;
            return fopen('php://input', 'rb');
        }
        if ($currentContentIsResource) {
            rewind($this->content);
            return stream_get_contents($this->content);
        }
        if (null === $this->content || false === $this->content) {
            $this->content = file_get_contents('php://input');
        }
        return $this->content;
    }
    /**
     * Gets the Etags.
     *
     * @return array The entity tags
     */
    public function getETags()
    {
        return preg_split('/\\s*,\\s*/', $this->headers->get('if_none_match'), null, PREG_SPLIT_NO_EMPTY);
    }
    /**
     * @return bool
     */
    public function isNoCache()
    {
        return $this->headers->hasCacheControlDirective('no-cache') || 'no-cache' == $this->headers->get('Pragma');
    }
    /**
     * Returns the preferred language.
     *
     * @param array $locales An array of ordered available locales
     *
     * @return string|null The preferred locale
     */
    public function getPreferredLanguage(array $locales = null)
    {
        $preferredLanguages = $this->getLanguages();
        if (empty($locales)) {
            return isset($preferredLanguages[0]) ? $preferredLanguages[0] : null;
        }
        if (!$preferredLanguages) {
            return $locales[0];
        }
        $extendedPreferredLanguages = [];
        foreach ($preferredLanguages as $language) {
            $extendedPreferredLanguages[] = $language;
            if (false !== ($position = strpos($language, '_'))) {
                $superLanguage = substr($language, 0, $position);
                if (!\in_array($superLanguage, $preferredLanguages)) {
                    $extendedPreferredLanguages[] = $superLanguage;
                }
            }
        }
        $preferredLanguages = array_values(array_intersect($extendedPreferredLanguages, $locales));
        return isset($preferredLanguages[0]) ? $preferredLanguages[0] : $locales[0];
    }
    /**
     * Gets a list of languages acceptable by the client browser.
     *
     * @return array Languages ordered in the user browser preferences
     */
    public function getLanguages()
    {
        if (null !== $this->languages) {
            return $this->languages;
        }
        $languages = AcceptHeader::fromString($this->headers->get('Accept-Language'))->all();
        $this->languages = [];
        foreach ($languages as $lang => $acceptHeaderItem) {
            if (false !== strpos($lang, '-')) {
                $codes = explode('-', $lang);
                if ('i' === $codes[0]) {
                    // Language not listed in ISO 639 that are not variants
                    // of any listed language, which can be registered with the
                    // i-prefix, such as i-cherokee
                    if (\count($codes) > 1) {
                        $lang = $codes[1];
                    }
                } else {
                    for ($i = 0, $max = \count($codes); $i < $max; ++$i) {
                        if (0 === $i) {
                            $lang = strtolower($codes[0]);
                        } else {
                            $lang .= '_' . strtoupper($codes[$i]);
                        }
                    }
                }
            }
            $this->languages[] = $lang;
        }
        return $this->languages;
    }
    /**
     * Gets a list of charsets acceptable by the client browser.
     *
     * @return array List of charsets in preferable order
     */
    public function getCharsets()
    {
        if (null !== $this->charsets) {
            return $this->charsets;
        }
        return $this->charsets = array_keys(AcceptHeader::fromString($this->headers->get('Accept-Charset'))->all());
    }
    /**
     * Gets a list of encodings acceptable by the client browser.
     *
     * @return array List of encodings in preferable order
     */
    public function getEncodings()
    {
        if (null !== $this->encodings) {
            return $this->encodings;
        }
        return $this->encodings = array_keys(AcceptHeader::fromString($this->headers->get('Accept-Encoding'))->all());
    }
    /**
     * Gets a list of content types acceptable by the client browser.
     *
     * @return array List of content types in preferable order
     */
    public function getAcceptableContentTypes()
    {
        if (null !== $this->acceptableContentTypes) {
            return $this->acceptableContentTypes;
        }
        return $this->acceptableContentTypes = array_keys(AcceptHeader::fromString($this->headers->get('Accept'))->all());
    }
    /**
     * Returns true if the request is a XMLHttpRequest.
     *
     * It works if your JavaScript library sets an X-Requested-With HTTP header.
     * It is known to work with common JavaScript frameworks:
     *
     * @see https://wikipedia.org/wiki/List_of_Ajax_frameworks#JavaScript
     *
     * @return bool true if the request is an XMLHttpRequest, false otherwise
     */
    public function isXmlHttpRequest()
    {
        return 'XMLHttpRequest' == $this->headers->get('X-Requested-With');
    }
    /*
     * The following methods are derived from code of the Zend Framework (1.10dev - 2010-01-24)
     *
     * Code subject to the new BSD license (https://framework.zend.com/license).
     *
     * Copyright (c) 2005-2010 Zend Technologies USA Inc. (https://www.zend.com/)
     */
    protected function prepareRequestUri()
    {
        $requestUri = '';
        if ('1' == $this->server->get('IIS_WasUrlRewritten') && '' != $this->server->get('UNENCODED_URL')) {
            // IIS7 with URL Rewrite: make sure we get the unencoded URL (double slash problem)
            $requestUri = $this->server->get('UNENCODED_URL');
            $this->server->remove('UNENCODED_URL');
            $this->server->remove('IIS_WasUrlRewritten');
        } elseif ($this->server->has('REQUEST_URI')) {
            $requestUri = $this->server->get('REQUEST_URI');
            if ('' !== $requestUri && '/' === $requestUri[0]) {
                // To only use path and query remove the fragment.
                if (false !== ($pos = strpos($requestUri, '#'))) {
                    $requestUri = substr($requestUri, 0, $pos);
                }
            } else {
                // HTTP proxy reqs setup request URI with scheme and host [and port] + the URL path,
                // only use URL path.
                $uriComponents = parse_url($requestUri);
                if (isset($uriComponents['path'])) {
                    $requestUri = $uriComponents['path'];
                }
                if (isset($uriComponents['query'])) {
                    $requestUri .= '?' . $uriComponents['query'];
                }
            }
        } elseif ($this->server->has('ORIG_PATH_INFO')) {
            // IIS 5.0, PHP as CGI
            $requestUri = $this->server->get('ORIG_PATH_INFO');
            if ('' != $this->server->get('QUERY_STRING')) {
                $requestUri .= '?' . $this->server->get('QUERY_STRING');
            }
            $this->server->remove('ORIG_PATH_INFO');
        }
        // normalize the request URI to ease creating sub-requests from this request
        $this->server->set('REQUEST_URI', $requestUri);
        return $requestUri;
    }
    /**
     * Prepares the base URL.
     *
     * @return string
     */
    protected function prepareBaseUrl()
    {
        $filename = basename($this->server->get('SCRIPT_FILENAME'));
        if (basename($this->server->get('SCRIPT_NAME')) === $filename) {
            $baseUrl = $this->server->get('SCRIPT_NAME');
        } elseif (basename($this->server->get('PHP_SELF')) === $filename) {
            $baseUrl = $this->server->get('PHP_SELF');
        } elseif (basename($this->server->get('ORIG_SCRIPT_NAME')) === $filename) {
            $baseUrl = $this->server->get('ORIG_SCRIPT_NAME');
            // 1and1 shared hosting compatibility
        } else {
            // Backtrack up the script_filename to find the portion matching
            // php_self
            $path = $this->server->get('PHP_SELF', '');
            $file = $this->server->get('SCRIPT_FILENAME', '');
            $segs = explode('/', trim($file, '/'));
            $segs = array_reverse($segs);
            $index = 0;
            $last = \count($segs);
            $baseUrl = '';
            do {
                $seg = $segs[$index];
                $baseUrl = '/' . $seg . $baseUrl;
                ++$index;
            } while ($last > $index && false !== ($pos = strpos($path, $baseUrl)) && 0 != $pos);
        }
        // Does the baseUrl have anything in common with the request_uri?
        $requestUri = $this->getRequestUri();
        if ('' !== $requestUri && '/' !== $requestUri[0]) {
            $requestUri = '/' . $requestUri;
        }
        if ($baseUrl && false !== ($prefix = $this->getUrlencodedPrefix($requestUri, $baseUrl))) {
            // full $baseUrl matches
            return $prefix;
        }
        if ($baseUrl && false !== ($prefix = $this->getUrlencodedPrefix($requestUri, rtrim(\dirname($baseUrl), '/' . \DIRECTORY_SEPARATOR) . '/'))) {
            // directory portion of $baseUrl matches
            return rtrim($prefix, '/' . \DIRECTORY_SEPARATOR);
        }
        $truncatedRequestUri = $requestUri;
        if (false !== ($pos = strpos($requestUri, '?'))) {
            $truncatedRequestUri = substr($requestUri, 0, $pos);
        }
        $basename = basename($baseUrl);
        if (empty($basename) || !strpos(rawurldecode($truncatedRequestUri), $basename)) {
            // no match whatsoever; set it blank
            return '';
        }
        // If using mod_rewrite or ISAPI_Rewrite strip the script filename
        // out of baseUrl. $pos !== 0 makes sure it is not matching a value
        // from PATH_INFO or QUERY_STRING
        if (\strlen($requestUri) >= \strlen($baseUrl) && false !== ($pos = strpos($requestUri, $baseUrl)) && 0 !== $pos) {
            $baseUrl = substr($requestUri, 0, $pos + \strlen($baseUrl));
        }
        return rtrim($baseUrl, '/' . \DIRECTORY_SEPARATOR);
    }
    /**
     * Prepares the base path.
     *
     * @return string base path
     */
    protected function prepareBasePath()
    {
        $baseUrl = $this->getBaseUrl();
        if (empty($baseUrl)) {
            return '';
        }
        $filename = basename($this->server->get('SCRIPT_FILENAME'));
        if (basename($baseUrl) === $filename) {
            $basePath = \dirname($baseUrl);
        } else {
            $basePath = $baseUrl;
        }
        if ('\\' === \DIRECTORY_SEPARATOR) {
            $basePath = str_replace('\\', '/', $basePath);
        }
        return rtrim($basePath, '/');
    }
    /**
     * Prepares the path info.
     *
     * @return string path info
     */
    protected function preparePathInfo()
    {
        if (null === ($requestUri = $this->getRequestUri())) {
            return '/';
        }
        // Remove the query string from REQUEST_URI
        if (false !== ($pos = strpos($requestUri, '?'))) {
            $requestUri = substr($requestUri, 0, $pos);
        }
        if ('' !== $requestUri && '/' !== $requestUri[0]) {
            $requestUri = '/' . $requestUri;
        }
        if (null === ($baseUrl = $this->getBaseUrl())) {
            return $requestUri;
        }
        $pathInfo = substr($requestUri, \strlen($baseUrl));
        if (false === $pathInfo || '' === $pathInfo) {
            // If substr() returns false then PATH_INFO is set to an empty string
            return '/';
        }
        return (string) $pathInfo;
    }
    /**
     * Initializes HTTP request formats.
     */
    protected static function initializeFormats()
    {
        static::$formats = ['html' => ['text/html', 'application/xhtml+xml'], 'txt' => ['text/plain'], 'js' => ['application/javascript', 'application/x-javascript', 'text/javascript'], 'css' => ['text/css'], 'json' => ['application/json', 'application/x-json'], 'jsonld' => ['application/ld+json'], 'xml' => ['text/xml', 'application/xml', 'application/x-xml'], 'rdf' => ['application/rdf+xml'], 'atom' => ['application/atom+xml'], 'rss' => ['application/rss+xml'], 'form' => ['application/x-www-form-urlencoded']];
    }
    /**
     * Sets the default PHP locale.
     *
     * @param string $locale
     */
    private function setPhpDefaultLocale($locale)
    {
        // if either the class Locale doesn't exist, or an exception is thrown when
        // setting the default locale, the intl module is not installed, and
        // the call can be ignored:
        try {
            if (class_exists('Locale', false)) {
                \Locale::setDefault($locale);
            }
        } catch (\Exception $e) {
        }
    }
    /**
     * Returns the prefix as encoded in the string when the string starts with
     * the given prefix, false otherwise.
     *
     * @param string $string The urlencoded string
     * @param string $prefix The prefix not encoded
     *
     * @return string|false The prefix as it is encoded in $string, or false
     */
    private function getUrlencodedPrefix($string, $prefix)
    {
        if (0 !== strpos(rawurldecode($string), $prefix)) {
            return false;
        }
        $len = \strlen($prefix);
        if (preg_match(sprintf('#^(%%[[:xdigit:]]{2}|.){%d}#', $len), $string, $match)) {
            return $match[0];
        }
        return false;
    }
    private static function createRequestFromFactory(array $query = [], array $request = [], array $attributes = [], array $cookies = [], array $files = [], array $server = [], $content = null)
    {
        if (self::$requestFactory) {
            $request = \call_user_func(self::$requestFactory, $query, $request, $attributes, $cookies, $files, $server, $content);
            if (!$request instanceof self) {
                throw new \LogicException('The Request factory must return an instance of Symfony\\Component\\HttpFoundation\\Request.');
            }
            return $request;
        }
        return new static($query, $request, $attributes, $cookies, $files, $server, $content);
    }
    /**
     * Indicates whether this request originated from a trusted proxy.
     *
     * This can be useful to determine whether or not to trust the
     * contents of a proxy-specific header.
     *
     * @return bool true if the request came from a trusted proxy, false otherwise
     */
    public function isFromTrustedProxy()
    {
        return self::$trustedProxies && IpUtils::checkIp($this->server->get('REMOTE_ADDR'), self::$trustedProxies);
    }
    private function getTrustedValues($type, $ip = null)
    {
        $clientValues = [];
        $forwardedValues = [];
        if (self::$trustedHeaders[$type] && $this->headers->has(self::$trustedHeaders[$type])) {
            foreach (explode(',', $this->headers->get(self::$trustedHeaders[$type])) as $v) {
                $clientValues[] = (self::HEADER_CLIENT_PORT === $type ? '0.0.0.0:' : '') . trim($v);
            }
        }
        if (self::$trustedHeaders[self::HEADER_FORWARDED] && $this->headers->has(self::$trustedHeaders[self::HEADER_FORWARDED])) {
            $forwardedValues = $this->headers->get(self::$trustedHeaders[self::HEADER_FORWARDED]);
            $forwardedValues = preg_match_all(sprintf('{(?:%s)="?([a-zA-Z0-9\\.:_\\-/\\[\\]]*+)}', self::$forwardedParams[$type]), $forwardedValues, $matches) ? $matches[1] : [];
            if (self::HEADER_CLIENT_PORT === $type) {
                foreach ($forwardedValues as $k => $v) {
                    if (']' === substr($v, -1) || false === ($v = strrchr($v, ':'))) {
                        $v = $this->isSecure() ? ':443' : ':80';
                    }
                    $forwardedValues[$k] = '0.0.0.0' . $v;
                }
            }
        }
        if (null !== $ip) {
            $clientValues = $this->normalizeAndFilterClientIps($clientValues, $ip);
            $forwardedValues = $this->normalizeAndFilterClientIps($forwardedValues, $ip);
        }
        if ($forwardedValues === $clientValues || !$clientValues) {
            return $forwardedValues;
        }
        if (!$forwardedValues) {
            return $clientValues;
        }
        if (!$this->isForwardedValid) {
            return null !== $ip ? ['0.0.0.0', $ip] : [];
        }
        $this->isForwardedValid = false;
        throw new ConflictingHeadersException(sprintf('The request has both a trusted "%s" header and a trusted "%s" header, conflicting with each other. You should either configure your proxy to remove one of them, or configure your project to distrust the offending one.', self::$trustedHeaders[self::HEADER_FORWARDED], self::$trustedHeaders[$type]));
    }
    private function normalizeAndFilterClientIps(array $clientIps, $ip)
    {
        if (!$clientIps) {
            return [];
        }
        $clientIps[] = $ip;
        // Complete the IP chain with the IP the request actually came from
        $firstTrustedIp = null;
        foreach ($clientIps as $key => $clientIp) {
            if (strpos($clientIp, '.')) {
                // Strip :port from IPv4 addresses. This is allowed in Forwarded
                // and may occur in X-Forwarded-For.
                $i = strpos($clientIp, ':');
                if ($i) {
                    $clientIps[$key] = $clientIp = substr($clientIp, 0, $i);
                }
            } elseif (0 === strpos($clientIp, '[')) {
                // Strip brackets and :port from IPv6 addresses.
                $i = strpos($clientIp, ']', 1);
                $clientIps[$key] = $clientIp = substr($clientIp, 1, $i - 1);
            }
            if (!filter_var($clientIp, FILTER_VALIDATE_IP)) {
                unset($clientIps[$key]);
                continue;
            }
            if (IpUtils::checkIp($clientIp, self::$trustedProxies)) {
                unset($clientIps[$key]);
                // Fallback to this when the client IP falls into the range of trusted proxies
                if (null === $firstTrustedIp) {
                    $firstTrustedIp = $clientIp;
                }
            }
        }
        // Now the IP chain contains only untrusted proxies and the client IP
        return $clientIps ? array_reverse($clientIps) : [$firstTrustedIp];
    }
}
}

namespace Symfony\Component\HttpFoundation {
/**
 * ParameterBag is a container for key/value pairs.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class ParameterBag implements \IteratorAggregate, \Countable
{
    /**
     * Parameter storage.
     */
    protected $parameters;
    /**
     * @param array $parameters An array of parameters
     */
    public function __construct(array $parameters = [])
    {
        $this->parameters = $parameters;
    }
    /**
     * Returns the parameters.
     *
     * @return array An array of parameters
     */
    public function all()
    {
        return $this->parameters;
    }
    /**
     * Returns the parameter keys.
     *
     * @return array An array of parameter keys
     */
    public function keys()
    {
        return array_keys($this->parameters);
    }
    /**
     * Replaces the current parameters by a new set.
     *
     * @param array $parameters An array of parameters
     */
    public function replace(array $parameters = [])
    {
        $this->parameters = $parameters;
    }
    /**
     * Adds parameters.
     *
     * @param array $parameters An array of parameters
     */
    public function add(array $parameters = [])
    {
        $this->parameters = array_replace($this->parameters, $parameters);
    }
    /**
     * Returns a parameter by name.
     *
     * @param string $key     The key
     * @param mixed  $default The default value if the parameter key does not exist
     *
     * @return mixed
     */
    public function get($key, $default = null)
    {
        return \array_key_exists($key, $this->parameters) ? $this->parameters[$key] : $default;
    }
    /**
     * Sets a parameter by name.
     *
     * @param string $key   The key
     * @param mixed  $value The value
     */
    public function set($key, $value)
    {
        $this->parameters[$key] = $value;
    }
    /**
     * Returns true if the parameter is defined.
     *
     * @param string $key The key
     *
     * @return bool true if the parameter exists, false otherwise
     */
    public function has($key)
    {
        return \array_key_exists($key, $this->parameters);
    }
    /**
     * Removes a parameter.
     *
     * @param string $key The key
     */
    public function remove($key)
    {
        unset($this->parameters[$key]);
    }
    /**
     * Returns the alphabetic characters of the parameter value.
     *
     * @param string $key     The parameter key
     * @param string $default The default value if the parameter key does not exist
     *
     * @return string The filtered value
     */
    public function getAlpha($key, $default = '')
    {
        return preg_replace('/[^[:alpha:]]/', '', $this->get($key, $default));
    }
    /**
     * Returns the alphabetic characters and digits of the parameter value.
     *
     * @param string $key     The parameter key
     * @param string $default The default value if the parameter key does not exist
     *
     * @return string The filtered value
     */
    public function getAlnum($key, $default = '')
    {
        return preg_replace('/[^[:alnum:]]/', '', $this->get($key, $default));
    }
    /**
     * Returns the digits of the parameter value.
     *
     * @param string $key     The parameter key
     * @param string $default The default value if the parameter key does not exist
     *
     * @return string The filtered value
     */
    public function getDigits($key, $default = '')
    {
        // we need to remove - and + because they're allowed in the filter
        return str_replace(['-', '+'], '', $this->filter($key, $default, FILTER_SANITIZE_NUMBER_INT));
    }
    /**
     * Returns the parameter value converted to integer.
     *
     * @param string $key     The parameter key
     * @param int    $default The default value if the parameter key does not exist
     *
     * @return int The filtered value
     */
    public function getInt($key, $default = 0)
    {
        return (int) $this->get($key, $default);
    }
    /**
     * Returns the parameter value converted to boolean.
     *
     * @param string $key     The parameter key
     * @param bool   $default The default value if the parameter key does not exist
     *
     * @return bool The filtered value
     */
    public function getBoolean($key, $default = false)
    {
        return $this->filter($key, $default, FILTER_VALIDATE_BOOLEAN);
    }
    /**
     * Filter key.
     *
     * @param string $key     Key
     * @param mixed  $default Default = null
     * @param int    $filter  FILTER_* constant
     * @param mixed  $options Filter options
     *
     * @see https://php.net/filter-var
     *
     * @return mixed
     */
    public function filter($key, $default = null, $filter = FILTER_DEFAULT, $options = [])
    {
        $value = $this->get($key, $default);
        // Always turn $options into an array - this allows filter_var option shortcuts.
        if (!\is_array($options) && $options) {
            $options = ['flags' => $options];
        }
        // Add a convenience check for arrays.
        if (\is_array($value) && !isset($options['flags'])) {
            $options['flags'] = FILTER_REQUIRE_ARRAY;
        }
        return filter_var($value, $filter, $options);
    }
    /**
     * Returns an iterator for parameters.
     *
     * @return \ArrayIterator An \ArrayIterator instance
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->parameters);
    }
    /**
     * Returns the number of parameters.
     *
     * @return int The number of parameters
     */
    public function count()
    {
        return \count($this->parameters);
    }
}
}

namespace Symfony\Component\HttpFoundation {
use Symfony\Component\HttpFoundation\File\UploadedFile;
/**
 * FileBag is a container for uploaded files.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 * @author Bulat Shakirzyanov <mallluhuct@gmail.com>
 */
class FileBag extends ParameterBag
{
    private static $fileKeys = ['error', 'name', 'size', 'tmp_name', 'type'];
    /**
     * @param array $parameters An array of HTTP files
     */
    public function __construct(array $parameters = [])
    {
        $this->replace($parameters);
    }
    /**
     * {@inheritdoc}
     */
    public function replace(array $files = [])
    {
        $this->parameters = [];
        $this->add($files);
    }
    /**
     * {@inheritdoc}
     */
    public function set($key, $value)
    {
        if (!\is_array($value) && !$value instanceof UploadedFile) {
            throw new \InvalidArgumentException('An uploaded file must be an array or an instance of UploadedFile.');
        }
        parent::set($key, $this->convertFileInformation($value));
    }
    /**
     * {@inheritdoc}
     */
    public function add(array $files = [])
    {
        foreach ($files as $key => $file) {
            $this->set($key, $file);
        }
    }
    /**
     * Converts uploaded files to UploadedFile instances.
     *
     * @param array|UploadedFile $file A (multi-dimensional) array of uploaded file information
     *
     * @return UploadedFile[]|UploadedFile|null A (multi-dimensional) array of UploadedFile instances
     */
    protected function convertFileInformation($file)
    {
        if ($file instanceof UploadedFile) {
            return $file;
        }
        if (\is_array($file)) {
            $file = $this->fixPhpFilesArray($file);
            $keys = array_keys($file);
            sort($keys);
            if ($keys == self::$fileKeys) {
                if (UPLOAD_ERR_NO_FILE == $file['error']) {
                    $file = null;
                } else {
                    $file = new UploadedFile($file['tmp_name'], $file['name'], $file['type'], $file['size'], $file['error']);
                }
            } else {
                $file = array_map([$this, 'convertFileInformation'], $file);
                if (array_keys($keys) === $keys) {
                    $file = array_filter($file);
                }
            }
        }
        return $file;
    }
    /**
     * Fixes a malformed PHP $_FILES array.
     *
     * PHP has a bug that the format of the $_FILES array differs, depending on
     * whether the uploaded file fields had normal field names or array-like
     * field names ("normal" vs. "parent[child]").
     *
     * This method fixes the array to look like the "normal" $_FILES array.
     *
     * It's safe to pass an already converted array, in which case this method
     * just returns the original array unmodified.
     *
     * @param array $data
     *
     * @return array
     */
    protected function fixPhpFilesArray($data)
    {
        $keys = array_keys($data);
        sort($keys);
        if (self::$fileKeys != $keys || !isset($data['name']) || !\is_array($data['name'])) {
            return $data;
        }
        $files = $data;
        foreach (self::$fileKeys as $k) {
            unset($files[$k]);
        }
        foreach ($data['name'] as $key => $name) {
            $files[$key] = $this->fixPhpFilesArray(['error' => $data['error'][$key], 'name' => $name, 'type' => $data['type'][$key], 'tmp_name' => $data['tmp_name'][$key], 'size' => $data['size'][$key]]);
        }
        return $files;
    }
}
}

namespace Symfony\Component\HttpFoundation {
/**
 * ServerBag is a container for HTTP headers from the $_SERVER variable.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 * @author Bulat Shakirzyanov <mallluhuct@gmail.com>
 * @author Robert Kiss <kepten@gmail.com>
 */
class ServerBag extends ParameterBag
{
    /**
     * Gets the HTTP headers.
     *
     * @return array
     */
    public function getHeaders()
    {
        $headers = [];
        $contentHeaders = ['CONTENT_LENGTH' => true, 'CONTENT_MD5' => true, 'CONTENT_TYPE' => true];
        foreach ($this->parameters as $key => $value) {
            if (0 === strpos($key, 'HTTP_')) {
                $headers[substr($key, 5)] = $value;
            } elseif (isset($contentHeaders[$key])) {
                $headers[$key] = $value;
            }
        }
        if (isset($this->parameters['PHP_AUTH_USER'])) {
            $headers['PHP_AUTH_USER'] = $this->parameters['PHP_AUTH_USER'];
            $headers['PHP_AUTH_PW'] = isset($this->parameters['PHP_AUTH_PW']) ? $this->parameters['PHP_AUTH_PW'] : '';
        } else {
            /*
             * php-cgi under Apache does not pass HTTP Basic user/pass to PHP by default
             * For this workaround to work, add these lines to your .htaccess file:
             * RewriteCond %{HTTP:Authorization} .+
             * RewriteRule ^ - [E=HTTP_AUTHORIZATION:%0]
             *
             * A sample .htaccess file:
             * RewriteEngine On
             * RewriteCond %{HTTP:Authorization} .+
             * RewriteRule ^ - [E=HTTP_AUTHORIZATION:%0]
             * RewriteCond %{REQUEST_FILENAME} !-f
             * RewriteRule ^(.*)$ app.php [QSA,L]
             */
            $authorizationHeader = null;
            if (isset($this->parameters['HTTP_AUTHORIZATION'])) {
                $authorizationHeader = $this->parameters['HTTP_AUTHORIZATION'];
            } elseif (isset($this->parameters['REDIRECT_HTTP_AUTHORIZATION'])) {
                $authorizationHeader = $this->parameters['REDIRECT_HTTP_AUTHORIZATION'];
            }
            if (null !== $authorizationHeader) {
                if (0 === stripos($authorizationHeader, 'basic ')) {
                    // Decode AUTHORIZATION header into PHP_AUTH_USER and PHP_AUTH_PW when authorization header is basic
                    $exploded = explode(':', base64_decode(substr($authorizationHeader, 6)), 2);
                    if (2 == \count($exploded)) {
                        list($headers['PHP_AUTH_USER'], $headers['PHP_AUTH_PW']) = $exploded;
                    }
                } elseif (empty($this->parameters['PHP_AUTH_DIGEST']) && 0 === stripos($authorizationHeader, 'digest ')) {
                    // In some circumstances PHP_AUTH_DIGEST needs to be set
                    $headers['PHP_AUTH_DIGEST'] = $authorizationHeader;
                    $this->parameters['PHP_AUTH_DIGEST'] = $authorizationHeader;
                } elseif (0 === stripos($authorizationHeader, 'bearer ')) {
                    /*
                     * XXX: Since there is no PHP_AUTH_BEARER in PHP predefined variables,
                     *      I'll just set $headers['AUTHORIZATION'] here.
                     *      https://php.net/reserved.variables.server
                     */
                    $headers['AUTHORIZATION'] = $authorizationHeader;
                }
            }
        }
        if (isset($headers['AUTHORIZATION'])) {
            return $headers;
        }
        // PHP_AUTH_USER/PHP_AUTH_PW
        if (isset($headers['PHP_AUTH_USER'])) {
            $headers['AUTHORIZATION'] = 'Basic ' . base64_encode($headers['PHP_AUTH_USER'] . ':' . $headers['PHP_AUTH_PW']);
        } elseif (isset($headers['PHP_AUTH_DIGEST'])) {
            $headers['AUTHORIZATION'] = $headers['PHP_AUTH_DIGEST'];
        }
        return $headers;
    }
}
}

namespace Symfony\Component\HttpFoundation {
/**
 * HeaderBag is a container for HTTP headers.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class HeaderBag implements \IteratorAggregate, \Countable
{
    protected $headers = [];
    protected $cacheControl = [];
    /**
     * @param array $headers An array of HTTP headers
     */
    public function __construct(array $headers = [])
    {
        foreach ($headers as $key => $values) {
            $this->set($key, $values);
        }
    }
    /**
     * Returns the headers as a string.
     *
     * @return string The headers
     */
    public function __toString()
    {
        if (!($headers = $this->all())) {
            return '';
        }
        ksort($headers);
        $max = max(array_map('strlen', array_keys($headers))) + 1;
        $content = '';
        foreach ($headers as $name => $values) {
            $name = implode('-', array_map('ucfirst', explode('-', $name)));
            foreach ($values as $value) {
                $content .= sprintf("%-{$max}s %s\r\n", $name . ':', $value);
            }
        }
        return $content;
    }
    /**
     * Returns the headers.
     *
     * @return array An array of headers
     */
    public function all()
    {
        return $this->headers;
    }
    /**
     * Returns the parameter keys.
     *
     * @return array An array of parameter keys
     */
    public function keys()
    {
        return array_keys($this->all());
    }
    /**
     * Replaces the current HTTP headers by a new set.
     *
     * @param array $headers An array of HTTP headers
     */
    public function replace(array $headers = [])
    {
        $this->headers = [];
        $this->add($headers);
    }
    /**
     * Adds new headers the current HTTP headers set.
     *
     * @param array $headers An array of HTTP headers
     */
    public function add(array $headers)
    {
        foreach ($headers as $key => $values) {
            $this->set($key, $values);
        }
    }
    /**
     * Returns a header value by name.
     *
     * @param string      $key     The header name
     * @param string|null $default The default value
     * @param bool        $first   Whether to return the first value or all header values
     *
     * @return string|string[]|null The first header value or default value if $first is true, an array of values otherwise
     */
    public function get($key, $default = null, $first = true)
    {
        $key = str_replace('_', '-', strtolower($key));
        $headers = $this->all();
        if (!\array_key_exists($key, $headers)) {
            if (null === $default) {
                return $first ? null : [];
            }
            return $first ? $default : [$default];
        }
        if ($first) {
            if (!$headers[$key]) {
                return $default;
            }
            if (null === $headers[$key][0]) {
                return null;
            }
            return (string) $headers[$key][0];
        }
        return $headers[$key];
    }
    /**
     * Sets a header by name.
     *
     * @param string          $key     The key
     * @param string|string[] $values  The value or an array of values
     * @param bool            $replace Whether to replace the actual value or not (true by default)
     */
    public function set($key, $values, $replace = true)
    {
        $key = str_replace('_', '-', strtolower($key));
        if (\is_array($values)) {
            $values = array_values($values);
            if (true === $replace || !isset($this->headers[$key])) {
                $this->headers[$key] = $values;
            } else {
                $this->headers[$key] = array_merge($this->headers[$key], $values);
            }
        } else {
            if (true === $replace || !isset($this->headers[$key])) {
                $this->headers[$key] = [$values];
            } else {
                $this->headers[$key][] = $values;
            }
        }
        if ('cache-control' === $key) {
            $this->cacheControl = $this->parseCacheControl(implode(', ', $this->headers[$key]));
        }
    }
    /**
     * Returns true if the HTTP header is defined.
     *
     * @param string $key The HTTP header
     *
     * @return bool true if the parameter exists, false otherwise
     */
    public function has($key)
    {
        return \array_key_exists(str_replace('_', '-', strtolower($key)), $this->all());
    }
    /**
     * Returns true if the given HTTP header contains the given value.
     *
     * @param string $key   The HTTP header name
     * @param string $value The HTTP value
     *
     * @return bool true if the value is contained in the header, false otherwise
     */
    public function contains($key, $value)
    {
        return \in_array($value, $this->get($key, null, false));
    }
    /**
     * Removes a header.
     *
     * @param string $key The HTTP header name
     */
    public function remove($key)
    {
        $key = str_replace('_', '-', strtolower($key));
        unset($this->headers[$key]);
        if ('cache-control' === $key) {
            $this->cacheControl = [];
        }
    }
    /**
     * Returns the HTTP header value converted to a date.
     *
     * @param string    $key     The parameter key
     * @param \DateTime $default The default value
     *
     * @return \DateTime|null The parsed DateTime or the default value if the header does not exist
     *
     * @throws \RuntimeException When the HTTP header is not parseable
     */
    public function getDate($key, \DateTime $default = null)
    {
        if (null === ($value = $this->get($key))) {
            return $default;
        }
        if (false === ($date = \DateTime::createFromFormat(DATE_RFC2822, $value))) {
            throw new \RuntimeException(sprintf('The %s HTTP header is not parseable (%s).', $key, $value));
        }
        return $date;
    }
    /**
     * Adds a custom Cache-Control directive.
     *
     * @param string $key   The Cache-Control directive name
     * @param mixed  $value The Cache-Control directive value
     */
    public function addCacheControlDirective($key, $value = true)
    {
        $this->cacheControl[$key] = $value;
        $this->set('Cache-Control', $this->getCacheControlHeader());
    }
    /**
     * Returns true if the Cache-Control directive is defined.
     *
     * @param string $key The Cache-Control directive
     *
     * @return bool true if the directive exists, false otherwise
     */
    public function hasCacheControlDirective($key)
    {
        return \array_key_exists($key, $this->cacheControl);
    }
    /**
     * Returns a Cache-Control directive value by name.
     *
     * @param string $key The directive name
     *
     * @return mixed|null The directive value if defined, null otherwise
     */
    public function getCacheControlDirective($key)
    {
        return \array_key_exists($key, $this->cacheControl) ? $this->cacheControl[$key] : null;
    }
    /**
     * Removes a Cache-Control directive.
     *
     * @param string $key The Cache-Control directive
     */
    public function removeCacheControlDirective($key)
    {
        unset($this->cacheControl[$key]);
        $this->set('Cache-Control', $this->getCacheControlHeader());
    }
    /**
     * Returns an iterator for headers.
     *
     * @return \ArrayIterator An \ArrayIterator instance
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->headers);
    }
    /**
     * Returns the number of headers.
     *
     * @return int The number of headers
     */
    public function count()
    {
        return \count($this->headers);
    }
    protected function getCacheControlHeader()
    {
        $parts = [];
        ksort($this->cacheControl);
        foreach ($this->cacheControl as $key => $value) {
            if (true === $value) {
                $parts[] = $key;
            } else {
                if (preg_match('#[^a-zA-Z0-9._-]#', $value)) {
                    $value = '"' . $value . '"';
                }
                $parts[] = "{$key}={$value}";
            }
        }
        return implode(', ', $parts);
    }
    /**
     * Parses a Cache-Control HTTP header.
     *
     * @param string $header The value of the Cache-Control HTTP header
     *
     * @return array An array representing the attribute values
     */
    protected function parseCacheControl($header)
    {
        $cacheControl = [];
        preg_match_all('#([a-zA-Z][a-zA-Z_-]*)\\s*(?:=(?:"([^"]*)"|([^ \\t",;]*)))?#', $header, $matches, PREG_SET_ORDER);
        foreach ($matches as $match) {
            $cacheControl[strtolower($match[1])] = isset($match[3]) ? $match[3] : (isset($match[2]) ? $match[2] : true);
        }
        return $cacheControl;
    }
}
}

namespace Platforms\QCloudSCF {
use Platforms\PlatformInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
class QCloudSCF implements PlatformInterface
{
    public static function request()
    {
        return new Request($_GET, $_POST, [], $_COOKIE, $_FILES, $_SERVER);
    }
    public static function response($response)
    {
        return ['isBase64Encoded' => false, 'statusCode' => $response->getStatusCode(), 'headers' => $response->headers->all(), 'body' => $response->getContent()];
    }
}
}

namespace Library {
class OneDrive
{
    public static function files()
    {
    }
}
}

