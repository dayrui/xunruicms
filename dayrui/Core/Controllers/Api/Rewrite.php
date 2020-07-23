<?php namespace Phpcmf\Controllers\Api;

/**
 * http://www.xunruicms.com
 * 本文件是框架系统文件，二次开发时不可以修改本文件
 **/

// 系统默认伪静态处理
class Rewrite extends \Phpcmf\Common
{

	// test
	public function test() {
		$this->_jsonp(1, '服务器支持伪静态功能，可以自定义URL规则和解析规则了');
	}
}
