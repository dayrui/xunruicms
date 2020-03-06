<?php namespace Phpcmf\Model;

/**
 * http://www.xunruicms.com
 * 本文件是框架系统文件，二次开发时不可以修改本文件，可以通过继承类方法来重写此文件
 **/


// 模型类
// 文档状态
// 1 ~ 8 审核流程
// 0 被退回
// 9 正常
// 10 回收站

class Content extends \Phpcmf\Model {

    public $siteid; // 站点id
    public $mytable; // 模块表名称
    public $mysharetable; // 共享模块表名称
    public $is_hcategory; // 模块不使用栏目
    public $is_share; // 是否共享模块

    // 初始化模块
    public function _init($dir, $siteid = SITE_ID, $is_share = 'null') {
        $this->siteid = $siteid;
        $this->dirname = $dir;
        $this->mytable = dr_module_table_prefix($dir, $siteid);
        $this->mysharetable = $siteid.'_share';
        if ($is_share == 'null') {
            $this->is_share = (int)\Phpcmf\Service::L('cache')->get('module-'.$siteid.'-'.$dir, 'share');
        } else {
            $this->is_share = $is_share;
        }
        return $this;
    }

    // 保存内容
    public function save($id, $data, $old = []) {

        // 二次开发函数
        $data = $this->_content_post_before($id, $data, $old);

        // 挂钩点 模块内容发布或修改完成之后
        \Phpcmf\Hooks::trigger('module_content_before', $data);

        if (!$id) {
            // 新增
            // 生成索引id
            $nid = $this->index($id, $data);
            if (!$nid) {
                return dr_return_data(0, dr_lang('内容索引id生成失败'));
            }
            $data[0]['id'] = $data[1]['id'] = $nid;
        } else {
            // 更新
            $this->index($id, array( 1 => array(
                'uid' => $data[1]['uid'],
                'catid' => $data[1]['catid'],
                'status' => $data[1]['status'],
                'inputtime' => $data[1]['updatetime'],
            )));
            $data[0]['id'] = $data[1]['id'] = $id;
            // 如果来自审核页面,且本次通过
            if ( defined('IS_MODULE_VERIFY') && $data[1]['status'] == 9
               && !$this->db->table($this->mytable)->where('id', $id)->countAllResults()) {
                $id = 0;
            }
        }

        // 表示审核文章机制
        if ($data[1]['status'] >= 0 && $data[1]['status'] < 9) {
            // 存储到审核表中去
            if (\Phpcmf\Service::L('input')->post('sync_cat')) {
                // 同步栏目设置进审核表
                $data[0]['sync_cat'] = \Phpcmf\Service::L('input')->post('sync_cat');
            }
            $verify = $this->table($this->mytable.'_verify')->get($data[1]['id']);
            if ($verify) {
				// 修改审核
                $update = [
                    'catid' => $data[1]['catid'],
                    'status' => (int)$data[1]['status'],
                    'content' => dr_array2string(@array_merge($data[0], $data[1])),
                    'backuid' => IS_ADMIN && !\Phpcmf\Service::M('auth')->is_post_user() ? $this->uid : 0,
                    'backinfo' => IS_ADMIN && !\Phpcmf\Service::M('auth')->is_post_user() ? dr_array2string([
                        'uid' => $this->uid,
                        'author' => \Phpcmf\Service::C()->member['username'],
                        'optiontime' => SYS_TIME,
                        'backcontent' => $_POST['verify']['msg']
                    ]) : '',
                ];
                $rt = $this->table($this->mytable.'_verify')->update($data[1]['id'], $update);
                if (!$rt['code']) {
                    return $rt;
                }
                // 清除以前的提醒
                $this->db->table('admin_notice')->where('uri', $this->dirname.'/verify/edit:id/'.$data[1]['id'])->update([
                    'status' => 3,
                    'updatetime' => SYS_TIME,
                ]);

                // 挂钩点 模块内容审核处理之后
                $verify['old'] = $old;
                $verify = array_merge($verify, $update);
                \Phpcmf\Hooks::trigger('module_verify_after', $verify);
                // 执行审核后的回调
                $this->_call_verify($data[1], $verify);
                // 通知管理员
                $data[1]['status'] > 0 && \Phpcmf\Service::M('member')->admin_notice(
                    $this->siteid,
                    'content',
                    IS_ADMIN ? dr_member_info($data[1]['uid']) : \Phpcmf\Service::C()->member,
                    dr_lang('%s【%s】审核', MODULE_NAME, $data[1]['title']), $this->dirname.'/verify/edit:id/'.$data[1]['id'],
                    $this->_get_verify_roleid($data[1]['catid'], $data[1]['status'],  IS_ADMIN ? dr_member_info($data[1]['uid']) : \Phpcmf\Service::C()->member)
                );
                // 通知用户
                IS_ADMIN && !\Phpcmf\Service::M('auth')->is_post_user() && $data[1]['status'] == 0 && \Phpcmf\Service::M('member')->notice(
                    $data[1]['uid'],
                    3,
                    dr_lang('《%s》审核被拒绝', $data[1]['title']),
                    \Phpcmf\Service::L('router')->member_url($this->dirname.'/verify/index')
                );
            } else {
				// 新增审核
                $verify = [
                    'id' => (int)$data[1]['id'],
                    'uid' => (int)$data[1]['uid'],
                    'isnew' => $id ? 0 : 1,
                    'catid' => (int)$data[1]['catid'],
                    'author' => $data[1]['author'],
                    'status' => (int)$data[1]['status'],
                    'content' => dr_array2string(array_merge($data[0], $data[1])),
                    'backuid' => IS_ADMIN ? $this->uid : 0,
                    'backinfo' => IS_ADMIN ? dr_array2string([
                        'uid' => $this->uid,
                        'author' => \Phpcmf\Service::C()->member['username'],
                        'optiontime' => SYS_TIME,
                        'backcontent' => $_POST['verify']['msg']
                    ]) : '',
                    'inputtime' => SYS_TIME
                ];
                $rt = $this->table($this->mytable.'_verify')->replace($verify);
                if (!$rt['code']) {
                    // 删除索引
                    $this->table($this->mytable.'_index')->delete($data[1]['id']);
                    return $rt;
                }

				if (IS_ADMIN && defined('IS_MODULE_TG') && $data[1]['status'] == 0) {
					// 后台退稿
					if (\Phpcmf\Service::L('input')->get('clear')) {
						$this->table($this->mytable)->delete($data[1]['id']);
						// 删除文件
						$this->_delete_show_file($old);
					}
					// 通知用户
					\Phpcmf\Service::M('member')->notice(
						$data[1]['uid'],
						3,
						dr_lang('《%s》审核被拒绝', $data[1]['title']),
						\Phpcmf\Service::L('router')->member_url($this->dirname.'/verify/index')
					);
				} else {
					// 通知管理员
					\Phpcmf\Service::M('member')->admin_notice(
						$this->siteid,
						'content',
						\Phpcmf\Service::C()->member,
						dr_lang('%s【%s】审核', MODULE_NAME, $data[1]['title']), $this->dirname.'/verify/edit:id/'.$data[1]['id'],
                        $this->_get_verify_roleid($data[1]['catid'], $data[1]['status'], \Phpcmf\Service::C()->member)
					);
				}

                // 挂钩点 模块内容审核处理之后
                $verify['old'] = $old;
                \Phpcmf\Hooks::trigger('module_verify_after', $verify);
                // 执行审核后的回调
                $this->_call_verify($data[1], $verify);
            }
            return dr_return_data(1, 'ok', $verify);
        }

        $cdata = [];

        // 筛选出来栏目模型字段
        if (!$this->is_hcategory && $catfield = \Phpcmf\Service::C()->module['category'][$data[1]['catid']]['field']) {
            foreach ($data as $main => $value) {
                foreach ($value as $i => $t) {
                    if (strpos($i, '_lng') || strpos($i, '_lat')) {
                        $i = str_replace(array('_lng', '_lat'), '', $i);
                        if (isset($catfield[$i]) && $catfield[$i]['ismain'] == $main && !isset($cdata[$main][$i.'_lng'])) {
                            $cdata[$main][$i.'_lng'] = $data[$main][$i.'_lng'];
                            $cdata[$main][$i.'_lat'] = $data[$main][$i.'_lat'];
                            unset($data[$main][$i.'_lng'], $data[$main][$i.'_lat']);
                        }
                    } else {
                        if (isset($catfield[$i]) && $catfield[$i]['ismain'] == $main) {
                            $cdata[$main][$i] = $t;
                            unset($data[$main][$i]);
                        }
                    }
                }
            }
        }

        // 主表数据
        $main = isset($data[1]) ? $data[1] : $data;
        if ($id) {
            // 更新数据
            //$main['hits'] = 0;
            $rt = $this->table($this->mytable)->update($id, $main);
            if (!$rt['code']) {
                return $rt;
            }
            $tid = intval($old['tableid']);
        } else {
            // 新增数据
            $main['hits'] = (int)$main['hits'];
            $main['tableid'] =  $main['comments'] = $main['avgsort'] = 0;
            $main['displayorder'] = (int)$main['displayorder'];
            $rt = $this->table($this->mytable)->replace($main);
            if (!$rt['code']) {
                // 删除索引
                $this->table($this->mytable.'_index')->delete($data[1]['id']);
                return $rt;
            }
            // 副表以5w数据量无限分表
            $tid = $this->_table_id($data[1]['id']);
            $this->table($this->mytable)->update($data[1]['id'], ['tableid' => $tid]);
        }

        // 判断附表是否存在,不存在则创建
        $this->is_data_table($this->mytable.'_data_', $tid);
        $table = $this->mytable.'_data_'.$tid;
        if ($id) {
            if ($data[0]) {
                $rt = $this->table($table)->update($id, $data[0]);
                if ($rt['msg']) {
                    // 删除主表
                    $this->table($this->mytable)->delete($id);
                    // 删除索引
                    $this->table($this->mytable.'_index')->delete($id);
                    return $rt;
                }
            } else {
                // 有种情况就是附表没有数据;
            }
        } else {
            $data[0]['id'] = $data[1]['id']; // 录入主表id
            $rt = $this->table($table)->replace($data[0]);
            if ($rt['msg']) {
                // 删除主表
                $this->table($this->mytable)->delete($data[1]['id']);
                // 删除索引
                $this->table($this->mytable.'_index')->delete($data[1]['id']);
                return $rt;
            }
        }

        // 存储栏目表
        if ($cdata) {
            $cdata[0]['id'] = $cdata[1]['id'] = $cid = $data[1]['id'];
            $cdata[0]['uid'] = $cdata[1]['uid'] = $data[1]['uid'];
            $cdata[0]['catid'] = $cdata[1]['catid'] = $data[1]['catid'];
            // 查询栏目数据表是否存在该数据
            $table = $this->mytable.'_category_data';
            $is_update = $this->db->table($table)->where('id', $cid)->countAllResults();
            // 判断附表是否存在,不存在则创建
            $this->is_data_table($table.'_', $tid);
            if ($is_update) {
                // 更新
                $this->table($table)->update($cid, $cdata[1]);
                $this->table($table.'_'.$tid)->update($cid, $cdata[0]);
            } else {
                // 新增
                $this->table($table)->replace($cdata[1]);
                $this->db->table($table.'_'.$tid)->replace($cdata[0]);
            }
        }

        // 更新url
        $data[1]['url'] = $this->update_url($data[1], \Phpcmf\Service::L('router')->show_url(\Phpcmf\Service::C()->module, $data[1]));

        // 修改内容是更新同步栏目相关数据
        $id && $old['link_id'] && $this->sync_update_cat($old['link_id'], $data);

        // 站长工具
        if (dr_is_app('bdts')) {
            \Phpcmf\Service::M('bdts', 'bdts')->module_bdts(MOD_DIR, dr_url_prefix($data[1]['url'], MOD_DIR, SITE_ID, 0), $id ? 'edit' : 'add');
        }

        if (dr_is_app('bdxz')) {
            \Phpcmf\Service::M('bdxz', 'bdxz')->module_bdxz(MOD_DIR, dr_url_prefix($data[1]['url'], MOD_DIR, SITE_ID, 0), $id ? 'edit' : 'add');
        }

        // 自动关键词存储
        if (\Phpcmf\Service::C()->module['setting']['auto_save_tag']) {
            $this->auto_save_tag($data[1]['keywords']);
        }

        // 如果来自审核页面,表示完成审核
        if (defined('IS_MODULE_VERIFY')) {

            // 通知用户
            \Phpcmf\Service::L('Notice')->send_notice('module_content_verify_1', $data[1]);

            $verify = $this->table($this->mytable.'_verify')->get($data[1]['id']);
            $verify['status'] = 9;
            $verify['content'] = dr_array2string(array_merge($data[1], $data[0]));
            $verify['backuid'] = IS_ADMIN ? $this->uid : 0;
            $verify['backinfo'] = dr_array2string($old['verify']['backinfo']);

            // 删除审核表
            $this->table($this->mytable.'_verify')->delete($data[1]['id']);

            // 挂钩点 模块内容审核处理之后
            \Phpcmf\Hooks::trigger('module_verify_after', $verify);

            // 执行审核后的回调
            $this->_call_verify($data[1], $verify);
        }

        // 表示新发布
        if (!$id) {
            // 增减金币
            $score = \Phpcmf\Service::C()->_module_member_value($data[1]['catid'], $this->dirname, 'score', \Phpcmf\Service::M('member')->authid($data[1]['uid']));
            $score && \Phpcmf\Service::M('member')->add_score($data[1]['uid'], $score, dr_lang('%s内容发布', MODULE_NAME), $data[1]['url']);
            // 增减经验
            $exp = \Phpcmf\Service::C()->_module_member_value($data[1]['catid'], $this->dirname, 'exp', \Phpcmf\Service::M('member')->authid($data[1]['uid']));
            $exp && \Phpcmf\Service::M('member')->add_experience($data[1]['uid'], $exp, dr_lang('%s内容发布', MODULE_NAME), $data[1]['url']);
        } else {
            // 修改内容
            if ($data[1]['catid'] != $old['catid']) {
                // 变更栏目的一些联动操作
                $this->_edit_category_id($data[1], $data[1]['catid']);
            }
            if ($data[1]['uid'] != $old['uid']) {
                // 变更作者的一些联动操作
                $this->_edit_author_id($data[1]);
            }
        }

        // 挂钩点 模块内容发布或修改完成之后
        \Phpcmf\Hooks::trigger('module_content_after', $data);

        // 二次开发函数
        $this->_content_post_after($id, $data, $old);

        \Phpcmf\Service::L('cache')->clear('module_'.MOD_DIR.'_show_id_'.$id);

        return dr_return_data($data[1]['id'], 'ok', $data);
    }

    // 定时发布
    public function post_time($row) {

        if (!$row) {
            return dr_return_data(0, dr_lang('内容不存在'));
        }

        $data = dr_string2array($row['content']);
        if (!$data) {
            return dr_return_data(0, dr_lang('内容不存在'));
        }

        $flag = $data['flag'];
        //$sync_weibo = $data['sync_weibo'];
        unset($data['sync_weibo'], $data['flag']);

        // 主表字段
        $fields[1] = \Phpcmf\Service::C()->get_cache('table-'.$this->siteid, $this->dbprefix($this->siteid.'_'.$this->dirname));
        $cache = \Phpcmf\Service::C()->get_cache('table-'.$this->siteid, $this->dbprefix($this->siteid.'_'.$this->dirname.'_category_data'));
        $cache && $fields[1] = array_merge($fields[1], $cache);
        // 附表字段
        $fields[0] = \Phpcmf\Service::C()->get_cache('table-'.$this->siteid, $this->dbprefix($this->siteid.'_'.$this->dirname.'_data_0'));
        $cache = \Phpcmf\Service::C()->get_cache('table-'.$this->siteid, $this->dbprefix($this->siteid.'_'.$this->dirname.'_category_data_0'));
        $cache && $fields[0] = array_merge($fields[0], $cache);

        // 去重复
        $fields[0] = array_unique($fields[0]);
        $fields[1] = array_unique($fields[1]);

        $save = [];

        // 主表附表归类
        foreach ($fields as $ismain => $field) {
            foreach ($field as $name) {
                isset($data[$name]) && $save[$ismain][$name] = $data[$name];
            }
        }

        // 系统字段
        foreach (['inputip', 'hits', 'displayorder', 'author', 'uid', 'catid'] as $name) {
            $save[1][$name] = $data[$name];
        }

        $save[0]['uid'] = $data['uid'];
        $save[0]['catid'] = $data['catid'];

        $save[1]['url'] = '';
        $save[1]['status'] = 9;
        $save[1]['link_id'] = 0;
        $save[1]['comments'] = 0;
        $save[1]['avgsort'] = 0;

        $time = min(SYS_TIME, $row['posttime']);
        !$time && $time = SYS_TIME;
        $save[1]['updatetime'] = $save[1]['inputtime'] = $time;

        $nid = $this->index(0, $save);
        if (!$nid) {
            return dr_return_data(0, dr_lang('内容索引id生成失败'));
        }
        $data[0]['id'] = $data[1]['id'] = $nid;

        $rt = $this->save(0, $save);
        if ($rt['code']) {
            // 发布成功
            $save[1]['id'] = $rt['code'];
            $this->db->table($this->mytable.'_time')->where('id', $row['id'])->delete();
            $prefix = $this->dbprefix($this->siteid.'_'.$this->dirname);
            $this->db->table('attachment')->where('related', $prefix.'_time-'.$row['id'])->update([
                'related' => $prefix.'-'.$rt['code']
            ]);
            $this->db->table('attachment_data')->where('related', $prefix.'_time-'.$row['id'])->update([
                'related' => $prefix.'-'.$rt['code']
            ]);
            // 生成权限文件
            if (\Phpcmf\Service::C()->module['category'][$data['catid']]['setting']['html']) {
                dr_html_auth(1);
                $rt['data'] = '/index.php?'.($this->is_share ? '' : 's='.$this->dirname.'&').'c=html&m=showfile&id='.$rt['code'];

            }
            // 推荐位
            if ($flag) {
                foreach ($flag as $i) {
                    $this->content_model->insert_flag((int)$i, $row['id'], $data['uid'], $data['catid']);
                }
            }
            // 同步到微博
            //$sync_weibo && $this->sync_weibo($save);
        } else {
            $this->db->table($this->mytable.'_time')->where('id', $row['id'])->update(['result' => $rt['msg']]);
        }

        return $rt;
    }

    // 存储到tag
    public function auto_save_tag($tag) {


        if (!$tag) {
            return;
        }

        if (!dr_is_app('tag')) {
            return;
        }

        $arr = explode(',', $tag);
        foreach ($arr as $t) {
            if ($t) {
                if ($this->table($this->siteid .'_tag')->is_exists(0, 'name', $t)) {
                    // 已经存在
                    continue;
                }
                $cname = \Phpcmf\Service::L('pinyin')->result($t); // 拼音转换类
                $count = $this->db->table($this->siteid .'_tag')->where('code', $cname)->countAllResults();
                $code = $count ? $cname.$count : $cname;
                $pcode = $this->_get_tag_pcode(['pid' => 0, 'code' => $code]);
                $this->table($this->siteid .'_tag')->insert(array(
                    'pid' => 0,
                    'name' => $t,
                    'code' => $code,
                    'pcode' => $pcode,
                    'hits' => 0,
                    'displayorder' => 0,
                    'childids' => '',
                    'content' => '',
                ));
            }
        }

    }

    // 获取pcode
    private function _get_tag_pcode($data) {

        if (!$data['pid']) {
            return $data['code'];
        }

        $row = $this->table($this->siteid .'_tag')->get($data['pid']);

        return trim($row['code'].'/'.$data['code'], '/');
    }



    // 验证栏目操作权限 后台
    public function admin_category_auth($cat, $act) {
        return 1;
    }

    // 验证栏目操作权限 会员
    public function member_category_auth($cat, $act) {
        return 1;
    }

    // 删除推荐位
    public function delete_flag($id, $flag) {
        $this->db->table($this->mytable.'_flag')->where('id', $id)->whereIn('flag', $flag)->delete();
    }

    // 查询id
    public function find_id($field, $value) {

        $row = $this->db->table($this->mytable)->select('id')->where($field, $value)->get()->getRowArray();

        return $row ? intval($row['id']) : 0;
    }

    // 新增推荐位
    public function insert_flag($flag, $id, $uid, $catid) {
        $this->db->table($this->mytable.'_flag')->insert(array(
            'id' => $id,
            'uid' => $uid,
            'flag' => $flag,
            'catid' => $catid,
        ));
    }

    // 获取推荐位
    public function get_flag($id) {

        $rt = [];
        $data = $this->table($this->mytable.'_flag')->where('id', $id)->getAll();
        if ($data) {
            foreach ($data as $t) {
                $rt[] = $t['flag'];
            }
        }

        return $rt;
    }

    // 获取关键词列表
    public function get_tag_url($name) {
        return \Phpcmf\Service::L('router')->get_tag_url($name, $this->dirname);
    }

    /**
     * 保存内容的草稿
     *
     * @param	intval	$id 	草稿id
     * @param	array	$data	数据数组
     * @return  intval  $id     草稿id
     */
    public function insert_draft($id, $data) {

        $save = [
            'uid' => $this->uid,
            'cid' => intval($data[1]['id']),
            'catid' => intval($data[1]['catid']),
            'content' => dr_array2string($data[0] ? array_merge($data[0], $data[1]) : $data[1]),
            'inputtime' => SYS_TIME
        ];

        // 判断草稿是否存在，不存在就插入
        if ($id && $this->db->table($this->mytable.'_draft')->where('id', $id)->countAllResults()) {
            $rt = $this->table($this->mytable.'_draft')->update($id, $save);
        } else {
            $r = $this->db
                ->table($this->mytable.'_draft')
                ->where('uid', $save['uid'])
                ->where('cid', $save['cid'])
                ->get()->getRowArray();
            if ($r) {
                $rt = $this->table($this->mytable.'_draft')->update($r['id'], $save);
            } else {
                $rt = $this->table($this->mytable.'_draft')->insert($save);
            }
        }

        return $rt;
    }

    /**
     * 保存定时发布内容
     *
     * @param	intval	$id 	id
     * @param	array	$data	数据数组
     * @param	intval	$time 	发布时间
     */
    public function save_post_time($id, $data, $time) {

        $post = $data[0] ? array_merge($data[0], $data[1]) : $data[1];
        $post['flag'] = \Phpcmf\Service::L('input')->post('flag');
        $post['sync_weibo'] = \Phpcmf\Service::L('input')->post('sync_weibo');

        $save = [
            'uid' => $this->uid,
            'catid' => intval($data[1]['catid']),
            'content' => dr_array2string($post),
            'result' => '',
            'posttime' => $time,
            'inputtime' => SYS_TIME
        ];

        // 判断是否存在，不存在就插入
        if ($id) {
            $rt = $this->table($this->mytable.'_time')->update($id, $save);
        } else {
            $rt = $this->table($this->mytable.'_time')->insert($save);
        }

        return dr_return_data($rt['code'], $rt['code'] ? dr_lang('定时内容保存成功') : $rt['msg']);
    }

    // 更新url
    public function update_url($row, $url) {
        $this->table($this->mytable)->update((int)$row['id'], ['url' => $url]);
        return $url;
    }

    // 获取草稿列表
    public function get_draft_list($where) {

        $rt = [];
        $data = $this->table($this->mytable.'_draft')->where('uid', $this->uid)->where($where)->order_by('inputtime desc')->getAll();
        if ($data) {
            foreach ($data as $t) {
                $rt[$t['id']] = dr_string2array($t['content']);
                $rt[$t['id']]['id'] = $t['id'];
                $rt[$t['id']]['inputtime'] = $t['inputtime'];
            }
        }

        return $rt;
    }

    // 获取草稿内容
    public function get_draft($id) {

        $data = $this->db
            ->table($this->mytable.'_draft')
            ->where('uid', $this->uid)
            ->where('id', $id)
            ->get()->getRowArray();
        if (!$data) {
            return NULL;
        }

        $body = dr_string2array($data['content']);
        $body['draft']['cid'] = $data['cid'];
        $body['draft']['catid'] = $body['catid'] = $data['catid'];

        return $body;
    }

    // 删除草稿
    public function delete_draft($id) {
        $this->table($this->mytable.'_draft')->delete($id, 'uid='.$this->uid);
        // 删附件
        SYS_ATTACHMENT_DB && \Phpcmf\Service::M('Attachment')->id_delete(
            $this->uid,
            [$id],
            $this->dbprefix($this->mytable.'_draft-'.$id)
        );
    }

    /**
     * 生成/更新索引数据
     *
     * @param	array	$data
     * @return	array
     */
    public function index($id, $data) {

        $in = [
            'uid' => (int)$data[1]['uid'],
            'catid' => (int)$data[1]['catid'],
            'status' => (int)$data[1]['status'],
            'inputtime' => (int)$data[1]['inputtime'],
        ];

        if ($this->is_share) {
            // 共享模块
            if ($id) {
                // 修改
                //$this->table($this->mysharetable.'_index')->replace(['id' => $id, 'mid' => $this->dirname ]);
                $in['id'] = $id;
            } else {
                // 新增
                $rt = $this->table($this->mysharetable.'_index')->replace(['mid' => $this->dirname]);
                $id = $in['id'] = (int)$rt['code'];
            }
            // 更新模块索引
            $this->table($this->mytable.'_index')->replace($in);
        } else {
            // 独立模块
            if ($id) {
                // 修改
                $in['id'] = $id;
                $this->table($this->mytable.'_index')->replace($in);
            } else {
                // 新增
                $rt = $this->table($this->mytable.'_index')->replace($in);
                $id = (int)$rt['code'];
            }
        }

        return $id;
    }

    // 按自定义字段获取内容
    public function find_row($field, $value) {

        if (!$field) {
            return [];
        } elseif (!strlen($value)) {
            return [];
        } elseif (!isset(\Phpcmf\Service::C()->module['field'][$field]['ismain'])) {
            return [];
        }

        return $this->db->table($this->mytable)->where($field, dr_safe_replace($value))->get()->getRowArray();
    }

    // 获取内容
    public function get_data($id, $is_table = 0, $param = []) {

        $cdata = $tables = $row = [];
		
        // 主表
        $tables[] = $table = $this->mytable;
		
        if (!$id) {
            if (!$param) {
                return [];
            } elseif (!$param['field']) {
                return [];
            }
			$row = $this->find_row($param['field'], $param['value']);
            if (!$row) {
                return [];
            }
            $id = $row['id'];
        } else {
			$row = $this->table($table)->get($id);
            if (!$row) {
                return [];
            }
		}
		
        $cdata[$table] = $row;

        // 附表id
        $tableid = intval($row['tableid']);

        // 副表
        $tables[] = $table = $this->mytable.'_data_'.$tableid;
        $cdata[$table] = $data = $this->table($table)->get($id);
        $data && $row = $row + $data;

        // 栏目模型数据
        if (!$this->is_hcategory) {
            $tables[] = $table = $this->mytable.'_category_data';
            $cdata[$table] = $data = $this->table($table)->get($id);
            // 栏目模型数据副表
            if ($data) {
                $row = $row + $data;
                $tables[] = $table = $this->mytable.'_category_data_'.$tableid;
                $cdata[$table] = $data = $this->table($table)->get($id);
                $data && $row = $row + $data;
            }
        }

        $row = $this->_format_content_data($row);

        if ($is_table) {
            return [$row, $tables, $cdata];
        }

        return $row;
    }

    // 格式化入库数据
    public function format_data($data) {

        isset($data[1]['uid']) && $data[0]['uid'] = (int)$data[1]['uid'];
        isset($data[1]['hits']) && $data[1]['hits'] = (int)$data[1]['hits'];
        !$data[1]['description'] && $data[1]['description'] = trim(dr_strcut(dr_clearhtml($data[0]['content']), 100));

        if (isset($data[1]['keywords']) && $data[1]['keywords']) {
			// 不要自动获取关键词，容易卡顿，引起发布延迟
            //!$data[1]['keywords'] && $data[1]['keywords'] = dr_get_keywords($data[1]['title'].' '.$data[1]['description'], $this->siteid);
            $data[1]['keywords'] = str_replace('"', '', $data[1]['keywords']);
        }

        return $data;
    }

    // 同步更新其他栏目
    public function sync_update_cat($lid, $data) {

        $id = $lid > 0 ? $lid : $data[1]['id'];

        unset($data[1]['id'],$data[1]['catid']);
        $this->db->table($this->mytable)->where('link_id', (int)$id)->update($data[1]);
    }

    // 同步到微博
    public function sync_weibo($data) {

        $weibo = \Phpcmf\Service::C()->get_cache('site', $this->siteid, 'weibo', 'module', $this->dirname);
        if (!$weibo || !$weibo['use']) {
            return dr_return_data(0, dr_lang('当前模块没有启用微博分享'));
        }

        // 合并data
        isset($data[1]) && $data = $data[1] + $data[0];

        if (!$data['id']) {
            return dr_return_data(0, dr_lang('内容不存在'));
        }

        $save = [
            'url' => dr_url_prefix($data['url'], MOD_DIR, SITE_ID, 0),
            'image' => [],
            'content' => '',
        ];

        // 图片
        if ($weibo['image'] && isset($data[$weibo['image']]) && $data[$weibo['image']]) {
            // 自定义图片
            // 判断是否是多图
            $arr = dr_string2array($data[$weibo['image']]);
            if (is_array($arr) && $arr) {
                if ($arr['file']) {
                    foreach ($arr['file'] as $c) {
                        $save['image'] = dr_get_file($c);
                        break;
                    }
                }
            } else {
                $save['image'] = dr_get_file($data[$weibo['image']]);
            }
        } else {
            $save['image'] = dr_get_file($data['thumb']);
        }

        // 微博内容
        $save['content'] = dr_clearhtml($weibo['content'] && isset($data[$weibo['content']]) && $data[$weibo['content']] ? $data[$weibo['content']] : $data['description']);

        // 加入队列并执行
        $rt = \Phpcmf\Service::M('cron')->add_cron($this->siteid, 'weibo', $save);
        if (!$rt['code']) {
            log_message('error', '任务注册失败：'.$rt['msg']);
            return dr_return_data(0, '任务注册失败：'.$rt['msg']);
        }

        return $rt;
    }

    // 同步到其他栏目
    public function sync_cat($catid, $data) {

        if (!$catid) {
            return;
        }

        $id = (int)$data[1]['id'];
        $sync = @explode(',', $catid);
        if ($sync && $id) {
            // 更新主表状态主表
            $this->table($this->mytable)->update($id, ['link_id' => -1]);
            // 同步记录
            foreach ($sync as $catid) {
                if ($catid && $catid != $data[1]['catid']) {
                    // 插入到同步栏目中
                    $new = $data;
                    $new[1]['catid'] = $catid;
                    $new[1]['link_id'] = $id;
                    $new[1]['tableid'] = 0;
                    $new[1]['id'] = $this->index(0, $new);
                    $new[1]['id'] && $this->table($this->mytable)->replace($new[1]);
                }
            }
        }
    }


    // 删除数据,放入回收站
    public function delete_to_recycle($ids, $note = '无') {

        // 格式化
        foreach ($ids as $id) {

            $id = intval($id);

            \Phpcmf\Service::L('cache')->clear('module_'.MOD_DIR.'_show_id_'.$id);

            // 主表
            $tables[$this->mytable] = $row = $this->table($this->mytable)->get($id);
            if (!$row) {
                return dr_return_data(0, dr_lang('内容不存在: '.$id));
            } elseif (dr_is_app('cqx') && \Phpcmf\Service::M('content', 'cqx')->is_edit($row['catid'], $row['uid'])) {
                return dr_return_data(0, dr_lang('当前角色无权限管理此栏目'));
            }

            $row['url'] = dr_url_prefix($row['url'], MOD_DIR, SITE_ID, 0);

            // 站长工具
            if (dr_is_app('bdts')) {
                \Phpcmf\Service::M('bdts', 'bdts')->module_bdts(MOD_DIR, $row['url'], 'del');
            }
            if (dr_is_app('bdxz')) {
                \Phpcmf\Service::M('bdxz', 'bdxz')->module_bdxz(MOD_DIR, $row['url'], 'del');
            }

            // 附表id
            $tableid = intval($row['tableid']);

            // 副表
            $table = $this->mytable.'_data_'.$tableid;
            $tables[$table]  = $this->table($table)->get($id);

            if (!$this->is_hcategory) {
                // 栏目模型数据
                $table = $this->mytable.'_category_data';
                $tables[$table] = $this->table($table)->get($id);

                // 栏目模型数据副表
                if ($tables[$table]) {
                    $table = $this->mytable.'_category_data_'.$tableid;
                    $tables[$table] = $this->table($table)->get($id);
                }
            }

            // 删除表数据
            foreach ($tables as $table => $t) {
                if ($t) {
                    $rt = $this->table($table)->delete($id);
                    if (!$rt['code']) {
                        return $rt;
                    }
                }
            }

            // 改改状态
            $rt = $this->table($this->mytable.'_index')->update($id, ['status' => 10]);
            if (!$rt['code']) {
                return $rt;
            }

            \Phpcmf\Service::M('member')->delete_admin_notice($this->dirname.'/verify/edit:id/'.$id, $this->siteid);
            \Phpcmf\Service::L('cache')->init()->delete('module_'.$this->dirname.'_show_id_'.$id);

            // 通知用户
            $row['note'] = dr_clearhtml($note);
            $row['title'] = dr_strcut(dr_clearhtml($row['title']), 50);
            \Phpcmf\Service::L('Notice')->send_notice('module_content_delete', $row);

            // 放入回收站
            $rt = $this->table($this->mytable.'_recycle')->insert([
                'uid' => $this->uid,
                'cid' => $id,
                'catid' => intval($row['catid']),
                'content' => dr_array2string($tables),
                'result' => $note,
                'inputtime' => SYS_TIME
            ]);
            if (!$rt['code']) {
                return $rt;
            }

            // 删除文件
            $this->_delete_show_file($row);

            // 删除执行的方法
            $this->_recycle_content($id, $row, $note);
        }

        return dr_return_data(1);
    }
	
	// 删除静态文件
	protected function _delete_show_file($row) {
		
		if (!$row['url']) {
			return;
		}
		
		$file = \Phpcmf\Service::L('Router')->remove_domain($row['url']); // 从地址中获取要生成的文件名
		$root = \Phpcmf\Service::L('html')->get_webpath(SITE_ID, $this->dirname);

		// 删除文件
		if (is_file($root.$file)) {
            @unlink($root.$file);
        }
        if (is_file($root.'mobile/'.$file)) {
            @unlink($root.'mobile/'.$file);
        }
	}

    // 删除回收站数据
    public function delete_for_recycle($ids) {

        foreach ($ids as $id) {
            $id = intval($id);
            $row = $this->table($this->mytable.'_recycle')->get($id);
            if (!$row) {
                return NULL;
            }
            $cid = intval($row['cid']);
            \Phpcmf\Service::L('cache')->init()->delete('module_'.$this->dirname.'_show_id_'.$cid);
            // 删除执行的方法
            $this->_delete_content($cid, $row);
            // 删除回收站表
            $this->table($this->mytable.'_recycle')->delete($id);
            // 才彻底删除内容数据
            $this->delete_content($cid);
        }

        return dr_return_data(1);
    }

    // 删除全部内容数据
    public function delete_content($cid) {

        $module = \Phpcmf\Service::L('cache')->get('module-'.$this->siteid.'-'.$this->dirname);

        // 删除索引表
        $this->table($this->mytable.'_index')->delete($cid);

        // 删除标记表
        $this->table($this->mytable.'_flag')->delete($cid);
        // 删除统计
        $this->table($this->mytable.'_hits')->delete($cid);
        $this->table($this->mytable.'_verify')->delete($cid);
        // 删除评论
        $this->db->table($this->mytable.'_comment')->where('cid', $cid)->delete();
        $this->db->table($this->mytable.'_comment_index')->where('cid', $cid)->delete();

        // 共享模块删除
        if ($this->is_share) {
            $this->table($this->mysharetable.'_index')->delete($cid);
        }
        // 删除草稿表
        $this->db->table($this->mytable.'_draft')->where('cid', $cid)->delete();
        // 删除相关插件表
        if ($this->is_table_exists($this->mytable.'_favorite')) {
            $this->db->table($this->mytable.'_favorite')->where('cid', $cid)->delete();
        }
        if ($this->is_table_exists($this->mytable.'_support')) {
            $this->db->table($this->mytable.'_support')->where('cid', $cid)->delete();
        }
        if ($this->is_table_exists($this->mytable.'_oppose')) {
            $this->db->table($this->mytable.'_oppose')->where('cid', $cid)->delete();
        }
        if ($this->is_table_exists($this->mytable.'_donation')) {
            $this->db->table($this->mytable.'_donation')->where('cid', $cid)->delete();
        }
        // 删除内容
        $tid = $this->_table_id($cid);
        $this->table($this->mytable.'_data_'.$tid)->delete($cid);
        if (!$this->is_hcategory) {
            $this->table($this->mytable.'_category_data')->delete($cid);
            $this->table($this->mytable.'_category_data_'.$tid)->delete($cid);
        }

        // 模块表单
        if ($module['form']) {
            foreach ($module['form'] as $t) {
                $table = $this->siteid.'_'.$this->dirname.'_form_'.$t['table'];
                if (!$this->db->query("SHOW TABLES LIKE '".$this->dbprefix($table)."'")->getRowArray()) {
                    break;
                }
                $this->db->table($table)->where('cid', $cid)->delete();
                for ($i = 0; $i < 200; $i ++) {
                    if (!$this->db->query("SHOW TABLES LIKE '".$this->dbprefix($table).'_data_'.$i."'")->getRowArray()) {
                        break;
                    }
                    $this->db->table($table.'_data_'.$i)->where('cid', $cid)->delete();
                }
            }
        }

        // 执行删除同步
        \Phpcmf\Service::M('Sync')->delete_content($cid, $this->siteid, $this->dirname);
    }

    // 恢复数据
    public function recovery($ids) {

        foreach ($ids as $id) {

            $id = intval($id);
            $row = $this->table($this->mytable.'_recycle')->get($id);
            if (!$row) {
                return NULL;
            }

            $cid = intval($row['cid']);
            $tables = dr_string2array($row['content']);
            if (!$tables) {
                return NULL;
            }
            // 内容恢复
            foreach ($tables as $table => $t) {
               if ($t) {
                   $rt = $this->table($table)->replace($t);
                   if (!$rt['code']) {
                       return $rt;
                   }
               }
            }
            $rt = $this->table($this->mytable.'_index')->update($cid, ['status' => 9]);
            if (!$rt['code']) {
                return $rt;
            }

            $this->db->table($this->mytable.'_recycle')->where('id', $id)->delete();
            $this->_recovery_content($cid, $row);
        }

        return dr_return_data(1);
    }

    // 移动栏目
    public function move_category($ids, $catid) {

        if (!$catid) {
            return dr_return_data(0, dr_lang('目标栏目不存在'));
        }

        $all = $this->table($this->mytable)->where_in('id', $ids)->getAll();
        if ($all) {
            foreach ($all as $t) {

                $id = intval($t['id']);
                $this->table($this->mytable)->update($id, ['catid' => $catid]);
                $this->table($this->mytable.'_index')->update($id, ['catid' => $catid]);
                // 判断附表是否存在,不存在则创建
                $this->is_data_table($this->mytable.'_data_', $t['tableid']);
                $this->is_data_table($this->mytable.'_category_data_', $t['tableid']);
                $this->db->table($this->mytable.'_data_'.intval($t['tableid']))->where('id', $id)->update(['catid' => $catid]);
                $this->db->table($this->mytable.'_category_data')->where('id', $id)->update(['catid' => $catid]);
                $this->db->table($this->mytable.'_category_data_'.intval($t['tableid']))->where('id', $id)->update(['catid' => $catid]);

                // 变更栏目的一些联动操作
                $this->_edit_category_id($t, $catid);
            }
        }

        return dr_return_data(1);
    }

    // 批量更新其他表的栏目id号
    public function update_catids($oid, $catid) {

        if (!$catid) {
            return;
        } elseif (!$oid) {
            return;
        }

        $all = $this->table($this->mytable)->where('catid', $oid)->getAll();
        if ($all) {
            foreach ($all as $t) {

                $id = intval($t['id']);
                $this->table($this->mytable)->update($id, ['catid' => $catid]);
                $this->table($this->mytable.'_index')->update($id, ['catid' => $catid]);
                $this->db->table($this->mytable.'_data_'.intval($t['tableid']))->where('id', $id)->update(['catid' => $catid]);
                $this->db->table($this->mytable.'_category_data')->where('id', $id)->update(['catid' => $catid]);
                $this->db->table($this->mytable.'_category_data_'.intval($t['tableid']))->where('id', $id)->update(['catid' => $catid]);

                // 变更栏目的一些联动操作
                $this->_edit_category_id($t, $catid);
            }
        }


    }

    // 变更栏目的一些联动操作
    private function _edit_category_id($t, $catid) {

        $id = intval($t['id']);
        $this->db->table($this->mytable.'_comment')->where('cid', $id)->update(['catid' => $catid]);
        $this->db->table($this->mytable.'_comment_index')->where('cid', $id)->update(['catid' => $catid]);
        $this->db->table($this->mytable.'_time')->where('id', $id)->update(['catid' => $catid]);
        $this->db->table($this->mytable.'_verify')->where('id', $id)->update(['catid' => $catid]);
        // 同步移动相关表单表
        $form = \Phpcmf\Service::L('cache')->get('module-'.$this->siteid.'-'.$this->dirname, 'form');
        if ($form) {
            foreach ($form as $r) {
                $table = $this->mytable.'_form_'.$r['table'];
                $this->db->table($table)->where('cid', $id)->update(['catid' => $catid]);
                for ($i = 0; $i < 200; $i ++) {
                    if (!$this->db->query("SHOW TABLES LIKE '".$table.'_data_'.$i."'")->getRowArray()) {
                        break;
                    }
                    $this->db->table($table.'_data_'.$i)->where('cid', $id)->update(['catid' => $catid]);
                }
            }
        }

        // 同步自定义接口
        $this->_edit_category_row($t, $catid);
    }

    // 移动栏目时的联动继承类
    protected function _edit_category_row($row, $catid) {

    }

    // 变更作者的一些联动操作
    private function _edit_author_id($t) {


        $id = intval($t['id']);
        $uid = intval($t['uid']);

        $this->db->table($this->mytable.'_time')->where('id', $id)->update(['uid' => $uid]);
        $this->db->table($this->mytable.'_verify')->where('id', $id)->update(['uid' => $uid]);

        // 同步自定义接口
        $this->_edit_author_row($t);
    }

    // 作者时的联动继承类
    protected function _edit_author_row($row) {

    }


    // 用于表单和评论的公共方法==========================================

    // 获取主数据
    public function get_row($id, $myid = 'id') {

        if (!$id) {
            return [];
        }

        $data = $this->table($this->mytable)->get($id);
        if (!$data) {
            return [];
        }

        $data['url'] = dr_url_prefix($data['url'], MOD_DIR, SITE_ID, 0);
        $data['is_comment'] = isset($data['is_comment']) ? $data['is_comment'] : 0;

        return $data;
    }

    // 获取评论索引数据
    public function get_comment_index($cid, $catid) {

        if (!$cid) {
            return [];
        }

        $rt = $this->table($this->mytable.'_comment_index')->where('cid', $cid)->getRow();
        if (!$rt) {
            // 不存在就创建它
            $rt = [
                'cid' => $cid,
                'catid' => $catid,
                'sort1' => 0,
                'sort2' => 0,
                'sort3' => 0,
                'sort4' => 0,
                'sort5' => 0,
                'sort6' => 0,
                'sort7' => 0,
                'sort8' => 0,
                'sort9' => 0,
                'oppose' => 0,
                'support' => 0,
                'avgsort' => 0,
                'comments' => 0,
            ];
            $rt['id'] = $this->table($this->mytable.'_comment_index')->insert($rt);
        }

        return $rt;
    }

    // 提交评论
    // $value 主题信息和回复人；$data评论内容和点评内容；$my自定义字段
    public function insert_comment($value, $data, $my = []) {

        $insert = [];
        $insert['cid'] = (int)$value['index']['id'];
        $insert['cuid'] = (int)$value['index']['uid'];
        $insert['catid'] = (int)$value['index']['catid'];
        $insert['uid'] = (int)$value['member']['uid'];
        $insert['reply'] = (int)$value['reply_id'];
        $insert['in_reply'] = 0;
        $insert['status'] = $value['status'];
        $insert['author'] = $value['member']['uid'] ? $value['member']['username'] : '游客';
        $insert['content'] = $data['content'];
        $insert['support'] = $insert['oppose'] = $insert['avgsort'] = 0;
        $insert['inputip'] = \Phpcmf\Service::L('input')->ip_address();
        $insert['inputtime'] = $data['inputtime'] ? $data['inputtime'] : SYS_TIME;
        $insert['orderid'] = (int)$value['orderid'];

        // 点评选项值
        $_value = [];
        $_avgsort = 0;
        for ($i = 1; $i <= 9; $i++) {
            $insert['sort'.$i] = isset($data['review'][$i]) ? (int)$data['review'][$i] : 0;
            $_avgsort+= $insert['sort'.$i];
            $insert['sort'.$i] && $_value[$i] = $insert['sort'.$i];
        }

        if ($_avgsort) {
            // 算法类别
            $dl = empty(\Phpcmf\Service::C()->module['comment']['review']['point']) || \Phpcmf\Service::C()->module['comment']['review']['point'] < 0 ? 0 : \Phpcmf\Service::C()->module['comment']['review']['point']; //小数点位数
            // 分别计算各个选项分数
            $insert['avgsort'] = round(($_avgsort / dr_count($_value)), $dl);
        }

        // 自定义字段入库
        $my && $insert = array_merge($insert, $my);

        $rt = $this->table($this->mytable.'_comment')->insert($insert);
        if (!$rt['code']) {
            return $rt;
        }

        // 评论成功返回id
        $id = $rt['code'];

        if (!$insert['status']) {
            // 需要审核时直接返回
            \Phpcmf\Service::M('member')->admin_notice($this->siteid, 'content', \Phpcmf\Service::C()->member, dr_lang('%s: 新%s审核', MODULE_NAME, dr_comment_cname(\Phpcmf\Service::C()->module['comment']['cname'])), $this->dirname.'/comment_verify/edit:cid/'.$insert['cid'].'/id/'.$id);
            return dr_return_data($id, 'verify');
        } else {
            // 直接通过的评论
            $insert['id'] = $id;
            $this->verify_comment($insert);
            return dr_return_data($id, 'ok');
        }

    }

    // 审核评论
    public function verify_comment($row) {

        if (!$row) {
            return;
        }

        $id = (int)$row['id'];

        // 当前是未审核状态时才更新状态
        if (!$row['status']) {
            // 变更审核状态
            $this->db->table($this->mytable.'_comment')->where('id', $id)->update(['status' => 1]);
        }

        // 提醒
        /* 暂时不要回复站内信通知
        $row['reply']
            ? \Phpcmf\Service::M('member')->notice($row['uid'], 2, dr_lang('评论被人回复'), '/index.php?s='.MOD_DIR.'&c=show&id='.$row['cid'])
            : \Phpcmf\Service::M('member')->notice($row['cuid'], 2, dr_lang('收到了新的评论'), '/index.php?s='.MOD_DIR.'&c=show&id='.$row['cid']);
        */

        // 统计评论总数
        $this->comment_update_total($row);

        // 回复评论时，将主题设置为存在回复状态
        $row['reply'] && $this->table($this->mytable.'_comment')->update($row['reply'], ['in_reply' => 1]);

        // 增减金币
        $score = \Phpcmf\Service::C()->_member_value(\Phpcmf\Service::M('member')->authid($row['uid']), \Phpcmf\Service::C()->member_cache['auth_module'][$this->siteid][$this->dirname]['comment']['score']);
        $score && \Phpcmf\Service::M('member')->add_score($row['uid'], $score, dr_lang('%s发布%s', MODULE_NAME, dr_comment_cname(\Phpcmf\Service::C()->module['comment']['cname'])), $row['curl']);

        // 增减经验
        $exp = \Phpcmf\Service::C()->_member_value(\Phpcmf\Service::M('member')->authid($row['uid']), \Phpcmf\Service::C()->member_cache['auth_module'][$this->siteid][$this->dirname]['comment']['exp']);
        $exp && \Phpcmf\Service::M('member')->add_experience($row['uid'], $exp, dr_lang('%s发布%s', MODULE_NAME, dr_comment_cname(\Phpcmf\Service::C()->module['comment']['cname'])), $row['curl']);

        // 更新评分
        $this->comment_update_review($row);

        // 执行通知
        \Phpcmf\Service::M('member')->todo_admin_notice($this->dirname.'/comment_verify/edit:cid/'.$row['cid'].'/id/'.$row['id'], $this->siteid);

        // 获取文章标题
        $index = $this->table($this->mytable)->get($row['cid']);
        $row['title'] = $index['title'];
        $row['index'] = $index;
        $row['comment_uid'] = $row['uid'];
        $row['comment_author'] = $row['author'];

        // 挂钩点 评论完成之后
        \Phpcmf\Hooks::trigger('comment_after', $row);
        $this->_comment_after($row);

        // 评论通过后的通知消息
        $row['url'] = $index['url'];
        \Phpcmf\Service::L('Notice')->send_notice('module_comment_verify_1', $row);

        // 评论后通知内容作者
        $row['uid'] = $index['uid'];
        $row['author'] = $index['author'];
        \Phpcmf\Service::L('Notice')->send_notice('module_comment_verify_2', $row);

        \Phpcmf\Service::L('cache')->clear('module_'.MOD_DIR.'_show_id_'.$id);
    }

    // 获取评论列表
    public function get_comment_result($cid, $order, $page, $pagesize, $total, $field) {

        if (!$cid) {
            return [];
        }

        $list = $this->db->table($this->mytable.'_comment')
            ->where('cid', $cid)->where('reply', 0)->where('status', 1)
            ->limit($pagesize, $pagesize * ($page - 1))
            ->orderBy($order)
            ->get()->getResultArray();

        if ($list) {
            $dfield = \Phpcmf\Service::L('Field')->app($this->dirname);
            foreach ($list as $i => $t) {
                $reply = !$t['in_reply'] ? [] : $this->db->table($this->mytable.'_comment')
                    ->where('cid', $cid)->where('reply', $t['id'])->where('status', 1)
                    ->orderBy('id asc')
                    ->get()->getResultArray();
                if ($field) {
                    // 格式化显示自定义字段内容
                    $list[$i] = $dfield->format_value($field, $t, 1);
                    if ($reply) {
                        foreach ($reply as $b => $v) {
                            $reply[$b] = $dfield->format_value($field, $v, 1);
                        }
                    }
                }
                $list[$i]['rlist'] = $reply;
            }
        }


        !$total && $total = $this->db->table($this->mytable.'_comment')->where('cid', $cid)->where('reply', 0)->where('status', 1)->countAllResults();

        return [$list, $total];
    }

    // 删除评论
    public function delete_comment($id, $cid = 0) {

        if (!$id) {
            return 0;
        }

        $data = $this->table($this->mytable.'_comment')->get($id);
        if (!$data) {
            return -1;
        }

        // 删除评论数据
        $this->db->table($this->mytable.'_comment')->where('id', $id)->delete();
        $this->db->table($this->mytable.'_comment')->where('reply', $id)->delete();


        // 统计评论总数
        $this->comment_update_total($data);

        // 删除附件
        SYS_ATTACHMENT_DB  && \Phpcmf\Service::M('Attachment')->id_delete(
            $data['uid'],
            [$id],
            $this->dbprefix($this->mytable.'_comment-'.($data['reply'] ? $data['reply'] : $id))
        );

        // 更新评分
        $this->comment_update_review($data);

        return 1;
    }

    // 更新统计
    public function comment_update_total($row) {

        if (!\Phpcmf\Service::C()->module['comment'] || !$row) {
            return;
        }

        $cid = (int)$row['cid'];
        // 统计评论总数
        if (!\Phpcmf\Service::C()->module['comment']['ct_reply']) {
            $total = $this->db->table($this->mytable.'_comment')->where('cid', $cid)->where('reply', 0)->where('status', 1)->countAllResults();
        } else {
            $total = $this->db->table($this->mytable.'_comment')->where('cid', $cid)->where('status', 1)->countAllResults();
        }

        $this->table($this->mytable)->update($cid, ['comments' => $total]);
        $this->table($this->mytable.'_comment_index')->update($cid, ['comments' => $total]);

    }

    // 更新评分
    public function comment_update_review($row) {

        // 只在开启时更新
        if (!\Phpcmf\Service::C()->module['comment']['review']) {
            return;
        }

        $id = (int)$row['id'];
        $cid = (int)$row['cid'];

        // 更新点评数据
        $review = $set = [];
        $_avgsort = 0;
        for ($i = 1; $i <= 9; $i++) {
            if ($row['sort'.$i]) {
                $review[$i] = $row['sort'.$i];
                $set['sort'.$i] = 0;
                $_avgsort += $review[$i];
            }
        }

        // 分值不存在时不更新
        if (!$review) {
            return ;
        }

        // 统计总的点评数
        $builder = $this->db->table($this->mytable.'_comment');
        $builder->where('cid', $cid);
        $builder->selectSum('status', 'num');
        foreach($review as $i => $val) {
            $builder->selectSum('sort'.$i);
        }

        // 不统计回复
        if (!\Phpcmf\Service::C()->module['comment']['ct_reply']) {
            $builder->where('reply', 0);
        }

        $grade = $builder->where('status', 1)->get()->getRowArray();

        if (!$grade) {
            return;
        }

        // 算法类别
        $st = round((int)\Phpcmf\Service::C()->module['comment']['review']['score'] / 5); //显示分数制 5分，10分，百分
        $dl = empty(\Phpcmf\Service::C()->module['comment']['review']['point']) || \Phpcmf\Service::C()->module['comment']['review']['point'] < 0 ? 0 : \Phpcmf\Service::C()->module['comment']['review']['point']; //小数点位数

        // 分别计算各个选项分数
        foreach($review as $i => $aaaaa) {
            $flag = 'sort'.$i;
            $set[$flag] = $grade[$flag] ? round( ($grade[$flag] / $grade['num'] * $st), $dl) : 0;
            $set['avgsort']+= $set[$flag];
        }

        // 总表的平均分
        $set['avgsort'] = round(($set['avgsort'] / dr_count($review)), $dl);

        // 本记录的
        $avgsort = round(($_avgsort / dr_count($review)), $dl);

        // 更新到索引表
        $this->db->table($this->mytable.'_comment')->where('id', $id)->update(['avgsort' => $avgsort]);

        // 更新到关联主表
        $this->table($this->mytable)->update($cid, [
            'avgsort' => $set['avgsort'],
        ]);

        // 更新到索引表
        $this->db->table($this->mytable.'_comment_index')->where('cid', $cid)->update($set);

    }

    // 获取表单数据
    public function get_form_row($id, $form) {

        if (!$id || !$form) {
            return [];
        }

        $rt = $this->table($this->mytable.'_form_'.$form)->get($id);
        if (!$rt) {
            return [];
        }

        $rt2 = $this->table($this->mytable.'_form_'.$form.'_data_'.intval($rt['tableid']))->get($id);
        if (!$rt2) {
            return $rt;
        }

        return $rt + $rt2;
    }

    // 更新统计字段
    public function update_form_total($cid, $form) {

        $total = $this->table($this->mytable.'_form_'.$form)->where('status', 1)->where('cid', $cid)->counts();
        $this->table($this->mytable)->update($cid, [
            $form.'_total' => $total,
        ]);
    }

    // 用户中心计算审核id
    public function get_verify_status($id, $authid, $auth) {

        $verify = \Phpcmf\Service::C()->get_cache('verify');
        if (!$verify) {
            return 9;
        }

        $v = [];
        foreach ($authid as $aid) {
            if (isset($auth[$aid]) && $auth[$aid] && isset($verify[$auth[$aid]])) {
                $v = $verify[$auth[$aid]];
                break; // 找到最近的审核机制就ok了
            }
        }

        if (!$v) {
            return 9;
        } elseif ($id && !$v['value']['edit']) {
            return 9; // 修改不审核
        }

        return 1; // 1 表示进入审核流程
    }

    // 更新时间
    public function update_time($ids) {
        $this->db->table($this->mytable)->whereIn('id', $ids)->update([
            'updatetime' => SYS_TIME,
        ]);
    }

    // 用户中心菜单
    public function module_menu() {

        return [
            'list' => [
                'name' => dr_lang('内容管理'),
                'icon' => dr_icon(\Phpcmf\Service::C()->module['icon']),
                'url' => \Phpcmf\Service::L('router')->member_url($this->dirname.'/home/index'),
            ],
            'verify' => [
                'name' => dr_lang('审核管理'),
                'icon' => 'fa fa-edit',
                'url' => \Phpcmf\Service::L('router')->member_url($this->dirname.'/verify/index'),
            ],
            'draft' => [
                'name' => dr_lang('草稿箱'),
                'icon' => 'fa fa-pencil',
                'url' => \Phpcmf\Service::L('router')->member_url($this->dirname.'/draft/index'),
            ],
            'add' => [
                'name' => dr_lang('发布内容'),
                'icon' => 'fa fa-plus',
                'url' => \Phpcmf\Service::L('router')->member_url($this->dirname.'/home/add'),
            ],
        ];

    }

    // 按5w分表
    public function _table_id($id) {
        return floor($id / 50000);
    }

    // 审核时候的权限组,返回可用权限组的id
    // array(
    //     *      to_uid 指定人
    //     *      to_rid 指定角色组
    //     * )
    protected function _get_verify_roleid($catid, $status, $member) {

        $verify = \Phpcmf\Service::C()->get_cache('verify');
        if (!$verify) {
            return ['to_uid' => 0, 'to_rid' => 0];
        }

        if (isset(\Phpcmf\Service::C()->member_cache['auth_module'][$this->siteid][$this->dirname]['category'][$catid]['verify'])
            && \Phpcmf\Service::C()->member_cache['auth_module'][$this->siteid][$this->dirname]['category'][$catid]['verify']) {

            $v = [];
            $auth = \Phpcmf\Service::C()->member_cache['auth_module'][$this->siteid][$this->dirname]['category'][$catid]['verify'];
            foreach ($member['authid'] as $aid) {
                if (isset($auth[$aid]) && $auth[$aid] && isset($verify[$auth[$aid]])) {
                    $v = $verify[$auth[$aid]];
                    break; // 找到最近的审核机制就ok了
                }
            }

            if ($v && isset($v['value']['role'][$status]) && $v['value']['role'][$status]) {
               return ['to_uid' => 0, 'to_rid' => $v['value']['role'][$status]];
            }

        }


        return ['to_uid' => 0, 'to_rid' => 0];
    }


    ////////////////////后台权限开放////////////////////

    // 后台内容列表条件
    public function get_admin_list_where() {

        if (\Phpcmf\Service::M('auth')->is_post_user()) {
            // 作为投稿者只能允许看自己的记录
            return 'uid = '.$this->uid;
        }

        return dr_is_app('cqx') ? \Phpcmf\Service::M('content', 'cqx')->get_list_where() : '';
    }

    // 后台内容编辑权限
    public function admin_is_edit($data) {

        if (\Phpcmf\Service::M('auth')->is_post_user()) {
            if ($data['uid'] == $this->uid) {
                return false;
            }
            return !$data['uid'] ? false : true;
        }

        return dr_is_app('cqx') ? \Phpcmf\Service::M('content', 'cqx')->is_edit((int)$data['catid'], (int)$data['uid']) : true;
    }

    ////////////////////二次开发调用////////////////////

    // 内容发布之前
    public function _content_post_before($id, $data, $old) {
        return $data;
    }

    // 内容发布之后
    public function _content_post_after($id, $data, $old) { }

    // 内容删除之后
    public function _delete_content($id, $row) { }

    // 内容回收站之后
    public function _recycle_content($id, $row, $note) { }

    // 内容恢复之后
    public function _recovery_content($id, $row) { }

    // 打赏成功之后
    public function _content_donation_after($id, $pay) { }

    // 内复制成功之后
    public function _content_copy_after($id, $save) { }

    // 内容审核操作之后
    public function _call_verify($data, $verify) { }

    // 评论成功操作之后
    public function _comment_after($data) { }

    // 格式化处理内容
    public function _format_content_data($data) {
        return $data;
    }

    // 格式化显示内容[功能与_format_content_data相同,不推荐]
    public function _call_show($data) {
        return $data;
    }

    // 格式化栏目seo信息
    public function _format_category_seo($module, $data, $page) {
        return \Phpcmf\Service::L('Seo')->category($module, $data, $page);
    }

    // 格式化首页seo信息
    public function _format_home_seo($module) {
        return \Phpcmf\Service::L('Seo')->module($module);
    }

    // 格式化内容页seo信息
    public function _format_show_seo($module, $data, $page) {
        return \Phpcmf\Service::L('Seo')->show($module, $data, $page);
    }

    // 格式化内容搜索seo信息
    public function _format_search_seo($module, $catid, $params, $page) {
        return \Phpcmf\Service::L('Seo')->search($module, $catid, $params, $page);
    }


    ////////////////////禁用栏目时，二次开发调用////////////////////

    // 禁用栏目时，用户保存内容之前的权限验证
    public function _hcategory_member_save_before($data) {
        return $data;
    }

    // 禁用栏目时，用户保存内容时的内容文章状态
    public function _hcategory_member_post_status($member_authid) {
        return 9;
    }

    // 禁用栏目时，用户保存内容时是否启用验证码
    public function _hcategory_member_post_code() {
        return 0;
    }

    // 禁用栏目时，用户发布内容时的权限验证
    public function _hcategory_member_add_auth() {

    }

    // 禁用栏目时，用户修改内容时的权限验证
    public function _hcategory_member_edit_auth() {

    }

    // 禁用栏目时，用户删除内容时的权限验证
    public function _hcategory_member_del_auth() {

    }

    // 禁用栏目时，用户阅读内容时的权限验证
    public function _hcategory_member_show_auth() {

    }
}