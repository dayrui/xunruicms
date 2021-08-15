<?php namespace Phpcmf\Control\Api;
/**
 * {{www.xunruicms.com}}
 * {{迅睿内容管理框架系统}}
 * 本文件是框架系统文件，二次开发时不可以修改本文件
 **/

$file = dr_get_app_dir('pay').'Controllers/Home.php';
if (is_file($file)) {
    require_once $file;
} else {
    \dr_exit_msg(0, '没有安装「支付系统」插件');
}

// 付款
class Pay extends \Phpcmf\Controllers\Home
{


}
