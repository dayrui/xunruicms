<?php namespace Phpcmf\Controllers\Admin;

/**
 * http://www.xunruicms.com
 * 本文件是框架系统文件，二次开发时不可以修改本文件
 **/

class Role extends \Phpcmf\Common
{
	private $form; // 表单验证配置
	
	public function __construct(...$params) {
		parent::__construct(...$params);
		\Phpcmf\Service::V()->assign('menu', \Phpcmf\Service::M('auth')->_admin_menu(
			[
				'角色权限' => ['role/index', 'fa fa-users'],
				'添加' => ['add:role/add', 'fa fa-plus', '500px', '400px'],
				'权限划分' => ['hide:role/edit_auth', 'fa fa-user-md'],
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
			    exit($this->_json(1, dr_lang('操作成功')));
            } else {
			    exit($this->_json(0, dr_lang('操作失败')));
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
			\Phpcmf\Service::M('auth')->update_role($id, $post);
            \Phpcmf\Service::M('cache')->sync_cache('auth');
			\Phpcmf\Service::L('input')->system_log('修改角色组('.$post['name'].')');
			exit($this->_json(1, dr_lang('操作成功')));
		}

		\Phpcmf\Service::V()->assign([
		    'id' => $id,
			'data' => $data,
			'form' => dr_form_hidden(),
		]);
		\Phpcmf\Service::V()->display('role_add.html');
		exit;
	}

	// 角色组权限，超级管理员有权限
	public function edit_auth() {

		$id = intval(\Phpcmf\Service::L('input')->get('id'));
		$data = \Phpcmf\Service::M('auth')->get_role($id);
		if (!$data) {
		    $this->_admin_msg(0, dr_lang('角色组（%s）不存在', $id));
        }
		
		if (IS_AJAX_POST) {
			$data = \Phpcmf\Service::L('input')->post('data');
			$module = \Phpcmf\Service::L('input')->post('module');
			\Phpcmf\Service::M('auth')->table('admin_role')->update($id, [
			    'system' => dr_array2string($data),
			    'module' => dr_array2string($module),
            ]);
            \Phpcmf\Service::M('cache')->sync_cache('auth');
			\Phpcmf\Service::L('input')->system_log('设置角色组('.$data['name'].')权限');
			exit($this->_json(1, dr_lang('操作成功')));
		}
		//print_r($data);
		#print_r($this->_M('Menu')->gets('admin'));

		$page = intval(\Phpcmf\Service::L('input')->get('page'));
        $module = \Phpcmf\Service::M()->db->table('module')->get()->getResultArray();
        $module_auth = [];
        if ($module) {
            foreach ($module as $t) {
                $mdir = $t['dirname'];
                if (!is_file(APPSPATH.ucfirst($mdir).'/Config/App.php')) {
                    continue;
                }
                $config = require APPSPATH.ucfirst($mdir).'/Config/App.php';
                $module_auth[$mdir] = [
                    'name' => dr_lang($config['name']),
                    'auth' => [
                        $mdir.'/comment/' => dr_lang('评论'),
                    ],
                ];
                if ($config['system']) {
                    // 内容模块
                    $module_auth[$mdir]['auth'][$mdir.'/draft/'] = dr_lang('草稿箱');
                    $module_auth[$mdir]['auth'][$mdir.'/recycle/'] = dr_lang('回收站');
                    $module_auth[$mdir]['auth'][$mdir.'/time/'] = dr_lang('定时发布');
                } else {
                    // 自定义模块
                }
                $mform = \Phpcmf\Service::M()->db->table('module_form')->where('module', $mdir)->get()->getResultArray();
                if ($mform) {
                    foreach ($mform as $c) {
                        $module_auth[$mdir]['auth'][$mdir.'/'.$c['table'].'/'] = dr_lang($c['name']);
                    }
                }
            }
        }

		\Phpcmf\Service::V()->assign([
			'data' => $data,
			'page' => $page,
			'form' => dr_form_hidden(['page' => $page]),
			'menu_data' => \Phpcmf\Service::M('Menu')->gets('admin'),
			'module_auth' => $module_auth,
		]);
		\Phpcmf\Service::V()->display('role_auth.html');
	}
	
	public function edit_site() {

		$id = intval(\Phpcmf\Service::L('input')->get('id'));
		$data = \Phpcmf\Service::M('auth')->get_role($id);
		if (!$data) {
		    $this->_admin_msg(0, dr_lang('角色组（%s）不存在', $id));
        }
		
		if (IS_AJAX_POST) {
			$data = \Phpcmf\Service::L('input')->post('data');
			\Phpcmf\Service::M('auth')->table('admin_role')->update($id, ['site' => dr_array2string($data)]);
            \Phpcmf\Service::M('cache')->sync_cache('auth');
			\Phpcmf\Service::L('input')->system_log('设置角色组('.$data['name'].')站点权限');
			exit($this->_json(1, dr_lang('操作成功')));
		}

		\Phpcmf\Service::V()->assign([
			'data' => $data,
			'form' => dr_form_hidden(),
		]);
		\Phpcmf\Service::V()->display('role_site.html');exit;
	}

	public function del() {

		$ids = \Phpcmf\Service::L('input')->get_post_ids();
		if (!$ids) {
            $this->_json(0, dr_lang('你还没有选择呢'));
        } elseif (in_array(1, $ids)) {
            $this->_json(0, dr_lang('超级管理员角色组不能删除'));
        }

		\Phpcmf\Service::M('auth')->delete_role($ids);
        \Phpcmf\Service::M('cache')->sync_cache('auth');
		\Phpcmf\Service::L('input')->system_log('批量删除角色组: '. @implode(',', $ids));

		exit($this->_json(1, dr_lang('操作成功'), ['ids' => $ids]));
	}
	
	// 验证数据
	private function _validation($data) {

		list($data, $return) = \Phpcmf\Service::L('Form')->validation($data, $this->form);
		$return && exit($this->_json(0, $return['error'], ['field' => $return['name']]));

	}

}
