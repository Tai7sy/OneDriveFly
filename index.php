<?php

include 'vendor/autoload.php';
include 'functions.php';
global $config;
$config = [
    'base_path' => getenv('base_path'), // 程序目录, 需要在环境变量配置
    'list_path' => '', // 需要列出的网盘路径, 默认根路径
    'refresh_token' => '',
];

function main_handler($event, $context)
{
    global $config;
    $event = json_decode(json_encode($event), true);
    $path = substr($event['path'], strlen($event['requestContext']['path']));
    $_GET = $event['queryString'];
    $_SERVER['PHP_SELF'] = $config['base_path'] . $path;
    if (!$config['base_path']) {
        return message('Missing env <code>base_path</code>');
    }
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
        return '<div>refresh_token:</div><textarea style="width: 100%; height: 80%">' . $ret['refresh_token'] . '</textarea>';
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

        $url = 'https://graph.microsoft.com/v1.0/me/drive/root';
        if ($path !== '/') $url .= ':/' . $path;
        $url .= '?expand=children(select=name,size,file,folder,parentReference,lastModifiedDateTime)';

        $files = json_decode(curl_request($url, false, ['Authorization' => 'Bearer ' . $access_token]), true);
        if (isset($files['children'])) {
            // is folder, then cache
            $cache->save('path_' . $path, $files, 60);
        }
    }
    return $files;
}

function list_files($path)
{
    $is_preview = false;
    if (substr($path, -8) === '/preview') {
        $is_preview = true;
        $path = substr($path, 0, -8);
    }
    $path = path_format($path);
    $files = fetch_files($path);
    if (isset($files['file']) && !$is_preview) {
        // is file && not preview mode
        return output('', 302, false, [
            'Location' => $files['@microsoft.graph.downloadUrl']
        ]);
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

function render_list($path, $files)
{
    global $config;
    $path = path_format(urldecode($path));
    @ob_start();
    ?>
    <!DOCTYPE html>
    <html lang="zh-cn">
    <head>
        <meta charset=utf-8>
        <meta http-equiv=X-UA-Compatible content="IE=edge">
        <meta name=viewport content="width=device-width,initial-scale=1">
        <title>QDrive</title>
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
        <a href="<?php echo $config['base_path']; ?>">QDrive</a>
    </h1>

    <div class="list-wrapper">
        <div class="list-container">
            <div class="list-header-container">
                <?php if ($path !== '/') {
                    $current_url = $_SERVER['PHP_SELF'];
                    if (substr($current_url, -8) === '/preview') {
                        $current_url = substr($current_url, 0, -8);
                    }
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
                <h3 class="table-header"><?php echo $path; ?></h3>
            </div>
            <div class="list-body-container">
                <?php

                if (isset($files['file'])) {

                    ?>
                    <div style="margin: 24px 4px 4px; text-align: center">
                        <div style="margin: 24px">
                            <a href="<?php echo $files['@microsoft.graph.downloadUrl'] ?>">
                                <ion-icon name="download" style="line-height: 16px;vertical-align: middle;"></ion-icon>&nbsp;下载
                            </a>
                            <textarea id="url" title="url" rows="1" style="width: 100%; margin-top: 12px;"><?php echo path_format($config['base_path'] . '/' . $path) ?></textarea>
                        </div>
                        <?php
                        $ext = strtolower(substr($path, strrpos($path, '.') + 1));
                        if (in_array($ext, ['bmp', 'gif', 'jpg', 'jpeg', 'jpe', 'jfif', 'tif', 'tiff', 'png', 'heic', 'webp'])) {
                            echo '
                        <img src="' . $files['@microsoft.graph.downloadUrl'] . '" alt="' . substr($path, strrpos($path, '/')) . '" style="width: 100%"/>
                        ';
                        } elseif (in_array($ext, ['mp4', 'mkv', 'avi', 'webm', 'ogg'])) {
                            echo '
                        <video controls="controls" style="width: 100%">
                            <source src="' . $files['@microsoft.graph.downloadUrl'] . '" type="video/webm">
                            Your browser does not support the video tag.
                        </video>';

                        } elseif (in_array($ext, ['mp3', 'flac', 'wav'])) {
                            echo '
                        <audio src="' . $files['@microsoft.graph.downloadUrl'] . '" controls="controls" style="width: 100%"></audio>
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
                            <th class="file" width="60%">文件</th>
                            <th class="updated_at" width="25%">修改时间</th>
                            <th class="size" width="15%">大小</th>
                        </tr>
                        <!-- Dirs -->
                        <?php

                        $readme = false;
                        if (isset($files['error'])) {
                            echo '<tr><td colspan="3">' . $files['error']['message'] . '<td></tr>';
                        } else {
                            foreach ($files['children'] as $file) {
                                // Folders
                                if (isset($file['folder'])) { ?>
                                    <tr data-to>
                                        <td class="file">
                                            <ion-icon name="folder"></ion-icon>
                                            <a href="<?php echo path_format($config['base_path'] . '/' . $path . '/' . $file['name']); ?>/">
                                                <?php echo $file['name']; ?>
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
                                    if (strtolower($file['name']) === 'readme.md')
                                        $readme = $file;
                                    ?>
                                    <tr data-to>
                                        <td class="file">
                                            <ion-icon name="document"></ion-icon>
                                            <a href="<?php echo path_format($config['base_path'] . '/' . $path . '/' . $file['name']); ?>/preview">
                                                <?php echo $file['name']; ?>
                                            </a>
                                            <a href="<?php echo path_format($config['base_path'] . '/' . $path . '/' . $file['name']) . ($file['name'] === 'preview' ? '/' : ''); ?>">
                                                <ion-icon name="download"></ion-icon>
                                            </a>
                                        </td>
                                        <td class="updated_at"><?php echo ISO_format($file['lastModifiedDateTime']); ?></td>
                                        <td class="size"><?php echo size_format($file['size']); ?></td>
                                    </tr>
                                <?php }
                            }

                        } ?>
                    </table>
                    <?php

                    if ($readme) {
                        echo '</div></div></div><div class="list-wrapper"><div class="list-container"><div class="list-header-container"><div class="readme">
<svg class="octicon octicon-book" viewBox="0 0 16 16" version="1.1" width="16" height="16" aria-hidden="true"><path fill-rule="evenodd" d="M3 5h4v1H3V5zm0 3h4V7H3v1zm0 2h4V9H3v1zm11-5h-4v1h4V5zm0 2h-4v1h4V7zm0 2h-4v1h4V9zm2-6v9c0 .55-.45 1-1 1H9.5l-1 1-1-1H2c-.55 0-1-.45-1-1V3c0-.55.45-1 1-1h5.5l1 1 1-1H15c.55 0 1 .45 1 1zm-8 .5L7.5 3H2v9h6V3.5zm7-.5H9.5l-.5.5V12h6V3z"></path></svg>
<span style="line-height: 16px;vertical-align: top;">'.$readme['name'].'</span>
<div class="markdown-body" id="readme"><textarea id="readme-md" style="display:none;">' . curl_request(fetch_files(path_format($path . '/' . $readme['name']))['@microsoft.graph.downloadUrl'])
                            . '</textarea></div></div>';
                    }

                }
                ?>
            </div>
        </div>
    </div>
    </body>
    <link rel="stylesheet" href="//unpkg.zhimg.com/github-markdown-css@3.0.1/github-markdown.css">
    <script type="text/javascript" src="//unpkg.zhimg.com/marked@0.6.2/marked.min.js"></script>
    <script type="text/javascript">
        var root = '<?php echo $config["base_path"]; ?>';

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

        var $url = document.getElementById('url');
        if ($url) {
            $url.innerHTML = location.protocol + '//' + location.host + $url.innerHTML
        }
    </script>
    <script src="//unpkg.zhimg.com/ionicons@4.4.4/dist/ionicons.js"></script>
    </html>

    <?php
    return output(ob_get_clean());
}

// for debug
if (php_sapi_name() !== 'cli') {
    $config['base_path'] = '/OneDrive_SCF';
    echo(main_handler(array(
        'headerParameters' =>
            array(),
        'headers' =>
            array(
                'accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3',
                'accept-encoding' => 'gzip, deflate, br',
                'accept-language' => 'zh-CN,zh;q=0.9,en;q=0.8,zh-TW;q=0.7',
                'cache-control' => 'max-age=0',
                'connection' => 'keep-alive',
                'endpoint-timeout' => '15',
                'host' => 'service-pzvjomp6-1251059978.gz.apigw.tencentcs.com',
                'referer' => 'https://service-pzvjomp6-1251059978.gz.apigw.tencentcs.com/release/QDrive',
                'upgrade-insecure-requests' => '1',
                'user-agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/73.0.3683.103 Safari/537.36',
                'x-anonymous-consumer' => 'true',
                'x-qualifier' => '$LATEST',
            ),
        'httpMethod' => 'GET',
        'path' => '/QDrive',
        'pathParameters' =>
            array(),
        'queryString' =>
            array(),
        'queryStringParameters' =>
            array(),
        'requestContext' =>
            array(
                'httpMethod' => 'ANY',
                'identity' =>
                    array(),
                'path' => '/QDrive',
                'serviceId' => 'service-pzvjomp6',
                'sourceIp' => '124.115.222.150',
                'stage' => 'release',
            )
    ), null)['body']);
}
