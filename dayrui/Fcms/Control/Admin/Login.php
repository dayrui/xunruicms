<?php namespace Phpcmf\Control\Admin;
use Phpcmf\Service;

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

		if (IS_AJAX_POST) {
            $sn = 0;
            // 回调钩子
            $data = \Phpcmf\Service::L('input')->post('data');
            \Phpcmf\Hooks::trigger('admin_login_before', $data);
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
                $data['username'] = trim($data['username']);
				$login = \Phpcmf\Service::M('auth')->login($data['username'], $data['password']);
                if (isset($this->admin) && is_array($this->admin)) {
                    $this->admin['uid'] = 0;
                    $this->admin['username'] = $data['username'];
                }
                if ($login['code']) {
                    // 登录成功
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
			'license' => $license,
            'crypto_key' => substr(md5(SYS_KEY), 0, 16),
            'crypto_iv' => substr(md5(SYS_KEY), 10, 16),
            'login_url' => '/'.SELF.'?c=login&go='.urlencode($url),
        ]);
        if (isset($_GET['is_cloud']) && $_GET['is_cloud']) {
            \Phpcmf\Service::V()->display('cloud_login_admin.html');exit;
        } else {
            \Phpcmf\Service::V()->display('login.html');exit;
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
