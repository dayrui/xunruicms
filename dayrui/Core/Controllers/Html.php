<?php namespace Phpcmf\Controllers;

/**
 * http://www.xunruicms.com
 * 本文件是框架系统文件，二次开发时不可以修改本文件
 **/


// 共享栏目生成静态
class Html extends \Phpcmf\Home\Module
{

	// 生成栏目
	public function category() {
		parent::_Category_Html();
	}

	// 生成内容
	public function show() {
		parent::_Show_Html();
	}

	// 生成栏目单页
	public function categoryfile() {
		parent::_Category_Html_File();
	}

	// 生成内容单页
	public function showfile() {
		parent::_Show_Html_File();
	}

	
}
