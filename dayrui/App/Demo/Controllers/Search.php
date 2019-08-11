<?php namespace Phpcmf\Controllers;

class Search extends \Phpcmf\Home\Module
{

	public function index() {
		// 初始化模块
		$this->_module_init();
		// 调用搜索方法
		$this->_Search();
	}

}
