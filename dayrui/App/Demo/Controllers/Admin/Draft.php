<?php namespace Phpcmf\Controllers\Admin;

class Draft extends \Phpcmf\Admin\Module
{

	public function index() {
		$this->_Admin_Draft_List();
	}

	public function edit() {
		$this->_Admin_Draft_Edit();
	}

	public function del() {
		$this->_Admin_Draft_Del();
	}
}
