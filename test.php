<?php

/**
 * 环境监测程序
 */

header('Content-Type: text/html; charset=utf-8');
error_reporting(E_ALL ^ E_NOTICE ^ E_WARNING ^ E_STRICT);
ini_set('display_errors', 1);

define('SELF', pathinfo(__FILE__, PATHINFO_BASENAME));
define('WEBPATH', dirname(__FILE__).'/');
define('SYSTEMPATH', true);

if (isset($_GET['log']) && $_GET['log']) {
    if (!is_file(WEBPATH.'cache/error/log-'.date('Y-m-d').'.php')) {
        exit('今天没有错误日志记录');
    }
    echo nl2br(file_get_contents(WEBPATH.'cache/error/log-'.date('Y-m-d').'.php'));
    exit;
}

// 判断环境
if (version_compare(PHP_VERSION, '7.1.0') < 0) {
    exit("<font color=red>PHP版本必须在7.2及以上</font>");
} else {
    dr_echo_msg(1, 'PHP版本要求：7.2及以上，当前'.PHP_VERSION);
}

// 扩展
if (!function_exists('intl_is_failure')) {
    dr_echo_msg(0, "PHP版本需要安装intl扩展");
}

require WEBPATH.'config/database.php';
$mysqli = function_exists('mysqli_init') ? mysqli_init() : 0;
if (!$mysqli) {
    dr_echo_msg(0, 'PHP环境必须启用Mysqli扩展');
}

if ($db['default']['database']) {
    if (!@mysqli_real_connect($mysqli, $db['default']['hostname'], $db['default']['username'], $db['default']['password'])) {
        dr_echo_msg(0, '['.mysqli_connect_errno().'] - ['.mysqli_connect_error().'] 无法连接到数据库服务器（'.$db['default']['hostname'].'），请检查用户名（'.$db['default']['username'].'）和密码（'.$db['default']['password'].'）是否正确');
    } elseif (!@mysqli_select_db($mysqli, $db['default']['database'])) {
        dr_echo_msg(0, '指定的数据库（'.$db['default']['database'].'）不存在，请手动创建');
    } else {
        if ($result = mysqli_query($mysqli, "SELECT id FROM ".$db['default']['DBPrefix']."member LIMIT 1")) {
            dr_echo_msg(1, 'MySQL数据连接正常');
        } else {
            dr_echo_msg(0, '数据库（'.$db['default']['database'].'）查询异常：'.mysqli_error($mysqli));
        }
    }
    if (strpos($db['default']['database'], '.') !== false) {
        dr_echo_msg(0,  '数据库名称（'.$db['default']['database'].'）不规范，不能存在.号');
    }
    $version = mysqli_get_server_version($mysqli);
    if ($version) {
        if ($version > 50600) {
            dr_echo_msg(1, 'MySQL版本要求：5.6及以上，当前'.substr($version, 0, 1).'.'.substr($version, 2));
        } else {
            dr_echo_msg(0, 'MySQL版本要求：5.6及以上，当前'.substr($version, 0, 1).'.'.substr($version, 2));
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
    $mysqli && mysqli_close($mysqli);
}

if (!$version) {
    dr_echo_msg(1, 'MySQL版本要求：5.6及以上');
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
        echo '<a href="https://www.baidu.com/s?ie=UTF-8&wd='.urlencode($msg).'" target="_blank" style="color:red;text-decoration:none;">'.$msg.'</a>';
    } else {
        echo '<font color=green>'.$msg.'</font>';
    }
    echo '</div>';
}

echo '<div style=" padding: 10px; color:blue">';
echo '如果以上提示文字是红色选项，就必须修改正确的环境配置 (*^▽^*) ，当网站正式上线后，请删除本文件吧~';
echo '</div>';