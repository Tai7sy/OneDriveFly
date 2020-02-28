<?php


namespace Library;


use Doctrine\Common\Cache\ArrayCache;
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
            $this->cache = new ArrayCache();
        }

        $this->cache_prefix = crc32($refresh_token) . '_';

        if (!($this->access_token = $this->cache->fetch($this->cache_prefix . 'access_token'))) {
            $response = curl_request(
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

    private function urlPrefix($path)
    {
        $url = $this->oauth['api_url'];
        if ($path !== '/') {
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
            $files['folder']['perPage'] = count($files['children']);
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
     */
    public function info($path, $thumbnails = false)
    {
        $cache_key = $this->cache_prefix . 'path_' . $path;
        if (!($files = $this->cache->fetch($cache_key))) {
            $url = $this->urlPrefix($path);
            if ($thumbnails)
                $url .= ':/thumbnails/0/medium';

            $files = json_decode(curl_request($url, 0, null, ['Authorization' => 'Bearer ' . $this->access_token]), true);
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
     */
    public function put($path, $contents)
    {
        $url = $this->urlPrefix($path) . ':/content';

        $response = curl_request($url, 'PUT', $contents, [
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
     */
    public function makeDirectory($path)
    {
        $url = $this->urlPrefix(substr($path, 0, strrpos($path, '/'))) . ':/children';
        $response = curl_request($url, 'POST', json_encode([
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
     */
    public function move($path, $target)
    {
        $url = $this->urlPrefix($path);
        $data = [];

        if (substr($path, 0, strrpos($path, '/')) !== substr($target, 0, strrpos($target, '/'))) {
            // parent changed
            $data['parentReference'] = [
                'path' => '/drive/root:' . urldecode(substr($target, 0, strrpos($target, '/')))
            ];
        } else {
            // name changed
            $data['name'] = urldecode(substr($target, strrpos($target, '/') + 1));
        }
        $response = curl_request($url, 'PATCH', json_encode($data), [
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
     */
    public function delete($path)
    {
        $url = $this->urlPrefix($path);

        $response = curl_request($url, 'DELETE', null, [
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
     */
    public function deleteDirectory($directory)
    {
        $url = $this->urlPrefix($directory);

        $response = curl_request($url, 'DELETE', null, [
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
     */
    public function uploadLink($path)
    {
        // $file = [
        //     'name' => urldecode(substr($path, strrpos($path, '/') + 1)),
        //     'size' => $size,
        //     'lastModified' => $lastModified
        // ];
        // '{"item": { "@microsoft.graph.conflictBehavior": "fail"  }}',$_SERVER['access_token']);
        $url = $this->urlPrefix($path) . ':/createUploadSession';
        $response = curl_request($url, 'POST', json_encode([
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
     */
    private function getFullInfo($path)
    {
        $cache_key = $this->cache_prefix . 'path_' . $path;
        if (!($files = $this->cache->fetch($cache_key))) {
            $url = $this->urlPrefix($path);
            $url .= '?expand=children(select=name,size,file,folder,parentReference,lastModifiedDateTime)';

            $files = json_decode(curl_request($url, 0, null, ['Authorization' => 'Bearer ' . $this->access_token]), true);
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
        $url .= ':/children?$select=name,size,file,folder,parentReference,lastModifiedDateTime';

        $children = [];
        for ($current_page = 1; $current_page <= $page; $current_page++) {
            $cache_key = $this->cache_prefix . 'path_' . $path . '_page_' . $current_page;
            if (!($children = $this->cache->fetch($cache_key))) {
                $response = curl_request($url, 0, null, ['Authorization' => 'Bearer ' . $this->access_token]);
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