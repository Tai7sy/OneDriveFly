<?php


namespace Library;


class OneDrive
{
    /** @var \Doctrine\Common\Cache\FilesystemCache */
    private $cache;

    private $cache_prefix;

    private $oauth;

    /** @var string */
    private $access_token;

    private static $instance;

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
            $this->cache = new \Doctrine\Common\Cache\FilesystemCache(sys_get_temp_dir(), '.qdrive');
        } else {
            $this->cache = new \Doctrine\Common\Cache\ArrayCache();
        }

        $this->cache_prefix = crc32($refresh_token) . '_';

        if (!($this->access_token = $this->cache->fetch($this->cache_prefix . 'access_token'))) {
            $response = json_decode(curl_request(
                $this->oauth['oauth_url'] . 'token',
                'client_id=' . $this->oauth['client_id'] .
                '&client_secret=' . urlencode($this->oauth['client_secret']) .
                '&grant_type=refresh_token&requested_token_use=on_behalf_of&refresh_token=' . $refresh_token
            ), true);

            if (empty($response['access_token'])) {
                error_log('failed to get access_token. response' . json_encode($response));

                if (!empty($response['error_description'])) {
                    throw new \Exception('failed to get access_token: <br>' . $response['error_description']);
                }
                throw new \Exception('failed to get access_token.');
            }
            $this->access_token = $response['access_token'];
            $this->cache->save($this->cache_prefix . 'access_token', $response['access_token'], $response['expires_in'] - 60);
        }
    }

    public static function instance()
    {
        return self::$instance;
    }

    public function files($path = '/', $page = 1)
    {
        $files = $this->getInfo($path);
        // die($path . '<br><pre>' . json_encode($files, JSON_PRETTY_PRINT) . '</pre>');

        if (empty($files['folder'])) {
            error_log('failed to get files. response: ' . json_encode($files));
            if (!empty($files['error']) && !empty($files['error']['message'])) {
                throw new \Exception('failed to get files: <br>' . $files['error']['message']);
            }
            throw new \Exception('failed to get files.');
        }

        if ($files['folder']['childCount'] > count($files['children']) && $page > 1) {
            $files['children'] = $this->getChildren($path, $page);
        }
        return $files;
    }

    public function getContent($path)
    {
        $info = $this->getInfo($path);
        if (isset($info['error'])) {
            if ($info['error']['code'] === 'itemNotFound') {
                return false;
            }
            throw new \Exception($info['error']['message']);
        }
        if (empty($info['@microsoft.graph.downloadUrl'])) {
            throw new \Exception('get_content failed');
        }
        return file_get_contents($info['@microsoft.graph.downloadUrl']);
    }

    // https://docs.microsoft.com/en-us/graph/api/driveitem-get?view=graph-rest-1.0
    // https://docs.microsoft.com/zh-cn/graph/api/driveitem-put-content?view=graph-rest-1.0&tabs=http
    // https://developer.microsoft.com/zh-cn/graph/graph-explorer

    /**
     * get path with count of children
     * @param $path
     * @return mixed
     */
    private function getInfo($path)
    {
        $cache_key = $this->cache_prefix . 'path_' . $path;
        if (!($files = $this->cache->fetch($cache_key))) {
            $url = $this->oauth['api_url'];
            if ($path !== '/') {
                $url .= ':' . $path;
                if (substr($url, -1) == '/') $url = substr($url, 0, -1);
            }
            $url .= '?expand=children(select=name,size,file,folder,parentReference,lastModifiedDateTime)';

            $files = json_decode(curl_request($url, false, ['Authorization' => 'Bearer ' . $this->access_token]), true);
            $this->cache->save($cache_key, $files, 60);
        }
        return $files;
    }

    /**
     * get children with page but there is no total count....
     * @param $path
     * @param int $page
     * @return mixed
     */
    private function getChildren($path, $page = 1)
    {
        $url = $this->oauth['api_url'];
        if ($path !== '/') {
            $url .= ':' . $path;
            if (substr($url, -1) == '/') $url = substr($url, 0, -1);
            $url .= ':/children';
        } else {
            $url .= '/children';
        }
        $url .= '?$select=name,size,file,folder,parentReference,lastModifiedDateTime';

        $children = [];
        for ($current_page = 1; $current_page <= $page; $current_page++) {
            $cache_key = $this->cache_prefix . 'path_' . $path . '_page_' . $current_page;
            if (!($children = $this->cache->fetch($cache_key))) {
                $children = json_decode(curl_request($url, false, ['Authorization' => 'Bearer ' . $this->access_token]), true);
                $url = $children['@odata.nextLink'];
                $this->cache->save($cache_key, $children, 60);
            }
        }
        return $children;
    }
}