<?php namespace Phpcmf\Control\Api;
/**
 * {{www.xunruicms.com}}
 * {{迅睿内容管理框架系统}}
 * 本文件是框架系统文件，二次开发时不可以修改本文件
 **/

// 接口处理
class Home extends \Phpcmf\Common
{

	public function index() {

	    if (IS_API === 'pay') {
            // 支付接口部分
            $info = pathinfo($_SERVER['PHP_SELF']);
            $name = basename($info['dirname']);
            $path = trim($info['dirname'], '/');
            $file = str_replace('_url.php', '_api.php', $info['basename']);
            $apifile = WEBPATH . $path . '/' . $file;
            if (!is_file($apifile)) {
                if (IS_DEV) {
                    exit('支付接口文件（'.$apifile.'）不存在');
                }
                exit('支付接口文件不存在');
            }
            // 接口配置参数
            $config = $this->member_cache['payapi'][$name];
            require $apifile;
        } elseif (IS_API === 'cron') {
	        // 任务脚本
            $cron = new \Phpcmf\Control\Api\Run($this);
            $cron->index();
        } elseif (is_file(IS_API)) {
            // 自定义任意目录的api
            require IS_API;
        } else {
            $myfile = MYPATH.'Api/'.ucfirst(IS_API).'.php';
            if (!is_file($myfile)) {
                if (IS_DEV) {
                    exit('Api接口文件（'.$myfile.'）不存在');
                }
                exit('api file is error');
            }
            require $myfile;
            exit;
        }
        exit;
	}

}
