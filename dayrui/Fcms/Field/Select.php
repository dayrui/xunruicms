<?php namespace Phpcmf\Field;
/**
 * {{www.xunruicms.com}}
 * {{迅睿内容管理框架系统}}
 * 本文件是框架系统文件，二次开发时不可以修改本文件，可以通过继承类方法来重写此文件
 **/

class Select extends \Phpcmf\Library\A_Field {
	
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
	 * @param	array	$value	值
	 * @return  string
	 */
	public function option($option) {

        $option['options'] = isset($option['options']) ? $option['options'] : 'name1|1'.PHP_EOL.'name2|2';

		return [
			'
			<div class="form-group">
				<label class="col-md-2 control-label">'.dr_lang('选项列表').'</label>
				<div class="col-md-9">
					<textarea class="form-control" name="data[setting][option][options]" style="height:150px;width:400px;">'.$option['options'].'</textarea>
					<span class="help-block">'.dr_lang('格式：选项名称|选项值[回车换行]选项名称2|值2....').'</span>
					<span class="help-block">'.dr_lang('选项值建议使用从1开始的数字，不得带符号，也可以省略不写').'</span>
				</div>
			</div>
			<div class="form-group">
				<label class="col-md-2 control-label">'.dr_lang('默认选中项').'</label>
				<div class="col-md-9">
					<label><input id="field_default_value" type="text" class="form-control" size="20" value="'.$option['value'].'" name="data[setting][option][value]"></label>
					<label>'.$this->member_field_select().'</label>
					<span class="help-block">'.dr_lang('用于字段为空时显示该填充值，并不会去主动变更数据库中的实际值；可以设置会员表字段，表示用当前登录会员信息来填充这个值').'</span>
				</div>
			</div>
			<div class="form-group">
                <label class="col-md-2 control-label">'.dr_lang('启用选项搜索').'</label>
                <div class="col-md-9">
                <input type="checkbox" name="data[setting][option][is_search]" '.($option['is_search'] ? 'checked' : '').' value="1" data-on-text="'.dr_lang('已开启').'" data-off-text="'.dr_lang('已关闭').'" data-on-color="success" data-off-color="danger" class="make-switch" data-size="small">
                
					<span class="help-block">'.dr_lang('当选项值过多时，可以在选择框中搜索选项值').'</span>
                </div>
            </div>
			'
			.
			$this->field_type($option['fieldtype'], $option['fieldlength'])
		];
	}

	/**
	 * 字段表单输入
	 *
	 * @return  string
	 */
	public function input($field, $value = '') {

		// 字段禁止修改时就返回显示字符串
		if ($this->_not_edit($field, $value)) {
			return $this->show($field, $value);
		}

		// 字段存储名称
		$name = $field['fieldname'];

		// 字段显示名称
		$text = ($field['setting']['validate']['required'] ? '<span class="required" aria-required="true"> * </span>' : '').dr_lang($field['name']);

		// 表单附加参数
		$attr = $field['setting']['validate']['formattr'];

		// 字段提示信息
		$tips = ($name == 'title' && APP_DIR) || $field['setting']['validate']['tips'] ? '<span class="help-block" id="dr_'.$field['fieldname'].'_tips">'.$field['setting']['validate']['tips'].'</span>' : '';

		// 是否必填
		$required =  $field['setting']['validate']['required'] ? ' required="required"' : '';

		// 字段默认值
		$value = dr_strlen($value) ? $value : $this->get_default_value($field['setting']['option']['value']);

		$str = '<label><select '.$required.' class="'.(isset($field['setting']['option']['is_search']) && $field['setting']['option']['is_search'] ? 'bs-select' : '').' form-control '.$field['setting']['option']['css'].'" '.(isset($field['setting']['option']['is_search']) && $field['setting']['option']['is_search'] ? ' data-live-search="true" ' : '').' name="data['.$name.']" id="dr_'.$name.'" '.$attr.' >';

		// 表单选项
		$options = dr_format_option_array($field['setting']['option']['options']);
		if ($options) {
            foreach ($options as $v => $n) {
				$str.= '<option value="'.$v.'" '.($v == $value ? ' selected' : '').'>'.dr_lang($n).'</option>';
			}
		}

		$str.= '</select></label>'.$tips;

		if (isset($field['setting']['option']['is_search']) && $field['setting']['option']['is_search']) {
            // 防止重复加载JS
            if (!$this->is_load_js($field['fieldtype'])) {
                $str.= $this->get_select_search_code();
            }
        }

		return $this->input_format($name, $text, $str);
	}



    /**
     * 字段表单显示
     *
     * @param	string	$field	字段数组
     * @param	array	$value	值
     * @return  string
     */
    public function show($field, $value = null) {

        $options = dr_format_option_array($field['setting']['option']['options']);

        $str = '<div class="form-control-static"> '.(isset($options[$value]) ? dr_lang($options[$value]) : dr_lang('未选择')).' </div>';

        return $this->input_format($field['fieldname'], $field['name'], $str);
    }
	
}