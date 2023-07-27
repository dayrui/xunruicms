<?php namespace Phpcmf\Model;
/**
 * {{www.xunruicms.com}}
 * {{迅睿内容管理框架系统}}
 * 本文件是框架系统文件，二次开发时不可以修改本文件，可以通过继承类方法来重写此文件
 **/

// 模型类

class Linkage extends \Phpcmf\Model {

    protected $top;
    protected $pids;
    protected $cache;
    protected $categorys;
    protected $child_pids;

    // 创建菜单
    public function create($data) {

        if ($this->table('linkage')->is_exists(0, 'code', $data['code'])) {
            return dr_return_data(0, dr_lang('别名已经存在'));
        }

        $rt = $this->table('linkage')->insert([
            'name' => $data['name'],
            'code' => strtolower($data['code']),
            'type' => (int)$data['type'],
        ]);
        if (!$rt['code']) {
            return $rt;
        }

        // 返回id
        $id = intval($rt['code']);

        // 创建数据表
        $table = $this->dbprefix('linkage_data_'.$id);
        $this->query('DROP TABLE IF EXISTS `'.$table.'`');
        $rt = $this->query(trim("CREATE TABLE IF NOT EXISTS `{$table}` (
		  `id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
		  `site` smallint(5) unsigned NOT NULL,
		  `pid` mediumint(8) unsigned NOT NULL DEFAULT '0' COMMENT '上级id',
		  `pids` varchar(255) DEFAULT NULL COMMENT '所有上级id',
		  `name` varchar(255) NOT NULL COMMENT '菜单名称',
		  `cname` varchar(255) NOT NULL COMMENT '菜单别名',
		  `child` tinyint(1) unsigned DEFAULT NULL DEFAULT '0' COMMENT '是否有下级',
		  `hidden` tinyint(1) unsigned DEFAULT NULL DEFAULT '0' COMMENT '前端隐藏',
		  `childids` text DEFAULT NULL COMMENT '下级所有id',
		  `displayorder` int(10) DEFAULT NULL DEFAULT '0',
		  PRIMARY KEY (`id`),
		  KEY `cname` (`cname`),
		  KEY `hidden` (`hidden`),
		  KEY `list` (`site`,`displayorder`)
		) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='联动菜单".dr_safe_replace($data['name'])."数据表'"));
        if (!$rt['code']) {
            // 创建失败删除数据
            $this->table('linkage')->delete($id);
            return $rt;
        }

        return dr_return_data($id);
    }

    // 批量删除
    public function delete_all($ids, $where = '') {

        foreach ($ids as $id) {
            $row = $this->table('linkage')->get(intval($id));
            if (!$row) {
                return dr_return_data(0, dr_lang('数据不存在(id:%s)', $id));
            }
            $rt = $this->table('linkage')->delete($id);
            if (!$rt['code']) {
                return dr_return_data(0, $rt['msg']);
            }
            // 删除表数据
            $table = $this->dbprefix('linkage_data_'.$id);
            $this->query('DROP TABLE IF EXISTS `'.$table.'`');
        }

        return dr_return_data(1, '');
    }

    // 批量删除
    public function delete_list_all($key, $ids) {

        foreach ($ids as $id) {
            $row = $this->table('linkage_data_'.$key)->get(intval($id));
            if (!$row) {
                return dr_return_data(0, dr_lang('数据不存在(id:%s)', $id));
            }
            if ($row['child']) {
                $rt = $this->table('linkage_data_'.$key)->deleteAll(explode(',', $row['childids']));
            }
            $rt = $this->table('linkage_data_'.$key)->delete($id);
            if (!$rt['code']) {
                return dr_return_data(0, $rt['msg']);
            }
        }

        return dr_return_data(1, '');
    }

    // 批量移动分类
    public function edit_pid_all($key, $pid, $ids) {

        foreach ($ids as $id) {
            $row = $this->table('linkage_data_'.$key)->get(intval($id));
            if (!$row) {
                return dr_return_data(0, dr_lang('数据不存在(id:%s)', $id));
            }

            $rt = $this->table('linkage_data_'.$key)->update($id, ['pid' => $pid]);
            if (!$rt['code']) {
                return dr_return_data(0, $rt['msg']);
            }
        }

        $this->repair([
            'id' => $key,
            'type' => 0
        ]);

        return dr_return_data(1, '');
    }

    // 添加子内容
    public function add_list($key, $data) {

        $pid = intval($data['pid']);

        if ($data['all']) {
            // 批量
            $c = 0;
            $py = \Phpcmf\Service::L('pinyin'); // 拼音转换类
            $names = explode(PHP_EOL, trim($data['all']));
            foreach ($names as $t) {
                $t = trim($t);
                if (!$t) {
                    continue;
                }
                $cname = $py->result($t);
                if (is_numeric($cname)) {
                    $cname = 'a'.$cname;
                } elseif (!preg_match('/^[a-z]+[a-z0-9\_]+$/i', $cname)) {
                    $cname = dr_safe_filename($cname);
                }
                $cf = $this->db->table('linkage_data_'.$key)->where('cname', $cname)->countAllResults();
                $rt = $this->table('linkage_data_'.$key)->insert(array(
                    'pid' => $pid,
                    'pids' => '',
                    'name' => $t,
                    'site' => SITE_ID,
                    'child' => 0,
                    'cname' => $cname,
                    'hidden' => 0,
                    'childids' => '',
                    'displayorder' => 0
                ));
                if (!$rt['code']) {
                    return $rt;
                }
                if ($cf) {
                    // 重复验证
                    $this->table('linkage_data_'.$key)->update($rt['code'], [
                        'cname' => $cname.$rt['code']
                    ]);
                }
                $c++;
            }
            // 更新pid
            $pid && $this->table('linkage_data_'.$key)->update($pid, ['child' => 1]);
            return dr_return_data(1, dr_lang('批量添加%s个', $c));
        } else {
            // 单个
            $data['name'] = trim($data['name']);
            if (!$data['name']) {
                return dr_return_data(0, dr_lang('名称不能为空'));
            } elseif (!$data['cname']) {
                return dr_return_data(0, dr_lang('别名不能为空'));
            }
            if (is_numeric($data['cname'])) {
                $data['cname'] = 'a'.$data['cname'];
            } elseif (!preg_match('/^[a-z]+[a-z0-9\_]+$/i', $data['cname'])) {
                $data['cname'] = dr_safe_filename($data['cname']);
            }
            if ($this->db->table('linkage_data_'.$key)->where('cname', $data['cname'])->countAllResults()) {
                return dr_return_data(0, dr_lang('别名已经存在'));
            }
            $rt = $this->table('linkage_data_'.$key)->insert(array(
                'pid' => $pid,
                'pids' => '',
                'name' => $data['name'],
                'site' => SITE_ID,
                'child' => 0,
                'cname' => $data['cname'],
                'hidden' => 0,
                'childids' => '',
                'displayorder' => 0
            ));
            if (!$rt['code']) {
                return $rt;
            }
            // 更新pid
            $pid && $this->table('linkage_data_'.$key)->update($pid, ['child' => 1]);
            return dr_return_data(1, dr_lang('操作成功'));
        }

    }

    /**
     * 全部子菜单数据
     *
     * @param	array	$link
     * @param	intval	$pid
     * @return	array
     */
    public function getList($link, $pid = 'NULL') {

        $key = (int)$link['id'];

        if ($pid === 'NULL') {
            $name = 'linkage-cahce-list-'.$key.'-'.$pid;
            $data = \Phpcmf\Service::L('cache')->get_data($name);
            if ($data) {
                return $data;
            }
            $db = $this->db->table('linkage_data_'.$key);
            // 站点查询
            //$link['type'] == 1 && $db->where('site', SITE_ID);
            // 获取菜单数据
            $menu = $db->orderBy('displayorder ASC,id ASC')->get()->getResultArray();
            if (!$menu) {
                return [];
            }
            // 格式返回数据
            $data = [];
            foreach ($menu as $t) {
                $data[$t['id']]	= $t;
            }
            \Phpcmf\Service::L('cache')->set_data($name, $data);
        } else {
            $db = $this->db->table('linkage_data_'.$key);
            // 站点查询
            $link['type'] == 1 && $db->where('site', SITE_ID);
            $menu = $db->where('pid', (int)$pid)->orderBy('displayorder ASC,id ASC')->get()->getResultArray();
            if (!$menu) {
                return [];
            }
            // 格式返回数据
            $data = [];
            foreach ($menu as $t) {
                $data[$t['id']]	= $t;
            }
        }

        return $data;
    }

    /**
     * 获取父栏目ID列表
     *
     * @param	integer	$catid	栏目ID
     * @param	array	$pids	父目录ID
     * @param	integer	$n		查找的层次
     * @return	string
     */
    protected function get_pids($catid, $pids = '', $n = 1) {

        if ($n > 100 || !$this->categorys || !isset($this->categorys[$catid])) {
            return FALSE;
        }

        $pid = $this->categorys[$catid]['pid'];
        $pids = $pids ? $pid.','.$pids : $pid;
        $pid && $pids = $this->get_pids($pid, $pids, ++$n);

        return $pids;
    }

    /**
     * 获取子栏目ID列表
     *
     * @param	$catid
     * @return	string
     */
    protected function get_childids($catid, $n = 1) {

        $childids = $catid;

        if ($n > 100 || !is_array($this->categorys)
            || !isset($this->categorys[$catid])) {
            return $childids;
        }

        if ($this->pids[$catid]) {
            foreach ($this->pids[$catid] as $id) {
                $cat = $this->categorys[$id];
                // 避免造成死循环
                $cat['pid']
                && $id != $catid
                && $cat['pid'] == $catid
                && $this->categorys[$catid]['pid'] != $id
                && $childids.= ','.$this->get_childids($id, ++$n);
            }
        }

        return $childids;
    }

    /**
     * 修复菜单数据
     */
    public function repair($link, $siteid = SITE_ID) {

        if (!$link) {
            return;
        }

        $this->categorys = $categorys = [];
        
        // 站点独立 // 共享共享
        $table = 'linkage_data_'.$link['id'];
        $_data = $link['type']
            ? $this->db->table($table)->where('site', $siteid)->orderBy('displayorder ASC,id ASC')->get()->getResultArray()
            : $this->db->table($table)->orderBy('displayorder ASC,id ASC')->get()->getResultArray();
        if (!$_data) {
            return;
        }

        // 全部栏目数据
        foreach ($_data as $t) {
            $this->pids[$t['pid']][] = $t['id']; // 归类
            $categorys[$t['id']] = $this->categorys[$t['id']] = $t;
        }

        $this->child_pids = [0];
        foreach ($this->categorys as $catid => $cat) {
            $this->categorys[$catid]['pids'] = $this->get_pids($catid);
            $this->categorys[$catid]['childids'] = $this->get_childids($catid);
            $this->categorys[$catid]['child'] = is_numeric($this->categorys[$catid]['childids']) ? 0 : 1;
            if ($this->categorys[$catid]['child']) {
                $this->child_pids[] = $catid;
            }
            // 当库中与实际不符合才更新数据表
            if ($categorys[$catid]['pids'] != $this->categorys[$catid]['pids']
                || $categorys[$catid]['childids'] != $this->categorys[$catid]['childids']
                || $categorys[$catid]['child'] != $this->categorys[$catid]['child']) {
                $this->table($table)->update($cat['id'], [
                    'pids' => $this->categorys[$catid]['pids'],
                    'child' => $this->categorys[$catid]['child'],
                    'childids' => $this->categorys[$catid]['childids']
                ]);
            }
        }
        
        return $this->categorys;
    }

    public function get_child_row($pid) {
        $newArr = [];
        foreach ($this->categorys as $cat) {
            $item = [
                'value' => $cat['id'],
                'label' => $cat['name'],
                'children' => [],
            ];
            if ($pid == $cat['pid']) {
                $item['children'] = $this->get_child_row($cat['id']);
                $newArr[] = $item;
            }

        }
        return $newArr;
    }

    public function get_child_pids() {
        return $this->child_pids;
    }

    // 自定义字段
    public function get_fields($id) {

        $rt = [];
        $field = $this->db->table('field')
            ->where('disabled', 0)
            ->where('relatedname', 'linkage')
            ->where('relatedid', intval($id))
            ->orderBy('displayorder ASC,id ASC')
            ->get()->getResultArray();
        if ($field) {
            foreach ($field as $fv) {
                $fv['setting'] = dr_string2array($fv['setting']);
                $rt[$fv['fieldname']] = $fv;
            }
        }

        return $rt;
    }

    // 缓存
    public function cache($siteid = SITE_ID) {

    }

    public function get_json($link, $links) {

        $json = [];
        if ($links) {
            $this->categorys = $links;
            $json = $this->get_child_row(0);
        }

        $data_path = 'linkage/'.SITE_ID.'_'.$link['code'].'/';
        \Phpcmf\Service::L('cache')->set_file('json', $json, $data_path);

    }

    /**
     * 分组缓存菜单数据
     */
    public function cache_list($link, $pid, $field) {

        $path = WRITEPATH.'linkage/';
        dr_mkdirs($path);

        $data_path = 'linkage/'.SITE_ID.'_'.$link['code'].'/';
        dr_mkdirs(WRITEPATH.$data_path);

        // 格式返回数据
        $lv = $data = [];
        $cid = \Phpcmf\Service::L('cache')->get_file('id', $data_path, false);
        !$cid && $cid = [];

        // 执行程序
        $key = (int)$link['id'];
        $db = $this->db->table('linkage_data_'.$key);
        $link['type'] == 1 && $db->where('site', SITE_ID); // 站点查询
        $menu = $db->where('pid', (int)$pid)->orderBy('displayorder ASC,id ASC')->get()->getResultArray();
        if ($menu) {
            foreach ($menu as $t) {
                if ($t['hidden']) {
                    continue;
                }
                $lv[] = substr_count($t['pids'], ',');
                $t['ii'] = $t['iid'] = $t['id'];
                $t['id'] = $t['cname'];
                $cid[$t['ii']] = $t['id'];
                $data[$t['cname']] = \Phpcmf\Service::L('Field')->app('')->format_value($field, $t);
                \Phpcmf\Service::L('cache')->set_file('data-'.$t['cname'], $data[$t['cname']], $data_path);
            }
        }

        \Phpcmf\Service::L('cache')->set_file('list-'.$pid, $data, $data_path);

        \Phpcmf\Service::L('cache')->set_file('id', $cid, $data_path);
        \Phpcmf\Service::L('cache')->set_file('key', $key, $data_path);

        $level_data = (int)\Phpcmf\Service::L('cache')->get_file('level', $data_path, false);
        \Phpcmf\Service::L('cache')->set_file('level', max($lv ? max($lv) : 0, $level_data), $data_path);

        return $data;
    }

}