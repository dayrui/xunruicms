<?php namespace Phpcmf\Extend;
/**
 * {{www.xunruicms.com}}
 * {{迅睿内容管理框架系统}}
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
    public function __construct($config) {
        // 执行时间标记
        $this->startTime = microtime(true);
        $this->config = $config;
        // 自定义函数库
        if (is_file(ROOTPATH.'config/custom.php')) {
            require ROOTPATH.'config/custom.php';
        }
        if (is_file(MYPATH.'Helper.php')) {
            require MYPATH.'Helper.php';
        }
        // 系统函数库
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
            exit('历史版本冗余文件未清理干净<br>第一步、请删除目录：'.SYSTEMPATH.'ThirdParty/<br>第二步、再到官网下载升级包，找的目录（dayrui/System/ThirdParty/），然后重新上传覆盖到第一步的路径中。');
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
     * 检测框架所需要的PHP扩展
     */
    protected function resolvePlatformExtensions()
    {
        $requiredExtensions = [
            'curl',
            //'intl',
            'json',
            'mbstring',
            'xml',
        ];

        $missingExtensions = [];

        foreach ($requiredExtensions as $extension)
        {
            if (! extension_loaded($extension))
            {
                $missingExtensions[] = $extension;
            }
        }

        if ($missingExtensions)
        {
            dr_exit_msg(0, '当前服务器环境缺少PHP扩展：'.implode(', ', $missingExtensions));
        }
    }

    /**
     * 页面缓存名称定义
     */
    protected function generateCacheName($config): string
    {
        return 'page-'.md5(FC_NOW_URL);
    }

    /**
     * 过滤保留的方法名称
     */
    protected function startController()
    {
        parent::startController();
        if ($this->method && in_array($this->method, ['get_attachment', 'get_cache', 'session', 'cachePage', 'init_file'])) {
            dr_exit_msg(0, '控制器方法（'.$this->method.'）是系统保留关键词，不能被访问');
        }
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
        header('HTTP/1.1 200 OK');
        echo ($_GET['callback'] ? $_GET['callback'] : 'callback').'('.json_encode($rt, JSON_UNESCAPED_UNICODE).')';
    } else if (($_GET['is_ajax'] || (defined('IS_API_HTTP') && IS_API_HTTP) || IS_AJAX)) {
        // json
        header('HTTP/1.1 200 OK');
        echo json_encode($rt, JSON_UNESCAPED_UNICODE);
    } else {
        // html
        dr_show_error($msg);
    }
    exit;
}