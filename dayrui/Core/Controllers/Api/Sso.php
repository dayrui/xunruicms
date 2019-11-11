<?php namespace Phpcmf\Controllers\Api;

/**
 * http://www.xunruicms.com
 * 本文件是框架系统文件，二次开发时不可以修改本文件
 **/

// 同步登陆接口
class Sso extends \Phpcmf\Common
{

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

                $member = \Phpcmf\Service::M()->db->table('member')->select('password,salt')->where('id', $uid)->get()->getRowArray();
                if (!$member) {
                    $this->_jsonp(0, '账号不存在');
                } elseif ($salt != $member['salt']) {
                    $this->_jsonp(0, '账号验证失败');
                }

                header('P3P: CP="CURa ADMa DEVa PSAo PSDo OUR BUS UNI PUR INT DEM STA PRE COM NAV OTC NOI DSP COR"');
                $expire = \Phpcmf\Service::L('input')->get('remember') ? 8640000 : SITE_LOGIN_TIME;
                \Phpcmf\Service::L('input')->set_cookie('member_uid', $uid, $expire);
                \Phpcmf\Service::L('input')->set_cookie('member_cookie', substr(md5(SYS_KEY.$member['password']), 5, 20), $expire);

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
                $this->session()->setTempdata('admin_login_member_code', md5($uid.$admin['id'].$admin['password']), 3600);
                break;

            case 'slogin': // 后台登录其他站点

                $code = dr_authcode(\Phpcmf\Service::L('input')->get('code'), 'DECODE');
                if (!$code) {
                    $this->_jsonp(0, '解密失败');
                }

                list($uid, $password) = explode('-', $code);

                $admin = \Phpcmf\Service::L('cache')->init('', 'site')->get('admin_login_site');
                if (!$admin) {
                    $this->_jsonp(0, '缓存失败');
                } elseif ($password != md5($admin['uid'].$admin['password']) ) {
                    $this->_jsonp(0, '验证失败');
                }

                // 存储状态
                header('P3P: CP="CURa ADMa DEVa PSAo PSDo OUR BUS UNI PUR INT DEM STA PRE COM NAV OTC NOI DSP COR"');
                \Phpcmf\Service::L('input')->set_cookie('admin_login_member', 0, -3600);
                $this->session()->set('admin_login_member_code', 0, -3600);
                $this->session()->set('uid', $uid);
                $this->session()->set('admin', $uid);
                \Phpcmf\Service::L('input')->set_cookie('member_uid', $uid, SITE_LOGIN_TIME);
                \Phpcmf\Service::L('input')->set_cookie('member_cookie', substr(md5(SYS_KEY . $admin['password']), 5, 20), SITE_LOGIN_TIME);
                break;

        }

        $this->_jsonp(1, 'ok');

		exit;
	}

}
