<?php namespace Phpcmf\Control\Admin;
/**
 * {{www.xunruicms.com}}
 * {{迅睿内容管理框架系统}}
 * 本文件是框架系统文件，二次开发时不可以修改本文件
 **/

class Role extends \Phpcmf\Common {

	private $form; // 表单验证配置
	
	public function __construct() {
		parent::__construct();
        // 不是超级管理员
        if (!dr_in_array(1, $this->admin['roleid'])) {
            $this->_admin_msg(0, dr_lang('需要超级管理员账号操作'));
        }
		\Phpcmf\Service::V()->assign('menu', \Phpcmf\Service::M('auth')->_admin_menu(
			[
				'角色权限' => ['role/index', 'fa fa-users'],
				'添加' => ['add:role/add', 'fa fa-plus', '500px', '400px'],
				'权限划分' => ['hide:role/auth_edit', 'fa fa-user-md'],
				'help' => ['824'],
			]
		));
		// 表单验证配置
		$this->form = [
			'name' => [
				'name' => '角色名称',
				'rule' => [
					'empty' => dr_lang('角色名称不能为空')
				],
				'filter' => [],
				'length' => '200'
			],
		];
	}

	public function index() {

		\Phpcmf\Service::V()->assign([
			'data' => \Phpcmf\Service::M('auth')->get_role_all(),
		]);
		\Phpcmf\Service::V()->display('role_index.html');
	}

	public function add() {

		if (IS_AJAX_POST) {
			$data = \Phpcmf\Service::L('input')->post('data');
			$this->_validation($data);
			\Phpcmf\Service::L('input')->system_log('添加角色组('.$data['name'].')');
			if (\Phpcmf\Service::M('auth')->add_role($data)) {
                \Phpcmf\Service::M('cache')->sync_cache('auth');
			    $this->_json(1, dr_lang('操作成功'));
            } else {
			    $this->_json(0, dr_lang('操作失败'));
            }
		}

		\Phpcmf\Service::V()->assign([
			'form' => dr_form_hidden()
		]);
		\Phpcmf\Service::V()->display('role_add.html');
		exit;
	}

	public function edit() {

		$id = intval(\Phpcmf\Service::L('input')->get('id'));
		$data = \Phpcmf\Service::M('auth')->get_role($id);
		if (!$data) {
		    $this->_json(0, dr_lang('数据#%s不存在', $id));
        }

		if (IS_AJAX_POST) {
			$temp = $post = \Phpcmf\Service::L('input')->post('data');
			$this->_validation($post);
			$post['application'] = $data['application'];
			$post['application']['tid'] = $temp['application']['tid'];
			$post['application']['mode'] = $temp['application']['mode'];
            $post['application']['verify'] = $temp['application']['verify'];
			\Phpcmf\Service::M('auth')->update_role($id, $post);
            \Phpcmf\Service::M('cache')->sync_cache('auth');
			\Phpcmf\Service::L('input')->system_log('修改角色组('.$post['name'].')');
			$this->_json(1, dr_lang('操作成功'));
		}

		\Phpcmf\Service::V()->assign([
		    'id' => $id,
			'data' => $data,
			'form' => dr_form_hidden(),
		]);
		\Phpcmf\Service::V()->display('role_add.html');
		exit;
	}

	// 复制动作
	public function copy_edit() {

		$id = intval(\Phpcmf\Service::L('input')->get('id'));
		$data = \Phpcmf\Service::M('auth')->get_role($id);
		if (!$data) {
		    $this->_json(0, dr_lang('数据#%s不存在', $id));
        }

		if (IS_AJAX_POST) {

			$post = \Phpcmf\Service::L('input')->post('data');
			if (!$post['ids']) {
                $this->_json(0, dr_lang('没有选择目标角色组'));
            } elseif (!$post['option']) {
                $this->_json(0, dr_lang('没有选择复制项目'));
            }

            $save = [];
			if (dr_in_array(1, $post['option'])) {
			    $save['site'] = dr_array2string($data['site']);
            }

            if (dr_in_array(2, $post['option'])) {
			    $save['system'] = dr_array2string($data['system']);
			    $save['module'] = dr_array2string($data['module']);
            }

			if (!$save) {
                $this->_json(0, dr_lang('没有选择复制项目'));
            }

			foreach ($post['ids'] as $rid) {
                \Phpcmf\Service::M('auth')->table('admin_role')->update($rid, $save);
            }

            \Phpcmf\Service::M('cache')->sync_cache('auth');
			\Phpcmf\Service::L('input')->system_log('修改角色组('.$data['name'].')到'.implode('、', $post['ids']));
			$this->_json(1, dr_lang('操作成功'));
		}

        \Phpcmf\Service::V()->assign([
            'id' => $id,
            'role' => \Phpcmf\Service::M('auth')->get_role_all()
        ]);
		\Phpcmf\Service::V()->display('role_copy.html');
		exit;
	}

	// 角色组权限，超级管理员有权限
	public function auth_edit() {

		$id = intval(\Phpcmf\Service::L('input')->get('id'));
		$data = \Phpcmf\Service::M('auth')->get_role($id);
		if (!$data) {
		    $this->_admin_msg(0, dr_lang('角色组（%s）不存在', $id));
        }
		
		if (IS_AJAX_POST) {
			$post = \Phpcmf\Service::L('input')->post('data');
			$module = \Phpcmf\Service::L('input')->post('module');
			\Phpcmf\Service::M('auth')->table('admin_role')->update($id, [
			    'system' => dr_array2string($post),
			    'module' => dr_array2string($module),
            ]);
            \Phpcmf\Service::M('cache')->sync_cache('auth');
			\Phpcmf\Service::L('input')->system_log('设置角色组('.$data['name'].')权限');
			$this->_json(1, dr_lang('操作成功'));
		}
		//print_r($data);
		#print_r($this->_M('Menu')->gets('admin'));

		$page = intval(\Phpcmf\Service::L('input')->get('page'));
        $module_auth = IS_USE_MODULE ? \Phpcmf\Service::M('module', 'module')->module_auth() : [];

		\Phpcmf\Service::V()->assign([
			'data' => $data,
			'page' => $page,
			'form' => dr_form_hidden(['page' => $page]),
			'menu_data' => \Phpcmf\Service::M('Menu')->gets('admin', 'mark<>\'cloud\''),
			'module_auth' => $module_auth,
		]);
		\Phpcmf\Service::V()->display('role_auth.html');
	}
	
	public function site_edit() {

		$id = intval(\Phpcmf\Service::L('input')->get('id'));
		$data = \Phpcmf\Service::M('auth')->get_role($id);
		if (!$data) {
		    $this->_admin_msg(0, dr_lang('角色组（%s）不存在', $id));
        }

		if (IS_AJAX_POST) {
			$post = \Phpcmf\Service::L('input')->post('data');
			\Phpcmf\Service::M('auth')->table('admin_role')->update($id, ['site' => dr_array2string($post)]);
            \Phpcmf\Service::M('cache')->sync_cache('auth');
			\Phpcmf\Service::L('input')->system_log('设置角色组('.$data['name'].')站点权限');
			$this->_json(1, dr_lang('操作成功'));
		}

		\Phpcmf\Service::V()->assign([
			'data' => $data,
			'form' => dr_form_hidden(),
		]);
		\Phpcmf\Service::V()->display('role_site.html');exit;
	}

	public function verify_edit() {

		$id = intval(\Phpcmf\Service::L('input')->get('id'));
		$data = \Phpcmf\Service::M('auth')->get_role($id);
		if (!$data) {
		    $this->_admin_msg(0, dr_lang('角色组（%s）不存在', $id));
        } elseif (!IS_USE_MEMBER) {
            $this->_admin_msg(0, dr_lang('需要安装用户系统插件，才能使用本功能'));
        }

        $verify = \Phpcmf\Service::M()->table('admin_verify')->getAll(30);

		if (IS_AJAX_POST) {
			$post = \Phpcmf\Service::L('input')->post('data');
			foreach ($verify as $v) {
                $rs = dr_string2array($v['verify']);
                if ($rs) {
                    foreach ($rs['role'] as $i => $t) {
                        if (isset($post[$v['id']]) && dr_in_array($i, $post[$v['id']])) {
                            // 表示选择的
                            if (!isset($rs['role'][$i])) {
                                $rs['role'][$i] = []; // 不存在时重新定义
                            } elseif (!is_array($rs['role'][$i]) && $rs['role'][$i]) {
                                if ($id == $rs['role'][$i]) {
                                    $rs['role'][$i] = []; // 值相同时
                                } else {
                                    $rs['role'][$i] = [
                                        (int)$rs['role'][$i]
                                    ]; // 老版本的单值
                                }
                            }
                            $rs['role'][$i][] = $id;
                        } else {
                            // 表示没选中
                            foreach ($t as $k => $kt) {
                                if ($id == $kt) {
                                    unset($rs['role'][$i][$k]);
                                }
                            }
                        }
                    }
                }
                \Phpcmf\Service::M()->table('admin_verify')->update($v['id'], [
                    'verify' => dr_array2string($rs)
                ]);
            }

            \Phpcmf\Service::M('cache')->sync_cache('');
			\Phpcmf\Service::L('input')->system_log('设置角色组('.$data['name'].')审核权限');
			$this->_json(1, dr_lang('操作成功'));
		}

		\Phpcmf\Service::V()->assign([
			'rid' => $id,
			'form' => dr_form_hidden(),
            'verify' => $verify,
		]);
		\Phpcmf\Service::V()->display('role_verify.html');exit;
	}

	public function del() {

		$ids = \Phpcmf\Service::L('input')->get_post_ids();
		if (!$ids) {
            $this->_json(0, dr_lang('你还没有选择呢'));
        } elseif (dr_in_array(1, $ids)) {
            $this->_json(0, dr_lang('超级管理员角色组不能删除'));
        }

		\Phpcmf\Service::M('auth')->delete_role($ids);
        \Phpcmf\Service::M('cache')->sync_cache('auth');
		\Phpcmf\Service::L('input')->system_log('批量删除角色组: '. @implode(',', $ids));

		$this->_json(1, dr_lang('操作成功'), ['ids' => $ids]);
	}
	
	// 验证数据
	private function _validation($data) {
		list($data, $return) = \Phpcmf\Service::L('Form')->validation($data, $this->form);
		if ($return) {
            $this->_json(0, $return['error'], ['field' => $return['name']]);
		}
	}

}
