<?php namespace Phpcmf\Field;

/* *
 *
 * Copyright [2018] [李睿]
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
 * 本文件是框架系统文件，二次开发时不建议修改本文件
 *
 * */


class Checkbox extends \Phpcmf\Library\A_Field  {
	
	/**
     * 构造函数
     */
    public function __construct(...$params) {
        parent::__construct(...$params);
        $this->close_xss = 1;
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

		$option['options'] = isset($option['options']) ? $option['options'] : 'name1|value1'.PHP_EOL.'name2|value2';
		
		return [
			'
			<div class="form-group">
				<label class="col-md-2 control-label">'.dr_lang('选项列表').'</label>
				<div class="col-md-9">
					<textarea class="form-control" name="data[setting][option][options]" style="height:150px;width:400px;">'.$option['options'].'</textarea>
					<span class="help-block">'.dr_lang('格式：选项名称|选项值[回车换行]选项名称2|值2....').'</span>
				</div>
			</div>
			<div class="form-group">
				<label class="col-md-2 control-label">'.dr_lang('默认选中项').'</label>
				<div class="col-md-9">
					<label><input id="field_default_value" type="text" class="form-control" size="20" value="'.$option['value'].'" name="data[setting][option][value]"></label>
					<label>'.$this->member_field_select().'</label>
					<span class="help-block">'.dr_lang('默认选中项，多个选中项用|分隔').'</span>
				</div>
			</div>'
			,
			'
			<div class="form-group">
				<label class="col-md-2 control-label">'.dr_lang('显示格式').'</label>
				<div class="col-md-9">
					<div class="mt-radio-inline">
						<label class="mt-radio  mt-radio-outline">
							<input type="radio" name="data[setting][option][show_type]" value="0" '.(!$option['option']['show_type'] ? 'checked' : '').'> '.dr_lang('横排显示').'
							<span></span>
						</label>
						<label class="mt-radio  mt-radio-outline">
							<input type="radio" name="data[setting][option][show_type]" value="1" '.($option['option']['show_type'] ? 'checked' : '').'> '.dr_lang('竖排显示').'
							<span></span>
						</label>
					</div>
					
				</div>
			</div>
			'
		];
	}
	
	/**
	 * 字段入库值
	 */
	public function insert_value($field) {
		\Phpcmf\Service::L('Field')->data[$field['ismain']][$field['fieldname']] = dr_array2string(\Phpcmf\Service::L('Field')->post[$field['fieldname']]);
	}
	
	/**
	 * 字段入库值
	 */
	public function output($value) {
		return dr_string2array($value);
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
		$text = ($field['setting']['validate']['required'] ? '<span class="required" aria-required="true"> * </span>' : '').$field['name'];

		// 表单附加参数
		$attr = $field['setting']['validate']['formattr'];

		// 字段提示信息
		$tips = ($name == 'title' && APP_DIR) || $field['setting']['validate']['tips'] ? '<span class="help-block" id="dr_'.$field['fieldname'].'_tips">'.$field['setting']['validate']['tips'].'</span>' : '';

		// 字段默认值
		if ($value) {
			$value = dr_string2array($value);
		} elseif ($field['setting']['option']['value']) {
			$value = $this->get_default_value($field['setting']['option']['value']);
			$value = is_array($value) ? $value : @explode('|', $value);
		} else {
			$value = null;
		}

        $str = '';

		// 显示方式
		$show_type = (int)$field['setting']['option']['show_type'];

		// 表单选项
		$options = dr_format_option_array($field['setting']['option']['options']);
		if ($options) {
            foreach ($options as $v => $n) {
				$s = is_array($value) && in_array($v, $value) ? ' checked' : '';
				$kj = '<input type="checkbox" name="data['.$name.'][]" value="'.$v.'" '.$s.' '.$attr.' />';
				$str.= '<label class="mt-checkbox mt-checkbox-outline">'.$kj.' '.$n.' <span></span> </label>';
			}
		}
		
		return $this->input_format($name, $text, '<div class="'.(!$show_type ? 'mt-checkbox-inline' : 'mt-checkbox-list').'">'.$str.'</div>'.$tips);
	}

    /**
     * 字段表单显示
     *
     * @param	string	$field	字段数组
     * @param	array	$value	值
     * @return  string
     */
    public function show($field, $value = null) {

        $str = '';
        $value = dr_string2array($value);
        $options = dr_format_option_array($field['setting']['option']['options']);
        if ($options && $value) {
            foreach ($options as $v => $n) {
                is_array($value) && in_array($v, $value) && $str.= '<label class="label label-default"> '.$n.' </label>&nbsp;&nbsp;&nbsp;';
            }
        }


        return $this->input_format($field['fieldname'], $field['name'], '<div class="form-control-static">'.$str.'</div>');
    }
}