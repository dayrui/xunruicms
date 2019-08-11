<?php namespace Phpcmf\Library;

/* *
 *
 * Copyright [2019] [李睿]
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * www.xunruicms.com
 *
 * 本文件是框架系统文件，二次开发时不建议修改本文件
 *
 * */


// 表单验证类
class Form
{

    private $id = 0;
    private $myfields;

    // 初始化
    public function id($id) {
        $this->id = $id;
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
                    continue; // 对象不存在
                //} else if (IS_MEMBER && !$field['ismember']) {
                    //continue; // 前端字段筛选
                } else if (IS_MEMBER && $field['setting']['validate']['isedit'] && $this->id) {
                    if (defined('IS_MODULE_VERIFY')) {

                    } else {
                        unset($fields[$fid]);
                        continue; // 前端禁止修改时
                    }
                }
                $name = $field['fieldname']; // 字段名称
                $validate = $field['setting']['validate']; // 字段验证规则
                // 编辑器默认关闭xss
                $validate['xss'] = !isset($validate['xss']) || $obj->close_xss ? 1 : $validate['xss'];
                // 从表单获取值
                $post[$name] = $value = $validate['xss'] ? $data[$name] : $this->xss($data[$name]);
                // 验证必填字段
                if ($field['fieldtype'] != 'Group' && $validate['required']) {
                    if ($value == '') {
                        // 验证值为空
                        return [[], ['name' => $name, 'error' => $validate['errortips'] ? $validate['errortips'] : dr_lang('%s不能为空', $field['name'])]];
                    } elseif ($field['fieldtype'] == 'Linkage' && !$value) {
                        // 当类别为联动时判定0值
                        return [[], ['name' => $name, 'error' => $validate['errortips'] ? $validate['errortips'] : dr_lang('%s不能为空', $field['name'])]];
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

    // xss 过滤
    public function xss($val) {
        // remove all non-printable characters. CR(0a) and LF(0b) and TAB(9) are allowed
        // this prevents some character re-spacing such as <java\0script>
        // note that you have to handle splits with \n, \r, and \t later since they *are* allowed in some inputs
        $val = preg_replace('/([\x00-\x08,\x0b-\x0c,\x0e-\x19])/', '', $val);
        // straight replacements, the user should never need these since they're normal characters
        // this prevents like <IMG SRC=@avascript:alert('XSS')>
        $search = 'abcdefghijklmnopqrstuvwxyz';
        $search .= 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $search .= '1234567890!@#$%^&*()';
        $search .= '~`";:?+/={}[]-_|\'\\';
        for ($i = 0; $i < strlen($search); $i++) {
            // ;? matches the ;, which is optional
            // 0{0,7} matches any padded zeros, which are optional and go up to 8 chars

            // @ @ search for the hex values
            $val = preg_replace('/(&#[xX]0{0,8}'.dechex(ord($search[$i])).';?)/i', $search[$i], $val); // with a ;
            // @ @ 0{0,7} matches '0' zero to seven times
            $val = preg_replace('/(&#0{0,8}'.ord($search[$i]).';?)/', $search[$i], $val); // with a ;
        }
        // now the only remaining whitespace attacks are \t, \n, and \r
        $ra1 = array('javascript', 'vbscript', 'expression', 'applet', 'meta', 'xml', 'blink', 'link', 'style', 'script', 'embed', 'object', 'iframe', 'frame', 'frameset', 'ilayer', 'layer', 'bgsound', 'title', 'base');
        $ra2 = array('onabort', 'onactivate', 'onafterprint', 'onafterupdate', 'onbeforeactivate', 'onbeforecopy', 'onbeforecut', 'onbeforedeactivate', 'onbeforeeditfocus', 'onbeforepaste', 'onbeforeprint', 'onbeforeunload', 'onbeforeupdate', 'onblur', 'onbounce', 'oncellchange', 'onchange', 'onclick', 'oncontextmenu', 'oncontrolselect', 'oncopy', 'oncut', 'ondataavailable', 'ondatasetchanged', 'ondatasetcomplete', 'ondblclick', 'ondeactivate', 'ondrag', 'ondragend', 'ondragenter', 'ondragleave', 'ondragover', 'ondragstart', 'ondrop', 'onerror', 'onerrorupdate', 'onfilterchange', 'onfinish', 'onfocus', 'onfocusin', 'onfocusout', 'onhelp', 'onkeydown', 'onkeypress', 'onkeyup', 'onlayoutcomplete', 'onload', 'onlosecapture', 'onmousedown', 'onmouseenter', 'onmouseleave', 'onmousemove', 'onmouseout', 'onmouseover', 'onmouseup', 'onmousewheel', 'onmove', 'onmoveend', 'onmovestart', 'onpaste', 'onpropertychange', 'onreadystatechange', 'onreset', 'onresize', 'onresizeend', 'onresizestart', 'onrowenter', 'onrowexit', 'onrowsdelete', 'onrowsinserted', 'onscroll', 'onselect', 'onselectionchange', 'onselectstart', 'onstart', 'onstop', 'onsubmit', 'onunload');
        $ra = array_merge($ra1, $ra2);

        $found = true; // keep replacing as long as the previous round replaced something
        while ($found == true) {
            $val_before = $val;
            for ($i = 0; $i < sizeof($ra); $i++) {
                $pattern = '/';
                for ($j = 0; $j < strlen($ra[$i]); $j++) {
                    if ($j > 0) {
                        $pattern .= '(';
                        $pattern .= '(&#[xX]0{0,8}([9ab]);)';
                        $pattern .= '|';
                        $pattern .= '|(&#0{0,8}([9|10|13]);)';
                        $pattern .= ')*';
                    }
                    $pattern .= $ra[$i][$j];
                }
                $pattern .= '/i';
                $replacement = substr($ra[$i], 0, 2).'<x>'.substr($ra[$i], 2); // add in <> to nerf the tag
                $val = preg_replace($pattern, $replacement, $val); // filter out the hex tags
                if ($val_before == $val) {
                    // no replacements were made, so exit the loop
                    $found = false;
                }
            }
        }

        return $val;
    }

    // 验证码类
    public function check_captcha($id) {

        $data = \Phpcmf\Service::L('input')->post($id);
        if (!$data) {
            return false;
        }

        $code = \Phpcmf\Service::C()->session()->get('captcha');
        if (strtolower($data) == strtolower($code)) {
            \Phpcmf\Service::C()->session()->remove('captcha');
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