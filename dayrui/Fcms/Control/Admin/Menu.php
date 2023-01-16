<?php namespace Phpcmf\Control\Admin;
/**
 * {{www.xunruicms.com}}
 * {{迅睿内容管理框架系统}}
 * 本文件是框架系统文件，二次开发时不可以修改本文件
 **/

class Menu extends \Phpcmf\Common
{
	private $form; // 表单验证配置
	
	public function __construct() {
		parent::__construct();
		\Phpcmf\Service::V()->assign('menu', \Phpcmf\Service::M('auth')->_admin_menu(
			[
				'后台菜单' => ['menu/index', 'fa fa-list-alt'],
				'初始化菜单' => ['ajax:menu/init', 'fa fa-refresh'],
				'help' => [927],
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
				'length' => '100'
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
			'data' => \Phpcmf\Service::M('menu')->gets('admin'),
            'color' => ['blue', 'red', 'green', 'dark', 'yellow'],
		]);
		\Phpcmf\Service::V()->display('menu_index.html');
	}

	public function add() {

		$pid = intval(\Phpcmf\Service::L('input')->get('pid'));
		$top = \Phpcmf\Service::M('menu')->get_top('admin');
		$type = $pid ? (isset($top[$pid]) ? 2 : 3) : 1;

		if (IS_AJAX_POST) {
			$data = \Phpcmf\Service::L('input')->post('data');
			$data = $this->_validation($type, $data);
            if ($data['uri'] && \Phpcmf\Service::M()->table('admin_menu')->where('uri', $data['uri'])->counts()) {
                // 链接菜单判断重复
                $this->_json(0, dr_lang('系统路径已经存在'), ['field' => 'uri']);
            }
			$rt = \Phpcmf\Service::M('menu')->_add('admin', $pid, $data);
			if ($rt['code']) {
                \Phpcmf\Service::M('cache')->sync_cache(''); // 自动更新缓存
                \Phpcmf\Service::L('input')->system_log('添加后台菜单: '.$data['name']);
                $this->_json(1, dr_lang('操作成功'));
            } else {
                $this->_json(0, $rt['msg']);
            }
		}

		\Phpcmf\Service::V()->assign([
			'type' => $type,
			'form' => dr_form_hidden()
		]);
		\Phpcmf\Service::V()->display('menu_add.html');
		exit;
	}

    public function site_add() {

        $ids = \Phpcmf\Service::L('input')->get_post_ids();
        if (!$ids) {
            $this->_json(0, dr_lang('你还没有选择呢'));
        }

        $data = \Phpcmf\Service::M()->db->table('admin_menu')->whereIN('id', $ids)->get()->getResultArray();
        if (!$data) {
            $this->_json(0, dr_lang('无可用菜单'));
        }

        $sids = \Phpcmf\Service::L('input')->post('siteid');
        if (!$sids) {
            foreach ($data as $t) {
                \Phpcmf\Service::M()->db->table('admin_menu')->where('id', $t['id'])->update([
                    'site' => ''
                ]);
            }
            $this->_json(1, dr_lang('取消成功'));
        }

        foreach ($data as $t) {
            $value = dr_string2array($t['site']);
            foreach ($sids as $sid) {
                $value[$sid] = $sid;
            }
            \Phpcmf\Service::M()->db->table('admin_menu')->where('id', $t['id'])->update([
                'site' => dr_array2string($value)
            ]);
        }

        \Phpcmf\Service::M('cache')->sync_cache(''); // 自动更新缓存
        $this->_json(1, dr_lang('划分成功'));
    }

    public function site_del() {

        $id = (int)\Phpcmf\Service::L('input')->get('id');
        $data = \Phpcmf\Service::M('menu')->getRowData('admin', $id);
        if (!$data) {
            $this->_json(0, dr_lang('菜单不存在'));
        }

        $sid = (int)\Phpcmf\Service::L('input')->get('sid');
        if (!$sid) {
            $this->_json(0, dr_lang('站点id不存在'));
        }

        $value = dr_string2array($data['site']);
        unset($value[$sid]);

        \Phpcmf\Service::M()->db->table('admin_menu')->where('id', $id)->update([
            'site' => dr_array2string($value)
        ]);

        \Phpcmf\Service::M('cache')->sync_cache(''); // 自动更新缓存
        $this->_json(1, dr_lang('删除成功'));
    }

	public function edit() {

		$id = intval(\Phpcmf\Service::L('input')->get('id'));
		$data = \Phpcmf\Service::M('menu')->getRowData('admin', $id);
		if (!$data) {
		    $this->_json(0, dr_lang('数据#%s不存在', $id));
        }

		$pid = intval($data['pid']);
		$top = \Phpcmf\Service::M('menu')->get_top('admin');
		$type = $pid ? (isset($top[$pid]) ? 2 : 3) : 1;

		if (IS_AJAX_POST) {
			$data = \Phpcmf\Service::L('input')->post('data');
			$data = $this->_validation($type, $data);
            if ($data['uri'] && \Phpcmf\Service::M()->table('admin_menu')->where('id<>'.$id)->where('uri', $data['uri'])->counts()) {
                // 链接菜单判断重复
                $this->_json(0, dr_lang('系统路径已经存在'), ['field' => 'uri']);
            }
			\Phpcmf\Service::M('menu')->_update('admin', $id, $data);
            \Phpcmf\Service::M('cache')->sync_cache(''); // 自动更新缓存
			\Phpcmf\Service::L('input')->system_log('修改后台菜单: '.$data['name']);
			$this->_json(1, dr_lang('操作成功'));
		}

		\Phpcmf\Service::V()->assign([
			'type' => $type,
			'data' => $data,
			'form' => dr_form_hidden(),
			'select' => \Phpcmf\Service::M('Menu')->parent_select('admin', $type, $pid)
		]);
		\Phpcmf\Service::V()->display('menu_add.html');
		exit;
	}

	public function del() {

		$ids = \Phpcmf\Service::L('input')->get_post_ids();
		if (!$ids) {
		    $this->_json(0, dr_lang('你还没有选择呢'));
        }

		\Phpcmf\Service::M('menu')->_delete('admin', $ids);
        \Phpcmf\Service::M('cache')->sync_cache(''); // 自动更新缓存
		\Phpcmf\Service::L('input')->system_log('批量删除后台菜单: '. implode(',', $ids));
		$this->_json(1, dr_lang('操作成功'), ['ids' => $ids]);
	}

	// 初始化
	public function init() {

		\Phpcmf\Service::M('menu')->init('admin');

		\Phpcmf\Service::L('input')->system_log('初始化后台菜单');
        \Phpcmf\Service::M('cache')->update_cache(); // 自动更新缓存

		$this->_json(1, dr_lang('初始化菜单成功，请按F5刷新整个页面'));
	}

	// 隐藏或者启用
	public function use_edit() {

		$i = intval(\Phpcmf\Service::L('input')->get('id'));
		$v = \Phpcmf\Service::M('menu')->_uesd('admin', $i);
		if ($v == -1) {
		    $this->_json(0, dr_lang('数据#%s不存在', $i), ['value' => $v]);
        }
        \Phpcmf\Service::M('cache')->sync_cache(''); // 自动更新缓存
		\Phpcmf\Service::L('input')->system_log('修改后台菜单状态: '. $i);
		$this->_json(1, dr_lang($v ? '此菜单已被隐藏' : '此菜单已被启用'), ['value' => $v]);

	}

	// 保存数据
	public function save_edit() {

		$i = intval(\Phpcmf\Service::L('input')->get('id'));
		\Phpcmf\Service::M('menu')->_save(
			'admin',
			$i,
			dr_safe_replace(\Phpcmf\Service::L('input')->get('name')),
			dr_safe_replace(\Phpcmf\Service::L('input')->get('value'))
		);

        \Phpcmf\Service::M('cache')->sync_cache(''); // 自动更新缓存
		\Phpcmf\Service::L('input')->system_log('修改后台菜单信息: '. $i);
		$this->_json(1, dr_lang('更改成功'));
	}


	// 验证数据
	private function _validation($type, $data) {

		if ($type != 3) {
			// 非链接菜单时不录入url和uri
			unset($this->form['url'], $this->form['uri']);
		} else {
			// url和uri 只验证一个
			if ($data['url']) {
			    unset($this->form['uri']);
            }
			if ($data['uri']) {
			    unset($this->form['url']);
            }
		}

		//$type是菜单级别 1 2 3
        if (in_array($type, [2, 1]) && !$data['mark']) {
             $this->_json(0, dr_lang('标识字符不能为空'), ['field' => 'mark']);
        }

		list($data, $return) = \Phpcmf\Service::L('form')->validation($data, $this->form);

        if ($return) {
            $this->_json(0, $return['error'], ['field' => $return['name']]);
        }

        $data['uri'] = strtolower((string)$data['uri']);
        return $data;
	}

}
