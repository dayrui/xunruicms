<?php namespace Phpcmf\Controllers\Admin;

/**
 * 二次开发时可以修改本文件，不影响升级覆盖
 */

class Draft extends \Phpcmf\Admin\Module
{

	public function index() {
		$this->_Admin_Draft_List();
	}

	public function del() {
		$this->_Admin_Draft_Del();
	}
}
