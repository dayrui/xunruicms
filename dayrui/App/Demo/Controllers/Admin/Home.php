<?php namespace Phpcmf\Controllers\Admin;

class Home extends \Phpcmf\Admin\Module
{

	public function index() {
		$this->_Admin_List();
	}

	public function add() {
		$this->_Admin_Add();
	}

	public function edit() {
		$this->_Admin_Edit();
	}

	public function show_index() {
		$this->_Admin_Show();
	}

	public function order_edit() {
		$this->_Admin_Order();
	}

	public function move_edit() {
		$this->_Admin_Move();
	}

	public function tui_edit() {
		$this->_Admin_Send();
	}

	public function syncat_edit() {
		$this->_Admin_Syncat();
	}

	public function del() {
		$this->_Admin_Del();
	}
}
