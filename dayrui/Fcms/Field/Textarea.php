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
 * www.xunruicms.com
 *
 * */
class Textarea extends \Phpcmf\Library\A_Field {
	
	/**
     * 构造函数
     */
    public function __construct(...$params) {
        parent::__construct(...$params);
		$this->fieldtype = ['TEXT' => ''];
		$this->defaulttype = 'TEXT';
    }
	
	/**
	 * 字段相关属性参数
	 *
	 * @param	array	$value	值
	 * @return  string
	 */
	public function option($option) {


		return ['
			
			<div class="form-group">
				<label class="col-md-2 control-label">'.dr_lang('默认存储值').'</label>
				<div class="col-md-9">
					<label><input id="field_default_value" type="text" class="form-control" size="20" value="'.$option['value'].'" name="data[setting][option][value]"></label>
					<label>'.$this->member_field_select().'</label>
					<span class="help-block">'.dr_lang('也可以设置会员表字段，表示用当前登录会员信息来填充这个值').'</span>
				</div>
			</div>
			'.$this->field_type($option['fieldtype'], $option['fieldlength']),
			'<div class="form-group">
				<label class="col-md-2 control-label">'.dr_lang('控件宽度').'</label>
				<div class="col-md-9">
					<label><input type="text" class="form-control" size="10" name="data[setting][option][width]" value="'.$option['width'].'"></label>
					<span class="help-block">'.dr_lang('[整数]表示固定宽带；[整数%]表示百分比').'</span>
				</div>
			</div>
			<div class="form-group">
				<label class="col-md-2 control-label">'.dr_lang('控件高度').'</label>
				<div class="col-md-9">
					<label><input type="text" class="form-control" size="10" name="data[setting][option][height]" value="'.$option['height'].'"></label>
					<label>px</label>
				</div>
			</div>'
		];
	}


    /**
     * 字段显示
     *
     * @return  string
     */
    public function show($field, $value = null) {
        $html = '
        <div class="portlet  bordered light">
        <div class="portlet-body">
        <div class="scroller" style="width:'.(\Phpcmf\Service::_is_mobile() ? '100%' : ($field['setting']['option']['width'] ? ($field['setting']['option']['width'].(is_numeric($field['setting']['option']['width']) ? 'px' : '')) : '400px')).';height:'.($field['setting']['option']['height'] ? $field['setting']['option']['height'] : '100').'px" data-always-visible="1" data-rail-visible="1">
        '.nl2br(htmlentities($value)).'                
        </div>
        </div>
        </div>';
        return $this->input_format($field['fieldname'], $field['name'], $html);
    }
	
	/**
	 * 字段表单输入
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

		// 表单宽度设置
		$width = \Phpcmf\Service::_is_mobile() ? '100%' : ($field['setting']['option']['width'] ? $field['setting']['option']['width'] : '100%');

		// 表单附加参数
		$attr = $field['setting']['validate']['formattr'];

		// 字段提示信息
		$tips = ($name == 'title' && APP_DIR) || $field['setting']['validate']['tips'] ? '<span class="help-block" id="dr_'.$field['fieldname'].'_tips">'.$field['setting']['validate']['tips'].'</span>' : '';

		// 表单高度设置
		$height = $field['setting']['option']['height'] ? $field['setting']['option']['height'] : '100';

		// 字段默认值
		$value = strlen($value) ? $value : $this->get_default_value($field['setting']['option']['value']);

		$str = '<textarea class="form-control" style="height:'.$height.'px; width:'.$width.(is_numeric($width) ? 'px' : '').';" name="data['.$name.']" id="dr_'.$name.'" '.$attr.'>'.$value.'</textarea>';

		return $this->input_format($name, $text, $str.$tips);
	}
	
}