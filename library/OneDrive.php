<?php


namespace Library;


use Doctrine\Common\Cache\VoidCache;
use Doctrine\Common\Cache\FilesystemCache;
use Exception;

class OneDrive
{
    /** @var FilesystemCache */
    private $cache;

    private $cache_prefix;

    private $oauth;

    /** @var string */
    private $access_token;

    private static $instance;

    const PAGE_SIZE = 200;

    /**
     * OneDrive constructor.
     * @param $refresh_token
     * @param string $version
     * @param array $oauth
     * @throws Exception
     */
    public function __construct($refresh_token, $version = 'MS', $oauth = [])
    {
        self::$instance = $this;

        $this->oauth = [
            'redirect_uri' => 'https://scfonedrive.github.io'
        ];

        switch ($version) {
            default:
            case 'MS':
                // MS
                // https://portal.azure.com
                $this->oauth = array_merge($this->oauth, [
                    'client_id' => '4da3e7f2-bf6d-467c-aaf0-578078f0bf7c',
                    'client_secret' => '7/+ykq2xkfx:.DWjacuIRojIaaWL0QI6',
                    'oauth_url' => 'https://login.microsoftonline.com/common/oauth2/v2.0/',
                    'api_url' => 'https://graph.microsoft.com/v1.0/me/drive/root',
                    'scope' => 'https://graph.microsoft.com/Files.ReadWrite.All offline_access',
                ]);
                break;
            case 'CN':
                // CN
                // https://portal.azure.cn
                $this->oauth = array_merge($this->oauth, [
                    'client_id' => '04c3ca0b-8d07-4773-85ad-98b037d25631',
                    'client_secret' => 'h8@B7kFVOmj0+8HKBWeNTgl@pU/z4yLB',
                    'oauth_url' => 'https://login.partner.microsoftonline.cn/common/oauth2/v2.0/',
                    'api_url' => 'https://microsoftgraph.chinacloudapi.cn/v1.0/me/drive/root',
                    'scope' => 'https://microsoftgraph.chinacloudapi.cn/Files.ReadWrite.All offline_access',
                ]);
                break;
        }
        $this->oauth = array_merge($this->oauth, $oauth);

        // 只能用个文件缓存代替
        if (is_writable(sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'test.qdrive')) {
            $this->cache = new FilesystemCache(sys_get_temp_dir(), '.qdrive');
        } else {
            $this->cache = new VoidCache();
        }

        $this->cache_prefix = dechex(crc32($refresh_token)) . '_';

        if (!($this->access_token = $this->cache->fetch($this->cache_prefix . 'access_token'))) {
            $response = curl(
                $this->oauth['oauth_url'] . 'token',
                'POST',
                'client_id=' . $this->oauth['client_id'] .
                '&client_secret=' . urlencode($this->oauth['client_secret']) .
                '&grant_type=refresh_token&requested_token_use=on_behalf_of&refresh_token=' . $refresh_token
            );
            $json = json_decode($response, true);

            if (empty($json['access_token'])) {
                error_log('failed to get access_token. response:' . $response);

                if (!empty($json['error_description'])) {
                    throw new Exception('failed to get access_token: <br>' . $json['error_description']);
                }
                throw new Exception(APP_DEBUG ? $response : 'failed to get access_token.');
            }
            $this->access_token = $json['access_token'];
            $this->cache->save($this->cache_prefix . 'access_token', $json['access_token'], $json['expires_in'] - 60);
        }
    }

    public static function instance()
    {
        return self::$instance;
    }


    function get_refresh_token($function_name, $Region, $Namespace)
    {
        global $constStr;
        $url = path_format($_SERVER['PHP_SELF'] . '/');

        if ($_GET['authorization_code'] && isset($_GET['code'])) {
            $ret = json_decode(curl($_SERVER['oauth_url'] . 'token', 'client_id=' . $_SERVER['client_id'] . '&client_secret=' . $_SERVER['client_secret'] . '&grant_type=authorization_code&requested_token_use=on_behalf_of&redirect_uri=' . $_SERVER['redirect_uri'] . '&code=' . $_GET['code']), true);
            if (isset($ret['refresh_token'])) {
                $tmptoken = $ret['refresh_token'];
                $str = '
        refresh_token :<br>';
                for ($i = 1; strlen($tmptoken) > 0; $i++) {
                    $t['t' . $i] = substr($tmptoken, 0, 128);
                    $str .= '
            t' . $i . ':<textarea readonly style="width: 95%">' . $t['t' . $i] . '</textarea><br><br>';
                    $tmptoken = substr($tmptoken, 128);
                }
                $str .= '
        Add t1-t' . --$i . ' to environments.
        <script>
            var texta=document.getElementsByTagName(\'textarea\');
            for(i=0;i<texta.length;i++) {
                texta[i].style.height = texta[i].scrollHeight + \'px\';
            }
            document.cookie=\'language=; path=/\';
        </script>';
                if (getenv('SecretId') != '' && getenv('SecretKey') != '') {
                    echo scf_update_env($t, $function_name, $Region, $Namespace);
                    $str .= '
            <meta http-equiv="refresh" content="5;URL=' . $url . '">';
                }
                return message($str, $constStr['WaitJumpIndex'][$constStr['language']]);
            }
            return message('<pre>' . json_encode($ret, JSON_PRETTY_PRINT) . '</pre>', 500);
        }

        if ($_GET['install2']) {
            if (getenv('Onedrive_ver') == 'MS' || getenv('Onedrive_ver') == 'CN' || getenv('Onedrive_ver') == 'MSC') {
                return message('
    <a href="" id="a1">' . $constStr['JumptoOffice'][$constStr['language']] . '</a>
    <script>
        url=location.protocol + "//" + location.host + "' . $url . '";
        url="' . $_SERVER['oauth_url'] . 'authorize?scope=' . $_SERVER['scope'] . '&response_type=code&client_id=' . $_SERVER['client_id'] . '&redirect_uri=' . $_SERVER['redirect_uri'] . '&state=' . '"+encodeURIComponent(url);
        document.getElementById(\'a1\').href=url;
        //window.open(url,"_blank");
        location.href = url;
    </script>
    ', $constStr['Wait'][$constStr['language']] . ' 1s', 201);
            }
        }

        if ($_GET['install1']) {
            // echo $_POST['Onedrive_ver'];
            if ($_POST['Onedrive_ver'] == 'MS' || $_POST['Onedrive_ver'] == 'CN' || $_POST['Onedrive_ver'] == 'MSC') {
                $tmp['Onedrive_ver'] = $_POST['Onedrive_ver'];
                $tmp['language'] = $_COOKIE['language'];
                $tmp['client_id'] = $_POST['client_id'];
                $tmp['client_secret'] = equal_replace(base64_encode($_POST['client_secret']));
                $response = json_decode(scf_update_env($tmp, $_SERVER['function_name'], $_SERVER['Region'], $Namespace), true)['Response'];
                sleep(2);
                $title = $constStr['MayinEnv'][$constStr['language']];
                $html = $constStr['Wait'][$constStr['language']] . ' 3s<meta http-equiv="refresh" content="3;URL=' . $url . '?install2">';
                if (isset($response['Error'])) {
                    $html = $response['Error']['Code'] . '<br>
' . $response['Error']['Message'] . '<br><br>
function_name:' . $_SERVER['function_name'] . '<br>
Region:' . $_SERVER['Region'] . '<br>
namespace:' . $Namespace . '<br>
<button onclick="location.href = location.href;">' . $constStr['Reflesh'][$constStr['language']] . '</button>';
                    $title = 'Error';
                }
                return message($html, $title, 201);
            }
        }

        if ($_GET['install0']) {
            if (getenv('SecretId') == '' || getenv('SecretKey') == '') return message($constStr['SetSecretsFirst'][$constStr['language']] . '<button onclick="location.href = location.href;">' . $constStr['Reflesh'][$constStr['language']] . '</button><br>' . '(<a href="https://console.cloud.tencent.com/cam/capi" target="_blank">' . $constStr['Create'][$constStr['language']] . ' SecretId & SecretKey</a>)', 'Error', 500);
            $response = json_decode(scf_update_configuration($_SERVER['function_name'], $_SERVER['Region'], $Namespace), true)['Response'];
            if (isset($response['Error'])) {
                $html = $response['Error']['Code'] . '<br>
' . $response['Error']['Message'] . '<br><br>
function_name:' . $_SERVER['function_name'] . '<br>
Region:' . $_SERVER['Region'] . '<br>
namespace:' . $Namespace . '<br>
<button onclick="location.href = location.href;">' . $constStr['Reflesh'][$constStr['language']] . '</button>';
                $title = 'Error';
            } else {
                if ($constStr['language'] != 'zh-cn') {
                    $linklang = 'en-us';
                } else $linklang = 'zh-cn';
                $ru = "https://developer.microsoft.com/" . $linklang . "/graph/quick-start?appID=_appId_&appName=_appName_&redirectUrl=" . $_SERVER['redirect_uri'] . "&platform=option-php";
                $deepLink = "/quickstart/graphIO?publicClientSupport=false&appName=one_scf&redirectUrl=" . $_SERVER['redirect_uri'] . "&allowImplicitFlow=false&ru=" . urlencode($ru);
                $app_url = "https://apps.dev.microsoft.com/?deepLink=" . urlencode($deepLink);
                $html = '
    <form action="?install1" method="post">
        Onedrive_Ver：<br>
        <label><input type="radio" name="Onedrive_ver" value="MS" checked>MS: ' . $constStr['OndriveVerMS'][$constStr['language']] . '</label><br>
        <label><input type="radio" name="Onedrive_ver" value="CN">CN: ' . $constStr['OndriveVerCN'][$constStr['language']] . '</label><br>
        <label><input type="radio" name="Onedrive_ver" value="MSC" onclick="document.getElementById(\'secret\').style.display=\'\';">MSC: ' . $constStr['OndriveVerMSC'][$constStr['language']] . '
            <div id="secret" style="display:none">
                <a href="' . $app_url . '" target="_blank">' . $constStr['GetSecretIDandKEY'][$constStr['language']] . '</a><br>
                client_secret:<input type="text" name="client_secret"><br>
                client_id(12345678-90ab-cdef-ghij-klmnopqrstuv):<input type="text" name="client_id"><br>
            </div>
        </label><br>
        <input type="submit" value="' . $constStr['Submit'][$constStr['language']] . '">
    </form>';
                $title = 'Install';
            }
            return message($html, $title, 201);
        }

        $html .= '
    <form action="?install0" method="post">
    language:<br>';
        foreach ($constStr['languages'] as $key1 => $value1) {
            $html .= '
    <label><input type="radio" name="language" value="' . $key1 . '" ' . ($key1 == $constStr['language'] ? 'checked' : '') . ' onclick="changelanguage(\'' . $key1 . '\')">' . $value1 . '</label><br>';
        }
        $html .= '<br>
    <input type="submit" value="' . $constStr['Submit'][$constStr['language']] . '">
    </form>
    <script>
        function changelanguage(str)
        {
            document.cookie=\'language=\'+str+\'; path=/\';
            location.href = location.href;
        }
    </script>';
        $title = $constStr['SelectLanguage'][$constStr['language']];
        return message($html, $title, 201);
    }


    private function urlPrefix($path)
    {
        $url = $this->oauth['api_url'];
        if ($path && $path !== '/') {
            $url .= ':' . $path;
            while (substr($url, -1) == '/') $url = substr($url, 0, -1);
        }
        return $url;
    }

    /**
     * @param string $path
     * @param int $page
     * @return mixed
     * @throws Exception
     */
    public function infos($path = '/', $page = 1)
    {
        $files = $this->getFullInfo($path);
        // die($path . '<br><pre>' . json_encode($files, JSON_PRETTY_PRINT) . '</pre>');
        if (isset($files['folder'])) {
            // is folder
            $files['folder']['currentPage'] = $page;
            $files['folder']['perPage'] = self::PAGE_SIZE;
            $files['folder']['lastPage'] = ceil($files['folder']['childCount'] / $files['folder']['perPage']);

            if ($files['folder']['childCount'] > $files['folder']['perPage'] && $page > 1) {
                $files['children'] = $this->getFullChildren($path, $page);
            }
        } elseif (isset($files['file'])) {
            // is file
        } else {
            error_log('failed to get files. response: ' . json_encode($files));
            if (!empty($files['error']) && !empty($files['error']['message'])) {
                throw new Exception($files['error']['message']);
            }
            throw new Exception(APP_DEBUG ? json_encode($files) : 'failed to get files.');
        }

        return $files;
    }

    /**
     * get info of target file
     * @param string $path
     * @param bool $thumbnails
     * @return mixed
     * @throws Exception
     */
    public function info($path, $thumbnails = false)
    {
        $cache_key = $this->cache_prefix . 'path_' . $path;
        if (!($files = $this->cache->fetch($cache_key))) {
            $url = $this->urlPrefix($path);
            if ($thumbnails) {
                $url .= ':/thumbnails/0/medium';
            }

            $files = json_decode(curl($url, 0, null, ['Authorization' => 'Bearer ' . $this->access_token]), true);
            if (is_array($files)) {
                $this->cache->save($cache_key, $files, 60);
            } else {
                if ($files == null) {
                    $files = [
                        'error' => [
                            'message' => 'timeout'
                        ]
                    ];
                }
            }
        }
        return $files;
    }

    /**
     * Get the contents of a file.
     *
     * @param string $path
     * @return bool|false|string
     * @throws Exception
     */
    public function get($path)
    {
        $info = $this->info($path);
        if (isset($info['error'])) {
            if ($info['error']['code'] === 'itemNotFound') {
                return false;
            }
            throw new Exception($info['error']['message']);
        }
        if (empty($info['@microsoft.graph.downloadUrl'])) {

            throw new Exception('get_content failed');
        }
        return file_get_contents($info['@microsoft.graph.downloadUrl']);
    }

    /**
     * Write the contents of a file.
     *
     * @param string $path
     * @param string $contents
     * @return array
     * @throws Exception
     */
    public function put($path, $contents)
    {
        $url = $this->urlPrefix($path) . ':/content';

        $response = curl($url, 'PUT', $contents, [
            'Content-Type' => 'text/plain',
            'Content-Length' => strlen($contents),
            'Authorization' => 'Bearer ' . $this->access_token
        ]);
        return json_decode($response, true) ?? $response;
    }

    /**
     * Create a directory.
     *
     * @param string $path
     * @return bool
     * @static
     * @throws Exception
     */
    public function makeDirectory($path)
    {
        $parent = substr($path, 0, strrpos($path, '/') + 1);
        $url = $this->urlPrefix($parent) . ($parent !== '/' ? ':' : '') . '/children';
        $response = curl($url, 'POST', json_encode([
            'name' => urldecode(substr($path, strrpos($path, '/') + 1)),
            'folder' => new \stdClass,
            '@microsoft.graph.conflictBehavior' => 'rename'
        ]), [
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $this->access_token
        ]);
        return json_decode($response, true) ?? $response;
    }

    /**
     * Move a file to a new location.
     *
     * @param string $path
     * @param string $target
     * @return array
     * @static
     * @throws Exception
     */
    public function move($path, $target)
    {
        $url = $this->urlPrefix($path);
        $data = [];

        $parent = substr($path, 0, strrpos($path, '/') + 1);
        $parent_new = substr($target, 0, strrpos($target, '/') + 1);
        if ($parent !== $parent_new) {
            // parent changed
            $data['parentReference'] = [
                'path' => '/drive/root:' . urldecode($parent_new)
            ];
        } else {
            // name changed
            $data['name'] = urldecode(substr($target, strrpos($target, '/') + 1));
        }
        $response = curl($url, 'PATCH', json_encode($data), [
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $this->access_token
        ]);
        return json_decode($response, true) ?? $response;
    }

    /**
     * Delete the file at a given path.
     *
     * @param string $path
     * @return array
     * @static
     * @throws Exception
     */
    public function delete($path)
    {
        $url = $this->urlPrefix($path);

        $response = curl($url, 'DELETE', null, [
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $this->access_token
        ], $status);
        return json_decode($response, true) ?? ['status' => $status];
    }

    /**
     * Delete a directory.
     *
     * @param string $directory
     * @return array
     * @static
     * @throws Exception
     */
    public function deleteDirectory($directory)
    {
        $url = $this->urlPrefix($directory);

        $response = curl($url, 'DELETE', null, [
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $this->access_token
        ]);
        return json_decode($response, true) ?? $response;
    }

    /**
     * get a direct link for upload
     *
     * @param string $path
     * @return bool|mixed|string
     * @throws Exception
     */
    public function uploadUrl($path)
    {
        // $file = [
        //     'name' => urldecode(substr($path, strrpos($path, '/') + 1)),
        //     'size' => $size,
        //     'lastModified' => $lastModified
        // ];
        // '{"item": { "@microsoft.graph.conflictBehavior": "fail"  }}',$_SERVER['access_token']);

        $url = $this->urlPrefix($path) . ':/createUploadSession';
        $response = curl($url, 'POST', json_encode([
            'item' => [
                '@microsoft.graph.conflictBehavior' => 'fail'
            ]
        ]), [
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $this->access_token
        ]);
        return json_decode($response, true) ?? $response;
    }


    // https://docs.microsoft.com/en-us/graph/api/driveitem-get?view=graph-rest-1.0
    // https://docs.microsoft.com/zh-cn/graph/api/driveitem-put-content?view=graph-rest-1.0&tabs=http
    // https://developer.microsoft.com/zh-cn/graph/graph-explorer

    /**
     * get path with count of children
     * @param $path
     * @return mixed
     * @throws Exception
     */
    private function getFullInfo($path)
    {
        $cache_key = $this->cache_prefix . 'path_' . $path;
        if (!($files = $this->cache->fetch($cache_key))) {
            $url = $this->urlPrefix($path);
            $url .= '?expand=children(select=name,size,file,folder,parentReference,lastModifiedDateTime)';

            $files = json_decode(curl($url, 0, null, ['Authorization' => 'Bearer ' . $this->access_token]), true);
            if (is_array($files)) {
                $this->cache->save($cache_key, $files, 60);
            } else {
                if ($files == null) {
                    $files = [
                        'error' => [
                            'message' => 'timeout'
                        ]
                    ];
                }
            }
        }
        return $files;
    }

    /**
     * get children with page but there is no total count....
     * @param $path
     * @param int $page
     * @return mixed
     * @throws Exception
     */
    private function getFullChildren($path, $page = 1)
    {
        $url = $this->urlPrefix($path);
        $url .= ($path !== '/' ? ':' : '') . '/children?$select=name,size,file,folder,parentReference,lastModifiedDateTime';

        $children = [];
        for ($current_page = 1; $current_page <= $page; $current_page++) {
            $cache_key = $this->cache_prefix . 'path_' . $path . '_page_' . $current_page;
            if (!($children = $this->cache->fetch($cache_key))) {
                $response = curl($url, 0, null, ['Authorization' => 'Bearer ' . $this->access_token]);
                $children = json_decode($response, true);
                if (isset($children['error'])) {
                    throw new Exception($children['error']['message']);
                }
                $this->cache->save($cache_key, $children, 60);
                if ($current_page === $page) {
                    break;
                }
                if (empty($children['@odata.nextLink'])) {
                    throw new Exception(APP_DEBUG ? $response : 'get children failed');
                }
                $url = $children['@odata.nextLink'];
            }
        }
        return $children['value'];
    }
}