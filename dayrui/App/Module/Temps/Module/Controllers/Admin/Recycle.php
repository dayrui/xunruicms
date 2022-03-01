<?php namespace Phpcmf\Controllers\Admin;

/**
 * 二次开发时可以修改本文件，不影响升级覆盖
 */

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

    public function edit() {
        $this->_Admin_Recycle_Edit();
    }
}
