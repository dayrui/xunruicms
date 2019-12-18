<?php namespace Phpcmf\Controllers\Admin;

/**
 * http://www.xunruicms.com
 * 本文件是框架系统文件，二次开发时不可以修改本文件
 **/

class Form extends \Phpcmf\Common
{

	private $form; // 表单验证配置

	public function __construct(...$params) {
		parent::__construct(...$params);
		\Phpcmf\Service::V()->assign('menu', \Phpcmf\Service::M('auth')->_admin_menu(
			[
				'网站表单' => ['form/index', 'fa fa-table'],
				'添加' => ['add:form/add', 'fa fa-plus', '500px', '310px'],
				'导入' => ['add:form/import_add', 'fa fa-sign-in', '60%', '70%'],
				'重建表单' => ['ajax:form/init_index', 'fa fa-refresh'],
				'修改' => ['hide:form/edit', 'fa fa-edit'],
                'help' => [54],
			]
		));
		// 表单验证配置
		$this->form = [
			'name' => [
				'name' => '表单名称',
				'rule' => [
					'empty' => dr_lang('表单名称不能为空')
				],
				'filter' => [],
				'length' => '200'
			],
			'table' => [
				'name' => '数据表名称',
				'rule' => [
					'empty' => dr_lang('数据表名称不能为空'),
					'table' => dr_lang('数据表名称格式不正确'),
				],
				'filter' => [],
				'length' => '200'
			],
		];
	}

	public function index() {

		\Phpcmf\Service::V()->assign([
			'list' => \Phpcmf\Service::M('Form')->getAll(),
		]);
		\Phpcmf\Service::V()->display('form_list.html');
	}

	public function init_index() {

		$data = \Phpcmf\Service::M('Form')->getAll();
		if (!$data) {
		    $this->_json(0, dr_lang('没有任何可用表单'));
        }

		$ok = 0;
		foreach ($data as $t) {
            $cg = dr_string2array($t['setting']);
            if ($cg['dev']) {
                continue;
            }
			$rt = \Phpcmf\Service::M('Form')->create_file($t['table'], 1);
			if (!$rt['code']) {
			    $this->_json(0, $rt['msg']);
            }
			$ok+= (int)$rt['msg'];
		}

		$this->_json(1, dr_lang('重建表单（%s）个', $ok));
	}


	public function add() {

		if (IS_AJAX_POST) {
			$data = \Phpcmf\Service::L('input')->post('data');
            if (!preg_match('/^[a-z]+[a-z0-9\_]+$/i', $data['table'])) {
                $this->_json(0, dr_lang('表单别名不规范'));
            } elseif (\Phpcmf\Service::M('app')->is_sys_dir($data['table'])) {
                $this->_json(0, dr_lang('名称[%s]是系统保留名称，请重命名', $data['table']));
            }
            $this->_validation(0, $data);
			\Phpcmf\Service::L('input')->system_log('创建网站表单('.$data['name'].')');
			$rt = \Phpcmf\Service::M('Form')->create($data);
			if (!$rt['code']) {
			    $this->_json(0, $rt['msg']);
            }
            \Phpcmf\Service::M('cache')->sync_cache('form', '', 1); // 自动更新缓存
			exit($this->_json(1, dr_lang('操作成功，请刷新后台页面')));
		}

		\Phpcmf\Service::V()->assign([
			'form' => dr_form_hidden()
		]);
		\Phpcmf\Service::V()->display('form_add.html');
		exit;
	}

	public function edit() {

		$id = intval(\Phpcmf\Service::L('input')->get('id'));
		$data = \Phpcmf\Service::M('Form')->get($id);
		if (!$data) {
		    $this->_admin_msg(0, dr_lang('网站表单（%s）不存在', $id));
        }

		$data['setting'] = dr_string2array($data['setting']);
		!$data['setting']['list_field'] && $data['setting']['list_field'] = [
			'title' => [
				'use' => 1,
				'name' => dr_lang('主题'),
				'func' => 'title',
				'width' => 0,
				'order' => 1,
			],
			'author' => [
				'use' => 1,
				'name' => dr_lang('作者'),
				'func' => 'author',
				'width' => 100,
				'order' => 2,
			],
			'inputtime' => [
				'use' => 1,
				'name' => dr_lang('录入时间'),
				'func' => 'datetime',
				'width' => 160,
				'order' => 3,
			],
		];

		if (IS_AJAX_POST) {
			$data = \Phpcmf\Service::L('input')->post('data');
            if ($data['setting']['list_field']) {
                foreach ($data['setting']['list_field'] as $t) {
                    if ($t['func']
                        && !method_exists(\Phpcmf\Service::L('Function_list'), $t['func']) && !function_exists($t['func'])) {
                        $this->_json(0, dr_lang('列表回调函数[%s]未定义', $t['func']));
                    }
                }
            }
			\Phpcmf\Service::M('Form')->update($id,
				[
					'name' => $data['name'],
					'setting' => dr_array2string($data['setting'])
				]
			);
            \Phpcmf\Service::M('cache')->sync_cache('form', '', 1); // 自动更新缓存
			\Phpcmf\Service::L('input')->system_log('修改网站表单('.$data['name'].')配置');
			exit($this->_json(1, dr_lang('操作成功')));
		}

		// 主表字段
		$field = \Phpcmf\Service::M()->db->table('field')
						->where('disabled', 0)
						->where('ismain', 1)
						->where('relatedname', 'form-'.SITE_ID)
						->where('relatedid', $id)
						->orderBy('displayorder ASC,id ASC')
						->get()->getResultArray();
		$sys_field = \Phpcmf\Service::L('field')->sys_field(['id', 'author', 'inputtime']);
        $page = intval(\Phpcmf\Service::L('input')->get('page'));

		\Phpcmf\Service::V()->assign([
			'data' => $data,
			'page' => $page,
			'form' => dr_form_hidden(['page' => $page]),
			'field' => dr_list_field_value($data['setting']['list_field'], $sys_field, $field),
            'diy_tpl' => is_file(APPSPATH.'Form/Views/diy_'.$data['table'].'.html') ? APPSPATH.'Form/Views/diy_'.$data['table'].'.html' : '',
		]);
		\Phpcmf\Service::V()->display('form_edit.html');
	}

	public function del() {

		$ids = \Phpcmf\Service::L('input')->get_post_ids();
		if (!$ids) {
		    exit($this->_json(0, dr_lang('你还没有选择呢')));
        }

		$rt = \Phpcmf\Service::M('Form')->delete_form($ids);
		if (!$rt['code']) {
		    exit($this->_json(0, $rt['msg']));
        }

        \Phpcmf\Service::M('cache')->sync_cache('form', '', 1); // 自动更新缓存
		\Phpcmf\Service::L('input')->system_log('批量删除网站表单: '. @implode(',', $ids));

		exit($this->_json(1, dr_lang('操作成功'), ['ids' => $ids]));
	}

	// 验证数据
	private function _validation($id, $data) {

		list($data, $return) = \Phpcmf\Service::L('form')->validation($data, $this->form);
		$return && exit($this->_json(0, $return['error'], ['field' => $return['name']]));
		\Phpcmf\Service::M('Form')->table('form')->is_exists($id, 'table', $data['table']) && exit($this->_json(0, dr_lang('数据表名称已经存在'), ['field' => 'table']));
	}

	// 导出
    public function export() {

        $id = intval(\Phpcmf\Service::L('input')->get('id'));
        $data = \Phpcmf\Service::M('Form')->get($id);
        if (!$data) {
            $this->_admin_msg(0, dr_lang('网站表单（%s）不存在', $id));
        }

        // 字段
        $data['field'] = \Phpcmf\Service::M()->db->table('field')->where('relatedname', 'form-'.SITE_ID)->where('relatedid', $id)->orderBy('displayorder ASC,id ASC')->get()->getResultArray();

        $data['setting'] = dr_string2array($data['setting']);

        // 数据结构
        $data['sql'] = '';

        $table = \Phpcmf\Service::M()->dbprefix(SITE_ID.'_form_'.$data['table']);
        $sql = \Phpcmf\Service::M()->db->query("SHOW CREATE TABLE `".$table."`")->getRowArray();
        $sql = 'DROP TABLE IF EXISTS `'.$table.'`;'.PHP_EOL.str_replace('CREATE TABLE', 'CREATE TABLE IF NOT EXISTS', $sql['Create Table']).';';
        $data['sql'].= str_replace($table, '{table}', $sql).PHP_EOL;

        $table = \Phpcmf\Service::M()->dbprefix(SITE_ID.'_form_'.$data['table'].'_data_0');
        $sql = \Phpcmf\Service::M()->db->query("SHOW CREATE TABLE `".$table."`")->getRowArray();
        $sql = 'DROP TABLE IF EXISTS `'.$table.'`;'.PHP_EOL.str_replace('CREATE TABLE', 'CREATE TABLE IF NOT EXISTS', $sql['Create Table']).';';
        $data['sql'].= str_replace($table, '{table}_data_0', $sql).PHP_EOL;

        \Phpcmf\Service::V()->assign([
            'data' => dr_array2string($data),
        ]);
        \Phpcmf\Service::V()->display('form_export.html');exit;
    }

    // 导入
    public function import_add() {

        if (IS_AJAX_POST) {
            $data = \Phpcmf\Service::L('input')->post('code');
            $data = dr_string2array($data);
            if (!is_array($data)) {
                $this->_json(0, dr_lang('导入信息验证失败'));
            } elseif (!$data['table']) {
                $this->_json(0, dr_lang('导入信息不完整'));
            } elseif (!$data['field']) {
                $this->_json(0, dr_lang('字段信息不完整'));
            } elseif (!$data['sql']) {
                $this->_json(0, dr_lang('数据结构不完整'));
            }
            $rt = \Phpcmf\Service::M('Form')->import($data);
            if (!$rt['code']) {
                $this->_json(0, $rt['msg']);
            }
            \Phpcmf\Service::M('cache')->sync_cache('form', '', 1); // 自动更新缓存
            \Phpcmf\Service::L('input')->system_log('导入网站表单('.$data['name'].')');
            exit($this->_json(1, dr_lang('操作成功')));
        }

        \Phpcmf\Service::V()->assign([
            'form' => dr_form_hidden()
        ]);
        \Phpcmf\Service::V()->display('form_import.html');
        exit;
    }

}
