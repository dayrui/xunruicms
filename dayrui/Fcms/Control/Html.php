<?php namespace Phpcmf\Control;
/**
 * http://www.xunruicms.com
 * 本文件是框架系统文件，二次开发时不可以修改本文件
 * 迅睿内容管理框架系统（简称：迅睿CMS）软件著作权登记号：2019SR0854684
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
