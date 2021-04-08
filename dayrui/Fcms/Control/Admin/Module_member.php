<?php namespace Phpcmf\Control\Admin;
/**
 * {{www.xunruicms.com}}
 * {{迅睿内容管理框架系统}}
 * 本文件是框架系统文件，二次开发时不可以修改本文件
 **/

class Module_member extends \Phpcmf\Common
{

    public function index() {

        dr_redirect(dr_url('member_auth/index'));
    }

}
