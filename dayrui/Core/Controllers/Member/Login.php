<?php namespace Phpcmf\Controllers\Member;

/**
 * http://www.xunruicms.com
 * 本文件是框架系统文件，二次开发时不可以修改本文件
 **/


class Login extends \Phpcmf\Common
{

    // 正常登录
    public function index() {

        // 获取返回页面
        $url = \Phpcmf\Service::L('Security')->xss_clean($_GET['back'] ? urldecode($_GET['back']) : $_SERVER['HTTP_REFERER']);
        strpos($url, 'login') !== false && $url = MEMBER_URL;

        if (IS_AJAX_POST) {
            $post = \Phpcmf\Service::L('input')->post('data', true);
            // 支付回调钩子
            \Phpcmf\Hooks::trigger('member_login_before', $post);
            if ($this->member_cache['login']['code']
                && !\Phpcmf\Service::L('Form')->check_captcha('code')) {
                $this->_json(0, dr_lang('图片验证码不正确'));
            } elseif (empty($post['username']) || empty($post['password'])) {
                $this->_json(0, dr_lang('账号或密码必须填写'));
            } else {
                $rt = \Phpcmf\Service::M('member')->login(dr_safe_username($post['username']), $post['password'], (int)$_POST['remember']);
                if ($rt['code']) {
                    // 登录成功
                    $rt['data']['url'] = urldecode(\Phpcmf\Service::L('input')->xss_clean($_POST['back'] ? $_POST['back'] : MEMBER_URL));
                    $this->_json(1, 'ok', $rt['data']);
                } else {
                    $this->_json(0, $rt['msg']);
                }
            }
            exit;
        }
        
        \Phpcmf\Service::V()->assign([
            'form' => dr_form_hidden(['back' => $url]),
            'is_code' => $this->member_cache['login']['code'],
            'meta_name' => dr_lang('用户登录'),
            'meta_title' => dr_lang('用户登录').SITE_SEOJOIN.SITE_NAME,
        ]);
        \Phpcmf\Service::V()->display('login.html');
    }

    // 短信验证码登录
    public function sms() {

        // 获取返回页面
        $url = $_GET['back'] ? urldecode($_GET['back']) : $_SERVER['HTTP_REFERER'];
        strpos($url, 'login') !== false && $url = MEMBER_URL;

        if (IS_AJAX_POST) {
            $post = \Phpcmf\Service::L('input')->post('data', true);
            if ($this->member_cache['login']['code']
                && !\Phpcmf\Service::L('Form')->check_captcha('code')) {
                $this->_json(0, dr_lang('图片验证码不正确'));
            } elseif (empty($post['phone'])) {
                $this->_json(0, dr_lang('手机号码必须填写'));
            } else {
                $sms = \Phpcmf\Service::L('Form')->get_mobile_code($post['phone']);
                if (!$sms) {
                    $this->_json(0, dr_lang('未发送手机验证码'), ['field' => 'sms']);
                } elseif (!$_POST['sms']) {
                    $this->_json(0, dr_lang('手机验证码未填写'), ['field' => 'sms']);
                } elseif ($sms != $_POST['sms']) {
                    $this->_json(0, dr_lang('手机验证码不正确'), ['field' => 'sms']);
                } else {
                    $rt = \Phpcmf\Service::M('member')->login_sms($post['phone'], (int)$_POST['remember']);
                    if ($rt['code']) {
                        // 登录成功
                        $rt['data']['url'] = urldecode(\Phpcmf\Service::L('input')->xss_clean($_POST['back'] ? $_POST['back'] : MEMBER_URL));
                        $this->_json(1, 'ok', $rt['data']);
                    } else {
                        $this->_json(0, $rt['msg']);
                    }
                }
            }
        } else {
            $this->_json(0, dr_lang('提交方式不正确'));
        }

    }


    /**
     * 授权登录
     */
    public function oauth() {

        $id = intval(\Phpcmf\Service::L('input')->get('id'));
        $name = dr_safe_replace(\Phpcmf\Service::L('input')->get('name'));
        $oauth_id = \Phpcmf\Service::L('cache')->init()->get('member_auth_login_'.$name.'_'.$id);
        if (!$oauth_id) {
            $this->_msg(0, dr_lang('授权信息(%s)获取失败', $name));
        }

        $oauth = \Phpcmf\Service::M()->table('member_oauth')->get($oauth_id);
        if (!$oauth) {
            $this->_msg(0, dr_lang('授权信息(#%s)不存在', $oauth_id));
        }

        // 查询关联用户
        $member = $oauth['uid'] ? \Phpcmf\Service::M('member')->get_member($oauth['uid']) : [];

        // 跳转地址
        $back = urldecode(dr_safe_replace(\Phpcmf\Service::L('input')->get('back')));
        $state = \Phpcmf\Service::L('input')->get('state');
        $goto_url = $back ? $back : MEMBER_URL;
        if ($state && $state != 'member') {
            $goto_url = strpos($state, 'http') === 0 ? $state : ROOT_URL.'index.php?s=weixin&c='.$state.'&oid='.$oauth['oid'];
        }

        if ($member) {

            // 已经绑定,直接登录
            $sso = '';
            $member['uid'] = $oauth['uid'];
            $rt = \Phpcmf\Service::M('member')->login_oauth($name, $member);
            foreach ($rt as $url) {
                $sso.= '<script src="'.$url.'"></script>';
            }

            if (strpos($goto_url, 'is_admin_call')) {
                // 存储后台回话
                \Phpcmf\Service::M('auth')->save_login_auth($name, $member['uid']);
                $goto_url.= '&name='.$name.'&uid='.$member['uid'];
                $this->_admin_msg(1, dr_lang('%s，欢迎回来', dr_html2emoji($oauth['nickname'])).$sso, \Phpcmf\Service::L('input')->xss_clean($goto_url), 0);
            } else {
                $this->_msg(1, dr_lang('%s，欢迎回来', dr_html2emoji($oauth['nickname'])).$sso, \Phpcmf\Service::L('input')->xss_clean($goto_url), 0);
            }

        } else {
            
            // 用户组判断
            if ($this->member_cache['register']['close']) {
                $this->_msg(0, dr_lang('系统关闭了注册功能'));
            } elseif (!$this->member_cache['register']['group']) {
                $this->_msg(0, dr_lang('系统没有可注册的用户组'));
            }

            // 验证用户组
            $groupid = (int)\Phpcmf\Service::L('input')->get('groupid');
            if (!$groupid) {
                $groupid = (int)@current($this->member_cache['register']['group']);
            }

            if (!$groupid) {
                $this->_msg(0, dr_lang('无效的用户组'));
            } elseif (!$this->member_cache['group'][$groupid]['register']) {
                $this->_msg(0, dr_lang('该用户组不允许注册'));
            }

            \Phpcmf\Service::V()->assign([
                'name' => $name,
                'oauth' => $oauth,
                'group' => $this->member_cache['group'],
                'groupid' => $groupid,
                'register' => $this->member_cache['register'],
            ]);

            // 没有绑定账号
            if ($this->member_cache['oauth']['login']) {
                // 直接登录
                if (IS_POST) {
                    $groupid = (int)\Phpcmf\Service::L('input')->post('groupid');
                    if (!$this->member_cache['group'][$groupid]['register']) {
                        $this->_json(0, dr_lang('该用户组不允许注册'));
                    }
                    // 注册
                    $rt = \Phpcmf\Service::M('member')->register_oauth($groupid, $oauth);
                    if ($rt['code']) {
                        // 登录成功
                        $rt['data']['url'] = \Phpcmf\Service::L('input')->xss_clean($goto_url);
                        $this->_json(1, 'ok', $rt['data']);
                    } else {
                        $this->_json(0, $rt['msg']);
                    }
                }
                \Phpcmf\Service::V()->assign([
                    'form' => dr_form_hidden(),
                    'meta_name' => dr_lang('授权登录'),
                    'meta_title' => dr_lang('授权登录').SITE_SEOJOIN.SITE_NAME,
                ]);
                \Phpcmf\Service::V()->display('login_select.html');
            } else {
                // 绑定账号
                $type = intval(\Phpcmf\Service::L('input')->get('type'));
                if (IS_POST) {
                    $post = \Phpcmf\Service::L('input')->post('data', true);
                    if (!\Phpcmf\Service::L('input')->post('is_protocol')) {
                        $this->_json(0, dr_lang('你没有同意注册协议'));
                    }
                    if ($type) {
                        // 注册绑定
                        $groupid = (int)\Phpcmf\Service::L('input')->post('groupid');
                        if (!$this->member_cache['group'][$groupid]['register']) {
                            $this->_json(0, dr_lang('该用户组不允许注册'));
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
                            $rt = \Phpcmf\Service::M('member')->register_oauth_bang($oauth, $groupid, [
                                'username' => (string)$post['username'],
                                'phone' => (string)$post['phone'],
                                'email' => (string)$post['email'],
                                'password' => dr_safe_password($post['password']),
                            ]);
                            if ($rt['code']) {
                                // 注册绑定成功
                                $rt['data']['url'] = \Phpcmf\Service::L('input')->xss_clean($goto_url);
                                $this->_json(1, 'ok', $rt['data']);
                            } else {
                                $this->_json(0, $rt['msg'], ['field' => $rt['data']['field']]);
                            }
                        }
                    } else {
                        // 登录绑定
                        if (empty($post['username']) || empty($post['password'])) {
                            $this->_json(0, dr_lang('账号或密码必须填写'));
                        } else {
                            $rt = \Phpcmf\Service::M('member')->login($post['username'], $post['password'], (int)$_POST['remember']);
                            if ($rt['code']) {
                                // 登录成功
                                $rt['data']['url'] = \Phpcmf\Service::L('input')->xss_clean($goto_url);
                                // 更改状态
                                \Phpcmf\Service::M()->db->table('member_oauth')->where('id', $oauth['id'])->update(['uid' => $rt['data']['member']['id']]);
                                dr_is_app('weixin') && $oauth['oauth'] == 'wechat' && \Phpcmf\Service::M()->db->table('weixin_user')->where('openid', $oauth['oid'])->update([
                                    'uid' => $rt['data']['member']['id'],
                                    'username' => $rt['data']['member']['username'],
                                ]);
                                $this->_json(1, 'ok', $rt['data']);
                            } else {
                                $this->_json(0, $rt['msg']);
                            }
                        }
                    }
                    exit;
                }
                \Phpcmf\Service::V()->assign([
                    'type' => $type,
                    'form' => dr_form_hidden(['type' => $type]),
                    'meta_name' => dr_lang('绑定账号'),
                    'meta_title' => dr_lang('绑定账号').SITE_SEOJOIN.SITE_NAME,
                ]);
                \Phpcmf\Service::V()->display('login_oauth.html');
            }
        }
        exit;
    }
    
    /**
     * 找回密码
     */
    public function find() {

        if (IS_POST) {

            $post = \Phpcmf\Service::L('input')->post('data');
            $value = dr_safe_replace($post['value']);
            if (strpos($value, '@') !== false) {
                // 邮箱模式
                $data = \Phpcmf\Service::M()->db->table('member')->where('email', $value)->get()->getRowArray();
                if (!$data) {
                    $this->_json(0, dr_lang('账号凭证不存在'), ['field' => 'value']);
                }
            } elseif (is_numeric($value) && strlen($value) == 11) {
                // 手机
                $data = \Phpcmf\Service::M()->db->table('member')->where('phone', $value)->get()->getRowArray();
                if (!$data) {
                    $this->_json(0, dr_lang('账号凭证不存在'), ['field' => 'value']);
                }
            } else {
                $this->_json(0, dr_lang('账号凭证格式不正确'), ['field' => 'value']);
            }

            // 防止验证码猜测
            if (dr_is_app('xrsafe')) {
                // 如果安装迅睿安全插件，进入插件验证机制
                \Phpcmf\Service::M('safe', 'xrsafe')->find_check($value);
            } else {
                // 普通验证
                $sn = 'fc_passwrod_find_'.date('Ymd', SYS_TIME).$value;
                $count = (int)\Phpcmf\Service::L('cache')->init()->get($sn);
                if ($count > 20) {
                    $this->_json(0, dr_lang('今日找回密码次数达到上限'));
                }
                \Phpcmf\Service::L('cache')->init()->save($sn, $count + 1, 3600 * 24);
            }

            if ((!$post['code'] || !$data['randcode'] || $post['code'] != $data['randcode'])) {
                $this->_json(0, dr_lang('凭证验证码不正确'), ['field' => 'code']);
            } elseif (!$post['password']) {
                $this->_json(0, dr_lang('密码不能为空'), ['field' => 'password']);
            } elseif ($post['password'] != $post['password2']) {
                $this->_json(0, dr_lang('两次密码不一致'), ['field' => 'password2']);
            }

            // 修改密码
            \Phpcmf\Service::M('member')->edit_password($data, $post['password']);
            \Phpcmf\Service::M()->db->table('member')->where('id', $data['id'])->update(['randcode' => 0]);

            $this->_json(1, dr_lang('修改成功'));
        }

        \Phpcmf\Service::V()->assign([
            'form' => dr_form_hidden(),
            'api_url' =>\Phpcmf\Service::L('Router')->member_url('api/find_code'),
            'meta_name' => dr_lang('找回密码'),
            'meta_title' => dr_lang('找回密码').SITE_SEOJOIN.SITE_NAME,
        ]);
        \Phpcmf\Service::V()->display('find.html');
    }

    /**
     * 退出
     */
    public function out() {

        // 注销授权登陆的会员
        if ($this->session()->get('member_auth_uid')) {
            \Phpcmf\Service::C()->session()->delete('member_auth_uid');
            $this->_json(0, dr_lang('当前状态无法退出账号'));
        }
        
        $this->_json(1, dr_lang('您的账号已退出系统'), [
            'url' => \Phpcmf\Service::L('input')->xss_clean(isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : SITE_URL),
            'sso' => \Phpcmf\Service::M('member')->logout(),
        ]);
    }

}
