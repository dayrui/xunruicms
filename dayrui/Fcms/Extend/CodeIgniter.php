<?php namespace Phpcmf\Extend;

/**
 * http://www.xunruicms.com
 * 本文件是框架系统文件，二次开发时不可以修改本文件
 **/

/**
 * 继承主类，用于init.php
 */

class CodeIgniter extends \CodeIgniter\CodeIgniter
{
    /**
     * 初始化程序
     */
    public function __construct(...$params)
    {
        parent::__construct(...$params);
        // 自定义函数库
        if (is_file(ROOTPATH.'config/custom.php'))
        {
            require ROOTPATH.'config/custom.php';
        }
        if (is_file(MYPATH.'Helper.php'))
        {
            require MYPATH.'Helper.php';
        }
        require CMSPATH.'Core/Helper.php';
    }

    /**
     * 初始化方法
     */
    public function initialize()
    {
        // 升级框架后的问题避免
        if ((is_file(SYSTEMPATH.'ThirdParty/Kint/kint.php')
                && strpos(file_get_contents(SYSTEMPATH.'ThirdParty/Kint/kint.php'), 'eval(gzuncompress(') !== false)
            || !is_file(SYSTEMPATH.'ThirdParty/Kint/Kint.php')) {
            exit('核心框架升级兼容调整<br>1、请删除目录：'.SYSTEMPATH.'ThirdParty/Kint/<br>2、再到官网下载升级包，将此目录（'.SYSTEMPATH.'ThirdParty/Kint/）重新上传覆盖一次');
        }

        parent::initialize();
        \Phpcmf\Hooks::trigger('init');
    }

    /**
     * 不执行此方法
     * 自定义错误显示和debug开关
     */
    protected function bootstrapEnvironment() {

    }

    /**
     * 页面缓存名称定义
     */
    protected function generateCacheName($config): string
    {
        return 'page-'.md5(FC_NOW_URL);
    }


}

/**
 * 全局返回消息
 */
function dr_exit_msg($code, $msg, $data = []) {


    ob_end_clean();

    $rt = [
        'code' => $code,
        'msg' => $msg,
        'data' => $data,
    ];

    if (isset($_GET['callback'])) {
        // jsonp
        @header('HTTP/1.1 200 OK');
        echo ($_GET['callback'] ? $_GET['callback'] : 'callback').'('.json_encode($rt, JSON_UNESCAPED_UNICODE).')';
    } else if (($_GET['is_ajax'] || (defined('IS_API_HTTP') && IS_API_HTTP) || IS_AJAX)) {
        // json
        @header('HTTP/1.1 200 OK');
        echo json_encode($rt, JSON_UNESCAPED_UNICODE);
    } else {
        // html
        exit($msg);
    }
    exit;
}