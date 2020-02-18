<?php namespace Phpcmf\Controllers\Admin;

/**
 * http://www.xunruicms.com
 * 本文件是框架系统文件，二次开发时不可以修改本文件
 **/


class Login extends \Phpcmf\Common
{

	public function index() {

		$url = pathinfo(\Phpcmf\Service::L('input')->get('go') ? urldecode(\Phpcmf\Service::L('input')->get('go')) :\Phpcmf\Service::L('Router')->url('home'));
		$url = $url['basename'] ? $url['basename'] :\Phpcmf\Service::L('Router')->url('home/index');

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
                $this->_json(0, dr_lang('失败次数已达到%s次，已被禁止登录', SYS_ADMIN_LOGINS));
			} elseif (empty($data['username']) || empty($data['password'])) {
				$this->_json(0, dr_lang('账号或密码必须填写'));
			} else {
				$login = \Phpcmf\Service::M('auth')->login($data['username'], $data['password']);
                $this->admin['uid'] = 0;
                $this->admin['username'] = $data['username'];
                if ($login['code']) {
                    // 登录成功
                    $sync = [];
                    // 写入日志
                    \Phpcmf\Service::L('input')->system_log('登录后台成功', 1);
					if ($sn) {
						// 解除禁止登陆
						\Phpcmf\Service::C()->session()->set('fclogin_error_sn', 0);
						\Phpcmf\Service::C()->session()->set('fclogin_error_time', 0);
					}
                    $this->_json(1, 'ok', ['sync' => $sync, 'url' => \Phpcmf\Service::L('input')->xss_clean($url)]);
                } else {
                    // 登录失败
                    if (defined('SYS_ADMIN_LOGINS') && SYS_ADMIN_LOGINS) {
                        \Phpcmf\Service::C()->session()->set('fclogin_error_sn', intval($sn) + 1);
                        \Phpcmf\Service::C()->session()->set('fclogin_error_time', SYS_TIME);
                    }
                    // 写入日志
                    \Phpcmf\Service::L('input')->system_log($login['msg'].'（密码'.$data['password'].'）', 1);
                    $this->_json(0, $login['msg']);
                }
			}
		}

        $license = [];
		if (is_file(MYPATH.'Config/License.php')) {
            $license = require MYPATH.'Config/License.php';
        }

        $url = ADMIN_URL.SELF.'?c=api&m=oauth&is_admin_call=1&name=';
        $name = ['qq', 'weixin', 'weibo', 'wechat'];
        $oauth = [];
        foreach ($name as $key => $value) {
            if (!isset($this->member_cache['oauth'][$value]['id'])
                || !$this->member_cache['oauth'][$value]['id']) {
                continue;
            }
            if ($value == 'wechat' && !dr_is_app('weixin')) {
                continue;
            }
            if (in_array($value, ['weixin', 'wechat'])) {
                if (dr_is_weixin_app()) {
                    dr_is_app('weixin') && $oauth['wechat'] = [
                        'name' => 'wechat',
                        'url' => ROOT_URL . 'index.php?s=weixin&c=member&m=login_url&back='.urlencode($url.'wechat'),
                    ];
                } else {
                    $oauth[$value] = [
                        'name' => $value,
                        'url' => ROOT_URL . 'index.php?s=api&c=oauth&m=index&name=' . $value . '&type=login&back='.urlencode($url.$value),
                    ];
                }
            } else {
                $oauth[$value] = [
                    'name' => $value,
                    'url' => ROOT_URL . 'index.php?s=api&c=oauth&m=index&name=' . $value . '&type=login&back='.urlencode($url.$value),
                ];
            }
        }

		\Phpcmf\Service::V()->assign(array(
			'form' => dr_form_hidden(),
            'oauth' => $oauth,
			'license' => $license,
		));
		\Phpcmf\Service::V()->display('login.html');exit;
	}

    // 子站客户端自动登录
    public function fclient() {

	    if (!is_file(ROOTPATH.'api/fclient/sync.php')) {
            $this->_admin_msg(0, '子站客户端程序未安装');
        }

        $sync = require ROOTPATH.'api/fclient/sync.php';
        if (!$_GET['id'] || !$_GET['sync']) {
            $this->_admin_msg(0, '通信密钥验证为空');
        } elseif ($_GET['id'] != md5($sync['id'])) {
            $this->_admin_msg(0, '通信ID验证失败');
        } elseif ($_GET['sync'] != $sync['sn']) {
            $this->_admin_msg(0, '通信密钥验证失败');
        }

        $prefix = \Phpcmf\Service::M()->dbprefix('');
        $member = \Phpcmf\Service::M()->db->query('select * from '.$prefix.'member where id in(select uid from '.$prefix.'admin_role_index order by roleid asc) order by id asc limit 1')->getRowArray();
        if (!$member) {
            $this->_admin_msg(0, '没有找到本站管理员账号', SELF);
        }

        \Phpcmf\Service::M('auth')->login_session($member);

        $this->_admin_msg(1, '授权登录成功', SELF);
    }

	public function out() {
		$this->session()->remove('uid');
		$this->session()->remove('admin');
		$this->session()->remove('siteid');
		$this->_json(1, dr_lang('您已经安全退出系统了'));
	}

}
