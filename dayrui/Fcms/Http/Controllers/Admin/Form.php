<?php namespace Phpcmf\Controllers\Admin;

/**
 * http://www.xunruicms.com
 * 本文件是框架系统文件，二次开发时不可以修改本文件
 **/

class Form extends \Phpcmf\Common
{

    public function index() {
        if (dr_is_app('form')) {
            dr_redirect(dr_url('form/form/index'));
        } else {
            $this->_admin_msg(0, dr_lang('系统没有安装网站表单插件'));
        }
    }
}