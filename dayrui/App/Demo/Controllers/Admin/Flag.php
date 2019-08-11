<?php namespace Phpcmf\Controllers\Admin;

class Flag extends \Phpcmf\Admin\Module
{

	public function index() {
		$this->_Admin_Flag_List();
	}
	
	public function edit() {
		$this->_Admin_Edit();
	}
}
