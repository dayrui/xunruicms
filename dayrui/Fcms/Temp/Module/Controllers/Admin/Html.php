<?php namespace Phpcmf\Controllers\Admin;

/**
 * 二次开发时可以修改本文件，不影响升级覆盖
 */

class Html extends \Phpcmf\Home\Module
{

	public function index() {
		parent::_Admin_Html();
	}

	public function index_del() {
		parent::_Admin_Index_Del();
	}
	
}
