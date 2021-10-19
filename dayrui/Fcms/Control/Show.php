<?php namespace Phpcmf\Control;
/**
 * {{www.xunruicms.com}}
 * {{迅睿内容管理框架系统}}
 * 本文件是框架系统文件，二次开发时不可以修改本文件
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

        // 挂钩点
        $rt2 = \Phpcmf\Hooks::trigger_callback('module_show_share', $row);
        if ($rt2 && isset($rt2['code']) && $rt2['code']) {
            $row = $rt2['data'];
        }
        $mid = $row['mid'];
		if (!$mid) {
            $this->goto_404_page(dr_lang('无法通过id找到共享模块的模块目录'));
        }

		// 初始化模块
		$this->_module_init($mid);

		// 调用内容方法
		$this->_Show($id, null, max(1, (int)\Phpcmf\Service::L('input')->get('page')));
	}

}
