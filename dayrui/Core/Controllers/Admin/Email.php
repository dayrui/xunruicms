<?php namespace Phpcmf\Controllers\Admin;

/**
 * http://www.xunruicms.com
 * 本文件是框架系统文件，二次开发时不可以修改本文件
 **/

class Email extends \Phpcmf\Common
{
	private $form; // 表单验证配置
	
	public function __construct(...$params) {
		parent::__construct(...$params);
		\Phpcmf\Service::V()->assign('menu', \Phpcmf\Service::M('auth')->_admin_menu(
			[
				'SMTP服务器' => ['email/index', 'fa fa-envelope'],
				'添加' => ['add:email/add', 'fa fa-plus'],
                'help' => [361],
			]
		));
		// 表单验证配置
		$this->form = [
			'host' => [
				'name' => '服务器',
				'rule' => [
					'empty' => dr_lang('服务器不能为空')
				],
				'filter' => [],
				'length' => '200'
			],
			'port' => [
				'name' => '端口号',
				'rule' => [
					'empty' => dr_lang('端口号不能为空')
				],
				'filter' => ['intval'],
				'length' => '30'
			],
			'user' => [
				'name' => '邮箱账号',
				'rule' => [
					'empty' => dr_lang('邮箱账号不能为空')
				],
				'filter' => [],
				'length' => '200'
			],
			'pass' => [
				'name' => '邮箱密码',
				'rule' => [
					'empty' => dr_lang('邮箱密码不能为空')
				],
				'filter' => [],
				'length' => '200'
			],
		];
		
	}

	public function index() {

		\Phpcmf\Service::V()->assign([
			'list' => \Phpcmf\Service::M()->table('mail_smtp')->order_by('displayorder asc')->getAll(),
		]);
		\Phpcmf\Service::V()->display('email_index.html');
	}

	public function add() {

		if (IS_AJAX_POST) {
			$data = \Phpcmf\Service::L('input')->post('data', true);
			$this->_validation($data);
			\Phpcmf\Service::L('input')->system_log('添加SMTP服务器: '.$data['name']);
			$data['displayorder'] = intval($data['displayorder']);
			\Phpcmf\Service::M()->table('mail_smtp')->insert($data);
            // 自动更新缓存
            \Phpcmf\Service::M('cache')->sync_cache('email');
			exit($this->_json(1, dr_lang('操作成功')));
		}

		\Phpcmf\Service::V()->assign([
			'form' => dr_form_hidden()
		]);
		\Phpcmf\Service::V()->display('email_add.html');
		exit;
	}


	public function edit() {

		$id = intval(\Phpcmf\Service::L('input')->get('id'));
		$data = \Phpcmf\Service::M()->table('mail_smtp')->get($id);
		!$data && exit($this->_json(0, dr_lang('数据#%s不存在', $id)));

		if (IS_AJAX_POST) {
			$data = \Phpcmf\Service::L('input')->post('data', true);
			$this->_validation($data);
			\Phpcmf\Service::M()->table('mail_smtp')->update($id, $data);
			\Phpcmf\Service::L('input')->system_log('修改SMTP服务器: '.$data['name']);

            \Phpcmf\Service::M('cache')->sync_cache('email'); // 自动更新缓存
			exit($this->_json(1, dr_lang('操作成功')));
		}

		\Phpcmf\Service::V()->assign([
			'data' => $data,
			'form' => dr_form_hidden(),
		]);
		\Phpcmf\Service::V()->display('email_add.html');
		exit;
	}

	// 保存数据
	public function save_edit() {

		$i = intval(\Phpcmf\Service::L('input')->get('id'));
		\Phpcmf\Service::M()->table('mail_smtp')->save(
			$i,
			dr_safe_replace(\Phpcmf\Service::L('input')->get('name')),
			dr_safe_replace(\Phpcmf\Service::L('input')->get('value'))
		);

		\Phpcmf\Service::L('input')->system_log('修改SMTP服务器排序值: '. $i);
        \Phpcmf\Service::M('cache')->sync_cache('email'); // 自动更新缓存
		exit($this->_json(1, dr_lang('更改成功')));
	}

	public function del() {

		$ids = \Phpcmf\Service::L('input')->get_post_ids();
		!$ids && exit($this->_json(0, dr_lang('你还没有选择呢')));

		\Phpcmf\Service::M()->table('mail_smtp')->deleteAll($ids);
        \Phpcmf\Service::M('cache')->sync_cache('email'); // 自动更新缓存
		\Phpcmf\Service::L('input')->system_log('批量删除SMTP服务器: '. @implode(',', $ids));
		exit($this->_json(1, dr_lang('操作成功'), ['ids' => $ids]));
	}


	// 验证数据
	private function _validation($data) {

		list($data, $return) = \Phpcmf\Service::L('Form')->validation($data, $this->form);
		$return && exit($this->_json(0, $return['error'], ['field' => $return['name']]));

	}
}
