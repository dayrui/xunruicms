<?php namespace Phpcmf\Controllers\Admin;

class Recycle extends \Phpcmf\Admin\Module
{

	public function index() {
		$this->_Admin_Recycle_List();
	}

	public function del() {
		$this->_Admin_Recycle_Del();
	}

    public function show() {
        $this->_Admin_Recycle_Show();
    }

	public function recovery_add() {
		$this->_Admin_Recovery();
	}
}
