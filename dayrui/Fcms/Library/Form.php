<?php namespace Phpcmf\Library;


/**
 * http://www.xunruicms.com
 * 本文件是框架系统文件，二次开发时不可以修改本文件，可以通过继承类方法来重写此文件
 **/



// 表单验证类
class Form
{

    private $id = 0;
    private $myfields;

    // 初始化
    public function id($id) {
        $this->id = (int)$id;
        return $this;
    }

    public function get_myfields() {
        return $this->myfields;
    }

    // 获取表单临时存储数据
    public function auto_form_data($name, $data) {
        // 默认数据
        $dt = \Phpcmf\Service::L('cache')->init('file')->get($name);
        if (!$dt) {
            return $data;
        }
        $dt['is_form_cache'] = 1;
        return $data ? $dt + $data : $dt;
    }

    // 删除表单临时存储数据
    public function auto_form_data_delete($name) {
        \Phpcmf\Service::L('cache')->init('file')->delete($name);
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
                url: "/index.php?s=api&c=api&m=save_form_data&name='.$name.'",
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
                url: "/index.php?s=api&c=api&m=delete_form_data&name='.$name.'",
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

        $attach = []; // 附件信息

        // 表单规则验证
        if ($config) {
            foreach ($config as $name => $t) {
                // 长度验证
                if ($t['length'] && strlen($data[$name]) > $t['length']) {
                    return [[], ['name' => $name, 'error' => dr_lang('长度不规范')]];
                }
                // 规则验证
                if ($t['rule']) {
                    foreach ($t['rule'] as $rule => $error) {
                        switch ($rule) {
                            case 'empty':
                                if (!$data[$name] && !strlen($data[$name])) {
                                    return [[], ['name' => $name, 'error' => $error]];
                                }
                                break;
                            case 'table':
                                if (!(preg_match('/^[a-z]+[0-9]+/i', $data[$name]) || preg_match('/[a-z]+/i', $data[$name]))) {
                                    return [[], ['name' => $name, 'error' => $error]];
                                }
                                break;
                            case 'pinyin':
                                if (!preg_match('/[a-z0-9]+/i', $data[$name])) {
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
                            case 'url':
                                $data[$name] = strpos($data[$name], 'http://') === 0 ? $data[$name] : 'http://'.$data[$name];
                                break;
                            case 'intval':
                                $data[$name] = intval($data[$name]);
                                break;
                        }
                    }
                }
            }
        }

        // 自定义字段验证
        if ($fields) {
            $post = [];
            $this->myfields = $fields;
            // 格式化字段值
            foreach ($fields as $fid => $field) {
                // 验证字段对象的有效性
                $obj = \Phpcmf\Service::L('Field')->get($field['fieldtype']);
                if (!$obj) {
                    unset($fields[$fid]);
                    continue; // 对象不存在
                }
                // 非后台时
                if (!IS_ADMIN) {
                    if (!$field['ismember']) {
                        unset($fields[$fid]);
                        continue; // 前端字段筛选
                    } elseif ($field['setting']['validate']['isedit'] && $this->id && $old[$field['fieldname']] && !defined('IS_MODULE_VERIFY')) {
                        unset($fields[$fid]);
                        continue; // 前端禁止修改时
                    } elseif ($field['setting']['show_member'] && array_intersect(\Phpcmf\Service::C()->member['groupid'], $field['setting']['show_member'])) {
                        unset($fields[$fid]);
                        continue; // 非后台时 判断用户权限
                    }
                }

                // 验证字段
                $name = $field['fieldname']; // 字段名称
                $validate = $field['setting']['validate']; // 字段验证规则
                // 编辑器默认关闭xss
                $validate['xss'] = !isset($validate['xss']) || $obj->close_xss ? 1 : $validate['xss'];
                // 从表单获取值
                $post[$name] = $value = $validate['xss'] ? $data[$name] : \Phpcmf\Service::L('Security')->xss_clean($data[$name]);
                // 验证字段值
                $frt = $obj->check_value($field, $value);
                if ($frt) {
                    return [[], ['name' => $name, 'error' => $frt]];
                }
                // 验证必填字段
                if ($field['fieldtype'] != 'Group' && $validate['required']) {
                    if (IS_ADMIN && in_array(1, \Phpcmf\Service::C()->admin['roleid'])) {
                        // 后台超管不验证必填
                    } else {
                        // 开始验证必填字段
                        if ($value == '') {
                            // 验证值为空
                            return [[], ['name' => $name, 'error' => $validate['errortips'] ? $validate['errortips'] : dr_lang('%s不能为空', $field['name'])]];
                        } elseif ($field['fieldtype'] == 'Linkage' && !$value) {
                            // 当类别为联动时判定0值
                            return [[], ['name' => $name, 'error' => $validate['errortips'] ? $validate['errortips'] : dr_lang('%s不能为空', $field['name'])]];
                        }
                    }
                    // 正则验证
                    if (!is_array($value) && $validate['pattern'] && !preg_match($validate['pattern'], $value)) {
                        return [[], ['name' => $name, 'error' => $field['name'].'：'.($validate['errortips'] ? $validate['errortips'] : dr_lang('格式不正确'))]];
                    }
                }
                // 编辑器长度判断
                if ($field['fieldtype'] == 'Ueditor' && strlen($data[$name]) > 1000000) {
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
                                $rt = call_user_func_array(array($this, $method), [$value, $data, $old]);
                                if (!$rt['code']) {
                                    return [[], ['name' => $name, 'error' => $rt['msg']]];
                                }
                            }
                        } else {
                            log_message('error', "校验方法 $method 不存在".FC_NOW_URL);
                        }
                    } else {
                        // 函数格式
                        $func = $validate['check'];
                        if (function_exists($func)) {
                            $rt = call_user_func_array($func, [$value, $data, $old]);
                            if (!$rt['code']) {
                                return [[], ['name' => $name, 'error' => $rt['msg']]];
                            }
                        } else {
                            log_message('error', "校验函数 $func 不存在！".FC_NOW_URL);
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
                            $post[$name] = call_user_func_array(array($this, $method), [$value, $data, $old]);
                        } else {
                            log_message('error', "过滤方法 $method 不存在！".FC_NOW_URL);
                        }
                    } else {
                        // 函数格式
                        $func = $validate['filter'];
                        if (function_exists($func)) {
                            // 开始过滤
                            $post[$name] = call_user_func_array($func, [$value, $data, $old]);
                        } else {
                            log_message('error', "过滤函数 $func 不存在！".FC_NOW_URL);
                        }
                    }
                }
                // 判断表字段值的唯一性
                if ($field['ismain'] && $field['setting']['option']['unique']) {
                    if (\Phpcmf\Service::C()->init['table']) {
                        $table = \Phpcmf\Service::M()->dbprefix(\Phpcmf\Service::C()->init['table']);
                        if (\Phpcmf\Service::M()->db->fieldExists($name, $table)) {
                            $rt = \Phpcmf\Service::M()->db->table(\Phpcmf\Service::C()->init['table'])->where('id<>', $this->id)->where($name, $post[$name])->countAllResults();
                            if ($rt) {
                                return [[], ['name' => $name, 'error' => dr_lang('%s已经存在', $field['name'])]];
                            }
                        } else {
                            log_message('error', "字段唯一性验证失败：表".$table."中字段".$name."不存在！".FC_NOW_URL);
                        }
                    } else {
                        log_message('error', "字段唯一性验证失败：数据表不存在！".FC_NOW_URL);
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

        #print_r($data);exit;
        return [$data, [], $attach];
    }
	
	// 获取已发短信验证码
	public function get_mobile_code($phone) {
		return \Phpcmf\Service::L('cache')->get_data('phone-code-'.$phone);
	}
	
	// 储存已发短信验证码
	public function set_mobile_code($phone, $code) {
		return \Phpcmf\Service::L('cache')->set_data('phone-code-'.$phone, $code, defined('SYS_CACHE_SMS') && SYS_CACHE_SMS ? SYS_CACHE_SMS : 60);
	}

    // 验证码类
    public function check_captcha($id) {

		// API请求时，是否进行验证图片
		if (IS_API_HTTP && defined('SYS_API_CODE') && !SYS_API_CODE) {
			return true;
		}

        $data = \Phpcmf\Service::L('input')->post($id);
        if (!$data) {
            return false;
        }

        $code = IS_API_HTTP ? \Phpcmf\Service::L('cache')->get_data('api-captcha-'.md5(IS_API_HTTP_CODE)) : \Phpcmf\Service::C()->session()->get('captcha');
        if ($code && strtolower($data) == strtolower($code)) {
            IS_API_HTTP ? \Phpcmf\Service::L('cache')->del_data('api-captcha-'.md5(IS_API_HTTP_CODE)) : \Phpcmf\Service::C()->session()->remove('captcha');
            return true;
        }

        return false;
    }

    // 验证码类：只比较不删除
    public function check_captcha_value($data) {

		// API请求时，是否进行验证图片
		if (IS_API_HTTP && defined('SYS_API_CODE') && !SYS_API_CODE) {
			return true;
		} elseif (!$data) {
            return false;
        }

        $code = IS_API_HTTP ? \Phpcmf\Service::L('cache')->get_data('api-captcha-'.md5(IS_API_HTTP_CODE)) : \Phpcmf\Service::C()->session()->get('captcha');
        if ($code && strtolower($data) == strtolower($code)) {
            return true;
        }

        return false;
    }

    // 验证手机号码
    public function check_phone($value) {

        if (!$value) {
            return false;
        } elseif (!is_numeric($value)) {
            return false;
        } elseif (strlen($value) != 11) {
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
            return false;
        } elseif (\Phpcmf\Service::C()->member_cache['register']['preg']
            && !preg_match(\Phpcmf\Service::C()->member_cache['register']['preg'], $value)) {
            return false;
        } elseif (\Phpcmf\Service::C()->member_cache['register']['notallow']
            && in_array($value, \Phpcmf\Service::C()->member_cache['register']['notallow'])) {
            return false;
        } elseif (strpos($value, '"') !== false || strpos($value, '\'') !== false) {
            return false;
        }

        return true;
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



    //=====校验方法=========

    /**
     * 验证会员名称是否存在
     *
     * @param   $value	当前字段提交的值
     * @return  flase不通过 , true通过
     */
    public function check_member($value) {

        if (!$value) {
            return dr_return_data(0, dr_lang('账号不能为空'));
        } elseif (!\Phpcmf\Service::M('member')->uid($value)) {
            if (IS_ADMIN && isset($_POST['no_author']) && $_POST['no_author']) {
                return dr_return_data(1);
            }
            return dr_return_data(0, dr_lang('账号【%s】不存在', $value));
        }

        return dr_return_data(1);
    }
}