<?php namespace Phpcmf\Controllers\Admin;

/**
 * 二次开发时可以修改本文件，不影响升级覆盖
 */

class $NAME$ extends \Phpcmf\Admin\Form
{

	// 数据列表
	public function index() {
		$this->_Admin_List();
	}

	// 添加
	public function add() {
		$this->_Admin_Add();
	}

	// 修改
	public function edit() {
		$this->_Admin_Edit();
	}

	// 显示
	public function show_index() {
		$this->_Admin_Show();
	}

	// 修改排序
	public function edit_order() {
		$this->_Admin_Order();
	}

	// 批量删除
	public function del() {
		$this->_Admin_Del();
	}

}
