<?php namespace Phpcmf\Control\Api;
/**
 * {{www.xunruicms.com}}
 * {{迅睿内容管理框架系统}}
 * 本文件是框架系统文件，二次开发时不可以修改本文件
 **/

// 同步登陆接口
class Sso extends \Phpcmf\Common {

	public function index() {

        switch (\Phpcmf\Service::L('input')->get('action')) {

            case 'logout': // 前台退出登录

                header('P3P: CP="CURa ADMa DEVa PSAo PSDo OUR BUS UNI PUR INT DEM STA PRE COM NAV OTC NOI DSP COR"');
                \Phpcmf\Service::L('input')->set_cookie('member_uid', 0, -30000);
                \Phpcmf\Service::L('input')->set_cookie('admin_login_member', 0, -30000);
                \Phpcmf\Service::L('input')->set_cookie('member_cookie', 0, -30000);
                break;

            case 'login': // 前台同步登录

                $code = dr_authcode(\Phpcmf\Service::L('input')->get('code'), 'DECODE');
                if (!$code) {
                    $this->_jsonp(0, '解密失败');
                }

                list($uid, $salt) = explode('-', $code);
                if (!$uid || !$salt) {
                    $this->_jsonp(0, '格式错误');
                }

                $member = \Phpcmf\Service::M()->db->table('member')->where('id', $uid)->get()->getRowArray();
                if (!$member) {
                    $this->_jsonp(0, '账号不存在');
                } elseif ($salt != $member['salt']) {
                    $this->_jsonp(0, '账号验证失败');
                }

                header('P3P: CP="CURa ADMa DEVa PSAo PSDo OUR BUS UNI PUR INT DEM STA PRE COM NAV OTC NOI DSP COR"');
                \Phpcmf\Service::M('member')->save_cookie($member, \Phpcmf\Service::L('input')->get('remember'));

                break;

            case 'alogin': // 后台登录授权

                $code = dr_authcode(\Phpcmf\Service::L('input')->get('code'), 'DECODE');
                if (!$code) {
                    $this->_jsonp(0, '解密失败');
                }

                list($uid, $password) = explode('-', $code);

                $admin = \Phpcmf\Service::L('cache')->get_data('admin_login_member');
                if (!$admin) {
                    $this->_jsonp(0, '缓存失败');
                } elseif ($password != md5($admin['id'].$admin['password'])) {
                    $this->_jsonp(0, '验证失败');
                }

                // 存储状态
                header('P3P: CP="CURa ADMa DEVa PSAo PSDo OUR BUS UNI PUR INT DEM STA PRE COM NAV OTC NOI DSP COR"');
                \Phpcmf\Service::L('input')->set_cookie('admin_login_member', $uid.'-'.$admin['id'], 3600);
                $this->session()->set('admin_login_member_code', md5($uid.$admin['id'].$admin['password']));
                break;

            case 'slogin': // 后台登录其他站点

                exit('功能废弃');
                break;

        }

        return $this->_jsonp(1, 'ok', [], true);
	}

    public function login() {
        if (!IS_USE_MEMBER) {
            $this->_json(0, '没有安装用户系统插件');
        }
        require IS_USE_MEMBER.'Controllers/Login.php';
        $ci = new \Phpcmf\Controllers\Login($this);
        $ci->index();
    }

    public function sms() {
        if (!IS_USE_MEMBER) {
            $this->_json(0, '没有安装用户系统插件');
        }
        require IS_USE_MEMBER.'Controllers/Login.php';
        $ci = new \Phpcmf\Controllers\Login($this);
        $ci->sms();
    }

    public function oauth() {
        if (!IS_USE_MEMBER) {
            $this->_json(0, '没有安装用户系统插件');
        }
        require IS_USE_MEMBER.'Controllers/Login.php';
        $ci = new \Phpcmf\Controllers\Login($this);
        $ci->oauth();
    }

    public function register() {
        if (!IS_USE_MEMBER) {
            $this->_json(0, '没有安装用户系统插件');
        }
        require IS_USE_MEMBER.'Controllers/Register.php';
        $ci = new \Phpcmf\Controllers\Register($this);
        $ci->index();
    }

}
