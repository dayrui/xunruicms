<?php namespace Phpcmf\Model;
/**
 * {{www.xunruicms.com}}
 * {{迅睿内容管理框架系统}}
 * 本文件是框架系统文件，二次开发时不可以修改本文件，可以通过继承类方法来重写此文件
 **/

class Member extends \Phpcmf\Model {

    protected $sso_url;

    /**
     * 由用户名获取uid
     */
    public function uid($name) {

        if (!$name || dr_lang('游客') == $name) {
            return 0;
        } elseif ($name == $this->member['username']) {
            return $this->member['uid'];
        }

        $data = $this->db->table('member')->select('id')->where('username', dr_safe_replace($name))->get()->getRowArray();

        return intval($data['id']);
    }

    /**
     * 由uid获取用户名
     */
    public function username($uid) {

        $uid = intval($uid);

        if (!$uid) {
            return '';
        } elseif ($uid == $this->member['uid']) {
            return $this->member['username'];
        }

        $data = $this->db->table('member')->select('username')->where('id', $uid)->get()->getRowArray();

        return $data['username'];
    }

    /**
     * 后台账号字段获取用户名
     */
    public function author($uid) {

        if (!$uid) {
            return dr_lang('游客');
        }

        return $this->username($uid);
    }

    /**
     * 由uid获取电话
     */
    public function phone($uid) {

        $uid = intval($uid);
        if (!$uid) {
            return '';
        } elseif ($uid == $this->member['uid']) {
            return $this->member['phone'];
        }

        $data = $this->db->table('member')->select('phone')->where('id', $uid)->get()->getRowArray();

        return $data['phone'];
    }

    // 用户基本信息
    public function member_info($uid) {

        $uid = intval($uid);
        if (!$uid) {
            return [];
        } elseif ($uid == $this->member['uid']) {
            return $this->member;
        }

        $data = $this->db->table('member')->where('id', $uid)->get()->getRowArray();
        if (!$data) {
            return [];
        }

        $data['uid'] = $data['id'];

        return $data;
    }

    /**
     * 登录记录
     *
     * @param   intval  $data       会员
     * @param   string  $OAuth      登录方式
     */
    protected function _login_log($data, $type = '') {

        if (!IS_USE_MEMBER) {
            return;
        }

        $ip = \Phpcmf\Service::L('input')->ip_address();
        if (!$ip || !$data['id']) {
            return;
        }

        $agent = \Phpcmf\Service::L('input')->get_user_agent();
        if (strlen($agent) <= 5) {
            return;
        }

        $log = [
            'uid' => $data['id'],
            'type' => $type,
            'loginip' => $ip,
            'logintime' => SYS_TIME,
            'useragent' => substr($agent, 0, 255),
        ];

        // 会员部分只保留20条登录记录
        $row = $this->db->table('member_login')->where('uid', $data['id'])->orderBy('logintime desc')->get()->getResultArray();
        if (dr_count($row) > 20) {
            $del = [];
            foreach ($row as $i => $t) {
                if ($i > 19) {
                    $del[] = (int)$t['id'];
                    unset($row[$i]);
                }
            }
            if ($del) {
                // 删除多余的记录
                $this->db->table('member_login')->where('uid', $data['id'])->whereIn('id', $del)->delete();
            }
        }

        // 登录后的通知
        \Phpcmf\Service::L('Notice')->send_notice('member_login', $data);

        // 登录后的钩子
        $data['log'] = [
            'now' => $log,
            'before' => $row,
        ];
        \Phpcmf\Hooks::trigger('member_login_after', $data);

        /*
        $time = \Phpcmf\Service::L('input')->get_cookie('member_login');
        if (!$time || date('Ymd') != date('Ymd', $time)) {

            \Phpcmf\Service::L('input')->set_cookie('member_login', SYS_TIME, 3600*12);
        }*/

        // 同一天Ip一致时只更新一次更新时间
        if ($row = $this->db
                        ->table('member_login')
                        ->where('uid', $data['id'])
                        ->where('loginip', $ip)
                        ->where('DATEDIFF(from_unixtime(logintime),now())=0')
                        ->get()
                        ->getRowArray()) {
                        $this->db->table('member_login')->where('id', $row['id'])->update($log);
        } else {
            $this->db->table('member_login')->insert($log);
        }

    }

    /**
     * 取会员COOKIE
     */
    public function member_uid() {

        // 获取本地cookie
        $uid = (int)\Phpcmf\Service::L('input')->get_cookie('member_uid');
        if (!$uid) {
            return 0;
        }

        return $uid;
    }


    /**
     * 初始化处理
     */
    public function init_member($member) {

        if (!$member || !IS_USE_MEMBER) {
            return;
        }

        // 明天凌晨时间戳
        $time = strtotime(date('Y-m-d', strtotime('+1 day')));

        // 每日登录积分处理
        if (dr_is_app('explog')) {
            $value = \Phpcmf\Service::L('member_auth', 'member')->member_auth('login_exp', $member);
            if ($value && !\Phpcmf\Service::L('input')->get_cookie('login_experience_'.$member['id'])) {
                $this->add_experience($member['id'], $value, dr_lang('每日登陆'), '', 'login_exp_'.date('Ymd', SYS_TIME), 1);
                \Phpcmf\Service::L('input')->set_cookie('login_experience_'.$member['id'], 1, $time - SYS_TIME);
            }
        }

        // 每日登录金币处理
        if (dr_is_app('scorelog')) {
            $value = \Phpcmf\Service::L('member_auth', 'member')->member_auth('login_score', $member);
            if ($value && !\Phpcmf\Service::L('input')->get_cookie('login_score_'.$member['id'])) {
                $this->add_score($member['id'], $value, dr_lang('每日登陆'), '', 'login_score_'.date('Ymd', SYS_TIME), 1);
                \Phpcmf\Service::L('input')->set_cookie('login_score_'.$member['id'], 1, $time - SYS_TIME);
            }
        }
    }

    /**
     * 存储cookie
     */
    public function save_cookie($data, $remember = 0) {

        // 存储cookie
        $expire = $remember ? 8640000 : SITE_LOGIN_TIME;
        \Phpcmf\Service::L('input')->set_cookie('member_uid', $data['id'], $expire);
        \Phpcmf\Service::L('input')->set_cookie('member_cookie', md5(SYS_KEY.$data['password'].(isset($data['login_attr']) ? $data['login_attr'] : '')), $expire);

        // 登录后的钩子
        \Phpcmf\Hooks::trigger('member_login_after', $data);

        $this->clear_cache($data['id']);
    }

    /**
     * 验证会员有效性 1表示通过 0表示不通过
     */
    public function check_member_cookie($member) {

        // 获取本地认证cookie
        $cookie = \Phpcmf\Service::L('input')->get_cookie('member_cookie');

        // 授权登陆时不验证
        if ($member['id'] && \Phpcmf\Service::C()->session()->get('member_auth_uid') == $member['id']) {
            return 1;
        } elseif (!$cookie) {
            return 0;
        } elseif (md5(SYS_KEY.$member['password'].(isset($member['login_attr']) ? $member['login_attr'] : '')) != $cookie) {
            return 0;
        }

        return 1;
    }

    /**
     * 授权登录信息
     */
    public function oauth($uid) {

        if (!IS_USE_MEMBER) {
            return [];
        }

        $data = $this->db->table('member_oauth')->where('uid', $uid)->get()->getResultArray();
        if (!$data) {
            return [];
        }

        $rt = [];
        foreach ($data as $t) {
            $rt[$t['oauth']] = $t;
        }

        return $rt;
    }

    /**
     * 会员信息
     */
    public function get_member($uid = 0, $name = '') {

        $uid = intval($uid);
        if ($uid && $uid == $this->member['id']) {
            return $this->member;
        }

        if ($uid) {
            $data = $this->db->table('member')->where('id', $uid)->get()->getRowArray();
        } elseif ($name) {
            $data = $this->db->table('member')->where('username', $name)->get()->getRowArray();
            $uid = (int)$data['id'];
        } else {
            return null;
        }

        if (!$data) {
            return null;
        }

        // 附表字段
        $data2 = $this->db->table('member_data')->where('id', $uid)->get()->getRowArray();
        $data2 && $data = array_merge($data, \Phpcmf\Service::L('Field')->app('member')->format_value(\Phpcmf\Service::C()->member_cache['field'], $data2));

        $data['uid'] = $data['id'];
        $data['avatar'] = dr_avatar($data['id']);
        $data['adminid'] = (int)$data['is_admin'];
        //$data['tableid'] = (int)substr((string)$data['id'], -1, 1);

        $data['group'] = $data['groupid'] = $data['levelid'] = $data['authid'] = $data['group_name'] = [];
        $data['group_timeout'] = 0;

        // 会员组信息
        if (IS_USE_MEMBER) {
            $data2 = $this->update_group($data, $this->db->table('member_group_index')->where('uid', $uid)->get()->getResultArray());
            if ($data2) {
                foreach ($data2 as $t) {
                    $data['group_name'][$t['gid']] = $t['group_name'] = \Phpcmf\Service::C()->member_cache['group'][$t['gid']]['name'];
                    $t['group_icon'] = \Phpcmf\Service::C()->member_cache['group'][$t['gid']]['level'][$t['lid']]['icon'];
                    $t['group_level'] = \Phpcmf\Service::C()->member_cache['group'][$t['gid']]['level'][$t['lid']]['name'];
                    $data['group'][$t['gid']] = $t;
                    $data['groupid'][$t['gid']] = $t['gid'];
                    $data['levelid'][$t['gid']] = $t['lid'];
                    $data['authid'][] = $t['lid'] ? $t['gid'].'-'.$t['lid'] : $t['gid'];
                    if ($t['timeout']) {
                        $data['group_timeout'] = $t['gid'];
                    }
                }
            }
        }

        return $data;
    }

    // 获取authid
    public function authid($uid) {

        if (!$uid || !IS_USE_MEMBER) {
            return [0];
        } elseif ($uid == $this->uid) {
            return \Phpcmf\Service::C()->member['authid'];
        }

        $rt = [];
        $data2 = $this->db->table('member_group_index')->where('uid', $uid)->get()->getResultArray();
        if ($data2) {
            foreach ($data2 as $t) {
                $rt[] = $t['lid'] ? $t['gid'].'-'.$t['lid'] : $t['gid'];
            }
        }

        return $rt;
    }

    // 更新用户组
    // member 用户信息
    // groups 拥有的用户组
    public function update_group($member, $groups) {

        $g = [];
        if (!$member || !$groups || !IS_USE_MEMBER) {
            return $g;
        }

        $uid = (int)$member['id'];
        foreach ($groups as $group) {
            // 判断是否可用
            if (!\Phpcmf\Service::C()->member_cache['group'][$group['gid']]) {
                continue;
            }
            $group['gid'] = (int)$group['gid'];
            // 判断等级是否有效
            $levels = isset(\Phpcmf\Service::C()->member_cache['group'][$group['gid']]['level']) ? \Phpcmf\Service::C()->member_cache['group'][$group['gid']]['level'] : [];
            if ($levels) {
                // bu存在
                if ($group['lid'] && (!isset($levels[$group['lid']]) || !$levels[$group['lid']])) {
                    $this->db->table('member_group_index')->where('uid', $uid)->where('gid', $group['gid'])->update(['lid' => 0]);
                    $group['lid'] = 0;
                }
            } elseif ($group['lid']) {
                // 还原升级
                $this->db->table('member_group_index')->where('uid', $uid)->where('gid', $group['gid'])->update(['lid' => 0]);
                $group['lid'] = 0;
            }
            $group_info = \Phpcmf\Service::C()->member_cache['group'][$group['gid']];
            // 判断过期
            $price = floatval($group_info['price']);
            if ($group['etime'] && $group['etime'] - SYS_TIME < 0) {
                // 过期了
                if ($group_info['setting']['timeout']) {
                    // 过期自动续费price
                    $name = $group_info['unit'] ? 'score' : 'money';
                    if ($name == 'money') {
                        // 余额
                        if ($price > 0) {
                            // 收费组情况下
                            if ($member[$name] - $price < 0) {
                                // 余额不足 删除
                                if (!$group_info['setting']['outtype'] && $this->delete_group($uid, $group['gid'], 0)) {
                                    $this->notice($uid, 2, dr_lang('您的用户组（%s）已过期，自动续费失败，账户%s不足', $group_info['name'], dr_lang('余额')));
                                } else {
                                    // // 不主动删除用户组 情况下保留
                                    $group['timeout'] = 1;
                                    $g[$group['gid']] = $group;
                                }
                                continue;
                            }
                            $group['etime'] = dr_member_group_etime($group_info['days'], $group_info['setting']['dtype']);
                            if (!dr_is_app('pay')) {
                                log_message('error', '用户组自动续费失败：没有安装「支付系统」插件');
                                continue;
                            } else {
                                // 自动续费
                                $rt = $this->add_money($uid, -$price);
                                if (!$rt['code']) {
                                    continue;
                                }
                                // 增加到交易流水
                                $rt = \Phpcmf\Service::M('Pay')->add_paylog([
                                    'uid' => $member['id'],
                                    'username' => $member['username'],
                                    'touid' => 0,
                                    'tousername' => '',
                                    'mid' => 'system',
                                    'title' => dr_lang('用户组（%s）续费', $group_info['name']),
                                    'value' => -$price,
                                    'type' => 'finecms',
                                    'status' => 1,
                                    'result' => dr_lang('有效期至%s', $group['etime'] ? dr_date($group['etime']) : dr_lang('永久')),
                                    'paytime' => SYS_TIME,
                                    'inputtime' => SYS_TIME,
                                ]);
                                // 提醒通知
                                $this->notice(
                                    $uid,
                                    2,
                                    dr_lang('您的用户组（%s）已过期，自动续费成功', $group_info['name']),
                                    \Phpcmf\Service::L('router')->member_url('paylog/show', ['id'=>$rt['code']])
                                );
                            }
                        } else {
                            // 免费组自己续费
                            // 提醒通知
                            $this->notice(
                                $uid,
                                2,
                                dr_lang('您的用户组（%s）已过期，自动免费续期成功', $group_info['name'])
                            );
                        }
                        // 更新时间
                        $this->db->table('member_group_index')->where('uid', $uid)->where('gid', $group['gid'])->update(['etime' => $group['etime']]);
                    } else {
                        // 金币
                        $price = (int)$price;
                        if ($price > 0) {
                            // 收费组情况下
                            if ($member[$name] - $price < 0) {
                                // 金币不足 删除
                                if (!$group_info['setting']['outtype'] && $this->delete_group($uid, $group['gid'], 0)) {
                                    // 提醒通知
                                    $this->notice($uid, 2, dr_lang('您的用户组（%s）已过期，自动续费失败，账户%s不足', $group_info['name'], SITE_SCORE));
                                } else {
                                    // false 情况下保留
                                    $group['timeout'] = 1;
                                    $g[$group['gid']] = $group;
                                }
                                continue;
                            }
                            // 自动续费
                            $group['etime'] = dr_member_group_etime($group_info['days'], $group_info['setting']['dtype']);
                            // 自动续费
                            $rt = $this->add_score($uid, -$price, dr_lang('您的用户组（%s）自动续费', $group_info['name']));
                            if (!$rt['code']) {
                                continue;
                            }
                            // 提醒通知
                            $this->notice(
                                $uid,
                                2,
                                dr_lang('您的用户组（%s）已过期，自动续费成功', $group_info['name'])
                            );
                        } else {
                            // 免费组自己续费
                            // 提醒通知
                            $this->notice(
                                $uid,
                                2,
                                dr_lang('您的用户组（%s）已过期，自动免费续期成功', $group_info['name'])
                            );
                        }
                        // 更新时间
                        $this->db->table('member_group_index')->where('uid', $uid)->where('gid', $group['gid'])->update(['etime' => $group['etime']]);
                    }
                } else {
                    // 未开通自动续费直接删除
                    if (!$group_info['setting']['outtype'] && $this->delete_group($uid, $group['gid'], 0)) {
                        // 提醒通知
                        $this->notice($uid, 2, dr_lang('您的用户组（%s）已过期，系统权限已关闭', $group_info['name']));
                    } else {
                        // false 情况下保留
                        $group['timeout'] = 1;
                        $g[$group['gid']] = $group;
                    }
                    continue;
                }
            }
            // 开启自动升级时需要判断等级
            if ($levels
                && \Phpcmf\Service::C()->member_cache['group'][$group['gid']]['setting']['level']['auto']) {
                $value = \Phpcmf\Service::C()->member_cache['group'][$group['gid']]['setting']['level']['unit'] ? $member['spend'] : $member['experience'];
                $level = array_reverse($levels); // 倒序判断
                foreach ($level as $t) {
                    if ($value >= $t['value']) {
                        if ($group['lid'] != $t['id']) {
                            // 开始变更等级
                            $this->db->table('member_group_index')->where('uid', $uid)->where('gid', $group['gid'])->update(array('lid' => $t['id']));
                            /* 等级升级 */
                            $this->notice($uid, 2, dr_lang('您的用户组（%s）等级自动升级为（%s）', \Phpcmf\Service::C()->member_cache['group'][$group['gid']]['name'], $t['name']));
                            $group['lid'] = $t['id'];
                        }
                        break;
                    }
                }
            }
            $g[$group['gid']] = $group;
        }

        return $g;
    }

    // 删除用户组 is_admin 是否是管理员删除，否则就是过期删除
    public function delete_group($uid, $gid, $is_admin = 1) {

        if (!IS_USE_MEMBER) {
            log_message('debug', '没有安装【用户系统】插件，无法执行函数：delete_group');
            return false;
        }

        // 回调信息
        $call = $this->member_info($uid);
        $call['group'] = $this->table('member_group_index')->where('gid', $gid)->where('uid', $uid)->getRow();

        $this->db->table('member_group_index')->where('gid', $gid)->where('uid', $uid)->delete();

        // 管理员删除时提醒
        if ($is_admin) {
            $this->notice($uid, 2, dr_lang('您的用户组（%s）被取消', \Phpcmf\Service::C()->member_cache['group'][$gid]['name']));
        }

        // 判断微信标记组
        if (dr_is_app('weixin')) {
            \Phpcmf\Service::C()->init_file('weixin');
            \Phpcmf\Service::M('user', 'weixin')->delete_member_group($uid, $gid);
        }

        // 过期后变更
        if (!$is_admin && \Phpcmf\Service::C()->member_cache['group'][$gid]['setting']['out_gid']
            && \Phpcmf\Service::C()->member_cache['group'][$gid]['setting']['out_gid'] != $gid) {
            $this->insert_group($uid, \Phpcmf\Service::C()->member_cache['group'][$gid]['setting']['out_gid']);
        }

        \Phpcmf\Service::M('member')->clear_cache($uid);

        \Phpcmf\Hooks::trigger('member_del_group_after', $call);

        return true;
    }

    // 新增用户组
    public function insert_group($uid, $gid, $is_notice = 1) {

        if (!IS_USE_MEMBER) {
            log_message('debug', '没有安装【用户系统】插件，无法执行函数：insert_group');
            return false;
        }

        $data = [
            'uid' => $uid,
            'gid' => $gid,
            'lid' => 0,
            'stime' => SYS_TIME,
            'etime' => \Phpcmf\Service::C()->member_cache['group'][$gid]['setting']['timetype'] ? 0 : dr_member_group_etime(\Phpcmf\Service::C()->member_cache['group'][$gid]['days'], \Phpcmf\Service::C()->member_cache['group'][$gid]['setting']['dtype']),
        ];
        $rt = $this->table('member_group_index')->insert($data);
        if (!$rt['code']) {
            return;
        }

        $data['id'] = $rt['code'];

        // 挂钩点 用户组变更之后
        $call = $this->member_info($uid);
        $call['group'] = $data;
        $call['group_gid'] = $call['gid'] = $gid;
        $call['group_name'] = \Phpcmf\Service::C()->member_cache['group'][$gid]['name'];
        $is_notice && \Phpcmf\Service::L('Notice')->send_notice('member_edit_group', $call);
        \Phpcmf\Hooks::trigger('member_edit_group_after', $call);

        // 判断微信标记组
        if (dr_is_app('weixin')) {
            \Phpcmf\Service::C()->init_file('weixin');
            \Phpcmf\Service::M('user', 'weixin')->add_member_group($uid, $gid);
        }

        if (!\Phpcmf\Service::C()->member_cache['config']['groups']) {
            // 没开启多个组时，关闭之前的用户组
            $data2 =  $this->db->table('member_group_index')->where('uid', $uid)->where('gid<>' . $gid)->get()->getResultArray();
            if ($data2) {
                foreach ($data2 as $t) {
                    $this->delete_group($uid, $t['gid'], 1);
                }
            }
        }
        \Phpcmf\Service::M('member')->clear_cache($uid);
    }

    // 手动变更等级
    public function update_level($uid, $gid, $lid) {

        if (!IS_USE_MEMBER) {
            log_message('debug', '没有安装【用户系统】插件，无法执行函数：update_level');
            return false;
        }

        $old = $data = $this->db->table('member_group_index')->where('uid', $uid)->where('gid', $gid)->get()->getRowArray();
        $data['gid'] = $gid;
        $data['lid'] = $lid;

        // 更新数据
        $update = [
            'lid' => $lid
        ];
        if (\Phpcmf\Service::C()->member_cache['group'][$gid]['setting']['timetype']) {
            // 按等级计算时间
            $update['etime'] = dr_member_group_etime(
                \Phpcmf\Service::C()->member_cache['group'][$gid]['level'][$lid]['setting']['days'],
                \Phpcmf\Service::C()->member_cache['group'][$gid]['level'][$lid]['setting']['dtype'],
                \Phpcmf\Service::C()->member_cache['group'][$gid]['setting']['timect'] ? $old['etime'] : 0
            );
        }
        $this->db->table('member_group_index')->where('uid', $uid)->where('gid', $gid)->update($update);
        // 挂钩点 用户组变更之后
        $call = $this->member_info($uid);
        $call['group_name'] = \Phpcmf\Service::C()->member_cache['group'][$gid]['name'];
        $call['group_level'] = \Phpcmf\Service::C()->member_cache['group'][$gid]['level'][$lid]['name'];
        \Phpcmf\Service::L('Notice')->send_notice('member_edit_level', $call);
        \Phpcmf\Hooks::trigger('member_edit_level_after', $data, $old);
        \Phpcmf\Service::M('member')->clear_cache($uid);
    }

    // 申请用户组
    public function apply_group($verify_id, $member, $gid, $lid, $price, $my_verify) {

        if (!IS_USE_MEMBER) {
            log_message('debug', '没有安装【用户系统】插件，无法执行函数：apply_group');
            return false;
        }

        $group = \Phpcmf\Service::C()->member_cache['group'][$gid];

        if ($group['setting']['verify']) {
            $my_verify['uid'] = $member['uid'];
            $my_verify['username'] = $member['username'];
            $my_verify['gid'] = $gid;
            $my_verify['price'] = $group['price'];
            $my_verify['lid'] = $lid;
            $my_verify['status'] = 0;
            $my_verify['content'] = dr_array2string($my_verify['content']);
            $my_verify['inputtime'] = SYS_TIME;
            if ($verify_id) {
                $rt = \Phpcmf\Service::M()->table('member_group_verify')->update($verify_id, $my_verify);
            } else {
                // 被拒再次提交不重复扣款
                $my_verify['price'] = (float)$price;
                $rt = \Phpcmf\Service::M()->table('member_group_verify')->insert($my_verify);
            }
            if (!$rt['code']) {
                return dr_return_data(0, $rt['msg']);
            }
            // 提醒
            $this->admin_notice(0, 'member', $member, dr_lang('用户组[%s]申请审核', $group['name']), 'member_apply/edit:id/'.$rt['code']);
            // 审核
            return dr_return_data(1, dr_lang('等待管理员审核'));
        } else {
            // 直接开通
            $this->insert_group($member['uid'], $gid);
            $lid && $this->update_level($member['uid'], $gid, $lid);
            $my_verify['content'] && \Phpcmf\Service::M()->table('member_data')->update($member['uid'], $my_verify['content']);

            return dr_return_data(1, dr_lang('开通成功'));
        }

    }

    /**
     * 添加一条通知
     *
     * @param   string  $uid
     * @param   string  $note
     * @return  null
     */
    public function notice($uid, $type, $note, $url = '', $mark = '') {

        if (!$uid || !$note) {
            return '';
        }

        if (dr_is_app('notice')) {
            \Phpcmf\Service::M('notice', 'notice')->add_notice($uid, $type, $note, $url, $mark);
        }

        return '';
    }

    /**
     * 系统提醒
     *
     * @param   site    站点id,公共部分0
     * @param   type    system系统  content内容相关  member会员相关 app应用相关 pay 交易相关
     * @param   msg     提醒内容
     * @param   uri     后台对应的链接
     * @param   to      通知对象 留空表示全部对象
     * array(
     *      to_uid 指定人
     *      to_rid 指定角色组
     * )
     */
    public function admin_notice($site, $type, $member, $msg, $uri, $to = []) {

        if (!$to || !is_array($to)) {
            $to = [
                'to_rid' => 0,
                'to_uid' => 0,
            ];
        }

        $data = [
            'site' => (int)$site,
            'type' => $type,
            'msg' => dr_strcut(dr_clearhtml($msg), 100),
            'uri' => $uri,
            'to_rid' => intval($to['to_rid']),
            'to_uid' => intval($to['to_uid']),
            'status' => 0,
            'uid' => (int)$member['id'],
            'username' => $member['username'] ? $member['username'] : '',
            'op_uid' => 0,
            'op_username' => '',
            'updatetime' => 0,
            'inputtime' => SYS_TIME,
        ];
        $this->db->table('admin_notice')->insert($data);

        // 挂钩点
        \Phpcmf\Hooks::trigger('admin_notice', $data);
    }

    // 执行提醒
    public function todo_admin_notice($uri, $site = 0) {
        $this->db->table('admin_notice')->where('site', (int)$site)->where('uri', $uri)->update([
            'status' => 3,
            'updatetime' => SYS_TIME,
        ]);
    }

    // 执行删除提醒
    public function delete_admin_notice($uri, $site = 0) {
        $this->db->table('admin_notice')->where('site', (int)$site)->where('uri', $uri)->delete();
    }


    // 审核用户
    public function verify_member($uid) {

        $this->db->table('member_data')->where('id', $uid)->update(['is_verify' => 1]);
        // 后台提醒
        $this->todo_admin_notice('member/home/verify/index:field/id/keyword/'.$uid, 0);
        // 审核提醒
        // 注册审核后的通知
        \Phpcmf\Service::L('Notice')->send_notice('member_register_verify', $this->get_member($uid));
    }

    // 获取本站通讯地址
    public function get_sso_url() {

        if ($this->sso_url) {
            return $this->sso_url;
        }

        $this->sso_url = [
            '/'
        ];

        if (is_file(WRITEPATH.'config/domain_sso.php')) {
            $sso = require WRITEPATH.'config/domain_sso.php';
            foreach ($sso as $u) {
                $this->sso_url[] = dr_http_prefix($u).'/';
            }
        }

        return $this->sso_url;
    }

    /**
     * sso 登录url
     */
    public function sso($data, $remember = 0) {

        $sso = [];
        $url = $this->get_sso_url();
        foreach ($url as $u) {
            $code = dr_authcode($data['id'].'-'.$data['salt'], 'ENCODE');
            $sso[]= $u.'index.php?s=api&c=sso&action=login&remember='.$remember.'&code='.$code;
        }

        return $sso;
    }

    /**
     * 前端会员退出登录
     */
    public function logout() {

        \Phpcmf\Hooks::trigger('member_logout', $this->member);

        \Phpcmf\Service::L('input')->set_cookie('member_uid', 0, -100000000);
        \Phpcmf\Service::L('input')->set_cookie('member_cookie', '', -100000000);
        \Phpcmf\Service::L('input')->set_cookie('admin_login_member', '', -100000000);

        $sso = [];
        $url = $this->get_sso_url();
        foreach ($url as $u) {
            $sso[]= $u.'index.php?s=api&c=sso&action=logout';
        }

        return $sso;
    }

    // 查询会员信息
    protected function _find_member_info($username) {

        $data = $this->db->table('member')->where('username', $username)->get()->getRowArray();
        if (!$data && \Phpcmf\Service::C()->member_cache['login']['field']) {
            if (dr_in_array('email', \Phpcmf\Service::C()->member_cache['login']['field'])
                && \Phpcmf\Service::L('Form')->check_email($username)) {
                $data = $this->db->table('member')->where('email', $username)->get()->getRowArray();
            } elseif (dr_in_array('phone', \Phpcmf\Service::C()->member_cache['login']['field'])
                && \Phpcmf\Service::L('Form')->check_phone($username)) {
                $data = $this->db->table('member')->where('phone', $username)->get()->getRowArray();
            }
        }

        if (!$data) {
            return [];
        }

        $data['uid'] = $data['id'];

        return $data;
    }

    // 验证管理员登录权限
    protected function _is_admin_login_member($uid) {

        if (!$uid) {
            return dr_return_data(1, 'ok');
        }

        if (!\Phpcmf\Service::C()->member_cache['login']['admin']
            && $this->db->table('admin')->where('uid', $uid)->countAllResults()) {
            return dr_return_data(0, dr_lang('管理员账号不允许前台登录'));
        }

        return dr_return_data(1, 'ok');
    }

    /**
     * 验证登录
     *
     * @param   string  $username   用户名
     * @param   string  $password   明文密码
     * @param   intval  $remember   是否记住密码
     */
    public function login($username, $password, $remember = 0) {

        if (!IS_USE_MEMBER) {
            log_message('debug', '没有安装【用户系统】插件，无法执行函数：login');
            return dr_return_data(0, dr_lang('没有权限'));
            return false;
        }

        if (!$username) {
            return dr_return_data(0, dr_lang('账号不能为空'));
        } elseif (!$password) {
            return dr_return_data(0, dr_lang('密码不能为空'));
        }
        // 登录
        $data = $this->_find_member_info($username);
        if (!$data) {
            return dr_return_data(0, dr_lang('用户不存在'));
        }
        // 密码验证
        $password2 = dr_safe_password($password);
        if (md5(md5($password2).$data['salt'].md5($password2)) != $data['password']) {
            if (strlen($password2) == 32 && md5($password2.$data['salt'].$password2) == $data['password']) {
                // 加密验证成功
            } else {
                \Phpcmf\Hooks::trigger('member_login_password_error', [
                    'member' => $data,
                    'password' => $password,
                    'ip' => (string)\Phpcmf\Service::L('input')->ip_address(),
                    'time' => SYS_TIME,
                ]);
                return dr_return_data(0, dr_lang('密码不正确'));
            }
        }

        // 验证管理员登录
        /*
        $rt = $this->_is_admin_login_member($data['id']);
        if (!$rt['code']) {
            return $rt;
        }*/

        // 保存本地会话
        $this->save_cookie($data, $remember);

        // 记录日志
        $this->_login_log($data);

        return dr_return_data(1, 'ok', [
            'auth'=> md5($data['password'].$data['salt']), // API认证字符串,
            'member' => $this->get_member($data['id']),
            'sso' => $this->sso($data, $remember)]
        );
    }

    // 短信登录
    public function login_sms($phone, $remember) {

        $data = $this->db->table('member')->where('phone', $phone)->get()->getRowArray();
        if (!$data) {
            // 未注册
            if (\Phpcmf\Service::C()->member_cache['login']['auto_reg']) {
                // 自动注册
                $groupid = (int)\Phpcmf\Service::C()->member_cache['register']['groupid'];
                if (!$groupid) {
                    return dr_return_data(0, dr_lang('无效的用户组'));
                } elseif (!\Phpcmf\Service::C()->member_cache['group'][$groupid]['register']) {
                    return dr_return_data(0, dr_lang('用户组[%s]不允许注册', \Phpcmf\Service::C()->member_cache['group'][$groupid]['name']));
                }
                $rt = $this->register($groupid, [
                    'username' => '',
                    'phone' => $phone,
                    'email' => '',
                    'password' => '',
                    'name' => '',
                ]);
                if ($rt['code']) {
                    $data = $rt['data'];
                    $data['uid'] = $data['id'];
                } else {
                    return dr_return_data(0, $rt['msg'], ['field' => $rt['data']['field']]);
                }
            } else {
                return dr_return_data(0, dr_lang('手机号码未注册'));
            }
        } else {
            // 记录日志
            $data['uid'] = $data['id'];
            $this->_login_log($data);
        }

        // 保存本地会话
        $this->save_cookie($data, $remember);

        return dr_return_data($data['id'], 'ok', [
                'auth'=> md5($data['password'].$data['salt']), // API认证字符串,
                'member' => $this->get_member($data['id']),
                'sso' => $this->sso($data, $remember)]
        );
    }

    // 授权登录
    public function login_oauth($name, $data) {

        // 保存本地会话
        $this->save_cookie($data);

        // 记录日志
        $this->_login_log($data, $name);

        return $this->sso($data);
    }

    // 绑定注册模式 授权注册绑定
    public function register_oauth_bang($oauth, $groupid, $member, $data = []) {

        if (!$oauth) {
            return dr_return_data(0, dr_lang('OAuth数据不存在，请重试'));
        }

        $rt = $this->register($groupid, $member, $data, $oauth);
        if (!$rt['code']) {
            return dr_return_data(0, $rt['msg']);
        }

        $member = $rt['data'];

        // 保存本地会话
        $this->save_cookie($member);

        // 记录日志
        $this->_login_log($member, $oauth['oauth']);

        // 更改状态
        $this->db->table('member_oauth')->where('id', $oauth['id'])->update(['uid' => $member['id']]);

        // 更新微信插件粉丝表
        if (dr_is_app('weixin') && $oauth['oauth'] == 'wechat') {
            $this->db->table('weixin_user')->where('openid', $oauth['oid'])->update([
                'uid' => $member['id'],
                'username' => $member['username'],
            ]);
        }

        // 同步登录
        $sso = $this->sso($member);

        // 下载头像
        \Phpcmf\Service::L('thread')->cron(['action' => 'oauth_down_avatar', 'id' => $oauth['id'] ]);

        return dr_return_data($member['id'], 'ok', [
            'auth'=> md5($member['password'].$member['salt']), // API认证字符串,
            'member' => $member,
            'sso' => $sso
        ]);
    }

    // api直接按uid登录
    public function login_uid($oauth, $uid) {

        $member = $this->get_member($uid);
        if (!$member) {
            return dr_return_data(0, dr_lang('用户不存在'));
        }

        // 保存本地会话
        $this->save_cookie($member);

        // 记录日志
        $this->_login_log($member, $oauth['oauth']);

        return dr_return_data($member['id'], 'ok', [
            'auth'=> md5($member['password'].$member['salt']), // API认证字符串,
            'member' => $member,
        ]);
    }

    // 直接登录模式 授权注册
    public function register_oauth($groupid, $oauth) {

        $rt = $this->register($groupid, [
            'username' => '',
            'name' => dr_clear_emoji($oauth['nickname']),
            'email' => '',
            'phone' => '',
        ], null, $oauth);
        if (!$rt['code']) {
            return dr_return_data(0, $rt['msg']);
        }

        $data = $rt['data'];

         // 保存本地会话
        $this->save_cookie($data);

        // 记录日志
        $this->_login_log($data, $oauth['oauth']);

        // 更改状态
        $this->db->table('member_oauth')->where('id', $oauth['id'])->update(['uid' => $data['id']]);
        dr_is_app('weixin') && $oauth['oauth'] == 'wechat' && $this->db->table('weixin_user')->where('openid', $oauth['oid'])->update([
            'uid' => $data['id'],
            'username' => $data['username'],
        ]);

        // 下载头像和同步登录
        $sso = $this->sso($data);
        $sso[] = \Phpcmf\Service::L('router')->member_url('api/avatar', ['id'=>$oauth['id']]);

        return dr_return_data($data['id'], 'ok', [
            'auth'=> md5($data['password'].$data['salt']), // API认证字符串,
            'member' => $data,
            'sso' => $sso
        ]);
    }

    /**
     * 用户注册
     *
     * @param   用户组
     * @param   注册账户信息
     * @param   自定义字段信息
     * @param   快捷登录注册
     */
    public function register($groupid, $member, $data = [], $oauth = []) {

        $member['email'] && $member['email'] = strtolower($member['email']);
        $member['name'] = htmlspecialchars(!$member['name'] ? '' : dr_strcut($member['name'], intval(\Phpcmf\Service::C()->member_cache['register']['cutname']), ''));

        // 没有账号，随机一个默认登录账号
        if (!$member['username']) {
            $member['username'] = $this->_register_rand_username($member);
        } else {
            $member['username'] = strtolower(dr_safe_filename($member['username']));
        }

        // 验证格式
        if (dr_in_array('username', \Phpcmf\Service::C()->member_cache['register']['field'])) {
            $rt = \Phpcmf\Service::L('Form')->check_username($member['username']);
            if (!$rt['code']) {
                return $rt;
            }
        }

        // 默认注册组
        !$groupid && $groupid = (int)\Phpcmf\Service::C()->member_cache['register']['groupid'];

        if ((\Phpcmf\Service::C()->member_cache['oauth']['login'] || !\Phpcmf\Service::C()->member_cache['oauth']['field']) && $oauth) {
            // 授权登录直接模式
        } else {
            if (dr_in_array('email', \Phpcmf\Service::C()->member_cache['register']['field'])
                && !\Phpcmf\Service::L('Form')->check_email($member['email'])) {
                return dr_return_data(0, dr_lang('邮箱格式不正确'), ['field' => 'email']);
            } elseif (dr_in_array('phone', \Phpcmf\Service::C()->member_cache['register']['field'])
                && !\Phpcmf\Service::L('Form')->check_phone($member['phone'])) {
                return dr_return_data(0, dr_lang('手机号码格式不正确'), ['field' => 'phone']);
            }
            // 前端验证密码格式
            if (!IS_ADMIN) {
                $rt = \Phpcmf\Service::L('Form')->check_password($member['password'], $member['username']);
                if (!$rt['code']) {
                    return $rt;
                }
            }
        }

        // 验证唯一性
        if ($member['username'] && $this->db->table('member')->where('username', $member['username'])->countAllResults()) {
            return dr_return_data(0, dr_lang('账号已经注册'), ['field' => 'username']);
        } elseif ($member['email'] && $this->db->table('member')->where('email', $member['email'])->countAllResults()) {
            return dr_return_data(0, dr_lang('邮箱已经注册'), ['field' => 'email']);
        } elseif ($member['phone'] && $this->db->table('member')->where('phone', $member['phone'])->countAllResults()) {
            return dr_return_data(0, dr_lang('手机号码已经注册'), ['field' => 'phone']);
        }

        if ($member['username'] == 'guest') {
            return dr_return_data(0, dr_lang('此名称guest系统不允许注册'), ['field' => 'username']);
        }
        /*
        elseif (!IS_ADMIN && \Phpcmf\Service::C()->member_cache['register']['notallow']) {
            foreach (\Phpcmf\Service::C()->member_cache['register']['notallow'] as $mt) {
                if ($mt && stripos($member['username'], $mt) !== false) {
                    return dr_return_data(0, dr_lang('账号[%s]禁止包含关键字[%s]', $member['username'], $mt), ['field' => 'username']);
                }
            }
        }*/

        $member['salt'] = substr(md5(rand(0, 999)), 0, 10); // 随机10位密码加密码
        $member['password'] = $member['password'] ? md5(md5($member['password']).$member['salt'].md5($member['password'])) : '';
        $member['login_attr'] = '';
        $member['money'] = 0;
        $member['freeze'] = 0;
        $member['spend'] = 0;
        $member['score'] = 0;
        $member['experience'] = 0;
        $member['regip'] = \Phpcmf\Service::L('input')->ip_info();
        $member['regtime'] = SYS_TIME;
        $member['randcode'] = \Phpcmf\Service::L('form')->get_rand_value();

        $rt = $this->table('member')->insert($member);
        if (!$rt['code']) {
            return dr_return_data(0, $rt['msg']);
        }

        // 再次判断没有账号，随机一个默认登录账号
        if (!$member['username']) {
            $member['username'] = strtolower(trim(\Phpcmf\Service::C()->member_cache['register']['unprefix']
            .intval($rt['code']+date('Ymd'))));
            // 更新操作
            $this->table('member')->update($rt['code'], [
                'username' => $member['username']
            ]);
        }

        // 附表信息
        $data['id'] = $member['uid'] = $uid = $rt['code'];
        $data['is_admin'] = 0;
        $data['is_avatar'] = 0;
        // 审核状态值
        if (IS_ADMIN) {
            $status = \Phpcmf\Service::L('input')->post('status');
            $data['is_lock'] = isset($status['is_lock']) ? intval($status['is_lock']) : 0;
            $data['is_email'] = isset($status['is_email']) ? intval($status['is_email']) : 0;
            $data['is_verify'] = isset($status['is_verify']) ? intval($status['is_verify']) : 0;
            $data['is_mobile'] = isset($status['is_mobile']) ? intval($status['is_mobile']) : 0;
        } else {
            $data['is_lock'] = 0;
            $data['is_email'] = 0;
            $data['is_verify'] = \Phpcmf\Service::C()->member_cache['register']['verify'] ? 0 : 1;
            $data['is_mobile'] = \Phpcmf\Service::C()->member_cache['register']['sms'] ? 1 : 0;
        }
        $data['is_complete'] = 0;
        $rt = $this->table('member_data')->insert($data);
        if (!$rt['code']) {
            // 删除主表
            $this->table('member')->delete($uid);
            return dr_return_data(0, $rt['msg']);
        }

        // 归属用户组
        IS_USE_MEMBER && $this->insert_group($uid, $groupid, 0);

        // 组合字段信息
        $data = array_merge($member, $data);
        $data['oauth'] = $oauth;
        $data['groupid'] = $groupid;

        // 审核判断
        if (!$data['is_verify']) {
            switch (\Phpcmf\Service::C()->member_cache['register']['verify']) {

                case 'phone':
                    $this->sendsms_code($member['phone'], $member['randcode']);
                    break;

                case 'email':
                    $this->sendmail($member['email'], dr_lang('注册邮件验证'), 'member_verify.html', $data);
                    break;
            }
            // 发送审核提醒
            $this->admin_notice(0, 'member', $member, dr_lang('新会员【%s】注册审核', $member['username']), 'member_verify/index:field/id/keyword/'.$uid);
        }

        // 注册后的通知
        \Phpcmf\Service::L('notice')->send_notice('member_register', $data);

        // 注册后的钩子
        \Phpcmf\Hooks::trigger('member_register_after', $data);

        // API认证字符串,
        $data['auth'] = md5($data['password'].$data['salt']);

        // 记录日志
        $this->_login_log($data);

        return dr_return_data($data['id'], 'ok', $data);
    }

    /**
     * 存储授权信息
     */
    public function insert_oauth($uid, $type, $data, $state = '', $back = '') {

        $row = $this->db->table('member_oauth')->where('oid', $data['oid'])->where('oauth', $data['oauth'])->get()->getRowArray();
        if (!$row && $data['unionid']) {
            // 没找到尝试 unionid
            $row = $this->db->table('member_oauth')->where('unionid', $data['unionid'])->get()->getRowArray();
            $ins = 1; // 新插入授权
        } else {
            $ins = 0;
        }

        // 授权更新
        if (!$row || $ins) {
            // 插入授权信息
            $data['uid'] = (int)$uid;
            $rt = $this->table('member_oauth')->insert($data);
            if (!$rt['code']) {
                return dr_return_data(0, $rt['msg']);
            }
            $id = $rt['code'];
        } else {
            // 更新授权信息
            $uid && $data['uid'] = $uid;
            $this->db->table('member_oauth')->where('id', $row['id'])->update($data);
            $id = $row['id'];
        }

        // 绑定成功更新头像
        if ($uid && $data['avatar']) {
            list($cache_path) = dr_avatar_path();
            if (!is_file($cache_path.$uid.'.jpg')) {
                // 没有头像下载头像
                $img = dr_catcher_data($data['avatar']);
                if (strlen($img) > 20 && file_put_contents($cache_path.$uid.'.jpg', $img)) {
                    // 头像状态认证
                    $this->db->table('member_data')->where('id', $uid)->update(['is_avatar' => 1]);
                }
            }
        }

        // 存储
        \Phpcmf\Service::L('cache')->set_auth_data('member_auth_'.$type.'_'.$data['oauth'].'_'.$id, $id);

        return dr_return_data($id, $type == 'login' ? \Phpcmf\Service::L('router')->member_url('login/oauth', ['id' => $id, 'name' => $data['oauth'], 'state' => $state, 'back' => $back]) : \Phpcmf\Service::L('router')->member_url('account/oauth', ['id' => $id, 'name' => $data['oauth']]));
    }

    // 修改密码
    public function edit_password($member, $password) {

        $id = (int)$member['id'];
        $password = dr_safe_password($password);
        if (!$id || !$password) {
            return false;
        }

        $update['salt'] = substr(md5(rand(0, 999)), 0, 10); // 随机10位密码加密码
        $update['randcode'] = 0;
        $update['password'] = md5(md5($password).$update['salt'].md5($password));
        $this->db->table('member')->where('id', $id)->update($update);

        $member['uid'] = $id;
        $member['password_value'] = $password;

        // 通知
        \Phpcmf\Service::L('Notice')->send_notice('member_edit_password', $member);
        // 钩子
        \Phpcmf\Hooks::trigger('member_edit_password_after', $member);

        $this->clear_cache($id);

        return true;
    }

    /**
     * 邮件发送
     */
    public function sendmail($tomail, $subject, $msg, $data = []) {
        return \Phpcmf\Service::M('email')->sendmail($tomail, $subject, $msg, $data);
    }

    /**
     * 短信发送验证码
     */
    public function sendsms_code($mobile, $code) {
        return $this->sendsms_text($mobile, $code, 'code');
    }

    /**
     * 短信发送文本
     */
    public function sendsms_text($mobile, $content, $type = 'text') {

        if (!$mobile || !$content) {
            return dr_return_data(0, dr_lang('手机号码或内容不能为空'));
        }

        $file = WRITEPATH.'config/sms.php';
        if (!is_file($file)) {
            log_message('error', '短信接口配置文件不存在');
            return dr_return_data(0, dr_lang('接口配置文件不存在'));
        }

        $config = \Phpcmf\Service::R($file);
        if ($config['third']) {
            if (is_file(ROOTPATH.'config/mysms.php')) {
                require_once ROOTPATH.'config/mysms.php';
            }
            $method = 'my_sendsms_'.$type;
            if (function_exists($method)) {
                return call_user_func_array($method, [
                    $mobile,
                    $content,
                    $config['third'],
                ]);
            } else {
                $error = dr_lang('你没有定义第三方短信接口: '. $method);
                @file_put_contents(WRITEPATH.'sms_log.php', date('Y-m-d H:i:s').' ['.$mobile.'] ['.$error.'] （'.str_replace(array(chr(13), chr(10)), '', $content).'）'.PHP_EOL, FILE_APPEND);
                return dr_return_data(0, $error);
            }
        } else {
            $content = $type == 'code' ? dr_lang('您的本次验证码是: %s', $content) : $content;
            $url = 'https://www.xunruicms.com/index.php?s=vip&c=home&uid='.$config['uid'].'&key='.$config['key'].'&mobile='.$mobile.'&content='.urlencode($content).'【'.$config['note'].'】&domain='.trim(str_replace('http://', '', SITE_URL), '/').'&sitename='.SITE_NAME;
            $result = dr_catcher_data($url);
            if (!$result) {
                log_message('error', '访问官方云短信服务器失败');
                return dr_return_data(0, dr_lang('访问官方云短信服务器失败'));
            }
            $result = json_decode($result, true);
        }

        @file_put_contents(WRITEPATH.'sms_log.php', date('Y-m-d H:i:s').' ['.$mobile.'] ['.$result['msg'].'] （'.str_replace(array(chr(13), chr(10)), '', $content).'）'.PHP_EOL, FILE_APPEND);

        return $result;
    }

    /**
     * 发送微信通知模板
     *
     * $uid 会员id
     * $id  微信模板id
     * $data    通知内容
     * $url 详细地址
     * $color   top颜色
     */
    public function weixin_template($uid, $id, $data, $url = '', $color = '') {
        return $this->wexin_template($uid, $id, $data, $url, $color);
    }
    public function wexin_template($uid, $id, $data, $url = '', $color = '') {

        if (dr_is_app('weixin')) {
            \Phpcmf\Service::C()->init_file('weixin');
            return \Phpcmf\Service::M('weixin', 'weixin')->send_template($uid, $id, $data, $url, $color);
        } else {
            return dr_return_data(0, '没有安装微信插件');
        }
    }

    /**
     * 增加经验
     *
     * @param   intval  $uid    会员id
     * @param   intval  $value  分数变动值
     * @param   string  $mark   标记
     * @param   string  $note   备注
     * @param   intval  $count  统计次数
     * @return  intval
     */
    public function add_experience($uid, $val, $note = '', $url = '', $mark = '', $count = 0) {

        if (!dr_is_app('explog')) {
            return dr_return_data(0, '未安装explog插件');
        }

        return \Phpcmf\Service::M('exp', 'explog')->add_experience($uid, $val, $note, $url, $mark, $count);
    }

    /**
     * 增加金币
     *
     * @param   intval  $uid    会员id
     * @param   intval  $value  分数变动值
     * @param   string  $mark   标记
     * @param   string  $note   备注
     * @param   intval  $count  统计次数
     * @return  intval
     */
    public function add_score($uid, $val, $note = '', $url = '', $mark = '', $count = 0) {

        if (!dr_is_app('scorelog')) {
            return dr_return_data(0, '未安装scorelog插件');
        }

        return \Phpcmf\Service::M('score', 'scorelog')->add_score($uid, $val, $note, $url, $mark, $count);
    }

    // 增加money
    public function add_money($uid, $value) {

        $value = floatval($value);
        if (!$value) {
            return dr_return_data(0, dr_lang('金额不正确'));
        }

        $member = $this->member_info($uid);
        if (!$member) {
            return dr_return_data(0, dr_lang('用户不存在'));
        }

        $money = (float)$member['money'] + $value;
        if ($money < 0) {
            return dr_return_data(0, dr_lang('账户可用余额不足'));
        }

        $update = [
            'money' => $money,
        ];
        $value < 0 && $update['spend'] = max(0, (float)$member['spend'] + abs($value));

        $rt = $this->table('member')->update($uid, $update);

        return $rt;
    }

    // 冻结资金
    public function add_freeze($uid, $value) {

        $value = floatval(abs($value));
        if (!$uid || !$value) {
            return dr_return_data(0, dr_lang('参数不正确'));
        }

        $rt = $this->query('update `'.$this->dbprefix('member').'` set `money`=`money`-'.$value.',`freeze`=`freeze`+'.$value.' where id='.$uid);
        return $rt;
    }

    // 取消冻结资金
    public function cancel_freeze($uid, $value) {

        $value = floatval(abs($value));
        if (!$uid || !$value) {
            return dr_return_data(0, dr_lang('参数不正确'));
        }

        $rt = $this->query('update `'.$this->dbprefix('member').'` set `money`=`money`+'.$value.',`freeze`=`freeze`-'.$value.' where id='.$uid);
        return $rt;
    }

    // 使用消费资金
    public function use_freeze($uid, $value) {

        $value = floatval(abs($value));
        if (!$uid || !$value) {
            return dr_return_data(0, dr_lang('参数不正确'));
        }

        $rt = $this->query('update `'.$this->dbprefix('member').'` set `spend`=`spend`+'.$value.',`freeze`=`freeze`-'.$value.' where id='.$uid);

        return $rt;
    }

    // 删除会员后执行 sync是否删除相关数据表
    public function member_delete($id, $sync = 0) {

        if (!IS_USE_MEMBER) {
            log_message('debug', '没有安装【用户系统】插件，无法执行函数：member_delete');
            return false;
        }

        $this->clear_cache($id);

        // 删除会员的相关表
        $this->db->table('member_data')->where('id', $id)->delete();
        $this->db->table('member_group_index')->where('uid', $id)->delete();
        $this->db->table('member_login')->where('uid', $id)->delete();
        $this->db->table('member_oauth')->where('uid', $id)->delete();
        $this->db->table('admin')->where('uid', $id)->delete();
        $this->db->table('admin_login')->where('uid', $id)->delete();
        $this->db->table('admin_role_index')->where('uid', $id)->delete();
        $this->db->table('member_group_verify')->where('uid', $id)->delete();
        $this->is_table_exists('member_paylog') && $this->db->table('member_paylog')->where('uid', $id)->delete();
        $this->is_table_exists('member_scorelog') && $this->db->table('member_scorelog')->where('uid', $id)->delete();
        $this->is_table_exists('member_explog') && $this->db->table('member_explog')->where('uid', $id)->delete();
        $this->is_table_exists('member_cashlog') && $this->db->table('member_cashlog')->where('uid', $id)->delete();
        $this->is_table_exists('member_notice') && $this->db->table('member_notice')->where('uid', $id)->delete();
        $this->delete_admin_notice('member_verify/index:field/id/keyword/'.$id, 0);

        // 删除头像
        list($cache_path, $cache_url) = dr_avatar_path();
        if (is_file($cache_path.$id.'.jpg')) {
            unlink($cache_url.$id.'.jpg');
        }

        // 删除微信uid
        if (dr_is_app('weixin') && $this->is_table_exists('weixin_user')) {
            $this->db->table('weixin_user')->where('uid', $id)->update([
                'uid' => 0,
                'username' => '',
            ]);
        }

        if (!$sync) {
            return ;
        }

        // 同步删除动作
        \Phpcmf\Service::M('Sync')->delete_member($id);

        // 按站点数据删除
        SYS_ATTACHMENT_DB && \Phpcmf\Service::M('Attachment')->uid_delete($id);
        foreach ($this->site as $siteid) {
            // 表单
            $form = $this->is_table_exists($siteid.'_form') ? $this->init(['table' => $siteid.'_form'])->getAll() : [];
            if ($form) {
                foreach ($form as $t) {
                    $table = $siteid.'_form_'.$t['table'];
                    \Phpcmf\Service::M()->db->tableExists(\Phpcmf\Service::M()->dbprefix($table)) && $this->db->table($table)->where('uid', $id)->delete();
                    for ($i = 0; $i < 200; $i ++) {
                        if (!$this->db->query("SHOW TABLES LIKE '".$this->dbprefix($table).'_data_'.$i."'")->getRowArray()) {
                            break;
                        }
                        $this->db->table($table.'_data_'.$i)->where('uid', $id)->delete();
                    }
                }
            }
            // 模块
            $module = $this->table('module')->getAll();
            if ($module) {
                foreach ($module as $m) {
                    $mdir = $m['dirname'];
                    $table = $siteid.'_'.$mdir;
                    // 模块内容
                    if (!$this->db->query("SHOW TABLES LIKE '".$this->dbprefix($table)."'")->getRowArray()) {
                        break;
                    }
                    $mdb = \Phpcmf\Service::M('Content', $mdir);
                    $mdb->_init($mdir, $siteid);
                    // 查询删除内容
                    $index = $this->table($table.'_index')->where('uid', $id)->getAll();
                    if ($index) {
                        foreach ($index as $t) {
                            $mdb->delete_content($t['id']);
                        }
                    }
                    $form = $this->is_table_exists('module_form') ? $this->db->table('module_form')->where('module', $mdir)->get()->getResultArray() : [];
                    if ($form) {
                        foreach ($form as $t) {
                            $mytable = $table.'_form_'.$t['table'];
                            if (!$this->db->query("SHOW TABLES LIKE '".$this->dbprefix($mytable)."'")->getRowArray()) {
                                break;
                            }
                            $this->db->table($mytable)->where('uid', $id)->delete();
                            for ($i = 0; $i < 200; $i ++) {
                                if (!$this->db->query("SHOW TABLES LIKE '".$this->dbprefix($mytable).'_data_'.$i."'")->getRowArray()) {
                                    break;
                                }
                                $this->db->table($mytable.'_data_'.$i)->where('uid', $id)->delete();
                            }
                        }
                    }
                }
            }
        }
    }

    // 头像认证执行
    public function do_avatar($member) {

        if ($member['is_avatar'] || !IS_USE_MEMBER) {
            return;
        }

        $this->db->table('member_data')->where('id', $member['id'])->update(['is_avatar' => 1]);
        // avatar_score
        $value = \Phpcmf\Service::L('member_auth', 'member')->member_auth('avatar_score', $member);
        if ($value) {
            \Phpcmf\Service::M('member')->add_experience($member['id'], $value, dr_lang('头像认证'), '', 'avatar_score', 1);
        }
        $value = \Phpcmf\Service::L('member_auth', 'member')->member_auth('avatar_exp', $member);
        if ($value) {
            $this->add_score($member['id'], $value, dr_lang('头像认证'), '', 'avatar_exp', 1);
        }
    }

    // 注册随机账号
    protected function _register_rand_username($member, $ct = 0) {

        if ($member['email']) {
            list($name) = explode('@', $member['email']);
        } elseif ($member['phone']) {
            $name = substr($member['phone'], 3);
        } elseif ($member['name']) {
            $name = \Phpcmf\Service::L('pinyin')->result($member['name']);
        } else {
            return '';
        }

        // 重复名称加随机数
        if ($ct && $ct < 5) {
            $name.= $ct + rand(0, 999);
            if ($ct > 5) {
                $name.= rand(0, 999);
            }
        }

        // 重复账号时
        if ($this->db->table('member')->where('username', $name)->countAllResults()) {
            $name = $this->_register_rand_username($member, $ct + 1);
        }

        // 最大位数
        if (\Phpcmf\Service::C()->member_cache['config']['userlenmax']
            && mb_strlen($name) > \Phpcmf\Service::C()->member_cache['config']['userlenmax']) {
            $name = dr_strcut($name, \Phpcmf\Service::C()->member_cache['config']['userlenmax'], '');
        }

        return $name;
    }

    // 修改账号
    public function edit_username($uid, $username) {

        $this->clear_cache($uid, $username);

        $this->table('member')->update($uid, [
            'username' => $username,
        ]);

        $this->db->table('member_group_verify')->where('uid', $uid)->update([ 'username' => $username ]);

        \Phpcmf\Service::L('cache')->set_data('member-info-'.$uid, '', 1);
    }

    // 清理指定用户缓存
    public function clear_cache($uid, $username = '') {

        \Phpcmf\Service::L('cache')->del_data('member-info-'.$uid);
        $username && \Phpcmf\Service::L('cache')->del_data('member-info-name-'.$username);
    }

    // 按用户uid查询表id集合
    protected function _get_data_ids($uid, $table) {

    }

    // 用户系统缓存
    public function cache($site = SITE_ID) {

        $cache = [
            'field' => [],
            'authid' => [ 0 ],
            'group' => [],
            'config' => [],
            'pay' => [],
        ];

        if (!dr_is_app('member')) {
            \Phpcmf\Service::L('cache')->set_file('member', $cache);
            return $cache;
        }

        // 审核流程
        $data = $this->table('admin_verify')->getAll();
        $verify = [];
        if ($data) {
            foreach ($data as $t) {
                $t['value'] = dr_string2array($t['verify']);
                unset($t['verify']);
                $verify[$t['id']] = $t;
            }
        }
        \Phpcmf\Service::L('cache')->set_file('verify', $verify);

        // 获取会员全部配置信息
        $cache = [];
        $result = $this->db->table('member_setting')->get()->getResultArray();
        if ($result) {
            foreach ($result as $t) {
                $cache[$t['name']] = dr_string2array($t['value']);
            }
        }

        if (!isset($cache['list_field']) || !$cache['list_field']) {
            $cache['list_field'] = array (
                'username' =>
                    array (
                        'use' => '1',
                        'name' => '账号',
                        'width' => '110',
                        'func' => 'author',
                    ),
                'group' =>
                    array (
                        'func' => 'group',
                        'center' => '1',
                    ),
                'name' =>
                    array (
                        'use' => '1',
                        'name' => '姓名',
                        'width' => '120',
                        'func' => '',
                    ),
                'money' =>
                    array (
                        'use' => '1',
                        'name' => '余额',
                        'width' => '120',
                        'func' => 'money',
                    ),
                'score' =>
                    array (
                        'use' => '1',
                        'name' => '积分',
                        'width' => '120',
                        'func' => 'score',
                    ),
                'regip' =>
                    array (
                        'use' => '1',
                        'name' => '注册IP',
                        'width' => '140',
                        'func' => 'ip',
                    ),
                'regtime' =>
                    array (
                        'use' => '1',
                        'name' => '注册时间',
                        'width' => '170',
                        'func' => 'datetime',
                    ),
                'is_lock' =>
                    array (
                        'func' => 'save_select_value',
                        'center' => '1',
                    ),
            );
        }

        // 字段归属
        $cache['myfield'] = $cache['field'];

        // 自定义字段
        $register_field = $group_field = $cache['field'] = [];
        $field = $this->db->table('field')->where('disabled', 0)->where('relatedname', 'member')->orderBy('displayorder ASC,id ASC')->get()->getResultArray();
        if ($field) {
            foreach ($field as $f) {
                $f['setting'] = dr_string2array($f['setting']);
                $cache['field'][$f['fieldname']] = $f;
                // 归类用户组字段
                if ($cache['myfield'][$f['id']]) {
                    foreach ($cache['myfield'][$f['id']] as $gid) {
                        $group_field[$gid][] = $f['fieldname'];
                    }
                }
                // 归类可用注册的字段
                if ($cache['register_field'][$f['id']]) {
                    $register_field[] = $f['fieldname'];
                }
            }
        }

        // 支付接口
        if ($cache['payapi']) {
            foreach ($cache['payapi'] as $i => $t) {
                if (!$t['use']) {
                    unset($cache['payapi'][$i]);
                }
            }
        }

        // 注册配置
        $cache['register']['notallow'] = explode(',', trim($cache['register']['notallow']));

        // 用户组
        $cache['register']['group'] = [];
        $group = $this->db->table('member_group')->orderBy('displayorder ASC,id ASC')->get()->getResultArray();
        if ($group) {
            foreach ($group as $t) {
                $level = $this->db->table('member_level')->where('gid', $t['id'])->orderBy('displayorder ASC,id ASC')->get()->getResultArray();
                if ($level) {
                    foreach ($level as $lv) {
                        $lv['icon'] = dr_get_file($lv['stars']);
                        $lv['setting'] = dr_string2array($lv['setting']);
                        $cache['authid'][] = $t['id'].'-'.$lv['id'];
                        $t['level'][$lv['id']] = $lv;
                    }
                } else {
                    $cache['authid'][] = $t['id'];
                }
                $t['setting'] = dr_string2array($t['setting']);
                // 用户组的可用字段
                $t['field'] = $group_field[$t['id']];
                // 当前用户组开启了注册时, 查询它可注册的字段
                $t['register'] && $t['field'] && $t['register_field'] = $register_field ? dr_array_intersect($t['field'], $register_field) : [];
                // 是否允许注册
                $t['register'] && $cache['register']['group'][] = $t['id'];
                $cache['group'][$t['id']] = $t;
            }
        }

        \Phpcmf\Service::L('cache')->set_file('member', $cache);
    }

}