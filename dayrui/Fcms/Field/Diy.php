<?php namespace Phpcmf\Field;
/**
 * {{www.xunruicms.com}}
 * {{迅睿内容管理框架系统}}
 * 本文件是框架系统文件，二次开发时不可以修改本文件，可以通过继承类方法来重写此文件
 **/

class Diy extends \Phpcmf\Library\A_Field {

    /**
     * 构造函数
     */
    public function __construct(...$params) {
        parent::__construct(...$params);
        $this->fieldtype = TRUE;
        $this->defaulttype = 'VARCHAR';
    }

    /**
     * 字段相关属性参数
     *
     * @param   array   $value  值
     * @return  string
     */
    public function option($option) {

        $option['type'] = isset($option['type']) ? $option['type'] : 0;
        $option['code'] = isset($option['code']) ? $option['code'] : '';
        $option['file'] = isset($option['file']) ? $option['file'] : '';

        $str = '<select class="form-control" name="data[setting][option][file]"><option value=""> -- </option>';
        $files = dr_file_map(CONFIGPATH.'myfield/', 1);
		if ($this->app) {
			$files2 = dr_file_map(dr_get_app_dir($this->app).'Config/myfield/', 1);
			$files2 && $files = dr_array2array($files2, $files);
		}
        if ($files) {
            foreach ($files as $t) {
                if ($t && strpos($t, '.php') !== 0) {
					$str.= '<option value="'.$t.'" '.($option['file'] == $t ? 'selected' : '').'> '.$t.' </option>';
				}
            }
        }
        $str.= '</select>';

        return ['
                <div class="form-group dr_type">
                    <label class="col-md-2 control-label">'.dr_lang('自定义文件').'</label>
                    <div class="col-md-9">
                    <label>'.$str.'</label>
                    <span class="help-block">'.dr_lang('将设计好的文件上传到./config/myfield/目录之下').'</span>
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-md-2 control-label">'.dr_lang('默认值').'</label>
                    <div class="col-md-9">
                    <label><input id="field_default_value" type="text" class="form-control" size="20" value="'.$option['value'].'" name="data[setting][option][value]"></label>
                    <label>'.$this->member_field_select().'</label>
                    <span class="help-block">'.dr_lang('用于字段为空时显示该填充值，并不会去主动变更数据库中的实际值；可以设置会员表字段，表示用当前登录会员信息来填充这个值').'</span>
                    </div>
                </div>'.$this->field_type($option['fieldtype'], $option['fieldlength'])];
    }

    /**
     * 字段入库值
     */
    public function insert_value($field) {
        $data = \Phpcmf\Service::L('Field')->post[$field['fieldname']];
        $func = 'dr_diy_field_'.substr($field['setting']['option']['file'], 0, -4).'_insert_value';
        // 回调格式化函数
        if (function_exists($func)) {
            $data = call_user_func($func, $data);
        }
        is_array($data) && $data = dr_array2string($data);
        \Phpcmf\Service::L('Field')->data[$field['ismain']][$field['fieldname']] = $data;
    }

    /**
     * 字段输出
     *
     * @param   array   $value  数据库值
     * @return  string
     */
    public function output($value) {

        if (!$value) {
            return $value;
        }

        return strpos($value, '["') === 0 || strpos($value, '{"') === 0 ? dr_string2array($value) : $value;
    }

    /**
     * 字段表单显示
     *
     * @param	string	$field	字段数组
     * @param	array	$value	值
     * @return  string
     */
    public function show($field, $value = null) {

        // 字段默认值
        $value = dr_strlen($value) ? $value : $this->get_default_value($field['setting']['option']['value']);

        // 回调格式化函数
        $func = 'dr_diy_field_'.substr($field['setting']['option']['file'], 0, -4).'_show';
        if (function_exists($func)) {
            $str = call_user_func($func, $field, $value);
        } else {
            $str = '<div class="form-control-static"> '.htmlspecialchars_decode((string)$value).' </div>';
        }

        return $this->input_format($field['fieldname'], $field['name'], $str);
    }

    /**
     * 字段表单输入
     *
     * @param   string  $cname  字段
     * @param   string  $value  值
     * @return  string
     */
    public function input($field, $value = 0) {

        // 字段禁止修改时就返回显示字符串
        if ($this->_not_edit($field, $value)) {
            return $this->show($field, $value);
        }

        // 字段显示名称
        $text = ($field['setting']['validate']['required'] ? '<span class="required" aria-required="true"> * </span>' : '').dr_lang($field['name']);

        // 表单附加参数
        $attr = isset($field['setting']['validate']['formattr']) && $field['setting']['validate']['formattr'] ? $field['setting']['validate']['formattr'] : '';
        // 字段提示信息
        $tips = isset($field['setting']['validate']['tips']) && $field['setting']['validate']['tips'] ? '<span class="help-block" id="dr_'.$field['fieldname'].'_tips">'.$field['setting']['validate']['tips'].'</span>' : '';
        // 字段默认值
        $value = dr_strlen($value) ? $value : $this->get_default_value($field['setting']['option']['value']);

        $name = $field['fieldname'];
        $code = '';

        // 文件类型
        if (!$field['setting']['option']['file']) {
            $code = '<font color=red>没有设置文件</font>';
        } else {
            if (strpos((string)$field['setting']['option']['file'], '/') !== false
                && is_file($field['setting']['option']['file'])) {
                require $field['setting']['option']['file'];
            } else {
                $file = CONFIGPATH.'myfield/'.$field['setting']['option']['file'];
                $file2 = dr_get_app_dir(APP_DIR).'Config/myfield/'.$field['setting']['option']['file'];
                if (is_file($file)) {
                    require $file;
                } elseif (is_file($file2)) {
                    require $file2;
                } elseif (!$field['setting']['option']['file']) {
                    $code = '<font color=red>没有选择文件，在字段属性中选择</font>';
                } else {
                    $code = '<font color=red>文件（'.$file.'）不存在</font>';
                }
            }
        }

        return $this->input_format($field['fieldname'], $text, $code);
    }

}