<?php namespace Phpcmf\Controllers\Admin;

/**
 * http://www.xunruicms.com
 * 本文件是框架系统文件，二次开发时不可以修改本文件
 **/



// 程序更新
class Update extends \Phpcmf\Common
{

	public function index() {

	    exit;
		$page = (int)\Phpcmf\Service::L('input')->get('page');
		if (!$page) {
			$this->_admin_msg(2, '正在更新数据...',\Phpcmf\Service::L('Router')->url('update/index', array('page' => $page + 1)), 1);
		}

		switch($page) {

			case 1:

				// 批量修改模块表
				$module = \Phpcmf\Service::M()->db->table('module')->get()->getResultArray();
				if ($module) {
                    foreach ($this->site as $siteid) {
                        foreach ($module as $t) {
                            $table = dr_module_table_prefix($t['dirname'], $siteid);
                            $prefix = \Phpcmf\Service::M()->dbprefix($table);
                            // 判断是否存在表
                            if (!\Phpcmf\Service::M()->db->tableExists($prefix)) {
                                continue;
                            }

                            \Phpcmf\Service::M()->db->fieldExists('permission', $prefix.'_category') && \Phpcmf\Service::M()->db->query('ALTER TABLE `' . $prefix . '_category` DROP `permission`');
                            \Phpcmf\Service::M()->db->fieldExists('letter', $prefix.'_category') &&  \Phpcmf\Service::M()->db->query('ALTER TABLE `' . $prefix . '_category` DROP `letter`');
                        }

                        $prefix = \Phpcmf\Service::M()->dbprefix($siteid);
                        \Phpcmf\Service::M()->db->fieldExists('letter', $prefix.'_share_category') && \Phpcmf\Service::M()->db->query('ALTER TABLE `' . $prefix . '_share_category` DROP `letter`');
                        \Phpcmf\Service::M()->db->fieldExists('pcatpost', $prefix.'_share_category') && \Phpcmf\Service::M()->db->query('ALTER TABLE `' . $prefix . '_share_category` DROP `pcatpost`');
                        \Phpcmf\Service::M()->db->fieldExists('permission', $prefix.'_share_category') && \Phpcmf\Service::M()->db->query('ALTER TABLE `' . $prefix . '_share_category` DROP `permission`');
                    }

				}


				$this->_admin_msg(1, '表结构升级成功...',\Phpcmf\Service::L('Router')->url('update/index', array('page' => $page + 1)), 1);
				break;


		}
	}



}
