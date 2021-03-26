<?php namespace Phpcmf\Controllers;
/**
 * http://www.xunruicms.com
 * 本文件是框架系统文件，二次开发时不可以修改本文件
 * 迅睿内容管理框架系统（简称：迅睿CMS）软件著作权登记号：2019SR0854684
 **/

class Show extends \Phpcmf\Home\Module
{

	public function index() {

        if (IS_POST) {
            $this->_json(0, '禁止提交，请检查提交地址是否有误');
        }

		// 共享模块通过id查找内容
		$id = (int)\Phpcmf\Service::L('input')->get('id');
		$row = \Phpcmf\Service::M()->table(SITE_ID.'_share_index')->get($id);
		$mid = $row['mid'];
		if (!$mid) {
            exit($this->goto_404_page(dr_lang('无法通过id找到共享模块的模块目录')));
        }

		// 初始化模块
		$this->_module_init($mid);

		// 调用内容方法
		$this->_Show($id, null, max(1, (int)\Phpcmf\Service::L('input')->get('page')));
	}

}
