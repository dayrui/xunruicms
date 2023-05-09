<?php namespace Phpcmf\Control\Admin;
/**
 * {{www.xunruicms.com}}
 * {{迅睿内容管理框架系统}}
 * 本文件是框架系统文件，二次开发时不可以修改本文件
 **/

// 联动菜单
class Linkage extends \Phpcmf\Common {

	public function index() {
		\Phpcmf\Service::V()->assign([
			'list' => \Phpcmf\Service::M('Linkage')->table('linkage')->getAll(),
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
			$data = \Phpcmf\Service::L('input')->post('data');
			$this->_validation(0, $data);
			\Phpcmf\Service::L('input')->system_log('创建联动菜单('.$data['name'].')');
			$rt = \Phpcmf\Service::M('Linkage')->create($data);
			if (!$rt['code']) {
			    $this->_json(0, $rt['msg']);
            }
            \Phpcmf\Service::M('cache')->sync_cache('linkage', '', 1); // 自动更新缓存
			$this->_json(1, dr_lang('操作成功'));
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
            $data['code'] = strtolower($data['code']);
			$this->_validation($id, $data);
			$rt = \Phpcmf\Service::M('Linkage')->table('linkage')->update($id, $data);
			if (!$rt['code']) {
			    $this->_json(0, $rt['msg']);
            }
			\Phpcmf\Service::L('input')->system_log('修改联动菜单('.$data['name'].')');
            \Phpcmf\Service::M('cache')->sync_cache('linkage', '', 1); // 自动更新缓存
			$this->_json(1, dr_lang('操作成功'));
		}

		\Phpcmf\Service::V()->assign([
			'data' => $data,
			'form' => dr_form_hidden()
		]);
		\Phpcmf\Service::V()->display('linkage_add.html');exit;
	}

	public function export_down() {

        $key = (int)\Phpcmf\Service::L('input')->get('key');
        $file = WRITEPATH.'temp/linkage-export-file-'.$this->uid.'-'.$key.'.json';

        if (!is_file($file)) {
            $this->_html_msg(0, dr_lang('导出文件不存在'));
        }

        set_time_limit(0);
        $handle = fopen($file,"rb");
        if (FALSE === $handle) {
            $this->_html_msg(0, dr_lang('文件已经损坏'));
        }

        $filesize = filesize($file);
        $link = \Phpcmf\Service::M()->table('linkage')->get($key);
        header('Content-Type: application/octet-stream');
        header("Accept-Ranges:bytes");
        header("Accept-Length:".$filesize);
        header("Content-Disposition: attachment; filename=".urlencode($link['name'].'.json'));

        while (!feof($handle)) {
            $contents = fread($handle, 4096);
            echo $contents;
            ob_flush();  //把数据从PHP的缓冲中释放出来
            flush();      //把被释放出来的数据发送到浏览器
        }

        fclose($handle);
        exit;
    }

	public function export() {

		$key = (int)\Phpcmf\Service::L('input')->get('key');
        $page = (int)\Phpcmf\Service::L('input')->get('page');
        $file = WRITEPATH.'temp/linkage-export-file-'.$this->uid.'-'.$key.'.json';

        if (!$page) {
            $nums = \Phpcmf\Service::M()->table('linkage_data_'.$key)->counts();
            if (!$nums) {
                $this->_json(0, dr_lang('数据为空，无法导出'));
            }

            $this->_html_msg(1, dr_lang('正在准备导出数据'), dr_url('linkage/export', ['key'=>$key, 'page' => 1]));
        } elseif ($page == 2) {
            $nums = \Phpcmf\Service::M()->table('linkage_data_'.$key)->counts();
            $this->_html_msg(1, dr_lang('导出完毕，共计%s条数据', $nums), dr_url('linkage/export_down', ['key'=>$key]), dr_lang('请关闭本窗口'));
        }

        $data = \Phpcmf\Service::M()->db->table('linkage_data_'.$key)->orderBy('id DESC')->get()->getResultArray();

        $rt = file_put_contents($file, dr_array2string($data));
        if (!$rt) {
            $this->_html_msg(0, '文件写入失败');
        }

        $this->_html_msg(1, dr_lang('正在整理数据...'),  dr_url('linkage/export', ['key'=>$key, 'page' => 2]));

	}

    public function import_add() {

        $key = (int)\Phpcmf\Service::L('input')->get('key');
        $page = (int)\Phpcmf\Service::L('input')->get('page');
        $file = WRITEPATH.'temp/linkage-import-file-'.$this->uid.'-'.$key.'.json';
        $table = 'linkage_data_'.$key;

        if (!$page) {
            if (!is_file($file)) {
                $this->_html_msg(0, dr_lang('导入文件不存在'));
            }
            \Phpcmf\Service::M('Linkage')->query('TRUNCATE `'.\Phpcmf\Service::M('Linkage')->dbprefix($table).'`');
            $this->_html_msg(1, dr_lang('正在准备导入数据'), dr_url('linkage/import_add', ['key'=>$key, 'page' => 1]));
        }

        if (!is_file($file)) {
            $nums = \Phpcmf\Service::M()->table('linkage_data_'.$key)->counts();
            \Phpcmf\Service::M('cache')->sync_cache('linkage', '', 1); // 自动更新缓存
            $this->_html_msg(1, dr_lang('导入完毕，共计%s条数据', $nums), '', dr_lang('请关闭本窗口'));
        }

        // 开始导入
        $data = dr_string2array(file_get_contents($file));
        if (!is_array($data)) {
            $this->_html_msg(0, dr_lang('导入信息验证失败'));
        }
        foreach ($data as $t) {
            if (is_numeric($t['cname'])) {
                $t['cname'] = 'a'.$t['cname'];
            } elseif (!preg_match('/^[a-z]+[a-z0-9\_]+$/i', $t['cname'])) {
                $t['cname'] = dr_safe_filename($t['cname']);
            }
            $rt = \Phpcmf\Service::M('Linkage')->table($table)->insert($t);
            if ($rt['code']) {
                $count++;
            }
        }

        unlink($file);

        $this->_html_msg(1, dr_lang('正在导入数据...'),  dr_url('linkage/import_add', ['key'=>$key, 'page' => 2]));

    }

    public function import() {

        $key = (int)\Phpcmf\Service::L('input')->get('key');

        \Phpcmf\Service::V()->assign([
            'add_url' => dr_url('linkage/import_add', ['key' => $key]),
            'upload_url' => dr_url('linkage/import_upload', ['key' => $key]),
        ]);
        \Phpcmf\Service::V()->display('linkage_import.html');
        exit;
    }

    // 上传处理
    function import_upload() {

        $key = (int)\Phpcmf\Service::L('input')->get('key');
        $file = WRITEPATH.'temp/linkage-import-file-'.$this->uid.'-'.$key.'.json';
        $rt = \Phpcmf\Service::L('upload')->upload_file([
            'save_file' => $file, // 上传的固定文件路径
            'form_name' => 'file_data', // 固定格式
            'file_exts' => ['json'], // 上传的扩展名
            'file_size' => 2 * 1024 * 1024, // 上传的大小限制
            'attachment' => \Phpcmf\Service::M('Attachment')->get_attach_info('null'), // 固定文件时必须这样写
        ]);
        if (!$rt['code']) {
            // 失败了
            exit(dr_array2string($rt));
        }

        // 上传成功了
        exit(dr_array2string($rt));
    }

	public function del() {

		$ids = \Phpcmf\Service::L('input')->get_post_ids();
		if (!$ids) {
		    $this->_json(0, dr_lang('你还没有选择呢'));
        }

		$rt = \Phpcmf\Service::M('Linkage')->delete_all($ids);
		if (!$rt['code']) {
		    $this->_json(0, $rt['msg']);
        }

        \Phpcmf\Service::M('cache')->sync_cache('linkage', '', 1); // 自动更新缓存
		\Phpcmf\Service::L('input')->system_log('批量联动菜单: '. implode(',', $ids));

		$this->_json(1, dr_lang('操作成功'), ['ids' => $ids]);
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
		if ($return) {
		    $this->_json(0, $return['error'], ['field' => $return['name']]);
        } elseif (\Phpcmf\Service::M('Linkage')->table('linkage')->is_exists($id, 'code', $data['code'])) {
		    $this->_json(0, dr_lang('别名已经存在'), ['field' => 'code']);
        }
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
		$this->_json(1, dr_lang($v ? '此菜单已被禁用' : '此菜单已被启用'), ['value' => $v]);
	}

	public function list_del() {

		$ids = \Phpcmf\Service::L('input')->get_post_ids();
		$key = (int)\Phpcmf\Service::L('input')->get('key');
		if (!$ids) {
		    $this->_json(0, dr_lang('你还没有选择呢'));
        }

		$rt = \Phpcmf\Service::M('Linkage')->delete_list_all($key, $ids);
		if (!$rt['code']) {
		    $this->_json(0, $rt['msg']);
        }

        \Phpcmf\Service::M('cache')->sync_cache('linkage', '', 1); // 自动更新缓存
		\Phpcmf\Service::L('input')->system_log('批量删除联动菜单: '. implode(',', $ids));

		$this->_json(1, dr_lang('操作成功'), ['ids' => $ids]);
	}

	public function close_edit() {

		$ids = \Phpcmf\Service::L('input')->get_post_ids();
		$key = (int)\Phpcmf\Service::L('input')->get('key');
		if (!$ids) {
		    $this->_json(0, dr_lang('你还没有选择呢'));
        }

        \Phpcmf\Service::M()->table('linkage_data_'.$key)->where_in('id', $ids)->update(null, [
            'hidden' => 1,
        ]);

        \Phpcmf\Service::M('cache')->sync_cache('linkage', '', 1); // 自动更新缓存
		\Phpcmf\Service::L('input')->system_log('批量禁用联动菜单: '. implode(',', $ids));

		$this->_json(1, dr_lang('操作成功'));
	}

	public function open_edit() {

		$ids = \Phpcmf\Service::L('input')->get_post_ids();
		$key = (int)\Phpcmf\Service::L('input')->get('key');
		if (!$ids) {
		    $this->_json(0, dr_lang('你还没有选择呢'));
        }

        \Phpcmf\Service::M()->table('linkage_data_'.$key)->where_in('id', $ids)->update(null, [
            'hidden' => 0,
        ]);

        \Phpcmf\Service::M('cache')->sync_cache('linkage', '', 1); // 自动更新缓存
		\Phpcmf\Service::L('input')->system_log('批量启用联动菜单: '. implode(',', $ids));

		$this->_json(1, dr_lang('操作成功'));
	}

    // 变更分类
	public function pid_edit() {

		$ids = \Phpcmf\Service::L('input')->get_post_ids();
		$key = (int)\Phpcmf\Service::L('input')->get('key');
		$pid = (int)\Phpcmf\Service::L('input')->post('pid');
		if (!$ids) {
		    $this->_json(0, dr_lang('你还没有选择呢'));
        }

		$rt = \Phpcmf\Service::M('Linkage')->edit_pid_all($key, $pid, $ids);
		if (!$rt['code']) {
		    $this->_json(0, $rt['msg']);
        }

        \Phpcmf\Service::M('cache')->sync_cache('linkage', '', 1); // 自动更新缓存
		\Phpcmf\Service::L('input')->system_log('批量更改联动菜单分类: '. implode(',', $ids));

		$this->_json(1, dr_lang('操作成功'), ['ids' => $ids]);
	}

    // 数据列表
	public function list_index() {

		$key = (int)\Phpcmf\Service::L('input')->get('key');
		$pid = (int)\Phpcmf\Service::L('input')->get('pid');

		$link = \Phpcmf\Service::M('Linkage')->table('linkage')->get($key);
		if (!$link) {
		    $this->_admin_msg(0, dr_lang('联动菜单不存在'));
        }

        $select = dr_fieldform('{"name":"pid","fieldname":"pid","ismain":"1","fieldtype":"Linkage","setting":{"option":{"linkage":"'.$link['code'].'","file":"","ck_child":"0","value":"","css":""},"validate":{"required":"0","pattern":"","errortips":"","check":"","filter":"","formattr":"","tips":""}},"ismember":"1"}', 0, 1, 1);

		\Phpcmf\Service::V()->assign([
			'key' => $key,
			'pid' => $pid,
			'list' => \Phpcmf\Service::M('Linkage')->getList($link, $pid),
            'menu' =>  \Phpcmf\Service::M('auth')->_admin_menu(
                [
                    '联动菜单' => ['linkage/index', 'fa fa-columns'],
                    '数据管理' => ['hide:linkage/list_index', 'fa fa-table'],
                    '修改' => ['hide:linkage/list_edit', 'fa fa-edit'],
                    '添加' => ['linkage/list_add{key='.$key.'&pid='.$pid.'}', 'fa fa-plus'],
                    'help' => [354],
                ]
            ),
            'select' => str_replace('data[pid]', 'pid', $select),
		]);
		\Phpcmf\Service::V()->display('linkage_list_index.html');
	}

    // 快速添加数据
    public function listk_add() {

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
            $this->_json(1, $rt['msg']);
        }

        $select = '';
        if ($pid) {
            $top = \Phpcmf\Service::M('Linkage')->table('linkage_data_'.$key)->get($pid);
            if ($top) {
                $select = '<input type="hidden" name="data[pid]" value="'.$pid.'">';
                $select.= '<p class="form-control-static"> '.$top['name'].' </p>';
            }
        }
        if (!$select) {
            $select = '<input type="hidden" name="data[pid]" value="0">';
            $select.= '<p class="form-control-static"> '.dr_lang('顶级').' </p>';
        }

        \Phpcmf\Service::V()->assign([
            'form' => dr_form_hidden(),
            'select' => $select
        ]);
        \Phpcmf\Service::V()->display('linkage_list_add.html');
        exit;
    }

    // 添加数据
	public function list_add() {

		$pid = (int)\Phpcmf\Service::L('input')->get('pid');
		$key = (int)\Phpcmf\Service::L('input')->get('key');
		$link = \Phpcmf\Service::M('Linkage')->table('linkage')->get($key);
		if (!$link) {
		    $this->_admin_msg(0, dr_lang('联动菜单不存在'));
        }

        $field = \Phpcmf\Service::M('Linkage')->get_fields($key);
		
		if (IS_AJAX_POST) {
            $post = \Phpcmf\Service::L('input')->post('data');
            $post['name'] = trim($post['name']);
            if (!$post['name']) {
                $this->_json(0, dr_lang('名称不能为空'));
            } elseif (!$post['cname']) {
                $this->_json(0, dr_lang('别名不能为空'));
            } elseif (is_numeric($post['cname'])) {
                $this->_json(0, dr_lang('别名不能是数字'));
            } elseif (!preg_match('/^[a-z]+[a-z0-9\_]+$/i', $post['cname'])) {
                $this->_json(0, dr_lang('别名名称不规范'));
            } elseif (\Phpcmf\Service::M()->db->table('linkage_data_'.$key)->where('cname', $post['cname'])->countAllResults()) {
                $this->_json(0, dr_lang('别名已经存在'));
            }
            $update = [
                'pid' => $post['pid'],
                'name' => $post['name'],
                'cname' => $post['cname'],
            ];
            if ($field) {
                list($save, $return, $attach) = \Phpcmf\Service::L('form')->validation($post, null, $field, []);
                // 输出错误
                if ($return) {
                    $this->_json(0, $return['error'], ['field' => $return['name']]);
                }
                $update = dr_array22array($update, $save[1]);
            }
            $update['site'] = SITE_ID;
            $rt = \Phpcmf\Service::M('Linkage')->table('linkage_data_'.$key)->insert($update);
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
            \Phpcmf\Service::L('input')->system_log('创建联动菜单('.$post['name'].')');
            $this->_json(1, dr_lang('操作成功'));
		}

		$select = '';
		if ($pid) {
			$top = \Phpcmf\Service::M('Linkage')->table('linkage_data_'.$key)->get($pid);
			if ($top) {
				$select = '<input type="hidden" name="data[pid]" value="'.$pid.'">';
				$select.= '<p class="form-control-static"> '.$top['name'].' </p>';
			}
		}
        if (!$select) {
            $select = '<input type="hidden" name="data[pid]" value="0">';
            $select.= '<p class="form-control-static"> '.dr_lang('顶级').' </p>';
        }

        \Phpcmf\Service::V()->assign([
            'form' => dr_form_hidden(),
            'menu' =>  \Phpcmf\Service::M('auth')->_admin_menu(
                [
                    '联动菜单' => ['linkage/index', 'fa fa-columns'],
                    '数据管理' => ['linkage/list_index{key='.$key.'}', 'fa fa-table'],
                    '修改' => ['hide:linkage/list_edit', 'fa fa-edit'],
                    '添加' => ['linkage/list_add{key='.$key.'&pid='.$pid.'}', 'fa fa-plus'],
                    'help' => [354],
                ]
            ),
            'select' => $select,
            'myfield' => \Phpcmf\Service::L('field')->toform($this->uid, $field, []),
            'reply_url' => dr_url('linkage/list_index', ['key'=> $key, 'pid' => $pid]),
        ]);
        \Phpcmf\Service::V()->display('linkage_list_edit.html');
	}

    // 修改数据
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
			} elseif (is_numeric($post['cname'])) {
				$this->_json(0, dr_lang('别名不能是数字'));
            } elseif (!preg_match('/^[a-z]+[a-z0-9\_]+$/i', $post['cname'])) {
                $this->_json(0, dr_lang('别名名称不规范'));
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
                if ($return) {
                    $this->_json(0, $return['error'], ['field' => $return['name']]);
                }
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
			$this->_json(1, dr_lang('操作成功'));
		}

        $select = '';
        if ($data['pid']) {
            $top = \Phpcmf\Service::M('Linkage')->table('linkage_data_'.$key)->get($data['pid']);
            if ($top) {
                $select = '<input type="hidden" name="data[pid]" value="'.$data['pid'].'">';
                $select.= '<p class="form-control-static"> '.$top['name'].' </p>';
            }
        }
        if (!$select) {
            $select = '<input type="hidden" name="data[pid]" value="0">';
            $select.= '<p class="form-control-static"> '.dr_lang('顶级').' </p>';
        }

		\Phpcmf\Service::V()->assign([
			'form' => dr_form_hidden(),
			'data' => $data,
            'menu' =>  \Phpcmf\Service::M('auth')->_admin_menu(
                [
                    '联动菜单' => ['linkage/index', 'fa fa-columns'],
                    '数据管理' => ['linkage/list_index{key='.$key.'}', 'fa fa-table'],
                    '修改' => ['hide:linkage/list_edit', 'fa fa-edit'],
                    '添加' => ['linkage/list_add{key='.$key.'&pid='.$data['pid'].'}', 'fa fa-plus'],
                    'help' => [354],
                ]
            ),
            'select' => $select,
            'myfield' => \Phpcmf\Service::L('field')->toform($this->uid, $field, $data),
            'reply_url' => dr_url('linkage/list_index', ['key'=> $key, 'pid' => $data['pid']]),
		]);
		\Phpcmf\Service::V()->display('linkage_list_edit.html');
		exit;
	}


    // 一键生成
    public function cache_index() {

        $key = (int)\Phpcmf\Service::L('input')->get('key');
        $link = \Phpcmf\Service::M('Linkage')->table('linkage')->get($key);
        if (!$link) {
            $this->_html_msg(0, dr_lang('联动菜单不存在'));
        }

        $page = (int)\Phpcmf\Service::L('input')->get('page');
        $psize = 10; // 每页处理的数量
        $total = (int)\Phpcmf\Service::L('input')->get('total');

        if (!$page) {
            $path = WRITEPATH.'linkage/'.SITE_ID.'_'.$link['code'].'/';
            dr_dir_delete($path);
            $links = \Phpcmf\Service::M('Linkage')->repair($link, SITE_ID); // 修复菜单
            $pids = \Phpcmf\Service::M('Linkage')->get_child_pids();
            $total = dr_count($pids);
            if (!$total) {
                $this->_html_msg(0, dr_lang('无可用数据'));
            }
            // 存储执行
            \Phpcmf\Service::L('cache')->set_auth_data('linkage-all-'.$key, $links, SITE_ID);
            \Phpcmf\Service::L('cache')->set_auth_data('linkage-cache-'.$key, array_chunk($pids, $psize), SITE_ID);
            $this->_html_msg(1, dr_lang('正在执行中...'), dr_url('linkage/cache_index', ['key' => $key]).'&total='.$total.'&page='.($page+1));
        }

        $tpage = ceil($total / $psize); // 总页数
        $pids = \Phpcmf\Service::L('cache')->get_auth_data('linkage-cache-'.$key, SITE_ID);
        if (!$pids) {
            $this->_html_msg(0, dr_lang('临时数据读取失败'));
        } elseif (!isset($pids[$page-1]) || $page > $tpage) {
            // 生成级联关系
            $links = \Phpcmf\Service::L('cache')->get_auth_data('linkage-all-'.$key, SITE_ID);
            \Phpcmf\Service::M('Linkage')->get_json($link, $links);
            $this->_html_msg(1, dr_lang('更新完成'));
        }

        $field = \Phpcmf\Service::M('Linkage')->get_fields($key);
        foreach ($pids[$page-1] as $pid) {
            \Phpcmf\Service::M('linkage')->cache_list($link, $pid, $field);
        }

        $this->_html_msg( 1, dr_lang('正在执行中【%s】...', "$tpage/$page"),
            dr_url('linkage/cache_index', ['key' => $key, 'total' => $total, 'page' => $page + 1])
        );
    }
}
