<?php
namespace {

/**
 * OneDriveFly
 * @author 风铃
 * @see https://github.com/Tai7sy/OneDriveFly
 */

class Config
{
    public static $config = [
        'name' => 'OneDriveFly',
        'platform' => 'Normal',
        'multi' => 0,
        'accounts' => [
            [
                'name' => 'disk_1',
                'path' => '',
                'path_image' => ['/some_public/image'],
                'refresh_token' => '',
            ],
        ],
        'debug' => true,
        'proxy' => '',
        'password_file' => 'password',
        'admin_password' => '123456',
    ];
}
}?>
<?php
namespace {
 function get_timezone($timezone = '8') { $timezones = array( '-12' => 'Pacific/Kwajalein', '-11' => 'Pacific/Samoa', '-10' => 'Pacific/Honolulu', '-9' => 'America/Anchorage', '-8' => 'America/Los_Angeles', '-7' => 'America/Denver', '-6' => 'America/Mexico_City', '-5' => 'America/New_York', '-4' => 'America/Caracas', '-3.5' => 'America/St_Johns', '-3' => 'America/Argentina/Buenos_Aires', '-2' => 'America/Noronha', '-1' => 'Atlantic/Azores', '0' => 'UTC', '1' => 'Europe/Paris', '2' => 'Europe/Helsinki', '3' => 'Europe/Moscow', '3.5' => 'Asia/Tehran', '4' => 'Asia/Baku', '4.5' => 'Asia/Kabul', '5' => 'Asia/Karachi', '5.5' => 'Asia/Calcutta', '6' => 'Asia/Dhaka', '6.5' => 'Asia/Rangoon', '7' => 'Asia/Bangkok', '8' => 'Asia/Shanghai', '9' => 'Asia/Tokyo', '9.5' => 'Australia/Darwin', '10' => 'Pacific/Guam', '11' => 'Asia/Magadan', '12' => 'Asia/Kamchatka' ); if (empty($timezone)) $timezone = '8'; return $timezones[$timezone]; } function error_trace($e) { $str = '<pre>' . $e->getTraceAsString() . '</pre>'; $str = str_replace(realpath(__DIR__ . '/../') . DIRECTORY_SEPARATOR, '', $str); return $str; } function path_format($path, $encode = false, $split = '/') { $items = explode($split, $path); $result = []; foreach ($items as $item) { if (empty($item)) continue; if ($item === '.') continue; if ($item === '..') { if (count($result) > 0) { array_pop($result); continue; } } if ($encode && strpos($path, '%') === FALSE) { $item = urlencode($item); $item = str_replace('+', '%20', $item); array_push($result, $item); } else { array_push($result, $item); } } return $split . join($split, $result); } function size_format($byte) { $i = 0; while (abs($byte) >= 1024) { $byte = $byte / 1024; $i++; if ($i == 3) break; } $units = array('B', 'KB', 'MB', 'GB', 'TB'); $ret = round($byte, 2); return ($ret . ' ' . $units[$i]); } function time_format($ISO) { $ISO = str_replace('T', ' ', $ISO); $ISO = str_replace('Z', ' ', $ISO); return date('Y-m-d H:i:s', strtotime($ISO . " UTC")); } function curl($url, $method = 0, $data = null, $headers = [], &$status = null) { if (!isset($headers['Accept'])) $headers['Accept'] = '*/*'; if (!isset($headers['Referer'])) $headers['Referer'] = $url; if (!isset($headers['Content-Type'])) $headers['Content-Type'] = 'application/x-www-form-urlencoded'; $sendHeaders = array(); foreach ($headers as $headerName => $headerVal) { $sendHeaders[] = $headerName . ': ' . $headerVal; } $ch = curl_init(); curl_setopt($ch, CURLOPT_URL, $url); if ($data !== null || $method === 1) { curl_setopt($ch, CURLOPT_POST, 1); curl_setopt($ch, CURLOPT_POSTFIELDS, $data); } if (is_string($method)) { curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method); } global $config; if (!empty($config['proxy'])) { $proxy = $config['proxy']; if (strpos($proxy, 'socks4://')) { $proxy = str_replace('socks4://', $proxy, $proxy); $proxy_type = CURLPROXY_SOCKS4; } elseif (strpos($proxy, 'socks4a://')) { $proxy = str_replace('socks4a://', $proxy, $proxy); $proxy_type = CURLPROXY_SOCKS4A; } elseif (strpos($proxy, 'socks5://')) { $proxy = str_replace('socks5://', $proxy, $proxy); $proxy_type = CURLPROXY_SOCKS5_HOSTNAME; } else { $proxy = str_replace('http://', $proxy, $proxy); $proxy = str_replace('https://', $proxy, $proxy); $proxy_type = CURLPROXY_HTTP; } curl_setopt($ch, CURLOPT_PROXY, $proxy); curl_setopt($ch, CURLOPT_PROXYTYPE, $proxy_type); } curl_setopt($ch, CURLOPT_TIMEOUT, 10); curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10); curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); curl_setopt($ch, CURLOPT_HEADER, 0); curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0); curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0); curl_setopt($ch, CURLOPT_HTTPHEADER, $sendHeaders); $response = curl_exec($ch); $status = curl_getinfo($ch, CURLINFO_HTTP_CODE); if (curl_errno($ch)) { throw new \Exception(curl_error($ch), 0); } curl_close($ch); return $response; }
}?>
<?php
namespace Library { class Ext { const IMG = ['ico', 'bmp', 'gif', 'jpg', 'jpeg', 'jpe', 'jfif', 'tif', 'tiff', 'png', 'heic', 'webp']; const MUSIC = ['mp3', 'wma', 'flac', 'wav', 'ogg']; const OFFICE = ['doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx']; const TXT = ['txt', 'bat', 'sh', 'js', 'json', 'css', 'php', 'asp', 'htm', 'html', 'c', 'cpp', 'h', 'hpp', 'py', 'md']; const VIDEO = ['mp4', 'webm', 'mkv', 'mov', 'flv', 'blv', 'avi', 'wmv']; const ZIP = ['zip', 'rar', '7z', 'gz', 'tar']; } } namespace Library { class Lang { private static $language; public static function init($language) { $supported = ['zh-CN', 'en-US']; if (in_array($language, $supported)) { self::$language = $language; } else { self::$language = $supported[0]; } } public static function language() { return self::$language; } public static function all() { global $LANG; return $LANG; } public static function get($key = null, $replace = array()) { global $LANG; if ($key == null) { return self::all(); } $string = $LANG; $key = explode('.', $key); $i = 0; while ($i < count($key)) { $string = $string[$key[$i++]]; } $string = $string[self::$language]; foreach ($replace as $var => $val) { $string = str_replace(":{$var}", $val, $string); } return $string; } } global $LANG; $LANG = ['languages' => ['en-US' => 'English', 'zh-CN' => '中文'], 'Week' => ['0' => ['en-US' => 'Sunday', 'zh-CN' => '星期日'], '1' => ['en-US' => 'Monday', 'zh-CN' => '星期一'], '2' => ['en-US' => 'Tuesday', 'zh-CN' => '星期二'], '3' => ['en-US' => 'Wednesday', 'zh-CN' => '星期三'], '4' => ['en-US' => 'Thursday', 'zh-CN' => '星期四'], '5' => ['en-US' => 'Friday', 'zh-CN' => '星期五'], '6' => ['en-US' => 'Saturday', 'zh-CN' => '星期六']], 'EnvironmentsDescription' => ['admin' => ['en-US' => 'The admin password, Login button will not show when empty', 'zh-CN' => '管理密码，不添加时不显示登录页面且无法登录。'], 'adminloginpage' => ['en-US' => 'if set, the Login button will not display, and the login page no longer \'?admin\', it is \'?{this value}\'.', 'zh-CN' => '如果设置，登录按钮及页面隐藏。管理登录的页面不再是\'?admin\'，而是\'?此设置的值\'。'], 'domain_path' => ['en-US' => 'more custom domain, format is a1.com:/dirto/path1|b2.com:/path2', 'zh-CN' => '使用多个自定义域名时，指定每个域名看到的目录。格式为a1.com:/dirto/path1|b1.com:/path2，比private_path优先。'], 'imgup_path' => ['en-US' => 'Set guest upload dir, before set this, the files in this dir will show as normal.', 'zh-CN' => '设置图床路径，不设置这个值时该目录内容会正常列文件出来，设置后只有上传界面，不显示其中文件（登录后显示）。'], 'passfile' => ['en-US' => 'The password of dir will save in this file.', 'zh-CN' => '自定义密码文件的名字，可以是\'pppppp\'，也可以是\'aaaa.txt\'等等；列目录时不会显示，只有知道密码才能查看或下载此文件。密码是这个文件的内容，可以空格、可以中文；'], 'private_path' => ['en-US' => 'Show this Onedrive dir when through custom domain, default is \'/\'.', 'zh-CN' => '使用自定义域名访问时，显示网盘文件的路径，不设置时默认为根目录。'], 'public_path' => ['en-US' => 'Show this Onedrive dir when through the long url of API Gateway; public show files less than private.', 'zh-CN' => '使用API长链接访问时，显示网盘文件的路径，不设置时默认为根目录；不能是private_path的上级（public看到的不能比private多，要么看到的就不一样）。'], 'sitename' => ['en-US' => 'sitename', 'zh-CN' => '网站的名称'], 'language' => ['en-US' => 'en or zh-CN', 'zh-CN' => '目前en 或 zh-CN'], 'SecretId' => ['en-US' => 'the SecretId of tencent cloud', 'zh-CN' => '腾讯云API的Id'], 'SecretKey' => ['en-US' => 'the SecretKey of tencent cloud', 'zh-CN' => '腾讯云API的Key'], 'Region' => ['en-US' => 'the Region of SCF', 'zh-CN' => 'SCF程序所在地区'], 'Onedrive_ver' => ['en-US' => 'Onedrive version', 'zh-CN' => 'Onedrive版本']], 'SetSecretsFirst' => ['en-US' => 'Set SecretId & SecretKey in Environments first! Then reflesh.', 'zh-CN' => '先在环境变量设置SecretId和SecretKey！再刷新。'], 'RefleshtoLogin' => ['en-US' => '<font color="red">Reflesh</font> and login.', 'zh-CN' => '请<font color="red">刷新</font>页面后重新登录'], 'AdminLogin' => ['en-US' => 'Admin Login', 'zh-CN' => '管理登录'], 'LoginSuccess' => ['en-US' => 'Login Success!', 'zh-CN' => '登录成功，正在跳转'], 'InputPassword' => ['en-US' => 'Input Password', 'zh-CN' => '输入密码'], 'Login' => ['en-US' => 'Login', 'zh-CN' => '登录'], 'Encrypt' => ['en-US' => 'Encrypt', 'zh-CN' => '加密'], 'SetpassfileBfEncrypt' => ['en-US' => 'Your should set \'password_file\' before encrypt', 'zh-CN' => '先在设置password_file才能加密'], 'updateProgram' => ['en-US' => 'Update Program', 'zh-CN' => '一键更新'], 'UpdateSuccess' => ['en-US' => 'Program update Success!', 'zh-CN' => '程序升级成功！'], 'Setup' => ['en-US' => 'Setup', 'zh-CN' => '设置'], 'NotNeedUpdate' => ['en-US' => 'Not Need Update', 'zh-CN' => '不需要更新'], 'Back' => ['en-US' => 'Back', 'zh-CN' => '返回'], 'Home' => ['en-US' => 'Home', 'zh-CN' => '首页'], 'NeedUpdate' => ['en-US' => 'Program can update<br>Click setup in Operate at top.', 'zh-CN' => '可以升级程序<br>在上方管理菜单中<br>进入设置页面升级'], 'Operate' => ['en-US' => 'Operate', 'zh-CN' => '管理'], 'Logout' => ['en-US' => 'Logout', 'zh-CN' => '登出'], 'Create' => ['en-US' => 'Create', 'zh-CN' => '新建'], 'Download' => ['en-US' => 'download', 'zh-CN' => '下载'], 'ClickToEdit' => ['en-US' => 'Click to edit', 'zh-CN' => '点击后编辑'], 'Save' => ['en-US' => 'Save', 'zh-CN' => '保存'], 'FileNotSupport' => ['en-US' => 'File not support preview.', 'zh-CN' => '文件格式不支持预览'], 'File' => ['en-US' => 'File', 'zh-CN' => '文件'], 'ShowThumbnails' => ['en-US' => 'Thumbnails', 'zh-CN' => '图片缩略'], 'EditTime' => ['en-US' => 'EditTime', 'zh-CN' => '修改时间'], 'Size' => ['en-US' => 'Size', 'zh-CN' => '大小'], 'Rename' => ['en-US' => 'Rename', 'zh-CN' => '重命名'], 'Move' => ['en-US' => 'Move', 'zh-CN' => '移动'], 'Delete' => ['en-US' => 'Delete', 'zh-CN' => '删除'], 'PrePage' => ['en-US' => 'PrePage', 'zh-CN' => '上一页'], 'NextPage' => ['en-US' => 'NextPage', 'zh-CN' => '下一页'], 'Upload' => ['en-US' => 'Upload', 'zh-CN' => '上传'], 'Submit' => ['en-US' => 'Submit', 'zh-CN' => '确认'], 'Close' => ['en-US' => 'Close', 'zh-CN' => '关闭'], 'InputPasswordUWant' => ['en-US' => 'Input Password you Want', 'zh-CN' => '输入想要设置的密码'], 'ParentDir' => ['en-US' => 'Parent Dir', 'zh-CN' => '上一级目录'], 'Folder' => ['en-US' => 'Folder', 'zh-CN' => '文件夹'], 'Name' => ['en-US' => 'Name', 'zh-CN' => '名称'], 'Content' => ['en-US' => 'Content', 'zh-CN' => '内容'], 'CancelEdit' => ['en-US' => 'Cancel Edit', 'zh-CN' => '取消编辑'], 'GetFileNameFail' => ['en-US' => 'Fail to Get File Name!', 'zh-CN' => '获取文件名失败！'], 'GetUploadLink' => ['en-US' => 'Get Upload Link', 'zh-CN' => '获取上传链接'], 'UpFileTooLarge' => ['en-US' => 'The File is too Large!', 'zh-CN' => '大于15G，终止上传。'], 'UploadStart' => ['en-US' => 'Upload Start', 'zh-CN' => '开始上传'], 'UploadStartAt' => ['en-US' => 'Start At', 'zh-CN' => '开始于'], 'ThisTime' => ['en-US' => 'This Time', 'zh-CN' => '本次'], 'LastUpload' => ['en-US' => 'Last time Upload', 'zh-CN' => '上次上传'], 'AverageSpeed' => ['en-US' => 'AverageSpeed', 'zh-CN' => '平均速度'], 'CurrentSpeed' => ['en-US' => 'CurrentSpeed', 'zh-CN' => '即时速度'], 'Expect' => ['en-US' => 'Expect', 'zh-CN' => '预计还要'], 'EndAt' => ['en-US' => 'End At', 'zh-CN' => '结束于'], 'UploadErrorUpAgain' => ['en-US' => 'Maybe error, do upload again.', 'zh-CN' => '可能出错，重新上传。'], 'UploadComplete' => ['en-US' => 'Upload Complete', 'zh-CN' => '上传完成'], 'UploadFail23' => ['en-US' => 'Upload Fail, contain #.', 'zh-CN' => '目录或文件名含有#，上传失败。'], 'defaultSitename' => ['en-US' => 'Set sitename in Environments', 'zh-CN' => '请在环境变量添加sitename'], 'MayinEnv' => ['en-US' => 'The \'Onedrive_ver\' may in Environments', 'zh-CN' => 'Onedrive_ver应该已经写入环境变量'], 'Wait' => ['en-US' => 'Wait', 'zh-CN' => '稍等'], 'WaitJumpIndex' => ['en-US' => 'Wait 5s jump to Home page', 'zh-CN' => '等5s跳到首页'], 'JumptoOffice' => ['en-US' => 'Login Office and Get a refresh_token', 'zh-CN' => '跳转到Office，登录获取refresh_token'], 'OndriveVerMS' => ['en-US' => 'default(Onedrive, Onedrive for business)', 'zh-CN' => '默认（支持商业版与个人版）'], 'OndriveVerCN' => ['en-US' => 'Onedrive in China', 'zh-CN' => '世纪互联版'], 'OndriveVerMSC' => ['en-US' => 'default but use customer app id & secret', 'zh-CN' => '国际版，自己申请应用ID与机密'], 'GetSecretIDandKEY' => ['en-US' => 'Get customer app id & secret', 'zh-CN' => '申请应用ID与机密'], 'Reflesh' => ['en-US' => 'Reflesh', 'zh-CN' => '刷新'], 'SelectLanguage' => ['en-US' => 'Select Language', 'zh-CN' => '选择语言']]; } namespace Library { use Doctrine\Common\Cache\VoidCache; use Doctrine\Common\Cache\FilesystemCache; use Exception; class OneDrive { private $cache; private $cache_prefix; private $oauth; private $access_token; private static $instance; const PAGE_SIZE = 200; const APP_MS = ['redirect_uri' => 'https://onedrivefly.github.io', 'client_id' => '298004f7-c751-4d56-aba3-b058c0154fd2', 'client_secret' => '-^(!BpF-l9/z#[+*5t)alg;[V@;;)_];)@j#^E;T(&^4uD;*&?#2)>H?', 'oauth_url' => 'https://login.microsoftonline.com/common/oauth2/v2.0/', 'api_url' => 'https://graph.microsoft.com/v1.0/me/drive/root', 'scope' => 'https://graph.microsoft.com/Files.ReadWrite.All offline_access']; const APP_MS_CN = ['redirect_uri' => 'https://onedrivefly.github.io', 'client_id' => '5010206d-75ac-4af1-8fc7-70d1576326f0', 'client_secret' => 'Gz5JsJkR6L-rA]w23RMPeQdL:.iZ1Pja', 'oauth_url' => 'https://login.partner.microsoftonline.cn/common/oauth2/v2.0/', 'api_url' => 'https://microsoftgraph.chinacloudapi.cn/v1.0/me/drive/root', 'scope' => 'https://microsoftgraph.chinacloudapi.cn/Files.ReadWrite.All offline_access']; public function __construct($refresh_token, $provider = 'MS', $oauth = []) { self::$instance = $this; switch ($provider) { default: case 'MS': $this->oauth = self::APP_MS; break; case 'CN': $this->oauth = self::APP_MS_CN; break; } $this->oauth = array_merge($this->oauth, $oauth); if (is_writable(sys_get_temp_dir())) { $this->cache = new FilesystemCache(sys_get_temp_dir(), '.qdrive'); } elseif (is_writable('/tmp/')) { $this->cache = new FilesystemCache('/tmp/', '.qdrive'); } else { $this->cache = new VoidCache(); } if ($refresh_token && isset($refresh_token[1])) { $this->cache_prefix = dechex(crc32($refresh_token)) . '_'; if (!($this->access_token = $this->cache->fetch($this->cache_prefix . 'access_token'))) { $response = curl($this->oauth['oauth_url'] . 'token', 'POST', 'client_id=' . $this->oauth['client_id'] . '&client_secret=' . urlencode($this->oauth['client_secret']) . '&grant_type=refresh_token&requested_token_use=on_behalf_of&refresh_token=' . $refresh_token); $json = json_decode($response, true); if (empty($json['access_token'])) { error_log('failed to get access_token. response:' . $response); if (!empty($json['error_description'])) { throw new Exception('failed to get access_token: <br>' . $json['error_description']); } throw new Exception(APP_DEBUG ? $response : 'failed to get access_token.'); } $this->access_token = $json['access_token']; $this->cache->save($this->cache_prefix . 'access_token', $json['access_token'], $json['expires_in'] - 60); } } } public static function instance() { return self::$instance; } public function authUrl($return_url) { return $this->oauth['oauth_url'] . 'authorize?scope=' . $this->oauth['scope'] . '&response_type=code&client_id=' . $this->oauth['client_id'] . '&redirect_uri=' . $this->oauth['redirect_uri'] . '&state=' . urlencode($return_url); } function get_refresh_token($authorization_code) { $response = curl($this->oauth['oauth_url'] . 'token', 'POST', 'client_id=' . $this->oauth['client_id'] . '&client_secret=' . urlencode($this->oauth['client_secret']) . '&grant_type=authorization_code&requested_token_use=on_behalf_of&redirect_uri=' . $this->oauth['redirect_uri'] . '&code=' . $authorization_code); $ret = json_decode($response, true); if (!$ret || !isset($ret['refresh_token'])) { return is_array($ret) ? $ret : ['error' => $response]; } return $ret['refresh_token']; } private function urlPrefix($path) { $url = $this->oauth['api_url']; if ($path && $path !== '/') { $url .= ':' . $path; while (substr($url, -1) == '/') { $url = substr($url, 0, -1); } } return $url; } public function infos($path = '/', $page = 1) { $files = $this->getFullInfo($path); if (isset($files['folder'])) { $files['folder']['currentPage'] = $page; $files['folder']['perPage'] = self::PAGE_SIZE; $files['folder']['lastPage'] = ceil($files['folder']['childCount'] / $files['folder']['perPage']); if ($files['folder']['childCount'] > $files['folder']['perPage'] && $page > 1) { $files['children'] = $this->getFullChildren($path, $page); } } elseif (isset($files['file'])) { } else { error_log('failed to get files. response: ' . json_encode($files)); if (!empty($files['error']) && !empty($files['error']['message'])) { throw new Exception($files['error']['message']); } throw new Exception(APP_DEBUG ? json_encode($files) : 'failed to get files.'); } return $files; } public function info($path, $thumbnails = false) { $cache_key = $this->cache_prefix . 'path_' . $path; if (!($files = $this->cache->fetch($cache_key))) { $url = $this->urlPrefix($path); if ($thumbnails) { $url .= ':/thumbnails/0/medium'; } $files = json_decode(curl($url, 0, null, ['Authorization' => 'Bearer ' . $this->access_token]), true); if (is_array($files)) { $this->cache->save($cache_key, $files, 60); } else { if ($files == null) { $files = ['error' => ['message' => 'timeout']]; } } } return $files; } public function get($path) { $info = $this->info($path); if (isset($info['error'])) { if ($info['error']['code'] === 'itemNotFound') { return false; } throw new Exception($info['error']['message']); } if (empty($info['@microsoft.graph.downloadUrl'])) { throw new Exception('get_content failed'); } return file_get_contents($info['@microsoft.graph.downloadUrl']); } public function put($path, $contents) { $url = $this->urlPrefix($path) . ':/content'; $response = curl($url, 'PUT', $contents, ['Content-Type' => 'text/plain', 'Content-Length' => strlen($contents), 'Authorization' => 'Bearer ' . $this->access_token]); return json_decode($response, true) ?? $response; } public function makeDirectory($path) { $parent = substr($path, 0, strrpos($path, '/') + 1); $url = $this->urlPrefix($parent) . ($parent !== '/' ? ':' : '') . '/children'; $response = curl($url, 'POST', json_encode(['name' => urldecode(substr($path, strrpos($path, '/') + 1)), 'folder' => new \stdClass(), '@microsoft.graph.conflictBehavior' => 'rename']), ['Content-Type' => 'application/json', 'Authorization' => 'Bearer ' . $this->access_token]); return json_decode($response, true) ?? $response; } public function move($path, $target) { $url = $this->urlPrefix($path); $data = []; $parent = substr($path, 0, strrpos($path, '/') + 1); $parent_new = substr($target, 0, strrpos($target, '/') + 1); if ($parent !== $parent_new) { $data['parentReference'] = ['path' => '/drive/root:' . urldecode($parent_new)]; } else { $data['name'] = urldecode(substr($target, strrpos($target, '/') + 1)); } $response = curl($url, 'PATCH', json_encode($data), ['Content-Type' => 'application/json', 'Authorization' => 'Bearer ' . $this->access_token]); return json_decode($response, true) ?? $response; } public function delete($path) { $url = $this->urlPrefix($path); $response = curl($url, 'DELETE', null, ['Content-Type' => 'application/json', 'Authorization' => 'Bearer ' . $this->access_token], $status); return json_decode($response, true) ?? ['status' => $status]; } public function deleteDirectory($directory) { $url = $this->urlPrefix($directory); $response = curl($url, 'DELETE', null, ['Content-Type' => 'application/json', 'Authorization' => 'Bearer ' . $this->access_token]); return json_decode($response, true) ?? $response; } public function uploadUrl($path) { $url = $this->urlPrefix($path) . ':/createUploadSession'; $response = curl($url, 'POST', json_encode(['item' => ['@microsoft.graph.conflictBehavior' => 'fail']]), ['Content-Type' => 'application/json', 'Authorization' => 'Bearer ' . $this->access_token]); return json_decode($response, true) ?? $response; } private function getFullInfo($path) { $cache_key = $this->cache_prefix . 'path_' . $path; if (!($files = $this->cache->fetch($cache_key))) { $url = $this->urlPrefix($path); $url .= '?expand=children(select=name,size,file,folder,parentReference,lastModifiedDateTime)'; $files = json_decode(curl($url, 0, null, ['Authorization' => 'Bearer ' . $this->access_token]), true); if (is_array($files)) { $this->cache->save($cache_key, $files, 60); } else { if ($files == null) { $files = ['error' => ['message' => 'timeout']]; } } } return $files; } private function getFullChildren($path, $page = 1) { $url = $this->urlPrefix($path); $url .= ($path !== '/' ? ':' : '') . '/children?$select=name,size,file,folder,parentReference,lastModifiedDateTime'; $children = []; for ($current_page = 1; $current_page <= $page; $current_page++) { $cache_key = $this->cache_prefix . 'path_' . $path . '_page_' . $current_page; if (!($children = $this->cache->fetch($cache_key))) { $response = curl($url, 0, null, ['Authorization' => 'Bearer ' . $this->access_token]); $children = json_decode($response, true); if (isset($children['error'])) { throw new Exception($children['error']['message']); } $this->cache->save($cache_key, $children, 60); if ($current_page === $page) { break; } if (empty($children['@odata.nextLink'])) { throw new Exception(APP_DEBUG ? $response : 'get children failed'); } $url = $children['@odata.nextLink']; } } return $children['value']; } } } namespace Doctrine\Common\Cache { interface Cache { const STATS_HITS = 'hits'; const STATS_MISSES = 'misses'; const STATS_UPTIME = 'uptime'; const STATS_MEMORY_USAGE = 'memory_usage'; const STATS_MEMORY_AVAILABLE = 'memory_available'; const STATS_MEMORY_AVAILIABLE = 'memory_available'; public function fetch($id); public function contains($id); public function save($id, $data, $lifeTime = 0); public function delete($id); public function getStats(); } } namespace Doctrine\Common\Cache { interface FlushableCache { public function flushAll(); } } namespace Doctrine\Common\Cache { interface ClearableCache { public function deleteAll(); } } namespace Doctrine\Common\Cache { interface MultiGetCache { function fetchMultiple(array $keys); } } namespace Doctrine\Common\Cache { interface MultiPutCache { function saveMultiple(array $keysAndValues, $lifetime = 0); } } namespace Doctrine\Common\Cache { abstract class CacheProvider implements Cache, FlushableCache, ClearableCache, MultiGetCache, MultiPutCache { const DOCTRINE_NAMESPACE_CACHEKEY = 'DoctrineNamespaceCacheKey[%s]'; private $namespace = ''; private $namespaceVersion; public function setNamespace($namespace) { $this->namespace = (string) $namespace; $this->namespaceVersion = null; } public function getNamespace() { return $this->namespace; } public function fetch($id) { return $this->doFetch($this->getNamespacedId($id)); } public function fetchMultiple(array $keys) { if (empty($keys)) { return array(); } $namespacedKeys = array_combine($keys, array_map(array($this, 'getNamespacedId'), $keys)); $items = $this->doFetchMultiple($namespacedKeys); $foundItems = array(); foreach ($namespacedKeys as $requestedKey => $namespacedKey) { if (isset($items[$namespacedKey]) || array_key_exists($namespacedKey, $items)) { $foundItems[$requestedKey] = $items[$namespacedKey]; } } return $foundItems; } public function saveMultiple(array $keysAndValues, $lifetime = 0) { $namespacedKeysAndValues = array(); foreach ($keysAndValues as $key => $value) { $namespacedKeysAndValues[$this->getNamespacedId($key)] = $value; } return $this->doSaveMultiple($namespacedKeysAndValues, $lifetime); } public function contains($id) { return $this->doContains($this->getNamespacedId($id)); } public function save($id, $data, $lifeTime = 0) { return $this->doSave($this->getNamespacedId($id), $data, $lifeTime); } public function delete($id) { return $this->doDelete($this->getNamespacedId($id)); } public function getStats() { return $this->doGetStats(); } public function flushAll() { return $this->doFlush(); } public function deleteAll() { $namespaceCacheKey = $this->getNamespaceCacheKey(); $namespaceVersion = $this->getNamespaceVersion() + 1; if ($this->doSave($namespaceCacheKey, $namespaceVersion)) { $this->namespaceVersion = $namespaceVersion; return true; } return false; } private function getNamespacedId($id) { $namespaceVersion = $this->getNamespaceVersion(); return sprintf('%s[%s][%s]', $this->namespace, $id, $namespaceVersion); } private function getNamespaceCacheKey() { return sprintf(self::DOCTRINE_NAMESPACE_CACHEKEY, $this->namespace); } private function getNamespaceVersion() { if (null !== $this->namespaceVersion) { return $this->namespaceVersion; } $namespaceCacheKey = $this->getNamespaceCacheKey(); $this->namespaceVersion = $this->doFetch($namespaceCacheKey) ?: 1; return $this->namespaceVersion; } protected function doFetchMultiple(array $keys) { $returnValues = array(); foreach ($keys as $key) { if (false !== ($item = $this->doFetch($key)) || $this->doContains($key)) { $returnValues[$key] = $item; } } return $returnValues; } protected abstract function doFetch($id); protected abstract function doContains($id); protected function doSaveMultiple(array $keysAndValues, $lifetime = 0) { $success = true; foreach ($keysAndValues as $key => $value) { if (!$this->doSave($key, $value, $lifetime)) { $success = false; } } return $success; } protected abstract function doSave($id, $data, $lifeTime = 0); protected abstract function doDelete($id); protected abstract function doFlush(); protected abstract function doGetStats(); } } namespace Doctrine\Common\Cache { abstract class FileCache extends CacheProvider { protected $directory; private $extension; private $umask; private $directoryStringLength; private $extensionStringLength; private $isRunningOnWindows; public function __construct($directory, $extension = '', $umask = 02) { if (!is_int($umask)) { throw new \InvalidArgumentException(sprintf('The umask parameter is required to be integer, was: %s', gettype($umask))); } $this->umask = $umask; if (!$this->createPathIfNeeded($directory)) { throw new \InvalidArgumentException(sprintf('The directory "%s" does not exist and could not be created.', $directory)); } if (!is_writable($directory)) { throw new \InvalidArgumentException(sprintf('The directory "%s" is not writable.', $directory)); } $this->directory = realpath($directory); $this->extension = (string) $extension; $this->directoryStringLength = strlen($this->directory); $this->extensionStringLength = strlen($this->extension); $this->isRunningOnWindows = defined('PHP_WINDOWS_VERSION_BUILD'); } public function getDirectory() { return $this->directory; } public function getExtension() { return $this->extension; } protected function getFilename($id) { $hash = hash('sha256', $id); if ('' === $id || strlen($id) * 2 + $this->extensionStringLength > 255 || $this->isRunningOnWindows && $this->directoryStringLength + 4 + strlen($id) * 2 + $this->extensionStringLength > 258) { $filename = '_' . $hash; } else { $filename = bin2hex($id); } return $this->directory . DIRECTORY_SEPARATOR . substr($hash, 0, 2) . DIRECTORY_SEPARATOR . $filename . $this->extension; } protected function doDelete($id) { $filename = $this->getFilename($id); return @unlink($filename) || !file_exists($filename); } protected function doFlush() { foreach ($this->getIterator() as $name => $file) { if ($file->isDir()) { @rmdir($name); } elseif ($this->isFilenameEndingWithExtension($name)) { @unlink($name); } } return true; } protected function doGetStats() { $usage = 0; foreach ($this->getIterator() as $name => $file) { if (!$file->isDir() && $this->isFilenameEndingWithExtension($name)) { $usage += $file->getSize(); } } $free = disk_free_space($this->directory); return array(Cache::STATS_HITS => null, Cache::STATS_MISSES => null, Cache::STATS_UPTIME => null, Cache::STATS_MEMORY_USAGE => $usage, Cache::STATS_MEMORY_AVAILABLE => $free); } private function createPathIfNeeded($path) { if (!is_dir($path)) { if (false === @mkdir($path, 0777 & ~$this->umask, true) && !is_dir($path)) { return false; } } return true; } protected function writeFile($filename, $content) { $filepath = pathinfo($filename, PATHINFO_DIRNAME); if (!$this->createPathIfNeeded($filepath)) { return false; } if (!is_writable($filepath)) { return false; } $tmpFile = tempnam($filepath, 'swap'); @chmod($tmpFile, 0666 & ~$this->umask); if (file_put_contents($tmpFile, $content) !== false) { if (@rename($tmpFile, $filename)) { return true; } @unlink($tmpFile); } return false; } private function getIterator() { return new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->directory, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST); } private function isFilenameEndingWithExtension($name) { return '' === $this->extension || strrpos($name, $this->extension) === strlen($name) - $this->extensionStringLength; } } } namespace Doctrine\Common\Cache { class FilesystemCache extends FileCache { const EXTENSION = '.doctrinecache.data'; public function __construct($directory, $extension = self::EXTENSION, $umask = 02) { parent::__construct($directory, $extension, $umask); } protected function doFetch($id) { $data = ''; $lifetime = -1; $filename = $this->getFilename($id); if (!is_file($filename)) { return false; } $resource = fopen($filename, "r"); if (false !== ($line = fgets($resource))) { $lifetime = (int) $line; } if ($lifetime !== 0 && $lifetime < time()) { fclose($resource); return false; } while (false !== ($line = fgets($resource))) { $data .= $line; } fclose($resource); return unserialize($data); } protected function doContains($id) { $lifetime = -1; $filename = $this->getFilename($id); if (!is_file($filename)) { return false; } $resource = fopen($filename, "r"); if (false !== ($line = fgets($resource))) { $lifetime = (int) $line; } fclose($resource); return $lifetime === 0 || $lifetime > time(); } protected function doSave($id, $data, $lifeTime = 0) { if ($lifeTime > 0) { $lifeTime = time() + $lifeTime; } $data = serialize($data); $filename = $this->getFilename($id); return $this->writeFile($filename, $lifeTime . PHP_EOL . $data); } } } namespace Platforms\Normal { use Symfony\Component\HttpFoundation\Request; use Symfony\Component\HttpFoundation\Response; class Normal { private static $request; public static function request() { if (!self::$request) { self::$request = Request::createFromGlobals(); if (self::$request->get('s')) { $new_uri = self::$request->getBaseUrl() . self::$request->get('s'); if (!empty($_SERVER['UNENCODED_URL'])) { $_SERVER['UNENCODED_URL'] = $new_uri; } else { $_SERVER['REQUEST_URI'] = $new_uri; } self::$request = Request::createFromGlobals(); } } return self::$request; } public static function response($response) { return $response->send(); } } } namespace Platforms { interface PlatformInterface { public static function response($response); } } namespace Platforms\QCloudSCF { use Platforms\PlatformInterface; use Symfony\Component\HttpFoundation\Request; use Symfony\Component\HttpFoundation\Response; class QCloudSCF implements PlatformInterface { public static function request($event, $context) { $event = json_decode(json_encode($event), true); foreach ($event['headers'] as $header => $value) { $_SERVER['HTTP_' . str_replace('-', '_', strtoupper($header))] = $value; } $_SERVER['SERVER_NAME'] = $_SERVER['HTTP_HOST']; $_SERVER['SERVER_ADDR'] = '127.0.0.1'; $_SERVER['SERVER_PORT'] = strpos($_SERVER['SERVER_NAME'], ':') === FALSE ? 80 : (int) explode(':', $_SERVER['SERVER_NAME'])[1]; $_SERVER['REMOTE_ADDR'] = $event['requestContext']['sourceIp']; $_SERVER['DOCUMENT_ROOT'] = '/tmp/'; $_SERVER['REQUEST_SCHEME'] = isset($_SERVER['HTTP_ORIGIN']) && strpos($_SERVER['HTTP_ORIGIN'], 'https://') !== FALSE ? 'https' : 'http'; $_SERVER['SERVER_ADMIN'] = 'https://github.com/Tai7sy/OneDriveFly'; $_SERVER['DOCUMENT_ROOT'] = dirname('D:\\MyProjects\\WebProjects\\OneDriveFly\\php\\platforms\\QCloudSCF\\QCloudSCF.php'); $_SERVER['SCRIPT_FILENAME'] = 'D:\\MyProjects\\WebProjects\\OneDriveFly\\php\\platforms\\QCloudSCF\\QCloudSCF.php'; $_SERVER['REDIRECT_URL'] = $event['requestContext']['path'] === '/' ? $event['path'] : substr($event['path'], strlen($event['requestContext']['path'])); $_SERVER['SERVER_PROTOCOL'] = 'HTTP/1.1'; $_SERVER['REQUEST_METHOD'] = $event['httpMethod']; $_SERVER['QUERY_STRING'] = http_build_query($event['queryString']); $_SERVER['REQUEST_URI'] = $_SERVER['REDIRECT_URL'] . ($_SERVER['QUERY_STRING'] ? '?' : '') . $_SERVER['QUERY_STRING']; $_POST = []; if ($event['headers']['content-type'] === 'application/x-www-form-urlencoded') { $posts = explode('&', $event['body']); foreach ($posts as $post) { $pos = strpos($post, '='); $_POST[urldecode(substr($post, 0, $pos))] = urldecode(substr($post, $pos + 1)); } } elseif (substr($event['headers']['content-type'], 0, 19) === 'multipart/form-data') { throw new \Exception('unsupported [multipart/form-data]'); } $_COOKIE = []; $cookies = explode('; ', $event['headers']['cookie']); foreach ($cookies as $cookie) { $pos = strpos($cookie, '='); $_COOKIE[urldecode(substr($cookie, 0, $pos))] = urldecode(substr($cookie, $pos + 1)); } return new Request(isset($event['queryString']) ? $event['queryString'] : [], $_POST, [], $_COOKIE, [], $_SERVER); } public static function response($response) { return ['isBase64Encoded' => false, 'statusCode' => $response->getStatusCode(), 'headers' => array_map(function ($values) { return $values[0]; }, $response->headers->all()), 'body' => $response->getContent()]; } } } namespace Platforms\AliyunSC { use Platforms\PlatformInterface; use Symfony\Component\HttpFoundation\Request; use Symfony\Component\HttpFoundation\Response; class AliyunSC implements PlatformInterface { public static function request($request, $context) { $headers = $request->getHeaders(); $headers = array_column(array_map(function ($k, $v) { return [strtolower($k), is_array($v) ? $v[0] : $v]; }, array_keys($headers), $headers), 1, 0); foreach ($headers as $header => $value) { $_SERVER['HTTP_' . str_replace('-', '_', strtoupper($header))] = $value; } $_SERVER['SERVER_NAME'] = $_SERVER['HTTP_HOST']; $_SERVER['SERVER_ADDR'] = '127.0.0.1'; $_SERVER['SERVER_PORT'] = strpos($_SERVER['SERVER_NAME'], ':') === FALSE ? 80 : (int) explode(':', $_SERVER['SERVER_NAME'])[1]; $_SERVER['REMOTE_ADDR'] = $request->getAttribute('clientIP'); $_SERVER['DOCUMENT_ROOT'] = '/tmp/'; $_SERVER['REQUEST_SCHEME'] = isset($_SERVER['HTTP_ORIGIN']) && strpos($_SERVER['HTTP_ORIGIN'], 'https://') !== FALSE ? 'https' : 'http'; $_SERVER['SERVER_ADMIN'] = 'https://github.com/Tai7sy/OneDriveFly'; $_SERVER['DOCUMENT_ROOT'] = dirname('D:\\MyProjects\\WebProjects\\OneDriveFly\\php\\platforms\\AliyunSC\\AliyunSC.php'); $_SERVER['SCRIPT_FILENAME'] = 'D:\\MyProjects\\WebProjects\\OneDriveFly\\php\\platforms\\AliyunSC\\AliyunSC.php'; $_SERVER['REDIRECT_URL'] = path_format($request->getAttribute('path'), true); $_SERVER['SERVER_PROTOCOL'] = 'HTTP/1.1'; $_SERVER['REQUEST_METHOD'] = $request->getMethod(); $_SERVER['QUERY_STRING'] = http_build_query($request->getQueryParams()); $_SERVER['REQUEST_URI'] = $_SERVER['REDIRECT_URL'] . ($_SERVER['QUERY_STRING'] ? '?' : '') . $_SERVER['QUERY_STRING']; $_POST = []; if ($headers['content-type'] === 'application/x-www-form-urlencoded') { $posts = explode('&', $request->getBody()->getContents()); foreach ($posts as $post) { $pos = strpos($post, '='); $_POST[urldecode(substr($post, 0, $pos))] = urldecode(substr($post, $pos + 1)); } } elseif (substr($headers['content-type'], 0, 19) === 'multipart/form-data') { throw new \Exception('unsupported [multipart/form-data]'); } $_COOKIE = []; if (isset($headers['cookie'])) { $cookies = explode('; ', $headers['cookie']); foreach ($cookies as $cookie) { $pos = strpos($cookie, '='); $_COOKIE[urldecode(substr($cookie, 0, $pos))] = urldecode(substr($cookie, $pos + 1)); } } return new Request($request->getQueryParams(), $_POST, [], $_COOKIE, [], $_SERVER, $request->getBody()->getContents()); } public static function response($response) { return new \RingCentral\Psr7\Response($response->getStatusCode(), $response->headers->all(), $response->getContent()); } } } namespace Doctrine\Common\Cache { class VoidCache extends CacheProvider { protected function doFetch($id) { return false; } protected function doContains($id) { return false; } protected function doSave($id, $data, $lifeTime = 0) { return true; } protected function doDelete($id) { return true; } protected function doFlush() { return true; } protected function doGetStats() { return; } } } namespace Symfony\Component\HttpFoundation { use Symfony\Component\HttpFoundation\Exception\ConflictingHeadersException; use Symfony\Component\HttpFoundation\Exception\SuspiciousOperationException; use Symfony\Component\HttpFoundation\Session\SessionInterface; class Request { const HEADER_FORWARDED = 0b1; const HEADER_X_FORWARDED_FOR = 0b10; const HEADER_X_FORWARDED_HOST = 0b100; const HEADER_X_FORWARDED_PROTO = 0b1000; const HEADER_X_FORWARDED_PORT = 0b10000; const HEADER_X_FORWARDED_ALL = 0b11110; const HEADER_X_FORWARDED_AWS_ELB = 0b11010; const HEADER_CLIENT_IP = self::HEADER_X_FORWARDED_FOR; const HEADER_CLIENT_HOST = self::HEADER_X_FORWARDED_HOST; const HEADER_CLIENT_PROTO = self::HEADER_X_FORWARDED_PROTO; const HEADER_CLIENT_PORT = self::HEADER_X_FORWARDED_PORT; const METHOD_HEAD = 'HEAD'; const METHOD_GET = 'GET'; const METHOD_POST = 'POST'; const METHOD_PUT = 'PUT'; const METHOD_PATCH = 'PATCH'; const METHOD_DELETE = 'DELETE'; const METHOD_PURGE = 'PURGE'; const METHOD_OPTIONS = 'OPTIONS'; const METHOD_TRACE = 'TRACE'; const METHOD_CONNECT = 'CONNECT'; protected static $trustedProxies = []; protected static $trustedHostPatterns = []; protected static $trustedHosts = []; protected static $trustedHeaders = [self::HEADER_FORWARDED => 'FORWARDED', self::HEADER_CLIENT_IP => 'X_FORWARDED_FOR', self::HEADER_CLIENT_HOST => 'X_FORWARDED_HOST', self::HEADER_CLIENT_PROTO => 'X_FORWARDED_PROTO', self::HEADER_CLIENT_PORT => 'X_FORWARDED_PORT']; protected static $httpMethodParameterOverride = false; public $attributes; public $request; public $query; public $server; public $files; public $cookies; public $headers; protected $content; protected $languages; protected $charsets; protected $encodings; protected $acceptableContentTypes; protected $pathInfo; protected $requestUri; protected $baseUrl; protected $basePath; protected $method; protected $format; protected $session; protected $locale; protected $defaultLocale = 'en'; protected static $formats; protected static $requestFactory; private $isHostValid = true; private $isForwardedValid = true; private static $trustedHeaderSet = -1; private static $trustedHeaderNames = [self::HEADER_FORWARDED => 'FORWARDED', self::HEADER_CLIENT_IP => 'X_FORWARDED_FOR', self::HEADER_CLIENT_HOST => 'X_FORWARDED_HOST', self::HEADER_CLIENT_PROTO => 'X_FORWARDED_PROTO', self::HEADER_CLIENT_PORT => 'X_FORWARDED_PORT']; private static $forwardedParams = [self::HEADER_X_FORWARDED_FOR => 'for', self::HEADER_X_FORWARDED_HOST => 'host', self::HEADER_X_FORWARDED_PROTO => 'proto', self::HEADER_X_FORWARDED_PORT => 'host']; public function __construct(array $query = [], array $request = [], array $attributes = [], array $cookies = [], array $files = [], array $server = [], $content = null) { $this->initialize($query, $request, $attributes, $cookies, $files, $server, $content); } public function initialize(array $query = [], array $request = [], array $attributes = [], array $cookies = [], array $files = [], array $server = [], $content = null) { $this->request = new ParameterBag($request); $this->query = new ParameterBag($query); $this->attributes = new ParameterBag($attributes); $this->cookies = new ParameterBag($cookies); $this->files = new FileBag($files); $this->server = new ServerBag($server); $this->headers = new HeaderBag($this->server->getHeaders()); $this->content = $content; $this->languages = null; $this->charsets = null; $this->encodings = null; $this->acceptableContentTypes = null; $this->pathInfo = null; $this->requestUri = null; $this->baseUrl = null; $this->basePath = null; $this->method = null; $this->format = null; } public static function createFromGlobals() { $server = $_SERVER; if ('cli-server' === \PHP_SAPI) { if (\array_key_exists('HTTP_CONTENT_LENGTH', $_SERVER)) { $server['CONTENT_LENGTH'] = $_SERVER['HTTP_CONTENT_LENGTH']; } if (\array_key_exists('HTTP_CONTENT_TYPE', $_SERVER)) { $server['CONTENT_TYPE'] = $_SERVER['HTTP_CONTENT_TYPE']; } } $request = self::createRequestFromFactory($_GET, $_POST, [], $_COOKIE, $_FILES, $server); if (0 === strpos($request->headers->get('CONTENT_TYPE'), 'application/x-www-form-urlencoded') && \in_array(strtoupper($request->server->get('REQUEST_METHOD', 'GET')), ['PUT', 'DELETE', 'PATCH'])) { parse_str($request->getContent(), $data); $request->request = new ParameterBag($data); } return $request; } public static function create($uri, $method = 'GET', $parameters = [], $cookies = [], $files = [], $server = [], $content = null) { $server = array_replace(['SERVER_NAME' => 'localhost', 'SERVER_PORT' => 80, 'HTTP_HOST' => 'localhost', 'HTTP_USER_AGENT' => 'Symfony/3.X', 'HTTP_ACCEPT' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8', 'HTTP_ACCEPT_LANGUAGE' => 'en-us,en;q=0.5', 'HTTP_ACCEPT_CHARSET' => 'ISO-8859-1,utf-8;q=0.7,*;q=0.7', 'REMOTE_ADDR' => '127.0.0.1', 'SCRIPT_NAME' => '', 'SCRIPT_FILENAME' => '', 'SERVER_PROTOCOL' => 'HTTP/1.1', 'REQUEST_TIME' => time()], $server); $server['PATH_INFO'] = ''; $server['REQUEST_METHOD'] = strtoupper($method); $components = parse_url($uri); if (isset($components['host'])) { $server['SERVER_NAME'] = $components['host']; $server['HTTP_HOST'] = $components['host']; } if (isset($components['scheme'])) { if ('https' === $components['scheme']) { $server['HTTPS'] = 'on'; $server['SERVER_PORT'] = 443; } else { unset($server['HTTPS']); $server['SERVER_PORT'] = 80; } } if (isset($components['port'])) { $server['SERVER_PORT'] = $components['port']; $server['HTTP_HOST'] .= ':' . $components['port']; } if (isset($components['user'])) { $server['PHP_AUTH_USER'] = $components['user']; } if (isset($components['pass'])) { $server['PHP_AUTH_PW'] = $components['pass']; } if (!isset($components['path'])) { $components['path'] = '/'; } switch (strtoupper($method)) { case 'POST': case 'PUT': case 'DELETE': if (!isset($server['CONTENT_TYPE'])) { $server['CONTENT_TYPE'] = 'application/x-www-form-urlencoded'; } case 'PATCH': $request = $parameters; $query = []; break; default: $request = []; $query = $parameters; break; } $queryString = ''; if (isset($components['query'])) { parse_str(html_entity_decode($components['query']), $qs); if ($query) { $query = array_replace($qs, $query); $queryString = http_build_query($query, '', '&'); } else { $query = $qs; $queryString = $components['query']; } } elseif ($query) { $queryString = http_build_query($query, '', '&'); } $server['REQUEST_URI'] = $components['path'] . ('' !== $queryString ? '?' . $queryString : ''); $server['QUERY_STRING'] = $queryString; return self::createRequestFromFactory($query, $request, [], $cookies, $files, $server, $content); } public static function setFactory($callable) { self::$requestFactory = $callable; } public function duplicate(array $query = null, array $request = null, array $attributes = null, array $cookies = null, array $files = null, array $server = null) { $dup = clone $this; if (null !== $query) { $dup->query = new ParameterBag($query); } if (null !== $request) { $dup->request = new ParameterBag($request); } if (null !== $attributes) { $dup->attributes = new ParameterBag($attributes); } if (null !== $cookies) { $dup->cookies = new ParameterBag($cookies); } if (null !== $files) { $dup->files = new FileBag($files); } if (null !== $server) { $dup->server = new ServerBag($server); $dup->headers = new HeaderBag($dup->server->getHeaders()); } $dup->languages = null; $dup->charsets = null; $dup->encodings = null; $dup->acceptableContentTypes = null; $dup->pathInfo = null; $dup->requestUri = null; $dup->baseUrl = null; $dup->basePath = null; $dup->method = null; $dup->format = null; if (!$dup->get('_format') && $this->get('_format')) { $dup->attributes->set('_format', $this->get('_format')); } if (!$dup->getRequestFormat(null)) { $dup->setRequestFormat($this->getRequestFormat(null)); } return $dup; } public function __clone() { $this->query = clone $this->query; $this->request = clone $this->request; $this->attributes = clone $this->attributes; $this->cookies = clone $this->cookies; $this->files = clone $this->files; $this->server = clone $this->server; $this->headers = clone $this->headers; } public function __toString() { try { $content = $this->getContent(); } catch (\LogicException $e) { if (\PHP_VERSION_ID >= 70400) { throw $e; } return trigger_error($e, E_USER_ERROR); } $cookieHeader = ''; $cookies = []; foreach ($this->cookies as $k => $v) { $cookies[] = $k . '=' . $v; } if (!empty($cookies)) { $cookieHeader = 'Cookie: ' . implode('; ', $cookies) . "\r\n"; } return sprintf('%s %s %s', $this->getMethod(), $this->getRequestUri(), $this->server->get('SERVER_PROTOCOL')) . "\r\n" . $this->headers . $cookieHeader . "\r\n" . $content; } public function overrideGlobals() { $this->server->set('QUERY_STRING', static::normalizeQueryString(http_build_query($this->query->all(), '', '&'))); $_GET = $this->query->all(); $_POST = $this->request->all(); $_SERVER = $this->server->all(); $_COOKIE = $this->cookies->all(); foreach ($this->headers->all() as $key => $value) { $key = strtoupper(str_replace('-', '_', $key)); if (\in_array($key, ['CONTENT_TYPE', 'CONTENT_LENGTH'])) { $_SERVER[$key] = implode(', ', $value); } else { $_SERVER['HTTP_' . $key] = implode(', ', $value); } } $request = ['g' => $_GET, 'p' => $_POST, 'c' => $_COOKIE]; $requestOrder = ini_get('request_order') ?: ini_get('variables_order'); $requestOrder = preg_replace('#[^cgp]#', '', strtolower($requestOrder)) ?: 'gp'; $_REQUEST = []; foreach (str_split($requestOrder) as $order) { $_REQUEST = array_merge($_REQUEST, $request[$order]); } } public static function setTrustedProxies(array $proxies) { self::$trustedProxies = $proxies; if (2 > \func_num_args()) { @trigger_error(sprintf('The %s() method expects a bit field of Request::HEADER_* as second argument since Symfony 3.3. Defining it will be required in 4.0. ', __METHOD__), E_USER_DEPRECATED); return; } $trustedHeaderSet = (int) func_get_arg(1); foreach (self::$trustedHeaderNames as $header => $name) { self::$trustedHeaders[$header] = $header & $trustedHeaderSet ? $name : null; } self::$trustedHeaderSet = $trustedHeaderSet; } public static function getTrustedProxies() { return self::$trustedProxies; } public static function getTrustedHeaderSet() { return self::$trustedHeaderSet; } public static function setTrustedHosts(array $hostPatterns) { self::$trustedHostPatterns = array_map(function ($hostPattern) { return sprintf('{%s}i', $hostPattern); }, $hostPatterns); self::$trustedHosts = []; } public static function getTrustedHosts() { return self::$trustedHostPatterns; } public static function setTrustedHeaderName($key, $value) { @trigger_error(sprintf('The "%s()" method is deprecated since Symfony 3.3 and will be removed in 4.0. Use the $trustedHeaderSet argument of the Request::setTrustedProxies() method instead.', __METHOD__), E_USER_DEPRECATED); if ('forwarded' === $key) { $key = self::HEADER_FORWARDED; } elseif ('client_ip' === $key) { $key = self::HEADER_CLIENT_IP; } elseif ('client_host' === $key) { $key = self::HEADER_CLIENT_HOST; } elseif ('client_proto' === $key) { $key = self::HEADER_CLIENT_PROTO; } elseif ('client_port' === $key) { $key = self::HEADER_CLIENT_PORT; } elseif (!\array_key_exists($key, self::$trustedHeaders)) { throw new \InvalidArgumentException(sprintf('Unable to set the trusted header name for key "%s".', $key)); } self::$trustedHeaders[$key] = $value; if (null !== $value) { self::$trustedHeaderNames[$key] = $value; self::$trustedHeaderSet |= $key; } else { self::$trustedHeaderSet &= ~$key; } } public static function getTrustedHeaderName($key) { if (2 > \func_num_args() || func_get_arg(1)) { @trigger_error(sprintf('The "%s()" method is deprecated since Symfony 3.3 and will be removed in 4.0. Use the Request::getTrustedHeaderSet() method instead.', __METHOD__), E_USER_DEPRECATED); } if (!\array_key_exists($key, self::$trustedHeaders)) { throw new \InvalidArgumentException(sprintf('Unable to get the trusted header name for key "%s".', $key)); } return self::$trustedHeaders[$key]; } public static function normalizeQueryString($qs) { if ('' == $qs) { return ''; } $parts = []; $order = []; foreach (explode('&', $qs) as $param) { if ('' === $param || '=' === $param[0]) { continue; } $keyValuePair = explode('=', $param, 2); $parts[] = isset($keyValuePair[1]) ? rawurlencode(urldecode($keyValuePair[0])) . '=' . rawurlencode(urldecode($keyValuePair[1])) : rawurlencode(urldecode($keyValuePair[0])); $order[] = urldecode($keyValuePair[0]); } array_multisort($order, SORT_ASC, $parts); return implode('&', $parts); } public static function enableHttpMethodParameterOverride() { self::$httpMethodParameterOverride = true; } public static function getHttpMethodParameterOverride() { return self::$httpMethodParameterOverride; } public function get($key, $default = null) { if ($this !== ($result = $this->attributes->get($key, $this))) { return $result; } if ($this !== ($result = $this->query->get($key, $this))) { return $result; } if ($this !== ($result = $this->request->get($key, $this))) { return $result; } return $default; } public function getSession() { return $this->session; } public function hasPreviousSession() { return $this->hasSession() && $this->cookies->has($this->session->getName()); } public function hasSession() { return null !== $this->session; } public function setSession(SessionInterface $session) { $this->session = $session; } public function getClientIps() { $ip = $this->server->get('REMOTE_ADDR'); if (!$this->isFromTrustedProxy()) { return [$ip]; } return $this->getTrustedValues(self::HEADER_CLIENT_IP, $ip) ?: [$ip]; } public function getClientIp() { $ipAddresses = $this->getClientIps(); return $ipAddresses[0]; } public function getScriptName() { return $this->server->get('SCRIPT_NAME', $this->server->get('ORIG_SCRIPT_NAME', '')); } public function getPathInfo() { if (null === $this->pathInfo) { $this->pathInfo = $this->preparePathInfo(); } return $this->pathInfo; } public function getBasePath() { if (null === $this->basePath) { $this->basePath = $this->prepareBasePath(); } return $this->basePath; } public function getBaseUrl() { if (null === $this->baseUrl) { $this->baseUrl = $this->prepareBaseUrl(); } return $this->baseUrl; } public function getScheme() { return $this->isSecure() ? 'https' : 'http'; } public function getPort() { if ($this->isFromTrustedProxy() && ($host = $this->getTrustedValues(self::HEADER_CLIENT_PORT))) { $host = $host[0]; } elseif ($this->isFromTrustedProxy() && ($host = $this->getTrustedValues(self::HEADER_CLIENT_HOST))) { $host = $host[0]; } elseif (!($host = $this->headers->get('HOST'))) { return $this->server->get('SERVER_PORT'); } if ('[' === $host[0]) { $pos = strpos($host, ':', strrpos($host, ']')); } else { $pos = strrpos($host, ':'); } if (false !== $pos && ($port = substr($host, $pos + 1))) { return (int) $port; } return 'https' === $this->getScheme() ? 443 : 80; } public function getUser() { return $this->headers->get('PHP_AUTH_USER'); } public function getPassword() { return $this->headers->get('PHP_AUTH_PW'); } public function getUserInfo() { $userinfo = $this->getUser(); $pass = $this->getPassword(); if ('' != $pass) { $userinfo .= ":{$pass}"; } return $userinfo; } public function getHttpHost() { $scheme = $this->getScheme(); $port = $this->getPort(); if ('http' == $scheme && 80 == $port || 'https' == $scheme && 443 == $port) { return $this->getHost(); } return $this->getHost() . ':' . $port; } public function getRequestUri() { if (null === $this->requestUri) { $this->requestUri = $this->prepareRequestUri(); } return $this->requestUri; } public function getSchemeAndHttpHost() { return $this->getScheme() . '://' . $this->getHttpHost(); } public function getUri() { if (null !== ($qs = $this->getQueryString())) { $qs = '?' . $qs; } return $this->getSchemeAndHttpHost() . $this->getBaseUrl() . $this->getPathInfo() . $qs; } public function getUriForPath($path) { return $this->getSchemeAndHttpHost() . $this->getBaseUrl() . $path; } public function getRelativeUriForPath($path) { if (!isset($path[0]) || '/' !== $path[0]) { return $path; } if ($path === ($basePath = $this->getPathInfo())) { return ''; } $sourceDirs = explode('/', isset($basePath[0]) && '/' === $basePath[0] ? substr($basePath, 1) : $basePath); $targetDirs = explode('/', substr($path, 1)); array_pop($sourceDirs); $targetFile = array_pop($targetDirs); foreach ($sourceDirs as $i => $dir) { if (isset($targetDirs[$i]) && $dir === $targetDirs[$i]) { unset($sourceDirs[$i], $targetDirs[$i]); } else { break; } } $targetDirs[] = $targetFile; $path = str_repeat('../', \count($sourceDirs)) . implode('/', $targetDirs); return !isset($path[0]) || '/' === $path[0] || false !== ($colonPos = strpos($path, ':')) && ($colonPos < ($slashPos = strpos($path, '/')) || false === $slashPos) ? "./{$path}" : $path; } public function getQueryString() { $qs = static::normalizeQueryString($this->server->get('QUERY_STRING')); return '' === $qs ? null : $qs; } public function isSecure() { if ($this->isFromTrustedProxy() && ($proto = $this->getTrustedValues(self::HEADER_CLIENT_PROTO))) { return \in_array(strtolower($proto[0]), ['https', 'on', 'ssl', '1'], true); } $https = $this->server->get('HTTPS'); return !empty($https) && 'off' !== strtolower($https); } public function getHost() { if ($this->isFromTrustedProxy() && ($host = $this->getTrustedValues(self::HEADER_CLIENT_HOST))) { $host = $host[0]; } elseif (!($host = $this->headers->get('HOST'))) { if (!($host = $this->server->get('SERVER_NAME'))) { $host = $this->server->get('SERVER_ADDR', ''); } } $host = strtolower(preg_replace('/:\\d+$/', '', trim($host))); if ($host && '' !== preg_replace('/(?:^\\[)?[a-zA-Z0-9-:\\]_]+\\.?/', '', $host)) { if (!$this->isHostValid) { return ''; } $this->isHostValid = false; throw new SuspiciousOperationException(sprintf('Invalid Host "%s".', $host)); } if (\count(self::$trustedHostPatterns) > 0) { if (\in_array($host, self::$trustedHosts)) { return $host; } foreach (self::$trustedHostPatterns as $pattern) { if (preg_match($pattern, $host)) { self::$trustedHosts[] = $host; return $host; } } if (!$this->isHostValid) { return ''; } $this->isHostValid = false; throw new SuspiciousOperationException(sprintf('Untrusted Host "%s".', $host)); } return $host; } public function setMethod($method) { $this->method = null; $this->server->set('REQUEST_METHOD', $method); } public function getMethod() { if (null !== $this->method) { return $this->method; } $this->method = strtoupper($this->server->get('REQUEST_METHOD', 'GET')); if ('POST' !== $this->method) { return $this->method; } $method = $this->headers->get('X-HTTP-METHOD-OVERRIDE'); if (!$method && self::$httpMethodParameterOverride) { $method = $this->request->get('_method', $this->query->get('_method', 'POST')); } if (!\is_string($method)) { return $this->method; } $method = strtoupper($method); if (\in_array($method, ['GET', 'HEAD', 'POST', 'PUT', 'DELETE', 'CONNECT', 'OPTIONS', 'PATCH', 'PURGE', 'TRACE'], true)) { return $this->method = $method; } if (!preg_match('/^[A-Z]++$/D', $method)) { throw new SuspiciousOperationException(sprintf('Invalid method override "%s".', $method)); } return $this->method = $method; } public function getRealMethod() { return strtoupper($this->server->get('REQUEST_METHOD', 'GET')); } public function getMimeType($format) { if (null === static::$formats) { static::initializeFormats(); } return isset(static::$formats[$format]) ? static::$formats[$format][0] : null; } public static function getMimeTypes($format) { if (null === static::$formats) { static::initializeFormats(); } return isset(static::$formats[$format]) ? static::$formats[$format] : []; } public function getFormat($mimeType) { $canonicalMimeType = null; if (false !== ($pos = strpos($mimeType, ';'))) { $canonicalMimeType = trim(substr($mimeType, 0, $pos)); } if (null === static::$formats) { static::initializeFormats(); } foreach (static::$formats as $format => $mimeTypes) { if (\in_array($mimeType, (array) $mimeTypes)) { return $format; } if (null !== $canonicalMimeType && \in_array($canonicalMimeType, (array) $mimeTypes)) { return $format; } } return null; } public function setFormat($format, $mimeTypes) { if (null === static::$formats) { static::initializeFormats(); } static::$formats[$format] = \is_array($mimeTypes) ? $mimeTypes : [$mimeTypes]; } public function getRequestFormat($default = 'html') { if (null === $this->format) { $this->format = $this->attributes->get('_format'); } return null === $this->format ? $default : $this->format; } public function setRequestFormat($format) { $this->format = $format; } public function getContentType() { return $this->getFormat($this->headers->get('CONTENT_TYPE')); } public function setDefaultLocale($locale) { $this->defaultLocale = $locale; if (null === $this->locale) { $this->setPhpDefaultLocale($locale); } } public function getDefaultLocale() { return $this->defaultLocale; } public function setLocale($locale) { $this->setPhpDefaultLocale($this->locale = $locale); } public function getLocale() { return null === $this->locale ? $this->defaultLocale : $this->locale; } public function isMethod($method) { return $this->getMethod() === strtoupper($method); } public function isMethodSafe() { if (!\func_num_args() || func_get_arg(0)) { @trigger_error('Checking only for cacheable HTTP methods with Symfony\\Component\\HttpFoundation\\Request::isMethodSafe() is deprecated since Symfony 3.2 and will throw an exception in 4.0. Disable checking only for cacheable methods by calling the method with `false` as first argument or use the Request::isMethodCacheable() instead.', E_USER_DEPRECATED); return \in_array($this->getMethod(), ['GET', 'HEAD']); } return \in_array($this->getMethod(), ['GET', 'HEAD', 'OPTIONS', 'TRACE']); } public function isMethodIdempotent() { return \in_array($this->getMethod(), ['HEAD', 'GET', 'PUT', 'DELETE', 'TRACE', 'OPTIONS', 'PURGE']); } public function isMethodCacheable() { return \in_array($this->getMethod(), ['GET', 'HEAD']); } public function getProtocolVersion() { if ($this->isFromTrustedProxy()) { preg_match('~^(HTTP/)?([1-9]\\.[0-9]) ~', $this->headers->get('Via'), $matches); if ($matches) { return 'HTTP/' . $matches[2]; } } return $this->server->get('SERVER_PROTOCOL'); } public function getContent($asResource = false) { $currentContentIsResource = \is_resource($this->content); if (\PHP_VERSION_ID < 50600 && false === $this->content) { throw new \LogicException('getContent() can only be called once when using the resource return type and PHP below 5.6.'); } if (true === $asResource) { if ($currentContentIsResource) { rewind($this->content); return $this->content; } if (\is_string($this->content)) { $resource = fopen('php://temp', 'r+'); fwrite($resource, $this->content); rewind($resource); return $resource; } $this->content = false; return fopen('php://input', 'rb'); } if ($currentContentIsResource) { rewind($this->content); return stream_get_contents($this->content); } if (null === $this->content || false === $this->content) { $this->content = file_get_contents('php://input'); } return $this->content; } public function getETags() { return preg_split('/\\s*,\\s*/', $this->headers->get('if_none_match'), null, PREG_SPLIT_NO_EMPTY); } public function isNoCache() { return $this->headers->hasCacheControlDirective('no-cache') || 'no-cache' == $this->headers->get('Pragma'); } public function getPreferredLanguage(array $locales = null) { $preferredLanguages = $this->getLanguages(); if (empty($locales)) { return isset($preferredLanguages[0]) ? $preferredLanguages[0] : null; } if (!$preferredLanguages) { return $locales[0]; } $extendedPreferredLanguages = []; foreach ($preferredLanguages as $language) { $extendedPreferredLanguages[] = $language; if (false !== ($position = strpos($language, '_'))) { $superLanguage = substr($language, 0, $position); if (!\in_array($superLanguage, $preferredLanguages)) { $extendedPreferredLanguages[] = $superLanguage; } } } $preferredLanguages = array_values(array_intersect($extendedPreferredLanguages, $locales)); return isset($preferredLanguages[0]) ? $preferredLanguages[0] : $locales[0]; } public function getLanguages() { if (null !== $this->languages) { return $this->languages; } $languages = AcceptHeader::fromString($this->headers->get('Accept-Language'))->all(); $this->languages = []; foreach ($languages as $lang => $acceptHeaderItem) { if (false !== strpos($lang, '-')) { $codes = explode('-', $lang); if ('i' === $codes[0]) { if (\count($codes) > 1) { $lang = $codes[1]; } } else { for ($i = 0, $max = \count($codes); $i < $max; ++$i) { if (0 === $i) { $lang = strtolower($codes[0]); } else { $lang .= '_' . strtoupper($codes[$i]); } } } } $this->languages[] = $lang; } return $this->languages; } public function getCharsets() { if (null !== $this->charsets) { return $this->charsets; } return $this->charsets = array_keys(AcceptHeader::fromString($this->headers->get('Accept-Charset'))->all()); } public function getEncodings() { if (null !== $this->encodings) { return $this->encodings; } return $this->encodings = array_keys(AcceptHeader::fromString($this->headers->get('Accept-Encoding'))->all()); } public function getAcceptableContentTypes() { if (null !== $this->acceptableContentTypes) { return $this->acceptableContentTypes; } return $this->acceptableContentTypes = array_keys(AcceptHeader::fromString($this->headers->get('Accept'))->all()); } public function isXmlHttpRequest() { return 'XMLHttpRequest' == $this->headers->get('X-Requested-With'); } protected function prepareRequestUri() { $requestUri = ''; if ('1' == $this->server->get('IIS_WasUrlRewritten') && '' != $this->server->get('UNENCODED_URL')) { $requestUri = $this->server->get('UNENCODED_URL'); $this->server->remove('UNENCODED_URL'); $this->server->remove('IIS_WasUrlRewritten'); } elseif ($this->server->has('REQUEST_URI')) { $requestUri = $this->server->get('REQUEST_URI'); if ('' !== $requestUri && '/' === $requestUri[0]) { if (false !== ($pos = strpos($requestUri, '#'))) { $requestUri = substr($requestUri, 0, $pos); } } else { $uriComponents = parse_url($requestUri); if (isset($uriComponents['path'])) { $requestUri = $uriComponents['path']; } if (isset($uriComponents['query'])) { $requestUri .= '?' . $uriComponents['query']; } } } elseif ($this->server->has('ORIG_PATH_INFO')) { $requestUri = $this->server->get('ORIG_PATH_INFO'); if ('' != $this->server->get('QUERY_STRING')) { $requestUri .= '?' . $this->server->get('QUERY_STRING'); } $this->server->remove('ORIG_PATH_INFO'); } $this->server->set('REQUEST_URI', $requestUri); return $requestUri; } protected function prepareBaseUrl() { $filename = basename($this->server->get('SCRIPT_FILENAME')); if (basename($this->server->get('SCRIPT_NAME')) === $filename) { $baseUrl = $this->server->get('SCRIPT_NAME'); } elseif (basename($this->server->get('PHP_SELF')) === $filename) { $baseUrl = $this->server->get('PHP_SELF'); } elseif (basename($this->server->get('ORIG_SCRIPT_NAME')) === $filename) { $baseUrl = $this->server->get('ORIG_SCRIPT_NAME'); } else { $path = $this->server->get('PHP_SELF', ''); $file = $this->server->get('SCRIPT_FILENAME', ''); $segs = explode('/', trim($file, '/')); $segs = array_reverse($segs); $index = 0; $last = \count($segs); $baseUrl = ''; do { $seg = $segs[$index]; $baseUrl = '/' . $seg . $baseUrl; ++$index; } while ($last > $index && false !== ($pos = strpos($path, $baseUrl)) && 0 != $pos); } $requestUri = $this->getRequestUri(); if ('' !== $requestUri && '/' !== $requestUri[0]) { $requestUri = '/' . $requestUri; } if ($baseUrl && false !== ($prefix = $this->getUrlencodedPrefix($requestUri, $baseUrl))) { return $prefix; } if ($baseUrl && false !== ($prefix = $this->getUrlencodedPrefix($requestUri, rtrim(\dirname($baseUrl), '/' . \DIRECTORY_SEPARATOR) . '/'))) { return rtrim($prefix, '/' . \DIRECTORY_SEPARATOR); } $truncatedRequestUri = $requestUri; if (false !== ($pos = strpos($requestUri, '?'))) { $truncatedRequestUri = substr($requestUri, 0, $pos); } $basename = basename($baseUrl); if (empty($basename) || !strpos(rawurldecode($truncatedRequestUri), $basename)) { return ''; } if (\strlen($requestUri) >= \strlen($baseUrl) && false !== ($pos = strpos($requestUri, $baseUrl)) && 0 !== $pos) { $baseUrl = substr($requestUri, 0, $pos + \strlen($baseUrl)); } return rtrim($baseUrl, '/' . \DIRECTORY_SEPARATOR); } protected function prepareBasePath() { $baseUrl = $this->getBaseUrl(); if (empty($baseUrl)) { return ''; } $filename = basename($this->server->get('SCRIPT_FILENAME')); if (basename($baseUrl) === $filename) { $basePath = \dirname($baseUrl); } else { $basePath = $baseUrl; } if ('\\' === \DIRECTORY_SEPARATOR) { $basePath = str_replace('\\', '/', $basePath); } return rtrim($basePath, '/'); } protected function preparePathInfo() { if (null === ($requestUri = $this->getRequestUri())) { return '/'; } if (false !== ($pos = strpos($requestUri, '?'))) { $requestUri = substr($requestUri, 0, $pos); } if ('' !== $requestUri && '/' !== $requestUri[0]) { $requestUri = '/' . $requestUri; } if (null === ($baseUrl = $this->getBaseUrl())) { return $requestUri; } $pathInfo = substr($requestUri, \strlen($baseUrl)); if (false === $pathInfo || '' === $pathInfo) { return '/'; } return (string) $pathInfo; } protected static function initializeFormats() { static::$formats = ['html' => ['text/html', 'application/xhtml+xml'], 'txt' => ['text/plain'], 'js' => ['application/javascript', 'application/x-javascript', 'text/javascript'], 'css' => ['text/css'], 'json' => ['application/json', 'application/x-json'], 'jsonld' => ['application/ld+json'], 'xml' => ['text/xml', 'application/xml', 'application/x-xml'], 'rdf' => ['application/rdf+xml'], 'atom' => ['application/atom+xml'], 'rss' => ['application/rss+xml'], 'form' => ['application/x-www-form-urlencoded']]; } private function setPhpDefaultLocale($locale) { try { if (class_exists('Locale', false)) { \Locale::setDefault($locale); } } catch (\Exception $e) { } } private function getUrlencodedPrefix($string, $prefix) { if (0 !== strpos(rawurldecode($string), $prefix)) { return false; } $len = \strlen($prefix); if (preg_match(sprintf('#^(%%[[:xdigit:]]{2}|.){%d}#', $len), $string, $match)) { return $match[0]; } return false; } private static function createRequestFromFactory(array $query = [], array $request = [], array $attributes = [], array $cookies = [], array $files = [], array $server = [], $content = null) { if (self::$requestFactory) { $request = \call_user_func(self::$requestFactory, $query, $request, $attributes, $cookies, $files, $server, $content); if (!$request instanceof self) { throw new \LogicException('The Request factory must return an instance of Symfony\\Component\\HttpFoundation\\Request.'); } return $request; } return new static($query, $request, $attributes, $cookies, $files, $server, $content); } public function isFromTrustedProxy() { return self::$trustedProxies && IpUtils::checkIp($this->server->get('REMOTE_ADDR'), self::$trustedProxies); } private function getTrustedValues($type, $ip = null) { $clientValues = []; $forwardedValues = []; if (self::$trustedHeaders[$type] && $this->headers->has(self::$trustedHeaders[$type])) { foreach (explode(',', $this->headers->get(self::$trustedHeaders[$type])) as $v) { $clientValues[] = (self::HEADER_CLIENT_PORT === $type ? '0.0.0.0:' : '') . trim($v); } } if (self::$trustedHeaders[self::HEADER_FORWARDED] && $this->headers->has(self::$trustedHeaders[self::HEADER_FORWARDED])) { $forwardedValues = $this->headers->get(self::$trustedHeaders[self::HEADER_FORWARDED]); $forwardedValues = preg_match_all(sprintf('{(?:%s)="?([a-zA-Z0-9\\.:_\\-/\\[\\]]*+)}', self::$forwardedParams[$type]), $forwardedValues, $matches) ? $matches[1] : []; if (self::HEADER_CLIENT_PORT === $type) { foreach ($forwardedValues as $k => $v) { if (']' === substr($v, -1) || false === ($v = strrchr($v, ':'))) { $v = $this->isSecure() ? ':443' : ':80'; } $forwardedValues[$k] = '0.0.0.0' . $v; } } } if (null !== $ip) { $clientValues = $this->normalizeAndFilterClientIps($clientValues, $ip); $forwardedValues = $this->normalizeAndFilterClientIps($forwardedValues, $ip); } if ($forwardedValues === $clientValues || !$clientValues) { return $forwardedValues; } if (!$forwardedValues) { return $clientValues; } if (!$this->isForwardedValid) { return null !== $ip ? ['0.0.0.0', $ip] : []; } $this->isForwardedValid = false; throw new ConflictingHeadersException(sprintf('The request has both a trusted "%s" header and a trusted "%s" header, conflicting with each other. You should either configure your proxy to remove one of them, or configure your project to distrust the offending one.', self::$trustedHeaders[self::HEADER_FORWARDED], self::$trustedHeaders[$type])); } private function normalizeAndFilterClientIps(array $clientIps, $ip) { if (!$clientIps) { return []; } $clientIps[] = $ip; $firstTrustedIp = null; foreach ($clientIps as $key => $clientIp) { if (strpos($clientIp, '.')) { $i = strpos($clientIp, ':'); if ($i) { $clientIps[$key] = $clientIp = substr($clientIp, 0, $i); } } elseif (0 === strpos($clientIp, '[')) { $i = strpos($clientIp, ']', 1); $clientIps[$key] = $clientIp = substr($clientIp, 1, $i - 1); } if (!filter_var($clientIp, FILTER_VALIDATE_IP)) { unset($clientIps[$key]); continue; } if (IpUtils::checkIp($clientIp, self::$trustedProxies)) { unset($clientIps[$key]); if (null === $firstTrustedIp) { $firstTrustedIp = $clientIp; } } } return $clientIps ? array_reverse($clientIps) : [$firstTrustedIp]; } } } namespace Symfony\Component\HttpFoundation { class ParameterBag implements \IteratorAggregate, \Countable { protected $parameters; public function __construct(array $parameters = []) { $this->parameters = $parameters; } public function all() { return $this->parameters; } public function keys() { return array_keys($this->parameters); } public function replace(array $parameters = []) { $this->parameters = $parameters; } public function add(array $parameters = []) { $this->parameters = array_replace($this->parameters, $parameters); } public function get($key, $default = null) { return \array_key_exists($key, $this->parameters) ? $this->parameters[$key] : $default; } public function set($key, $value) { $this->parameters[$key] = $value; } public function has($key) { return \array_key_exists($key, $this->parameters); } public function remove($key) { unset($this->parameters[$key]); } public function getAlpha($key, $default = '') { return preg_replace('/[^[:alpha:]]/', '', $this->get($key, $default)); } public function getAlnum($key, $default = '') { return preg_replace('/[^[:alnum:]]/', '', $this->get($key, $default)); } public function getDigits($key, $default = '') { return str_replace(['-', '+'], '', $this->filter($key, $default, FILTER_SANITIZE_NUMBER_INT)); } public function getInt($key, $default = 0) { return (int) $this->get($key, $default); } public function getBoolean($key, $default = false) { return $this->filter($key, $default, FILTER_VALIDATE_BOOLEAN); } public function filter($key, $default = null, $filter = FILTER_DEFAULT, $options = []) { $value = $this->get($key, $default); if (!\is_array($options) && $options) { $options = ['flags' => $options]; } if (\is_array($value) && !isset($options['flags'])) { $options['flags'] = FILTER_REQUIRE_ARRAY; } return filter_var($value, $filter, $options); } public function getIterator() { return new \ArrayIterator($this->parameters); } public function count() { return \count($this->parameters); } } } namespace Symfony\Component\HttpFoundation { use Symfony\Component\HttpFoundation\File\UploadedFile; class FileBag extends ParameterBag { private static $fileKeys = ['error', 'name', 'size', 'tmp_name', 'type']; public function __construct(array $parameters = []) { $this->replace($parameters); } public function replace(array $files = []) { $this->parameters = []; $this->add($files); } public function set($key, $value) { if (!\is_array($value) && !$value instanceof UploadedFile) { throw new \InvalidArgumentException('An uploaded file must be an array or an instance of UploadedFile.'); } parent::set($key, $this->convertFileInformation($value)); } public function add(array $files = []) { foreach ($files as $key => $file) { $this->set($key, $file); } } protected function convertFileInformation($file) { if ($file instanceof UploadedFile) { return $file; } if (\is_array($file)) { $file = $this->fixPhpFilesArray($file); $keys = array_keys($file); sort($keys); if ($keys == self::$fileKeys) { if (UPLOAD_ERR_NO_FILE == $file['error']) { $file = null; } else { $file = new UploadedFile($file['tmp_name'], $file['name'], $file['type'], $file['size'], $file['error']); } } else { $file = array_map([$this, 'convertFileInformation'], $file); if (array_keys($keys) === $keys) { $file = array_filter($file); } } } return $file; } protected function fixPhpFilesArray($data) { $keys = array_keys($data); sort($keys); if (self::$fileKeys != $keys || !isset($data['name']) || !\is_array($data['name'])) { return $data; } $files = $data; foreach (self::$fileKeys as $k) { unset($files[$k]); } foreach ($data['name'] as $key => $name) { $files[$key] = $this->fixPhpFilesArray(['error' => $data['error'][$key], 'name' => $name, 'type' => $data['type'][$key], 'tmp_name' => $data['tmp_name'][$key], 'size' => $data['size'][$key]]); } return $files; } } } namespace Symfony\Component\HttpFoundation { class ServerBag extends ParameterBag { public function getHeaders() { $headers = []; $contentHeaders = ['CONTENT_LENGTH' => true, 'CONTENT_MD5' => true, 'CONTENT_TYPE' => true]; foreach ($this->parameters as $key => $value) { if (0 === strpos($key, 'HTTP_')) { $headers[substr($key, 5)] = $value; } elseif (isset($contentHeaders[$key])) { $headers[$key] = $value; } } if (isset($this->parameters['PHP_AUTH_USER'])) { $headers['PHP_AUTH_USER'] = $this->parameters['PHP_AUTH_USER']; $headers['PHP_AUTH_PW'] = isset($this->parameters['PHP_AUTH_PW']) ? $this->parameters['PHP_AUTH_PW'] : ''; } else { $authorizationHeader = null; if (isset($this->parameters['HTTP_AUTHORIZATION'])) { $authorizationHeader = $this->parameters['HTTP_AUTHORIZATION']; } elseif (isset($this->parameters['REDIRECT_HTTP_AUTHORIZATION'])) { $authorizationHeader = $this->parameters['REDIRECT_HTTP_AUTHORIZATION']; } if (null !== $authorizationHeader) { if (0 === stripos($authorizationHeader, 'basic ')) { $exploded = explode(':', base64_decode(substr($authorizationHeader, 6)), 2); if (2 == \count($exploded)) { list($headers['PHP_AUTH_USER'], $headers['PHP_AUTH_PW']) = $exploded; } } elseif (empty($this->parameters['PHP_AUTH_DIGEST']) && 0 === stripos($authorizationHeader, 'digest ')) { $headers['PHP_AUTH_DIGEST'] = $authorizationHeader; $this->parameters['PHP_AUTH_DIGEST'] = $authorizationHeader; } elseif (0 === stripos($authorizationHeader, 'bearer ')) { $headers['AUTHORIZATION'] = $authorizationHeader; } } } if (isset($headers['AUTHORIZATION'])) { return $headers; } if (isset($headers['PHP_AUTH_USER'])) { $headers['AUTHORIZATION'] = 'Basic ' . base64_encode($headers['PHP_AUTH_USER'] . ':' . $headers['PHP_AUTH_PW']); } elseif (isset($headers['PHP_AUTH_DIGEST'])) { $headers['AUTHORIZATION'] = $headers['PHP_AUTH_DIGEST']; } return $headers; } } } namespace Symfony\Component\HttpFoundation { class HeaderBag implements \IteratorAggregate, \Countable { protected $headers = []; protected $cacheControl = []; public function __construct(array $headers = []) { foreach ($headers as $key => $values) { $this->set($key, $values); } } public function __toString() { if (!($headers = $this->all())) { return ''; } ksort($headers); $max = max(array_map('strlen', array_keys($headers))) + 1; $content = ''; foreach ($headers as $name => $values) { $name = implode('-', array_map('ucfirst', explode('-', $name))); foreach ($values as $value) { $content .= sprintf("%-{$max}s %s\r\n", $name . ':', $value); } } return $content; } public function all() { return $this->headers; } public function keys() { return array_keys($this->all()); } public function replace(array $headers = []) { $this->headers = []; $this->add($headers); } public function add(array $headers) { foreach ($headers as $key => $values) { $this->set($key, $values); } } public function get($key, $default = null, $first = true) { $key = str_replace('_', '-', strtolower($key)); $headers = $this->all(); if (!\array_key_exists($key, $headers)) { if (null === $default) { return $first ? null : []; } return $first ? $default : [$default]; } if ($first) { if (!$headers[$key]) { return $default; } if (null === $headers[$key][0]) { return null; } return (string) $headers[$key][0]; } return $headers[$key]; } public function set($key, $values, $replace = true) { $key = str_replace('_', '-', strtolower($key)); if (\is_array($values)) { $values = array_values($values); if (true === $replace || !isset($this->headers[$key])) { $this->headers[$key] = $values; } else { $this->headers[$key] = array_merge($this->headers[$key], $values); } } else { if (true === $replace || !isset($this->headers[$key])) { $this->headers[$key] = [$values]; } else { $this->headers[$key][] = $values; } } if ('cache-control' === $key) { $this->cacheControl = $this->parseCacheControl(implode(', ', $this->headers[$key])); } } public function has($key) { return \array_key_exists(str_replace('_', '-', strtolower($key)), $this->all()); } public function contains($key, $value) { return \in_array($value, $this->get($key, null, false)); } public function remove($key) { $key = str_replace('_', '-', strtolower($key)); unset($this->headers[$key]); if ('cache-control' === $key) { $this->cacheControl = []; } } public function getDate($key, \DateTime $default = null) { if (null === ($value = $this->get($key))) { return $default; } if (false === ($date = \DateTime::createFromFormat(DATE_RFC2822, $value))) { throw new \RuntimeException(sprintf('The "%s" HTTP header is not parseable (%s).', $key, $value)); } return $date; } public function addCacheControlDirective($key, $value = true) { $this->cacheControl[$key] = $value; $this->set('Cache-Control', $this->getCacheControlHeader()); } public function hasCacheControlDirective($key) { return \array_key_exists($key, $this->cacheControl); } public function getCacheControlDirective($key) { return \array_key_exists($key, $this->cacheControl) ? $this->cacheControl[$key] : null; } public function removeCacheControlDirective($key) { unset($this->cacheControl[$key]); $this->set('Cache-Control', $this->getCacheControlHeader()); } public function getIterator() { return new \ArrayIterator($this->headers); } public function count() { return \count($this->headers); } protected function getCacheControlHeader() { $parts = []; ksort($this->cacheControl); foreach ($this->cacheControl as $key => $value) { if (true === $value) { $parts[] = $key; } else { if (preg_match('#[^a-zA-Z0-9._-]#', $value)) { $value = '"' . $value . '"'; } $parts[] = "{$key}={$value}"; } } return implode(', ', $parts); } protected function parseCacheControl($header) { $cacheControl = []; preg_match_all('#([a-zA-Z][a-zA-Z_-]*)\\s*(?:=(?:"([^"]*)"|([^ \\t",;]*)))?#', $header, $matches, PREG_SET_ORDER); foreach ($matches as $match) { $cacheControl[strtolower($match[1])] = isset($match[3]) ? $match[3] : (isset($match[2]) ? $match[2] : true); } return $cacheControl; } } } namespace Symfony\Component\HttpFoundation { class Response { const HTTP_CONTINUE = 100; const HTTP_SWITCHING_PROTOCOLS = 101; const HTTP_PROCESSING = 102; const HTTP_EARLY_HINTS = 103; const HTTP_OK = 200; const HTTP_CREATED = 201; const HTTP_ACCEPTED = 202; const HTTP_NON_AUTHORITATIVE_INFORMATION = 203; const HTTP_NO_CONTENT = 204; const HTTP_RESET_CONTENT = 205; const HTTP_PARTIAL_CONTENT = 206; const HTTP_MULTI_STATUS = 207; const HTTP_ALREADY_REPORTED = 208; const HTTP_IM_USED = 226; const HTTP_MULTIPLE_CHOICES = 300; const HTTP_MOVED_PERMANENTLY = 301; const HTTP_FOUND = 302; const HTTP_SEE_OTHER = 303; const HTTP_NOT_MODIFIED = 304; const HTTP_USE_PROXY = 305; const HTTP_RESERVED = 306; const HTTP_TEMPORARY_REDIRECT = 307; const HTTP_PERMANENTLY_REDIRECT = 308; const HTTP_BAD_REQUEST = 400; const HTTP_UNAUTHORIZED = 401; const HTTP_PAYMENT_REQUIRED = 402; const HTTP_FORBIDDEN = 403; const HTTP_NOT_FOUND = 404; const HTTP_METHOD_NOT_ALLOWED = 405; const HTTP_NOT_ACCEPTABLE = 406; const HTTP_PROXY_AUTHENTICATION_REQUIRED = 407; const HTTP_REQUEST_TIMEOUT = 408; const HTTP_CONFLICT = 409; const HTTP_GONE = 410; const HTTP_LENGTH_REQUIRED = 411; const HTTP_PRECONDITION_FAILED = 412; const HTTP_REQUEST_ENTITY_TOO_LARGE = 413; const HTTP_REQUEST_URI_TOO_LONG = 414; const HTTP_UNSUPPORTED_MEDIA_TYPE = 415; const HTTP_REQUESTED_RANGE_NOT_SATISFIABLE = 416; const HTTP_EXPECTATION_FAILED = 417; const HTTP_I_AM_A_TEAPOT = 418; const HTTP_MISDIRECTED_REQUEST = 421; const HTTP_UNPROCESSABLE_ENTITY = 422; const HTTP_LOCKED = 423; const HTTP_FAILED_DEPENDENCY = 424; const HTTP_RESERVED_FOR_WEBDAV_ADVANCED_COLLECTIONS_EXPIRED_PROPOSAL = 425; const HTTP_TOO_EARLY = 425; const HTTP_UPGRADE_REQUIRED = 426; const HTTP_PRECONDITION_REQUIRED = 428; const HTTP_TOO_MANY_REQUESTS = 429; const HTTP_REQUEST_HEADER_FIELDS_TOO_LARGE = 431; const HTTP_UNAVAILABLE_FOR_LEGAL_REASONS = 451; const HTTP_INTERNAL_SERVER_ERROR = 500; const HTTP_NOT_IMPLEMENTED = 501; const HTTP_BAD_GATEWAY = 502; const HTTP_SERVICE_UNAVAILABLE = 503; const HTTP_GATEWAY_TIMEOUT = 504; const HTTP_VERSION_NOT_SUPPORTED = 505; const HTTP_VARIANT_ALSO_NEGOTIATES_EXPERIMENTAL = 506; const HTTP_INSUFFICIENT_STORAGE = 507; const HTTP_LOOP_DETECTED = 508; const HTTP_NOT_EXTENDED = 510; const HTTP_NETWORK_AUTHENTICATION_REQUIRED = 511; public $headers; protected $content; protected $version; protected $statusCode; protected $statusText; protected $charset; public static $statusTexts = [ 100 => 'Continue', 101 => 'Switching Protocols', 102 => 'Processing', 103 => 'Early Hints', 200 => 'OK', 201 => 'Created', 202 => 'Accepted', 203 => 'Non-Authoritative Information', 204 => 'No Content', 205 => 'Reset Content', 206 => 'Partial Content', 207 => 'Multi-Status', 208 => 'Already Reported', 226 => 'IM Used', 300 => 'Multiple Choices', 301 => 'Moved Permanently', 302 => 'Found', 303 => 'See Other', 304 => 'Not Modified', 305 => 'Use Proxy', 307 => 'Temporary Redirect', 308 => 'Permanent Redirect', 400 => 'Bad Request', 401 => 'Unauthorized', 402 => 'Payment Required', 403 => 'Forbidden', 404 => 'Not Found', 405 => 'Method Not Allowed', 406 => 'Not Acceptable', 407 => 'Proxy Authentication Required', 408 => 'Request Timeout', 409 => 'Conflict', 410 => 'Gone', 411 => 'Length Required', 412 => 'Precondition Failed', 413 => 'Payload Too Large', 414 => 'URI Too Long', 415 => 'Unsupported Media Type', 416 => 'Range Not Satisfiable', 417 => 'Expectation Failed', 418 => 'I\'m a teapot', 421 => 'Misdirected Request', 422 => 'Unprocessable Entity', 423 => 'Locked', 424 => 'Failed Dependency', 425 => 'Too Early', 426 => 'Upgrade Required', 428 => 'Precondition Required', 429 => 'Too Many Requests', 431 => 'Request Header Fields Too Large', 451 => 'Unavailable For Legal Reasons', 500 => 'Internal Server Error', 501 => 'Not Implemented', 502 => 'Bad Gateway', 503 => 'Service Unavailable', 504 => 'Gateway Timeout', 505 => 'HTTP Version Not Supported', 506 => 'Variant Also Negotiates', 507 => 'Insufficient Storage', 508 => 'Loop Detected', 510 => 'Not Extended', 511 => 'Network Authentication Required', ]; public function __construct($content = '', $status = 200, $headers = []) { $this->headers = new ResponseHeaderBag($headers); $this->setContent($content); $this->setStatusCode($status); $this->setProtocolVersion('1.0'); } public static function create($content = '', $status = 200, $headers = []) { return new static($content, $status, $headers); } public function __toString() { return sprintf('HTTP/%s %s %s', $this->version, $this->statusCode, $this->statusText) . "\r\n" . $this->headers . "\r\n" . $this->getContent(); } public function __clone() { $this->headers = clone $this->headers; } public function prepare(Request $request) { $headers = $this->headers; if ($this->isInformational() || $this->isEmpty()) { $this->setContent(null); $headers->remove('Content-Type'); $headers->remove('Content-Length'); } else { if (!$headers->has('Content-Type')) { $format = $request->getRequestFormat(); if (null !== $format && ($mimeType = $request->getMimeType($format))) { $headers->set('Content-Type', $mimeType); } } $charset = $this->charset ?: 'UTF-8'; if (!$headers->has('Content-Type')) { $headers->set('Content-Type', 'text/html; charset=' . $charset); } elseif (0 === stripos($headers->get('Content-Type'), 'text/') && false === stripos($headers->get('Content-Type'), 'charset')) { $headers->set('Content-Type', $headers->get('Content-Type') . '; charset=' . $charset); } if ($headers->has('Transfer-Encoding')) { $headers->remove('Content-Length'); } if ($request->isMethod('HEAD')) { $length = $headers->get('Content-Length'); $this->setContent(null); if ($length) { $headers->set('Content-Length', $length); } } } if ('HTTP/1.0' != $request->server->get('SERVER_PROTOCOL')) { $this->setProtocolVersion('1.1'); } if ('1.0' == $this->getProtocolVersion() && false !== strpos($headers->get('Cache-Control'), 'no-cache')) { $headers->set('pragma', 'no-cache'); $headers->set('expires', -1); } $this->ensureIEOverSSLCompatibility($request); return $this; } public function sendHeaders() { if (headers_sent()) { return $this; } foreach ($this->headers->allPreserveCaseWithoutCookies() as $name => $values) { $replace = 0 === strcasecmp($name, 'Content-Type'); foreach ($values as $value) { header($name . ': ' . $value, $replace, $this->statusCode); } } foreach ($this->headers->getCookies() as $cookie) { header('Set-Cookie: ' . $cookie, false, $this->statusCode); } header(sprintf('HTTP/%s %s %s', $this->version, $this->statusCode, $this->statusText), true, $this->statusCode); return $this; } public function sendContent() { echo $this->content; return $this; } public function send() { $this->sendHeaders(); $this->sendContent(); if (\function_exists('fastcgi_finish_request')) { fastcgi_finish_request(); } elseif (!\in_array(\PHP_SAPI, ['cli', 'phpdbg'], true)) { static::closeOutputBuffers(0, true); } return $this; } public function setContent($content) { if (null !== $content && !\is_string($content) && !is_numeric($content) && !\is_callable([$content, '__toString'])) { throw new \UnexpectedValueException(sprintf('The Response content must be a string or object implementing __toString(), "%s" given.', \gettype($content))); } $this->content = (string) $content; return $this; } public function getContent() { return $this->content; } public function setProtocolVersion($version) { $this->version = $version; return $this; } public function getProtocolVersion() { return $this->version; } public function setStatusCode($code, $text = null) { $this->statusCode = $code = (int) $code; if ($this->isInvalid()) { throw new \InvalidArgumentException(sprintf('The HTTP status code "%s" is not valid.', $code)); } if (null === $text) { $this->statusText = isset(self::$statusTexts[$code]) ? self::$statusTexts[$code] : 'unknown status'; return $this; } if (false === $text) { $this->statusText = ''; return $this; } $this->statusText = $text; return $this; } public function getStatusCode() { return $this->statusCode; } public function setCharset($charset) { $this->charset = $charset; return $this; } public function getCharset() { return $this->charset; } public function isCacheable() { if (!\in_array($this->statusCode, [200, 203, 300, 301, 302, 404, 410])) { return false; } if ($this->headers->hasCacheControlDirective('no-store') || $this->headers->getCacheControlDirective('private')) { return false; } return $this->isValidateable() || $this->isFresh(); } public function isFresh() { return $this->getTtl() > 0; } public function isValidateable() { return $this->headers->has('Last-Modified') || $this->headers->has('ETag'); } public function setPrivate() { $this->headers->removeCacheControlDirective('public'); $this->headers->addCacheControlDirective('private'); return $this; } public function setPublic() { $this->headers->addCacheControlDirective('public'); $this->headers->removeCacheControlDirective('private'); return $this; } public function setImmutable($immutable = true) { if ($immutable) { $this->headers->addCacheControlDirective('immutable'); } else { $this->headers->removeCacheControlDirective('immutable'); } return $this; } public function isImmutable() { return $this->headers->hasCacheControlDirective('immutable'); } public function mustRevalidate() { return $this->headers->hasCacheControlDirective('must-revalidate') || $this->headers->hasCacheControlDirective('proxy-revalidate'); } public function getDate() { return $this->headers->getDate('Date'); } public function setDate(\DateTime $date) { $date->setTimezone(new \DateTimeZone('UTC')); $this->headers->set('Date', $date->format('D, d M Y H:i:s') . ' GMT'); return $this; } public function getAge() { if (null !== ($age = $this->headers->get('Age'))) { return (int) $age; } return max(time() - (int) $this->getDate()->format('U'), 0); } public function expire() { if ($this->isFresh()) { $this->headers->set('Age', $this->getMaxAge()); $this->headers->remove('Expires'); } return $this; } public function getExpires() { try { return $this->headers->getDate('Expires'); } catch (\RuntimeException $e) { return \DateTime::createFromFormat(DATE_RFC2822, 'Sat, 01 Jan 00 00:00:00 +0000'); } } public function setExpires(\DateTime $date = null) { if (null === $date) { $this->headers->remove('Expires'); } else { $date = clone $date; $date->setTimezone(new \DateTimeZone('UTC')); $this->headers->set('Expires', $date->format('D, d M Y H:i:s') . ' GMT'); } return $this; } public function getMaxAge() { if ($this->headers->hasCacheControlDirective('s-maxage')) { return (int) $this->headers->getCacheControlDirective('s-maxage'); } if ($this->headers->hasCacheControlDirective('max-age')) { return (int) $this->headers->getCacheControlDirective('max-age'); } if (null !== $this->getExpires()) { return (int) $this->getExpires()->format('U') - (int) $this->getDate()->format('U'); } return null; } public function setMaxAge($value) { $this->headers->addCacheControlDirective('max-age', $value); return $this; } public function setSharedMaxAge($value) { $this->setPublic(); $this->headers->addCacheControlDirective('s-maxage', $value); return $this; } public function getTtl() { if (null !== ($maxAge = $this->getMaxAge())) { return $maxAge - $this->getAge(); } return null; } public function setTtl($seconds) { $this->setSharedMaxAge($this->getAge() + $seconds); return $this; } public function setClientTtl($seconds) { $this->setMaxAge($this->getAge() + $seconds); return $this; } public function getLastModified() { return $this->headers->getDate('Last-Modified'); } public function setLastModified(\DateTime $date = null) { if (null === $date) { $this->headers->remove('Last-Modified'); } else { $date = clone $date; $date->setTimezone(new \DateTimeZone('UTC')); $this->headers->set('Last-Modified', $date->format('D, d M Y H:i:s') . ' GMT'); } return $this; } public function getEtag() { return $this->headers->get('ETag'); } public function setEtag($etag = null, $weak = false) { if (null === $etag) { $this->headers->remove('Etag'); } else { if (0 !== strpos($etag, '"')) { $etag = '"' . $etag . '"'; } $this->headers->set('ETag', (true === $weak ? 'W/' : '') . $etag); } return $this; } public function setCache(array $options) { if ($diff = array_diff(array_keys($options), ['etag', 'last_modified', 'max_age', 's_maxage', 'private', 'public', 'immutable'])) { throw new \InvalidArgumentException(sprintf('Response does not support the following options: "%s".', implode('", "', $diff))); } if (isset($options['etag'])) { $this->setEtag($options['etag']); } if (isset($options['last_modified'])) { $this->setLastModified($options['last_modified']); } if (isset($options['max_age'])) { $this->setMaxAge($options['max_age']); } if (isset($options['s_maxage'])) { $this->setSharedMaxAge($options['s_maxage']); } if (isset($options['public'])) { if ($options['public']) { $this->setPublic(); } else { $this->setPrivate(); } } if (isset($options['private'])) { if ($options['private']) { $this->setPrivate(); } else { $this->setPublic(); } } if (isset($options['immutable'])) { $this->setImmutable((bool) $options['immutable']); } return $this; } public function setNotModified() { $this->setStatusCode(304); $this->setContent(null); foreach (['Allow', 'Content-Encoding', 'Content-Language', 'Content-Length', 'Content-MD5', 'Content-Type', 'Last-Modified'] as $header) { $this->headers->remove($header); } return $this; } public function hasVary() { return null !== $this->headers->get('Vary'); } public function getVary() { if (!($vary = $this->headers->get('Vary', null, false))) { return []; } $ret = []; foreach ($vary as $item) { $ret = array_merge($ret, preg_split('/[\\s,]+/', $item)); } return $ret; } public function setVary($headers, $replace = true) { $this->headers->set('Vary', $headers, $replace); return $this; } public function isNotModified(Request $request) { if (!$request->isMethodCacheable()) { return false; } $notModified = false; $lastModified = $this->headers->get('Last-Modified'); $modifiedSince = $request->headers->get('If-Modified-Since'); if ($etags = $request->getETags()) { $notModified = \in_array($this->getEtag(), $etags) || \in_array('*', $etags); } if ($modifiedSince && $lastModified) { $notModified = strtotime($modifiedSince) >= strtotime($lastModified) && (!$etags || $notModified); } if ($notModified) { $this->setNotModified(); } return $notModified; } public function isInvalid() { return $this->statusCode < 100 || $this->statusCode >= 600; } public function isInformational() { return $this->statusCode >= 100 && $this->statusCode < 200; } public function isSuccessful() { return $this->statusCode >= 200 && $this->statusCode < 300; } public function isRedirection() { return $this->statusCode >= 300 && $this->statusCode < 400; } public function isClientError() { return $this->statusCode >= 400 && $this->statusCode < 500; } public function isServerError() { return $this->statusCode >= 500 && $this->statusCode < 600; } public function isOk() { return 200 === $this->statusCode; } public function isForbidden() { return 403 === $this->statusCode; } public function isNotFound() { return 404 === $this->statusCode; } public function isRedirect($location = null) { return \in_array($this->statusCode, [201, 301, 302, 303, 307, 308]) && (null === $location ?: $location == $this->headers->get('Location')); } public function isEmpty() { return \in_array($this->statusCode, [204, 304]); } public static function closeOutputBuffers($targetLevel, $flush) { $status = ob_get_status(true); $level = \count($status); $flags = \defined('PHP_OUTPUT_HANDLER_REMOVABLE') ? PHP_OUTPUT_HANDLER_REMOVABLE | ($flush ? PHP_OUTPUT_HANDLER_FLUSHABLE : PHP_OUTPUT_HANDLER_CLEANABLE) : -1; while ($level-- > $targetLevel && ($s = $status[$level]) && (!isset($s['del']) ? !isset($s['flags']) || ($s['flags'] & $flags) === $flags : $s['del'])) { if ($flush) { ob_end_flush(); } else { ob_end_clean(); } } } protected function ensureIEOverSSLCompatibility(Request $request) { if (false !== stripos($this->headers->get('Content-Disposition'), 'attachment') && 1 == preg_match('/MSIE (.*?);/i', $request->server->get('HTTP_USER_AGENT'), $match) && true === $request->isSecure()) { if ((int) preg_replace('/(MSIE )(.*?);/', '$2', $match[0]) < 9) { $this->headers->remove('Cache-Control'); } } } } } namespace Symfony\Component\HttpFoundation { class ResponseHeaderBag extends HeaderBag { const COOKIES_FLAT = 'flat'; const COOKIES_ARRAY = 'array'; const DISPOSITION_ATTACHMENT = 'attachment'; const DISPOSITION_INLINE = 'inline'; protected $computedCacheControl = []; protected $cookies = []; protected $headerNames = []; public function __construct(array $headers = []) { parent::__construct($headers); if (!isset($this->headers['cache-control'])) { $this->set('Cache-Control', ''); } if (!isset($this->headers['date'])) { $this->initDate(); } } public function allPreserveCase() { $headers = []; foreach ($this->all() as $name => $value) { $headers[isset($this->headerNames[$name]) ? $this->headerNames[$name] : $name] = $value; } return $headers; } public function allPreserveCaseWithoutCookies() { $headers = $this->allPreserveCase(); if (isset($this->headerNames['set-cookie'])) { unset($headers[$this->headerNames['set-cookie']]); } return $headers; } public function replace(array $headers = []) { $this->headerNames = []; parent::replace($headers); if (!isset($this->headers['cache-control'])) { $this->set('Cache-Control', ''); } if (!isset($this->headers['date'])) { $this->initDate(); } } public function all() { $headers = parent::all(); foreach ($this->getCookies() as $cookie) { $headers['set-cookie'][] = (string) $cookie; } return $headers; } public function set($key, $values, $replace = true) { $uniqueKey = str_replace('_', '-', strtolower($key)); if ('set-cookie' === $uniqueKey) { if ($replace) { $this->cookies = []; } foreach ((array) $values as $cookie) { $this->setCookie(Cookie::fromString($cookie)); } $this->headerNames[$uniqueKey] = $key; return; } $this->headerNames[$uniqueKey] = $key; parent::set($key, $values, $replace); if (\in_array($uniqueKey, ['cache-control', 'etag', 'last-modified', 'expires'], true)) { $computed = $this->computeCacheControlValue(); $this->headers['cache-control'] = [$computed]; $this->headerNames['cache-control'] = 'Cache-Control'; $this->computedCacheControl = $this->parseCacheControl($computed); } } public function remove($key) { $uniqueKey = str_replace('_', '-', strtolower($key)); unset($this->headerNames[$uniqueKey]); if ('set-cookie' === $uniqueKey) { $this->cookies = []; return; } parent::remove($key); if ('cache-control' === $uniqueKey) { $this->computedCacheControl = []; } if ('date' === $uniqueKey) { $this->initDate(); } } public function hasCacheControlDirective($key) { return \array_key_exists($key, $this->computedCacheControl); } public function getCacheControlDirective($key) { return \array_key_exists($key, $this->computedCacheControl) ? $this->computedCacheControl[$key] : null; } public function setCookie(Cookie $cookie) { $this->cookies[$cookie->getDomain()][$cookie->getPath()][$cookie->getName()] = $cookie; $this->headerNames['set-cookie'] = 'Set-Cookie'; } public function removeCookie($name, $path = '/', $domain = null) { if (null === $path) { $path = '/'; } unset($this->cookies[$domain][$path][$name]); if (empty($this->cookies[$domain][$path])) { unset($this->cookies[$domain][$path]); if (empty($this->cookies[$domain])) { unset($this->cookies[$domain]); } } if (empty($this->cookies)) { unset($this->headerNames['set-cookie']); } } public function getCookies($format = self::COOKIES_FLAT) { if (!\in_array($format, [self::COOKIES_FLAT, self::COOKIES_ARRAY])) { throw new \InvalidArgumentException(sprintf('Format "%s" invalid (%s).', $format, implode(', ', [self::COOKIES_FLAT, self::COOKIES_ARRAY]))); } if (self::COOKIES_ARRAY === $format) { return $this->cookies; } $flattenedCookies = []; foreach ($this->cookies as $path) { foreach ($path as $cookies) { foreach ($cookies as $cookie) { $flattenedCookies[] = $cookie; } } } return $flattenedCookies; } public function clearCookie($name, $path = '/', $domain = null, $secure = false, $httpOnly = true) { $sameSite = \func_num_args() > 5 ? func_get_arg(5) : null; $this->setCookie(new Cookie($name, null, 1, $path, $domain, $secure, $httpOnly, false, $sameSite)); } public function makeDisposition($disposition, $filename, $filenameFallback = '') { if (!\in_array($disposition, [self::DISPOSITION_ATTACHMENT, self::DISPOSITION_INLINE])) { throw new \InvalidArgumentException(sprintf('The disposition must be either "%s" or "%s".', self::DISPOSITION_ATTACHMENT, self::DISPOSITION_INLINE)); } if ('' == $filenameFallback) { $filenameFallback = $filename; } if (!preg_match('/^[\\x20-\\x7e]*$/', $filenameFallback)) { throw new \InvalidArgumentException('The filename fallback must only contain ASCII characters.'); } if (false !== strpos($filenameFallback, '%')) { throw new \InvalidArgumentException('The filename fallback cannot contain the "%" character.'); } if (false !== strpos($filename, '/') || false !== strpos($filename, '\\') || false !== strpos($filenameFallback, '/') || false !== strpos($filenameFallback, '\\')) { throw new \InvalidArgumentException('The filename and the fallback cannot contain the "/" and "\\" characters.'); } $output = sprintf('%s; filename="%s"', $disposition, str_replace('"', '\\"', $filenameFallback)); if ($filename !== $filenameFallback) { $output .= sprintf("; filename*=utf-8''%s", rawurlencode($filename)); } return $output; } protected function computeCacheControlValue() { if (!$this->cacheControl) { if ($this->has('Last-Modified') || $this->has('Expires')) { return 'private, must-revalidate'; } return 'no-cache, private'; } $header = $this->getCacheControlHeader(); if (isset($this->cacheControl['public']) || isset($this->cacheControl['private'])) { return $header; } if (!isset($this->cacheControl['s-maxage'])) { return $header . ', private'; } return $header; } private function initDate() { $now = \DateTime::createFromFormat('U', time()); $now->setTimezone(new \DateTimeZone('UTC')); $this->set('Date', $now->format('D, d M Y H:i:s') . ' GMT'); } } } ?>
<?php
namespace {


use Library\Ext;
use Library\Lang;
use Library\OneDrive;
use Platforms\AliyunSC\AliyunSC;
use Platforms\Normal\Normal;
use Platforms\QCloudSCF\QCloudSCF;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

global $config;
$config = Config::$config;

/**
 * Normal cgi request entry
 * @return mixed
 */
function cgi_entry()
{
    global $config;
    $config['request'] = Normal::request();
    return Normal::response(
        handler($config['request'])
    );
}


/**
 * QCloud scf entry
 * @param array $event
 * @param array $context
 * @return array
 * @throws Exception
 */
function scf_entry($event, $context)
{
    global $config;
    $config['request'] = QCloudSCF::request($event, $context);

    return QCloudSCF::response(
        handler($config['request'])
    );
}

/**
 * Aliyun fc entry
 * @param array $request
 * @param array $context
 * @return array
 * @throws Exception
 */
function fc_entry($request, $context)
{
    global $config;
    $config['request'] = AliyunSC::request($request, $context);

    return AliyunSC::response(
        handler($config['request'])
    );
}


if (in_array(php_sapi_name(), ['apache2handler', 'cgi-fcgi', 'fpm-fcgi'])) {
    cgi_entry();
}

//////////////////////////////////////////////////////////////////////////////////////////


/**
 * core request handler
 * @param Request $request
 * @return array|Response
 */
function handler($request)
{
    global $config;

    if (!defined('APP_DEBUG')) define('APP_DEBUG', $config['debug']);
    Lang::init($request->cookies->get('language'));
    date_default_timezone_set(get_timezone($request->cookies->get('timezone')));

    $path = array_values(array_filter(
        explode('/', $request->getPathInfo()),
        function ($path) {
            return !empty($path);
        }
    ));

    $relative_path = join('/', $path);
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
                return message('此账号未找到', 'Error', null, 500);
            }
        }
        array_shift($path);
    } else {
        $account = $config['accounts'][0];
    }

    if (empty($account['path'])) {
        $account['path'] = '/';
    }


    $path = [
        'relative' => $relative_path,
        'absolute' => path_format($account['path'] . '/' . join('/', $path))
    ];
    $is_admin = $config['is_admin'] = !empty($config['admin_password']) && $request->cookies->get('admin_password') === $config['admin_password'];
    $is_image_path = $config['is_image_path'] = in_array($path['absolute'], $account['path_image']);

    try {
        $account['driver'] = new OneDrive($account['refresh_token'],
            !empty($account['provider']) ? $account['provider'] : 'MS',
            !empty($account['oauth']) ? $account['oauth'] : []);

        // get thumbnails for image file
        if ($request->query->has('thumbnails')) {
            return redirect($account['driver']->info($path['absolute'], true)['url']);
        }

        // install -> go to oauth
        if ($request->query->has('install') ||
            ($request->query->has('oauth_callback'))
            || empty($account['refresh_token'])) {

            if ($request->query->has('oauth_callback')) {
                if (($oauth = @json_decode($request->query->get('oauth_callback'), true)['oauth'])) {
                    $account['driver'] = new OneDrive(null, 'MS', $oauth);
                }
            }
            return install($request, $account);
        }

        // ajax request
        if ($request->isXmlHttpRequest()) {
            $response = false;
            if ($is_admin) {
                switch ($request->get('action')) {
                    case trans('Create'):
                        $file_path = path_format($path['absolute'] . '/' . $request->get('create_name'), true);
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
                        $file_path = path_format($path['absolute'] . '/' . $request->get('encrypt_folder') . '/' . $config['password_file'], true);
                        $response = $account['driver']->put($file_path, $request->get('encrypt_newpass'));
                        break;
                    case trans('Move'):
                        $file_path = path_format($path['absolute'] . '/' . $request->get('move_name'), true);
                        if ($request->get('move_folder') === '/../') {
                            if ($path['absolute'] == '/') {
                                $response = ['error' => 'cannot move'];
                                break;
                            }
                            $new_path = $path['absolute'] . '/../';

                        } else {
                            $new_path = $path['absolute'] . '/' . $request->get('move_folder');
                        }
                        $new_path = path_format($new_path . '/' . $request->get('move_name'), true);
                        $response = $account['driver']->move($file_path, $new_path);
                        break;
                    case trans('Rename'):
                        $file_path = path_format($path['absolute'] . '/' . $request->get('rename_oldname'), true);
                        $response = $account['driver']->move($file_path, path_format($path['absolute'] . '/' . $request->get('rename_newname')));
                        break;
                    case trans('Delete'):
                        $file_path = path_format($path['absolute'] . '/' . $request->get('delete_name'), true);
                        $response = $account['driver']->delete($file_path);
                        break;
                }
            }

            if ($is_admin || $is_image_path) {
                switch ($request->get('action')) {
                    case 'upload': // create a upload session
                        $file_path = path_format($path['absolute'] . '/' . $request->get('filename'), true);
                        $response = $account['driver']->uploadUrl($file_path);
                        break;
                }
            }

            if ($response)
                return response($response, !$response || isset($response['error']) ? 500 : 200);
        }

        // preview -> edit file
        if ($is_admin && $request->isMethod('POST')) {
            if ($request->query->has('preview')) {
                $account['driver']->put($path['absolute'], $request->get('content'));
            }
        }

        // default -> fetch files
        $files = $account['driver']->infos($path['absolute'], (int)$request->get('page', 1));

        if (!$request->query->has('preview')) {
            if (isset($files['@microsoft.graph.downloadUrl'])) {
                return redirect($files['@microsoft.graph.downloadUrl']);
            }
        }
        return render($account, $path, $files);
    } catch (Throwable $e) {
        @ob_get_clean();
        try {
            $error = ['error' => ['message' => $e->getMessage()]];
            if ($config['debug']) {
                $error['error']['message'] = $e->getMessage() . error_trace($e);
            }
            return render($account, $path, $error);
        } catch (Throwable $e) {
            @ob_get_clean();
            return message($e->getMessage(), 'Error', $config['debug'] ? error_trace($e) : null, 500);
        }
    }
}

/**
 * @param Request $request
 * @param [OneD] $account
 * @return Response
 */
function install($request, $account)
{
    $state = [];
    if ($request->query->has('oauth_callback')) {
        $callback = json_decode($request->query->get('oauth_callback'), true);
        if ($callback && !empty($callback['code'])) {
            $state = [
                'name' => $callback['name'],
                'refresh_token' => $account['driver']->get_refresh_token($callback['code'])
            ];
        }
    }
    @ob_start();
    // @formatter:off
    ?>
<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="content-type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <script type="text/javascript" src="//unpkg.zhimg.com/vue/dist/vue.min.js"></script>
    <script type="text/javascript" src="//unpkg.zhimg.com/muse-ui/dist/muse-ui.js"></script>
    <script type="text/javascript" src="//unpkg.zhimg.com/muse-ui-message/dist/muse-ui-message.js"></script>
    <link rel="stylesheet" type="text/css" href="//unpkg.zhimg.com/muse-ui/dist/muse-ui.css">
    <link rel="stylesheet" type="text/css" href="//unpkg.zhimg.com/muse-ui-message/dist/muse-ui-message.css">
    <!--suppress CssInvalidPropertyValue -->
    <style type="text/css">
        .container{max-width:800px;margin:24px auto}
        .title{text-align:center;margin:0;padding:20px 0 12px}
        .close-btn{float:right;width:32px;min-width:unset}
        .account-container{margin:4px 0;padding:4px 8px}
        .account-container input[type=text]{outline:0!important;width:280px;border:1px solid #e5e5e5;border-radius:2px;padding:2px 4px}
        .account-container .item{min-height:32px}
        .account-container .item>label{display:inline-block;width:64px}
        .config-dialog textarea{color:#fff;background:#222;font-size:12px;width:100%;outline:0!important;white-space:nowrap;overflow:scroll}

    </style>
    <title>OneDriveFly</title>
</head>
<body>
<div id="app" style="display: none">
    <mu-paper class="container" :z-depth="3">
        <h2 class="title">安装</h2>
        <mu-alert color="error" v-if="error" style="margin-bottom: 24px">
            {{error}}
        </mu-alert>
        <mu-form :model="form" class="mu-demo-form" label-position="top">
            <mu-form-item prop="input" label="站点名称">
                <mu-text-field v-model="form.name" placeholder="网站名称"></mu-text-field>
            </mu-form-item>
            <mu-form-item prop="switch" label="启用多账户">
                <mu-switch v-model="form.multi"></mu-switch>
            </mu-form-item>

            <mu-form-item prop="switch" label="OneDrive账户">
                <div style="width: 100%">
                    <mu-button small @click="addAccount" v-if="form.multi"
                               style="position: absolute; right: 0; top: 0;">增加
                    </mu-button>

                    <br>
                    <mu-paper v-for="(account, index) in form.accounts" class="account-container" :z-depth="1">
                        <mu-button v-if="form.multi && form.accounts.length !== 1"
                                   @click="form.accounts.splice(index, 1)" flat small color="red"
                                   class="close-btn">X
                        </mu-button>

                        <div>

                            <mu-form-item prop="select" label="版本">
                                <mu-select v-model="account.provider">
                                    <mu-option label="官方" value="MS"></mu-option>
                                    <mu-option label="官方-自定义" value="MSC"></mu-option>
                                    <mu-option label="世纪互联" value="CN"></mu-option>
                                </mu-select>
                            </mu-form-item>
                        </div>
                        <div class="item">
                            <label id="account_name">名称: </label>
                            <input id="account_name" type="text" v-model="account.name"
                                   placeholder="账户名称, 开启多账户时显示">

                        </div>
                        <div class="item">
                            <label for="account_path">路径: </label>
                            <input id="account_path" type="text" v-model="account.path" placeholder="需要列目录的路径">

                        </div>
                        <div class="item">
                            <label id="account_path_image">图床路径: </label>
                            <input id="account_path_image" type="text" v-model="account.path_image"
                                   placeholder="默认不启用">

                        </div>
                        <div v-if="account.provider === 'MSC'">
                            <a href="javascript:void(0);" @click="handleRegisterApp(account)"
                               style="display: inline-block;float: right">Register a app</a>
                            <div class="item">
                                <label for="redirect_uri">Uri:</label>
                                <input id="redirect_uri" type="text" v-model="account.oauth.redirect_uri"
                                       placeholder="redirect_uri">

                            </div>
                            <div class="item">
                                <label for="client_id">Id:</label>
                                <input id="client_id" type="text" v-model="account.oauth.client_id"
                                       placeholder="client_id">

                            </div>
                            <div class="item">
                                <label for="client_secret">Secret:</label>
                                <input id="client_secret" type="text" v-model="account.oauth.client_secret"
                                       placeholder="client_secret">

                            </div>
                        </div>
                        <div class="item">
                            <label>登录:</label>
                            <mu-badge v-if="account.refresh_token" content="已登录" color="green"></mu-badge>
                            <a v-else href="javascript:" @click="handleAuth(account)">点击登录</a>
                        </div>
                    </mu-paper>
                </div>
            </mu-form-item>

            <mu-form-item prop="input" label="代理">
                <mu-text-field v-model="form.proxy" placeholder="可为空, 程序内请求OneDrive使用的代理"></mu-text-field>
            </mu-form-item>
            <mu-form-item prop="input" label="目录密码文件">
                <mu-text-field v-model="form.password_file" placeholder="可为空，填写后此文件内容将作为当前目录密码"></mu-text-field>
            </mu-form-item>
            <mu-form-item prop="input" label="管理员密码">
                <mu-text-field v-model="form.admin_password" placeholder="可为空，填写后将可以在线管理文件"></mu-text-field>
            </mu-form-item>

        </mu-form>


        <div class="item" style="text-align: center; margin-top: 24px">
            <mu-button color="primary" @click="handleViewConfig">生成配置</mu-button>
        </div>

        <br>
        <br>
    </mu-paper>
</div>
<script type='text/javascript'>

    <?php
    echo 'var state=' . json_encode($state) . ';';
    ?>

    window.onload = function () {
        var insideOAuth = <?php echo json_encode([
            'MS' => OneDrive::APP_MS,
            'CN' => OneDrive::APP_MS_CN
        ]) ?>;

        Vue.use(MuseUI);
        Vue.use(MuseUIMessage);
        new Vue({
            el: '#app',
            data: function () {
                return {
                    error: null,
                    form: {
                        name: 'My Index',
                        multi: false,
                        accounts: [],
                        proxy: '',
                        password_file: '.password.txt',
                        admin_password: ''
                    },
                    config: null
                }
            },
            watch: {
                form: {
                    handler: function () {
                        this.config = JSON.stringify(this.form);
                        localStorage.setItem('config', this.config);
                    },
                    deep: true
                }
            },
            mounted: function () {
                var config = localStorage.getItem('config');
                if (config) {
                    config = JSON.parse(config);
                    if (state.refresh_token) {
                        if (state.refresh_token.constructor === String) {
                            for (var i = 0; i < config.accounts.length; i++) {
                                if (config.accounts[i].name === state.name) {
                                    config.accounts[i].refresh_token = state.refresh_token;
                                    break;
                                }
                            }
                        } else if (state.refresh_token.constructor === Object) {
                            this.error = state.refresh_token.error_description ? state.refresh_token.error_description : state.refresh_token;
                        }
                    }
                    this.form = config;
                } else {
                    this.addAccount();
                }
                document.getElementById('app').style.display = '';
            },
            methods: {
                handleRegisterApp: function (account) {
                    var lang = 'zh-cn';
                    var ru = 'https://developer.microsoft.com/' + lang + '/graph/quick-start?appID=_appId_&appName=_appName_&redirectUrl=' + encodeURIComponent(account.oauth.redirect_uri) + '&platform=option-php';
                    var deepLink = '/quickstart/graphIO?publicClientSupport=false&appName=one_scf&redirectUrl=' + encodeURIComponent(account.oauth.redirect_uri) + '&allowImplicitFlow=false&ru=' + encodeURIComponent(ru);
                    var url = 'https://apps.dev.microsoft.com/?deepLink=' + encodeURIComponent(deepLink);
                    window.open(url, '_blank');
                },
                addAccount: function () {
                    this.form.accounts.push({
                        name: 'disk_' + (this.form.accounts.length + 1),
                        provider: 'MS',
                        path: '/',
                        path_image: '',
                        refresh_token: '',
                        oauth: {
                            redirect_uri: 'https://onedrivefly.github.io',
                            client_id: '',
                            client_secret: ''
                        }
                    });
                },
                handleAuth: function (account) {
                    if (!account.name) {
                        return alert('请填写账户名称');
                    }
                    var oauth;
                    switch (account.provider) {
                        default:
                        case 'MS':
                            oauth = insideOAuth['MS'];
                            break;
                        case 'CN':
                            oauth = insideOAuth['CN'];
                            break;
                    }

                    if (account.provider === 'MSC') {
                        for (var key in account.oauth) {
                            if (account.oauth.hasOwnProperty(key)) {
                                if (oauth.hasOwnProperty(key)) {
                                    oauth[key] = account.oauth[key]
                                }
                            }
                        }
                    }

                    var return_url = location.protocol + "//" + location.host + location.pathname;
                    var state = {
                        name: account.name,
                        return_url: return_url,
                        oauth: oauth
                    };
                    location.href = oauth['oauth_url'] + 'authorize?scope=' + oauth['scope'] +
                        '&response_type=code&client_id=' + oauth['client_id'] +
                        '&redirect_uri=' + oauth['redirect_uri'] + '&state=' + encodeURIComponent(JSON.stringify(state));
                },
                handleViewConfig: function () {
                    if (!this.form.accounts.length) {
                        this.$alert('请至少添加一个账号');
                        return;
                    }
                    for (var i = 0; i < this.form.accounts.length; i++) {
                        if (!this.form.accounts[i].refresh_token) {
                            this.$alert('第' + i + '个账户未登录，请登录后重试');
                            return;
                        }
                    }


                    var config = "    public static $config = [\n" +
                        "        'name' => '" + this.form.name + "',\n" +
                        "        'multi' => " + (this.form.multi ? 1 : 0) + ",\n" +
                        "        'accounts' => [\n";


                    this.form.accounts.forEach(function (account) {
                        config += "            [\n" +
                            "                'name' => '" + account.name + "',\n" +
                            "                'provider' => '" + account.provider + "',\n" +
                            "                'path' => '" + account.path + "',\n" +
                            "                'path_image' => ['" + account.path_image + "'],\n";
                        if (account.provider === 'MSC') {
                            config += "                'oauth' => [\n" +
                                "                    'redirect_uri' => '" + account.oauth.redirect_uri + "',\n" +
                                "                    'client_id' => '" + account.oauth.client_id + "',\n" +
                                "                    'client_secret' => '" + account.oauth.client_secret + "'\n" +
                                "                ],\n";
                        }
                        config += "                'refresh_token' => '" + account.refresh_token + "'\n" +
                            "            ]\n" +
                            "        ],\n";
                    });
                    config +=
                        "        'debug' => false,\n" +
                        "        'proxy' => '" + this.form.proxy + "',\n" +
                        "        'password_file' => '" + this.form.password_file + "',\n" +
                        "        'admin_password' => '" + this.form.admin_password + "',\n" +
                        "    ];";
                    this.$alert(null, '配置已生成', {
                        content: function (h) {
                            return h('textarea', {
                                attrs: {
                                    rows: 24
                                }
                            }, [config]);
                        },
                        width: '90%',
                        className: 'config-dialog'
                    })
                }
            }
        })
    }
</script>
</body>
</html>
    <?php
    // @formatter:on
    $html = ob_get_clean();
    return response($html);
}

/**
 * @param string $message
 * @param string $title
 * @param string|null $description
 * @param int $status
 * @param array $headers
 * @return Response
 */
function message($message, $title, $description = null, $status = 200, $headers = [])
{
    // @formatter:off
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
        body,html{background-color:#fff;color:#636b6f;font-family:'Microsoft Yahei UI','PingFang SC',Raleway,sans-serif;font-weight:100;height:100vh;margin:0}
        .full-height{height:100vh}
        .flex-center{display:flex;justify-content:center}
        .position-ref{position:relative}
        .content{text-align:center;padding-top:30vh}
        .title{font-size:36px;padding:20px}
    </style>
</head>
<body>
<div class="flex-center position-ref full-height">
    <div class="content">
        <div class="title"><?php echo $message; ?></div>
        <?php echo $description ? $description : ''; ?>
    </div>
</div>
</body>
</html>
    <?php
    // @formatter:on
    $html = ob_get_clean();
    return response($html, $status, $headers);
}


/**
 * @return Request
 */
function request()
{
    global $config;
    return $config['request'];
}

function response($content = '', $status = 200, array $headers = [])
{
    $headers = array_merge(['content-type' => 'text/html'], $headers);
    if (is_array($content)) $content = json_encode($content);
    return new Response(
        $content,
        $status,
        $headers
    );
}

function redirect($to = null, $status = 302, $headers = [])
{
    return response('redirecting', $status, array_merge($headers, [
        'location' => $to
    ]));
}

function trans($key = null, $replace = array())
{
    return Lang::get($key, $replace);
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
    $is_admin = $config['is_admin'] === true;
    $is_image_path = $config['is_image_path'] === true;
    $base_url = $request->getBaseUrl();
    if ($base_url == '') $base_url = '/';
    $status_code = 200;
    $is_video = false;
    $readme = false;
    @ob_start();
    // @formatter:off
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
            body{font-family:'Microsoft Yahei UI','PingFang TC','Helvetica Neue',Helvetica,Arial,sans-serif;font-size:14px;line-height:1em;background-color:#f7f7f9;color:#000}
            a{color:#24292e;cursor:pointer;text-decoration:none}
            a:hover{color:#24292e}
            .select-language{position:absolute;right:5px}
            .title{text-align:center;margin:2rem 0;letter-spacing:2px}
            .title a{color:#333;text-decoration:none}
            .list-wrapper{width:80%;margin:0 auto 40px;position:relative;box-shadow: 0 0 12px 0 rgba(0,0,0,.1);}
            .list-container{position:relative;overflow:hidden;border-radius:0}
            .list-header-container{position:relative}
            .list-header-container a.back-link{color:#000;display:inline-block;position:absolute;font-size:16px;margin:20px 10px;padding:10px 10px;vertical-align:middle;text-decoration:none}
            .list-container,.list-header-container,.list-wrapper,a.back-link:hover,body{color:#24292e}
            .list-header-container .table-header{margin:0;border:0 none;padding:30px 60px;text-align:left;font-weight:400;color:#000;background-color:#f7f7f9}
            .list-body-container{position:relative;left:0;overflow-x:hidden;overflow-y:auto;box-sizing:border-box;background:#fff}
            .list-table{width:100%;padding:20px;border-spacing:0}
            .list-table tr{height:40px}
            .list-table tr[data-to]:hover{background:#f1f1f1}
            .list-table tr:first-child{background:#fff}
            .list-table td,.list-table th{padding:0 10px;text-align:left;cursor:pointer}
            .list-table .size,.list-table .updated_at{text-align:right}
            .list-table .file ion-icon{font-size:15px;margin-right:5px;vertical-align:bottom}
            .mask{position:absolute;left:0;top:0;width:100%;height:100%;background-color:#000;opacity:.5;z-index:2}
            <?php if ($is_admin) { ?>
            .operate{display:inline-table;margin:0;list-style:none}
            .operate ul{position:absolute;display:none;background:#fffaaa;border:0 #f7f7f7 solid;border-radius:5px;margin:-7px 0 0 0;padding:0 7px;color:#205d67;z-index:1}
            .operate:hover ul{position:absolute;display:inline-table}
            .operate ul li{padding:7px;list-style:none;display:inline-table}
            <?php } ?>
            .operate-model{position:absolute;border:1px #ccc;background-color:#ffc;z-index:2}
            .operate-model div{margin:16px}
            .closeModel{position:absolute;right:3px;top:3px}
            .readme{padding:8px;background-color:#fff}
            #readme{padding:20px;text-align:left}
            @media only screen and (max-width:480px){.title{margin-bottom:24px}
                .list-wrapper{width:95%;margin-bottom:24px}
                .list-table{padding:8px}
                .list-table td,.list-table th{padding:0 10px;text-align:left;white-space:nowrap;overflow:auto;max-width:80px}
            }
        </style>
        <script type="text/javascript">
            function setCookie(name,value,expire){if(expire!==undefined){var expTime=new Date();expTime.setTime(expTime.getTime()+expire);document.cookie=name+'='+encodeURI(value)+'; expires='+expTime.toUTCString()+'; path=/'}else{document.cookie=name+'='+encodeURI(value)+'; path=/'}}
            function getCookie(name){var parts=('; '+document.cookie).split('; '+name+'=');if(parts.length>=2)return parts.pop().split(';').shift()}
            function loadResources(type,src,callback){var script=document.createElement(type);var loaded=false;if(typeof callback==='function'){script.onload=script.onreadystatechange=function(){if(!loaded&&(!script.readyState||/loaded|complete/.test(script.readyState))){script.onload=script.onreadystatechange=null;loaded=true;callback()}}}if(type==='link'){script.href=src;script.rel='stylesheet'}else{script.src=src}document.getElementsByTagName('head')[0].appendChild(script)}String.prototype.between=function(before,after){var index1=this.indexOf(before);var index2=this.indexOf(after,index1+1);if(index1===-1||index2===-1)return null;return this.substring(index1+before.length,index2)};(function timezone(){if(!getCookie('timezone')){var now=new Date();var timezone=parseInt(0-now.getTimezoneOffset()/60);setCookie('timezone',timezone,7*24*3600*1000);if(timezone!==8){alert('Your timezone is '+timezone+', reload local timezone.');location.reload()}}})();
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
            echo '<img src="' . $files['@microsoft.graph.downloadUrl'] . '" alt="' . substr($path['relative'], strrpos($path['relative'], '/')) . '" onload="if(this.offsetWidth>document.getElementById(\'url\').offsetWidth) this.style.width=\'100%\';" />';
        } elseif (in_array($ext, Ext::VIDEO)) {
            //echo '<video src="' . $files['@microsoft.graph.downloadUrl'] . '" controls="controls" style="width: 100%"></video>';
            $is_video = true;
            echo '<div id="video-a0" data-url="' . $files['@microsoft.graph.downloadUrl'] . '"></div>';
        } elseif (in_array($ext, Ext::MUSIC)) {
            echo '<audio src="' . $files['@microsoft.graph.downloadUrl'] . '" controls="controls" style="width: 100%"></audio>';
        } elseif (in_array($ext, ['pdf'])) {
            echo '<embed src="' . $files['@microsoft.graph.downloadUrl'] . '" type="application/pdf" width="100%" height=800px">';
        } elseif (in_array($ext, Ext::OFFICE)) {
            echo '<iframe id="office-a" src="https://view.officeapps.live.com/op/view.aspx?src=' . urlencode($files['@microsoft.graph.downloadUrl']) . '" style="width: 100%;height: 800px; border: 0"></iframe>';
        } elseif (in_array($ext, Ext::TXT)) {
            $txt_content = htmlspecialchars(curl($files['@microsoft.graph.downloadUrl']));
            ?>
            <div id="txt">
                <?php if ($is_admin) { ?>
                <form id="txt-form" action="" method="POST">
                    <a onclick="previewEnableEdit(this);"
                       id="txt-editbutton"><?php echo trans('ClickToEdit'); ?></a>
                    <a id="txt-save"
                       style="display:none"><?php echo trans('Save'); ?></a>
                    <?php } ?>
                    <label for="txt-a"></label>
                    <textarea id="txt-a" name="content" readonly
                              style="width: 100%; margin-top: 2px;" <?php if ($is_admin) echo 'onchange="document.getElementById(\'txt-save\').onclick=function(){document.getElementById(\'txt-form\').submit();}"'; ?> ><?php echo $txt_content; ?></textarea>
                    <?php if ($is_admin) echo '</form>'; ?>
            </div>
        <?php } elseif (in_array($ext, ['md'])) {
            echo '<div class="markdown-body" id="readme"><textarea id="readme-md" style="display:none;">' . curl($files['@microsoft.graph.downloadUrl']) . '</textarea></div>';
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
               href="<?php echo htmlspecialchars(path_format($base_url . '/' . $path['relative'] . '/' . $file['name'] . '/')); ?>"><?php echo htmlspecialchars(urldecode($file['name'])); ?></a>
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
                    @ob_get_clean();
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
               href="<?php echo htmlspecialchars(path_format($base_url . '/' . $path['relative'] . '/' . $file['name'])); ?>?preview"
               target=_blank><?php echo htmlspecialchars(urldecode($file['name'])); ?></a>
            <a href="<?php echo htmlspecialchars(path_format($base_url . '/' . $path['relative'] . '/' . $file['name'])); ?>">
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

<?php
    if ($files['folder']['childCount'] > $files['folder']['perPage']) {
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
?>

<?php
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
            </div><!-- list-body-container end -->
        </div><!-- list-container end-->
    </div><!-- list-wrapper end-->
    
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
                        } // end of if-error
                    }
                    else // end of if-password
                    {
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
    <span style="color: #f7f7f9"><?php echo date("Y-m-d H:i:s") . " " . trans('Week.' . date('w')) . ' ' . $request->getClientIp(); ?></span>
    </body>
<?php if (isset($files['folder']) && $is_image_path && !$is_admin) { ?>
    <script type="text/javascript" src="//cdn.bootcss.com/spark-md5/3.0.0/spark-md5.min.js"></script>
<?php } ?>
    <script type="text/javascript">
        var root = '<?php echo $base_url; ?>';

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
            loadResources('script','//unpkg.zhimg.com/marked@0.6.2/marked.min.js', function() {
                $readme.innerHTML = marked(document.getElementById('readme-md').innerText)
            });
            loadResources('link','//unpkg.zhimg.com/github-markdown-css@3.0.1/github-markdown.css');
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

            loadResources('link', host + '/dplayer/1.25.0/DPlayer.min.css', callback);
            loadResources('script', host + '/dplayer/1.25.0/DPlayer.min.js', callback);
            loadResources('script', host + '/hls.js/0.12.4/hls.light.min.js', callback);
            loadResources('script', host + '/flv.js/1.5.0/flv.min.js', callback);
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
            var now = new Date().getTime();
            var index = 0;
            getUploadUrl(index);

            function getUploadUrl(index) {
                var file = files[index];
                var elementTag = now + '_' + index;
                var tr1 = document.createElement('tr');
                table1.appendChild(tr1);
                tr1.setAttribute('data-to', '1');
                var td1 = document.createElement('td');
                tr1.appendChild(td1);
                td1.setAttribute('style', 'width:30%');
                td1.setAttribute('id', 'upload_td1_' + elementTag);
                td1.innerHTML = file.name + '<br>' + sizeFormat(file.size);
                var tip = document.createElement('td');
                tr1.appendChild(tip);
                tip.setAttribute('id', 'upload_td2_' + elementTag);
                tip.innerHTML = '<?php echo trans('GetUploadLink'); ?> ...';
                if (file.size > 100 * 1024 * 1024 * 1024) {
                    tip.innerHTML = '<font color="red"><?php echo trans('UpFileTooLarge'); ?></font>';
                    showUploadBtn();
                    return;
                }

                function createNewUploadUrl() {
                    var ajax = new XMLHttpRequest();
                    ajax.open('POST', '');
                    ajax.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                    ajax.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
                    ajax.send('action=upload&filename=' + encodeURIComponent(file.name) + '&filesize=' + file.size + '&lastModified=' + file.lastModified);
                    ajax.onload = function () {
                        tip.innerHTML = '<span style="color: red">' + ajax.responseText + '</span>';
                        var res = JSON.parse(ajax.responseText);
                        if (res && res['uploadUrl']) {
                            localStorage.setItem('_tmp_uploadUrl_' + file.name, res['uploadUrl']);
                            tip.innerHTML = '<?php echo trans('UploadStart'); ?> ...';
                            upload(file, res['uploadUrl'], elementTag, 0);
                        } else {
                            tip.innerHTML = '<span style="color: red">' + ajax.responseText + '</span><br>';
                            showUploadBtn();
                        }
                        if (index < files.length - 1) {
                            index++;
                            getUploadUrl(index);
                        }
                    }
                }

                var uploadUrl = localStorage.getItem('_tmp_uploadUrl_' + file.name);
                if (uploadUrl) {
                    var xhr = new XMLHttpRequest();
                    xhr.open('GET', uploadUrl); // 先获取一下上次上次到哪里了
                    xhr.send();
                    xhr.onload = function () {
                        if (xhr.status === 200) {
                            var res = JSON.parse(xhr.responseText);
                            var positionLast = Number(res['nextExpectedRanges'][0].slice(0, res['nextExpectedRanges'][0].indexOf('-')));
                            tip.innerHTML = '<?php echo trans('UploadStart'); ?> ...';
                            upload(file, uploadUrl, elementTag, positionLast);
                        } else if (xhr.status === 404) {
                            // upload session does not esists, create a new one
                            createNewUploadUrl();
                        } else {
                            tip.innerHTML = '<span style="color:red;">' + xhr.responseText + '</span>';
                            showUploadBtn();
                        }
                    };
                } else {
                    createNewUploadUrl();
                }
            }
        }


        function upload(file, url, elementTag, uploadOffset) {
            var tip1 = document.getElementById('upload_td1_' + elementTag);
            var tip2 = document.getElementById('upload_td2_' + elementTag);
            var reader = new FileReader();
            var tipStart = '';
            var tipMiddle = '';

            var timeStart = new Date();
            var timeEnd;
            var positionLast = 0;

            if (!file) {
                return;
            }

            var sizeUploaded = 0;
            var sizeTotal = file.size;

            positionLast = uploadOffset;
            <?php if ($is_admin) { ?>
            sizeUploaded = positionLast;
            <?php } ?>
            if (positionLast === 0) {
                tipStart = '<?php echo trans('UploadStartAt'); ?>:' + timeStart.toLocaleString() + '<br>';
            } else {
                tipStart = '<?php echo trans('LastUpload'); ?>' + sizeFormat(positionLast) + '<br><?php
                    echo trans('ThisTime') . trans('UploadStartAt');
                    ?>:' + timeStart.toLocaleString() + '<br>';
            }
            var sizePerChunk = 5 * 1024 * 1024; // chunk size, max 60M. 每小块上传大小，最大60M，微软建议10M
            if (sizeTotal > 200 * 1024 * 1024) sizePerChunk = 10 * 1024 * 1024;

            function readBlob(start) {
                var end = start + sizePerChunk;
                var blob = file.slice(start, end);
                reader.readAsArrayBuffer(blob);
            }

            readBlob(sizeUploaded);
            <?php if (!$is_admin) { ?>
            var spark = new SparkMD5.ArrayBuffer();
            <?php } ?>
            reader.onload = function (reader) {
                var binary = this.result;
                <?php if (!$is_admin) { ?>
                spark.append(binary);
                if (sizeUploaded < positionLast) {
                    sizeUploaded += sizePerChunk;
                    readBlob(sizeUploaded);
                    return;
                }
                <?php } ?>
                var xhr2 = new XMLHttpRequest();
                xhr2.open('PUT', url, true);
                var positionEnd = sizeUploaded + reader.loaded - 1;
                var thisNow = new Date();
                xhr2.setRequestHeader('Content-Range', 'bytes ' + sizeUploaded + '-' + positionEnd + '/' + sizeTotal);
                xhr2.upload.onprogress = function (xhr2) {
                    if (xhr2.lengthComputable) {
                        var now = new Date();
                        var speed = xhr2.loaded * 1000 / (now.getTime() - thisNow.getTime());
                        var remainSecond = (sizeTotal - sizeUploaded - xhr2.loaded) / speed;
                        tip2.innerHTML = tipStart + '<?php echo trans('Upload'); ?> ' + sizeFormat(sizeUploaded + xhr2.loaded) +
                            ' / ' + sizeFormat(sizeTotal) + ' = ' + ((sizeUploaded + xhr2.loaded) * 100 / sizeTotal).toFixed(2) +
                            '% <?php echo trans('AverageSpeed'); ?>:' + sizeFormat((sizeUploaded + xhr2.loaded - positionLast) * 1000 / (now.getTime() - timeStart.getTime())) +
                            '/s<br><?php echo trans('CurrentSpeed'); ?> ' + sizeFormat(speed) + '/s <?php echo trans('Expect'); ?> ' +
                            remainSecond.toFixed(1) + 's';
                    }
                };
                xhr2.onload = function () {
                    if (xhr2.status < 500) {
                        var response = JSON.parse(xhr2.responseText);
                        if (response['size'] > 0) {
                            // contain size, upload finish. 有size说明是最终返回，上传结束
                            localStorage.removeItem('_tmp_uploadUrl_' + file.name);
                            timeEnd = new Date();
                            tipMiddle = '<?php echo trans('EndAt'); ?>:' + timeEnd.toLocaleString() + '<br>';
                            if (positionLast === 0) {
                                tipMiddle += '<?php echo trans('AverageSpeed'); ?>:' + sizeFormat(sizeTotal * 1000 / (timeEnd.getTime() - timeStart.getTime())) + '/s<br>';
                            } else {
                                tipMiddle += '<?php echo trans('ThisTime') . trans('AverageSpeed'); ?>:' + sizeFormat((sizeTotal - positionLast) * 1000 / (timeEnd.getTime() - timeStart.getTime())) + '/s<br>';
                            }
                            tip1.innerHTML = '<font color="green">' + tip1.innerHTML + '<br><?php echo trans('UploadComplete'); ?></font>';
                            tip2.innerHTML = tipStart + tipMiddle;
                            showUploadBtn();
                            <?php if ($is_admin) { ?>
                            addFileToList(response);
                            <?php } ?>
                        } else {
                            if (!response['nextExpectedRanges']) {
                                tip2.innerHTML = '<span style="color: red">' + xhr2.responseText + '</span><br>';
                            } else {
                                var a = response['nextExpectedRanges'][0];
                                sizeUploaded = Number(a.slice(0, a.indexOf("-")));
                                readBlob(sizeUploaded);
                            }
                        }
                    } else readBlob(sizeUploaded);
                };
                xhr2.send(binary);
            }

        }

        <?php
        }


        if ($is_admin) { // admin login. 管理登录后 ?>

        function logout() {
            setCookie('admin_password', null, -1);
            location.reload();
        }

        function previewEnableEdit(obj) {
            document.getElementById('txt-a').readOnly = !document.getElementById('txt-a').readOnly;
            obj.innerHTML = (obj.innerHTML === '<?php echo trans('CancelEdit'); ?>') ? '<?php echo trans('ClickToEdit'); ?>' : '<?php echo trans('CancelEdit'); ?>';
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
            a1.innerText = decodeURIComponent(obj.name);
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
    // @formatter:on
    $html = ob_get_clean();
    return response($html, $status_code);
}


}