<?php namespace Phpcmf\Library;

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


/**
 * 静态生成
 */

class Html
{
    private $webpath;

    // 栏目的数量统计
    public function get_category_data($app, $cat) {

        // 获取生成栏目
        !$cat && \Phpcmf\Service::C()->_json(0, '没有可用生成的栏目数据');

        $data = [];
        foreach ($cat as $t) {
            if ($t['tid'] == 0) {
                // 单网页
                $data[] = [
                    'id' => $t['id'],
                    'mid' => $t['mid'],
                    'url' => $t['url'],
                    'page' => 0,
                    'name' => $t['name'],
                    'html' => $t['setting']['html'],
                ];
            } elseif ($t['tid'] == 1) {
                // 模块
                if ($t['child'] && $t['setting']['template']['list'] != $t['setting']['template']['category']) {
                    // 判断是封面页面
                    $data[] = [
                        'id' => $t['id'],
                        'url' => $t['url'],
                        'mid' => $t['mid'],
                        'page' => 0,
                        'name' => $t['name'],
                        'html' => $t['setting']['html'],
                    ];
                } else {
                    // 内容列表页面
                    $db = \Phpcmf\Service::M()->db->table(SITE_ID.'_'.$t['mid'].'_index');
                    $t['child'] ? $db->whereIn('catid', @implode(',', $t['childids'])) : $db->where('catid', (int)$t['id']);
                    $total = $db->countAllResults(); // 统计栏目的数据量
                    $data[] = [
                        'id' => $t['id'],
                        'mid' => $t['mid'],
                        'url' => $t['url'],
                        'page' => 0,
                        'name' => $t['name'],
                        'html' => $t['setting']['html'],
                    ];
                    if ($total) {
                        // 分页
                        if (\Phpcmf\Service::V()->_is_mobile) {
                            $pagesize = (int)$t['setting']['template']['mpagesize']; // 每页数量
                        } else {
                            $pagesize = (int)$t['setting']['template']['pagesize']; // 每页数量
                        }
                        !$pagesize && $pagesize = 10; // 默认10条分页
                        $count = ceil($total/$pagesize); // 计算总页数
                        if ($count > 1) {
                            for ($i = 1; $i <= $count; $i++) {
                                $data[] = [
                                    'id' => $t['id'],
                                    'mid' => $t['mid'],
                                    'url' => $t['url'],
                                    'page' => $i,
                                    'name' => $t['name'].'【第'.$i.'页】',
                                    'html' => $t['setting']['html'],
                                ];
                            }
                        }
                    }
                }
            }
        }

        $data = dr_save_bfb_data($data);
        !dr_count($data) && \Phpcmf\Service::C()->_json(0, '没有可用生成的栏目数据');

        $name = 'category-'.($app ? $app : '').'-html-file';
        \Phpcmf\Service::L('cache')->init()->save($name, $data, 3600);
        \Phpcmf\Service::C()->_json(1, $name);
    }

    // 内容的数量统计
    public function get_show_data($app, $param) {

        // 获取生成栏目
        $cids = [];
        $mids = [];
        if ($param['catids']) {
            $catids = explode(',', $param['catids']);
            if ($catids) {
                $cats = \Phpcmf\Service::C()->get_cache('module-'.SITE_ID.'-'.($app ? $app : 'share'), 'category');
                foreach ($catids as $id) {
                    if ($cats[$id]) {
                        $cids = dr_array2array($cids, explode(',', $cats[$id]['childids']));
                        $cats[$id]['mid'] && $mids[$cats[$id]['mid']] = $cats[$id]['mid'];
                    }
                }
                $cids = array_unique($cids);
            }
        }

        if (!$app) {
            if ($cids) {
                if (!$mids) {
                    \Phpcmf\Service::C()->_json(0, '没有可用生成的内容模块');
                }
                $data = [];
                foreach ($mids as $mid) {
                    $db = \Phpcmf\Service::M()->db->table(SITE_ID.'_'.$mid)->select('id,catid,title,url');
                    if (isset($param['date_form']) && $param['date_form']) {
                        $db->where('`updatetime` BETWEEN ' . strtotime($param['date_form'].' 00:00:00') . ' AND ' . ($param['date_to'] ? strtotime($param['date_to'].' 23:59:59') : SYS_TIME));
                    } elseif (isset($param['date_to']) && $param['date_to']) {
                        $db->where('`updatetime` BETWEEN 0 AND ' . strtotime($param['date_to'].' 23:59:59'));
                    }
                    $db->where('catid IN ('. implode(',', $cids).')');
                    $rows = $db->get()->getResultArray(); // 获取需要生成的内容索引
                    if ($rows) {
                        foreach ($rows as $r) {
                            $r['is_module_dirname'] = $mid;
                            $data[] = $r;
                        }
                    }
                }
            } else {
                \Phpcmf\Service::C()->_json(0, '模块参数app不存在');
            }
        } else {
            $db = \Phpcmf\Service::M()->db->table(SITE_ID.'_'.$app)->select('id,catid,title,url');
            if (isset($param['date_form']) && $param['date_form']) {
                $db->where('`updatetime` BETWEEN ' . strtotime($param['date_form'].' 00:00:00') . ' AND ' . ($param['date_to'] ? strtotime($param['date_to'].' 23:59:59') : SYS_TIME));
            } elseif (isset($param['date_to']) && $param['date_to']) {
                $db->where('`updatetime` BETWEEN 0 AND ' . strtotime($param['date_to'].' 23:59:59'));
            }
            if ($cids) {
                $db->where('catid IN ('. implode(',', $cids).')');
            }
            $data = $db->get()->getResultArray(); // 获取需要生成的内容索引
        }

        $data = dr_save_bfb_data($data);
        !dr_count($data) && \Phpcmf\Service::C()->_json(0, '没有可用生成的内容数据');

        \Phpcmf\Service::L('cache')->init()->save('show-'.$app.'-html-file', $data, 3600);
        \Phpcmf\Service::C()->_json(1, 'ok');
    }

    // 网站文件生成地址
    public function get_webpath($siteid, $mid, $file = '') {

        if (!$this->webpath) {
            $this->webpath = require WRITEPATH.'config/webpath.php';
        }

        $webpath = WEBPATH;
        if (isset($this->webpath[$siteid][$mid]) && $this->webpath[$siteid][$mid]) {
            $webpath = $this->webpath[$siteid][$mid];
        }

        return $webpath.$file;
    }

}