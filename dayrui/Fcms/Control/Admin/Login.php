<?php namespace Phpcmf\Control\Admin;

/**
 * {{www.xunruicms.com}}
 * {{迅睿内容管理框架系统}}
 * 本文件是框架系统文件，二次开发时不可以修改本文件
 **/

class Login extends \Phpcmf\Common
{

	public function index() {

	    $url = '';
	    if (isset($_GET['go']) && $_GET['go']) {
            $url = pathinfo(urldecode((string)\Phpcmf\Service::L('input')->get('go')));
            $url = $url['basename'] && $url['basename'] != SELF ? $url['basename'] : '';
        }

		// 避免安装时的卡顿超时
		if (is_file(WRITEPATH.'install.test')) {
            set_time_limit(0);
            // 创建后台默认菜单
            \Phpcmf\Service::M('Menu')->init('admin');
            \Phpcmf\Service::M('Menu')->init('admin_min');
            // 完成之后更新缓存
            \Phpcmf\Service::M('cache')->update_cache();
            unlink(WRITEPATH.'install.test');
        }

        $is_sms = isset($_GET['is_sms']) && $_GET['is_sms'];

		if (IS_AJAX_POST) {
            $sn = 0;
            // 回调钩子
            $data = \Phpcmf\Service::L('input')->post('data');
            \Phpcmf\Hooks::trigger('admin_login_before', $data);
            if (!IS_DEV && isset($_POST['is_check']) && $_POST['is_check']) {
                // 二次短信验证
                $data['phone'] = dr_authcode($data['phone'], 'DECODE');
                if (!$data['phone']) {
                    $this->_json(0, dr_lang('手机号码解析失败'));
                }
                if (SYS_ADMIN_CODE && !\Phpcmf\Service::L('form')->check_captcha('code')) {
                    $this->_json(0, dr_lang('验证码不正确'));
                }
                $is_sms = 1;
            }
            if (!IS_DEV && $is_sms) {
                // 验证码登录
                if (!$data['phone']) {
                    $this->_json(0, dr_lang('手机号码未填写'));
                } elseif (!$data['sms']) {
                    $this->_json(0, dr_lang('短信验证码未填写'));
                }
                // 验证操作间隔
                $name = 'admin-login-phone-'.$data['phone'];
                $code = \Phpcmf\Service::L('cache')->check_auth_data($name,
                    defined('SYS_CACHE_SMS') && SYS_CACHE_SMS ? SYS_CACHE_SMS : 60);
                if (!$code) {
                    $this->_json(0, dr_lang('短信验证码还没有发送'));
                } elseif ($code != $data['sms']) {
                    $this->_json(0, dr_lang('短信验证码不正确'));
                }
                $login = \Phpcmf\Service::M('auth')->login('cms_sms_00001', $data['phone']);
                if ($login['code']) {
                    // 登录成功
                } else {
                    // 登录失败
                    // 写入日志
                    \Phpcmf\Service::L('input')->system_log($login['msg'], 1, [], dr_safe_replace($data['phone']));
                    $this->_json(0, $login['msg']);
                }
            } else {
                // 账号登录
                if (defined('SYS_ADMIN_LOGINS') && SYS_ADMIN_LOGINS) {
                    $sn = (int)$this->session()->get('fclogin_error_sn');
                    $time = (int)$this->session()->get('fclogin_error_time');
                    if (defined('SYS_ADMIN_LOGIN_TIME') && SYS_ADMIN_LOGIN_TIME && $time && SYS_TIME - $time > (SYS_ADMIN_LOGIN_TIME * 60)) {
                        // 超过时间了
                        \Phpcmf\Service::C()->session()->set('fclogin_error_sn', 0);
                        \Phpcmf\Service::C()->session()->set('fclogin_error_time', 0);
                    }
                }
                if (SYS_ADMIN_CODE && !\Phpcmf\Service::L('form')->check_captcha('code')) {
                    $this->_json(0, dr_lang('验证码不正确'));
                } elseif (!IS_DEV && defined('SYS_ADMIN_LOGINS') && SYS_ADMIN_LOGINS && $sn && $sn > SYS_ADMIN_LOGINS) {
                    $msg = dr_lang('失败次数已达到%s次，已被禁止登录', SYS_ADMIN_LOGINS);
                    $msg2 = dr_lang('后台登录密码错误的次数已达到%s次', SYS_ADMIN_LOGINS);
                    $user = \Phpcmf\Service::M()->table('member')->where('username', $data['username'])->getRow();
                    if ($user) {
                        \Phpcmf\Service::M()->table('member_data')->update($user['id'], ['is_lock' => 1]);
                        $user['email'] && \Phpcmf\Service::M('member')->sendmail($user['email'], $msg2, 'admin_password_error.html', $data);
                    }
                    \Phpcmf\Service::L('input')->system_log($msg2, 1, [], $data['username']);
                    $this->_json(0, $msg);
                } elseif (empty($data['username']) || empty($data['password'])) {
                    $this->_json(0, dr_lang('账号或密码必须填写'));
                } else {
                    if (defined('SYS_ADMIN_LOGIN_AES') && SYS_ADMIN_LOGIN_AES && !isset($_POST['is_aes'])) {
                        $this->_json(0, dr_lang('当前登录模板不支持AES加密传输'));
                    }
                    $data['username'] = dr_safe_username($data['username']);
                    $login = \Phpcmf\Service::M('auth')->login(
                        $data['username'],
                        $data['password'],
                        !IS_DEV && defined('SYS_ADMIN_SMS_CHECK') && SYS_ADMIN_SMS_CHECK
                    );
                    if (isset($this->admin) && is_array($this->admin)) {
                        $this->admin['uid'] = 0;
                        $this->admin['username'] = $data['username'];
                    }
                    if ($login['code']) {
                        // 登录成功
                        if ($login['code'] == "sms") {
                            $this->_json(9, dr_lang('向%s的手机发送验证码：',
                                substr($login['msg'], 0, 3).'****'.substr($login['msg'],-4)),
                                dr_authcode($login['msg'], 'ENCODE')
                            );
                        }
                    } else {
                        // 登录失败
                        if ($login['data'] == 3) {
                            // 密码错误时记录日志
                            \Phpcmf\Service::L('input')->password_log($data);
                            // 记录错误计次
                            if (defined('SYS_ADMIN_LOGINS') && SYS_ADMIN_LOGINS) {
                                \Phpcmf\Service::C()->session()->set('fclogin_error_sn', $sn + 1);
                                \Phpcmf\Service::C()->session()->set('fclogin_error_time', SYS_TIME);
                            }
                        } else {
                            // 写入日志
                            \Phpcmf\Service::L('input')->system_log($login['msg'], 1, [], dr_safe_replace($data['username']));
                        }
                        $this->_json(0, $login['msg']);
                    }
                }
            }

            \Phpcmf\Service::L('input')->system_log('登录后台成功', 1, [], $data['username']);
            if ($sn) {
                // 解除禁止登陆
                \Phpcmf\Service::C()->session()->set('fclogin_error_sn', 0);
                \Phpcmf\Service::C()->session()->set('fclogin_error_time', 0);
            }
            if (IS_API_HTTP) {
                return $this->_json(1, 'ok', $login['data'], true);
            }
            if (!$url) {
                $url = SELF.'?time='.SYS_TIME;
                if (isset($data['mode']) && $data['mode']) {
                    // 模式选择
                    $admin = \Phpcmf\Service::M()->table('admin')->where('uid', $login['code'])->getRow();
                    $setting = (array)dr_string2array($admin['setting']);
                    if ($data['mode'] == 2) {
                        // 简化
                        $setting['admin_min'] = 1;
                        file_put_contents(WRITEPATH.'config/admin.mode', 1);
                    } else {
                        // 完整
                        if (\Phpcmf\Service::M('auth')->is_admin_min_mode()) {
                            $this->_json(0, dr_lang('此账号无法使用完整模式'));
                        }
                        $setting['admin_min'] = 0;
                        @unlink(WRITEPATH.'config/admin.mode');
                    }
                    \Phpcmf\Service::M()->table('admin')->update($login['code'], [
                        'setting' => dr_array2string($setting)
                    ]);
                }
            }
            $url = dr_redirect_safe_check(\Phpcmf\Service::L('input')->xss_clean($url, true));
            // 写入日志
            $this->admin = $login['data'];
            return $this->_json(1, 'ok', ['sync' => [], 'url' => $url], true);
        }

        // 检测登录了就跳转首页
        if ($this->member) {
            $uid = (int)$this->session()->get('uid');
            if ($uid == $this->member['uid']) {
                $rt = \Phpcmf\Service::M('auth')->member($this->member, 1);
                if (!$rt['code']) {
                    $this->_admin_msg(0, $rt['msg']);
                } else {
                    dr_redirect(SELF);exit;
                }
            }
        }

        $license = [];
		if (is_file(MYPATH.'Config/License.php')) {
            $license = require MYPATH.'Config/License.php';
        }

        $name = dr_oauth_list();
        if (dr_is_app('weixin')) {
            $name['wechat'] = [];
        }

        $oauth = [];
        if ($name) {
            $ourl = ADMIN_URL.SELF.'?c=api&m=oauth&is_admin_call=1&name=';
            foreach ($name as $value => $t) {
                if (!isset($this->member_cache['oauth'][$value]['id'])
                    || !$this->member_cache['oauth'][$value]['id']) {
                    continue;
                }
                if (in_array($value, ['weixin', 'wechat'])) {
                    if (dr_is_weixin_app()) {
                        dr_is_app('weixin') && $oauth['wechat'] = [
                            'title' => '微信公众号登录',
                            'name' => 'wechat',
                            'url' => OAUTH_URL.'index.php?s=weixin&c=member&m=login_url&back='.urlencode($ourl.'wechat'),
                        ];
                    } else {
                        $oauth[$value] = [
                            'title' => ($value == 'weixin' ? '微信扫码' : '微信公众号').'登录',
                            'name' => $value,
                            'url' => OAUTH_URL.'index.php?s=api&c=oauth&m=index&name='.$value.'&type=login&back='.urlencode($ourl.$value),
                        ];
                    }
                } else {
                    $oauth[$value] = [
                        'title' => $t['name'].'登录',
                        'name' => $value,
                        'url' => OAUTH_URL.'index.php?s=api&c=oauth&m=index&name='.$value.'&type=login&back='.urlencode($ourl.$value),
                    ];
                }
            }
        }

        $mode = is_file(WRITEPATH.'config/admin.mode') ? 2 : 1;

		\Phpcmf\Service::V()->assign([
		    'mode' => $mode,
			'form' => dr_form_hidden(),
            'oauth' => $oauth,
			'is_sms' => $is_sms,
            'license' => $license,
            'crypto_iv' => substr(md5(SYS_KEY), 10, 16),
            'crypto_key' => substr(md5(SYS_KEY), 0, 16),
            'sms_url' => WEB_DIR.SELF.'?c=login&m=sms',
            'login_url' => WEB_DIR.SELF.'?c=login&go='.urlencode($url).'&'.($is_sms ? 'is_sms=1' : ''),
            'rlogin_url' => WEB_DIR.SELF.'?c=login&go='.urlencode($url),
        ]);
        if (isset($_GET['is_cloud']) && $_GET['is_cloud']) {
            \Phpcmf\Service::V()->display('cloud_login_admin.html');exit;
        } else {
            \Phpcmf\Service::V()->display('login.html');exit;
        }
	}

    /**
     * 发送验证码
     */
    public function sms() {

        if (IS_POST) {
            $data = \Phpcmf\Service::L('input')->post('data');
            if (SYS_ADMIN_CODE && !\Phpcmf\Service::L('form')->check_captcha('code')) {
                $this->_json(0, dr_lang('图片验证码不正确'));
            } elseif (!$data['phone']) {
                $this->_json(0, dr_lang('手机号码未填写'));
            }
            $phone = dr_authcode($data['phone'], 'DECODE');
            if ($phone) {
                $data['phone'] = $phone;
            }

            // 验证操作间隔
            $name = 'admin-login-phone-'.$data['phone'];
            if (\Phpcmf\Service::L('cache')->check_auth_data($name, defined('SYS_CACHE_SMS') && SYS_CACHE_SMS ? SYS_CACHE_SMS : 60)) {
                $this->_json(0, dr_lang('已经发送稍后再试'));
            }

            $randcode = \Phpcmf\Service::L('Form')->get_rand_value();
            $rt = \Phpcmf\Service::M('member')->sendsms_code($data['phone'], $randcode);
            if (!$rt['code']) {
                $this->_json(0, IS_DEV ? $rt['msg'] : dr_lang('发送失败'));
            }

            \Phpcmf\Service::L('cache')->set_auth_data($name, $randcode);

            $this->_json(1, dr_lang('验证码发送成功'));
        } else {
            $this->_json(0, dr_lang('请求方式错误'));
        }
    }

    // 子站客户端自动登录
    public function fclient() {

        $file = ROOTPATH.'api/fclient/login.php';
        if (!is_file($file)) {
            $this->_admin_msg(0, '子站客户端程序'.(IS_DEV ? $file : '').'未安装');
        }

        require $file;
    }

	public function out() {
		$this->session()->remove('uid');
		$this->session()->remove('admin');
		$this->session()->remove('siteid');
		return $this->_json(1, dr_lang('您已经安全退出系统了'), 0, true);
	}

}
