<?php namespace Phpcmf\Model;

/**
 * http://www.xunruicms.com
 * 本文件是框架系统文件，二次开发时不可以修改本文件，可以通过继承类方法来重写此文件
 **/


// 栏目模型类

class Category extends \Phpcmf\Model
{
    private $tree;
    private $tree_html;
    protected $tablename;
    protected $categorys;

    // 初始化模型
    public function init($data) {
        parent::init($data);
        $this->tablename = $data['table'];
        return $this;
    }

    // 检查目录是否可用
    public function check_dirname($id, $value) {
        
        if (!$value) {
            return 1;
        } elseif (!preg_match('/^[a-z0-9]*$/i', $value)) {
            return 1;
        } elseif (defined('SYS_CAT_RNAME') && SYS_CAT_RNAME) {
            return 0;
        }
        
        return $this->table($this->tablename)->is_exists($id, 'dirname', $value);
    }


    /**
     * 找出子目录列表
     *
     * @param	array	$data
     * @return	bool
     */
    private function get_categorys($data = array()) {

        if (is_array($data) && !empty($data)) {
            foreach ($data as $catid => $c) {
                $this->categorys[$catid] = $c;
                $result = array();
                foreach ($this->categorys as $_k => $_v) {
                    $_v['pid'] && $result[] = $_v;
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
    private function get_pids($catid, $pids = '', $n = 1) {

        if ($n > 100 || !is_array($this->categorys)
            || !isset($this->categorys[$catid])) {
            return FALSE;
        }

        $pid = $this->categorys[$catid]['pid'];
        $pids = $pids ? $pid.','.$pids : $pid;
        $pid ? $pids = $this->get_pids($pid, $pids, ++$n) : $this->categorys[$catid]['pids'] = $pids;

        return $pids;
    }

    /**
     * 获取子栏目ID列表
     *
     * @param	$catid	栏目ID
     * @return	string
     */
    private function get_childids($catid, $n = 1) {

        $childids = $catid;

        if ($n > 100 || !is_array($this->categorys)
            || !isset($this->categorys[$catid])) {
            return $childids;
        }

        if (is_array($this->categorys)) {
            foreach ($this->categorys as $id => $cat) {
                $cat['pid'] && $id != $catid && $cat['pid'] == $catid && $childids.= ','.$this->get_childids($id, ++$n);
            }
        }

        return $childids;
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
        $catdirs = array();
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

        $ids = @explode(',',  $category[$catid]['childids']);
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

        $mid && dr_count(array_unique($mid)) > 1 && $this->table($this->tablename)->update((int)$catid, array(
            'mid' => '',
            'tid' => 0
        ));
    }

    /**
     * 修复菜单数据
     */
    public function repair($_data = [], $dirname = MOD_DIR) {

        $this->categorys = $categorys = [];
        !$_data && $_data = $this->table($this->tablename)->order_by('displayorder ASC,id ASC')->getAll();
        if (!$_data) {
            return;
        }

        // 全部栏目数据
        foreach ($_data as $t) {
            $this->categorys[$t['id']] = $categorys[$t['id']] = $t;
        }

        foreach ($this->categorys as $catid => $cat) {

            $this->categorys[$catid]['pids'] = $this->get_pids($catid);
            $this->categorys[$catid]['childids'] = $this->get_childids($catid);
            $this->categorys[$catid]['child'] = is_numeric($this->categorys[$catid]['childids']) ? 0 : 1;
            $this->categorys[$catid]['pdirname'] = $this->get_pdirname($catid);

            if ($cat['pdirname'] != $this->categorys[$catid]['pdirname']
                || $cat['pids'] != $this->categorys[$catid]['pids']
                || $cat['childids'] != $this->categorys[$catid]['childids']
                || $cat['child'] != $this->categorys[$catid]['child']) {
                // 当库中与实际不符合才更新数据表
                // 更新数据库
                $this->table($this->tablename)->update($cat['id'], array(
                    'pids' => $this->categorys[$catid]['pids'],
                    'child' => $this->categorys[$catid]['child'],
                    'childids' => $this->categorys[$catid]['childids'],
                    'pdirname' => $this->categorys[$catid]['pdirname']
                ));
            }

            $dirname == 'share' && $this->categorys[$catid]['child'] && $this->update_parent_mid($this->categorys, $catid);
        }

        return $this->categorys;
    }

    
    // 用于删除时获取的数据
    public function data_for_delete() {

        $this->repair();

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

        $this->repair();

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

        $row['setting'] = dr_string2array($row['setting']);
        if ($at == 'tpl') {
            $row['setting']['template'] = $setting['template'];
        } elseif ($at == 'seo') {
            $row['setting']['seo'] = $setting['seo'];
            $row['setting']['html'] = $setting['html'];
            $row['setting']['urlrule'] = $setting['urlrule'];
        } else {
            $row['setting'][$at] = $setting[$at];
        }

        $this->table($this->tablename)->update($id, [
            'setting' => dr_array2string($row['setting']),
        ]);
    }

    // 删除内容模块
    public function delete_content($cats, $module) {

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
					if (!$this->db->tableExists($this->dbprefix(SITE_ID.'_'.$t['mid']))) {
					   continue;
					}   
                   // 删除内容
                   $this->table(SITE_ID.'_'.$t['mid'])->where('catid', $t['id'])->delete();
                   $this->table(SITE_ID.'_'.$t['mid'].'_draft')->where('catid', $t['id'])->delete();
                   $this->table(SITE_ID.'_'.$t['mid'].'_flag')->where('catid', $t['id'])->delete();
                   $this->table(SITE_ID.'_'.$t['mid'].'_index')->where('catid', $t['id'])->delete();
                   $this->table(SITE_ID.'_'.$t['mid'].'_time')->where('catid', $t['id'])->delete();
                   $this->table(SITE_ID.'_'.$t['mid'].'_verify')->where('catid', $t['id'])->delete();
                   $this->table(SITE_ID.'_'.$t['mid'].'_comment')->where('catid', $t['id'])->delete();
                   $this->table(SITE_ID.'_'.$t['mid'].'_comment_index')->where('catid', $t['id'])->delete();
                   $this->table(SITE_ID.'_'.$t['mid'].'_category_data')->where('catid', $t['id'])->delete();
                   // 附表分表删除
                   for ($i = 0; $i <= 255 ;$i++) {
                       $table = $this->dbprefix(SITE_ID.'_'.$t['mid'].'_data_'.$i);
                       if (!$this->db->tableExists($table)) {
                           continue;
                       }
                       $this->table($table)->where('catid', $t['id'])->delete();
                   }
                   for ($i = 0; $i <= 255 ;$i++) {
                       $table = $this->dbprefix(SITE_ID.'_'.$t['mid'].'_category_data_'.$i);
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
            $this->table(SITE_ID.'_'.APP_DIR)->where_in('catid', $catids)->delete();
            $this->table(SITE_ID.'_'.APP_DIR.'_draft')->where_in('catid', $catids)->delete();
            $this->table(SITE_ID.'_'.APP_DIR.'_flag')->where_in('catid', $catids)->delete();
            $this->table(SITE_ID.'_'.APP_DIR.'_index')->where_in('catid', $catids)->delete();
            $this->table(SITE_ID.'_'.APP_DIR.'_time')->where_in('catid', $catids)->delete();
            $this->table(SITE_ID.'_'.APP_DIR.'_verify')->where_in('catid', $catids)->delete();
            $this->table(SITE_ID.'_'.APP_DIR.'_comment')->where_in('catid', $catids)->delete();
            $this->table(SITE_ID.'_'.APP_DIR.'_comment_index')->where_in('catid', $catids)->delete();
            $this->table(SITE_ID.'_'.APP_DIR.'_category_data')->where_in('catid', $catids)->delete();
            // 附表分表删除
            for ($i = 0; $i <= 255 ;$i++) {
                $table = $this->dbprefix(SITE_ID.'_'.APP_DIR.'_data_'.$i);
                if (!$this->db->tableExists($table)) {
                    continue;
                }
                $this->table($table)->where_in('catid', $catids)->delete();
            }
            for ($i = 0; $i <= 255 ;$i++) {
                $table = $this->dbprefix(SITE_ID.'_'.APP_DIR.'_category_data_'.$i);
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
        
    }


    // 递归栏目树形结构
    public function _tree_html($pid = 0) {
        $this->_tree_html.= '<ul>';
        foreach ($this->_tree as $t) {
            if ($t['pid'] == $pid) {
                if ($t['child']) {
                    // 下级
                    $this->_tree_html.= ' <li> '.$t['name'];
                    $this->_tree_html($t['id']);
                    $this->_tree_html.= '</li>';
                } else {
                    $url = '';
                    if ($t['tid'] == 1) {
                        $url = dr_url($t['mid'].'/home/index', ['catid' => $t['id']]);
                    } elseif ($t['tid'] == 2) {
                        $url = dr_url('category/edit', ['id' => $t['id']]);
                    } else {
                        $url = dr_url('category/edit', ['id' => $t['id']]);
                    }
                    $this->_tree_html.= ' <li data-jstree=\'\'>
                            <a href="'.$url.'"> '.$t['name'].' </a>
                        </li>';
                }
            }
        }
        $this->_tree_html.= '</ul>';
    }

    public function get_tree_category($data) {

        $this->_tree = $data;
        $this->_tree_html = '';
        $this->_tree_html(0);
        return $this->_tree_html;

    }
}