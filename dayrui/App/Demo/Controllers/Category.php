<?php namespace Phpcmf\Controllers;

class Category extends \Phpcmf\Home\Module
{

	public function index() {
		// 初始化模块
		$this->_module_init();
		// 调用栏目方法
		$this->_Category(
			(int)\Phpcmf\Service::L('Input')->get('id'), 
			dr_safe_replace(\Phpcmf\Service::L('Input')->get('dir')), 
			max(1, (int)\Phpcmf\Service::L('Input')->get('page'))
		);
	}

}
