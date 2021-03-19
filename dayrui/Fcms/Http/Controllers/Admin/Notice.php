<?php namespace Phpcmf\Controllers\Admin;

/**
 * http://www.xunruicms.com
 * 本文件是框架系统文件，二次开发时不可以修改本文件
 **/

class Notice extends \Phpcmf\Common
{
	
	private $field;
	
	public function __construct(...$params) {
		parent::__construct(...$params);
		$this->field = [
			'msg' => [
				'name' => dr_lang('内容'),
				'ismain' => 1,
				'fieldname' => 'msg',
				'fieldtype' => 'Text',
			],
			'username' => [
				'name' => dr_lang('申请人'),
				'ismain' => 1,
				'fieldname' => 'username',
				'fieldtype' => 'Text',
			],
			'op_username' => [
				'name' => dr_lang('处理人'),
				'ismain' => 1,
				'fieldname' => 'op_username',
				'fieldtype' => 'Text',
			]
		];
		\Phpcmf\Service::V()->assign([
			'menu' => \Phpcmf\Service::M('auth')->_admin_menu(
				[
					'处理记录' => [\Phpcmf\Service::L('Router')->class.'/index', 'fa fa-bell-slash'],
					'待处理' => [\Phpcmf\Service::L('Router')->class.'/my_index', 'fa fa-bell-o'],
					'全部' => [\Phpcmf\Service::L('Router')->class.'/all_index', 'fa fa-bell'],
				]
			),
			'field' => $this->field,
		]);
	}

	// 处理记录
	public function index() {

		list($list, $total, $param) = \Phpcmf\Service::M()->init([
			'table' => 'admin_notice',
			'where_list' => 'op_uid='. $this->uid . ' and (site=0 or site='.SITE_ID.')',
			'field' => $this->field,
			'date_field' => 'inputtime',
			'order' => 'inputtime desc'
		])->limit_page();

		\Phpcmf\Service::V()->assign([
			'list' => $list,
			'total' => $total,
			'param' => $param,
			'mypages'	=> \Phpcmf\Service::L('input')->page(\Phpcmf\Service::L('Router')->url(\Phpcmf\Service::L('Router')->class.'/'.\Phpcmf\Service::L('Router')->method), $total, 'admin')
		]);
		\Phpcmf\Service::V()->display('notice_index.html');
	}

	// 待处理
	public function my_index() {

		list($list, $total, $param) = \Phpcmf\Service::M()->init([
			'table' => 'admin_notice',
			'where_list' => '((`to_uid`='.$this->uid.') '.($this->admin['roleid'] ? ' or (`to_rid` IN ('.implode(',', $this->admin['roleid']).'))' : '').' or (`to_uid`=0 and `to_rid`=0)) and `status`<>3 and (site=0 or site='.SITE_ID.')',
			'field' => $this->field,
			'date_field' => 'inputtime',
			'order' => 'inputtime desc'
		])->limit_page();

		\Phpcmf\Service::V()->assign([
			'list' => $list,
			'total' => $total,
			'param' => $param,
			'mypages'	=> \Phpcmf\Service::L('input')->page(\Phpcmf\Service::L('Router')->url(\Phpcmf\Service::L('Router')->class.'/'.\Phpcmf\Service::L('Router')->method), $total, 'admin')
		]);
		\Phpcmf\Service::V()->display('notice_index.html');
	}

	// 全部
	public function all_index() {

		list($list, $total, $param) = \Phpcmf\Service::M()->init([
			'table' => 'admin_notice',
			'field' => $this->field,
			'date_field' => 'inputtime',
			'where_list' =>  '(site=0 or site='.SITE_ID.')',
			'order' => 'inputtime desc'
		])->limit_page();

		\Phpcmf\Service::V()->assign([
			'list' => $list,
			'total' => $total,
			'param' => $param,
			'mypages'	=> \Phpcmf\Service::L('input')->page(\Phpcmf\Service::L('Router')->url(\Phpcmf\Service::L('Router')->class.'/'.\Phpcmf\Service::L('Router')->method, $param), $total, 'admin')
		]);
		\Phpcmf\Service::V()->display('notice_index.html');
	}



	public function del() {

		$ids = \Phpcmf\Service::L('input')->get_post_ids();
		if (!$ids) {
		    $this->_json(0, dr_lang('所选数据不存在'));
        }

        \Phpcmf\Service::M()->db->table('admin_notice')->whereIn('id', $ids)->delete();

		$this->_json(1, dr_lang('操作成功'));
	}
	

}
