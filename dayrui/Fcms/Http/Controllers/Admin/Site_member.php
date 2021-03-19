<?php namespace Phpcmf\Controllers\Admin;

/**
 * http://www.xunruicms.com
 * 本文件是框架系统文件，二次开发时不可以修改本文件
 **/


// 站点用户权限
class Site_member extends \Phpcmf\Common
{

	public function index() {

        dr_redirect(dr_url('member_auth/index'));

	}

}
