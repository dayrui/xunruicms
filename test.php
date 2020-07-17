<?php

/**
 * 环境监测程序
 */

header('Content-Type: text/html; charset=utf-8');
error_reporting(E_ALL ^ E_NOTICE ^ E_WARNING ^ E_STRICT);
ini_set('display_errors', 1);

// 判断环境
if (version_compare(PHP_VERSION, '7.1.0') < 0) {
    exit("<font color=red>PHP版本必须在7.2以上</font>");
} else {
    dr_echo_msg(1, 'PHP版本：v'.PHP_VERSION);
}
// 扩展
if (!function_exists('intl_is_failure')) {
    dr_echo_msg(0, "<font color=red>PHP版本需要安装intl扩展</font>");
}

define('WEBPATH', dirname(__FILE__).'/');


require WEBPATH.'config/database.php';
$mysqli = function_exists('mysqli_init') ? mysqli_init() : 0;
if (!$mysqli) {
    dr_echo_msg(0, 'PHP环境必须启用Mysqli扩展');
}

if ($db['default']['database']) {
    if (!@mysqli_real_connect($mysqli, $db['default']['hostname'], $db['default']['username'], $db['default']['password'])) {
        dr_echo_msg(0, '[mysqli_real_connect] - ['.mysqli_connect_errno().'] 无法连接到数据库服务器（'.$db['default']['hostname'].'），请检查用户名（'.$db['default']['username'].'）和密码（'.$db['default']['password'].'）是否正确');
    } elseif (!@mysqli_select_db($mysqli, $db['default']['database'])) {
        dr_echo_msg(0, '指定的数据库（'.$db['default']['database'].'）不存在');
    } else {
        if ($result = mysqli_query($mysqli, "SELECT id FROM ".$db['default']['DBPrefix']."member LIMIT 1")) {
            dr_echo_msg(1, 'MySQL数据连接正常');
        } else {
            dr_echo_msg(0, '数据库（'.$db['default']['database'].'）查询异常：'.mysqli_error($mysqli));
        }
        mysqli_close($mysqli);
    }
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

// 输出
function dr_echo_msg($code, $msg) {
    echo '<div style="border-bottom: 1px dashed #9699a2; padding: 10px;">';
    if (!$code) {
        echo '<font color=red>'.$msg.'</font>';
    } else {
        echo '<font color=green>'.$msg.'</font>';
    }
    echo '</div>';
}

echo '<div style=" padding: 10px; color:blue">';
echo '如果以上提示文字是红色选项，就必须修改正确的环境配置 (*^▽^*) ，当网站正式上线后，请删除本文件吧~';
echo '</div>';