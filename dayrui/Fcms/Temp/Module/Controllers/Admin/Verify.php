<?php namespace Phpcmf\Controllers\Admin;

/**
 * 二次开发时可以修改本文件，不影响升级覆盖
 */

class Verify extends \Phpcmf\Admin\Module
{

	public function index() {
		$this->_Admin_Verify_List();
	}

	public function edit() {
		$this->_Admin_Verify_Edit();
	}

	public function del() {
		$this->_Admin_Verify_Del();
	}
}
