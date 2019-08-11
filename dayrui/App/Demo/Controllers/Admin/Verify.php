<?php namespace Phpcmf\Controllers\Admin;

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
