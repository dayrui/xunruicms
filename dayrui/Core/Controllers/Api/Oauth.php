<?php namespace Phpcmf\Controllers\Api;
use function Composer\Autoload\includeFile;

/**
 * http://www.xunruicms.com
 * 本文件是框架系统文件，二次开发时不可以修改本文件
 **/


// 快捷登录接口
class Oauth extends \Phpcmf\Common
{

    /**
     * 快捷登录
     */
    public function index() {

        $name = dr_safe_replace(\Phpcmf\Service::L('input')->get('name'));
        $type = dr_safe_replace(\Phpcmf\Service::L('input')->get('type'));
        $back = dr_safe_replace(\Phpcmf\Service::L('input')->get('back'));
        $action = dr_safe_replace(\Phpcmf\Service::L('input')->get('action'));

        // 非授权登录时必须验证登录状态
        if ($type != 'login' && !$this->uid) {
            $this->_msg(0, dr_lang('你还没有登录'));
        }

        // 请求参数
        $appid = $this->member_cache['oauth'][$name]['id'];
        $appkey = $this->member_cache['oauth'][$name]['value'];
        $callback_url = ROOT_URL.'index.php?s=api&c=oauth&m=index&action=callback&name='.$name.'&type='.$type.'&back='.urlencode($back);

        switch ($name) {

            case 'weixin':

                if ($action == 'callback') {
                    // 表示回调返回
                    if (isset($_REQUEST['code'])) {
                        // 获取access_token
                        $url = 'https://api.weixin.qq.com/sns/oauth2/access_token?appid='.$appid.'&secret='.$appkey.'&code='.$_REQUEST['code'].'&grant_type=authorization_code';
                        $token = json_decode(dr_catcher_data($url), true);
                        if (!$token) {
                            $this->_msg(0, dr_lang('无法获取到远程信息'));
                        } elseif ($token['errmsg']) {
                            $this->_msg(0, $token['errmsg']);
                        }
                        // 获取用户信息
                        $url = 'https://api.weixin.qq.com/sns/userinfo?access_token='.$token['access_token'].'&openid='.$token['openid'];
                        $user = json_decode(dr_catcher_data($url), true);
                        if (!$user) {
                            $this->_msg(0, dr_lang('无法获取到用户信息'));
                        } elseif ($user['errmsg']) {
                            $this->_msg(0, $user['errmsg']);
                        }
                        $rt = \Phpcmf\Service::M('member')->insert_oauth($this->uid, $type, [
                            'oid' => $token['openid'],
                            'oauth' => 'weixin',
                            'avatar' => $user['headimgurl'],
                            'unionid' => (string)$user['unionid'],
                            'nickname' => dr_emoji2html($user['nickname']),
                            'expire_at' => SYS_TIME,
                            'access_token' => 0,
                            'refresh_token' => $token['refresh_token'],
                        ], null,  $back);
                        if (!$rt['code']) {
                            $this->_msg(0, $rt['msg']);exit;
                        } else {
                            dr_redirect($rt['msg']);
                        }
                    } else {
                        $this->_msg(0, dr_lang('回调参数code不存在'));exit;
                    }
                } else {
                    // 跳转授权页面
                    $url = 'https://open.weixin.qq.com/connect/qrconnect?appid='.$appid.'&redirect_uri='.urlencode($callback_url).'&response_type=code&scope=snsapi_login&state=STATE#wechat_redirect';
                    dr_redirect($url);
                }
                break;

            case 'qq':

                define("CLASS_PATH", FCPATH."ThirdParty/Qq/");
                require FCPATH.'ThirdParty/Qq/QC.class.php';
                $qc = new \QC();
                if ($action == 'callback') {
                    // 表示回调返回
                    if (isset($_REQUEST['code'])) {
                        $rt = $qc->qq_callback($appid, $appkey, $callback_url, $_REQUEST['code']);
                        if (is_array($rt)) {
                            // 回调成功
                            $open = $qc->get_openid($rt['access_token']);
                            !is_array($open) && exit($this->_msg(0, $open)); // 获取失败
                            $user = $qc->init($appid, $rt['access_token'], $open['openid'])->get_user_info();
                            if (is_array($user)) {
                                // 入库oauth表
                                $rt = \Phpcmf\Service::M('member')->insert_oauth($this->uid, $type, [
                                    'oid' => $open['openid'],
                                    'oauth' => 'qq',
                                    'avatar' => $user['figureurl_qq_2'] ? $user['figureurl_qq_2'] : $user['figureurl_qq_1'],
                                    'unionid' => (string)$user['unionid'],
                                    'nickname' => dr_emoji2html($user['nickname']),
                                    'expire_at' => SYS_TIME,
                                    'access_token' => 0,
                                    'refresh_token' => $rt['refresh_token'],
                                ], null,  $back);
                                if (!$rt['code']) {
                                    $this->_msg(0, $rt['msg']);exit;
                                } else {
                                    dr_redirect($rt['msg']);
                                }
                            } else {
                                $this->_msg(0, dr_lang('获取QQ用户信息失败: '.$user));exit;
                            }
                        } else {
                            $this->_msg(0, $rt);exit;
                        }
                    } else {
                        $this->_msg(0, dr_lang('回调参数code不存在'));exit;
                    }
                } else {
                    // 跳转授权页面
                    $rt = $qc->qq_login($appid, $callback_url);
                    if (!$rt) {
                        $this->_msg(0, dr_lang('授权执行失败'));
                    };
                }
                break;


            case 'weibo':

                define("WB_AKEY", $appid);
                define("WB_SKEY", $appkey);
                require FCPATH.'ThirdParty/Weibo/saetv2.ex.class.php';
                $o = new \SaeTOAuthV2(WB_AKEY, WB_SKEY);
                if ($action == 'callback') {
                    // 表示回调返回
                    if (isset($_REQUEST['code'])) {
                        $keys = [];
                        $keys['code'] = $_REQUEST['code'];
                        $keys['redirect_uri'] = $callback_url;
                        $token = $o->getAccessToken('code', $keys);
                        if (is_array($token)) {
                            // 回调成功
                            $c = new \SaeTClientV2(WB_AKEY, WB_SKEY, $token['access_token']);
                            $user = $c->show_user_by_id($token['uid']); //根据ID获取用户等基本信息
                            if ($user) {
                                // 入库oauth表
                                $rt = \Phpcmf\Service::M('member')->insert_oauth($this->uid, $type, [
                                    'oid' => $token['uid'],
                                    'oauth' => 'weibo',
                                    'avatar' => $user['avatar_large'] ? $user['avatar_large'] : $user['profile_image_url'],
                                    'unionid' => '',
                                    'nickname' => dr_emoji2html($user['name']),
                                    'expire_at' => SYS_TIME,
                                    'access_token' => 0,
                                    'refresh_token' => '',
                                ], null, $back);
                                if (!$rt['code']) {
                                    $this->_msg(0, $rt['msg']);exit;
                                } else {
                                    dr_redirect($rt['msg']);
                                }
                            } else {
                                $this->_msg(0, dr_lang('获取微博用户信息失败'));exit;
                            }
                        } else {
                            // 回调失败
                            $this->_msg(0, $token);exit;
                        }
                    } else {
                        $this->_msg(0, dr_lang('回调参数code不存在'));exit;
                    }
                } else {
                    // 跳转授权页面
                    dr_redirect($o->getAuthorizeURL($callback_url));
                }
                break;

            case 'wechat':
                // 微信公众号
                if (!dr_is_app('weixin')) {
                    $this->_msg(0, dr_lang('没有安装微信应用插件'));
                }
                //
                \Phpcmf\Service::C()->init_file('weixin');
                $rt = \Phpcmf\Service::M('user', 'weixin')->qrcode_bang($this->member);
                if (!$rt['code']) {
                    $this->_msg(0, $rt['msg']);
                } else {
                    // 获取返回页面
                    $url = \Phpcmf\Service::L('Security')->xss_clean($back ? urldecode($back) : $_SERVER['HTTP_REFERER']);
                    if (!$url || strpos($url, 'login') !== false ) {
                        $url = $this->uid ? dr_member_url('account/oauth') : dr_member_url('home/index');
                    }
                    // 是否在公众号内部
                    if (dr_is_weixin_app()) {
                        # 公众号内部请求登录

                        if ($action == 'callback') {

                            $code = \Phpcmf\Service::L('input')->get('code');
                            $state = \Phpcmf\Service::L('input')->get('state');

                            $url = 'https://api.weixin.qq.com/sns/oauth2/access_token?appid='.$this->weixin['account']['appid'].'&secret='.$this->weixin['account']['appsecret'].'&code='.$code.'&state='.$state.'&grant_type=authorization_code';
                            $rt = wx_get_https_json_data($url);
                            if (!$rt['code']) {
                                $this->_msg(0, $rt['msg']);
                            }

                            $user = \Phpcmf\Service::M()->table(weixin_wxtable('user'))->where('openid', $rt['data']['openid'])->getRow();
                            if (!$user) {
                                // 刷新
                                $rs = wx_get_https_json_data('https://api.weixin.qq.com/sns/oauth2/refresh_token?appid='.$this->weixin['account']['appid'].'&grant_type=refresh_token&refresh_token='.$rt['data']['refresh_token']);
                                if (!$rs['code']) {
                                    $this->_msg(0, $rs['msg']);
                                }

                                $rts = wx_get_https_json_data('https://api.weixin.qq.com/sns/userinfo?access_token='.$rs['data']['access_token'].'&openid='.$rt['data']['openid'].'&lang=zh_CN');
                                if (!$rts['code']) {
                                    $this->_msg(0, $rts['msg']);
                                } elseif (!$rts['data']['nickname']) {
                                    $this->_msg(0, '未获取到微信用户昵称');
                                }
                                $user = $rts['data'];
                                $user['id'] = \Phpcmf\Service::M('user', 'weixin')->insert_user($rts['data']);
                            }

                            $oid = $rt['data']['openid'];
                            $rt = \Phpcmf\Service::M('member')->insert_oauth($this->uid, 'login', [
                                'oid' => $oid,
                                'oauth' => 'wechat',
                                'avatar' => $user['headimgurl'],
                                'unionid' => (string)$user['unionid'],
                                'nickname' => dr_emoji2html($user['nickname']),
                                'expire_at' => SYS_TIME,
                                'access_token' => 0,
                                'refresh_token' => 0,
                            ], $state);
                            if (!$rt['code']) {
                                $this->_msg(0, $rt['msg']);
                                exit;
                            } else {
                                if ($user['uid'] && $state) {
                                    // 存储cookie
                                    $member = \Phpcmf\Service::M('member')->member_info($user['uid']);
                                    if (!$member) {
                                        \Phpcmf\Service::M()->db->table('weixin_user')->where('uid', $user['uid'])->delete();
                                        dr_redirect($rt['msg']);
                                        exit;
                                    }
                                    \Phpcmf\Service::M('member')->save_cookie($member, 1);
                                    $goto_url = urldecode($state);
                                    if (strpos($state, DOMAIN_NAME) === false) {
                                        // 域名不同的情况下
                                        $rt = \Phpcmf\Service::M('member')->sso($member, 1);
                                        $sso = '';
                                        foreach ($rt as $url) {
                                            $sso.= '<script src="'.$url.'"></script>';
                                        }
                                        $this->_msg(1, dr_lang('%s，欢迎回来', dr_html2emoji($user['nickname'])).$sso, $goto_url, 0);exit;
                                    }
                                    dr_redirect($goto_url);
                                } else {
                                    dr_redirect($rt['msg']);
                                }
                            }


                        } else {
                            dr_redirect('https://open.weixin.qq.com/connect/oauth2/authorize?appid='.$this->weixin['account']['appid'].'&redirect_uri='.urlencode($callback_url).'&response_type=code&scope=snsapi_userinfo&state='.urlencode($url).'#wechat_redirect');
                        }

                    } else {
                        $notify_url = '/index.php?s=api&c=oauth&m=wxbang&ep='.$rt['data']['action_info']['scene']['scene_str'];
                        if (strpos($url, 'is_admin_call')) {
                            // 后台会话
                            $notify_url.= '&is_admin_call='.urlencode($url);
                        }

                        \Phpcmf\Service::V()->admin();
                        \Phpcmf\Service::V()->assign([
                            'back_url' => $url,
                            'qrcode_url' => $rt['msg'],
                            'notify_url' => $notify_url,
                        ]);
                        \Phpcmf\Service::V()->display('api_weixin_bang.html');
                    }
                }
                // 跳转微信公众号
                break;

            default:
                exit('未定义的接口');

        }
    }

    // 微信公众号绑定
    public function wxbang() {

        if (!$this->uid) {
            $ep = dr_safe_replace($_GET['ep']);
            $rt = \Phpcmf\Service::M()->table('member_oauth')->where('refresh_token', $ep)->where('uid>0')->where('oauth', 'wechat')->getRow();
            if ($rt) {
                // 为他登录
                // 存储cookie
                $this->uid = $rt['uid'];
                $this->member = \Phpcmf\Service::M('member')->get_member($this->uid);
                \Phpcmf\Service::M('member')->save_cookie($this->member);
                // 存储后台回话
                if (isset($_GET['is_admin_call'])) {
                    \Phpcmf\Service::M('auth')->save_login_auth('wechat', $this->uid);
                    $this->_json(1, 'ok', [
                        'url' => urldecode($_GET['is_admin_call']).'&uid='.$this->uid,
                        'sso' => \Phpcmf\Service::M('member')->sso($this->member, 1),
                    ]);
                } else {
                    $this->_json(1, 'ok', [
                        'sso' => \Phpcmf\Service::M('member')->sso($this->member, 1),
                    ]);
                }

            }
        } else {
            $rt = \Phpcmf\Service::M()->table('member_oauth')->where('uid', $this->uid)->where('oauth', 'wechat')->getRow();
        }

        if (!$rt) {
            $this->_json(0, '');
        } else {
            // 绑定成功更新头像
            list($cache_path) = dr_avatar_path();
            if (!is_file($cache_path.$this->uid.'.jpg')) {
                // 没有头像下载头像
                $img = dr_catcher_data($rt['avatar']);
                if (strlen($img) > 20) {
                    @file_put_contents($cache_path.$this->uid.'.jpg', $img);
                }
            }
            // 存储后台回话
            if (isset($_GET['is_admin_call'])) {
                \Phpcmf\Service::M('auth')->save_login_auth('wechat', $this->uid);
                $this->_json($rt['access_token'] ? 0 : 1, 'ok', [
                    'url' => urldecode($_GET['is_admin_call']).'&uid='.$this->uid,
                    'sso' => \Phpcmf\Service::M('member')->sso($this->member, 1),
                ]);
            } else {
                $this->_json($rt['access_token'] ? 0 : 1, 'ok', $rt);
            }
        }
    }

    // 微信公众号登录
    public function wxmp() {

        exit;
    }

    // 微信小程序登录
    public function xcx() {


    }
}
