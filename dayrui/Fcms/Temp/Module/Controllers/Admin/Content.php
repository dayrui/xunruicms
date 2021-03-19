<?php namespace Phpcmf\Controllers\Admin;

/**
 * 二次开发时可以修改本文件，不影响升级覆盖
 */

class Content extends \Phpcmf\Admin\Content
{

	public function index() {
		$this->_Index();
	}

	public function url_index() {
		$this->_Url();
	}

	public function thumb_index() {
		$this->_Thumb();
	}

	public function tag_index() {
		$this->_Tag();
	}

	public function replace_module_index() {
		$this->_Replace_Module();
	}

}
