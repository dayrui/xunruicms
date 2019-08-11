<?php namespace Phpcmf\Field;

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
 * 本文件是框架系统文件，二次开发时不建议修改本文件
 *
 * */


class Redirect extends \Phpcmf\Library\A_Field {
	
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
		$option['width'] = isset($option['width']) ? $option['width'] : 400;
		return ['<div class="form-group">
				<label class="col-md-2 control-label">'.dr_lang('适应范围').'</label>
				<div class="col-md-9">
					<p class="form-control-static">'.dr_lang('此字段只能用于模块内容自定义字段').'</p>
				</div>
			</div>', '
		<div class="form-group">
				<label class="col-md-2 control-label">'.dr_lang('控件宽度').'</label>
				<div class="col-md-9">
					<label><input type="text" class="form-control" size="10" name="data[setting][option][width]" value="'.$option['width'].'"></label>
					<span class="help-block">'.dr_lang('[整数]表示固定宽带；[整数%]表示百分比').'</span>
				</div>
			</div>
				'];
	}
	
	/**
	 * 字段入库值
	 *
	 * @param	array	$field	字段信息
	 * @return  void
	 */
	public function insert_value($field) {
	
		$value = \Phpcmf\Service::L('Field')->post[$field['fieldname']];
		$value && $value = stripos($value, 'https://') === 0 || stripos($value, 'http://') === 0 ? $value : 'http://'.$value;
        \Phpcmf\Service::L('Field')->data[$field['ismain']][$field['fieldname']] = $value;
	}

    /**
     * 字段表单输入
     *
     * @param	string	$field	字段数组
     * @param	array	$value	值
     * @return  string
     */
    public function input($field, $value = null) {

        // 字段禁止修改时就返回显示字符串
        if ($this->_not_edit($field, $value)) {
            return $this->show($field, $value);
        }

        // 字段存储名称
        $name = $field['fieldname'];

        // 字段显示名称
        $text = ($field['setting']['validate']['required'] ? '<span class="required" aria-required="true"> * </span>' : '').$field['name'];

        // 表单宽度设置
        $width = \Phpcmf\Service::_is_mobile() ? '100%' : ($field['setting']['option']['width'] ? $field['setting']['option']['width'] : 200);

        // 风格
        $style = 'style="width:'.$width.(is_numeric($width) ? 'px' : '').';"';

        // 表单附加参数
        $attr = $field['setting']['validate']['formattr'];

        // 字段提示信息
		$tips = isset($field['setting']['validate']['tips']) && $field['setting']['validate']['tips'] ? '<span class="help-block" id="dr_'.$name.'_tips">'.$field['setting']['validate']['tips'].'</span>' : '';

        // 字段默认值
		$value = strlen($value) ? $value : $this->get_default_value($field['setting']['option']['value']);

		// 当字段必填时，加入html5验证标签
        isset($field['setting']['validate']['required']) && $field['setting']['validate']['required'] == 1 && $attr.= ' required="required"';

		$str = '<input class="form-control" type="text" name="data['.$name.']" id="dr_'.$name.'" value="'.$value.'" '.$style.' '.$attr.' />'.$tips;
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


        return $this->input_format($field['fieldname'], $field['name'], '<div class="form-control-static"><a href="'.$value.'" target="_blank">'.$value.'</a></div>');
    }
	
}