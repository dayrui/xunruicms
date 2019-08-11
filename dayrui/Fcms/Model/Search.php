<?php namespace Phpcmf\Model;

/* *
 *
 * Copyright [2019] [李睿]
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * www.xunruicms.com
 *
 * 本文件是框架系统文件，二次开发时不建议修改本文件
 *
 * */


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

        // 栏目模型表
        $table_more = $this->dbprefix($this->mytable.'_category_data');

        // 排序查询参数
        ksort($get);
        $param = $get;
        $get['order'] = $get['page'] = null;
        unset($get['order'], $get['page']);

        // 查询缓存
        $id = md5(MOD_DIR.dr_array2string($get));
        if (SYS_CACHE_SEARCH) {
            $data = $this->db->table($this->mytable.'_search')->where('id', $id)->get()->getRowArray();
            $time = intval(SYS_CACHE_SEARCH) * 3600;
            if ($data && $data['inputtime'] < SYS_TIME - $time) {
                $this->db->table($this->mytable.'_search')->where('id', $id)->delete();
                //$this->db->table($this->mytable.'_search_index')->where('id', $id)->delete();
                //$this->db->query('REPAIR TABLE  `'.$table.'_search_index`');
                $data = [];
            }
        } else {
            $data = [];
        }

        // 缓存不存在重新入库更新缓存
        if (!$data) {

            $get['keyword'] = $get['catid'] = null;
            unset($get['keyword'], $get['catid']);

            $from = '`'.$table.'`';

            // 主表的字段
            $field = \Phpcmf\Service::L('cache')->get('table-'.SITE_ID, $this->dbprefix($this->mytable));
            if (!$field) {
                return dr_return_data(0, dr_lang('主表【%s】字段不存在', $this->mytable));
            }

            $mod_field = $module['field'];
            foreach ($field as $i) {
                !isset($mod_field[$i]) && $mod_field[$i] = ['ismain' => 1];
            }

            // 搜索关键字条件
            $where = [];
            $where[] = '`'.$table.'`.`status` = 9';

            // 关键字匹配条件
            if ($param['keyword'] != '') {
                $sfield = explode(',', $module['setting']['search']['field'] ? $module['setting']['search']['field'] : 'title,keywords');
                !$sfield && $sfield = 'title,keywords';
                $temp = [];
                $search_keyword = trim(str_replace([' ', '_'], '%', dr_safe_replace($param['keyword'])), '%');
                foreach ($sfield as $t) {
                    $t && $temp[] = '`'.$table.'`.`'.trim($t).'` LIKE "%'.$search_keyword.'%"';
                }
                $where[] = '('.implode(' OR ', $temp).')';
            }
            // 字段过滤
            foreach ($mod_field as $name => $field) {
                if (isset($field['ismain']) && !$field['ismain']) {
                    continue;
                }
                isset($get[$name]) && strlen($get[$name]) && $where[] = $this->_where($table, $name, $get[$name], $field);
                // 地图坐标排序，这里不用它，默认id
                /*
                if (isset($_order_by[$name])) {
                    if (isset($field['fieldtype']) && $field['fieldtype'] == 'Baidumap') {
                        $order_by[] =   '`id` desc ';
                    } else {
                        $order_by[] = '`'.$table.'`.`'.$name.'` '.$_order_by[$name];
                    }
                }*/
            }

            // 栏目的字段
            if ($catid) {
                $more = 0;
                $cat_field = $module['category'][$catid]['field'];

                // 副栏目判断
                if (isset($module['field']['catids']) && $module['field']['catids']['fieldtype'] = 'Catids') {
                    $fwhere = [];
                    if ($module['category'][$catid]['child']) {
                        $fwhere[] = '`'.$table.'`.`catid` IN ('.implode(',', $module['category'][$catid]['childids']).')';
                        $catids = @explode(',', $module['category'][$catid]['childids']);
                    } else {
                        $fwhere[] = '`'.$table.'`.`catid` = '.$catid;
                        $catids = [ $catid ];
                    }
                    foreach ($catids as $c) {
                        $fwhere[] = '`'.$table.'`.`catids` LIKE "%\"'.intval($c).'\"%"';
                    }
                    $where[0] = '('.implode(' OR ', $fwhere).')';
                } else {
                    // 无副栏目时
                    $where[0] = '`'.$table.'`.`catid`'.($module['category'][$catid]['child'] ? 'IN ('.$module['category'][$catid]['childids'].')' : '='.(int)$catid);
                }

                if ($cat_field) {
                    $more_where = [];
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
                if (!$t) {
                    unset($where[$i]);
                }
            }


            // 自定义组合查询
            $where = $this->mysearch($module, $where, $get);

            $where = $where ? 'WHERE '.implode(' AND ', $where) : '';

            // 最大数据量
            $limit = (int)$module['setting']['search']['total'] ? ' LIMIT '.(int)$module['setting']['search']['total'] : ' LIMIT 10000';

            // 组合sql查询结果
            $sql = "SELECT `{$table}`.`id` FROM {$from} {$where} ORDER BY NULL".$limit;

            // 重新生成缓存文件
            $result = $this->db->query($sql)->getResultArray();
            if ($result) {
                $cid = [];
                // 删除旧数据
                $this->db->table($this->mytable.'_search')->where('id', $id)->delete();
                //$this->db->table($this->mytable.'_search_index')->where('id', $id)->delete();
                // 入库索引表
                foreach ($result as $t) {
                    /*
                    $this->db->table($this->mytable.'_search_index')->insert([
                        'id' => $id,
                        'cid' => $t['id'],
                        'inputtime' => SYS_TIME
                    ]);*/
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
        }

        // 格式化值
        $p = dr_string2array($data['params']);
        $data['sql'] = $p['sql'];
        $data['params'] = $p['param'];
        $catid && $data['params']['catid'] = $catid;
        $data['params']['order'] = $param['order']; // order 参数不变化

        return $data;
    }

    // 获取搜索参数
    public function get_param($module) {

        $get =  $_GET;
        $get = isset($get['rewrite']) ? dr_search_rewrite_decode($get['rewrite'], $module['setting']['search']) : $get;
        $get && $get = \Phpcmf\Service::L('input')->xss_clean($get);

        $get['s'] = $get['c'] = $get['m'] = $get['id'] = null;
        unset($get['s'], $get['c'], $get['m'], $get['id']);
        if (!$get && IS_API_HTTP) {
            $get = $_POST;
        }

        $_GET['page'] = $get['page'];
        $get['keyword'] = dr_get_keyword($get['keyword']);

        $catid = isset($get['catdir']) && $get['catdir'] ? (int)$module['category_dir'][$get['catdir']] : (int)$get['catid'];
        isset($get['catid']) && $get['catid'] = $catid;

        return [$catid, $get];
    }

    // 自定义组合查询条件
    protected function mysearch($module, $where, $get) {
        return $where;
    }

    // 条件组合
    private function _where($table, $name, $value, $field) {
        $name = dr_safe_replace($name, ['\\', '/']);
        if (strpos($value, '%') === 0 && strrchr($value, '%') === '%') {
            // like 条件
            return '`'.$table.'`.`'.$name.'` LIKE "%'.trim($this->db->escapeString($value, true), '%').'%"';
        } elseif (preg_match('/[0-9]+,[0-9]+/', $value)) {
            // BETWEEN 条件
            list($s, $e) = explode(',', $value);
            return '`'.$table.'`.`'.$name.'` BETWEEN '.(int)$s.' AND '.intval($e ? $e : SYS_TIME);
        } elseif (isset($field['fieldtype']) && $field['fieldtype'] == 'Baidumap') {
            // 百度地图
            if (SITE_MAP_LAT && SITE_MAP_LNG) {
                // 获取Nkm内的数据
                $lat = '`'.$table.'`.`'.$name.'_lat`';
                $lng = '`'.$table.'`.`'.$name.'_lng`';
                $squares = dr_square_point(SITE_MAP_LNG, SITE_MAP_LAT, $value);
                return "({$lat} between {$squares['right-bottom']['lat']} and {$squares['left-top']['lat']}) and ({$lng} between {$squares['left-top']['lng']} and {$squares['right-bottom']['lng']})";
            } else {
                \Phpcmf\Service::C()->goto_404_page(dr_lang('没有定位到您的坐标'));
            }
        } elseif (isset($field['fieldtype']) && $field['fieldtype'] == 'Linkage') {
            // 联动菜单字段
            $arr = explode('|', $value);
            $where = [];
            foreach ($arr as $value) {
                $data = dr_linkage($field['setting']['option']['linkage'], $value);
                if ($data) {
                    if ($data['child']) {
                        $where[] = '`'.$table.'`.`'.$name.'` IN ('.$data['childids'].')';
                    } else {
                        $where[] = '`'.$table.'`.`'.$name.'`='.intval($data['ii']);
                    }
                }
            }
            return '('.implode(' OR ', $where).')';
        } elseif (isset($field['fieldtype']) && $field['fieldtype'] == 'Linkages') {
            // 联动菜单多选字段
            $arr = explode('|', $value);
            $where = [];
            foreach ($arr as $value) {
                $data = dr_linkage($field['setting']['option']['linkage'], $value);
                if ($data) {
                    if ($data['child']) {
                        $ids = explode(',', $data['childids']);
                        foreach ($ids as $id) {
                            if (version_compare(\Phpcmf\Service::M()->db->getVersion(), '5.7.0') < 0) {
                                // 兼容写法
                                $where[] = '`'.$table.'`.`'.$name.'` LIKE "%\"'.intval($id).'\"%"';
                            } else {
                                // 高版本写法
                                $where[] = " JSON_CONTAINS (`{$table}`.`{$name}`->'$[*]', '\"".intval($id)."\"', '$')";
                            }
                        }
                    } else {
                        if (version_compare(\Phpcmf\Service::M()->db->getVersion(), '5.7.0') < 0) {
                            // 兼容写法
                            $where[] = '`'.$table.'`.`'.$name.'` LIKE "%\"'.intval($data['ii']).'\"%"';
                        } else {
                            // 高版本写法
                            $where[] = " JSON_CONTAINS (`{$table}`.`{$name}`->'$[*]', '\"".intval($data['ii'])."\"', '$')";
                        }
                    }
                }
            }
            return '('.implode(' OR ', $where).')';
        } elseif (isset($field['fieldtype']) && $field['fieldtype'] == 'Checkbox') {
            // 复选字段
            $arr = explode('|', $value);
            $where = [];
            foreach ($arr as $value) {
                if (version_compare(\Phpcmf\Service::M()->db->getVersion(), '5.7.0') < 0) {
                    // 兼容写法
                    $where[] = '`'.$table.'`.`'.$name.'` LIKE "%\"'.$this->db->escapeString($value, true).'\"%"';
                } else {
                    // 高版本写法
                    $where[] = " JSON_CONTAINS (`{$table}`.`{$name}`->'$[*]', '\"".$this->db->escapeString($value, true)."\"', '$')";
                }
            }
            return '('.implode(' OR ', $where).')';
        } elseif (isset($field['fieldtype']) && $field['fieldtype'] == 'Radio') {
            // 单选字段
            $arr = explode('|', $value);
            $where = [];
            foreach ($arr as $value) {
                if (is_numeric($value)) {
                    $where[] = '`'.$table.'`.`'.$name.'`='.$value;
                } else {
                    $where[] = '`'.$table.'`.`'.$name.'`="'.dr_safe_replace($value, ['\\', '/']).'"';
                }
            }
            return '('.implode(' OR ', $where).')';
        } elseif (is_numeric($value)) {
            return '`'.$table.'`.`'.$name.'`='.$value;
        } else {
            return '`'.$table.'`.`'.$name.'`="'.dr_safe_replace($value, ['\\', '/']).'"';
        }
    }


}