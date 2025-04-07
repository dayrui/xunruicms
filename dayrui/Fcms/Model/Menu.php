<?php namespace Phpcmf\Model;
/**
 * {{www.xunruicms.com}}
 * {{迅睿内容管理框架系统}}
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
        $data['name'] = dr_lang($data['name']);

        if ($table == 'admin') {
            // 重复判断
            if ($data['uri'] && $this->table('admin_menu')->where('uri', $data['uri'])->counts()) {
                // 链接菜单判断重复
                return $is_return ? 0 : dr_return_data(0, dr_lang('系统路径已经存在'), ['field' => 'uri']);
            } elseif ($mark && $r = $this->table('admin_menu')->where('mark', $mark)->getRow()) {
                return $is_return ? $r['id'] : dr_return_data(0, dr_lang('标识字符已经存在'), ['field' => 'uri']);
            }
            $this->db->table('admin_menu')->replace([
                'pid' => $pid,
                'name' => $data['name'],
                'uri' => $data['uri'] ? $data['uri'] : '',
                'url' => $data['url'] ? $data['url'] : '',
                'mark' => $mark ? $mark : (string)$data['mark'],
                'site' => $data['site'] ? (string)$data['site'] : '',
                'icon' => $data['icon'] ? $data['icon'] : '',
                'hidden' => (int)$data['hidden'],
                'displayorder' => (int)$data['displayorder'],
            ]);
        } elseif ($table == 'admin_min') {
            // 重复判断
            if ($data['uri'] && $this->table('admin_min_menu')->where('uri', $data['uri'])->counts()) {
                // 链接菜单判断重复
                return $is_return ? 0 : dr_return_data(0, dr_lang('系统路径已经存在'), ['field' => 'uri']);
            } elseif ($data['uri'] && !$this->table('admin_menu')->where('uri', $data['uri'])->counts()) {
                // 判断完整菜单表
                return $is_return ? 0 : dr_return_data(0, dr_lang('系统路径没有存在于完整菜单中'), ['field' => 'uri']);
            } elseif ($mark && $r = $this->table('admin_min_menu')->where('mark', $mark)->counts()) {
                return $is_return ? $r['id'] : dr_return_data(0, dr_lang('标识字符已经存在'), ['field' => 'uri']);
            }
            $this->db->table('admin_min_menu')->replace([
                'pid' => $pid,
                'name' => $data['name'],
                'uri' => $data['uri'] ? $data['uri'] : '',
                'url' => $data['url'] ? $data['url'] : '',
                'mark' => $mark ? $mark : (string)$data['mark'],
                'site' => $data['site'] ? (string)$data['site'] : '',
                'icon' => $data['icon'] ? $data['icon'] : '',
                'hidden' => (int)$data['hidden'],
                'displayorder' => (int)$data['displayorder'],
            ]);
        } elseif ($this->is_table_exists('member_menu')) {
            // 重复 判断
            if ($data['uri']  && $this->table('member_menu')->where('uri', $data['uri'])->counts()) {
                // 链接菜单判断重复
                return $is_return ? 0 : dr_return_data(0, dr_lang('系统路径已经存在'), ['field' => 'uri']);
            } elseif ($mark && $r = $this->table('member_menu')->where('mark', $mark)->counts()) {
                return $is_return ? $r['id'] : dr_return_data(0, dr_lang('标识字符已经存在'), ['field' => 'uri']);
            }
            $group = [];
            if ($data['group']) {
                foreach ($data['group'] as $i) {
                    $group[$i] = $i;
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
                'site' => $data['site'] ? (string)$data['site'] : '',
                'client' => $data['client'] ? (string)$data['client'] : '',
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
        } elseif ($table == 'admin_min') {
            $this->db->table('admin_min_menu')->where('id', (int)$id)->update($data);
        } elseif ($this->is_table_exists('member_menu')) {
            $this->db->table('member_menu')->where('id', (int)$id)->update($data);
        }

        return $id;
    }

    // 后台菜单合并
    protected function _admin_add_menu($menu, $new) {

        foreach ($new as $mk1 => $top) {
            // 合并顶级菜单
            if ($mk1 && isset($menu[$mk1])) {
                // 它存在分组菜单时才合并
                if (!isset($menu[$mk1]['name']) && $top['name']) {
                    $menu[$mk1]['name'] = $top['name'];
                }
                if (!isset($menu[$mk1]['icon']) && $top['icon']) {
                    $menu[$mk1]['icon'] = $top['icon'];
                }
                if ($top['left']) {
                    foreach ($top['left'] as $mk2 => $left) {
                        if ($mk2 && isset($menu[$mk1]['left'][$mk2])) {
                            if (!isset($menu[$mk1]['left'][$mk2]['name']) && $left['name']) {
                                $menu[$mk1]['left'][$mk2]['name'] = $left['name'];
                            }
                            if (!isset($menu[$mk1]['left'][$mk2]['icon']) && $left['icon']) {
                                $menu[$mk1]['left'][$mk2]['icon'] = $left['icon'];
                            }
                            foreach ($left['link'] as $link) {
                                $menu[$mk1]['left'][$mk2]['link'][] = $link;
                            }
                        } elseif ($mk2) {
                            $menu[$mk1]['left'][$mk2] = $left;
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
    protected function _member_add_menu($menu, $new) {

        foreach ($new as $mk1 => $top) {
            // 合并顶级菜单
            if ($mk1 && isset($menu[$mk1])) {
                if (!isset($menu[$mk1]['name']) && $top['name']) {
                    $menu[$mk1]['name'] = $top['name'];
                }
                if (!isset($menu[$mk1]['icon']) && $top['icon']) {
                    $menu[$mk1]['icon'] = $top['icon'];
                }
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
    protected function _get_id_for_mark($table, $mark) {

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

        $afirst = '';
        if ($menu['admin']) {
            // 后台菜单
            foreach ($menu['admin'] as $mark => $top) {
                // 插入顶级菜单
                $mark = strlen($mark) > 2 ? $mark : '';
                $top_id = $top['name'] ? $this->_add('admin', 0, $top, $mark, true) : 0;
                !$top_id && $top_id = $this->_get_id_for_mark('admin', $mark);
                // 插入分组菜单
                if ($top_id && $top['left'] && is_array($top['left'])) {
                    foreach ($top['left'] as $mark2 => $left) {
                        $mark2 = strlen($mark2) > 2 ? $mark2 : '';
                        $left_id = $left['name'] ? $this->_add('admin', $top_id, $left, $mark2, true) : 0;
                        !$left_id && $left_id = $this->_get_id_for_mark('admin', $mark2);
                        // 插入链接菜单
                        if ($left_id && $left['link'] && is_array($left['link'])) {
                            foreach ($left['link'] as $key => $link) {
                                if (!$afirst && $link['uri']) {
                                    $afirst = $link['uri']; // 第一个菜单
                                }
                                if ($this->counts('admin_menu', 'pid='.$left_id.' and `uri`=\''.$link['uri'].'\'')) {
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

        if ($menu['member'] && $this->is_table_exists('member_menu')) {
            // 用户菜单
            foreach ($menu['member'] as $mark => $top) {
                // 插入顶级菜单
                $mark = strlen($mark) > 2 ? $mark : '';
                $top_id = $top['name'] ? $this->_add('member', 0, $top, $mark, true) : 0;
                !$top_id && $top_id = $this->_get_id_for_mark('member', $mark);
                // 插入链接菜单
                if ($top_id && $top['link'] && is_array($top['link'])) {
                    foreach ($top['link'] as $mark2 => $link) {
                        if ($this->counts('member_menu', 'pid='.$top_id.' and `uri`=\''.$link['uri'].'\'')) {
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

        if ($menu['admin_min']) {
            foreach ($menu['admin_min'] as $mark => $top) {
                $mark = strlen($mark) > 2 ? $mark : '';
                $top_id = $top['name'] ? $this->_add('admin_min', 0, $top, $mark, true) : 0;
                !$top_id && $top_id = $this->_get_id_for_mark('admin_min', $mark);
                // 插入链接菜单
                if ($top_id && $top['link'] && is_array($top['link'])) {
                    foreach ($top['link'] as $mark2 => $link) {
                        if ($this->counts('admin_min_menu', 'pid='.$top_id.' and `uri`=\''.$link['uri'].'\'')) {
                            continue;
                        }
                        $id = $this->_add('admin_min', $top_id, $link, $link['mark'], 1);
                        if (!$link['mark']) {
                            $this->_edit('admin_min_menu', $id, [
                                'mark' => 'app-'.$dir.'-'.$id,
                            ]);
                        }
                    }
                }
            }
        }

        return $afirst;
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
                                $this->db->table('admin_menu')->where('pid='.$left_id.' and `uri`=\''.$link['uri'].'\'')->delete();
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

        if ($menu['member'] && $this->is_table_exists('member_menu')) {
            // 用户菜单
            foreach ($menu['member'] as $mark => $top) {
                // 插入顶级菜单
                $mark = strlen($mark) > 2 ? $mark : '';
                $top_id = $this->_get_id_for_mark('member', $mark);
                // 插入链接菜单
                if ($top_id && $top['link']) {
                    foreach ($top['link'] as $link) {
                        $this->db->table('member_menu')->where('pid='.$top_id.' and `uri`=\''.$link['uri'].'\'')->delete();
                    }
                    //
                }
                // 判断当前顶级菜单是否为空
                if ($top_id && !$this->db->table('member_menu')->where('pid='.$top_id)->countAllResults()) {
                    $this->db->table('member_menu')->where('id='.$top_id)->delete();
                }
            }
        }

        if ($menu['admin_min']) {
            // 简化菜单
            foreach ($menu['admin_min'] as $mark => $top) {
                // 插入顶级菜单
                $mark = strlen($mark) > 2 ? $mark : '';
                $top_id = $this->_get_id_for_mark('admin_min', $mark);
                // 插入链接菜单
                if ($top_id && $top['link']) {
                    foreach ($top['link'] as $link) {
                        $this->db->table('admin_min_menu')->where('pid='.$top_id.' and `uri`=\''.$link['uri'].'\'')->delete();
                    }
                    //
                }
                // 判断当前顶级菜单是否为空
                if ($top_id && !$this->db->table('admin_min_menu')->where('pid='.$top_id)->countAllResults()) {
                    $this->db->table('admin_min_menu')->where('id='.$top_id)->delete();
                }
            }
        }


        $this->db->table('admin_menu')->where('mark', 'app-'.$dir)->delete();
        $this->db->table('admin_min_menu')->where('mark', 'app-'.$dir)->delete();

        $this->db->table('admin_menu')->like('mark', 'app-'.$dir.'%')->delete();
        $this->db->table('admin_min_menu')->like('mark', 'app-'.$dir.'%')->delete();

        if ($this->is_table_exists('member_menu')) {
            $this->db->table('member_menu')->where('mark', 'app-'.$dir)->delete();
            $this->db->table('member_menu')->like('mark', 'app-'.$dir.'%')->delete();
        }
    }

    // 查询老库值
    private function _get_old_value($rp, $t) {
        $key = ((string)$t['name'].(string)$t['mark'].(string)$t['uri'].(string)$t['url']);
        if (isset($rp[$key]) && $rp[$key]) {
            return $rp[$key];
        }
        return '';
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
        $local = \Phpcmf\Service::Apps();
        foreach ($local as $dir => $path) {
            if (is_file($path.'Config/Menu.php')) {
                if (is_file($path.'Config/App.php')) {
                    $cfg = require $path.'Config/App.php';
                    if ($cfg['type'] == 'app' && !is_file($path.'install.lock')) {
                        // 表示应用插件
                        continue;
                    } elseif ($cfg['type'] == 'module' && IS_USE_MODULE
                        && !$this->counts('module', '`dirname`=\''.strtolower($dir).'\'')) {
                        // 表示模块
                        continue;
                    }
                    $_menu = require $path.'Config/Menu.php';
                    if ($_menu) {
                        $_menu['admin'] && $menu['admin'] = $this->_admin_add_menu($menu['admin'], $_menu['admin']);
                        $_menu['member'] && $menu['member'] = $this->_member_add_menu($menu['member'], $_menu['member']);
                        $_menu['admin_min'] && $menu['admin_min'] = $this->_member_add_menu($menu['admin_min'], $_menu['admin_min']);
                    }
                }
            }
        }

        if ($table == 'admin' || !$table) {
            // 清空表
            $rp = [];
            $old = $this->table('admin_menu')->where('site<>\'\'')->getAll();
            if ($old) {
                foreach ($old as $t) {
                    $key = ($t['name'].$t['mark'].$t['uri'].$t['url']);
                    $rp[$key] = $t['site'];
                }
            }
            $this->db->table('admin_menu')->truncate();
            $this->db->table('admin_menu')->emptyTable();
            foreach ($menu['admin'] as $mark => $top) {
                // 插入顶级菜单
                $top['mark'] = $mark = strlen($mark) > 2 ? $mark : $top['mark'];
                $top['site'] = $this->_get_old_value($rp, $top);
                $top_id = $this->_add('admin', 0, $top, $mark, true);
                // 插入分组菜单
                if ($top_id) {
                    foreach ($top['left'] as $mark2 => $left) {
                        $left['mark'] = $mark2 = strlen($mark2) > 2 ? $mark2 : $left['mark'];
                        $left['site'] = $this->_get_old_value($rp, $left);
                        $left_id = $this->_add('admin', $top_id, $left, $mark2, true);
                        // 插入链接菜单
                        if ($left_id) {
                            foreach ($left['link'] as $mark3 => $link) {
                                $link['mark'] = $mark3 = strlen($mark3) > 2 ? $mark3 : $link['mark'];
                                $link['site'] = $this->_get_old_value($rp, $link);
                                $this->_add('admin', $left_id, $link, $mark3);
                            }
                        }
                    }
                }
            }
        } elseif ($table == 'admin_min') {
            // 清空表
            $this->db->table('admin_min_menu')->truncate();
            $this->db->table('admin_min_menu')->emptyTable();
            foreach ($menu['admin_min'] as $mark => $top) {
                $mark = strlen($mark) > 2 ? $mark : '';
                $top_id = $this->_add('admin_min', 0, $top, $mark, true);
                // 插入链接菜单
                if ($top_id) {
                    foreach ($top['link'] as $mark2 => $link) {
                        if (is_numeric($mark2)) {
                            $mark2 = $top_id.'-'.$mark2;
                        }
                        $this->_add('admin_min', $top_id, $link, $mark2);
                    }
                }
            }

        } elseif ($this->is_table_exists('member_menu') && $table == 'member') {
            // 清空表
            $site = $group = $client = [];
            $old = $this->table('member_menu')->where('`site`<>\'\' or `group`<>\'\' or `client`<>\'\'')->getAll();
            if ($old) {
                foreach ($old as $t) {
                    $key = ($t['name'].$t['mark'].$t['uri'].$t['url']);
                    $site[$key] = ($t['site']);
                    $group[$key] = dr_string2array($t['group']);
                    $client[$key] = ($t['client']);
                }
            }
            $this->db->table('member_menu')->truncate();
            $this->db->table('member_menu')->emptyTable();
            foreach ($menu['member'] as $mark => $top) {
                // 插入顶级菜单
                $top['mark'] = $mark = strlen($mark) > 2 ? $mark : $top['mark'];
                $top['site'] = $this->_get_old_value($site, $top);
                $top['group'] = $this->_get_old_value($group, $top);
                $top['client'] = $this->_get_old_value($client, $top);
                $top_id = $this->_add('member', 0, $top, $mark, true);
                // 插入链接菜单
                if ($top_id) {
                    foreach ($top['link'] as $mark2 => $link) {
                        if (is_numeric($mark2)) {
                            $mark2 = $top_id.'-'.$mark2;
                        }
                        $link['site'] = $this->_get_old_value($site, $link);
                        $link['group'] = $this->_get_old_value($group, $link);
                        $link['client'] = $this->_get_old_value($client, $link);
                        $this->_add('member', $top_id, $link, $mark2);
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
    public function gets($table, $where = '') {

        $menu = [];
        $db = $this->db->table($table.'_menu');
		$where && $db->where($where);
		$db->orderBy('displayorder ASC,id ASC');
		$data = $db->get()->getResultArray();

        if ($data) {

            $top = $left = [];
            // 第一级
            foreach ($data as $i => $t) {
                if ($t['pid'] == 0) {
                    $top[] = $t['id'];
                    $data[$i]['level'] = 1;
                }
            }
            // 第二级
            foreach ($data as $i => $t) {
                if (dr_in_array($t['pid'], $top)) {
                    $left[$t['id']] = $t['pid'];
                    $data[$i]['level'] = 2;
                }
            }
            // 第三级
            foreach ($data as $i => $t) {
                if (isset($left[$t['pid']])) {
                    $data[$i]['mark'] = $t['uri'] ? $t['uri'] : $t['url'];
                    $data[$i]['tid'] = $left[$t['pid']];
                    $data[$i]['level'] = 3;
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
    protected function _get_id($table, $id) {

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

        $select = $name ? $name : '<select class="form-control bs-select" data-live-search="true" name="data[pid]">';

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
        $select.= \Phpcmf\Service::L('Field')->get('select')->get_select_search_code();

        return $select;
    }

    // 缓存
    public function cache($site = SITE_ID) {

        $menu = [
            'admin' => [],
            'member' => [],
            'admin-uri' => [],
            'admin-min' => [],
        ];

        // admin 菜单
        $data = $this->db->table('admin_menu')->where('hidden', 0)->orderBy('displayorder ASC,id ASC')->get()->getResultArray();
        if ($data) {
            $list = [];
            foreach ($data as $t) {
                if ($t['pid'] == 0) {
                    $t['site'] = dr_string2array($t['site']);
                    $list[$t['id']] = $t;
                    foreach ($data as $m) {
                        if ($m['pid'] == $t['id']) {
                            $m['site'] = dr_string2array($m['site']);
                            $list[$t['id']]['left'][$m['id']] = $m;
                            foreach ($data as $n) {
                                if ($n['pid'] == $m['id']) {
                                    $n['tid'] = $t['id'];
                                    $n['uri'] = str_replace('admin/', '', $n['uri']);
                                    $n['site'] = dr_string2array($n['site']);
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
        if ($this->is_table_exists('member_menu')) {
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
                        $t['client'] = dr_string2array($t['client']);
                        $list['url'][$t['id']] = $t;
                        foreach ($data as $n) {
                            $n['site'] = dr_string2array($n['site']);
                            $n['group'] = dr_string2array($n['group']);
                            $n['client'] = dr_string2array($n['client']);
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
                        if (!$list['url'][$t['id']]['link']) {
                            unset($list['url'][$t['id']]);
                        }
                    }
                }
                $menu['member'] = $list;
            }
        }

        // admin-min 菜单
        $data = $this->db->table('admin_min_menu')->where('hidden', 0)->orderBy('displayorder ASC,id ASC')->get()->getResultArray();
        if ($data) {
            $list = [];
            foreach ($data as $t) {
                if ($t['pid'] == 0) {
                    $list[$t['id']] = $t;
                    foreach ($data as $n) {
                        $n['site'] = dr_string2array($n['site']);
                        if ($n['pid'] == $t['id']) {
                            $list[$t['id']]['link'][$n['id']] = $n;
                        }
                    }
                }
            }
            $menu['admin-min'] = $list;
        }

        \Phpcmf\Service::L('cache')->set_file('menu-admin', $menu['admin']);
        \Phpcmf\Service::L('cache')->set_file('menu-admin-uri', $menu['admin-uri']);
        \Phpcmf\Service::L('cache')->set_file('menu-member', $menu['member']);
        \Phpcmf\Service::L('cache')->set_file('menu-admin-min', $menu['admin-min']);

        return $menu;
    }

    public function update_module($mdir, $config, $form) {
        \Phpcmf\Service::M('menu', 'module')->update_module($mdir, $config, $form);
    }
}