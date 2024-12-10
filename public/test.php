<?php

/**
 * 环境监测程序（正式上线后可删除本文件）
 */

header('Content-Type: text/html; charset=utf-8');
ini_set('display_errors', 1);

define('SELF', pathinfo(__FILE__, PATHINFO_BASENAME));
define('WEBPATH', dirname(__FILE__).'/');
define('SYSTEMPATH', true);

// 部分虚拟主机禁用函数时


// 判断环境
$min = '7.4.0';
$max = '8.4.0';

// 判断目录
if (is_file(WEBPATH.'config/api.php')) {
    define('CONFIGPATH',WEBPATH.'config/');
    define('WRITEPATH',WEBPATH.'cache/');
    if (is_dir(WEBPATH.'dayrui/CodeIgniter72/')) {
        $min = '7.2.0';
    }
    $version = WEBPATH.'dayrui/My/Config/Version.php';
    if (is_file($version)) {
        $vcfg = require $version;
        dr_echo_msg(1, '当前CMS版本：V'.$vcfg['version'].'（'.$vcfg['downtime'].'）- '.$vcfg['name']);
    }
} else {
    $wpath = dirname(dirname(__FILE__)).'/';
    define('CONFIGPATH', $wpath.'config/');
    define('WRITEPATH', $wpath.'cache/');
    if (is_dir($wpath.'dayrui/CodeIgniter72/')) {
        $min = '7.2.0';
    }
    $version = $wpath.'dayrui/My/Config/Version.php';
    if (is_file($version)) {
        $vcfg = require $version;
        dr_echo_msg(1, '当前CMS版本：V'.$vcfg['version'].'（'.$vcfg['downtime'].'）- '.$vcfg['name']);
    }
}

// 判断环境
if (version_compare(PHP_VERSION, $max) > 0) {
    dr_echo_msg(0, "<font color=red>PHP版本过高，请在".$max."以下的环境使用，当前".PHP_VERSION."，高版本需要等待官方对CMS版本的更新升级！~</font>");exit;
} elseif (version_compare(PHP_VERSION, $min) < 0) {
    dr_echo_msg(0, "<font color=red>PHP版本建议在".$min."及以上，当前".PHP_VERSION."</font><hr>最低支持PHP7.2环境，需要在这里下载兼容包：https://www.xunruicms.com/doc/1166.html");exit;
} else {
    dr_echo_msg(1, 'PHP版本要求：'.$min.'及以上，当前'.PHP_VERSION.'');
}

if (strpos(PHP_VERSION, '7.2') === 0 && !extension_loaded('ionCube Loader')) {
    dr_echo_msg(2, '试用插件安装：当前环境无法运行试用插件，PHP环境中的ionCube扩展没有安装成功');
}


dr_echo_msg(1, '当前脚本地址：'.$_SERVER['SCRIPT_NAME']);
dr_echo_msg(1, '当前脚本路径：'.__FILE__);
$pos = strpos(trim($_SERVER['SCRIPT_NAME'], '/'), '/');
if ($pos !== false && $pos > 1) {
    dr_echo_msg(0, "<font color=red>本程序必须在域名根目录中安装</font>，查看手册：https://www.xunruicms.com/doc/741.html");exit;
}

if (preg_match('/[\x{4e00}-\x{9fff}]+/u', WEBPATH)) {
    dr_echo_msg(0, '<font color=red>WEB目录['.WEBPATH.']不允许出现中文或全角符号</font>');
}

foreach (array(' ', '[', ']') as $t) {
    if (strpos(WEBPATH, $t) !== false) {
        dr_echo_msg(0, '<font color=red>WEB目录['.WEBPATH.']不允许出现'.($t ? $t : '空格').'符号</font>');
    }
}

foreach (array(WEBPATH.'index.php', CONFIGPATH.'database.php', CONFIGPATH.'rewrite.php',CONFIGPATH.'custom.php' ) as $t) {
    if (is_file($t) && dr_check_bom($t)) {
        dr_echo_msg(0, '<font color=red>文件['.str_replace(WEBPATH, '', $t).']编码存在严重问题，查看手册：https://www.xunruicms.com/doc/395.html</font>');
    }
}

foreach (array(WRITEPATH.'file/', WRITEPATH.'template/', WRITEPATH.'session/', WEBPATH.'uploadfile/' ) as $t) {
    if (is_dir($t) && !dr_check_put_path($t)) {
        dr_echo_msg(0, '<font color=red>目录['.str_replace(WEBPATH, '', $t).']无法写入文件，请给可读可写权限：0777</font>');
    }
}

if (isset($_GET['log']) && $_GET['log']) {
    if (!is_file(WRITEPATH.'error/log-'.date('Y-m-d').'.php')) {
        exit('今天没有错误日志记录');
    }
    echo nl2br(file_get_contents(WRITEPATH.'error/log-'.date('Y-m-d').'.php'));
    exit;
}

dr_echo_msg(1, '客户端字符串：'.$_SERVER['HTTP_USER_AGENT']);


$url = 'http';
if ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on')
    || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == '443')
    || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')
    || (isset($_SERVER['HTTP_FROM_HTTPS']) && $_SERVER['HTTP_FROM_HTTPS'] == 'on')
    || (!empty($_SERVER['HTTP_FRONT_END_HTTPS']) && strtolower($_SERVER['HTTP_FRONT_END_HTTPS']) != 'off')
) {
    $url.= 's';
}
$host = strtolower($_SERVER['HTTP_HOST']);
if (strpos($host, ':') !== false) {
    list($nhost, $port) = explode(':', $host);
    if ($port == 80) {
        $host = $nhost; // 排除80端口
    }
}
$url.= '://'.$host.'/';
if (filter_var($url, FILTER_VALIDATE_URL) === false) {
    dr_echo_msg(0, 'FILTER_VALIDATE_URL检测：'.$url.' 不合法');
}

if (is_file(CONFIGPATH.'database.php')) {
    require CONFIGPATH.'database.php';
    dr_echo_msg(1, '数据库配置文件：'.CONFIGPATH.'database.php');
}

// GD库判断
if (!function_exists('imagettftext')) {
    dr_echo_msg(0, 'PHP扩展库：GD库未安装或GD库版本太低');
}
if (! extension_loaded('curl')) {
    dr_echo_msg(0, 'PHP扩展库：CURL未安装');
}
if (! extension_loaded('json')) {
    dr_echo_msg(0, 'PHP扩展库：JSON未安装');
}
if (! extension_loaded('xml')) {
    dr_echo_msg(0, 'PHP扩展库：xml未安装');
}

if (!fopen('https://www.xunruicms.com/', "rb")) {
    dr_echo_msg(0, 'fopen无法获取远程数据，无法使用在线下载插件和在线升级');
}

$mysqli = function_exists('mysqli_init') ? mysqli_init() : 0;
if (!$mysqli) {
    dr_echo_msg(0, 'PHP环境必须启用Mysqli扩展');
}
$version = '';
if (isset($db['default']['hostname']) && $db['default']['hostname'] && strpos($db['default']['hostname'], '，') === false) {
    if (!@mysqli_real_connect($mysqli, $db['default']['hostname'], $db['default']['username'], $db['default']['password'])) {
        dr_echo_msg(0, '['.mysqli_connect_errno().'] - ['.mysqli_connect_error().'] 无法连接到数据库服务器（'.$db['default']['hostname'].'），请检查用户名（'.$db['default']['username'].'）和密码（'.$db['default']['password'].'）是否正确');
    } elseif (!@mysqli_select_db($mysqli, $db['default']['database'])) {
        dr_echo_msg(0, '指定的数据库（'.$db['default']['database'].'）不存在，请手动创建');
    } else {
        if ($result = mysqli_query($mysqli, "SELECT id FROM ".$db['default']['DBPrefix']."member LIMIT 1")) {
            dr_echo_msg(1, 'MySQL数据连接正常');
        } else {
            dr_echo_msg(0, '数据库（'.$db['default']['database'].'）数据不完整，查询异常：'.mysqli_error($mysqli));
        }
    }
    if (is_numeric(substr($db['default']['database'], 0, 1))) {
        dr_echo_msg(0,  '数据库名称（'.$db['default']['database'].'）不规范，不能是数字开头');
    } elseif (strpos($db['default']['database'], '.') !== false) {
        dr_echo_msg(0,  '数据库名称（'.$db['default']['database'].'）不规范，不能存在.号');
    }
    $version = mysqli_get_server_version($mysqli);
    if ($version) {
        if ($version > 50600) {
            dr_echo_msg(1, 'MySQL版本建议：5.6及以上，当前'.substr($version, 0, 1).'.'.substr($version, 2));
        } else {
            dr_echo_msg(1, 'MySQL版本建议：5.6及以上，当前'.substr($version, 0, 1).'.'.substr($version, 2));
        }
    }
    $rs = mysqli_query($mysqli, 'show engines');
    if ($rs) {
        $status = false;
        foreach($rs as $row){
            if($row['Engine'] == 'InnoDB' && ($row['Support'] == 'YES' || $row['Support'] == 'DEFAULT') ){
                $status = true;
            }
        }
        if (!$status) {
            dr_echo_msg(0, 'MySQL不支持InnoDB存储引擎，无法安装');
        } else {
            dr_echo_msg(1, 'MySQL支持InnoDB存储引擎');
        }
    }
    if (!mysqli_set_charset($mysqli, "utf8mb4")) {
		dr_echo_msg(0, "MySQL不支持utf8mb4编码（".mysqli_error($mysqli)."）");
	}
    $mysqli && mysqli_close($mysqli);
}

if (!$version) {
    dr_echo_msg(1, 'MySQL版本建议：5.6 及以上');
}

$post = intval(@ini_get("post_max_size"));
$file = intval(@ini_get("upload_max_filesize"));

if ($file > $post) {
    dr_echo_msg(1,'系统配置不合理，post_max_size值('.$post.')必须大于upload_max_filesize值('.$file.')');
}
if ($file < 10) {
    dr_echo_msg(1,'系统环境只允许上传'.$file.'MB文件，可以设置upload_max_filesize值提升上传大小');
}
if ($post < 10) {
    dr_echo_msg(1,'系统环境要求每次发布内容不能超过'.$post.'MB（含文件），可以设置post_max_size值提升发布大小');
}

if (!function_exists('mb_substr')) {
    dr_echo_msg(0, 'PHP不支持mbstring扩展，必须开启');
}
if (!function_exists('curl_init')) {
    dr_echo_msg(0, 'PHP不支持CURL扩展，必须开启');
}
if (!function_exists('mb_convert_encoding')) {
    dr_echo_msg(0, 'PHP的mb函数不支持，无法使用百度关键词接口');
}
if (!function_exists('imagecreatetruecolor')) {
    dr_echo_msg(0,'PHP的GD库版本太低，无法支持验证码图片');
}
if (!function_exists('ini_get')) {
    dr_echo_msg(0, '系统函数ini_get未启用，将无法获取到系统环境参数');
}
if (!function_exists('gzopen')) {
    dr_echo_msg(0,'zlib扩展未启用，您将无法进行在线升级、无法下载应用插件等');
}
if (!function_exists('gzinflate')) {
    dr_echo_msg(0,'函数gzinflate未启用，您将无法进行在线升级、无法下载应用插件等');
}
if (!function_exists('fsockopen')) {
    dr_echo_msg(0,'PHP不支持fsockopen，可能充值接口无法使用、手机短信无法发送、电子邮件无法发送、一键登录无法登录等');
}
if (!function_exists('openssl_open')) {
    dr_echo_msg(0,'PHP不支持openssl，可能充值接口无法使用、手机短信无法发送、电子邮件无法发送、一键登录无法登录等');
}
if (!ini_get('allow_url_fopen')) {
    dr_echo_msg(0,'allow_url_fopen未启用，远程图片无法保存、网络图片无法上传、可能充值接口无法使用、手机短信无法发送、电子邮件无法发送、一键登录无法登录等');
}
if (!class_exists('ZipArchive')) {
    dr_echo_msg(0,'php_zip扩展未开启，无法使用应用市场功能');
}

// 存在错误日志
if (is_file(WEBPATH.'cache/error/log-'.date('Y-m-d').'.php')) {
    $log = file_get_contents(WEBPATH.'cache/error/log-'.date('Y-m-d').'.php');
    dr_echo_msg(1, '系统故障的错误日志记录：<a style="color:blue;text-decoration:none;" href="'.SELF.'?log=true">查看日志</a>');
}

// 输出
function dr_echo_msg($code, $msg) {
    echo '<div style="border-bottom: 1px dashed #9699a2; padding: 10px;">';
    if (!$code) {
        if (strpos($msg, 'http')) {
            echo '<b style="color:red;text-decoration:none;">'.$msg.'</b>';
        } else {
            echo '<a href="https://www.baidu.com/s?ie=UTF-8&wd=迅睿CMS'.urlencode(strip_tags($msg)).'" target="_blank" style="color:red;text-decoration:none;">'.$msg.'</a>';
        }
    } elseif ($code == 2) {
        echo '<font color=blue>'.$msg.'</font>';
    } else {
        echo '<font color=green>'.$msg.'</font>';
    }
    echo '</div>';
}
// 检查bom
function dr_check_bom($filename) {
    $contents = file_get_contents($filename);
    $charset[1] = substr($contents, 0, 1);
    $charset[2] = substr($contents, 1, 1);
    $charset[3] = substr($contents, 2, 1);
    if ($charset[1] != '<') {
        return false;
    } elseif (ord($charset[1]) == 239 && ord($charset[2]) == 187 && ord($charset[3]) == 191) {
        return true;
    } else {
        return false;
    };
}
// 检查目录权限
function dr_check_put_path($dir) {

    $size = @file_put_contents($dir.'test.html', 'test');
    if ($size === false) {
        return 0;
    } else {
        @unlink($dir.'test.html');
        return 1;
    }
}

echo '<div style=" padding: 10px; color:blue">';
echo '如果以上提示文字是红色选项，就必须修改正确的环境配置 (*^▽^*) ，<font color="red">当网站正式上线后，请删除本文件吧~</font>';
echo '</div>';