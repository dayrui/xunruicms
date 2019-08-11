<?php namespace Phpcmf\Controllers\Member;

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



class Register extends \Phpcmf\Common
{

    // 注册
    public function index() {

        // 验证系统是否支持注册
        if ($this->member_cache['register']['close']) {
            $this->_msg(0, dr_lang('系统关闭了注册功能'));
        } elseif (!$this->member_cache['register']['groupid']) {
            $this->_msg(0, dr_lang('系统没有设置默认注册的用户组'));
        } elseif (!$this->member_cache['register']['group']) {
            $this->_msg(0, dr_lang('系统没有可注册的用户组'));
        } elseif (!$this->member_cache['register']['field']) {
            $this->_msg(0, dr_lang('系统没有可用的注册字段'));
        }

        // 验证用户组
        $groupid = (int)\Phpcmf\Service::L('input')->get('groupid');
        !$groupid && $groupid = (int)$this->member_cache['register']['groupid'];
        if (!$groupid) {
            $this->_msg(0, dr_lang('无效的用户组'));
        } elseif (!$this->member_cache['group'][$groupid]['register']) {
            $this->_msg(0, dr_lang('该用户组不允许注册'));
        }

        // 获取返回URL
        $url = \Phpcmf\Service::L('Security')->xss_clean($_GET['back'] ? urldecode($_GET['back']) : $_SERVER['HTTP_REFERER']);
        strpos($url, 'register') !== false && $url = MEMBER_URL;
        
        // 初始化自定义字段类
        \Phpcmf\Service::L('Field')->app(APP_DIR);

        // 获取该组可用注册字段
        $field = [];
        if ($this->member_cache['group'][$groupid]['register_field']) {
            foreach ($this->member_cache['group'][$groupid]['register_field'] as $fname) {
                $field[$fname] = $this->member_cache['field'][$fname];
                $field[$fname]['ismember'] = 1;
            }
        }

        if (IS_AJAX_POST) {
            $post = \Phpcmf\Service::L('input')->post('data', true);
            if (!\Phpcmf\Service::L('input')->post('is_protocol')) {
                $this->_json(0, dr_lang('你没有同意注册协议'));
            } elseif ($this->member_cache['register']['code']
                && !\Phpcmf\Service::L('Form')->check_captcha('code')) {
                $this->_json(0, dr_lang('验证码不正确'), ['field' => 'code']);
            } elseif (in_array('username', $this->member_cache['register']['field'])
                && !\Phpcmf\Service::L('Form')->check_username($post['username'])) {
                $this->_json(0, dr_lang('账号格式不正确'), ['field' => 'username']);
            } elseif (in_array('email', $this->member_cache['register']['field'])
                && !\Phpcmf\Service::L('Form')->check_email($post['email'])) {
                $this->_json(0, dr_lang('邮箱格式不正确'), ['field' => 'email']);
            } elseif (in_array('phone', $this->member_cache['register']['field'])
                && !\Phpcmf\Service::L('Form')->check_phone($post['phone'])) {
                $this->_json(0, dr_lang('手机号码格式不正确'), ['field' => 'phone']);
            } elseif (empty($post['password'])) {
                $this->_json(0, dr_lang('密码必须填写'), ['field' => 'password']);
            } elseif ($post['password'] != $post['password2']) {
                $this->_json(0, dr_lang('确认密码不正确'), ['field' => 'password2']);
            } else {
                // 注册之前的钩子
                \Phpcmf\Hooks::trigger('member_register_before', $post);
                // 验证操作
                if ($this->member_cache['register']['sms']) {
                    $sms = $this->session()->get('member-register-phone-'.$post['phone']);
                    if (!$sms) {
                        $this->_json(0, dr_lang('未发送手机验证码'), ['field' => 'sms']);
                    } elseif (!$_POST['sms']) {
                        $this->_json(0, dr_lang('手机验证码未填写'), ['field' => 'sms']);
                    } elseif ($sms != $_POST['sms']) {
                        $this->_json(0, dr_lang('手机验证码不正确'), ['field' => 'sms']);
                    }
                }

                // 验证字段
                list($data, $return, $attach) = \Phpcmf\Service::L('Form')->validation($post, null, $field);
                // 输出错误
                $return && $this->_json(0, $return['error'], ['field' => $return['name']]);
                $rt = \Phpcmf\Service::M('member')->register($groupid, [
                    'username' => (string)$post['username'],
                    'phone' => (string)$post['phone'],
                    'email' => (string)$post['email'],
                    'password' => dr_safe_password($post['password']),
                ], $data[1]);
                if ($rt['code']) {
                    // 注册成功
                    $this->member = $rt['data'];
                    $remember = 0;
                    // 保存本地会话
                    \Phpcmf\Service::M('member')->save_cookie($this->member, $remember);
                    // 附件归档
                    SYS_ATTACHMENT_DB && $attach && \Phpcmf\Service::M('Attachment')->handle(
                        $this->member['id'],
                        \Phpcmf\Service::M()->dbprefix('member').'-'.$rt['code'],
                        $attach
                    );
                    // 手机认证成功
                    if ($this->member_cache['register']['sms']) {
                        \Phpcmf\Service::M()->db->table('member_data')->where('id', $this->member['id'])->update(['is_mobile' => 1]);
                    }
                    $this->_json(1, 'ok', [
                        'url' => urldecode(\Phpcmf\Service::L('input')->xss_clean($_POST['back'] ? $_POST['back'] : MEMBER_URL)),
                        'sso' => \Phpcmf\Service::M('member')->sso($this->member, $remember),
                        'member' => $this->member,
                    ]);
                } else {
                    $this->_json(0, $rt['msg'], ['field' => $rt['data']['field']]);
                }
            }
            exit;
        }
        
        \Phpcmf\Service::V()->assign([
            'form' => dr_form_hidden(['back' => $url]),
            'group' => $this->member_cache['group'],
            'groupid' => $groupid,
            'is_code' => $this->member_cache['register']['code'],
            'myfield' => \Phpcmf\Service::L('field')->toform(0, $field),
            'register' => $this->member_cache['register'],
            'meta_name' => dr_lang('用户注册'),
            'meta_title' => dr_lang('用户注册').SITE_SEOJOIN.SITE_NAME,
        ]);
        \Phpcmf\Service::V()->display('register.html');
    }

    // 邀请注册
    public function yq() {

        if (!dr_is_app('yaoqing')) {
            $this->_msg(0, dr_lang('邀请注册应用未安装'));
        }

        // 存储邀请参数
        $uid = (int)\Phpcmf\Service::L('input')->get('uid');
        $rt = \Phpcmf\Service::M('yq', 'yaoqing')->yaoqing_rule($uid);
        if (!$rt['code']) {
            $this->_msg(0, $rt['msg']);
        }

        // 存储邀请状态
        $this->session()->set('app_yaoqing_uid', $uid);

        dr_redirect(dr_member_url('register/index'));exit;

    }


}
