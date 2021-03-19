<?php namespace Phpcmf\Controllers\Member;

class Draft extends \Phpcmf\Member\Module
{
	// 内容列表
	public function index() {
		$this->_Member_Draft_List();
	}

	public function del() {
		$this->_Member_Draft_Del();
	}
}
