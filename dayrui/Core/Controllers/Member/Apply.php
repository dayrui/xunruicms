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

        // 申请用户组之前的钩子
        \Phpcmf\Hooks::trigger('member_apply_group_before', ['uid' => $this->uid, 'gid' => $gid]);

        $is_pay = 1;
        // 判断是否已经申请
        $verify = \Phpcmf\Service::M()->db->table('member_group_verify')->where('uid', $this->uid)->where('gid', $gid)->get()->getRowArray();
        if ($verify) {
            if (!$verify['status']) {
                $this->_msg(0, dr_lang('正在审核之中'));
            }
            $verify['content'] = dr_string2array($verify['content']);
            if ($verify['price'] < $group['price']) {
                // 当审核被拒绝时，之前付款的价格小于现在价格，需要补差价
                $group['price'] = $group['price'] - $verify['price'];
            } else {
                $is_pay = 0;
            }
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
                // 审核申请
                $my_verify['content'] = $data[1];
            }
            // 附件归档
            SYS_ATTACHMENT_DB && $attach && \Phpcmf\Service::M('Attachment')->handle(
                $this->member['id'],
                \Phpcmf\Service::M()->dbprefix('member').'-'.$this->uid,
                $attach
            );

            // 不重复扣款
            if ($is_pay && !$group['setting']['level']['auto']) {
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
                    $title = dr_lang('申请用户组（%s）: %s', $group['name'], $group['level'][$lid]['name']);
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
                            $rt = \Phpcmf\Service::M('member')->add_score($this->uid, -$price, $title, '', '');
                            if (!$rt['code']) {
                                $this->_json(0, $rt['msg']);
                            }
                            // 提醒通知
                            \Phpcmf\Service::M('member')->notice($this->uid, 2, $title);
                        } else {
                            // rmb
                            $price = (int)$group['level'][$lid]['value'];

                            // 支付方式
                            $pay_type = \Phpcmf\Service::L('input')->post('pay_type');
                            !$pay_type && $pay_type = 'finecms';
                            // 唤起支付接口
                            $pay = [
                                'mark' => 'group-'.$group['id'].'-'.$lid.'-'.(int)$verify['id'],
                                'uid' => $this->member['uid'],
                                'username' => $this->member['username'],
                                'type' => $pay_type,
                                'url' => '',
                                'result' => dr_array2string($my_verify),
                                'money' => $price,
                                'title' => $title
                            ];
                            $rt = \Phpcmf\Service::M('Pay')->post($pay);
                            if (!$rt['code']) {
                                $this->_msg(0, $rt['msg']);exit;
                            }
                            // 必须跳转到统一的主域名中付款
                            $url = PAY_URL . 'index.php?s=api&c=pay&id=' . $rt['code'];
                            if (IS_AJAX || IS_API_HTTP) {
                                // 回调页面
                                $rt['data']['url'] = $url;
                                $this->_json($rt['code'], dr_lang('请稍后'), $rt['data']);
                                exit;
                            } else {
                                // 跳转到支付页面
                                dr_redirect($url, 'auto');
                                exit;
                            }
                        }
                    }
                } elseif ($group['price'] > 0) {
                    // 存在价格时
                    $title = dr_lang('申请用户组（%s）', $group['name']);
                    if ($group['unit']) {
                        // 金币
                        $price = (int)$group['price'];
                        // 金币不足
                        if ($this->member['score'] - $price < 0 ) {
                            $this->_json(0, dr_lang('账户%s不足', SITE_SCORE));
                        }
                        // 扣分
                        $rt = \Phpcmf\Service::M('member')->add_score($this->uid, -$price, $title);
                        if (!$rt['code']) {
                            $this->_json(0, $rt['msg']);
                        }
                        // 提醒通知
                        \Phpcmf\Service::M('member')->notice($this->uid, 2, $title);
                    } else {
                        // rmb
                        $price = (int)$group['price'];
                        // 支付方式
                        $pay_type = \Phpcmf\Service::L('input')->post('pay_type');
                        !$pay_type && $pay_type = 'finecms';
                        // 唤起支付接口
                        $pay = [
                            'mark' => 'group-'.$group['id'].'-0-'.(int)$verify['id'],
                            'uid' => $this->member['uid'],
                            'username' => $this->member['username'],
                            'type' => $pay_type,
                            'url' => '',
                            'result' => dr_array2string($my_verify),
                            'money' => $price,
                            'title' => $title
                        ];
                        $rt = \Phpcmf\Service::M('Pay')->post($pay);
                        if (!$rt['code']) {
                            $this->_msg(0, $rt['msg']);exit;
                        }
                        // 必须跳转到统一的主域名中付款
                        $url = PAY_URL . 'index.php?s=api&c=pay&id=' . $rt['code'];
                        if (IS_AJAX || IS_API_HTTP) {
                            // 回调页面
                            $rt['data']['url'] = $url;
                            $this->_json($rt['code'], dr_lang('请稍后'), $rt['data']);
                            exit;
                        } else {
                            // 跳转到支付页面
                            dr_redirect($url, 'auto');
                            exit;
                        }
                    }
                }
            }

            // 入库存储
            $rt = \Phpcmf\Service::M('member')->apply_group($verify['id'], $this->member, $gid, $lid, $price, $my_verify);
            if (!$rt['code']) {
                $this->_json(0, $rt['msg']);
            }
            $this->_json(1, $rt['msg'], ['url' => MEMBER_URL]);
        }

        // 付款方式
        \Phpcmf\Service::V()->assign([
            'form' => dr_form_hidden(),
            'level' => $level,
            'group' => $group,
            'verify' => $verify,
            'myfield' => \Phpcmf\Service::L('Field')->toform($this->uid, $field, $verify ? dr_string2array($verify['content']) : $this->member),
            'pay_type' => \Phpcmf\Service::M('pay')->get_pay_type(1),
            'meta_title' => dr_lang('申请用户组').SITE_SEOJOIN.dr_lang('用户中心')
        ]);
        \Phpcmf\Service::V()->display(is_file(dr_tpl_path(1).'apply_'.$gid.'.html') ? 'apply_'.$gid.'.html' : 'apply_index.html');
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
            $title = dr_lang('用户组（%s）升级: %s', $group['name'], $group['level'][$lid]['name']);
            if ($price > 0) {
                // 存在价格时才扣钱
                if ($group['unit']) {
                    // 金币
                    // 金币不足
                    $this->member['score'] - $price < 0 && $this->_json(0, dr_lang('账户%s不足', SITE_SCORE));
                    // 扣分
                    $rt = \Phpcmf\Service::M('member')->add_score($this->uid, -$price, $title);
                    if (!$rt['code']) {
                        $this->_json(0, $rt['msg']);
                    }
                    // 提醒通知
                    \Phpcmf\Service::M('member')->notice($this->uid, 2, $title);
                } else {
                    // rmb
                    // 支付方式
                    $pay_type = \Phpcmf\Service::L('input')->post('pay_type');
                    !$pay_type && $pay_type = 'finecms';
                    // 唤起支付接口
                    $pay = [
                        'mark' => 'level-' . $group['id'] . '-' . $lid,
                        'uid' => $this->member['uid'],
                        'username' => $this->member['username'],
                        'type' => $pay_type,
                        'url' => '',
                        'result' => '',
                        'money' => $price,
                        'title' => $title
                    ];
                    $rt = \Phpcmf\Service::M('Pay')->post($pay);
                    if (!$rt['code']) {
                        $this->_msg(0, $rt['msg']);
                        exit;
                    }
                    // 必须跳转到统一的主域名中付款
                    $url = PAY_URL . 'index.php?s=api&c=pay&id=' . $rt['code'];
                    if (IS_AJAX || IS_API_HTTP) {
                        // 回调页面
                        $rt['data']['url'] = $url;
                        $this->_json($rt['code'], dr_lang('请稍后'), $rt['data']);
                        exit;
                    } else {
                        // 跳转到支付页面
                        dr_redirect($url, 'auto');
                        exit;
                    }
                }
            }

            \Phpcmf\Service::M('member')->update_level($this->uid, $gid, $lid);

            $this->_json(1, dr_lang('开通成功'), ['url' => MEMBER_URL]);
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
            'pay_type' => \Phpcmf\Service::M('pay')->get_pay_type(1),
            'meta_title' => dr_lang('升级用户组级别').SITE_SEOJOIN.dr_lang('用户中心')
        ]);
        \Phpcmf\Service::V()->display('apply_level.html');
    }

}
