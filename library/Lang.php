<?php


namespace Library;


class Lang
{
    private static $language;

    public static function init($language)
    {
        $supported = ['zh-CN', 'en-US'];
        if (in_array($language, $supported)) {
            self::$language = $language;
        } else {
            self::$language = $supported[0];
        }
    }

    public static function language()
    {
        return self::$language;
    }

    public static function all()
    {
        global $LANG;
        return $LANG;
    }

    public static function get($key = null, $replace = array())
    {
        global $LANG;

        if ($key == null) {
            return self::all();
        }

        $string = $LANG;
        $key = explode('.', $key);
        $i = 0;
        while ($i < count($key)) {
            $string = $string[$key[$i++]];
        }
        $string = $string[self::$language];

        foreach ($replace as $var => $val) {
            $string = str_replace(":$var", $val, $string);
        }
        return $string;
    }
}

global $LANG;

$LANG = [
    'languages' => [
        'en-US' => 'English',
        'zh-CN' => '中文',
    ],
    'Week' => [
        '0' => [
            'en-US' => 'Sunday',
            'zh-CN' => '星期日',
        ],
        '1' => [
            'en-US' => 'Monday',
            'zh-CN' => '星期一',
        ],
        '2' => [
            'en-US' => 'Tuesday',
            'zh-CN' => '星期二',
        ],
        '3' => [
            'en-US' => 'Wednesday',
            'zh-CN' => '星期三',
        ],
        '4' => [
            'en-US' => 'Thursday',
            'zh-CN' => '星期四',
        ],
        '5' => [
            'en-US' => 'Friday',
            'zh-CN' => '星期五',
        ],
        '6' => [
            'en-US' => 'Saturday',
            'zh-CN' => '星期六',
        ],
    ],
    'EnvironmentsDescription' => [
        'admin' => [
            'en-US' => 'The admin password, Login button will not show when empty',
            'zh-CN' => '管理密码，不添加时不显示登录页面且无法登录。',
        ],
        'adminloginpage' => [
            'en-US' => 'if set, the Login button will not display, and the login page no longer \'?admin\', it is \'?{this value}\'.',
            'zh-CN' => '如果设置，登录按钮及页面隐藏。管理登录的页面不再是\'?admin\'，而是\'?此设置的值\'。',
        ],
        'domain_path' => [
            'en-US' => 'more custom domain, format is a1.com:/dirto/path1|b2.com:/path2',
            'zh-CN' => '使用多个自定义域名时，指定每个域名看到的目录。格式为a1.com:/dirto/path1|b1.com:/path2，比private_path优先。',
        ],
        'imgup_path' => [
            'en-US' => 'Set guest upload dir, before set this, the files in this dir will show as normal.',
            'zh-CN' => '设置图床路径，不设置这个值时该目录内容会正常列文件出来，设置后只有上传界面，不显示其中文件（登录后显示）。',
        ],
        'passfile' => [
            'en-US' => 'The password of dir will save in this file.',
            'zh-CN' => '自定义密码文件的名字，可以是\'pppppp\'，也可以是\'aaaa.txt\'等等；列目录时不会显示，只有知道密码才能查看或下载此文件。密码是这个文件的内容，可以空格、可以中文；',
        ],
        'private_path' => [
            'en-US' => 'Show this Onedrive dir when through custom domain, default is \'/\'.',
            'zh-CN' => '使用自定义域名访问时，显示网盘文件的路径，不设置时默认为根目录。',
        ],
        'public_path' => [
            'en-US' => 'Show this Onedrive dir when through the long url of API Gateway; public show files less than private.',
            'zh-CN' => '使用API长链接访问时，显示网盘文件的路径，不设置时默认为根目录；不能是private_path的上级（public看到的不能比private多，要么看到的就不一样）。',
        ],
        'sitename' => [
            'en-US' => 'sitename',
            'zh-CN' => '网站的名称',
        ],
        'language' => [
            'en-US' => 'en or zh-CN',
            'zh-CN' => '目前en 或 zh-CN',
        ],
        'SecretId' => [
            'en-US' => 'the SecretId of tencent cloud',
            'zh-CN' => '腾讯云API的Id',
        ],
        'SecretKey' => [
            'en-US' => 'the SecretKey of tencent cloud',
            'zh-CN' => '腾讯云API的Key',
        ],
        'Region' => [
            'en-US' => 'the Region of SCF',
            'zh-CN' => 'SCF程序所在地区',
        ],
        'Onedrive_ver' => [
            'en-US' => 'Onedrive version',
            'zh-CN' => 'Onedrive版本',
        ],
    ],
    'SetSecretsFirst' => [
        'en-US' => 'Set SecretId & SecretKey in Environments first! Then reflesh.',
        'zh-CN' => '先在环境变量设置SecretId和SecretKey！再刷新。',
    ],
    'RefleshtoLogin' => [
        'en-US' => '<font color="red">Reflesh</font> and login.',
        'zh-CN' => '请<font color="red">刷新</font>页面后重新登录',
    ],
    'AdminLogin' => [
        'en-US' => 'Admin Login',
        'zh-CN' => '管理登录',
    ],
    'LoginSuccess' => [
        'en-US' => 'Login Success!',
        'zh-CN' => '登录成功，正在跳转',
    ],
    'InputPassword' => [
        'en-US' => 'Input Password',
        'zh-CN' => '输入密码',
    ],
    'Login' => [
        'en-US' => 'Login',
        'zh-CN' => '登录',
    ],
    'Encrypt' => [
        'en-US' => 'Encrypt',
        'zh-CN' => '加密',
    ],
    'SetpassfileBfEncrypt' => [
        'en-US' => 'Your should set \'password_file\' before encrypt',
        'zh-CN' => '先在设置password_file才能加密',
    ],
    'updateProgram' => [
        'en-US' => 'Update Program',
        'zh-CN' => '一键更新',
    ],
    'UpdateSuccess' => [
        'en-US' => 'Program update Success!',
        'zh-CN' => '程序升级成功！',
    ],
    'Setup' => [
        'en-US' => 'Setup',
        'zh-CN' => '设置',
    ],
    'NotNeedUpdate' => [
        'en-US' => 'Not Need Update',
        'zh-CN' => '不需要更新',
    ],
    'Back' => [
        'en-US' => 'Back',
        'zh-CN' => '返回',
    ],
    'Home' => [
        'en-US' => 'Home',
        'zh-CN' => '首页',
    ],
    'NeedUpdate' => [
        'en-US' => 'Program can update<br>Click setup in Operate at top.',
        'zh-CN' => '可以升级程序<br>在上方管理菜单中<br>进入设置页面升级',
    ],
    'Operate' => [
        'en-US' => 'Operate',
        'zh-CN' => '管理',
    ],
    'Logout' => [
        'en-US' => 'Logout',
        'zh-CN' => '登出',
    ],
    'Create' => [
        'en-US' => 'Create',
        'zh-CN' => '新建',
    ],
    'Download' => [
        'en-US' => 'download',
        'zh-CN' => '下载',
    ],
    'ClickToEdit' => [
        'en-US' => 'Click to edit',
        'zh-CN' => '点击后编辑',
    ],
    'Save' => [
        'en-US' => 'Save',
        'zh-CN' => '保存',
    ],
    'FileNotSupport' => [
        'en-US' => 'File not support preview.',
        'zh-CN' => '文件格式不支持预览',
    ],
    'File' => [
        'en-US' => 'File',
        'zh-CN' => '文件',
    ],
    'ShowThumbnails' => [
        'en-US' => 'Thumbnails',
        'zh-CN' => '图片缩略',
    ],
    'EditTime' => [
        'en-US' => 'EditTime',
        'zh-CN' => '修改时间',
    ],
    'Size' => [
        'en-US' => 'Size',
        'zh-CN' => '大小',
    ],
    'Rename' => [
        'en-US' => 'Rename',
        'zh-CN' => '重命名',
    ],
    'Move' => [
        'en-US' => 'Move',
        'zh-CN' => '移动',
    ],
    'Delete' => [
        'en-US' => 'Delete',
        'zh-CN' => '删除',
    ],
    'PrePage' => [
        'en-US' => 'PrePage',
        'zh-CN' => '上一页',
    ],
    'NextPage' => [
        'en-US' => 'NextPage',
        'zh-CN' => '下一页',
    ],
    'Upload' => [
        'en-US' => 'Upload',
        'zh-CN' => '上传',
    ],
    'Submit' => [
        'en-US' => 'Submit',
        'zh-CN' => '确认',
    ],
    'Close' => [
        'en-US' => 'Close',
        'zh-CN' => '关闭',
    ],
    'InputPasswordUWant' => [
        'en-US' => 'Input Password you Want',
        'zh-CN' => '输入想要设置的密码',
    ],
    'ParentDir' => [
        'en-US' => 'Parent Dir',
        'zh-CN' => '上一级目录',
    ],
    'Folder' => [
        'en-US' => 'Folder',
        'zh-CN' => '文件夹',
    ],
    'Name' => [
        'en-US' => 'Name',
        'zh-CN' => '名称',
    ],
    'Content' => [
        'en-US' => 'Content',
        'zh-CN' => '内容',
    ],
    'CancelEdit' => [
        'en-US' => 'Cancel Edit',
        'zh-CN' => '取消编辑',
    ],
    'GetFileNameFail' => [
        'en-US' => 'Fail to Get File Name!',
        'zh-CN' => '获取文件名失败！',
    ],
    'GetUploadLink' => [
        'en-US' => 'Get Upload Link',
        'zh-CN' => '获取上传链接',
    ],
    'UpFileTooLarge' => [
        'en-US' => 'The File is too Large!',
        'zh-CN' => '大于15G，终止上传。',
    ],
    'UploadStart' => [
        'en-US' => 'Upload Start',
        'zh-CN' => '开始上传',
    ],
    'UploadStartAt' => [
        'en-US' => 'Start At',
        'zh-CN' => '开始于',
    ],
    'ThisTime' => [
        'en-US' => 'This Time',
        'zh-CN' => '本次',
    ],
    'LastUpload' => [
        'en-US' => 'Last time Upload',
        'zh-CN' => '上次上传',
    ],
    'AverageSpeed' => [
        'en-US' => 'AverageSpeed',
        'zh-CN' => '平均速度',
    ],
    'CurrentSpeed' => [
        'en-US' => 'CurrentSpeed',
        'zh-CN' => '即时速度',
    ],
    'Expect' => [
        'en-US' => 'Expect',
        'zh-CN' => '预计还要',
    ],
    'EndAt' => [
        'en-US' => 'End At',
        'zh-CN' => '结束于',
    ],
    'UploadErrorUpAgain' => [
        'en-US' => 'Maybe error, do upload again.',
        'zh-CN' => '可能出错，重新上传。',
    ],
    'UploadComplete' => [
        'en-US' => 'Upload Complete',
        'zh-CN' => '上传完成',
    ],
    'UploadFail23' => [
        'en-US' => 'Upload Fail, contain #.',
        'zh-CN' => '目录或文件名含有#，上传失败。',
    ],
    'defaultSitename' => [
        'en-US' => 'Set sitename in Environments',
        'zh-CN' => '请在环境变量添加sitename',
    ],
    'MayinEnv' => [
        'en-US' => 'The \'Onedrive_ver\' may in Environments',
        'zh-CN' => 'Onedrive_ver应该已经写入环境变量',
    ],
    'Wait' => [
        'en-US' => 'Wait',
        'zh-CN' => '稍等',
    ],
    'WaitJumpIndex' => [
        'en-US' => 'Wait 5s jump to Home page',
        'zh-CN' => '等5s跳到首页',
    ],
    'JumptoOffice' => [
        'en-US' => 'Login Office and Get a refresh_token',
        'zh-CN' => '跳转到Office，登录获取refresh_token',
    ],
    'OndriveVerMS' => [
        'en-US' => 'default(Onedrive, Onedrive for business)',
        'zh-CN' => '默认（支持商业版与个人版）',
    ],
    'OndriveVerCN' => [
        'en-US' => 'Onedrive in China',
        'zh-CN' => '世纪互联版',
    ],
    'OndriveVerMSC' => [
        'en-US' => 'default but use customer app id & secret',
        'zh-CN' => '国际版，自己申请应用ID与机密',
    ],
    'GetSecretIDandKEY' => [
        'en-US' => 'Get customer app id & secret',
        'zh-CN' => '申请应用ID与机密',
    ],
    'Reflesh' => [
        'en-US' => 'Reflesh',
        'zh-CN' => '刷新',
    ],
    'SelectLanguage' => [
        'en-US' => 'Select Language',
        'zh-CN' => '选择语言',
    ],
];

