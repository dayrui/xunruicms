<?php namespace Phpcmf;

/**
 * http://www.xunruicms.com
 * 本文件是框架系统文件，二次开发时不可以修改本文件
 **/

// 应用公共继承类
class App extends \Phpcmf\Common
{

    public function __construct(...$params)
    {
        parent::__construct($params);
        if (!dr_is_app(APP_DIR)) {
            if (is_file(APPPATH.'Config/App.php')) {
                $cfg = require APPPATH.'Config/App.php';
                $this->_msg(0, dr_lang('应用[%s]未安装', $cfg['name']));
            } else {
                $this->_msg(0, dr_lang('应用[%s]未安装', APP_DIR));
            }
        }
    }

}
