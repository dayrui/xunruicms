<?php namespace Phpcmf\Model;

/**
 * http://www.xunruicms.com
 * 本文件是框架系统文件，二次开发时不可以修改本文件，可以通过继承类方法来重写此文件
 **/


// 模块搜索类
class Search extends \Phpcmf\Model {

    public $mytable; // 模块表名称

    // 初始化搜索主表
    public function init($table) {
        $this->mytable = SITE_ID.'_'.$table;
        return $this;
    }

    /**
     * 查询数据并设置缓存
     */
    public function get($module, $get, $catid) {

        // 模块表名称
        $table = $this->dbprefix($this->mytable);

        // 排序查询参数
        ksort($get);
        $param = $get;
        $get['order'] = $get['page'] = null;
        unset($get['order'], $get['page']);

        // 查询缓存
        $id = md5($table.dr_array2string($get));
        if (SYS_CACHE_SEARCH) {
            $data = $this->db->table($this->mytable.'_search')->where('id', $id)->get()->getRowArray();
            $time = intval(SYS_CACHE_SEARCH) * 3600;
            if ($data && $data['inputtime'] + $time < SYS_TIME) {
                $this->db->table($this->mytable.'_search')->where('id', $id)->delete();
                $data = [];
            }
        } else {
            $data = [];
        }

        // 缓存不存在重新入库更新缓存
        if (!$data) {

            $get['keyword'] = $get['catid'] = null;
            unset($get['keyword'], $get['catid']);

            // 主表的字段
            $field = \Phpcmf\Service::L('cache')->get('table-'.SITE_ID, $this->dbprefix($this->mytable));
            if (!$field) {
                return dr_return_data(0, dr_lang('主表【%s】字段不存在', $this->mytable));
            }

            $mod_field = $module['field'];
            foreach ($field as $i) {
                !isset($mod_field[$i]) && $mod_field[$i] = ['ismain' => 1];
            }

            // 默认搜索条件
            $where = [ '`'.$table.'`.`status` = 9' ];
            /*
            if (dr_is_app('fstatus') && isset($this->module['field']['fstatus']) && $this->module['field']['fstatus']['ismain']) {
                $where[] = [ '`'.$table.'`.`fstatus` = 1' ];
            }*/

            // 关键字匹配条件
            if ($param['keyword'] != '') {
                $temp = [];
                $sfield = explode(',', $module['setting']['search']['field'] ? $module['setting']['search']['field'] : 'title,keywords');
                $search_keyword = trim(str_replace([' ', '_'], '%', dr_safe_replace($param['keyword'])), '%');
                if ($sfield) {
                    foreach ($sfield as $t) {
                        if ($t && in_array($t, $field)) {
                            $temp[] = '`'.$table.'`.`'.$t.'` LIKE "%'.$search_keyword.'%"';
                        }
                    }
                }
                $where[] = $temp ? '('.implode(' OR ', $temp).')' : '`'.$table.'`.`title` LIKE "%'.$search_keyword.'%"';
            }
            // 字段过滤
            foreach ($mod_field as $name => $field) {
                if (isset($field['ismain']) && !$field['ismain']) {
                    continue;
                }
                if (isset($get[$name]) && strlen($get[$name])) {
                    $where[] = $this->_where($table, $name, $get[$name], $field);
                }
            }

            // 栏目的字段
            if ($catid) {
                $more = 0;
                $cat_field = $module['category'][$catid]['field'];
                // 副栏目判断
                if (isset($module['field']['catids']) && $module['field']['catids']['fieldtype'] = 'Catids') {
                    $fwhere = [];
                    if ($module['category'][$catid]['child'] && $module['category'][$catid]['childids']) {
                        $fwhere[] = '`'.$table.'`.`catid` IN ('.$module['category'][$catid]['childids'].')';
                        $catids = @explode(',', $module['category'][$catid]['childids']);
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
                    $fwhere && $where[0] = '('.implode(' OR ', $fwhere).')';
                } else {
                    // 无副栏目时
                    $where[0] = '`'.$table.'`.`catid`'.($module['category'][$catid]['child'] ? 'IN ('.$module['category'][$catid]['childids'].')' : '='.(int)$catid);
                }

                if ($cat_field) {
                    // 栏目模型表
                    $more_where = [];
                    $table_more = $this->dbprefix($this->mytable.'_category_data');
                    foreach ($cat_field as $name => $field) {
                        if (isset($get[$name]) && strlen($get[$name])) {
                            $more = 1;
                            $more_where[] = $this->_where($table_more, $name, $get[$name], $field);
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

            // 筛选空值
            foreach ($where as $i => $t) {
                if (strlen($t) == 0) {
                    unset($where[$i]);
                }
            }

            // 自定义组合查询
            $where = $this->mysearch($module, $where, $get);
            $where = $where ? 'WHERE '.implode(' AND ', $where) : '';

            // 最大数据量
            $limit = (int)$module['setting']['search']['total'] ? ' LIMIT '.(int)$module['setting']['search']['total'] : '';
            // 组合sql查询结果
            $sql = "SELECT `{$table}`.`id` FROM `".$table."` {$where} ORDER BY id ".$limit;

            if ($limit) {
                // 重新生成缓存文件
                $result = $this->db->query($sql)->getResultArray();
                if ($result) {
                    $cid = [];
                    // 删除旧数据
                    $this->db->table($this->mytable.'_search')->where('id', $id)->delete();
                    // 入库索引表
                    foreach ($result as $t) {
                        $cid[] = $t['id'];
                    }
                    // 缓存入库
                    $data = [
                        'id' => $id,
                        'catid' => intval($catid),
                        'params' => dr_array2string(['param' => $param, 'sql' => $sql]),
                        'keyword' => $param['keyword'] ? $param['keyword'] : '',
                        'contentid' => @implode(',', $cid),
                        'inputtime' => SYS_TIME
                    ];
                    $this->db->table($this->mytable.'_search')->replace($data);
                } else {
                    $data = [
                        'id' => $id,
                        'catid' => intval($catid),
                        'params' => dr_array2string(['param' => $param, 'sql' => $sql]),
                        'keyword' => $param['keyword'] ? $param['keyword'] : '',
                        'contentid' => '',
                    ];
                }
            } else {
                // 不限搜索数量
                $ct = $this->db->query("SELECT count(*) as t FROM `".$table."` {$where} ORDER BY id ")->getRowArray();
                if ($ct['t']) {
                    $data = [
                        'id' => $id,
                        'catid' => intval($catid),
                        'params' => dr_array2string(['param' => $param, 'sql' => $sql]),
                        'keyword' => $param['keyword'] ? $param['keyword'] : '',
                        'contentid' => intval($ct['t']),
                        'inputtime' => SYS_TIME
                    ];
                    $this->db->table($this->mytable.'_search')->replace($data);
                } else {
                    $data = [
                        'id' => $id,
                        'catid' => intval($catid),
                        'params' => dr_array2string(['param' => $param, 'sql' => $sql]),
                        'keyword' => $param['keyword'] ? $param['keyword'] : '',
                        'contentid' => '',
                    ];
                }
            }
        }

        // 格式化值
        $p = dr_string2array($data['params']);
        $data['sql'] = $p['sql'];
        $data['params'] = $p['param'];
        if (isset($param['catdir']) && $param['catdir'] && $catid) {
            # 目录栏目模式
            unset($data['params']['catid']);
        } elseif ($catid) {
            $data['params']['catid'] = $catid;
        }
        $data['params']['order'] = $param['order']; // order 参数不变化

        return $data;
    }

    // 获取搜索参数
    public function get_param($module) {

        $get = $_GET;
        $get = isset($get['rewrite']) ? dr_search_rewrite_decode($get['rewrite'], $module['setting']['search']) : $get;
        $get && $get = \Phpcmf\Service::L('input')->xss_clean($get);

        $get['s'] = $get['c'] = $get['m'] = $get['id'] = null;
        unset($get['s'], $get['c'], $get['m'], $get['id']);
        if (!$get && IS_API_HTTP) {
            $get = \Phpcmf\Service::L('input')->xss_clean($_POST);
        }

        $_GET['page'] = $get['page'];
        $get['keyword'] = dr_get_keyword($get['keyword']);

        if (isset($get['catdir']) && $get['catdir']) {
            $catid = (int)$module['category_dir'][$get['catdir']];
            unset($get['catid']);
        } else {
            $catid = (int)$get['catid'];
            isset($get['catid']) && $get['catid'] = $catid;
        }

        return [$catid, $get];
    }

    // 自定义组合查询条件
    protected function mysearch($module, $where, $get) {
        return $where;
    }
}