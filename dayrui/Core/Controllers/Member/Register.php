<?php namespace Phpcmf\Controllers\Member;

/**
 * http://www.xunruicms.com
 * 本文件是框架系统文件，二次开发时不可以修改本文件
 **/


class Register extends \Phpcmf\Common
{

    // 注册
    public function index() {

        // 获取返回URL
        $url = $_GET['back'] ? urldecode($_GET['back']) : $_SERVER['HTTP_REFERER'];
        $url && parse_str($url, $arr);
        if (isset($arr['back']) && $arr['back']) {
            $url = \Phpcmf\Service::L('input')->xss_clean($arr['back']);
        }
        if (strpos($url, 'login') !== false || strpos($url, 'register') !== false) {
            $url = MEMBER_URL; // 当来自登录或注册页面时返回到用户中心去
        } else {
            $url = \Phpcmf\Service::L('input')->xss_clean($url);
        }

        // 判断重复登录
        if ($this->uid) {
            dr_redirect($url ? $url : MEMBER_URL);exit;
        }

        // 验证系统是否支持注册
        if ($this->member_cache['register']['close']) {
            $this->_msg(0, dr_lang('系统关闭了注册功能'));
        } elseif (!$this->member_cache['register']['groupid']) {
            $this->_msg(0, dr_lang('系统没有设置默认注册的用户组'));
        } elseif (!$this->member_cache['register']['group']) {
            $this->_msg(0, dr_lang('系统没有可注册的用户组'));
        } elseif (!$this->member_cache['register']['field']) {
            $this->_msg(0, dr_lang('系统没有可用的注册字段'));
        } elseif (!array_intersect($this->member_cache['register']['field'], ['username', 'email', 'phone'])) {
            $this->_msg(0, dr_lang('必须设置用户名、邮箱、手机的其中一个设为可注册字段'));
        }

        // 验证用户组
        $groupid = (int)\Phpcmf\Service::L('input')->get('groupid');
        !$groupid && $groupid = (int)$this->member_cache['register']['groupid'];
        if (!$groupid) {
            $this->_msg(0, dr_lang('无效的用户组'));
        } elseif (!$this->member_cache['group'][$groupid]['register']) {
            $this->_msg(0, dr_lang('用户组[%s]不允许注册', $this->member_cache['group'][$groupid]['name']));
        }
        
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
            $post = \Phpcmf\Service::L('input')->post('data');
            if (!\Phpcmf\Service::L('input')->post('is_protocol')) {
                $this->_json(0, dr_lang('你没有同意注册协议'));
            } elseif ($this->member_cache['register']['code']
                && !\Phpcmf\Service::L('Form')->check_captcha('code')) {
                $this->_json(0, dr_lang('图片验证码不正确'), ['field' => 'code']);
            } elseif (empty($post['password'])) {
                $this->_json(0, dr_lang('密码必须填写'), ['field' => 'password']);
            } elseif ($post['password'] != $post['password2']) {
                $this->_json(0, dr_lang('确认密码不正确'), ['field' => 'password2']);
            } else {
                // 注册之前的钩子
                \Phpcmf\Hooks::trigger('member_register_before', $post);
                // 验证操作
                if ($this->member_cache['register']['sms']) {
                    $sms = \Phpcmf\Service::L('Form')->get_mobile_code($post['phone']);
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
                if ($return) {
                    $this->_json(0, $return['error'], ['field' => $return['name']]);
                }
                $rt = \Phpcmf\Service::M('member')->register($groupid, [
                    'username' => (string)$post['username'],
                    'phone' => (string)$post['phone'],
                    'email' => (string)$post['email'],
                    'password' => dr_safe_password($post['password']),
                    'name' => dr_safe_replace($post['name']),
                ], $data[1]);
                if ($rt['code']) {
                    // 注册成功
                    $remember = 0;
                    $this->member = $rt['data'];
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


}
