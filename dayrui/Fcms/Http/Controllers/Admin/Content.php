<?php namespace Phpcmf\Controllers\Admin;

/**
 * http://www.xunruicms.com
 * 本文件是框架系统文件，二次开发时不可以修改本文件
 **/

class Content extends \Phpcmf\Admin\Content
{

	public function index() {

        if (\Phpcmf\Service::L('input')->get('p')) {
            \Phpcmf\Service::V()->assign('menu', \Phpcmf\Service::M('auth')->_admin_menu(
                [
                    '数据结构' => ['db/index', 'fa fa-database'],
                    '执行SQL' => ['content/index{p=1}', 'fa fa-code'],
                ]
            ));
        }

        $bm = [];
        $tables = \Phpcmf\Service::M()->db->query('show table status')->getResultArray();
        foreach ($tables as $t) {
            $t['Name'] = str_replace('_data_0', '_data_[tableid]', $t['Name']);
            $bm[$t['Name']] = $t;
        }

        $page = intval(\Phpcmf\Service::L('input')->get('page'));
        \Phpcmf\Service::V()->assign([
            'page' => $page,
            'form' =>  dr_form_hidden(['page' => $page]),
            'tables' => $bm,
            'sql_cache' => \Phpcmf\Service::L('File')->get_sql_cache(),
        ]);
		\Phpcmf\Service::V()->display('content_index.html');
	}

	public function field_index() {

	    $table = dr_safe_replace(\Phpcmf\Service::L('input')->get('table'));
        $table = str_replace('_data_[tableid]', '_data_0', $table);
	    if (!$table) {
            $this->_json(0, dr_lang('表参数不能为空'));
        } elseif (!\Phpcmf\Service::M()->db->tableExists($table)) {
            $this->_json(0, dr_lang('表[%s]不存在', $table));
        }

        $fields = \Phpcmf\Service::M()->db->query('SHOW FULL COLUMNS FROM `'.$table.'`')->getResultArray();
	    if (!$fields) {
	        $this->_json(0, dr_lang('表[%s]没有可用字段', $table));
        }

        $msg = '<select name="fd" class="form-control">';
        foreach ($fields as $t) {
            if ($t['Field'] != 'id') {
                $msg.= '<option value="'.$t['Field'].'">'.$t['Field'].($t['Comment'] ? '（'.$t['Comment'].'）' : '').'</option>';
            }
        }
        $msg.= '</select>';

        $this->_json(1, $msg);
    }

	public function replace_index() {
		$this->_Replace();
	}

	public function sql_index() {
		$this->_Sql();
	}

}
