<?php namespace Phpcmf\Controllers\Admin;

class Html extends \Phpcmf\Home\Module
{

	public function index() {
		parent::_Admin_Html();
	}

	public function index_del() {
		parent::_Admin_Index_Del();
	}
	
}
