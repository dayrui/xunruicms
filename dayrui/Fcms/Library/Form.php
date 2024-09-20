<?php namespace Phpcmf\Library;
/**
 * {{www.xunruicms.com}}
 * {{迅睿内容管理框架系统}}
 * 本文件是框架系统文件，二次开发时不可以修改本文件，可以通过继承类方法来重写此文件
 **/

// 表单验证类
class Form {

    public $mfields;
    public $fields;

    protected $id = 0;

    // 初始化
    public function id($id) {
        $this->id = (int)$id;
        return $this;
    }

    // 获取表单临时存储数据
    public function auto_form_data($name, $data) {
        // 默认数据
        $dt = \Phpcmf\Service::L('cache')->init('file')->get(md5($name));
        if (!$dt) {
            return $data;
        }
        $dt['is_form_cache'] = 1;
        return $data ? $dt + $data : $dt;
    }

    // 删除表单临时存储数据
    public function auto_form_data_delete($name) {
        \Phpcmf\Service::L('cache')->init('file')->delete(md5($name));
    }

    /**
     * 自动临时存储表单数据ajax
     */
    public function auto_form_data_ajax($name) {

        return '
        $(function(){
            setInterval("auto_form_data_ajax()", 5000);
        });
        function auto_form_data_ajax() {
            $.ajax({
                type: "POST",
                url: "'.dr_web_prefix('index.php?s=api&c=api&m=save_form_data&name='.$name).'",
                dataType: "json",
                data: $("#myform").serialize(),
                success: function(data){ }
            });
        }
        function auto_form_data_delete() {
            var index = layer.load(2, {
                shade: [0.3,\'#fff\'],
                time: 10000
            });
            $.ajax({
                type: "GET",
                url: "'.dr_web_prefix('index.php?s=api&c=api&m=delete_form_data&name='.$name).'",
                dataType: "json",
                success: function(json){ 
                    layer.close(index);
                    dr_tips(json.code, json.msg);
                    setTimeout("window.location.reload(true)", 3000)
                }
            });
        }
        ';
    }

    /**
     *
     * 验证字段格式
     * 字段名 => [name显示名称, rule验证规则(支持正则), length长度]
     * field 自定义字段配置
     * id  是否修改的条件
     **/

    public function validation($data, $config, $fields = [], $old = []) {

        if (!$data) {
            $data = [];
        }

        // 初始化信息
        \Phpcmf\Service::L('Field')->old = $old;
        \Phpcmf\Service::L('Field')->data = [];

        $attach = []; // 附件信息

        // 表单规则验证
        if ($config) {
            foreach ($config as $name => $t) {
                // 长度验证
                if ($t['length'] && dr_strlen($data[$name]) > $t['length']) {
                    return [[], ['name' => $name, 'error' => dr_lang('长度不规范')]];
                }
                // 规则验证
                if ($t['rule']) {
                    foreach ($t['rule'] as $rule => $error) {
                        switch ($rule) {
                            case 'empty':
                                if (!$data[$name] && !dr_strlen($data[$name])) {
                                    return [[], ['name' => $name, 'error' => $error]];
                                }
                                break;
                            case 'table':
                                if (!(preg_match('/^[a-z]+[0-9]+/i', (string)$data[$name]) || preg_match('/[a-z]+/i', (string)$data[$name]))) {
                                    return [[], ['name' => $name, 'error' => $error]];
                                }
                                break;
                            case 'pinyin':
                                if (!preg_match('/[a-z0-9]+/i', (string)$data[$name])) {
                                    return [[], ['name' => $name, 'error' => $error]];
                                }
                                break;
                        }
                    }
                }
                // 过滤
                if ($t['filter']) {
                    foreach ($t['filter'] as $value) {
                        switch ($value) {
                            case 'intval':
                                $data[$name] = intval($data[$name]);
                                break;
                        }
                    }
                }
            }
        }

        $notfields = [];

        // 自定义字段验证
        if ($fields) {
            $post = [];
            $this->fields = $fields;
            // 格式化字段值
            foreach ($fields as $fid => $field) {
                // 验证字段对象的有效性
                $obj = \Phpcmf\Service::L('Field')->get($field['fieldtype']);
                if (!$obj) {
                    unset($fields[$fid]);
                    continue; // 对象不存在
                }
                $obj->init($field);
                $name = $field['fieldname']; // 字段名称
                $obj->id = $this->id;
                // 非后台时
                if (!IS_ADMIN) {
                    if (!$field['ismember']) {
                        $notfields[] = $field['fieldname']; // 无权限排除的字段
                        unset($fields[$fid]);
                        continue; // 前端字段筛选
                    } elseif ($obj->_not_edit($field, $old[$field['fieldname']])) {
                        unset($fields[$fid]);
                        $notfields[] = $field['fieldname']; // 无权限排除的字段
                        continue; // 前端禁止修改时
                    } elseif ($field['setting']['show_member'] && dr_array_intersect(\Phpcmf\Service::C()->member['groupid'], $field['setting']['show_member'])) {
                        unset($fields[$fid]);
                        $notfields[] = $field['fieldname']; // 无权限排除的字段
                        continue; // 非后台时 判断用户权限
                    }
                } elseif (IS_ADMIN && $field['setting']['show_admin'] && !dr_in_array(1, \Phpcmf\Service::C()->admin['roleid'])
                    && dr_array_intersect(\Phpcmf\Service::C()->admin['roleid'], $field['setting']['show_admin'])) {
                    $notfields[] = $field['fieldname']; // 无权限排除的字段
                    continue; // 后台时 判断管理员权限
                }

                // 验证字段
                $validate = $field['setting']['validate']; // 字段验证规则
                // 默认xss开关
                $xss = 0;
                if ($obj->close_xss) {
                    $xss = 0;
                } elseif ($obj->use_xss) {
                    $xss = 1;
                } elseif (isset($validate['xss']) && $validate['xss']) {
                    // 手动开启xss
                    $xss = 1;
                }
                // 从表单获取值
                $post[$name] = $value = ($xss ? \Phpcmf\Service::L('Security')->xss_clean($data[$name]) : $data[$name]);
                // 验证字段值
                $frt = $obj->check_value($field, $value);
                if ($frt) {
                    return [[], ['name' => $name, 'error' => $frt]];
                }
                // 验证必填字段
                if ($obj->is_validate && $validate['required']) {
                    if (IS_ADMIN && dr_in_array(1, \Phpcmf\Service::C()->admin['roleid'])) {
                        // 后台超管不验证必填
                    } else {
                        // 开始验证必填字段
                        $frt = $obj->check_required($field, $value);
                        if ($frt) {
                            return [[], ['name' => $name, 'error' => $frt]];
                        }
                    }
                    // 正则验证
                    if (!is_array($value) && $validate['pattern'] && !preg_match($validate['pattern'], $value)) {
                        return [[], ['name' => $name, 'error' => $field['name'].'：'.($validate['errortips'] ? dr_lang($validate['errortips']) : dr_lang('格式不正确'))]];
                    }
                }
                // 编辑器长度判断
                if (stripos($field['fieldtype'], 'editor') !== false && dr_strlen($data[$name]) > 16000000) {
                    return [[], ['name' => $name, 'error' => dr_lang('%s长度超限', $field['name'])]];
                }
                // 函数/方法校验
                if ($validate['check']) {
                    if (strpos($validate['check'], '_') === 0) {
                        // 方法格式
                        $method = substr($validate['check'], 1);
                        if (method_exists($this, $method)) {
                            if ('check_member' == $method && $value == 'guest') {
                                // 游客不验证
                            } else {
                                $rt = call_user_func_array([$this, $method], [$value, $data, $old]);
                                if (!$rt['code']) {
                                    return [[], ['name' => $name, 'error' => $rt['msg']]];
                                }
                            }
                        } else {
                            log_message('error', "校验方法 $method 不可用");
                        }
                    } else {
                        // 函数格式
                        $func = $validate['check'];
                        if (dr_is_call_function($func)) {
                            $rt = call_user_func_array($func, [$value, $data, $old]);
                            if (!$rt['code']) {
                                return [[], ['name' => $name, 'error' => $rt['msg']]];
                            }
                        } else {
                            log_message('error', "校验函数 $func 不可用");
                        }
                    }
                }
                // 过滤函数/方法
                if ($validate['filter']) {
                    if (strpos($validate['filter'], '_') === 0) {
                        // 方法格式
                        $method = substr($validate['filter'], 1);
                        if (method_exists($this, $method)) {
                            // 开始过滤
                            $post[$name] = call_user_func_array([$this, $method], [$value, $data, $old]);
                        } else {
                            log_message('error', "过滤方法 $method 不可用");
                        }
                    } else {
                        // 函数格式
                        $func = $validate['filter'];
                        if (dr_is_call_function($func)) {
                            // 开始过滤
                            $post[$name] = call_user_func_array($func, [$value, $data, $old]);
                        } else {
                            log_message('error', "过滤函数 $func 不可用");
                        }
                    }
                }
                // 判断表字段值的唯一性
                if ($field['ismain'] && $field['setting']['option']['unique']) {
                    if (empty(dr_strlen($post[$name]))) {
                        return [[], ['name' => $name, 'error' => dr_lang('%s不能为空', $field['name'])]];
                    }
                    if (\Phpcmf\Service::C()->init['table']) {
                        $table = \Phpcmf\Service::M()->dbprefix(\Phpcmf\Service::C()->init['table']);
                        if (\Phpcmf\Service::M()->db->fieldExists($name, $table)) {
                            $rt = \Phpcmf\Service::M()->db->table(\Phpcmf\Service::C()->init['table'])->where('id<>'. $this->id)->where($name, $post[$name])->countAllResults();
                            if ($rt) {
                                return [[], ['name' => $name, 'error' => dr_lang('%s已经存在', $field['name'])]];
                            }
                        } else {
                            log_message('error', "字段唯一性验证失败：表".$table."中字段".$name."不存在！");
                        }
                    } else {
                        log_message('error', "字段唯一性验证失败：数据表不存在！");
                    }
                }
            }

            // 存储附件id
            $attach['add'] = $attach['del'] = [];

            // 主表附表归类
            foreach ($fields as $field) {
                // 验证字段对象的有效性
                $obj = \Phpcmf\Service::L('Field')->get($field['fieldtype'], $this->id, $post);
                if (!$obj) {
                    continue; // 对象不存在
                }
                $obj->insert_value($field); // 格式化入库值
                // 处理附件归档
                if (SYS_ATTACHMENT_DB) {
                    // 通过附件处理方法获得增加和删除的附件
                    list($add_id, $del_id) = $obj->attach(
                        \Phpcmf\Service::L('Field')->data[$field['ismain']][$field['fieldname']],
                        $old[$field['fieldname']]
                    );
                    $attach['add'] = $add_id ? array_merge($add_id, $attach['add']) : $attach['add'];
                    $attach['del'] = $del_id ? array_merge($del_id, $attach['del']) : $attach['del'];
                }
            }
            // 格式化后的数据
            $data = \Phpcmf\Service::L('Field')->data;
            // 获取uid
        }


        return [$data, [], $attach, $notfields];
    }
	
	// 获取已发短信验证码
	public function get_mobile_code($phone) {
		return \Phpcmf\Service::L('cache')->get_auth_data('phone-code-'.$phone, SITE_ID, defined('SYS_CACHE_SMS') && SYS_CACHE_SMS ? SYS_CACHE_SMS : 300);
	}
	
	// 储存已发短信验证码
	public function set_mobile_code($phone, $code) {
		return \Phpcmf\Service::L('cache')->set_auth_data('phone-code-'.$phone, $code, SITE_ID);
	}

    // 验证码类
    public function check_captcha($id) {

		// API请求时，是否进行验证图片
		if (IS_API_HTTP && defined('SYS_API_CODE') && !SYS_API_CODE) {
			return true;
		}

        if (!IS_ADMIN && function_exists('my_check_captcha')) {
            return my_check_captcha($id);
        }

        $data = trim((string)\Phpcmf\Service::L('input')->post($id));
        if (!$data) {
            IS_DEV && log_message('debug', '图片验证码验证失败：没有输入验证码'.dr_safe_replace(\Phpcmf\Service::L('input')->ip_address().':'.\Phpcmf\Service::L('input')->get_user_agent()));
            return false;
        }

        $code = \Phpcmf\Service::L('cache')->get_auth_data('web-captcha-'.USER_HTTP_CODE, SITE_ID, 300);
        if (!$code) {
            IS_DEV && log_message('error', '图片验证码未生成（'.USER_HTTP_CODE.'）'.dr_safe_replace(\Phpcmf\Service::L('input')->ip_address().':'.\Phpcmf\Service::L('input')->get_user_agent()));
            return false;
        } elseif (strtolower($data) == strtolower($code)) {
            \Phpcmf\Service::L('cache')->del_auth_data('web-captcha-'.USER_HTTP_CODE, SITE_ID);
            return true;
        }

        IS_DEV && log_message('debug', '图片验证码验证失败：你输入的是（'.$data.'），正确的是（'.$code.'）'.dr_safe_replace(\Phpcmf\Service::L('input')->ip_address().':'.\Phpcmf\Service::L('input')->get_user_agent()));

        return false;
    }

    // 验证码类：只比较不删除
    public function check_captcha_value($data) {

		// API请求时，是否进行验证图片
		if (IS_API_HTTP && defined('SYS_API_CODE') && !SYS_API_CODE) {
			return true;
		} elseif (!$data) {
            IS_DEV && log_message('debug', '图片验证码验证失败：没有输入验证码');
            return false;
        }

        $data = trim((string)$data);
        if (!IS_ADMIN && function_exists('my_check_captcha_value')) {
            return my_check_captcha_value($data);
        }

        $code = \Phpcmf\Service::L('cache')->get_auth_data('web-captcha-'.USER_HTTP_CODE, SITE_ID, 300);
        if (!$code) {
            IS_DEV && log_message('error', '图片验证码未生成（'.USER_HTTP_CODE.'）'.dr_safe_replace(\Phpcmf\Service::L('input')->ip_address().':'.\Phpcmf\Service::L('input')->get_user_agent()));
            return false;
        } elseif (strtolower($data) == strtolower($code)) {
            return true;
        }

        IS_DEV && log_message('debug', '图片验证码验证失败：你输入的是（'.$data.'），正确的是（'.$code.'）'.dr_safe_replace(\Phpcmf\Service::L('input')->ip_address().':'.\Phpcmf\Service::L('input')->get_user_agent()));

        return false;
    }

    // 验证手机号码长度
    public function check_phone($value) {

        if (!$value) {
            return false;
        } elseif (dr_strlen($value) > 30) {
            return false;
        }

        return true;
    }

    // 验证邮件地址
    public function check_email($value) {

        if (!$value) {
            return false;
        } elseif (!preg_match('/^[\w\-\.]+@[\w\-\.]+(\.\w+)+$/', $value)) {
            return false;
        } elseif (strpos($value, '"') !== false || strpos($value, '\'') !== false) {
            return false;
        }

        return true;
    }

    // 验证账号
    public function check_username($value) {

        if (!$value) {
            return dr_return_data(0, dr_lang('账号不能为空'), ['field' => 'username']);
        } elseif (\Phpcmf\Service::C()->member_cache['register']['preg']
            && !preg_match(\Phpcmf\Service::C()->member_cache['register']['preg'], $value)) {
            // 验证账号的组成格式
            return dr_return_data(0, dr_lang('账号格式不正确'), ['field' => 'username']);
        } elseif (strpos($value, '"') !== false
            || strpos($value, '<') !== false
            || strpos($value, '>') !== false
            || strpos($value, '\'') !== false
        ) {
            // 引号判断
            return dr_return_data(0, dr_lang('账号名存在非法字符'), ['field' => 'username']);
        } elseif (\Phpcmf\Service::C()->member_cache['config']['userlen']
            && mb_strlen($value) < \Phpcmf\Service::C()->member_cache['config']['userlen']) {
            // 验证账号长度
            return dr_return_data(0, dr_lang('账号长度不能小于%s位，当前%s位', \Phpcmf\Service::C()->member_cache['config']['userlen'], mb_strlen($value)), ['field' => 'username']);
        } elseif (\Phpcmf\Service::C()->member_cache['config']['userlenmax']
            && mb_strlen($value) > \Phpcmf\Service::C()->member_cache['config']['userlenmax']) {
            // 验证账号长度
            return dr_return_data(0, dr_lang('账号长度不能大于%s位，当前%s位', \Phpcmf\Service::C()->member_cache['config']['userlenmax'], mb_strlen($value)), ['field' => 'username']);
        }
        $notallow = \Phpcmf\Service::C()->member_cache['register']['notallow'];
        if (!$notallow || !is_array($notallow)) {
            $notallow = [];
        }
        $notallow[] = dr_lang('游客');
        // 后台不允许注册的词语，放在最后一次比较
        foreach ($notallow as $a) {
            if (dr_strlen($a) && strpos($value, $a) !== false) {
                return dr_return_data(0, dr_lang('账号名不允许注册'), ['field' => 'username']);
            }
        }

        return dr_return_data(1, 'ok');
    }

    // 验证账号的密码
    public function check_password($value, $username) {

        if (!$value) {
            return dr_return_data(0, dr_lang('密码不能为空'), ['field' => 'password']);
        } elseif (!\Phpcmf\Service::C()->member_cache['config']['user2pwd'] && $value == $username) {
            return dr_return_data(0, dr_lang('密码不能与账号相同'), ['field' => 'password']);
        } elseif (\Phpcmf\Service::C()->member_cache['config']['pwdpreg']
            && !preg_match(trim(\Phpcmf\Service::C()->member_cache['config']['pwdpreg']), $value)) {
            return dr_return_data(0, dr_lang('密码格式不正确'), ['field' => 'password']);
        } elseif (\Phpcmf\Service::C()->member_cache['config']['pwdlen']
            && mb_strlen($value) < \Phpcmf\Service::C()->member_cache['config']['pwdlen']) {
            return dr_return_data(0, dr_lang('密码长度不能小于%s位，当前%s位', \Phpcmf\Service::C()->member_cache['config']['pwdlen'], mb_strlen($value)), ['field' => 'password']);
        } elseif (mb_strlen($value) > 100) {
            return dr_return_data(0, dr_lang('密码长度不能过长'), ['field' => 'password']);
        }

        return dr_return_data(1, 'ok');
    }

    // 验证姓名
    public function check_name($value) {

        if (!$value) {
            return false;
        } elseif (\Phpcmf\Service::C()->member_cache['register']['cutname'] && mb_strlen($value) > \Phpcmf\Service::C()->member_cache['register']['cutname']) {
            return false;
        }

        return true;
    }

    // 验证域名
    public function check_domain($value) {

        if (!$value) {
            return false;
        }

        foreach (['/', '?', '&', '\\', '*', ' ', '..', '(', ')', '\'', '"', ',', ';'] as $p) {
            if (strpos($value, $p) !== false) {
                return false;
            }
        }

        return true;
    }

    // 验证目录式域名
    public function check_domain_dir($value) {

        if (!$value) {
            return false;
        }

        foreach (['/', '?', '&', '\\', '*', ' ', '..', '(', ')', '\'', '"', ',', ';'] as $p) {
            if (strpos($value, $p) !== false) {
                return false;
            }
        }

        if (substr_count($value, '/') > 1) {
            return false;
        }

        return true;
    }

    // 生成随机验证码
    public function get_rand_value() {
        return rand(100000, 999999);
    }



    //=====校验方法=========

    /**
     * 验证会员名称是否存在
     *
     * @param   $value	当前字段提交的值
     * @return  flase不通过 , true通过
     */
    public function check_member($value) {

        if (!$value || dr_lang('游客') == $value) {
            return dr_return_data(1);
        } elseif (!\Phpcmf\Service::M('member')->uid($value)) {
            return dr_return_data(0, dr_lang('账号【%s】不存在', $value));
        }

        return dr_return_data(1);
    }
}