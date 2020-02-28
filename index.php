<?php

use Doctrine\Common\Cache\FilesystemCache;
use Library\Ext;
use Library\Lang;
use Library\OneDrive;
use Platforms\Platform;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

include __DIR__ . '/vendor/autoload.php';
include __DIR__ . '/config.php';
include __DIR__ . '/library/Helper.php';

/**
 * @param Request $request
 * @return array|Response
 */
function handler($request)
{
    global $config;

    define('APP_DEBUG', $config['debug']);
    Lang::init($request->cookies->get('language'));
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
        'relative' => join('/', $path),
        'absolute' => path_format($account['path'] . '/' . join('/', $path))
    ];
    try {
        $account['driver'] = new OneDrive($account['refresh_token'],
            !empty($account['version']) ? $account['version'] : 'MS',
            !empty($account['oauth']) ? $account['oauth'] : []);

        if ($request->query->has('thumbnails')) {
            return redirect($account['driver']->info($path['absolute'], true)['url']);
        }

        if ($request->isXmlHttpRequest()) {
            $response = ['error' => 'invalid action'];
            switch ($request->get('action')) {
                case trans('Create'):
                    $file_path = path_format($path['absolute'] . '/' . urlencode2($request->get('create_name')));
                    switch ($request->get('create_type')) {
                        default:
                        case 'file':
                            $response = $account['driver']->put($file_path, $request->get('create_content'));
                            break;
                        case 'folder':
                            $response = $account['driver']->makeDirectory($file_path);
                            break;
                    }
                    break;
                case trans('Encrypt'):
                    $file_path = path_format($path['absolute'] . '/' . urlencode2($request->get('encrypt_folder') . '/' . urlencode2($config['password_file'])));
                    $response = $account['driver']->put($file_path, $request->get('encrypt_newpass'));
                    break;
                case trans('Move'):
                    $file_path = path_format($path['absolute'] . '/' . urlencode2($request->get('move_name')));
                    if ($request->get('move_folder') === '/../') {
                        if ($path['absolute'] == '/') {
                            $response = ['error' => 'cannot move'];
                            break;
                        }
                        $new_path = path_format($path['absolute'] . '/../');

                    } else {
                        $new_path = path_format($path['absolute'] . '/' . urlencode2($request->get('move_folder')) . '/');
                    }
                    $new_path .= urlencode2($request->get('move_name'));
                    $response = $account['driver']->move($file_path, $new_path);
                    break;
                case trans('Rename'):
                    $file_path = path_format($path['absolute'] . '/' . urlencode2($request->get('rename_oldname')));
                    $response = $account['driver']->move($file_path, path_format($path['absolute'] . '/' . $request->get('rename_newname')));
                    break;
                case trans('Delete'):
                    $file_path = path_format($path['absolute'] . '/' . urlencode2($request->get('delete_name')));
                    $response = $account['driver']->delete($file_path);
                    break;
                case 'upload':
                    $file_path = path_format($path['absolute'] . '/' . urlencode2($request->get('filename')));
                    $response = $account['driver']->uploadLink($file_path);
                    break;
            }
            return response($response, !$response || isset($response['error']) ? 500 : 200);
        } elseif ($request->isMethod('POST')) {
            if ($request->query->has('preview')) {
                $account['driver']->put($path['absolute'], $request->get('content'));
            }
        }

        $files = $account['driver']->infos($path['absolute'], (int)$request->get('page', 1));

        if (!$request->query->has('preview')) {
            if (isset($files['@microsoft.graph.downloadUrl'])) {
                return redirect($files['@microsoft.graph.downloadUrl']);
            }
        }
        return render($account, $path, $files);
    } catch (Throwable $e) {
        @ob_clean();
        try {
            $error = ['error' => ['message' => $e->getMessage()]];
            if ($config['debug']) {
                $error['error']['message'] = trace_error($e);
            }
            return render($account, $path, $error);
        } catch (Throwable $e) {
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
    $config['platform'] = Platform::PLATFORM_NORMAL;
    return Platform::response(
        handler(
            Platform::request()
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
    $config['platform'] = Platform::PLATFORM_QCLOUD_SCF;

    return Platform::response(
        handler(
            Platform::request()
        )
    );
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


function EnvOpt($function_name, $Region, $namespace = 'default', $needUpdate = 0)
{
    $constEnv = [
        //'admin',
        'adminloginpage', 'domain_path', 'imgup_path', 'passfile', 'private_path', 'public_path', 'sitename', 'language'
    ];
    asort($constEnv);
    $html = '<title>SCF ' . trans('Setup') . '</title>';
    if ($_POST['updateProgram'] == trans('updateProgram')) {
        $response = json_decode(scf_update_code($function_name, $Region, $namespace), true)['Response'];
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
        echo scf_update_env($tmp, $function_name, $Region, $namespace);
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
            foreach (Lang::all()['languages'] as $key1 => $value1) {
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
 * @return array|Response
 * @throws Exception
 */
function render($account, $path, $files)
{
    global $config;

    $request = request();
    $is_admin = $request->cookies->get('admin_password') === $config['admin_password'];
    $base_url = $request->getBaseUrl();
    if ($base_url == '') $base_url = '/';
    $status_code = 200;

    $is_image_path = in_array($path['relative'], $account['path_image']);
    $is_video = false;
    $readme = false;
    @ob_start();
    ?>
    <!DOCTYPE html>
    <html lang="<?php echo Lang::language(); ?>">
    <head>
        <title><?php echo ($path['relative'] === '' ? '/' : urldecode($path['relative'])) . ' - ' . $config['name']; ?></title>
        <!--
        https://github.com/Tai7sy/OneDriveFly
        -->
        <meta charset=utf-8>
        <meta http-equiv=X-UA-Compatible content="IE=edge">
        <meta name=viewport content="width=device-width,initial-scale=1">
        <meta name="keywords" content="<?php
        echo htmlspecialchars(str_replace('/', ',', $path['relative']) . ',' . $config['name']); ?>,OneDrive_SCF,OneDriveFly">
        <link rel="icon" href="<?php echo $base_url ?>favicon.ico" type="image/x-icon"/>
        <link rel="shortcut icon" href="<?php echo $base_url ?>favicon.ico" type="image/x-icon"/>
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
                margin: 2rem 0;
                letter-spacing: 2px;
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
                text-align: left;
                cursor: pointer;
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
                height: 100%;
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
                border: 0 #f7f7f7 solid;
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
            .operate-model {
                position: absolute;
                border: 1px #CCCCCC;
                background-color: #FFFFCC;
                z-index: 2;
            }

            .operate-model div {
                margin: 16px
            }

            .closeModel {
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

            function loadResources(type, src, callback) {
                var script = document.createElement(type);
                var loaded = false;
                if (typeof callback === 'function') {
                    script.onload = script.onreadystatechange = function () {
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

            String.prototype.between = function (before, after) {
                var index1 = this.indexOf(before);
                var index2 = this.indexOf(after, index1 + 1);
                if (index1 === -1 || index2 === -1) return null;
                return this.substring(index1 + before.length, index2)
            };

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
    if (!empty($config['admin_password'])) {
        if (!$is_admin) {
            ?>
            <a onclick="login();"><?php echo trans('Login'); ?></a>
            <?php
        } else { ?>
            <li class="operate"><?php echo trans('Operate'); ?>
                <ul>
                    <?php if (isset($files['folder'])) { ?>
                        <li>
                            <a onclick="showModel(event,'create');"><?php echo trans('Create'); ?></a>
                        </li>
                        <li>
                            <a onclick="showModel(event,'encrypt');"><?php echo trans('Encrypt'); ?></a>
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
    <label class="select-language">
        <select name="language" onchange="changeLanguage(this.value)">
            <option value="-1">Language</option>
            <?php
            foreach (Lang::all()['languages'] as $key1 => $value1) {
                echo '<option value="' . $key1 . '" ' . (Lang::language() === $key1 ? 'selected="true"' : '') . '">' . $value1 . '</option>';
            }
            ?>
        </select>
    </label>
    <!-- update -->
    <div style='position:absolute; display: none'><span style="color: red"><?php echo trans('NeedUpdate'); ?></span>
    </div>
    <h1 class="title">
        <a href="<?php echo $base_url; ?>"><?php echo $config['name']; ?></a>
    </h1>
    <div class="list-wrapper">
        <div class="list-container">
            <div class="list-header-container">
                <?php if ($path !== '/') {
                    $current_url = $request->getUri();
                    while (substr($current_url, -1) === '/') {
                        $current_url = substr($current_url, 0, -1);
                    }
                    if (strpos($current_url, '/') !== FALSE) {
                        $parent_url = substr($current_url, 0, strrpos($current_url, '/'));
                    } else {
                        $parent_url = $current_url;
                    }
                    ?>
                    <a href="<?php echo $parent_url; ?>" class="back-link">
                        <ion-icon name="arrow-back"></ion-icon>
                    </a>
                <?php } ?>
                <h3 class="table-header">/<?php echo htmlspecialchars(urldecode($path['relative'])); ?></h3>
            </div>
            <div class="list-body-container">
                <?php
                if ($is_image_path && !$is_admin) { ?>
                    <div id="upload_div" style="margin: 12px; text-align: center">
                        <div>
                            <input id="upload_file" type="file" name="upload_filename">
                            <input id="upload_submit" onclick="uploadPrepare();"
                                   value="<?php echo trans('Upload'); ?>" type="button">
                        </div>
                    </div>
                    <?php
                } else {
                    $folder_password = false;
                    if (!empty($config['password_file']) && !empty($account['driver'])) {
                        $folder_password = $account['driver']->get(path_format($path['absolute'] . '/' . $config['password_file']));
                    }
                    if ($is_admin || empty($folder_password) || $folder_password === request()->cookies->get('password')) {
                        if (isset($files['error'])) {
                            echo '<div style="margin: 8px;">' . $files['error']['message'] . '</div>';
                            $status_code = 404;
                        } else {
                            if (isset($files['file'])) {
                                // request is a file
                                ?>
                                <div style="margin: 12px 4px 4px; text-align: center">
                                    <div style="margin: 24px">
                                        <label>
                                        <textarea id="url" rows="1" style="width: 100%; margin-top: 2px;"
                                                  readonly><?php echo path_format($base_url . '/' . $path['relative']); ?></textarea>
                                        </label>
                                        <a style="display: inline-block; margin: 8px 0 0"
                                           href="<?php echo path_format($base_url . '/' . $path['relative']);//$files['@microsoft.graph.downloadUrl'] ?>">
                                            <ion-icon name="download"
                                                      style="line-height: 16px;vertical-align: middle;"></ion-icon>&nbsp;<?php echo trans('Download'); ?>
                                        </a>
                                    </div>
                                    <div style="margin: 24px">
                                        <?php
                                        $ext = strtolower(substr($path['relative'], strrpos($path['relative'], '.') + 1));
                                        if (in_array($ext, Ext::IMG)) {
                                            echo '
                        <img src="' . $files['@microsoft.graph.downloadUrl'] . '" alt="' . substr($path['relative'], strrpos($path['relative'], '/')) . '" onload="if(this.offsetWidth>document.getElementById(\'url\').offsetWidth) this.style.width=\'100%\';" />
';
                                        } elseif (in_array($ext, Ext::VIDEO)) {
                                            //echo '<video src="' . $files['@microsoft.graph.downloadUrl'] . '" controls="controls" style="width: 100%"></video>';
                                            $is_video = true;
                                            echo '<div id="video-a0" data-url="' . $files['@microsoft.graph.downloadUrl'] . '"></div>';
                                        } elseif (in_array($ext, Ext::MUSIC)) {
                                            echo '
                        <audio src="' . $files['@microsoft.graph.downloadUrl'] . '" controls="controls" style="width: 100%"></audio>
';
                                        } elseif (in_array($ext, ['pdf'])) {
                                            echo '
                        <embed src="' . $files['@microsoft.graph.downloadUrl'] . '" type="application/pdf" width="100%" height=800px">
';
                                        } elseif (in_array($ext, Ext::OFFICE)) {
                                            echo '
                        <iframe id="office-a" src="https://view.officeapps.live.com/op/view.aspx?src=' . urlencode($files['@microsoft.graph.downloadUrl']) . '" style="width: 100%;height: 800px; border: 0"></iframe>
';
                                        } elseif (in_array($ext, Ext::TXT)) {
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
                                                    <label for="txt-a"></label>
                                                    <textarea id="txt-a" name="content" readonly
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
                                <?php
                            } elseif (isset($files['folder'])) {
                                $index = 0;
                                ?>
                                <table class="list-table" id="list-table">
                                    <tr id="tr0">
                                        <th class="file" onclick="sortTable(event, 0);"><?php echo trans('File'); ?>
                                            &nbsp;&nbsp;&nbsp;
                                            <button onclick="showThumbnails(this)"><?php echo trans('ShowThumbnails'); ?></button>
                                        </th>
                                        <th class="updated_at" style="width: 25%"
                                            onclick="sortTable(event, 1);"><?php echo trans('EditTime'); ?></th>
                                        <th class="size" style="width: 15%"
                                            onclick="sortTable(event, 2);"><?php echo trans('Size'); ?></th>
                                    </tr>
                                    <!-- Dirs -->
                                    <?php
                                    // echo json_encode($files['children'], JSON_PRETTY_PRINT);
                                    foreach ($files['children'] as $file) {
                                        // Folders
                                        if (isset($file['folder'])) {
                                            $index++; ?>
                                            <tr data-to id="tr<?php echo $index; ?>">
                                                <td class="file">
                                                    <?php if ($is_admin) { ?>
                                                        <li class="operate"><?php echo trans('Operate'); ?>
                                                            <ul>
                                                                <li>
                                                                    <a onclick="showModel(event,'encrypt',<?php echo $index; ?>);"><?php echo trans('Encrypt'); ?></a>
                                                                </li>
                                                                <li>
                                                                    <a onclick="showModel(event, 'rename',<?php echo $index; ?>);"><?php echo trans('Rename'); ?></a>
                                                                </li>
                                                                <li>
                                                                    <a onclick="showModel(event, 'move',<?php echo $index; ?>);"><?php echo trans('Move'); ?></a>
                                                                </li>
                                                                <li>
                                                                    <a onclick="showModel(event, 'delete',<?php echo $index; ?>);"><?php echo trans('Delete'); ?></a>
                                                                </li>
                                                            </ul>
                                                        </li>&nbsp;&nbsp;&nbsp;
                                                    <?php } ?>
                                                    <ion-icon name="folder"></ion-icon>
                                                    <a id="filename_<?php echo $index; ?>"
                                                       href="<?php echo htmlspecialchars(path_format($path['relative'] . '/' . $file['name'] . '/')); ?>"><?php echo htmlspecialchars(urldecode($file['name'])); ?></a>
                                                </td>
                                                <td class="updated_at"><?php echo time_format($file['lastModifiedDateTime']); ?></td>
                                                <td class="size"><?php echo size_format($file['size']); ?></td>
                                            </tr>
                                        <?php }
                                    }
                                    // if ($filenum) echo '<tr data-to></tr>';
                                    foreach ($files['children'] as $file) {
                                        // Files
                                        if (isset($file['file'])) {
                                            if ($is_admin || (substr($file['name'], 0, 1) !== '.' && $file['name'] !== $config['password_file'])) {
                                                if (strtolower($file['name']) === 'readme.md' || strtolower($file['name']) === 'readme') {
                                                    $readme = $file;
                                                }
                                                if (strtolower($file['name']) === 'index.html' || strtolower($file['name']) === 'index.htm') {
                                                    $html = $account['driver']->get(path_format($path['absolute'] . '/' . $file['name']));
                                                    @ob_clean();
                                                    return response($html);
                                                }
                                                $index++;
                                                ?>
                                                <tr data-to id="tr<?php echo $index; ?>">
                                                    <td class="file">
                                                        <?php if ($is_admin) { ?>
                                                            <li class="operate"><?php echo trans('Operate'); ?>
                                                                <ul>
                                                                    <li>
                                                                        <a onclick="showModel(event, 'rename',<?php echo $index; ?>);"><?php echo trans('Rename'); ?></a>
                                                                    </li>
                                                                    <li>
                                                                        <a onclick="showModel(event, 'move',<?php echo $index; ?>);"><?php echo trans('Move'); ?></a>
                                                                    </li>
                                                                    <li>
                                                                        <a onclick="showModel(event, 'delete',<?php echo $index; ?>);"><?php echo trans('Delete'); ?></a>
                                                                    </li>
                                                                </ul>
                                                            </li>&nbsp;&nbsp;&nbsp;
                                                        <?php }
                                                        $ext = strtolower(substr($file['name'], strrpos($file['name'], '.') + 1));
                                                        if (in_array($ext, Ext::MUSIC)) { ?>
                                                            <ion-icon name="musical-notes"></ion-icon>
                                                        <?php } elseif (in_array($ext, Ext::VIDEO)) { ?>
                                                            <ion-icon name="logo-youtube"></ion-icon>
                                                        <?php } elseif (in_array($ext, Ext::IMG)) { ?>
                                                            <ion-icon name="image"></ion-icon>
                                                        <?php } elseif (in_array($ext, Ext::OFFICE)) { ?>
                                                            <ion-icon name="paper"></ion-icon>
                                                        <?php } elseif (in_array($ext, Ext::TXT)) { ?>
                                                            <ion-icon name="clipboard"></ion-icon>
                                                        <?php } elseif (in_array($ext, Ext::ZIP)) { ?>
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
                                                        <a id="filename_<?php echo $index; ?>"
                                                           class="filename"
                                                           href="<?php echo htmlspecialchars(path_format($path['relative'] . '/' . $file['name'])); ?>?preview"
                                                           target=_blank><?php echo htmlspecialchars(urldecode($file['name'])); ?></a>
                                                        <a href="<?php echo htmlspecialchars(path_format($path['relative'] . '/' . $file['name'])); ?>">
                                                            <ion-icon name="download"></ion-icon>
                                                        </a>
                                                    </td>
                                                    <td class="updated_at"><?php echo time_format($file['lastModifiedDateTime']); ?></td>
                                                    <td class="size"><?php echo size_format($file['size']); ?></td>
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
                                <a href="?page=' . ($files['folder']['currentPage'] - 1) . '">' . trans('PrePage') . '</a>';
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
                                <a href="?page=' . $page . '">' . $page . '</a>';
                                        }
                                    }
                                    $pageForm .= '
                            </td>
                            <td style="width: 60px; text-align: center">';
                                    if ($files['folder']['currentPage'] != $files['folder']['lastPage']) {
                                        $pageForm .= '
                                <a href="?page=' . ($files['folder']['lastPage'] + 1) . '">' . trans('NextPage') . '</a>';
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
                                        <div style="text-align: center">
                                            <input id="upload_file" type="file" name="upload_filename"
                                                   multiple="multiple">
                                            <input id="upload_submit" onclick="uploadPrepare();"
                                                   value="<?php echo trans('Upload'); ?>"
                                                   type="button">
                                        </div>
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
                                    $account['driver']->get(path_format($path['absolute'] . '/' . $readme['name'])) . '
                        </textarea>
                    </div>
                </div>
';
                            }
                        }
                    } else {
                        echo '
                <div style="padding:20px">
	            <div style="text-align: center">
		            <input id="password" type="password" placeholder="' . trans('InputPassword') . '">
		            <button onclick="inputPassword()">' . trans('Submit') . '</button>
                </div>
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
        if (!$request->query->has('preview')) { ?>

            <div id="rename_div" class="operate-model" style="display:none">
                <div>
                    <label id="rename_label"></label><br><br>
                    <a onclick="closeModel('rename')"
                       class="closeModel"><?php echo trans('Close'); ?></a>
                    <form id="rename_form" onsubmit="return submitOperate('rename');">
                        <input id="rename_sid" name="rename_sid" type="hidden" value="">
                        <input id="rename_hidden" name="rename_oldname" type="hidden" value="">
                        <label for="rename_input"></label>
                        <input id="rename_input" name="rename_newname" type="text" value="">
                        <input name="action" type="submit" value="<?php echo trans('Rename'); ?>">
                    </form>
                </div>
            </div>

            <div id="delete_div" class="operate-model" style="display:none">
                <div>
                    <br><a onclick="closeModel('delete')"
                           class="closeModel"><?php echo trans('Close'); ?></a>
                    <label id="delete_label"></label>
                    <form id="delete_form" onsubmit="return submitOperate('delete');">
                        <label id="delete_input"><?php echo trans('Delete'); ?>?</label>
                        <input id="delete_sid" name="delete_sid" type="hidden" value="">
                        <input id="delete_hidden" name="delete_name" type="hidden" value="">
                        <input name="action" type="submit"
                               value="<?php echo trans('Delete'); ?>">
                    </form>
                </div>
            </div>

            <div id="encrypt_div" class="operate-model" style="display:none">
                <div>
                    <label id="encrypt_label"></label><br><br>
                    <a onclick="closeModel('encrypt')" class="closeModel"><?php echo trans('Close'); ?></a>
                    <form id="encrypt_form" onsubmit="return submitOperate('encrypt');">
                        <input id="encrypt_sid" name="encrypt_sid" type="hidden" value="">
                        <input id="encrypt_hidden" name="encrypt_folder" type="hidden" value="">
                        <label for="encrypt_input"></label>
                        <input id="encrypt_input" name="encrypt_newpass" type="text"
                               placeholder="<?php echo trans('InputPasswordUWant'); ?>">

                        <?php if (!empty($config['password_file'])) { ?>
                            <input name="action" type="submit" value="<?php echo trans('Encrypt'); ?>">
                        <?php } else { ?>
                            <br>
                            <label><?php echo trans('SetpassfileBfEncrypt'); ?></label><?php } ?>
                    </form>
                </div>
            </div>

            <div id="move_div" class="operate-model" style="display:none">
                <div>
                    <label id="move_label"></label><br><br>
                    <a onclick="closeModel('move')" class="closeModel"><?php echo trans('Close'); ?></a>
                    <form id="move_form" onsubmit="return submitOperate('move');">
                        <input id="move_sid" name="move_sid" type="hidden" value="">
                        <input id="move_hidden" name="move_name" type="hidden" value="">
                        <label for="move_input"></label>
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
                        <input name="action" type="submit"
                               value="<?php echo trans('Move'); ?>">
                    </form>
                </div>
            </div>

            <div id="create_div" class="operate-model" style="display:none">
                <div>
                    <a onclick="closeModel('create')" class="closeModel"><?php echo trans('Close'); ?></a>
                    <form id="create_form" onsubmit="return submitOperate('create');">
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
                                    <label>
                                        <input id="create_type_folder" name="create_type" type="radio" value="folder"
                                               onclick="document.getElementById('create_content_div').style.display='none';">
                                        <?php echo trans('Folder'); ?>
                                    </label>
                                    <label>
                                        <input id="create_type_file" name="create_type" type="radio" value="file"
                                               onclick="document.getElementById('create_content_div').style.display='';"
                                               checked>
                                        <?php echo trans('File'); ?>
                                    </label>
                                <td>
                            </tr>
                            <tr>
                                <td><?php echo trans('Name'); ?>：</td>
                                <td><label><input id="create_input" name="create_name" type="text" value=""></label>
                                </td>
                            </tr>
                            <tr id="create_content_div">
                                <td><?php echo trans('Content'); ?>：</td>
                                <td><label><textarea id="create_content" name="create_content" rows="6"
                                                     cols="40"></textarea></label>
                                </td>
                            </tr>
                            <tr>
                                <td>　　　</td>
                                <td><input name="action" type="submit" value="<?php echo trans('Create'); ?>">
                                </td>
                            </tr>
                        </table>
                    </form>
                </div>
            </div>

        <?php }
    } else {
        ?>
        <div id="login_div" class="operate-model" style="display: none">
            <div style="margin:50px">
                <a onclick="closeModel('login')"
                   class="closeModel"><?php echo trans('Close'); ?></a>
                <div style="text-align: center">
                    <label>
                        <input id="admin_password" type="password" placeholder="<?php echo trans('InputPassword'); ?>">
                    </label>
                    <button onclick="inputAdminPassword()"><?php echo trans('Login'); ?></button>
                </div>
            </div>
        </div>
        <?php
    } ?>
    <span style="color: #f7f7f9"><?php echo date("Y-m-d H:i:s") . " " . trans('Week.' . date('w')) . ' ' . $_SERVER['REMOTE_ADDR']; ?></span>
    </body>

    <link rel="stylesheet" href="//unpkg.zhimg.com/github-markdown-css@3.0.1/github-markdown.css">
    <script type="text/javascript" src="//unpkg.zhimg.com/marked@0.6.2/marked.min.js"></script>
    <?php if (isset($files['folder']) && $is_image_path && !$is_admin) { ?>
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
            var path = e.innerText.split('/');
            e.innerHTML = '/ ';
            for (var i = 1; i < path.length - 1; i++) {
                var to = path_format(root + path.slice(0, i + 1).join('/'));
                e.innerHTML += '<a href="' + to + '">' + path[i] + '</a> / '
            }
            e.innerHTML += path[path.length - 1];
            e.innerHTML = e.innerHTML.replace(/\s\/\s$/, '')
        });

        function changeLanguage(lang) {
            setCookie('language', lang, 7 * 24 * 3600 * 1000);
            location.reload();
        }

        var $readme = document.getElementById('readme');
        if ($readme) {
            $readme.innerHTML = marked(document.getElementById('readme-md').innerText)
        }

        function inputPassword() {
            setCookie('password', document.getElementById('password').value);
            location.reload()
        }

        function inputAdminPassword() {
            setCookie('admin_password', document.getElementById('admin_password').value);
            location.reload()
        }

        <?php
        //is preview mode. 在预览时处理
        if ($request->query->has('preview')) {
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

        <?php
        if ($is_video) {
        ?>
        (function loadDPlayer() {

            function createDPlayers() {
                var container = document.getElementById('video-a0');
                var url = container.getAttribute('data-url');
                var subtitle = url.replace(/\.[^.]+?(\?|$)/, '.vtt$1');
                var dp = new DPlayer({
                    container: container,
                    autoplay: false,
                    screenshot: true,
                    hotkey: true,
                    volume: 1,
                    preload: 'auto',
                    mutex: true,
                    video: {
                        url: url
                    },
                    subtitle: {
                        url: subtitle,
                        fontSize: '25px',
                        bottom: '7%'
                    }
                });
                // 防止出现401 token过期
                dp.on('error', function () {
                    console.log('获取资源错误，开始重新加载！');
                    var last = dp.video.currentTime;
                    dp.video.src = url;
                    dp.video.load();
                    dp.video.currentTime = last;
                    dp.play();
                });
                // 如果是播放状态 & 没有播放完 每25分钟重载视频防止卡死
                setInterval(function () {
                    if (!dp.video.paused && !dp.video.ended) {
                        console.log('开始重新加载！');
                        var last = dp.video.currentTime;
                        dp.video.src = url;
                        dp.video.load();
                        dp.video.currentTime = last;
                        dp.play();
                    }
                }, 1000 * 60 * 25)
            }

            var host = 'https://s0.pstatp.com/cdn/expire-1-M';
            var unloadedResourceCount = 4;
            var callback = (function () {
                return function () {
                    if (!--unloadedResourceCount) {
                        createDPlayers();
                    }
                };
            })(unloadedResourceCount);

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
        })();
        <?php
        }
        ?>

        <?php
        }
        else
        {
        // view folder. 不预览，即浏览目录时
        ?>

        function showThumbnails(obj) {
            var files = document.querySelectorAll('td.file>.filename');
            for (var i = 0; i < files.length; i++) {
                var filename = files[i].innerText;
                while (filename.substr(-1) === ' ') filename = filename.substr(0, filename.length - 1);
                if (!filename) return;
                var ext = filename.split('.').pop().toLowerCase();
                if ((<?php echo json_encode(Ext::IMG); ?>).indexOf(ext) > -1) {
                    files[i].innerHTML = '<img src="' + location.href + '/' + encodeURIComponent(filename) + '?thumbnails" alt="' + filename + '" title="' + filename + '">';
                }
            }
            obj.disabled = 'disabled';
        }

        var sortDirection = [];

        function sortTable(event, n) {
            if (event.target.tagName !== 'TH') return;
            var rows = document.getElementById("list-table").rows;
            var i, j;
            var cells;

            sortDirection[n] = sortDirection[n] === undefined || sortDirection[n] === 'desc' ? 'asc' : 'desc';

            function compare(a, b) {
                if (a[n] === b[n]) {
                    return 0;
                }
                if (n === 0) {
                    if (a[n].between('<a', '/a>').between('>', '<') > b[n].between('<a', '/a>').between('>', '<')) {
                        return sortDirection[n] === "asc" ? 1 : -1;
                    }
                } else if (n === 2) {
                    // sort by size
                    if (parseSize(a[n]) > parseSize(b[n])) {
                        return sortDirection[n] === "asc" ? 1 : -1;
                    }
                } else {
                    if (a[n] > b[n]) {
                        return sortDirection[n] === "asc" ? 1 : -1;
                    }
                }
                return sortDirection[n] === "asc" ? -1 : 1;
            }

            var arr = [];
            for (i = 1; i < rows.length; i++) {
                cells = rows[i].cells;

                arr[i - 1] = [];
                for (j = 0; j < cells.length; j++) {
                    arr[i - 1][j] = cells[j].innerHTML;
                }
            }

            // sorting
            arr.sort(compare);
            // replace existing rows with new rows created from the sorted array
            for (i = 1; i < rows.length; i++) {
                cells = rows[i].cells;
                for (j = 0; j < cells.length; j++) {
                    cells[j].innerHTML = arr[i - 1][j];
                }
            }
        }

        function parseSize(str) {
            if (str.substr(-1) === ' ') str = str.substr(0, str.length - 1);
            if (str.substr(-2) === 'GB') return str.substr(0, str.length - 3) * 1024 * 1024 * 1024;
            if (str.substr(-2) === 'MB') return str.substr(0, str.length - 3) * 1024 * 1024;
            if (str.substr(-2) === 'KB') return str.substr(0, str.length - 3) * 1024;
            if (str.substr(-2) === ' B') return str.substr(0, str.length - 2);
        }
        <?php
        }
        ?>

        function closeModel(operate) {
            document.getElementById(operate + '_div').style.display = 'none';
            document.getElementById('mask').style.display = 'none';
        }

        <?php
        if (isset($files['folder']) && ($is_image_path || $is_admin)) {
        // is folder and is admin or guest upload path. 当前是admin登录或图床目录时
        ?>

        function sizeFormat(num) {
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

        function hideUploadBtn() {
            document.getElementById('upload_submit').disabled = 'disabled';
            document.getElementById('upload_file').disabled = 'disabled';
            document.getElementById('upload_submit').style.display = 'none';
            document.getElementById('upload_file').style.display = 'none';
        }

        function showUploadBtn() {
            document.getElementById('upload_file').disabled = '';
            document.getElementById('upload_submit').disabled = '';
            document.getElementById('upload_submit').style.display = '';
            document.getElementById('upload_file').style.display = '';
        }

        function uploadPrepare() {
            hideUploadBtn();
            var files = document.getElementById('upload_file').files;
            if (files.length < 1) {
                showUploadBtn();
                return;
            }
            var table1 = document.createElement('table');
            document.getElementById('upload_div').appendChild(table1);
            table1.setAttribute('class', 'list-table');
            var timea = new Date().getTime();
            var i = 0;
            getUploadUrl(i);

            function getUploadUrl(i) {
                var file = files[i];
                var tr1 = document.createElement('tr');
                table1.appendChild(tr1);
                tr1.setAttribute('data-to', '1');
                var td1 = document.createElement('td');
                tr1.appendChild(td1);
                td1.setAttribute('style', 'width:30%');
                td1.setAttribute('id', 'upfile_td1_' + timea + '_' + i);
                td1.innerHTML = file.name + '<br>' + sizeFormat(file.size);
                var td2 = document.createElement('td');
                tr1.appendChild(td2);
                td2.setAttribute('id', 'upfile_td2_' + timea + '_' + i);
                td2.innerHTML = '<?php echo trans('GetUploadLink'); ?> ...';
                if (file.size > 100 * 1024 * 1024 * 1024) {
                    td2.innerHTML = '<font color="red"><?php echo trans('UpFileTooLarge'); ?></font>';
                    showUploadBtn();
                    return;
                }
                var xhr = new XMLHttpRequest();
                xhr.open('POST', '');
                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
                xhr.send('action=upload&filename=' + encodeURIComponent(file.name) + '&filesize=' + file.size + '&lastModified=' + file.lastModified);
                xhr.onload = function () {
                    td2.innerHTML = '<span style="color: red">' + xhr.responseText + '</span>';
                    if (xhr.status === 200) {
                        var html = JSON.parse(xhr.responseText);
                        if (!html['uploadUrl']) {
                            td2.innerHTML = '<span style="color: red">' + xhr.responseText + '</span><br>';
                            showUploadBtn();
                        } else {
                            td2.innerHTML = '<?php echo trans('UploadStart'); ?> ...';
                            upload(file, html['uploadUrl'], timea + '_' + i);
                        }
                    }
                    if (i < files.length - 1) {
                        i++;
                        getUploadUrl(i);
                    }
                }
            }
        }


        function upload(file, url, tdnum) {
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
                //xhr2.setRequestHeader('X-Requested-With','XMLHttpRequest');
                xhr2.send(null);
                xhr2.onload = function () {
                    if (xhr2.status === 200) {
                        var html = JSON.parse(xhr2.responseText);
                        var a = html['nextExpectedRanges'][0];
                        newstartsize = Number(a.slice(0, a.indexOf("-")));
                        StartTime = new Date();
                        <?php if ($is_admin) { ?>
                        asize = newstartsize;
                        <?php } ?>
                        if (newstartsize === 0) {
                            StartStr = '<?php echo trans('UploadStartAt'); ?>:' + StartTime.toLocaleString() + '<br>';
                        } else {
                            StartStr = '<?php echo trans('LastUpload'); ?>' + sizeFormat(newstartsize) + '<br><?php echo trans('ThisTime') . trans('UploadStartAt'); ?>:' + StartTime.toLocaleString() + '<br>';
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
                            //xhr.setRequestHeader('X-Requested-With','XMLHttpRequest');
                            var bsize = asize + e.loaded - 1;
                            xhr.setRequestHeader('Content-Range', 'bytes ' + asize + '-' + bsize + '/' + totalsize);
                            xhr.upload.onprogress = function (e) {
                                if (e.lengthComputable) {
                                    var tmptime = new Date();
                                    var tmpspeed = e.loaded * 1000 / (tmptime.getTime() - C_starttime.getTime());
                                    var remaintime = (totalsize - asize - e.loaded) / tmpspeed;
                                    label.innerHTML = StartStr + '<?php echo trans('Upload'); ?> ' + sizeFormat(asize + e.loaded) + ' / ' + sizeFormat(totalsize) + ' = ' + ((asize + e.loaded) * 100 / totalsize).toFixed(2) + '% <?php echo trans('AverageSpeed'); ?>:' + sizeFormat((asize + e.loaded - newstartsize) * 1000 / (tmptime.getTime() - StartTime.getTime())) + '/s<br><?php echo trans('CurrentSpeed'); ?> ' + sizeFormat(tmpspeed) + '/s <?php echo trans('Expect'); ?> ' + remaintime.toFixed(1) + 's';
                                }
                            };
                            var C_starttime = new Date();
                            xhr.onload = function () {
                                if (xhr.status < 500) {
                                    var response = JSON.parse(xhr.responseText);
                                    if (response['size'] > 0) {
                                        // contain size, upload finish. 有size说明是最终返回，上传结束
                                        var xhr3 = new XMLHttpRequest();
                                        xhr3.open("POST", '');
                                        xhr3.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
                                        xhr3.send('action=del_upload_cache&filename=.' + file.lastModified + '_' + file.size + '_' + encodeURIComponent(file.name) + '.tmp');
                                        xhr3.onload = function () {
                                            console.log(xhr3.responseText + ',' + xhr3.status);
                                        };
                                        <?php if (!$is_admin) { ?>
                                        var xhr4 = new XMLHttpRequest();
                                        xhr4.open("POST", '');
                                        xhr4.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
                                        var filemd5 = spark.end();
                                        xhr4.send('action=uploaded_rename&filename=' + encodeURIComponent(file.name) + '&filemd5=' + filemd5);
                                        xhr4.onload = function () {
                                            console.log(xhr4.responseText + ',' + xhr4.status);
                                            var filename;
                                            if (xhr4.status === 200) filename = JSON.parse(xhr4.responseText)['name'];
                                            if (xhr4.status === 409) filename = filemd5 + file.name.substr(file.name.indexOf('.'));
                                            if (!filename || !filename.length) {
                                                alert('<?php echo trans('UploadErrorUpAgain'); ?>');
                                                showUploadBtn();
                                                return;
                                            }
                                            var lasturl = location.href;
                                            if (lasturl.substr(lasturl.length - 1) !== '/') lasturl += '/';
                                            lasturl += filename + '?preview';
                                            //alert(lasturl);
                                            window.open(lasturl);
                                        };
                                        <?php } ?>
                                        EndTime = new Date();
                                        MiddleStr = '<?php echo trans('EndAt'); ?>:' + EndTime.toLocaleString() + '<br>';
                                        if (newstartsize === 0) {
                                            MiddleStr += '<?php echo trans('AverageSpeed'); ?>:' + sizeFormat(totalsize * 1000 / (EndTime.getTime() - StartTime.getTime())) + '/s<br>';
                                        } else {
                                            MiddleStr += '<?php echo trans('ThisTime') . trans('AverageSpeed'); ?>:' + sizeFormat((totalsize - newstartsize) * 1000 / (EndTime.getTime() - StartTime.getTime())) + '/s<br>';
                                        }
                                        document.getElementById('upfile_td1_' + tdnum).innerHTML = '<font color="green"><?php if (!$is_admin) { ?>' + filemd5 + '<br><?php } ?>' + document.getElementById('upfile_td1_' + tdnum).innerHTML + '<br><?php echo trans('UploadComplete'); ?></font>';
                                        label.innerHTML = StartStr + MiddleStr;
                                        showUploadBtn();
                                        <?php if ($is_admin) { ?>
                                        addFileToList(response);
                                        <?php } ?>
                                    } else {
                                        if (!response['nextExpectedRanges']) {
                                            label.innerHTML = '<span style="color: red">' + xhr.responseText + '</span><br>';
                                        } else {
                                            var a = response['nextExpectedRanges'][0];
                                            asize = Number(a.slice(0, a.indexOf("-")));
                                            readblob(asize);
                                        }
                                    }
                                } else readblob(asize);
                            };
                            xhr.send(binary);
                        }
                    } else {
                        if (window.location.pathname.indexOf('%23') > 0 || file.name.indexOf('%23') > 0) {
                            label.innerHTML = '<span style="color:red;"><?php echo trans('UploadFail23'); ?></span>';
                        } else {
                            label.innerHTML = '<span style="color:red;">' + xhr2.responseText + '</span>';
                        }
                        showUploadBtn();
                    }
                }
            }
        }

        <?php
        }


        if ($is_admin) { // admin login. 管理登录后 ?>

        function logout() {
            setCookie('admin_password', null, -1);
            location.reload();
        }

        function enableedit(obj) {
            document.getElementById('txt-a').readOnly = !document.getElementById('txt-a').readOnly;
            //document.getElementById('txt-editbutton').innerHTML=(document.getElementById('txt-editbutton').innerHTML=='取消编辑')?'点击后编辑':'取消编辑';
            obj.innerHTML = (obj.innerHTML === '<?php echo trans('CancelEdit'); ?>') ? '<?php echo trans('ClicktoEdit'); ?>' : '<?php echo trans('CancelEdit'); ?>';
            document.getElementById('txt-save').style.display = document.getElementById('txt-save').style.display === '' ? 'none' : '';
        }

        function showModel(event, action, index) {
            var models = document.getElementsByName('operate-model');
            for (var i = 0; i < models.length; i++) {
                models.style.display = 'none';
            }
            document.getElementById('mask').style.display = 'block';

            var filename = '';
            if (index !== undefined) {
                filename = document.getElementById('filename_' + index).innerText;
                if (filename === '') {
                    filename = document.getElementById('filename_' + index).getElementsByTagName("img")[0].alt;
                    if (filename === '') {
                        alert('<?php echo trans('GetFileNameFail'); ?>');
                        closeModel(action);
                        return;
                    }
                }
                while (filename.substr(-1) === ' ')
                    filename = filename.substr(0, filename.length - 1);
            }
            document.getElementById(action + '_div').style.display = '';
            document.getElementById(action + '_label').innerText = filename;
            document.getElementById(action + '_sid').value = index;
            document.getElementById(action + '_hidden').value = filename;
            if (action === 'rename') document.getElementById(action + '_input').value = filename;

            var $e = event || window.event;
            var $scrollX = document.documentElement.scrollLeft || document.body.scrollLeft;
            var $scrollY = document.documentElement.scrollTop || document.body.scrollTop;
            var $x = $e.pageX || $e.clientX + $scrollX;
            var $y = $e.pageY || $e.clientY + $scrollY;
            if (action === 'create') {
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

        function submitOperate(action) {
            var submit = document.querySelector('#' + action + '_form input[type=submit]');
            var index = document.getElementById(action + '_sid').value;
            submit.setAttribute('disabled', 'disabled');

            var xhr = new XMLHttpRequest();
            xhr.open('POST', '', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            xhr.send(serializeForm(action + '_form'));
            xhr.onload = function () {
                var obj;
                if (xhr.status < 300) {
                    if (action === 'rename') {
                        obj = JSON.parse(xhr.responseText);
                        var filename = document.getElementById('filename_' + index);
                        filename.innerText = obj.name;
                        if (obj.hasOwnProperty('file')) {
                            filename.href = location.href + '/' + obj.name + '?preview';
                            filename.nextElementSibling.href = location.href + '/' + obj.name;
                        } else if (obj.hasOwnProperty('folder')) {
                            filename.href = location.href + '/' + obj.name;
                        }
                    } else if (action === 'move' || action === 'delete')
                        document.getElementById('tr' + index).parentNode.removeChild(document.getElementById('tr' + index));
                    else if (action === 'create') {
                        addFileToList(JSON.parse(xhr.responseText));
                    }
                } else {
                    alert(xhr.status + '\n' + xhr.responseText);
                }
                document.getElementById(action + '_div').style.display = 'none';
                document.getElementById('mask').style.display = 'none';
                submit.removeAttribute('disabled');
            };
            return false;
        }

        function addFileToList(obj) {
            var tr1 = document.createElement('tr');
            tr1.setAttribute('data-to', 1);
            var td1 = document.createElement('td');
            td1.setAttribute('class', 'file');
            var a1 = document.createElement('a');
            a1.href = location.href + '/' + obj.name;
            a1.innerText = obj.name;
            a1.target = '_blank';
            var td2 = document.createElement('td');
            td2.setAttribute('class', 'updated_at');
            td2.innerText = obj.lastModifiedDateTime.replace(/T/, ' ').replace(/Z/, '');
            var td3 = document.createElement('td');
            td3.setAttribute('class', 'size');
            td3.innerText = sizeFormat(obj.size);
            if (obj.folder) {
                a1.href += '/';
                document.getElementById('tr0').parentNode.insertBefore(tr1, document.getElementById('tr0').nextSibling);
            } else if (obj.file) {
                a1.href += '?preview';
                a1.className = 'filename';
                document.getElementById('tr0').parentNode.appendChild(tr1);
            }
            tr1.appendChild(td1);
            td1.appendChild(a1);
            tr1.appendChild(td2);
            tr1.appendChild(td3);
        }


        function serializeForm(formId) {

            function getElements(formId) {
                var form = document.getElementById(formId);
                var elements = [];
                var tagElements = form.getElementsByTagName('input');
                var j;
                for (j = 0; j < tagElements.length; j++) {
                    elements.push(tagElements[j]);
                }
                tagElements = form.getElementsByTagName('select');
                for (j = 0; j < tagElements.length; j++) {
                    elements.push(tagElements[j]);
                }
                tagElements = form.getElementsByTagName('textarea');
                for (j = 0; j < tagElements.length; j++) {
                    elements.push(tagElements[j]);
                }
                return elements;
            }

            function serializeElement(element) {
                var method = element.tagName.toLowerCase();
                var parameter;
                if (method === 'select') {
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
                    if (key.length === 0) return;
                    if (parameter[1].constructor !== Array) parameter[1] = [parameter[1]];
                    var values = parameter[1];
                    var results = [];
                    for (var i = 0; i < values.length; i++) {
                        results.push(key + '=' + encodeURIComponent(values[i]));
                    }
                    return results.join('&');
                }
            }

            var elements = getElements(formId);
            var queryComponents = [];
            for (var i = 0; i < elements.length; i++) {
                var queryComponent = serializeElement(elements[i]);
                if (queryComponent) {
                    queryComponents.push(queryComponent);
                }
            }
            return queryComponents.join('&');
        }
        <?php
        }
        ?>

        function login() {
            document.getElementById('mask').style.display = 'block';
            document.getElementById('login_div').style.display = '';
            document.getElementById('login_div').style.left = (document.body.clientWidth - document.getElementById('login_div').offsetWidth) / 2 + 'px';
            document.getElementById('login_div').style.top = (window.innerHeight - document.getElementById('login_div').offsetHeight) / 2 + document.body.scrollTop + 'px';
            document.getElementById('admin_password').focus();
        }
    </script>
    <script src="//unpkg.zhimg.com/ionicons@4.4.4/dist/ionicons.js"></script>
    </html>
    <?php
    $html = ob_get_clean();
    return response($html, $status_code);
}

