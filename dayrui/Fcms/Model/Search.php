<?php namespace Phpcmf\Model;
/**
 * {{www.xunruicms.com}}
 * {{迅睿内容管理框架系统}}
 * 本文件是框架系统文件，二次开发时不可以修改本文件，可以通过继承类方法来重写此文件
 **/

// 模块搜索类
class Search extends \Phpcmf\Model {

    public $mytable; // 模块表名称
    public $module; // 模块属性
    public $catid; // 栏目id
    public $get; // 搜索参数

    // 初始化搜索主表
    public function init($table) {
        $this->mytable = dr_module_table_prefix($table, SITE_ID);
        return $this;
    }

    // 获取搜索参数
    public function get_param($module) {

        $get = $_GET;
        $get = isset($get['rewrite']) ? dr_search_rewrite_decode($get['rewrite'], $module['setting']['search']) : $get;
        if ($get) {
            $get = \Phpcmf\Service::L('input')->xss_clean($get);
        }

        $get['s'] = $get['c'] = $get['m'] = $get['id'] = null;
        unset($get['s'], $get['c'], $get['m'], $get['id']);
        if (!$get && IS_API_HTTP) {
            $get = \Phpcmf\Service::L('input')->xss_clean($_POST);
        }

        $_GET['page'] = $get['page'];
        if (isset($get['catdir']) && $get['catdir']) {
            $catid = (int)$module['category_dir'][$get['catdir']];
            unset($get['catid']);
        } else {
            $catid = (int)$get['catid'];
            isset($get['catid']) && $get['catid'] = $catid;
        }
        if (isset($get['keyword'])) {
            $get['keyword'] = dr_safe_replace($get['keyword']);
        }

        $this->get = $get;
        $this->catid = $catid;
        $this->module = $module;

        // 挂钩点 搜索之前对参数处理
        \Phpcmf\Hooks::trigger('search_param', $get);

        return [$catid, $get];
    }

    /**
     * 查询数据并设置缓存
     */
    public function get_data() {

        // 模块表名称
        $table = $this->dbprefix($this->mytable);

        // 排序查询参数
        ksort($this->get);
        $param = $this->get;
        $catid = $this->catid;
        $param_new = [];
        $this->get['order'] = $this->get['page'] = null;
        unset($this->get['order'], $this->get['page']);

        // 查询缓存
        $id = md5($table.dr_array2string($this->get).$catid);
        if (!IS_DEV && SYS_CACHE_SEARCH) {
            $data = $this->db->table($this->mytable.'_search')->where('id', $id)->get()->getRowArray();
            $time = SYS_CACHE_SEARCH * 3600;
            if ($data && $data['inputtime'] + $time < SYS_TIME) {
                $this->db->table($this->mytable.'_search')->where('id', $id)->delete();
                $data = [];
            }
        } else {
            $data = [];
        }

        // 缓存不存在重新入库更新缓存
        if (!$data) {

            $this->get['keyword'] = $this->get['catid'] = null;
            unset($this->get['keyword'], $this->get['catid']);

            // 主表的字段
            $field = \Phpcmf\Service::L('cache')->get('table-'.SITE_ID, $this->dbprefix($this->mytable));
            if (!$field) {
                return dr_return_data(0, dr_lang('主表【%s】字段不存在', $this->mytable));
            }

            $mod_field = $this->module['field'];
            foreach ($field as $i) {
                if (!isset($mod_field[$i])) {
                    $mod_field[$i] = ['ismain' => 1];
                }
            }

            // 默认搜索条件
            $where = [
                'status' => '`'.$table.'`.`status` = 9'
            ];

            // 栏目的字段
            if ($catid) {
                $more = 0;
                $cat_field = $this->module['category'][$catid]['field'];
                // 副栏目判断
                if (isset($this->module['field']['catids']) && $this->module['field']['catids']['fieldtype'] == 'Catids') {
                    $fwhere = [];
                    if ($this->module['category'][$catid]['child'] && $this->module['category'][$catid]['childids']) {
                        $fwhere[] = '`'.$table.'`.`catid` IN ('.$this->module['category'][$catid]['childids'].')';
                        $catids = explode(',', $this->module['category'][$catid]['childids']);
                    } else {
                        $fwhere[] = '`'.$table.'`.`catid` = '.$catid;
                        $catids = [ $catid ];
                    }
                    foreach ($catids as $c) {
                        if (version_compare(\Phpcmf\Service::M()->db->getVersion(), '5.7.0') < 0) {
                            // 兼容写法
                            $fwhere[] = '`'.$table.'`.`catids` LIKE "%\"'.intval($c).'\"%"';
                        } else {
                            // 高版本写法
                            $fwhere[] = "(`{$table}`.`catids` <>'' AND JSON_CONTAINS (`{$table}`.`catids`->'$[*]', '\"".intval($c)."\"', '$'))";
                        }
                    }
                    $fwhere && $where['catid'] = '('.implode(' OR ', $fwhere).')';
                } else {
                    // 无副栏目时
                    $where['catid'] = '`'.$table.'`.`catid`'.($this->module['category'][$catid]['child'] ? 'IN ('.$this->module['category'][$catid]['childids'].')' : '='.(int)$catid);
                }

                if ($cat_field) {
                    // 栏目模型表
                    $more_where = [];
                    $table_more = $this->dbprefix($this->mytable.'_category_data');
                    foreach ($cat_field as $name) {
                        if (isset($this->get[$name]) && strlen($this->get[$name])) {
                            $more = 1;
                            $r = $this->_where($table_more, $name, $this->get[$name], $this->module['category_data_field'][$name]);
                            if ($r) {
                                $more_where[] = $r;
                                $param_new[$name] = $this->get[$name];
                            }
                        }
                        /*
                        if (isset($_order_by[$name])) {
                            $more = 1;
                            $order_by[] = '`'.$table.'`.`'.$name.'` '.$_order_by[$name];
                        }*/
                    }
                    $more && $where[] = '`'.$table.'`.`id` IN (SELECT `id` FROM `'.$table_more.'` WHERE '.implode(' AND ', $more_where).')';
                }
            }
            /*
            if (dr_is_app('fstatus') && isset($this->module['field']['fstatus']) && $this->module['field']['fstatus']['ismain']) {
                $where[] = [ '`'.$table.'`.`fstatus` = 1' ];
            }*/
            // 查找mwhere目录
            $mwhere = \Phpcmf\Service::Mwhere_Apps();
            if ($mwhere) {
                list($siteid, $mid) = explode('_', $this->mytable);
                foreach ($mwhere as $mapp) {
                    $w = require dr_get_app_dir($mapp).'Config/Mwhere.php';
                    if ($w) {
                        $where[] = $w;
                    }
                }
            }

            // 关键字匹配条件
            if ($param['keyword'] != '') {
                $temp = [];
                $sfield = explode(',', $this->module['setting']['search']['field'] ? $this->module['setting']['search']['field'] : 'title,keywords');
                $search_keyword = dr_safe_keyword($param['keyword']);
                if ($sfield) {
                    foreach ($sfield as $t) {
                        if ($t && dr_in_array($t, $field)) {
                            $temp[] = $this->module['setting']['search']['complete'] ? '`'.$table.'`.`'.$t.'` = "'.$search_keyword.'"' : '`'.$table.'`.`'.$t.'` LIKE "%'.$search_keyword.'%"';
                        }
                    }
                }
                $where['keyword'] = $temp ? '('.implode(' OR ', $temp).')' : ($this->module['setting']['search']['complete'] ? '`'.$table.'`.`title` = "'.$search_keyword.'"' : '`'.$table.'`.`title` LIKE "%'.$search_keyword.'%"');
                $param_new['keyword'] = $search_keyword;
            }

            // 模块字段过滤
            foreach ($mod_field as $name => $field) {
                if (isset($field['ismain']) && !$field['ismain']) {
                    continue;
                }
                if (isset($this->get[$name]) && strlen($this->get[$name])) {
                    $r = $this->_where($table, $name, $this->get[$name], $field);
                    if ($r) {
                        $where[$name] = $r;
                        $param_new[$name] = $this->get[$name];
                    }
                }
            }

            if (IS_USE_MEMBER) {
                // 会员字段过滤
                $member_where = [];
                if (\Phpcmf\Service::C()->member_cache['field']) {
                    foreach (\Phpcmf\Service::C()->member_cache['field'] as $name => $field) {
                        if (isset($field['ismain']) && !$field['ismain']) {
                            continue;
                        }
                        if (!isset($mod_field[$name]) && isset($this->get[$name]) && strlen($this->get[$name])) {
                            $r = $this->_where($this->dbprefix('member_data'), $name, $this->get[$name], $field);
                            if ($r) {
                                $member_where[] = $r;
                                $param_new[$name] = $this->get[$name];
                            }
                        }
                    }
                }
                // 按会员组搜索时
                if ($param['groupid'] != '') {
                    $member_where[] = '`'.$this->dbprefix('member_data').'`.`id` IN (SELECT `uid` FROM `'.$this->dbprefix('member').'_group_index` WHERE gid='.intval($param['groupid']).')';
                    $param_new['groupid'] = $this->get['groupid'];
                }
                // 组合会员字段
                if ($member_where) {
                    $where[] =  '`'.$table.'`.`uid` IN (select `id` from `'.$this->dbprefix('member_data').'` where '.implode(' AND ', $member_where).')';
                }
            }

            // flag
            if (isset($param['flag']) && $param['flag']) {
                $wh = [];
                $arr = explode('|', $param['flag']);
                foreach ($arr as $k) {
                    $wh[] = intval($k);
                }
                $where[] =  '`'.$table.'`.`id` IN (select `id` from `'.$table.'_flag` where `flag` in ('.implode(',', $wh).'))';
                $param_new['flag'] = $param['flag'];
            }

            // 筛选空值
            foreach ($where as $i => $t) {
                if (dr_strlen($t) == 0) {
                    unset($where[$i]);
                }
            }

            // 自定义组合查询
            isset($param['catid']) && $param_new['catid'] = $param['catid'];
            isset($param['keyword']) && $param_new['keyword'] = $param['keyword'];
            $where = $this->mysearch($this->module, $where, $param_new);
            $where = $where ? implode(' AND ', $where) : '';
            $where_sql = $where ? 'WHERE '.$where : '';

            // 组合sql查询结果
            $sql = "SELECT `{$table}`.`id` FROM `".$table."` {$where_sql} ORDER BY NULL ";

            // 统计搜索数量
            $ct = $this->db->query("SELECT count(*) as t FROM `".$table."` {$where_sql} ORDER BY NULL ")->getRowArray();
            $data = [
                'id' => $id,
                'catid' => intval($catid),
                'params' => dr_array2string(['param' => $param_new, 'sql' => $sql, 'where' => $where]),
                'keyword' => $param['keyword'] ? $param['keyword'] : '',
                'contentid' => intval($ct['t']),
                'inputtime' => SYS_TIME
            ];
            if ($ct['t']) {
                // 存储数据
                $this->db->table($this->mytable.'_search')->replace($data);
            }
        }

        // 格式化值
        $p = dr_string2array($data['params']);
        $data['sql'] = $p['sql'];
        $data['where'] = $p['where'];
        $data['params'] = $p['param'];
        if (isset($param['catdir']) && $param['catdir'] && $catid) {
            # 目录栏目模式
            unset($data['params']['catid']);
        } elseif ($catid) {
            $data['params']['catid'] = $catid;
        }
        isset($param['order']) && $data['params']['order'] = $param['order']; // order 参数不变化

        return $data;
    }

    // 自定义组合查询条件
    protected function mysearch($module, $where, $get) {
        return $where;
    }

}