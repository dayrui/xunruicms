<?php namespace Phpcmf\Model;

/**
 * http://www.xunruicms.com
 * 本文件是框架系统文件，二次开发时不可以修改本文件，可以通过继承类方法来重写此文件
 **/


// 菜单控制模型

class Menu extends \Phpcmf\Model {

    protected $ids;

    // 新增后台菜单
    public function _add($table, $pid, $data, $mark = '', $is_return = false) {

        if (!$data['name']) {
            return $is_return ? 0 : dr_return_data(0, dr_lang('名称不能为空'));
        }

        !$mark && ($mark = $data['mark'] ? $data['mark'] : '');

        if ($table == 'admin') {
            // 重复判断
            if ($data['uri'] && \Phpcmf\Service::M()->table('admin_menu')->where('uri', $data['uri'])->counts()) {
                // 链接菜单判断重复
                return $is_return ? 0 : dr_return_data(0, dr_lang('系统路径已经存在'), ['field' => 'uri']);
            } elseif ($mark && \Phpcmf\Service::M()->table('admin_menu')->where('mark', $mark)->counts()) {
                return $is_return ? 0 : dr_return_data(0, dr_lang('标识字符已经存在'), ['field' => 'uri']);
            }
            $this->db->table('admin_menu')->replace([
                'pid' => $pid,
                'name' => $data['name'],
                'uri' => $data['uri'] ? $data['uri'] : '',
                'url' => $data['url'] ? $data['url'] : '',
                'mark' => $mark,
                'icon' => $data['icon'] ? $data['icon'] : '',
                'hidden' => (int)$data['hidden'],
                'displayorder' => (int)$data['displayorder'],
            ]);
        } else {
            // 重复判断
            if ($data['uri']  && \Phpcmf\Service::M()->table('member_menu')->where('uri', $data['uri'])->counts()) {
                // 链接菜单判断重复
                return $is_return ? 0 : dr_return_data(0, dr_lang('系统路径已经存在'), ['field' => 'uri']);
            } elseif ($mark && \Phpcmf\Service::M()->table('member_menu')->where('mark', $mark)->counts()) {
                return $is_return ? 0 : dr_return_data(0, dr_lang('标识字符已经存在'), ['field' => 'uri']);
            }
            $group = [];
            if ($data['group']) {
                foreach ($data['group'] as $i) {
                    $group[$i] = $i;
                }
            }
            $site = [];
            if ($site['group']) {
                foreach ($site['group'] as $i) {
                    $site[$i] = $i;
                }
            }
            $this->db->table('member_menu')->replace([
                'pid' => $pid,
                'name' => $data['name'],
                'uri' => $data['uri'] ? $data['uri'] : '',
                'url' => $data['url'] ? $data['url'] : '',
                'mark' => $mark,
                'icon' => $data['icon'] ? $data['icon'] : '',
                'group' => dr_array2string($group),
                'site' => dr_array2string($site),
                'hidden' => (int)$data['hidden'],
                'displayorder' => (int)$data['displayorder'],
            ]);
        }

        return $is_return ? $this->db->insertID() : dr_return_data($this->db->insertID(), 'ok');
    }

    // 修改菜单
    public function _edit($table, $id, $data) {

        if ($table == 'admin') {
            $this->db->table('admin_menu')->where('id', (int)$id)->update($data);
        } else {
            $this->db->table('member_menu')->where('id', (int)$id)->update($data);
        }

        return $id;
    }

    // 从模块中更新菜单
    public function update_module($mdir, $config, $form, $comment_cname = '') {

        // 作为应用模块时且不操作menu.php时,不需要菜单
        if (isset($config['ftpye']) && $config['ftpye'] == 'module'
            && is_file(dr_get_app_dir($mdir).'Config/Menu.php')) {
            return;
        }

        // 内容模块 入库后台菜单
        if ($config['system'] == 1) {
            $left = $this->db->table('admin_menu')->where('mark', 'content-module')->get()->getRowArray();
            if ($left) {
                // 查询模块菜单
                $menu = $this->db->table('admin_menu')->where('mark', 'module-'.$mdir)->get()->getRowArray();
                $save = [
                    'uri' => $mdir.'/home/index',
                    'mark' => 'module-'.$mdir,
                    'name' => $menu && $menu['name'] ? $menu['name'] : dr_lang('%s管理', $config['name']),
                    'icon' => $menu && $menu['icon'] ? $menu['icon'] : dr_icon($config['icon']),
                    'displayorder' => $menu ? intval($menu['displayorder']) : '-1',
                ];
                $menu ? $this->_edit('admin', $menu['id'], $save) : $this->_add('admin', $left['id'], $save);
            }
            // 入库后台审核菜单
            $left = $this->db->table('admin_menu')->where('mark', 'content-verify')->get()->getRowArray();
            if ($left) {
                // 内容模块入库
                if ($config['system'] == 1) {
                    $menu = $this->db->table('admin_menu')->where('mark', 'verify-module-'.$mdir)->get()->getRowArray();
                    $save = [
                        'uri' => $mdir.'/verify/index',
                        'mark' => 'verify-module-'.$mdir,
                        'name' => $menu && $menu['name'] ? $menu['name'] : dr_lang('%s审核', $config['name']),
                        'icon' => $menu && $menu['icon'] ? $menu['icon'] : dr_icon($config['icon']),
                        'displayorder' => $menu ? intval($menu['displayorder']) : '-1',
                    ];
                    $menu ? $this->_edit('admin', $menu['id'], $save) : $this->_add('admin', $left['id'], $save);
                }
                // 评论入库
                $menu = $this->db->table('admin_menu')->where('mark', 'verify-comment-'.$mdir)->get()->getRowArray();
                $save = [
                    'uri' => $mdir.'/comment_verify/index',
                    'mark' => 'verify-comment-'.$mdir,
                    'name' => $menu && $menu['name'] ? $menu['name'] : dr_lang('%s%s', $config['name'], dr_comment_cname($comment_cname)),
                    'icon' => 'fa fa-comments',
                    'displayorder' => $menu ? intval($menu['displayorder']) : '-1',
                ];
                $menu ? $this->_edit('admin', $menu['id'], $save) : $this->_add('admin', $left['id'], $save);
                // 表单入库
                if ($form) {
                    foreach ($form as $t) {
                        $mark = 'verify-mform-'.$mdir.'-'.$t['table'];
                        $menu = $this->db->table('admin_menu')->where('mark', $mark)->get()->getRowArray();
                        $save = [
                            'uri' => $mdir.'/'.$t['table'].'_verify/index',
                            'mark' => $mark,
                            'name' => $menu && $menu['name'] ? $menu['name'] : dr_lang('%s%s', $config['name'], $t['name']),
                            'icon' => $menu && $menu['icon'] ? $menu['icon'] : dr_icon($t['setting']['icon']),
                            'displayorder' => $menu ? intval($menu['displayorder']) : '-1',
                        ];
                        $menu ? $this->_edit('admin', $menu['id'], $save) : $this->_add('admin', $left['id'], $save);
                    }
                }
            }
        }

        // 内容模块入库用户菜单
        if ($config['system'] == 1) {
            $left = $this->db->table('member_menu')->where('mark', 'content-module')->get()->getRowArray();
            if ($left) {
                // 查询模块菜单
                $menu = $this->db->table('member_menu')->where('mark', 'module-'.$mdir)->get()->getRowArray();
                $save = [
                    'uri' => $mdir.'/home/index',
                    'mark' => 'module-'.$mdir,
                    'name' => $menu && $menu['name'] ? $menu['name'] : dr_lang('%s管理', $config['name']),
                    'icon' => $menu && $menu['icon'] ? $menu['icon'] : dr_icon($config['icon']),
                    'displayorder' => $menu ? intval($menu['displayorder']) : '-1',
                ];
                $menu ? $this->_edit('member', $menu['id'], $save) : $this->_add('member', $left['id'], $save);
            }
        }

    }


    // 从网站表单中更新菜单
    public function form($data) {

        // 后台管理菜单
        $menu = $this->db->table('admin_menu')->where('mark', 'form-'.$data['table'])->get()->getRowArray();
        if ($menu) {
            // 更新
            /*
            $this->db->table('admin_menu')->where('id', intval($menu['id']))->update([
                'name' => dr_lang('%s管理', $data['name']),
                'icon' => (string)$data['setting']['icon'],
            ]);*/
        } else {
            // 新增菜单
            $menu = $this->db->table('admin_menu')->where('mark', 'content-form')->get()->getRowArray();
            if ($menu) {
                $this->_add('admin', $menu['id'], [
                    'name' => dr_lang('%s管理', $data['name']),
                    'icon' => (string)$data['setting']['icon'],
                    'uri' => 'form/'.$data['table'].'/index',
                ], 'form-'.$data['table']);
            }
        }
        // 后台审核菜单
        $menu = $this->db->table('admin_menu')->where('mark', 'verify-form-'.$data['table'])->get()->getRowArray();
        if ($menu) {
            // 更新
            /*
            $this->db->table('admin_menu')->where('id', intval($menu['id']))->update([
                'name' => dr_lang('%s审核', $data['name']),
                'icon' => $data['setting']['icon'],
            ]);*/
        } else {
            // 新增菜单
            $menu = $this->db->table('admin_menu')->where('mark', 'content-verify')->get()->getRowArray();
            if ($menu) {
                $this->_add('admin', $menu['id'], [
                    'name' => dr_lang('%s审核', $data['name']),
                    'icon' => (string)$data['setting']['icon'],
                    'uri' => 'form/'.$data['table'].'_verify/index',
                ], 'verify-form-'.$data['table']);
            }
        }
        // 用户菜单
        $menu = $this->db->table('member_menu')->where('mark', 'form-'.$data['table'])->get()->getRowArray();
        if ($menu) {
            // 更新
            /*
            $this->db->table('member_menu')->where('id', intval($menu['id']))->update([
                'name' => dr_lang('%s管理', $data['name']),
                'icon' => (string)$data['setting']['icon'],
            ]);*/
        } else {
            // 新增菜单
            $menu = $this->db->table('member_menu')->where('mark', 'content-module')->get()->getRowArray();
            if ($menu) {
                $this->_add('member', $menu['id'], [
                    'name' => dr_lang('%s管理', $data['name']),
                    'icon' => (string)$data['setting']['icon'],
                    'uri' => 'form/'.$data['table'].'/index',
                ], 'form-'.$data['table']);
            }
        }

    }

    // 后台菜单合并
    private function _admin_add_menu($menu, $new) {

        foreach ($new as $mk1 => $top) {
            // 合并顶级菜单
            if ($mk1 && isset($menu[$mk1])) {
                // 它存在分组菜单时才合并
                if ($top['left']) {
                    foreach ($top['left'] as $mk2 => $left) {
                        if ($mk2 && isset($menu[$mk1]['left'][$mk2])) {
                            foreach ($left['link'] as $link) {
                                $menu[$mk1]['left'][$mk2]['link'][] = $link;
                            }
                        } else {
                            $menu[$mk1]['left'][] = $left;
                        }
                    }
                }
            } else {
                $menu[$mk1] = $top;
            }
        }

        return $menu;
    }

    // 用户菜单合并
    private function _member_add_menu($menu, $new) {

        foreach ($new as $mk1 => $top) {
            // 合并顶级菜单
            if ($mk1 && isset($menu[$mk1])) {
                // 它存在下级菜单时才合并
                if ($top['link']) {
                    foreach ($top['link'] as $left) {
                        $menu[$mk1]['link'][] = $left;
                    }
                }
            } else {
                $menu[$mk1] = $top;
            }
        }

        return $menu;
    }

    // 更具mark获取id
    private function _get_id_for_mark($table, $mark) {

        if (!$mark) {
            return 0;
        }

        $data = $this->db->table($table.'_menu')->where('mark', $mark)->get()->getRowArray();

        return (int)$data['id'];
    }

    // 安装app时的操作
    public function add_app($dir) {

        $path = dr_get_app_dir($dir);
        if (!is_file($path.'Config/Menu.php')) {
            return;
        }

        $menu = require $path.'Config/Menu.php';
        if (!$menu) {
            return;
        }

        if ($menu['admin']) {
            // 后台菜单
            foreach ($menu['admin'] as $mark => $top) {
                // 插入顶级菜单
                $mark = strlen($mark) > 2 ? $mark : '';
                $top_id = $top['name'] ? $this->_add('admin', 0, $top, $mark, true) : $this->_get_id_for_mark('admin', $mark);
                // 插入分组菜单
                if ($top_id && $top['left']) {
                    foreach ($top['left'] as $mark2 => $left) {
                        $mark2 = strlen($mark2) > 2 ? $mark2 : '';
                        $left_id = $left['name'] ? $this->_add('admin', $top_id, $left, $mark2, true) : $this->_get_id_for_mark('admin', $mark2);
                        // 插入链接菜单
                        if ($left_id) {
                            foreach ($left['link'] as $key => $link) {
                                if ($this->counts('admin_menu', 'pid='.$left_id.' and `uri`="'.$link['uri'].'"')) {
                                    continue;
                                }
                                $id = $this->_add('admin', $left_id, $link, $link['mark'], 1);
                                if (!$link['mark']) {
                                    $this->_edit('admin', $id, [
                                        'mark' => 'app-'.$dir.'-'.$id,
                                    ]);
                                }
                            }
                        }
                    }
                }
            }
        }

        if ($menu['member']) {
            // 用户菜单
            foreach ($menu['member'] as $mark => $top) {
                // 插入顶级菜单
                $mark = strlen($mark) > 2 ? $mark : '';
                $top_id = $top['name'] ? $this->_add('member', 0, $top, $mark, true) : $this->_get_id_for_mark('member', $mark);
                // 插入链接菜单
                if ($top_id && $top['link']) {
                    foreach ($top['link'] as $mark2 => $link) {
                        if ($this->counts('member_menu', 'pid='.$top_id.' and `uri`="'.$link['uri'].'"')) {
                            continue;
                        }
                        $id = $this->_add('member', $top_id, $link, $link['mark'], 1);
                        if (!$link['mark']) {
                            $this->_edit('member', $id, [
                                'mark' => 'app-'.$dir.'-'.$id,
                            ]);
                        }
                    }
                }

            }
        }
    }

    // 卸载app时的操作
    public function delete_app($dir) {

        $path = dr_get_app_dir($dir);
        if (!is_file($path.'Config/Menu.php')) {
            return;
        }

        $menu = require $path.'Config/Menu.php';
        if (!$menu) {
            return;
        }

        if ($menu['admin']) {
            // 后台菜单
            foreach ($menu['admin'] as $mark => $top) {
                // 插入顶级菜单
                $mark = strlen($mark) > 2 ? $mark : '';
                $top_id = $this->_get_id_for_mark('admin', $mark);
                // 插入分组菜单
                if ($top_id && $top['left']) {
                    foreach ($top['left'] as $mark2 => $left) {
                        $mark2 = strlen($mark2) > 2 ? $mark2 : '';
                        $left_id = $this->_get_id_for_mark('admin', $mark2);
                        // 插入链接菜单
                        if ($left_id) {
                            foreach ($left['link'] as $link) {
                                $this->db->table('admin_menu')->where('pid='.$left_id.' and `uri`="'.$link['uri'].'"')->delete();
                            }
                            // 判断当前分组菜单是否为空
                            if (!$this->db->table('admin_menu')->where('pid='.$left_id)->countAllResults()) {
                                $this->db->table('admin_menu')->where('id='.$left_id)->delete();
                            }
                        }
                    }
                }
                // 判断当前顶级菜单是否为空
                if ($top_id && !$this->db->table('admin_menu')->where('pid='.$top_id)->countAllResults()) {
                    $this->db->table('admin_menu')->where('id='.$top_id)->delete();
                }
            }
        }

        if ($menu['member']) {
            // 用户菜单
            foreach ($menu['member'] as $mark => $top) {
                // 插入顶级菜单
                $mark = strlen($mark) > 2 ? $mark : '';
                $top_id = $this->_get_id_for_mark('member', $mark);
                // 插入链接菜单
                if ($top_id && $top['link']) {
                    foreach ($top['link'] as $link) {
                        $this->db->table('member_menu')->where('pid='.$top_id.' and `uri`="'.$link['uri'].'"')->delete();
                    }
                    //
                }
                // 判断当前顶级菜单是否为空
                if ($top_id && !$this->db->table('member_menu')->where('pid='.$top_id)->countAllResults()) {
                    $this->db->table('member_menu')->where('id='.$top_id)->delete();
                }
            }
        }


        $this->db->table('admin_menu')->where('mark', 'app-'.$dir)->delete();
        $this->db->table('member_menu')->where('mark', 'app-'.$dir)->delete();

        $this->db->table('admin_menu')->like('mark', 'app-'.$dir.'%')->delete();
        $this->db->table('member_menu')->like('mark', 'app-'.$dir.'%')->delete();
    }

    // 初始化菜单
    public function init($table = '') {

        // 程序自定义菜单
        if (is_file(MYPATH.'Config/Menu.php')) {
            $menu = require MYPATH.'Config/Menu.php';
        }

        if (!$menu) {
            $menu = require CMSPATH.'Config/Menu.php';
        }

        // 子程序菜单
        $local = dr_dir_map(dr_get_app_list(), 1);
        foreach ($local as $dir) {
            $path = dr_get_app_dir($dir);
            if (is_file($path.'Config/Menu.php')) {
                if (is_file($path.'Config/App.php')) {
                    $cfg = require $path.'Config/App.php';
                    if ($cfg['type'] == 'app' && !is_file($path.'install.lock')) {
                        // 表示应用插件
                        continue;
                    } elseif ($cfg['type'] == 'module' && !$this->counts('module', '`dirname`="'.strtolower($dir).'"')) {
                        // 表示模块
                        continue;
                    }
                    $_menu = require $path.'Config/Menu.php';
                    if ($_menu) {
                        $_menu['admin'] && $menu['admin'] = $this->_admin_add_menu($menu['admin'], $_menu['admin']);
                        $_menu['member'] && $menu['member'] = $this->_member_add_menu($menu['member'], $_menu['member']);
                    }
                }
            }
        }

        if ($table == 'admin' || !$table) {
            // 清空表
            $this->db->table('admin_menu')->truncate();
            $this->db->table('admin_menu')->emptyTable();
            foreach ($menu['admin'] as $mark => $top) {
                // 插入顶级菜单
                $mark = strlen($mark) > 2 ? $mark : '';
                $top_id = $this->_add('admin', 0, $top, $mark, true);
                // 插入分组菜单
                if ($top_id) {
                    foreach ($top['left'] as $mark2 => $left) {
                        $mark2 = strlen($mark2) > 2 ? $mark2 : '';
                        $left_id = $this->_add('admin', $top_id, $left, $mark2, true);
                        // 插入链接菜单
                        if ($left_id) {
                            foreach ($left['link'] as $link) {
                                $this->_add('admin', $left_id, $link);
                            }
                        }
                    }
                }
            }
        } else {
            // 清空表
            $this->db->table('member_menu')->truncate();
            $this->db->table('member_menu')->emptyTable();
            foreach ($menu['member'] as $mark => $top) {
                // 插入顶级菜单
                $mark = strlen($mark) > 2 ? $mark : '';
                $top_id = $this->_add('member', 0, $top, $mark, true);
                // 插入链接菜单
                if ($top_id) {
                    foreach ($top['link'] as $mark2 => $link) {
                        $this->_add('member', $top_id, $link);
                    }
                }
            }
        }

        #\Phpcmf\Service::M('Form')->cache(); // 更新表单菜单

    }

    // 获取单个
    public function getRowData($table, $id) {

        return $this->db->table($table.'_menu')->where('id', $id)->get()->getRowArray();
    }

    // 获取菜单
    public function gets($table) {

        $menu = [];
        $data = $this->db->table($table.'_menu')
		//->where('mark<>"cloud"')
		->orderBy('displayorder ASC,id ASC')
		->get()->getResultArray();

        if ($data) {

            $top = $left = [];
            // 第一级
            foreach ($data as $i => $t) {
                $t['pid'] == 0 && $top[] = $t['id'];
            }
            // 第二级
            foreach ($data as $i => $t) {
                in_array($t['pid'], $top) && $left[$t['id']] = $t['pid'];
            }
            // 第三级
            foreach ($data as $i => $t) {
                if (isset($left[$t['pid']])) {
                    $data[$i]['mark'] = $t['uri'] ? $t['uri'] : $t['url'];
                    $data[$i]['tid'] = $left[$t['pid']];
                }
				/*
                if (strpos($t['uri'], 'cloud/') === 0 && substr_count($t['uri'], '/') == 1) {
                    unset($data[$i]);
                }*/
            }

            $menu = \Phpcmf\Service::L('tree')->get($data);
        }

        return $menu;
    }

    // 获取顶级菜单
    public function get_top($table) {

        $menu = [];
        $data = $this->db->table($table.'_menu')->where('pid', 0)->orderBy('displayorder ASC,id ASC')->get()->getResultArray();

        if ($data) {
            foreach ($data as $t) {
                $menu[$t['id']] = $t;
            }
        }

        return $menu;
    }

    // 操作菜单
    public function _uesd($table, $id) {

        return $this->table($table.'_menu')->used($id, 'hidden');
    }

    public function _save($table, $id, $name, $value) {

        return $this->table($table.'_menu')->save($id, $name, $value);
    }

    public function _update($table, $id, $data) {

        return $this->table($table.'_menu')->update($id, $data);
    }

    public function _delete($table, $ids) {

        if ($ids) {
            $this->ids = array();
            foreach ($ids as $id) {
                $this->_get_id($table, (int)$id);
            }
            $this->ids && $this->db->table($table.'_menu')->whereIn('id', $this->ids)->delete();
        }
    }

    // 获取自己id和子id
    private function _get_id($table, $id) {

        if (!$id) {
            return NULL;
        }

        $this->ids[$id] = $id;

        $data = $this->db->table($table.'_menu')->where('pid', $id)->get()->getResultArray();
        if (!$data) {
            return NULL;
        }

        foreach ($data as $t) {
            $this->ids[$t['id']] = $t['id'];
            $this->_get_id($table, (int)$t['id']);
        }
    }

    /**
     * 父级菜单选择
     *
     * @param	intval	$level	级别
     * @param	intval	$id		选中项id
     * @param	intval	$name	select部分
     * @return	string
     */
    public function parent_select($table, $level, $id = NULL, $name = NULL) {

        $select = $name ? $name : '<select class="form-control" name="data[pid]">';

        switch ($level) {
            case 1: // 顶级菜单
                $select.= '<option value="0">'.dr_lang('顶级菜单').'</option>';
                break;
            case 2: // 分组菜单
                $topdata = $this->db->table($table.'_menu')->select('id,name')->where('pid=0')->get()->getResultArray();
                foreach ($topdata as $t) {
                    $select.= '<option value="'.$t['id'].'"'.($id == $t['id'] ? ' selected' : '').'>'.$t['name'].'</option>';
                }
                break;
            case 3: // 链接菜单
                $topdata = $this->db->table($table.'_menu')->select('id,name')->where('pid=0')->get()->getResultArray();
                foreach ($topdata as $t) {
                    $select.= '<optgroup label="'.$t['name'].'">';
                    $linkdata = $this->db->table($table.'_menu')->select('id,name')->where('pid='.$t['id'])->get()->getResultArray();
                    foreach ($linkdata as $c) {
                        $select.= '<option value="'.$c['id'].'"'.($id == $c['id'] ? ' selected' : '').'>'.$c['name'].'</option>';
                    }
                    $select.= '</optgroup>';
                }
                break;
        }

        $select.= '</select>';

        return $select;
    }

    // 缓存
    public function cache($site = SITE_ID) {


        $menu = [
            'admin' => [],
            'member' => [],
            'admin-uri' => [],
        ];

        // admin 菜单
        $data = $this->db->table('admin_menu')->where('hidden', 0)->orderBy('displayorder ASC,id ASC')->get()->getResultArray();
        if ($data) {
            $list = [];
            foreach ($data as $t) {
                if ($t['pid'] == 0) {
                    $list[$t['id']] = $t;
                    foreach ($data as $m) {
                        if ($m['pid'] == $t['id']) {
                            $list[$t['id']]['left'][$m['id']] = $m;
                            foreach ($data as $n) {
                                if ($n['pid'] == $m['id']) {
                                    $n['tid'] = $t['id'];
                                    $n['uri'] = str_replace('admin/', '', $n['uri']);
                                    $n['pid'] == $m['id'] && $list[$t['id']]['left'][$m['id']]['link'][$n['id']] = $n;
                                    $menu['admin-uri'][$n['uri']] = $n;
                                }
                            }
                        }
                    }
                }
            }
            $menu['admin'] = $list;
        }
        // member 菜单
        $data = $this->db->table('member_menu')->where('hidden', 0)->orderBy('displayorder ASC,id ASC')->get()->getResultArray();
        if ($data) {
            $list = [
                'url' => [],
                'uri' => [],
            ];
            foreach ($data as $t) {
                if ($t['pid'] == 0) {
                    $t['site'] = dr_string2array($t['site']);
                    $t['group'] = dr_string2array($t['group']);
                    $list['url'][$t['id']] = $t;
                    foreach ($data as $n) {
                        $n['site'] = dr_string2array($n['site']);
                        $n['group'] = dr_string2array($n['group']);
                        if ($n['pid'] == $t['id']) {
                            $list['url'][$t['id']]['link'][$n['id']] = $n;
                            $n['uri'] && $list['uri'][$n['uri']] = [
                                'id' => $n['id'],
                                'pid' => $t['id'],
                                'icon' => $n['icon'],
                                'picon' => $t['icon'],
                                'name' => $n['name'],
                                'pname' => $t['name'],
                            ];
                        }
                    }
                }
            }
            $menu['member'] = $list;
        }

        \Phpcmf\Service::L('cache')->set_file('menu-admin', $menu['admin']);
        \Phpcmf\Service::L('cache')->set_file('menu-admin-uri', $menu['admin-uri']);
        \Phpcmf\Service::L('cache')->set_file('menu-member', $menu['member']);

        return $menu;
    }

}