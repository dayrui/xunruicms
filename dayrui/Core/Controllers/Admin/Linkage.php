<?php namespace Phpcmf\Controllers\Admin;

/**
 * http://www.xunruicms.com
 * 本文件是框架系统文件，二次开发时不可以修改本文件
 **/

// 联动菜单
class Linkage extends \Phpcmf\Common
{

	public function index() {

		\Phpcmf\Service::V()->assign([
			'list' => \Phpcmf\Service::M('Linkage')->table('linkage')->getAll(),
			'dt_data' => [
                1 => '导入省级',
                2 => '导入省市',
                3 => '导入省市县',
            ],
            'menu' =>  \Phpcmf\Service::M('auth')->_admin_menu(
                [
                    '联动菜单' => ['linkage/index', 'fa fa-columns'],
                    '创建菜单' => ['add:linkage/add', 'fa fa-plus'],
                    '修改' => ['hide:linkage/edit', 'fa fa-edit'],
                    '数据管理' => ['hide:linkage/list_index', 'fa fa-table'],
                    'help' => [354],
                ]
            ),
		]);
		\Phpcmf\Service::V()->display('linkage_index.html');
	}

	public function add() {

		if (IS_AJAX_POST) {
			$data = \Phpcmf\Service::L('input')->post('data', true);
			$this->_validation(0, $data);
			\Phpcmf\Service::L('input')->system_log('创建联动菜单('.$data['name'].')');
			$rt = \Phpcmf\Service::M('Linkage')->create($data);
			if (!$rt['code']) {
			    $this->_json(0, $rt['msg']);
            }
            \Phpcmf\Service::M('cache')->sync_cache('linkage', '', 1); // 自动更新缓存
			exit($this->_json(1, dr_lang('操作成功')));
		}

		\Phpcmf\Service::V()->assign([
			'form' => dr_form_hidden()
		]);
		\Phpcmf\Service::V()->display('linkage_add.html');
		exit;
	}

	public function edit() {

		$id = intval(\Phpcmf\Service::L('input')->get('id'));
		$data = \Phpcmf\Service::M('Linkage')->table('linkage')->get($id);
		if (!$data) {
		    $this->_admin_msg(0, dr_lang('联动菜单（%s）不存在', $id));
        }

		if (IS_AJAX_POST) {
			$data = \Phpcmf\Service::L('input')->post('data');
			$this->_validation($id, $data);
			$rt = \Phpcmf\Service::M('Linkage')->table('linkage')->update($id, $data);
			if (!$rt['code']) {
			    $this->_json(0, $rt['msg']);
            }
			\Phpcmf\Service::L('input')->system_log('修改联动菜单('.$data['name'].')');
            \Phpcmf\Service::M('cache')->sync_cache('linkage', '', 1); // 自动更新缓存
			exit($this->_json(1, dr_lang('操作成功')));
		}

		\Phpcmf\Service::V()->assign([
			'data' => $data,
			'form' => dr_form_hidden()
		]);
		\Phpcmf\Service::V()->display('linkage_add.html');exit;
	}

	public function import() {
		
		$id = (int)\Phpcmf\Service::L('input')->get('id');
		$code = (int)\Phpcmf\Service::L('input')->get('code');
		if (!is_file(APPPATH.'Config/Linkage/'.$code.'.php')) {
		    $this->_json(0, dr_lang('数据文件不存在无法导入'));
        }

		// 清空数据
		$table = 'linkage_data_'.$id;
		\Phpcmf\Service::M('Linkage')->query('TRUNCATE `'.\Phpcmf\Service::M('Linkage')->dbprefix($table).'`');
		$count = 0;

		// 开始导入
		$data = require APPPATH.'Config/Linkage/'.$code.'.php';
		foreach ($data as $t) {
			$rt = \Phpcmf\Service::M('Linkage')->table($table)->insert($t);
			if ($rt['code']) {
				$count++;
			}
		}

        \Phpcmf\Service::M('cache')->sync_cache('linkage', '', 1); // 自动更新缓存
		$this->_json(1, dr_lang('共%s条数据，导入成功%s条', dr_count($data), $count));
	}
	
	
	public function del() {

		$ids = \Phpcmf\Service::L('input')->get_post_ids();
		if (!$ids) {
		    exit($this->_json(0, dr_lang('你还没有选择呢')));
        }

		$rt = \Phpcmf\Service::M('Linkage')->delete_all($ids);
		if (!$rt['code']) {
		    exit($this->_json(0, $rt['msg']));
        }

        \Phpcmf\Service::M('cache')->sync_cache('linkage', '', 1); // 自动更新缓存
		\Phpcmf\Service::L('input')->system_log('批量联动菜单: '. @implode(',', $ids));

		exit($this->_json(1, dr_lang('操作成功'), ['ids' => $ids]));
	}

	// 验证数据
	private function _validation($id, $data) {
		// 表单验证配置
		$config = [
			'name' => [
				'name' => '名称',
				'rule' => [
					'empty' => dr_lang('名称不能为空')
				],
				'filter' => [],
				'length' => '200'
			],
			'code' => [
				'name' => '别名',
				'rule' => [
					'empty' => dr_lang('别名不能为空'),
					'table' => dr_lang('别名格式不正确'),
				],
				'filter' => [],
				'length' => '200'
			],
		];
		list($data, $return) = \Phpcmf\Service::L('Form')->validation($data, $config);
		$return && exit($this->_json(0, $return['error'], ['field' => $return['name']]));
		\Phpcmf\Service::M('Linkage')->table('linkage')->is_exists($id, 'code', $data['code']) && exit($this->_json(0, dr_lang('别名已经存在'), ['field' => 'code']));
	}

	public function displayorder_edit() {

		// 查询数据
		$id = (int)\Phpcmf\Service::L('input')->get('id');
		$key = (int)\Phpcmf\Service::L('input')->get('key');
		$row = \Phpcmf\Service::M('Linkage')->table('linkage_data_'.$key)->get($id);
		if (!$row) {
		    $this->_json(0, dr_lang('数据#%s不存在', $id));
        }

		$value = (int)\Phpcmf\Service::L('input')->get('value');
		$rt = \Phpcmf\Service::M('Linkage')->table('linkage_data_'.$key)->save($id, 'displayorder', $value);
		if (!$rt['code']) {
		    $this->_json(0, $rt['msg']);
        }

        \Phpcmf\Service::M('cache')->sync_cache('linkage', '', 1); // 自动更新缓存
		\Phpcmf\Service::L('input')->system_log('修改联动菜单值('.$row['name'].')的排序值为'.$value);
		$this->_json(1, dr_lang('操作成功'));
	}

	// 禁用或者启用
	public function hidden_edit() {

		$id = (int)\Phpcmf\Service::L('input')->get('id');
		$key = (int)\Phpcmf\Service::L('input')->get('key');
		$row = \Phpcmf\Service::M('Linkage')->table('linkage_data_'.$key)->get($id);
		if (!$row) {
		    $this->_json(0, dr_lang('数据#%s不存在', $id));
        }

		$i = intval(\Phpcmf\Service::L('input')->get('id'));
		$v = $row['hidden'] ? 0 : 1;
		\Phpcmf\Service::M('Linkage')->table('linkage_data_'.$key)->update($id, ['hidden' => $v]);

        \Phpcmf\Service::M('cache')->sync_cache('linkage', '', 1); // 自动更新缓存
		\Phpcmf\Service::L('input')->system_log('修改联动菜单状态: '. $i);
		exit($this->_json(1, dr_lang($v ? '此菜单已被禁用' : '此菜单已被启用'), ['value' => $v]));

	}

	public function list_del() {

		$ids = \Phpcmf\Service::L('input')->get_post_ids();
		$key = (int)\Phpcmf\Service::L('input')->get('key');
		if (!$ids) {
		    exit($this->_json(0, dr_lang('你还没有选择呢')));
        }

		$rt = \Phpcmf\Service::M('Linkage')->delete_list_all($key, $ids);
		if (!$rt['code']) {
		    exit($this->_json(0, $rt['msg']));
        }

        \Phpcmf\Service::M('cache')->sync_cache('linkage', '', 1); // 自动更新缓存
		\Phpcmf\Service::L('input')->system_log('批量删除联动菜单: '. @implode(',', $ids));

		exit($this->_json(1, dr_lang('操作成功'), ['ids' => $ids]));
	}

	public function pid_edit() {

		$ids = \Phpcmf\Service::L('input')->get_post_ids();
		$key = (int)\Phpcmf\Service::L('input')->get('key');
		$pid = (int)\Phpcmf\Service::L('input')->post('pid');
		if (!$ids) {
		    exit($this->_json(0, dr_lang('你还没有选择呢')));
        }

		$rt = \Phpcmf\Service::M('Linkage')->edit_pid_all($key, $pid, $ids);
		if (!$rt['code']) {
		    exit($this->_json(0, $rt['msg']));
        }

        \Phpcmf\Service::M('cache')->sync_cache('linkage', '', 1); // 自动更新缓存
		\Phpcmf\Service::L('input')->system_log('批量更改联动菜单分类: '. @implode(',', $ids));

		exit($this->_json(1, dr_lang('操作成功'), ['ids' => $ids]));
	}

	public function list_index() {

		$key = (int)\Phpcmf\Service::L('input')->get('key');
		$pid = (int)\Phpcmf\Service::L('input')->get('pid');

		$link = \Phpcmf\Service::M('Linkage')->table('linkage')->get($key);
		if (!$link) {
		    $this->_admin_msg(0, dr_lang('联动菜单不存在'));
        }

		if (\Phpcmf\Service::M('Linkage')->counts('linkage_data_'.$key) > 3000) {
			$select = '<input type="text" class="form-control" name="pid" placeholder="'.dr_lang('输入所属Id号').'"> ';
		} else {
			$select = \Phpcmf\Service::L('Tree')->select_linkage(
				\Phpcmf\Service::M('Linkage')->getList($link),
				$pid,
				'name="pid"',
				dr_lang('顶级菜单')
			);
		}

		\Phpcmf\Service::V()->assign([
			'key' => $key,
			'pid' => $pid,
			'list' => \Phpcmf\Service::M('Linkage')->getList($link, $pid),
			'select' => $select,
            'menu' =>  \Phpcmf\Service::M('auth')->_admin_menu(
                [
                    '联动菜单' => ['linkage/index', 'fa fa-columns'],
                    '数据管理' => ['hide:linkage/list_index', 'fa fa-table'],
                    '修改' => ['hide:linkage/list_edit', 'fa fa-edit'],
                    '添加' => ['add:linkage/list_add{key='.$key.'&pid=0}', 'fa fa-plus'],
                    'help' => [354],
                ]
            ),
		]);
		\Phpcmf\Service::V()->display('linkage_list_index.html');
	}

	public function list_add() {

		$pid = (int)\Phpcmf\Service::L('input')->get('pid');
		$key = (int)\Phpcmf\Service::L('input')->get('key');
		$link = \Phpcmf\Service::M('Linkage')->table('linkage')->get($key);
		if (!$link) {
		    $this->_admin_msg(0, dr_lang('联动菜单不存在'));
        }
		
		if (IS_AJAX_POST) {
			$data = \Phpcmf\Service::L('input')->post('data', true);
			$rt = \Phpcmf\Service::M('Linkage')->add_list($key, $data);
			if (!$rt['code']) {
			    $this->_json(0, $rt['msg']);
            }
            \Phpcmf\Service::M('cache')->sync_cache('linkage', '', 1); // 自动更新缓存
			\Phpcmf\Service::L('input')->system_log('创建联动菜单('.$data['name'].')');
			exit($this->_json(1, $rt['msg']));
		}

		$select = '';
		if ($pid) {
			$top = \Phpcmf\Service::M('Linkage')->table('linkage_data_'.$key)->get($pid);
			if ($top) {
				$select = '<input type="hidden" name="data[pid]" value="'.$pid.'">';
				$select.= '<p class="form-control-static"> '.$top['name'].' </p>';
			}
		}

		!$select && $select = \Phpcmf\Service::L('Tree')->select_linkage(
			\Phpcmf\Service::M('Linkage')->getList($link),
			$pid,
			'name="data[pid]"',
			dr_lang('顶级菜单')
		);

		\Phpcmf\Service::V()->assign([
			'form' => dr_form_hidden(),
			'select' => $select
		]);
		\Phpcmf\Service::V()->display('linkage_list_add.html');
		exit;
	}

	public function list_edit() {

		$id = (int)\Phpcmf\Service::L('input')->get('id');
		$key = (int)\Phpcmf\Service::L('input')->get('key');

		$link = \Phpcmf\Service::M('Linkage')->table('linkage')->get($key);
		if (!$link) {
		    $this->_admin_msg(0, dr_lang('联动菜单不存在'));
        }

		$data = \Phpcmf\Service::M('Linkage')->table('linkage_data_'.$key)->get($id);
		if (!$data) {
		    $this->_admin_msg(0, dr_lang('联动菜单数据#%s不存在', $id));
        }

        $field = \Phpcmf\Service::M('Linkage')->get_fields($key);

		if (IS_AJAX_POST) {
			$post = \Phpcmf\Service::L('input')->post('data');
			$post['name'] = trim($post['name']);
			if (!$post['name']) {
				$this->_json(0, dr_lang('名称不能为空'));
			} elseif (!$post['cname']) {
				$this->_json(0, dr_lang('别名不能为空'));
			} else if (\Phpcmf\Service::M()->db->table('linkage_data_'.$key)->where('id<>', $id)->where('cname', $post['cname'])->countAllResults()) {
				$this->_json(0, dr_lang('别名已经存在'));
			}
            $update = [
                'pid' => $post['pid'],
                'name' => $post['name'],
                'cname' => $post['cname'],
            ];
			if ($field) {
                list($save, $return, $attach) = \Phpcmf\Service::L('form')->validation($post, null, $field, $data);
                // 输出错误
                $return && $this->_json(0, $return['error'], ['field' => $return['name']]);
                $update = dr_array22array($update, $save[1]);
            }
			$rt = \Phpcmf\Service::M('Linkage')->table('linkage_data_'.$key)->update($id, $update);
			if (!$rt['code']) {
			    $this->_json(0, $rt['msg']);
            }
            // 附件归档
            SYS_ATTACHMENT_DB && $attach && \Phpcmf\Service::M('Attachment')->handle(
                $this->member['id'],
                \Phpcmf\Service::M()->dbprefix('linkage_data_'.$key),
                $attach
            );
            \Phpcmf\Service::M('cache')->sync_cache('linkage', '', 1); // 自动更新缓存
			\Phpcmf\Service::L('input')->system_log('修改联动菜单('.$post['name'].')');
			exit($this->_json(1, dr_lang('操作成功')));
		}

		\Phpcmf\Service::V()->assign([
			'form' => dr_form_hidden(),
			'data' => $data,
            'myfield' => \Phpcmf\Service::L('field')->toform($this->uid, $field, $data),
			'select' => $this->_select($link, $data['pid']),
            'menu' =>  \Phpcmf\Service::M('auth')->_admin_menu(
                [
                    '联动菜单' => ['linkage/index', 'fa fa-columns'],
                    '数据管理' => ['hide:linkage/list_index', 'fa fa-table'],
                    '修改' => ['hide:linkage/list_edit', 'fa fa-edit'],
                    '添加' => ['add:linkage/list_add{key='.$key.'&pid=0}', 'fa fa-plus'],
                    'help' => [354],
                ]
            ),
            'reply_url' => dr_url('linkage/list_index', ['key'=> $key]),
		]);
		\Phpcmf\Service::V()->display('linkage_list_edit.html');
		exit;
	}

	private function _select($link, $pid) {

		if (\Phpcmf\Service::M('Linkage')->counts('linkage_data_'.$link['id']) > 3000) {
			$select = '<input type="text" class="form-control" name="data[pid]" value="'.$pid.'"> ';
			$select.= '<span class="help-block"> '.dr_lang('这是上级分类的Id号').' </span>';
		} else {
			$select = \Phpcmf\Service::L('Tree')->select_linkage(\Phpcmf\Service::M('Linkage')->getList($link), $pid, 'name="data[pid]"', dr_lang('顶级菜单'));
		}

		return $select;
	}

}
