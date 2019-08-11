<?php namespace Phpcmf\Controllers;

/**
 * 二次开发时可以修改本文件，不影响升级覆盖
 */

class Html extends \Phpcmf\Home\Module
{

	// 生成首页
	public function index() {
		parent::_Index_Html();
	}

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
