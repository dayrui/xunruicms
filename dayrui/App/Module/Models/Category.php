<?php namespace Phpcmf\Model\Module;
/**
 * {{www.xunruicms.com}}
 * {{迅睿内容管理框架系统}}
 * 本文件是框架系统文件，二次开发时不可以修改本文件，可以通过继承类方法来重写此文件
 **/

// 栏目模型类

class Category extends \Phpcmf\Model {

    protected $tablename;
    protected $categorys;

    // 初始化模型
    public function init($data) {
        parent::init($data);
        $this->tablename = $data['table'];
        return $this;
    }

    // 检查目录是否可用
    public function check_dirname($id, $pid, $value) {
        
        if (!$value) {
            return dr_return_data(0, dr_lang('目录不能为空'));
        } elseif (!preg_match('/^[a-z0-9 \_\-]*$/i', $value)) {
            return dr_return_data(0, dr_lang('目录格式不能包含特殊符号或文字'));
        } elseif (defined('SYS_CAT_RNAME') && SYS_CAT_RNAME) {
            return dr_return_data(1);
        } else {
            if ($pid) {
                $pcat = $this->table($this->tablename)->get($pid);
                if ($pcat && $this->table($this->tablename)->where('id<>'.$id)
                    ->where('pdirname', $pcat['dirname'].'/')
                    ->where('dirname', $value)->counts()) {
                    return dr_return_data(0, dr_lang('目录不能重复（可以在栏目属性设置中关闭重复验证）'));
                }
            } elseif ($this->table($this->tablename)->where('id<>'.$id)
                ->where('pdirname=""')->where('dirname', $value)->counts()) {
                return dr_return_data(0, dr_lang('目录不能重复（可以在栏目属性设置中关闭重复验证）'));
            }
        }

        return dr_return_data(1);
    }

    // 检查栏目上限
    public function check_counts($id, $fix = 0) {

        if ($id) {
            return 0;
        }

        return 0;
    }

    /**
     * 找出子目录列表
     *
     * @param	array	$data
     * @return	bool
     */
    protected function get_categorys($data = array()) {

        if (is_array($data) && !empty($data)) {
            foreach ($data as $catid => $c) {
                $result = [];
                $this->categorys[$catid] = $c;
                foreach ($this->categorys as $_k => $_v) {
                    if ($_v['pid']) {
                        $result[] = $_v;
                    }
                }
            }
        }

        return true;
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

        if ($n > 100 || !is_array($this->categorys)
            || !isset($this->categorys[$catid])) {
            return FALSE;
        }

        $pid = $this->categorys[$catid]['pid'];
        $pids = $pids ? $pid.','.$pids : $pid;
        if ($pid) {
            $pids = $this->get_pids($pid, $pids, ++$n);
        }
        //  : $this->categorys[$catid]['pids'] = $pids;

        return $pids;
    }

    /**
     * 获取子栏目ID列表
     *
     * @param	$catid	栏目ID
     * @return	string
     */
    protected function get_childids($catid, $n = 1) {

        $childids = $catid;

        if ($n > 100 || !is_array($this->categorys) || !isset($this->categorys[$catid])) {
            return $childids;
        }

        if (is_array($this->categorys)) {
            foreach ($this->categorys as $id => $cat) {
                if ($cat['pid'] && $id != $catid && $cat['pid'] == $catid) {
                    $childids.= ','.$this->get_childids($id, ++$n);
                }
            }
        }

        return $childids;
    }

    // 获取栏目下级ids
    protected function _get_next_ids($catid) {

        $rt = [];

        if (is_array($this->categorys)) {
            foreach ($this->categorys as $id => $cat) {
                if ($cat['pid'] == $catid) {
                    $rt[] = $id;
                }
            }
        }

        return $rt;
    }

    /**
     * 所有父目录
     *
     * @param	$catid	��ĿID
     * @return	string
     */
    public function get_pdirname($catid) {

        if ($this->categorys[$catid]['pid']==0) {
            return '';
        }

        $t = $this->categorys[$catid];
        $pids = $t['pids'];
        $pids = explode(',', $pids);
        $catdirs = [];
        krsort($pids);

        foreach ($pids as $id) {
            if ($id == 0) {
                continue;
            }
            $catdirs[] = $this->categorys[$id]['dirname'];
            if ($this->categorys[$id]['pdirname'] == '') {
                break;
            }
        }
        krsort($catdirs);

        return implode('/', $catdirs).'/';
    }

    /**
     * 获取全部父级的mid值, 或者更新
     */
    public function get_parent_mid($category, $id, $update = 0) {

        if (!isset($category[$id])) {
            return [];
        }

        $mid = '';
        $ids = dr_array2array(explode(',',  $category[$id]['childids']), explode(',',  $category[$id]['pids']));
        foreach ($ids as $id) {
            if ($id && $category[$id] && $category[$id]['mid']) {
                $mid = $category[$id]['mid'];
                break;
            }
        }

        return [$mid, $ids];
    }

    /**
     * 格式化父级栏目模块mid
     */
    public function update_parent_mid($category, $catid) {

        if (!isset($category[$catid])) {
            return;
        }

        $ids = explode(',',  $category[$catid]['childids']);
        if (!$ids) {
            return;
        }

        $mid = [];

        foreach ($ids as $id) {
            $id
            && $category[$id]
            && $category[$id]['tid'] == 1
            && $category[$id]['mid']
            && $mid[] = $category[$id]['mid'];
        }

        /* 当栏目下面存在多个模块时
        $mid && dr_count(array_unique($mid)) > 1 && $this->table($this->tablename)->update((int)$catid, array(
            'mid' => '',
            'tid' => 0
        ));*/
    }

    /**
     * 获取菜单数据
     */
    public function cat_data($pid) {
        return $this->table($this->tablename)->where('pid', $pid)->order_by('displayorder ASC,id ASC')->getAll();
    }

    /**
     * 修复菜单数据
     */
    public function repair($_data = [], $dirname = '') {

        $this->categorys = $this->categorys_dir = $categorys = [];
        !$_data && $_data = $this->table($this->tablename)->where('disabled', 0)->order_by('displayorder ASC,id ASC')->getAll();
        if (!$_data) {
            return;
        }

        // 全部栏目数据
        foreach ($_data as $t) {
            $t['setting'] = dr_string2array($t['setting']);
            $this->categorys[$t['id']] = $categorys[$t['id']] = $t;
        }

        foreach ($this->categorys as $catid => $cat) {

            $this->categorys[$catid]['pids'] = $this->get_pids($catid);
            $this->categorys[$catid]['childids'] = $this->get_childids($catid);
            $this->categorys[$catid]['child'] = is_numeric($this->categorys[$catid]['childids']) ? 0 : 1;
            $this->categorys[$catid]['pdirname'] = $this->get_pdirname($catid);
            //$this->categorys[$catid]['next_ids'] = $this->_get_next_ids($catid);

            if ($cat['pdirname'] != $this->categorys[$catid]['pdirname']
                || $cat['pids'] != $this->categorys[$catid]['pids']
                || $cat['childids'] != $this->categorys[$catid]['childids']
                || $cat['child'] != $this->categorys[$catid]['child']) {
                // 当库中与实际不符合才更新数据表
                // 更新数据库
                $this->table($this->tablename)->update($cat['id'], [
                    'pids' => $this->categorys[$catid]['pids'],
                    'child' => $this->categorys[$catid]['child'],
                    'childids' => $this->categorys[$catid]['childids'],
                    'pdirname' => $this->categorys[$catid]['pdirname']
                ]);
            }

            if ($this->categorys[$catid]['child'] == 1 && $this->categorys[$catid]['catids']) {
                $ispost = 0;
                foreach ($t['catids'] as $i) {
                    // 当此栏目还存在下级栏目时,逐步判断全部下级栏目是否具备发布权限
                    if (isset($cat[$i]) && $cat[$i]['child'] == 0) {
                        $ispost = 1; // 可以发布 表示此栏目可用
                        break;
                    }
                }
                if (!$ispost) {
                    // ispost = 0 表示此栏目没有发布权限
                    //$is_cks = 1;
                    continue;
                }
            }

            // 共享栏目是更新mid值
            if ($dirname == 'share' && $this->categorys[$catid]['child']) {
                $this->update_parent_mid($this->categorys, $catid);
            }
            $this->categorys_dir[$t['dirname']] = $t['id'];
        }

        return $this->categorys;
    }

    // 用于删除时获取的数据
    public function data_for_delete() {

        $cache = [];
        // 全部栏目
        $data = $this->db->table($this->tablename)->orderBy('displayorder ASC,id ASC')->get()->getResultArray();
        if ($data) {
            foreach ($data as $t) {
                $cache[$t['id']] = $t;
            }
        }
        
        return $cache;
    }

    // 用于移动时获取的数据
    public function data_for_move() {

        $cache = [];
        // 全部栏目
        $data = $this->db->table($this->tablename)->orderBy('displayorder ASC,id ASC')->get()->getResultArray();
        if ($data) {
            foreach ($data as $t) {
                $cache[$t['id']] = $t;
            }
        }

        return $cache;
    }

    // 复制属性
    public function copy_value($at, $setting, $id) {

        $row = $this->table($this->tablename)->get($id);
        if (!$row) {
            return;
        }

        $save = $row['setting'] = dr_string2array($row['setting']);
        $arr = explode(',', $at);
        foreach ($arr as $at) {
            if ($at == 'tpl') {
                $save['template'] = $setting['template'];
                $save['template']['pagesize'] = $row['setting']['template']['pagesize'];
                $save['template']['mpagesize'] = $row['setting']['template']['mpagesize'];
            } elseif ($at == 'url') {
                $save['urlrule'] = $setting['urlrule'];
            } elseif ($at == 'html') {
                $save['html'] = $setting['html'];
            } elseif ($at == 'seo') {
                $save['seo'] = $setting['seo'];
            } elseif ($at == 'size') {
                $save['template']['pagesize'] = $setting['template']['pagesize'];
                $save['template']['mpagesize'] = $setting['template']['mpagesize'];
            } elseif ($at == 'cat_field') {
                $save['cat_field'] = $setting['cat_field'];
            }
        }

        $this->table($this->tablename)->update($id, [
            'setting' => dr_array2string($save),
        ]);
    }

    // 删除内容模块
    public function delete_content($cats, $module) {

        /*
        if (!$cats) {
            return;
        }
        
        if ($module['share']) {
            // 共享模块单独删除
            foreach ($cats as $t) {
                $mod = \Phpcmf\Service::L('cache')->get('module-'.SITE_ID.'-content', $t['mid']);
               if ($mod && $t['mid']) {
                   // 删除栏目模型字段
					$this->db->table('field')->where('relatedid', $t['id'])
                       ->where('relatedname', 'share-'.SITE_ID)->delete();
					if (!$this->db->tableExists($this->dbprefix(dr_module_table_prefix($t['mid'])))) {
					   continue;
					}   
                   // 删除内容
                   $this->table(dr_module_table_prefix($t['mid']))->where('catid', $t['id'])->delete();
                   $this->table(dr_module_table_prefix($t['mid']).'_draft')->where('catid', $t['id'])->delete();
                   $this->table(dr_module_table_prefix($t['mid']).'_flag')->where('catid', $t['id'])->delete();
                   $this->table(dr_module_table_prefix($t['mid']).'_index')->where('catid', $t['id'])->delete();
                   $this->table(dr_module_table_prefix($t['mid']).'_time')->where('catid', $t['id'])->delete();
                   $this->table(dr_module_table_prefix($t['mid']).'_verify')->where('catid', $t['id'])->delete();
                   $this->table(dr_module_table_prefix($t['mid']).'_category_data')->where('catid', $t['id'])->delete();
                   // 附表分表删除
                   for ($i = 0; $i <= 255 ;$i++) {
                       $table = $this->dbprefix(dr_module_table_prefix($t['mid']).'_data_'.$i);
                       if (!$this->db->tableExists($table)) {
                           continue;
                       }
                       $this->table($table)->where('catid', $t['id'])->delete();
                   }
                   for ($i = 0; $i <= 255 ;$i++) {
                       $table = $this->dbprefix(dr_module_table_prefix($t['mid']).'_category_data_'.$i);
                       if (!$this->db->tableExists($table)) {
                           continue;
                       }
                       $this->table($table)->where('catid', $t['id'])->delete();
                   }
                   // 删除表单
                   if ($mod['form']) {
                       foreach ($mod['form'] as $form) {
                           $ftable = dr_module_table_prefix($t['mid']).'_form_'.$form['table'];
                           $this->table($ftable)->where('catid', $t['id'])->delete();
                           for ($i = 0; $i <= 255 ;$i++) {
                               $table = $this->dbprefix($ftable.'_data_'.$i);
                               if (!$this->db->tableExists($table)) {
                                   continue;
                               }
                               $this->table($table)->where('catid', $t['id'])->delete();
                           }
                       }
                   }
               }

            }
        } else {
            // 独立模块批量删除
            $catids = [];
            foreach ($cats as $t) {
                $catids[] = $t['id'];
                // 删除栏目模型字段
                $this->db->table('field')->where('relatedid', $t['id'])
                    ->where('relatedname', APP_DIR.'-'.SITE_ID)->delete();
            }
            // 批量删除
            $this->table(dr_module_table_prefix(APP_DIR))->where_in('catid', $catids)->delete();
            $this->table(dr_module_table_prefix(APP_DIR).'_draft')->where_in('catid', $catids)->delete();
            $this->table(dr_module_table_prefix(APP_DIR).'_flag')->where_in('catid', $catids)->delete();
            $this->table(dr_module_table_prefix(APP_DIR).'_index')->where_in('catid', $catids)->delete();
            $this->table(dr_module_table_prefix(APP_DIR).'_time')->where_in('catid', $catids)->delete();
            $this->table(dr_module_table_prefix(APP_DIR).'_verify')->where_in('catid', $catids)->delete();
            $this->table(dr_module_table_prefix(APP_DIR).'_category_data')->where_in('catid', $catids)->delete();
            // 附表分表删除
            for ($i = 0; $i <= 255 ;$i++) {
                $table = $this->dbprefix(dr_module_table_prefix(APP_DIR).'_data_'.$i);
                if (!$this->db->tableExists($table)) {
                    continue;
                }
                $this->table($table)->where_in('catid', $catids)->delete();
            }
            for ($i = 0; $i <= 255 ;$i++) {
                $table = $this->dbprefix(dr_module_table_prefix(APP_DIR).'_category_data_'.$i);
                if (!$this->db->tableExists($table)) {
                    continue;
                }
                $this->table($table)->where_in('catid', $catids)->delete();
            }
            // 删除表单
            if ($module['form']) {
                foreach ($module['form'] as $form) {
                    $ftable = dr_module_table_prefix(APP_DIR).'_form_'.$form['table'];
                    $this->table($ftable)->where_in('catid', $catids)->delete();
                    for ($i = 0; $i <= 255 ;$i++) {
                        $table = $this->dbprefix($ftable.'_data_'.$i);
                        if (!$this->db->tableExists($table)) {
                            continue;
                        }
                        $this->table($table)->where_in('catid', $catids)->delete();
                    }
                }
            }
        }
        */
    }

    // 兼容老版本
    public function get_tree_category($data) {
        return [];
    }

    // 找到主栏目id
    public function get_ismain_id($cats, $id) {
        if ($cats[$id]['ismain']) {
            return $id;
        }
        if ($cats[$id]['pids']) {
            $arr = array_reverse(explode(',', $cats[$id]['pids']));
            foreach ($arr as $t) {
                if ($cats[$t]['ismain']) {
                    return $t;
                }
            }
        }
        return 0;
    }

}