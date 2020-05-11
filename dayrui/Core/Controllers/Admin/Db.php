<?php namespace Phpcmf\Controllers\Admin;

/**
 * http://www.xunruicms.com
 * 本文件是框架系统文件，二次开发时不可以修改本文件
 **/

class Db extends \Phpcmf\Common
{

	public function __construct(...$params) {
		parent::__construct(...$params);
		\Phpcmf\Service::V()->assign('menu', \Phpcmf\Service::M('auth')->_admin_menu(
			[
				'数据结构' => ['db/index', 'fa fa-database'],
				'执行SQL' => ['content/index{p=1}', 'fa fa-code'],
			]
		));
	}

	public function index() {

	    $list = \Phpcmf\Service::M()->db->query('show table status')->getResultArray();

		\Phpcmf\Service::V()->assign([
			'list' => $list,
            'uriprefix' => 'db'
		]);
		\Phpcmf\Service::V()->display('db_index.html');
	}


	public function show_index() {

	    $table = dr_safe_replace(\Phpcmf\Service::L('input')->get('id'));
        $list = \Phpcmf\Service::M()->db->query('SHOW FULL COLUMNS FROM `'.$table.'`')->getResultArray();

		\Phpcmf\Service::V()->assign([
			'list' => $list,
			'table' => $table,
		]);
		\Phpcmf\Service::V()->display('db_show.html');exit;
	}

	// 批量操作
	public function add() {

	    $at = \Phpcmf\Service::L('input')->get('at');
        $ids = \Phpcmf\Service::L('input')->post('ids');
        if (!$ids) {
            $this->_json(0, dr_lang('没有选择表'));
        }

        $cache = [];
        $count = count($ids);
        if ($count > 100) {
            $pagesize = ceil($count/100);
            for ($i = 1; $i <= 100; $i ++) {
                $cache[$i] = array_slice($ids, ($i - 1) * $pagesize, $pagesize);
            }
        } else {
            for ($i = 1; $i <= $count; $i ++) {
                $cache[$i] = array_slice($ids, ($i - 1), 1);
            }
        }

        // 存储文件
        \Phpcmf\Service::L('cache')->set_data('db-todo-'.$at, $cache, 3600);
        $this->_json(1, 'ok', ['url' => dr_url('db/count_index', ['at' => $at, 'hide_menu' => 1])]);
    }

    public function count_index() {

        $at = \Phpcmf\Service::L('input')->get('at');

        /*
        $i = 0;
        foreach ($ids as $table) {

            if (!$table) {
                continue;
            }

            switch ($at) {

                case 'x':
                    \Phpcmf\Service::M()->db->query('REPAIR TABLE `'.$table.'`');
                    break;

                case 'y':
                    \Phpcmf\Service::M()->db->query('OPTIMIZE TABLE `'.$table.'`');
                    break;

                case 's':
                    \Phpcmf\Service::M()->db->query('FLUSH TABLE `'.$table.'`');
                    break;

            }
            $i++;
        }*/


        \Phpcmf\Service::V()->assign([
            'todo_url' => dr_url('db/todo_index', ['at' => $at]),
        ]);
        \Phpcmf\Service::V()->display('db_bfb.html');exit;
    }

    public function todo_index() {

        $at = \Phpcmf\Service::L('input')->get('at');
        $page = max(1, intval(\Phpcmf\Service::L('input')->get('page')));
        $cache = \Phpcmf\Service::L('cache')->get_data('db-todo-'.$at);
        if (!$cache) {
            $this->_json(0, '数据缓存不存在');
        }

        $data = $cache[$page];
        if ($data) {
            $html = '';
            foreach ($data as $table) {

                $ok = '完成';
                $class = '';
                switch ($at) {

                    case 'x':
                        \Phpcmf\Service::M()->db->query('REPAIR TABLE `'.$table.'`');
                        break;

                    case 'y':
                        \Phpcmf\Service::M()->db->query('OPTIMIZE TABLE `'.$table.'`');
                        break;

                    case 's':
                        \Phpcmf\Service::M()->db->query('FLUSH TABLE `'.$table.'`');
                        break;

                    case 'ut':
                        \Phpcmf\Service::M()->db->query('alter table `'.$table.'` convert to character set utf8mb4;');
                        break;

                    case 'jc':
                        $data = \Phpcmf\Service::M()->db->query('CHECK TABLE `'.$table.'`')->getRowArray();
                        if (!$data) {
                            $class = 'p_error';
                            $ok = "<span class='error'>".dr_lang('表信息读取失败')."</span>";
                        } else {
                            $ok = $data['Msg_text'];
                        }

                }

                $html.= '<p class="'.$class.'"><label class="rleft">'.$table.'</label><label class="rright">'.$ok.'</label></p>';
            }
            $this->_json($page + 1, $html);
        }

        // 完成
        \Phpcmf\Service::L('cache')->clear('db-todo-'.$at);
        $this->_json(100, '');
    }


}
