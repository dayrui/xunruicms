<?php namespace Phpcmf\Controllers\Admin;

/**
 * http://www.xunruicms.com
 * 本文件是框架系统文件，二次开发时不可以修改本文件
 **/


class Min_menu extends \Phpcmf\Common
{
	private $form; // 表单验证配置

	public function __construct(...$params) {
		parent::__construct(...$params);
		\Phpcmf\Service::V()->assign('menu', \Phpcmf\Service::M('auth')->_admin_menu(
			[
				'简化菜单' => ['min_menu/index', 'fa fa-list-alt'],
				'初始化菜单' => ['ajax:min_menu/init', 'fa fa-refresh'],
                'help' => [961],
			]
		));
		// 表单验证配置
		$this->form = [
			'name' => [
				'name' => '菜单名称',
				'rule' => [
					'empty' => dr_lang('菜单名称不能为空')
				],
				'filter' => [],
				'length' => '200'
			],
			'icon' => [
				'name' => '菜单图标',
				'rule' => [
					'empty' => dr_lang('菜单图标不能为空')
				],
				'filter' => [],
				'length' => '30'
			],
			'uri' => [
				'name' => '系统路径',
				'rule' => [
					'empty' => dr_lang('系统路径不能为空')
				],
				'filter' => [],
				'length' => '200'
			],
			'url' => [
				'name' => '实际地址',
				'rule' => [
					'empty' => dr_lang('实际地址不能为空')
				],
				'filter' => [
					'url'
				],
				'length' => '200'
			],
		];
	}

	public function index() {
		\Phpcmf\Service::V()->assign([
			'data' => \Phpcmf\Service::M('Menu')->gets('admin_min'),
			'color' => ['blue', 'red', 'green', 'dark', 'yellow'],
		]);
		\Phpcmf\Service::V()->display('min_menu_list.html');
	}

	public function site_add() {

		$ids = \Phpcmf\Service::L('input')->get_post_ids();
		if (!$ids) {
		    $this->_json(0, dr_lang('你还没有选择呢'));
        }

		$sid = (int)\Phpcmf\Service::L('input')->post('siteid');
		if (!$sid) {
		    $this->_json(0, dr_lang('你还没有选择站点'));
        }

		$data = \Phpcmf\Service::M()->db->table('admin_min_menu')->whereIN('id', $ids)->get()->getResultArray();
		if (!$data) {
		    $this->_json(0, dr_lang('无可用菜单'));
        }

		foreach ($data as $t) {
			$value = dr_string2array($t['site']);
			$value[$sid] = $sid;
			\Phpcmf\Service::M()->db->table('admin_min_menu')->where('id', $t['id'])->update([
				'site' => dr_array2string($value)
			]);
		}

        \Phpcmf\Service::M('cache')->sync_cache(''); // 自动更新缓存
		$this->_json(1, dr_lang('划分成功'));
	}

	public function site_del() {

		$id = (int)\Phpcmf\Service::L('input')->get('id');
		$data = \Phpcmf\Service::M('Menu')->getRowData('admin_min', $id);
		if (!$data) {
		    $this->_json(0, dr_lang('菜单不存在'));
        }

        $sid = (int)\Phpcmf\Service::L('input')->get('sid');
		if (!$sid) {
		    $this->_json(0, dr_lang('站点id不存在'));
        }

		$value = dr_string2array($data['site']);
		unset($value[$sid]);

		\Phpcmf\Service::M()->db->table('admin_min_menu')->where('id', $id)->update([
			'site' => dr_array2string($value)
		]);

        \Phpcmf\Service::M('cache')->sync_cache(''); // 自动更新缓存
		$this->_json(1, dr_lang('删除成功'));
	}


	public function add() {

		$pid = intval(\Phpcmf\Service::L('input')->get('pid'));
		$top = \Phpcmf\Service::M('Menu')->get_top('admin_min');

		if (IS_AJAX_POST) {
			$data = \Phpcmf\Service::L('input')->post('data');
			$this->_validation($data);
			\Phpcmf\Service::L('input')->system_log('添加简化菜单: '.$data['name']);
            $rt = \Phpcmf\Service::M('Menu')->_add('admin_min', $pid, $data);
            if ($rt['code']) {
                \Phpcmf\Service::M('cache')->sync_cache(''); // 自动更新缓存
                $this->_json(1, dr_lang('操作成功'));
            } else {
                $this->_json(0, $rt['msg']);
            }
		}

		\Phpcmf\Service::V()->assign([
			'top' => $top,
			'type' => $pid,
			'form' => dr_form_hidden()
		]);
		\Phpcmf\Service::V()->display('min_menu_add.html');
		exit;
	}

	public function edit() {

		$id = intval(\Phpcmf\Service::L('input')->get('id'));
		$data = \Phpcmf\Service::M('Menu')->getRowData('admin_min', $id);
		!$data && $this->_json(0, dr_lang('数据#%s不存在', $id));

		$top = \Phpcmf\Service::M('Menu')->get_top('admin_min');

		if (IS_AJAX_POST) {
			$data = \Phpcmf\Service::L('input')->post('data');
			$this->_validation($data);
			if ($data['uri']
                && \Phpcmf\Service::M()->table('admin_min_menu')->where('id<>'.$id)->where('uri', $data['uri'])->counts()) {
			    // 链接菜单判断重复
                $this->_json(0, dr_lang('系统路径已经存在'), ['field' => 'uri']);
            }
			\Phpcmf\Service::M('Menu')->_update('admin_min', $id, $data);
			\Phpcmf\Service::L('input')->system_log('修改简化菜单: '.$data['name']);

            \Phpcmf\Service::M('cache')->sync_cache(''); // 自动更新缓存
			exit($this->_json(1, dr_lang('操作成功')));
		}

		\Phpcmf\Service::V()->assign([
			'top' => $top,
			'type' => $data['pid'],
			'data' => $data,
			'form' => dr_form_hidden(),
		]);
		\Phpcmf\Service::V()->display('min_menu_add.html');
		exit;
	}

	public function del() {

		$ids = \Phpcmf\Service::L('input')->get_post_ids();
		!$ids && exit($this->_json(0, dr_lang('你还没有选择呢')));

		\Phpcmf\Service::M('Menu')->_delete('admin_min', $ids);
        \Phpcmf\Service::M('cache')->sync_cache(''); // 自动更新缓存
		\Phpcmf\Service::L('input')->system_log('批量删除简化菜单: '. @implode(',', $ids));
		exit($this->_json(1, dr_lang('操作成功'), ['ids' => $ids]));
	}


	// 初始化
	public function init() {

		\Phpcmf\Service::M('Menu')->init('admin_min');
		\Phpcmf\Service::L('input')->system_log('初始化简化菜单');
		exit($this->_json(1, dr_lang('初始化菜单成功，请按F5刷新整个页面')));
	}

	// 隐藏或者启用
	public function use_edit() {

		$i = intval(\Phpcmf\Service::L('input')->get('id'));
		$v = \Phpcmf\Service::M('Menu')->_uesd('admin_min', $i);
		$v == -1 && exit($this->_json(0, dr_lang('数据#%s不存在', $i), ['value' => $v]));
		\Phpcmf\Service::L('input')->system_log('修改简化菜单状态: '. $i);
        \Phpcmf\Service::M('cache')->sync_cache(''); // 自动更新缓存
		exit($this->_json(1, dr_lang($v ? '此菜单已被隐藏' : '此菜单已被启用'), ['value' => $v]));

	}

	// 保存数据
	public function save_edit() {

		$i = intval(\Phpcmf\Service::L('input')->get('id'));
		\Phpcmf\Service::M('Menu')->_save(
			'admin_min',
			$i,
			dr_safe_replace(\Phpcmf\Service::L('input')->get('name')),
			dr_safe_replace(\Phpcmf\Service::L('input')->get('value'))
		);

        \Phpcmf\Service::M('cache')->sync_cache(''); // 自动更新缓存
		\Phpcmf\Service::L('input')->system_log('修改简化菜单信息: '. $i);
		exit($this->_json(1, dr_lang('更改成功')));
	}


	// 验证数据
	private function _validation($data) {

		if ($data['type'] != 3) {
			// 非链接菜单时不录入url和uri
			unset($this->form['url'], $this->form['uri']);
		} else {
			// url和uri 只验证一个
			if ($data['url']) unset($this->form['uri']);
			if ($data['uri']) unset($this->form['url']);
		}

		if ($data['mark']) {
            list($a, $b) = explode('-', $data['mark']);
            if ($a == 'module' && !is_dir(dr_get_app_dir($b))) {
                $this->_json(0, dr_lang('模块[%s]不存在', $b), ['field' => 'mark']);
            }
        }


		list($data, $return) = \Phpcmf\Service::L('form')->validation($data, $this->form);
		$return && exit($this->_json(0, $return['error'], ['field' => $return['name']]));

	}

}
