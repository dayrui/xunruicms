<?php namespace Phpcmf\Control\Admin;
/**
 * {{www.xunruicms.com}}
 * {{迅睿内容管理框架系统}}
 * 本文件是框架系统文件，二次开发时不可以修改本文件
 **/

class Min_menu extends \Phpcmf\Common {

	private $form; // 表单验证配置

	public function __construct() {
		parent::__construct();
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
			'data' => \Phpcmf\Service::M('Menu')->gets('admin_min'),
			'color' => ['blue', 'red', 'green', 'dark', 'yellow'],
		]);
		\Phpcmf\Service::V()->display('min_menu_list.html');
	}

	public function add() {

		$pid = intval(\Phpcmf\Service::L('input')->get('pid'));
		$top = \Phpcmf\Service::M('Menu')->get_top('admin_min');

		if (IS_AJAX_POST) {
			$data = \Phpcmf\Service::L('input')->post('data');
			if ($pid) {
			    if (!$data['id']) {
			        $this->_json(0, dr_lang('没有选择菜单'));
                }
                $menu = \Phpcmf\Service::M('Menu')->getRowData('admin', $data['id']);
                if (!$menu) {
                    $this->_json(0, dr_lang('所选菜单不存在'));
                }
                $data = [
                    'pid' => $pid,
                    'name' => $menu['name'],
                    'mark' => $menu['mark'],
                    'icon' => $menu['icon'],
                    'uri' => $menu['uri'],
                    'url' => $menu['url'],
                    'displayorder' => $data['displayorder'],
                ];
            }
            $data = $this->_validation($data);
			\Phpcmf\Service::L('input')->system_log('添加简化菜单: '.$data['name']);
            $rt = \Phpcmf\Service::M('Menu')->_add('admin_min', $pid, $data);
            if ($rt['code']) {
                \Phpcmf\Service::M('cache')->sync_cache(''); // 自动更新缓存
                $this->_json(1, dr_lang('操作成功'));
            } else {
                $this->_json(0, $rt['msg']);
            }
		}

        $select = '<select class="form-control" name="data[id]">';
        $select.= '<option value="0"> -- </option>';
        $topdata = \Phpcmf\Service::M()->table('admin_menu')->where('pid=0')->order_by('displayorder ASC,id ASC')->getAll();
        foreach ($topdata as $t) {
            $leftdata = \Phpcmf\Service::M()->table('admin_menu')->where('pid='.$t['id'])->order_by('displayorder ASC,id ASC')->getAll();
            $is_left = '';
            foreach ($leftdata as $c) {
                $linkdata = \Phpcmf\Service::M()->table('admin_menu')->where('pid='.$c['id'])->order_by('displayorder ASC,id ASC')->getAll();
                $is_link = '';
                if ($linkdata) {
                    foreach ($linkdata as $k) {
                        if ($k['uri'] && !$this->_is_admin_auth($k['uri'])) {
                            continue;
                        }
                        $is_link.= '<option value="'.$k['id'].'">&nbsp;&nbsp;&nbsp;└ '.$k['name'].'</option>';
                    }
                }
                if ($is_link) {
                    $is_left.= '<optgroup label=" └ '.$c['name'].'">';
                    $is_left.= $is_link;
                    $is_left.= '</optgroup>';
                }
            }
            if ($is_left) {
                $select.= '<optgroup label="'.$t['name'].'">';
                $select.= $is_left;
                $select.= '</optgroup>';
            }
        }
        $select.= '</select>';

		\Phpcmf\Service::V()->assign([
			'top' => $top,
			'type' => $pid,
			'form' => dr_form_hidden(),
            'select' => $select
		]);
		\Phpcmf\Service::V()->display('min_menu_add.html');
		exit;
	}

	public function edit() {

		$id = intval(\Phpcmf\Service::L('input')->get('id'));
		$data = \Phpcmf\Service::M('Menu')->getRowData('admin_min', $id);
		if (!$data) {
		    $this->_json(0, dr_lang('数据#%s不存在', $id));
        }

		$top = \Phpcmf\Service::M('Menu')->get_top('admin_min');

		if (IS_AJAX_POST) {
			$data = \Phpcmf\Service::L('input')->post('data');
			$data = $this->_validation($data);
			if ($data['uri']
                && \Phpcmf\Service::M()->table('admin_min_menu')->where('id<>'.$id)->where('uri', $data['uri'])->counts()) {
			    // 链接菜单判断重复
                $this->_json(0, dr_lang('系统路径已经存在'), ['field' => 'uri']);
            }
			\Phpcmf\Service::M('Menu')->_update('admin_min', $id, $data);
			\Phpcmf\Service::L('input')->system_log('修改简化菜单: '.$data['name']);

            \Phpcmf\Service::M('cache')->sync_cache(''); // 自动更新缓存
			$this->_json(1, dr_lang('操作成功'));
		}

        if ($data['pid']) {
            $select = '<select class="form-control" name="data[pid]">';
            $topdata = \Phpcmf\Service::M()->table('admin_min_menu')->where('pid=0')->order_by('displayorder ASC,id ASC')->getAll();
            foreach ($topdata as $t) {
                $select.= '<option value="'.$t['id'].'" '.($data['pid'] == $t['id'] ? 'selected' : '').'>'.$t['name'].'</option>';
            }
            $select.= '</select>';
        } else {
            $select = '<input type="hidden" value="0" name="data[pid]" /><div class="form-control-static"><label>'.dr_lang('顶级').'</label></div>';
        }


		\Phpcmf\Service::V()->assign([
			'top' => $top,
			'type' => $data['pid'],
			'data' => $data,
			'form' => dr_form_hidden(),
            'select' => $select
		]);
		\Phpcmf\Service::V()->display('min_menu_edit.html');
		exit;
	}

	public function del() {

		$ids = \Phpcmf\Service::L('input')->get_post_ids();
		if (!$ids) {
            $this->_json(0, dr_lang('你还没有选择呢'));
        }

		\Phpcmf\Service::M('Menu')->_delete('admin_min', $ids);
        \Phpcmf\Service::M('cache')->sync_cache(''); // 自动更新缓存
		\Phpcmf\Service::L('input')->system_log('批量删除简化菜单: '. implode(',', $ids));
		$this->_json(1, dr_lang('操作成功'), ['ids' => $ids]);
	}


	// 初始化
	public function init() {

		\Phpcmf\Service::M('Menu')->init('admin_min');
		\Phpcmf\Service::L('input')->system_log('初始化简化菜单');
        \Phpcmf\Service::M('cache')->update_cache(); // 自动更新缓存
		$this->_json(1, dr_lang('初始化菜单成功，请按F5刷新整个页面'));
	}

	// 隐藏或者启用
	public function use_edit() {

		$i = intval(\Phpcmf\Service::L('input')->get('id'));
		$v = \Phpcmf\Service::M('Menu')->_uesd('admin_min', $i);
		if ($v == -1) {
            $this->_json(0, dr_lang('数据#%s不存在', $i), ['value' => $v]);
        }
		\Phpcmf\Service::L('input')->system_log('修改简化菜单状态: '. $i);
        \Phpcmf\Service::M('cache')->sync_cache(''); // 自动更新缓存
		$this->_json(1, dr_lang($v ? '此菜单已被隐藏' : '此菜单已被启用'), ['value' => $v]);

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

		$this->_json(1, dr_lang('更改成功'));
	}


	// 验证数据
	private function _validation($data) {

		if (!$data['pid']) {
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

        // 顶级验证mark
		if (!$data['pid']) {
		    if (!$data['mark']) {
                $this->_json(0, dr_lang('标识字符不能为空'), ['field' => 'mark']);
            }
            /*
            elseif (!\Phpcmf\Service::M()->table('admin_menu')->where('mark', $data['mark'])->counts()) {
                $this->_json(0, dr_lang('标识字符没有存在于完整菜单中'), ['field' => 'mark']);
            }*/
        }

        if ($data['uri'] && !\Phpcmf\Service::M()->table('admin_menu')->where('uri', $data['uri'])->counts()) {
            // 验证是否操作员完整菜单中
            $this->_json(0, dr_lang('系统路径没有存在于完整菜单中'), ['field' => 'uri']);
        }

		if ($data['mark']) {
            list($a, $b) = explode('-', $data['mark']);
            if ($a == 'module' && !is_dir(dr_get_app_dir($b))) {
                $this->_json(0, dr_lang('模块[%s]不存在', $b), ['field' => 'mark']);
            }
        }

		list($data, $return) = \Phpcmf\Service::L('form')->validation($data, $this->form);
		if ($return) {
            $this->_json(0, $return['error'], ['field' => $return['name']]);
        }

        $data['uri'] = strtolower((string)$data['uri']);
        return $data;
	}

}
