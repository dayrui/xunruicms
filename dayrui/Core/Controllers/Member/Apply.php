<?php namespace Phpcmf\Controllers\Member;

/**
 * http://www.xunruicms.com
 * 本文件是框架系统文件，二次开发时不可以修改本文件
 **/

class Apply extends \Phpcmf\Common
{

    /**
     * 申请升级用户组
     */
    public function index() {

        $gid = intval(\Phpcmf\Service::L('input')->get('gid'));
        if ($this->member['groupid'][$gid]) {
            exit($this->_msg(0, dr_lang('无需重复申请')));
        }

        $group = $this->member_cache['group'][$gid];
        if (!$group['apply']) {
            $this->_msg(0, dr_lang('用户组[%s]不存在', $gid));
        } elseif (!$group['apply']) {
            $this->_msg(0, dr_lang('用户组[%s]没有开启申请权限', $group['name']));
        }

        // 判断是否已经申请
        $verify = \Phpcmf\Service::M()->db->table('member_group_verify')->where('uid', $this->uid)->where('gid', $gid)->get()->getRowArray();
        if ($verify) {
            if (!$verify['status']) {
                $this->_msg(0, dr_lang('正在审核之中'));
            }
            $verify['content'] = dr_string2array($verify['content']);
        }

        $level = $group['level'] && !$group['setting']['level']['auto'] ? $group['level'] : [];

        // 判断是否存在用户组级别,并且设置为手动模式才可以选择申请
        if ($level) {
            foreach ($level as $i => $t) {
                if (!$t['apply']) {
                    unset($level[$i]);
                }
            }
        }

        // 初始化自定义字段类
        \Phpcmf\Service::L('Field')->app(APP_DIR);

        // 获取该组可用字段
        $field = [];
        if ($this->member_cache['field'] && $this->member_cache['group'][$gid]['field']) {
            foreach ($this->member_cache['field'] as $fname => $t) {
                in_array($fname, $this->member_cache['group'][$gid]['field']) && $field[$fname] = $t;
            }
        }
        //print_r($this->member_cache);exit;

        if (IS_POST) {

            $lid = 0;
            $post = \Phpcmf\Service::L('input')->post('data');
            $my_verify = $attach = [];
            if ($field) {
                list($data, $return, $attach) = \Phpcmf\Service::L('Form')->id($this->uid)->validation($post, null, $field, $verify ? dr_string2array($verify['content']) : $this->member);
                // 输出错误
                $return && $this->_json(0, $return['error'], ['field' => $return['name']]);
            }

            // 审核申请
            $group['setting']['verify'] && $my_verify['content'] = dr_array2string($data[1]);
            // 附件归档
            SYS_ATTACHMENT_DB && $attach && \Phpcmf\Service::M('Attachment')->handle(
                $this->member['id'],
                \Phpcmf\Service::M()->dbprefix('member').'-'.$this->uid,
                $attach
            );

            // 不重复扣款
            if (!$verify && !$group['setting']['level']['auto']) {
                // 不开启自动升级的时候进入
                // 价格判断
                if ($level) {
                    $lid = (int)\Phpcmf\Service::L('input')->post('lid');
                    if (!$lid) {
                        $this->_json(0, dr_lang('用户组级别未选择'));
                    } elseif (!$group['level'][$lid]) {
                        $this->_json(0, dr_lang('用户组级别无效'));
                    } elseif (!$group['level'][$lid]['apply']) {
                        $this->_json(0, dr_lang('用户组级别不允许申请'));
                    }
                    if ($group['level'][$lid]['value']) {
                        // 存在价格时才扣钱
                        if ($group['unit']) {
                            // 金币
                            $price = (int)$group['level'][$lid]['value'];
                            // 金币不足
                            if ($this->member['score'] - $price < 0) {
                                $this->_json(0, dr_lang('账户%s不足', SITE_SCORE));
                            }
                            // 扣分
                            $rt = \Phpcmf\Service::M('member')->add_score($this->uid, -$price, dr_lang('申请用户组（%s）: %s', $group['name'], $group['level'][$lid]['name']), '', '');
                            if (!$rt['code']) {
                                $this->_json(0, $rt['msg']);
                            }
                            // 提醒通知
                            \Phpcmf\Service::M('member')->notice($this->uid, 2, dr_lang('申请用户组（%s）: %s', $group['name'], $group['level'][$lid]['name']));
                        } else {
                            // rmb
                            $price = (int)$group['level'][$lid]['value'];
                            if ($this->member['money'] - $price < 0) {
                                $this->_json(0, dr_lang('账户余额不足'));
                            }
                            // 扣钱
                            $rt = \Phpcmf\Service::M('Pay')->add_money($this->uid, -$price);
                            if (!$rt['code']) {
                                $this->_json(0, $rt['msg']);
                            }
                            // 增加到交易流水
                            $rt = \Phpcmf\Service::M('Pay')->add_paylog([
                                'uid' => $this->member['id'],
                                'username' => $this->member['username'],
                                'touid' => 0,
                                'tousername' => '',
                                'mid' => 'system',
                                'title' => dr_lang('申请用户组（%s）: %s', $group['name'], $group['level'][$lid]['name']),
                                'value' => -$price,
                                'type' => 'finecms',
                                'status' => 1,
                                'result' => '',
                                'paytime' => SYS_TIME,
                                'inputtime' => SYS_TIME,
                            ]);
                            // 提醒通知
                            \Phpcmf\Service::M('member')->notice(
                                $this->uid,
                                2,
                                dr_lang('申请用户组（%s）: %s', $group['name'], $group['level'][$lid]['name']),
                                \Phpcmf\Service::L('Router')->member_url('paylog/show', ['id'=>$rt['code']])
                            );
                        }
                    }
                } elseif ($group['price'] > 0) {
                    // 存在价格时
                    if ($group['unit']) {
                        // 金币
                        $price = (int)$group['price'];
                        // 金币不足
                        if ($this->member['score'] - $price < 0 ) {
                            $this->_json(0, dr_lang('账户%s不足', SITE_SCORE));
                        }
                        // 扣分
                        $rt = \Phpcmf\Service::M('member')->add_score($this->uid, -$price, dr_lang('申请用户组（%s）', $group['name']));
                        if (!$rt['code']) {
                            $this->_json(0, $rt['msg']);
                        }
                        // 提醒通知
                        \Phpcmf\Service::M('member')->notice($this->uid, 2, dr_lang('申请用户组（%s）', $group['name']));
                    } else {
                        // rmb
                        $price = (int)$group['price'];
                        if ($this->member['money'] - $price < 0) {
                            $this->_json(0, dr_lang('账户余额不足'));
                        }
                        // 扣钱
                        $rt = \Phpcmf\Service::M('Pay')->add_money($this->uid, -$price);
                        if (!$rt['code']) {
                            $this->_json(0, $rt['msg']);
                        }
                        // 增加到交易流水
                        $rt = \Phpcmf\Service::M('Pay')->add_paylog([
                            'uid' => $this->member['id'],
                            'username' => $this->member['username'],
                            'touid' => 0,
                            'tousername' => '',
                            'mid' => 'system',
                            'title' => dr_lang('申请用户组（%s）', $group['name']),
                            'value' => -$price,
                            'type' => 'finecms',
                            'status' => 1,
                            'result' => '',
                            'paytime' => SYS_TIME,
                            'inputtime' => SYS_TIME,
                        ]);
                        // 提醒通知
                        \Phpcmf\Service::M('member')->notice(
                            $this->uid,
                            2,
                            dr_lang('申请用户组（%s）', $group['name']),
                            \Phpcmf\Service::L('Router')->member_url('paylog/show', ['id'=>$rt['code']])
                        );
                    }
                }
            }

            if ($group['setting']['verify']) {
                $my_verify['uid'] = $this->uid;
                $my_verify['username'] = $this->member['username'];
                $my_verify['gid'] = $gid;
                $my_verify['lid'] = $lid;
                $my_verify['status'] = 0;
                $my_verify['inputtime'] = SYS_TIME;
                if ($verify['id']) {
                    $rt = \Phpcmf\Service::M()->table('member_group_verify')->update($verify['id'], $my_verify);
                } else {
                    // 被拒再次提交不重复扣款
                    $my_verify['price'] = (float)$price;
                    $rt = \Phpcmf\Service::M()->table('member_group_verify')->insert($my_verify);
                }
                if (!$rt['code']) {
                    $this->_json(0, $rt['msg']);
                }
                // 提醒
                \Phpcmf\Service::M('member')->admin_notice(0, 'member', $this->member, dr_lang('用户组申请'), 'member_apply/edit:id/'.$rt['code']);
                // 审核
                $this->_json(1, dr_lang('等待管理员审核'));
            } else {
                // 直接开通
                \Phpcmf\Service::M('member')->insert_group($this->uid, $gid);
                $lid && \Phpcmf\Service::M('member')->update_level($this->uid, $gid, $lid);
                $data[1] && \Phpcmf\Service::M()->table('member_data')->update($this->uid, $data[1]);
                // 邀请注册用户组分成
                if (dr_is_app('yaoqing') && !$group['unit']) {
                    \Phpcmf\Service::M('yq', 'yaoqing')->insert_group($this->uid, $gid, $price);
                }
                $this->_json(1, dr_lang('开通成功'));
            }
        }


        \Phpcmf\Service::V()->assign([
            'form' => dr_form_hidden(),
            'level' => $level,
            'group' => $group,
            'verify' => $verify,
            'myfield' => \Phpcmf\Service::L('Field')->toform($this->uid, $field, $verify ? dr_string2array($verify['content']) : $this->member),
            'meta_title' => dr_lang('申请用户组').SITE_SEOJOIN.dr_lang('用户中心')
        ]);
        \Phpcmf\Service::V()->display('apply_index.html');
    }

    /**
     * 升级用户组级别
     */
    public function level() {

        $gid = intval(\Phpcmf\Service::L('input')->get('gid'));
        if (!$this->member['groupid'][$gid]) {
            $this->_msg(0, dr_lang('你还不是该用户组成员'));
        }

        $group = $this->member_cache['group'][$gid];
        if ($group['setting']['level']['auto']) {
            $this->_msg(0, dr_lang('此用户组是自动升级模式'));
        } elseif (!$group['level']) {
            $this->_msg(0, dr_lang('此用户组没有创建用户级别'));
        }

        $mylid = (int)$this->member['levelid'][$gid]; // 我的当前级别
        $myvalue = !$group['setting']['level']['price'] ? (int)$group['level'][$mylid]['value'] : 0; // 我的当前级别的升级值

        if (!$group['level'][$mylid]['apply']) {
            $this->_msg(0, dr_lang('当前级别不允许升级'));
        }

        // 列出当前可用的升级级别
        $level = [];
        $level2 = array_reverse($group['level']);
        foreach ($level2 as $i => $t) {
            if ($mylid == $t['id']) {
                break;
            }
            $t['value2'] = $t['value'];
            $t['value'] = $t['value'] - $myvalue;
            $level[$t['id']] = $t;
        }
        if (!$level) {
            $this->_msg(0, dr_lang('没有可用的用户组级别'));
        }

        if (IS_POST) {

            $lid = intval($_POST['lid']);
            if (!$level[$lid]) {
                $this->_msg(0, dr_lang('用户组级别不存在'));
            } elseif (!$level[$lid]['apply']) {
                $this->_msg(0, dr_lang('用户组级别不允许升级'));
            } elseif ($level[$lid]['value'] < 0) {
                $this->_msg(0, dr_lang('用户组级别升级值不规范'));
            }

            $price = (int)$level[$lid]['value'];
            if ($price > 0) {
                // 存在价格时才扣钱
                if ($group['unit']) {
                    // 金币
                    // 金币不足
                    $this->member['score'] - $price < 0 && $this->_json(0, dr_lang('账户%s不足', SITE_SCORE));
                    // 扣分
                    $rt = \Phpcmf\Service::M('member')->add_score($this->uid, -$price, dr_lang('用户组（%s）升级: %s', $group['name'], $group['level'][$lid]['name']));
                    if (!$rt['code']) {
                        $this->_json(0, $rt['msg']);
                    }
                } else {
                    // rmb
                    $this->member['money'] - $price < 0 && $this->_json(0, dr_lang('账户余额不足'));
                    // 扣钱
                    $rt = \Phpcmf\Service::M('Pay')->add_money($this->uid, -$price);
                    if (!$rt['code']) {
                        $this->_json(0, $rt['msg']);
                    }
                    // 增加到交易流水
                    \Phpcmf\Service::M('Pay')->add_paylog([
                        'uid' => $this->member['id'],
                        'username' => $this->member['username'],
                        'touid' => 0,
                        'tousername' => '',
                        'mid' => 'system',
                        'title' => dr_lang('用户组（%s）升级: %s', $group['name'], $group['level'][$lid]['name']),
                        'value' => -$price,
                        'type' => 'finecms',
                        'status' => 1,
                        'result' => '',
                        'paytime' => SYS_TIME,
                        'inputtime' => SYS_TIME,
                    ]);
                }
            }

            // 提醒通知
            \Phpcmf\Service::M('member')->notice($this->uid, 2, dr_lang('用户组（%s）升级: %s', $group['name'], $group['level'][$lid]['name']));

            \Phpcmf\Service::M('member')->update_level($this->uid, $gid, $lid);
            $this->_json(1, dr_lang('开通成功'));
            exit;
        }

        // 删除不可用的升级级别
        if ($level) {
            foreach ($level as $i => $t) {
                if (!$t['apply']) {
                    unset($level[$i]);
                }
            }
        }

        \Phpcmf\Service::V()->assign([
            'form' => dr_form_hidden(),
            'level' => $level,
            'group' => $group,
            'myvalue' => $myvalue,
            'meta_title' => dr_lang('升级用户组级别').SITE_SEOJOIN.dr_lang('用户中心')
        ]);
        \Phpcmf\Service::V()->display('apply_level.html');
    }

}
