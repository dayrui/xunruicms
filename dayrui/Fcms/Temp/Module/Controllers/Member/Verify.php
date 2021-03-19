<?php namespace Phpcmf\Controllers\Member;

class Verify extends \Phpcmf\Member\Module
{

	// 内容列表
	public function index() {
		$this->_Member_Verify_List();
	}

	public function edit() {
		$this->_Member_Verify_Edit();
	}

	public function del() {
		$this->_Member_Verify_Del();
	}
}
