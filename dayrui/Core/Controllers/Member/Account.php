<?php namespace Phpcmf\Controllers\Member;

/**
 * http://www.xunruicms.com
 * 本文件是框架系统文件，二次开发时不可以修改本文件
 **/

// 账号信息
class Account extends \Phpcmf\Common
{

    /**
     * 修改资料
     */
    public function index() {

        // 初始化自定义字段类
        \Phpcmf\Service::L('field')->app(APP_DIR);

        // 获取该组可用字段
        $field = [];
        if ($this->member_cache['field'] && $this->member['groupid']) {
            $fieldid = [];
            foreach ($this->member['groupid'] as $gid) {
                $this->member_cache['group'][$gid]['field']
                && $fieldid = dr_array2array($fieldid, $this->member_cache['group'][$gid]['field']);
            }
            if ($fieldid) {
                foreach ($this->member_cache['field'] as $fname => $t) {
                    in_array($fname, $fieldid) && $field[$fname] = $t;
                }
            }
        }

        // 是否允许更新姓名
        $is_update_name = $this->member_cache['config']['edit_name'] || !$this->member['name'];

        if (IS_POST) {
            $post = \Phpcmf\Service::L('input')->post('data');
            \Phpcmf\Hooks::trigger('member_edit_before', $post);
            list($data, $return, $attach) = \Phpcmf\Service::L('form')->id($this->uid)->validation($post, null, $field, $this->member);
            // 输出错误
            if ($return) {
                $this->_json(0, $return['error'], ['field' => $return['name']]);
            }
            if ($is_update_name) {
                if (!$post['name']) {
                    $this->_json(0, dr_lang('姓名没有填写'), ['field' => 'name']);
                } elseif (strlen($post['name']) > 20) {
                    $this->_json(0, dr_lang('姓名太长了'), ['field' => 'name']);
                }
                \Phpcmf\Service::M()->table('member')->update($this->uid, [
                    'name' => dr_strcut(dr_safe_replace($post['name']), 20, ''),
                ]);
            }
            $data[1]['is_complete'] = 1;
            \Phpcmf\Service::M()->table('member_data')->update($this->uid, $data[1]);
            // 附件归档
            SYS_ATTACHMENT_DB && $attach && \Phpcmf\Service::M('Attachment')->handle(
                $this->member['id'],
                \Phpcmf\Service::M()->dbprefix('member').'-'.$this->uid,
                $attach
            );
            \Phpcmf\Hooks::trigger('member_edit_after', $data[1]);
			\Phpcmf\Service::L('cache')->del_data('member-info-'.$this->uid);
			\Phpcmf\Service::L('cache')->del_data('member-info-name-'.$this->member['username']);
            $this->_json(1, dr_lang('保存成功'), IS_API_HTTP ? \Phpcmf\Service::M('member')->get_member($this->uid) : []);
            exit;
        }

        \Phpcmf\Service::V()->assign([
            'form' => dr_form_hidden(),
            'myfield' => \Phpcmf\Service::L('field')->toform($this->uid, $field, $this->member),
            'is_update_name' => $is_update_name,
        ]);
        \Phpcmf\Service::V()->display('account_index.html');
    }

    /**
     * 头像上传
     */
    public function avatar() {

        if (IS_POST) {
            $content = $_POST['file'];
            // 普通文件上传
            if (isset($_FILES['file'])) {
                if (isset($_FILES["file"]["tmp_name"]) && $_FILES["file"]["tmp_name"]) {
                    $content = \Phpcmf\Service::L('file')->base64_image($_FILES["file"]["tmp_name"]);
                }
            }

            list($cache_path, $cache_url) = dr_avatar_path();
            if (preg_match('/^(data:\s*image\/(\w+);base64,)/i', $content, $result)) {
                $ext = strtolower($result[2]);
                if (!in_array($ext, ['png', 'jpg', 'jpeg'])) {
                    $this->_json(0, dr_lang('图片格式不正确'));
                } elseif (!is_dir($cache_path)) {
                    $this->_json(0, dr_lang('头像存储目录不存在'));
                }
                $content = base64_decode(str_replace($result[1], '', $content));
                if (strlen($content) > 30000000) {
                    $this->_json(0, dr_lang('图片太大了'));
                }
                $data = strtolower($content);
                if (strpos($data, '<?php') !== false) {
                    $this->_json(0, dr_lang('此图片不安全，禁止上传'));
                } elseif (strpos($data, 'eval(') !== false) {
                    $this->_json(0, dr_lang('此图片不安全，禁止上传'));
                } elseif (strpos($data, '.php') !== false) {
                    $this->_json(0, dr_lang('此图片不安全，禁止上传'));
                } elseif (strpos($data, 'base64_decode(') !== false) {
                    $this->_json(0, dr_lang('此图片不安全，禁止上传'));
                }
                $file = $cache_path.$this->uid.'.jpg';
                $temp = dr_upload_temp_path().'member.'.$this->uid.'.jpg';
                $size = @file_put_contents($temp, $content);
                if (!$size) {
                    $this->_json(0, dr_lang('头像存储失败'));
                } elseif (!is_file($temp)) {
                    $this->_json(0, dr_lang('头像存储失败'));
                } elseif (!getimagesize($temp)) {
                    @unlink($file);
                    $this->_json(0, '文件不是规范的图片');
                }
                // 上传图片到服务器
                copy($temp, $file);
                if (!is_file($file)) {
                    $this->_json(0, dr_lang('头像复制失败'));
                }
                \Phpcmf\Service::M()->db->table('member_data')->where('id', $this->member['id'])->update(['is_avatar' => 1]);
                $this->_json(1, dr_lang('上传成功'), IS_API_HTTP ? \Phpcmf\Service::M('member')->get_member($this->uid) : []);
            } else {
                $this->_json(0, dr_lang('头像内容不规范'));
            }
        }

        \Phpcmf\Service::V()->assign([
            'form' => dr_form_hidden(['file' => '']),
        ]);
        \Phpcmf\Service::V()->display('account_avatar.html');
    }

    /**
     * 修改密码
     */
    public function password() {

        if (IS_POST) {
            $post = \Phpcmf\Service::L('input')->post('data');
            $password = dr_safe_password($post['password']);
            if ((empty($post['password2']) || empty($post['password3']))) {
                $this->_json(0, dr_lang('密码不能为空'), ['field' => 'password2']);
            } elseif ($post['password3'] != $post['password2']) {
                $this->_json(0, dr_lang('两次密码不一致'), ['field' => 'password3']);
            } elseif ($this->member['password'] && md5(md5($password).$this->member['salt'].md5($password)) != $this->member['password']) {
                $this->_json(0, dr_lang('原密码不正确'), ['field' => 'password']);
            } elseif ($this->member['password'] && md5(md5($post['password2']).$this->member['salt'].md5($post['password2'])) == $this->member['password']) {
                $this->_json(0, dr_lang('原密码不能与新密码相同'), ['field' => 'password2']);
            }
            // 修改密码
            \Phpcmf\Service::M('member')->edit_password($this->member, $post['password2']);
            $this->_json(1, dr_lang('操作成功'));
        }

        \Phpcmf\Service::V()->assign([
            'form' => dr_form_hidden(),
        ]);
        \Phpcmf\Service::V()->display('account_password.html');
    }

    /**
     * 手机修改
     */
    public function mobile() {

        // 是否允许更新手机号码
        $is_update = $this->member_cache['config']['edit_mobile'] || !$this->member['phone'];

        // 是否需要认证手机号码
        $is_mobile = $this->member_cache['config']['mobile'] && !$this->member['is_mobile'] ;

        // 账号已经录入了手机，且没有进行手机认证时，强制不更新，先认证
        //$is_mobile && $this->member['phone'] && $is_update = 0;

        if (IS_POST) {
            $post = \Phpcmf\Service::L('input')->post('data');
            $value = dr_safe_replace($post['phone']);
			$cache = \Phpcmf\Service::L('cache')->get_data('member-mobile-code-'.$this->uid);
            if (!$this->member['randcode']) {
                $this->_json(0, dr_lang('手机验证码已过期'));
            } elseif ($post['code'] != $this->member['randcode']) {
                $this->_json(0, dr_lang('手机验证码不正确'));
            } elseif (!$cache) {
                $this->_json(0, dr_lang('手机验证码储存过期'));
            } elseif ($cache != $value) {
                // caceh存储的是手机号码，验证手机号码是否匹配
                $this->_json(0, dr_lang('手机号码不匹配'));
            }

            // 更新手机号
            if ($is_update && $value) {
                $value = dr_safe_replace($post['phone']);
                if (!is_numeric($value) || strlen($value) != 11) {
                    $this->_json(0, dr_lang('手机号码格式不正确'));
                } elseif (\Phpcmf\Service::M()->db->table('member')->where('id<>'.$this->member['id'])->where('phone', $value)->countAllResults()) {
                    $this->_json(0, dr_lang('手机号码已经注册'));
                }
                \Phpcmf\Service::M()->db->table('member')->where('id', $this->member['id'])->update(['phone' => $value]);
            }

            // 认证号码
            $is_mobile && \Phpcmf\Service::M()->db->table('member_data')->where('id', $this->member['id'])->update(['is_mobile' => 1]);

            \Phpcmf\Service::M()->db->table('member')->where('id', $this->member['id'])->update(['randcode' => 0]);

            $this->_json(1, dr_lang('操作成功'));
        }

        \Phpcmf\Service::V()->assign([
            'form' => dr_form_hidden(),
            'api_url' =>\Phpcmf\Service::L('Router')->member_url('account/mobile_code'),
            'is_update' => $is_update,
            'is_mobile' => $is_mobile,
        ]);
        \Phpcmf\Service::V()->display('account_mobile.html');
    }

    /**
     * 短信验证码
     */
    public function mobile_code() {

        // 是否允许更新手机号码
        ($this->member_cache['config']['edit_mobile'] || !$this->member['phone'])
        && $value = dr_safe_replace(\Phpcmf\Service::L('input')->get('value'));

        // 是否需要认证手机号码
        !$value && $this->member['phone'] && $this->member_cache['config']['mobile'] && !$this->member['is_mobile']
        && $value = $this->member['phone'];

        // 验证操作间隔
        $name = 'member-mobile-code-'.$this->uid;
		if (\Phpcmf\Service::L('cache')->get_data($name)) {
			$this->_json(0, dr_lang('已经发送稍后再试'));
		} elseif ((!is_numeric($value) || strlen($value) != 11)) {
			$this->_json(0, dr_lang('手机号码格式不正确'));
		}

        $this->member['randcode'] = rand(100000, 999999);
        \Phpcmf\Service::M()->db->table('member')->where('id', $this->member['uid'])->update(['randcode' => $this->member['randcode']]);

        $rt = \Phpcmf\Service::M('member')->sendsms_code($value, $this->member['randcode']);
        if (!$rt['code']) {
			$this->_json(0, dr_lang('发送失败'));	
		}

		\Phpcmf\Service::L('cache')->set_data($name, $value, defined('SYS_CACHE_SMS') && SYS_CACHE_SMS ? SYS_CACHE_SMS : 60);
		
        $this->_json(1, dr_lang('验证码发送成功'));
    }


    /**
     * 登录记录
     */
    public function login() {

        \Phpcmf\Service::V()->display('account_login.html');
    }

    /**
     * 解除绑定
     */
    public function oauth_delete() {

        $name = dr_safe_replace(\Phpcmf\Service::L('input')->get('name'));
        \Phpcmf\Service::M()->db->table('member_oauth')->where('uid', $this->uid)->where('oauth', $name)->delete();
        if (dr_is_app('weixin')) {
            \Phpcmf\Service::M()->db->table('weixin_user')->where('uid', $this->uid)->delete();
        }
        $this->_json(1, dr_lang('操作成功'), ['url' =>\Phpcmf\Service::L('Router')->member_url('account/oauth')]);
    }

    /**
     * 快捷登录
     */
    public function oauth() {

        $name = ['qq', 'weixin', 'weibo', 'wechat'];
        foreach ($name as $key => $value) {
            if (!isset($this->member_cache['oauth'][$value]['id'])
                || !$this->member_cache['oauth'][$value]['id']) {
                unset($name[$key]);
            }
            if ($value == 'wechat' && !dr_is_app('weixin')) {
                unset($name[$key]);
            }
        }

        \Phpcmf\Service::V()->assign([
            'list' => \Phpcmf\Service::M('member')->oauth($this->uid),
            'oauth' => $name,
        ]);
        \Phpcmf\Service::V()->display('account_oauth.html');
    }
}
