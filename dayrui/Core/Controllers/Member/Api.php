<?php namespace Phpcmf\Controllers\Member;

/**
 * http://www.xunruicms.com
 * 本文件是框架系统文件，二次开发时不可以修改本文件
 **/


// 用户api
class Api extends \Phpcmf\Common
{

    /**
     * 头像更新
     */
    public function avatar() {
        \Phpcmf\Service::L('Thread')->cron(['action' => 'oauth_down_avatar', 'id' => intval(\Phpcmf\Service::L('input')->get('id')) ]);
        exit;
    }

    /**
     * 审核
     */
    public function verify() {

        if (!$this->member) {
            $this->_msg(0, dr_lang('账号未登录'));
        }

        // 获取返回页面
        $url = $_SERVER['HTTP_REFERER'];
        (strpos($url, 'verify') !== false || !$url) && $url = MEMBER_URL;

        if ($this->member['is_verify']) {
            if (IS_API_HTTP) {
                $this->_json(0, dr_lang('此用户已经通过审核了'));
            }
            dr_redirect($url);
            exit;
        } elseif (!$this->member['is_verify'] && !$this->member_cache['register']['verify']) {
            // 如果系统已经关闭了审核机制就自动通过
            \Phpcmf\Service::M('member')->verify_member($this->member['uid']);
            if (IS_API_HTTP) {
                $this->_json(0, dr_lang('系统已经关闭了审核机制'));
            }
            dr_redirect($url);
            exit;
        } elseif ($this->member_cache['register']['verify'] == 'admin') {
            $this->_msg(0, dr_lang('请等待管理员的审核'));
        } elseif (!$this->member['email'] && !$this->member['phone']) {
            // 手机邮箱都为空，表示从第三方登录注册的
            $this->change();exit;
        }

        \Phpcmf\Service::V()->assign([
            'post_url' => dr_member_url('api/verify_code', ['back' => urlencode($url)]),
            'meta_title' => dr_lang('账号审核'),
            'verify_url' => dr_member_url('api/'.$this->member_cache['register']['verify'].'_verify_code'),
            'verify_name' => $this->member[$this->member_cache['register']['verify']],
            'verify_type' => $this->member_cache['register']['verify'],
        ]);
        \Phpcmf\Service::V()->display('verify.html');
    }

    /**
     * 审核时变更邮箱或者手机
     */
    public function change() {

        if (!$this->member) {
            $this->_msg(0, dr_lang('账号未登录'));
        } elseif ($this->member['is_verify']) {
            if (IS_API_HTTP) {
                $this->_json(0, dr_lang('此用户已经通过审核了'));
            }
            dr_redirect(MEMBER_URL);
            exit;
        } elseif (!$this->member['is_verify'] && !$this->member_cache['register']['verify']) {
            // 如果系统已经关闭了审核机制就自动通过
            \Phpcmf\Service::M('member')->verify_member($this->member['uid']);
            if (IS_API_HTTP) {
                $this->_json(0, dr_lang('系统已经关闭了审核机制'));
            }
            dr_redirect(MEMBER_URL);
            exit;
        } elseif ($this->member_cache['register']['verify'] == 'admin') {
            $this->_msg(0, dr_lang('请等待管理员的审核'));
        }

        if (IS_POST) {
            $value = dr_safe_replace(\Phpcmf\Service::L('input')->post('value'));
            if (!\Phpcmf\Service::L('Form')->check_captcha('code')) {
                $this->_json(0, dr_lang('图片验证码不正确'), ['field' => 'code']);
            } elseif (empty($value)) {
                $this->_json(0, dr_lang('新邮箱或者手机号码必须填写'));
            } elseif ($this->member_cache['register']['verify'] == 'email' && !\Phpcmf\Service::L('Form')->check_email($value)) {
                $this->_json(0, dr_lang('邮箱格式不正确'), ['field' => 'value']);
            } elseif ($this->member_cache['register']['verify'] == 'phone' && !\Phpcmf\Service::L('Form')->check_phone($value)) {
                $this->_json(0, dr_lang('手机号码格式不正确'), ['field' => 'value']);
            } elseif ($this->member_cache['register']['verify'] == 'email' && \Phpcmf\Service::M()->db->table('member')->where('email', $value)->countAllResults()) {
                $this->_json(0, dr_lang('邮箱%s已经注册',$value), ['field' => 'value']);
            } elseif ($this->member_cache['register']['verify'] == 'phone' && \Phpcmf\Service::M()->db->table('member')->where('phone', $value)->countAllResults()) {
                $this->_json(0, dr_lang('手机号码%s已经注册', $value), ['field' => 'value']);
            }

            $this->member['randcode'] = rand(100000, 999999);
            \Phpcmf\Service::M()->db->table('member')->where('id', $this->uid)->update([
                'randcode' => $this->member['randcode'],
                $this->member_cache['register']['verify'] => $value
            ]);

            switch ($this->member_cache['register']['verify']) {

                case 'phone':
                    \Phpcmf\Service::M('member')->sendsms_code($value, $this->member['randcode']);
                    break;

                case 'email':
                    \Phpcmf\Service::M('member')->sendmail($value, dr_lang('注册邮件验证'), 'member_verify.html', $this->member);
                    break;
            }

            $this->_json(1, dr_lang('信息已经变更'));
        }

        \Phpcmf\Service::V()->assign([
            'post_url' => dr_member_url('api/verify_code'),
            'meta_title' => dr_lang('账号审核信息变更'),
            'verify_name' => $this->member[$this->member_cache['register']['verify']],
            'verify_type' => $this->member_cache['register']['verify'],
        ]);
        \Phpcmf\Service::V()->display('change.html');
    }

    /**
     * 邮件审核验证码
     */
    public function email_verify_code() {

        if (!$this->member) {
            $this->_msg(0, dr_lang('账号未登录'));
        } elseif ($this->member['is_verify']) {
            $this->_msg(0, dr_lang('此用户已经通过审核了'));
        } elseif ($this->member_cache['register']['verify'] != 'email') {
            $this->_msg(0, dr_lang('审核方式不正确'));
        }

        // 验证操作间隔
        $name = 'member-verify-email-'.$this->uid;
        if (\Phpcmf\Service::L('cache')->get_data($name)) {
            $this->_json(0, dr_lang('已经发送稍后再试'));
        }

        $this->member['randcode'] = rand(100000, 999999);
        \Phpcmf\Service::M()->db->table('member')->where('id', $this->member['id'])->update(['randcode' => $this->member['randcode']]);
        $rt = \Phpcmf\Service::M('member')->sendmail($this->member['email'], dr_lang('注册邮件验证'), 'member_verify.html', $this->member);
        if (!$rt['code']) {
            $this->_json(0, dr_lang('邮件发送失败'));
        }

        \Phpcmf\Service::L('cache')->set_data($name, $this->member['randcode'], 60);
		
        $this->_json(1, dr_lang('验证码发送成功'));
    }

    /**
     * 短信审核验证码
     */
    public function phone_verify_code() {

        if (!$this->member) {
            $this->_json(0, dr_lang('账号未登录'));
        } elseif ($this->member['is_verify']) {
            $this->_msg(0, dr_lang('此用户已经通过审核了'));
        } elseif ($this->member_cache['register']['verify'] != 'phone') {
            $this->_msg(0, dr_lang('审核方式不正确'));
        }

        // 验证操作间隔
        $name = 'member-verify-phone-'.$this->uid;
        if (\Phpcmf\Service::L('cache')->get_data($name)) {
			$this->_json(0, dr_lang('已经发送稍后再试'));
		} 

        $this->member['randcode'] = rand(100000, 999999);
        \Phpcmf\Service::M()->db->table('member')->where('id', $this->member['id'])->update(['randcode' => $this->member['randcode']]);
        $rt = \Phpcmf\Service::M('member')->sendsms_code($this->member['phone'], $this->member['randcode']);
        if (!$rt['code']) {
            $this->_json(0, dr_lang('发送失败'));
        }

        \Phpcmf\Service::L('cache')->set_data($name, $this->member['randcode'], defined('SYS_CACHE_SMS') && SYS_CACHE_SMS ? SYS_CACHE_SMS : 60);
		
        $this->_json(1, dr_lang('验证码发送成功'));
    }


    /**
     * 验证审核验证码
     */
    public function verify_code() {

        // 获取返回页面
        $url = $_GET['back'] ? urldecode($_GET['back']) : $_SERVER['HTTP_REFERER'];
        strpos($url, 'login') !== false && $url = MEMBER_URL;

        // 挂钩点 短信验证之前
        \Phpcmf\Hooks::trigger('member_verify_before', $this->member);

        if (!$this->member) {
            $this->_json(0, dr_lang('账号未登录'));
        } elseif ($this->member['is_verify']) {
            $this->_json(1, dr_lang('已经验证成功'), ['url' => $url ? $url : MEMBER_URL]);
        } elseif (!$this->member['randcode']) {
            $this->_json(0, dr_lang('验证码已过期'));
        } elseif (\Phpcmf\Service::L('input')->get('code') != $this->member['randcode']) {
            $this->_json(0, dr_lang('验证码不正确'));
        }

        \Phpcmf\Service::M()->db->table('member')->where('id', $this->member['id'])->update(['randcode' => 0]);

        \Phpcmf\Service::M('member')->verify_member($this->member['id']);
        if ($this->member_cache['register']['verify'] == 'phone') {
            \Phpcmf\Service::M()->db->table('member_data')->where('id', $this->member['uid'])->update(['is_mobile' => 1]);
        }

        $this->_json(1, dr_lang('验证成功'), ['url' => $url ? $url : MEMBER_URL]);
    }

    /**
     * 找回密码验证码
     */
    public function find_code() {

		$code = dr_safe_replace(\Phpcmf\Service::L('input')->get('code'));
        $value = dr_safe_replace(\Phpcmf\Service::L('input')->get('value'));
        if (!$value) {
            $this->_json(0, dr_lang('账号凭证不能为空'), ['field' => 'value']);
        } elseif (!$code) {
            $this->_json(0, dr_lang('图片验证码未填写'), ['field' => 'code']);
        } elseif (!\Phpcmf\Service::L('Form')->check_captcha_value($code)) {
            $this->_json(0, dr_lang('图片验证码不正确'), ['field' => 'code']);
        }

        // 验证操作间隔
        $name = 'member-find-password-'.$value;
        if (\Phpcmf\Service::L('cache')->get_data($name)) {
			$this->_json(0, dr_lang('已经发送稍后再试'));
		} 

        if (strpos($value, '@') !== false) {
            // 邮箱模式
            $data = \Phpcmf\Service::M()->db->table('member')->where('email', $value)->get()->getRowArray();
            if (!$data) {
                $this->_json(0, dr_lang('账号凭证不存在'), ['field' => 'value']);
            }
            $data['randcode'] = $rand = rand(100000, 999999);
            \Phpcmf\Service::M()->db->table('member')->where('id', $data['id'])->update(['randcode' => $rand]);
            $rt = \Phpcmf\Service::M('member')->sendmail($value, dr_lang('找回密码'), 'member_find.html', $data);
            if (!$rt['code']) {
                $this->_json(0, dr_lang('邮件发送失败'));
            }
        } else if (is_numeric($value) && strlen($value) == 11) {
            // 手机
            $data = \Phpcmf\Service::M()->db->table('member')->where('phone', $value)->get()->getRowArray();
            if (!$data) {
                $this->_json(0, dr_lang('账号凭证不存在'), ['field' => 'value']);
            }
            $rand = rand(100000, 999999);
            \Phpcmf\Service::M()->db->table('member')->where('id', $data['id'])->update(['randcode' => $rand]);
            $rt = \Phpcmf\Service::M('member')->sendsms_code($value, $rand);
            if (!$rt['code']) {
                $this->_json(0, dr_lang('发送失败'));
            }
        } else {
            $this->_json(0, dr_lang('账号凭证格式不正确'), ['field' => 'value']);
        }

        \Phpcmf\Service::L('cache')->set_data($name, $this->member['randcode'], defined('SYS_CACHE_SMS') && SYS_CACHE_SMS ? SYS_CACHE_SMS : 60);
		
        $this->_json(1, dr_lang('验证码发送成功'));
    }

    /**
     * 注册验证码
     */
    public function register_code() {

		$code = dr_safe_replace(\Phpcmf\Service::L('input')->get('code'));
        $phone = dr_safe_replace(\Phpcmf\Service::L('input')->get('id'));
        if (!$phone) {
            $this->_json(0, dr_lang('手机号码未填写'), ['field' => 'phone']);
        } elseif (!\Phpcmf\Service::L('Form')->check_phone($phone)) {
            $this->_json(0, dr_lang('手机号码格式不正确'), ['field' => 'phone']);
        } elseif (!$code) {
            $this->_json(0, dr_lang('图片验证码未填写'), ['field' => 'code']);
        } elseif (!\Phpcmf\Service::L('Form')->check_captcha_value($code)) {
            $this->_json(0, dr_lang('图片验证码不正确'), ['field' => 'code']);
        } elseif (\Phpcmf\Service::M()->db->table('member')->where('phone', $phone)->countAllResults()) {
            $this->_json(0, dr_lang('手机号码已经注册'), ['field' => 'phone']);
        } elseif (\Phpcmf\Service::L('Form')->get_mobile_code($phone)) {
			$this->_json(0, dr_lang('已经发送稍后再试')); // 验证操作间隔
		} 

        $code = rand(100000, 999999);
        $rt = \Phpcmf\Service::M('member')->sendsms_code($phone, $code);
        if (!$rt['code']) {
            $this->_json(0, dr_lang('发送失败'));
        }

		\Phpcmf\Service::L('Form')->set_mobile_code($phone, $code);
		
        $this->_json(1, dr_lang('验证码发送成功'));
    }

    /**
     * 登录验证码
     */
    public function login_code() {

        $code = dr_safe_replace(\Phpcmf\Service::L('input')->get('code'));
        $phone = dr_safe_replace(\Phpcmf\Service::L('input')->get('id'));
        if (!$phone) {
            $this->_json(0, dr_lang('手机号码未填写'), ['field' => 'phone']);
        } elseif (!\Phpcmf\Service::L('Form')->check_phone($phone)) {
            $this->_json(0, dr_lang('手机号码格式不正确'), ['field' => 'phone']);
        } elseif (!$code) {
            $this->_json(0, dr_lang('图片验证码未填写'), ['field' => 'code']);
        } elseif (!\Phpcmf\Service::L('Form')->check_captcha_value($code)) {
            $this->_json(0, dr_lang('图片验证码不正确'), ['field' => 'code']);
        } elseif (!\Phpcmf\Service::M()->db->table('member')->where('phone', $phone)->countAllResults()) {
            $this->_json(0, dr_lang('手机号码未注册'), ['field' => 'phone']);
        } elseif (\Phpcmf\Service::L('Form')->get_mobile_code($phone)) {
			$this->_json(0, dr_lang('已经发送稍后再试'));// 验证操作间隔
		} 

        $code = rand(100000, 999999);
        $rt = \Phpcmf\Service::M('member')->sendsms_code($phone, $code);
        if (!$rt['code']) {
            $this->_json(0, dr_lang('发送失败'));
        }

		\Phpcmf\Service::L('Form')->set_mobile_code($phone, $code);
		
        $this->_json(1, dr_lang('验证码发送成功'));
    }

    /**
     * 发送验证码
     */
    public function send_code() {

        $phone = dr_safe_replace(\Phpcmf\Service::L('input')->get('id'));
        if (!$phone) {
            $this->_json(0, dr_lang('手机号码未填写'), ['field' => 'phone']);
        } elseif (!\Phpcmf\Service::L('Form')->check_phone($phone)) {
            $this->_json(0, dr_lang('手机号码格式不正确'), ['field' => 'phone']);
        }

        // 挂钩点 短信验证之前
        \Phpcmf\Hooks::trigger('member_send_phone_before', $phone);

        $code = dr_safe_replace(\Phpcmf\Service::L('input')->get('code'));
        if (!$code) {
            $this->_json(0, dr_lang('图片验证码未填写'), ['field' => 'code']);
        } elseif (!\Phpcmf\Service::L('Form')->check_captcha_value($code)) {
            $this->_json(0, dr_lang('图片验证码不正确'), ['field' => 'code']);
        } elseif (\Phpcmf\Service::L('Form')->get_mobile_code($phone)) {
			// 验证操作间隔
            $this->_json(1, dr_lang('已经发送稍后再试'));
        }

        $code = rand(100000, 999999);
        $rt = \Phpcmf\Service::M('member')->sendsms_code($phone, $code);
        if (!$rt['code']) {
            $this->_json(0, dr_lang('发送失败'));
        }

		\Phpcmf\Service::L('Form')->set_mobile_code($phone, $code);
		
        $this->_json(1, dr_lang('验证码发送成功'));
    }


    /**
     * 注册协议
     */
    public function protocol() {
        \Phpcmf\Service::V()->display('protocol.html');
    }

}
