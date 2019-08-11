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


class Color extends \Phpcmf\Library\A_Field {
	
	/**
     * 构造函数
     */
    public function __construct(...$params) {
        parent::__construct(...$params);
		$this->fieldtype = ['VARCHAR' => 30];
		$this->defaulttype = 'VARCHAR';
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
				<label class="col-md-2 control-label">'.dr_lang('附加到指定字段').'</label>
				<div class="col-md-9">
					<label><input type="text" class="form-control" size="10" name="data[setting][option][field]" value="'.$option['field'].'"></label>
					<span class="help-block">'.dr_lang('对文本类型字段有效,会实时变动颜色').'</span>
				</div>
			</div>
			<div class="form-group">
				<label class="col-md-2 control-label">'.dr_lang('默认填充值').'</label>
				<div class="col-md-9">
					<label><input id="field_default_value" type="text" class="form-control" size="20" value="'.$option['value'].'" name="data[setting][option][value]"></label>
					<label>'.$this->member_field_select().'</label>
					<span class="help-block">'.dr_lang('也可以设置会员表字段，表示用当前登录会员信息来填充这个值').'</span>
				</div>
			</div>
				',
			'
		<div class="form-group">
			<label class="col-md-2 control-label">'.dr_lang('控件宽度').'</label>
			<div class="col-md-9">
				<label><input type="text" class="form-control" size="10" name="data[setting][option][width]" value="'.$option['width'].'"></label>
				<span class="help-block">'.dr_lang('[整数]表示固定宽带；[整数%]表示百分比').'</span>
			</div>
		</div>'
		];
	}
	
	/**
	 * 字段输出
	 */
	public function output($value) {
		return $value;
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


		// 字段提示信息
		$tips = $field['setting']['validate']['tips'] ? '<span class="help-block" id="dr_'.$name.'_tips">'.$field['setting']['validate']['tips'].'</span>' : '';

		// 表单宽度设置
		$width = \Phpcmf\Service::_is_mobile() ? '100%' : ($field['setting']['option']['width'] ? $field['setting']['option']['width'] : 200);

		// 风格
		$style = 'style="width:'.$width.(is_numeric($width) ? 'px' : '').';"';

		// 字段默认值
		$value = $value ? $value : $this->get_default_value($field['setting']['option']['value']);

		$str = '';

		// 加载js
		if (!defined('PHPCMF_FIELD_COLOR')) {
			$str.= '
			<link href="'.ROOT_THEME_PATH.'assets/global/plugins/bootstrap-colorpicker/css/colorpicker.css" rel="stylesheet" type="text/css" />
        	<link href="'.ROOT_THEME_PATH.'assets/global/plugins/jquery-minicolors/jquery.minicolors.css" rel="stylesheet" type="text/css" />
			';
			$str.= '<script type="text/javascript" src="'.ROOT_THEME_PATH.'assets/global/plugins/bootstrap-colorpicker/js/bootstrap-colorpicker.js"></script>';
			$str.= '<script type="text/javascript" src="'.ROOT_THEME_PATH.'assets/global/plugins/jquery-minicolors/jquery.minicolors.min.js"></script>';
			define('PHPCMF_FIELD_COLOR', 1);//防止重复加载JS
		}

		$str.= '
		<input type="text" class="form-control color '.$field['setting']['option']['css'].'" data-control="brightness" name="data['.$name.']" id="dr_'.$name.'" '.$style.' value="'.$value.'" >';
		$str.= '
		<script type="text/javascript">
		$(function(){
			$("#dr_'.$name.'").minicolors({
                control: $("#dr_'.$name.'").attr("data-control") || "hue",
                defaultValue: $("#dr_'.$name.'").attr("data-defaultValue") || "",
                inline: "true" === $("#dr_'.$name.'").attr("data-inline"),
                letterCase: $("#dr_'.$name.'").attr("data-letterCase") || "lowercase",
                opacity: $("#dr_'.$name.'").attr("data-opacity"),
                position: $("#dr_'.$name.'").attr("data-position") || "bottom left",
                change: function(t, o) {
                    t && (o && (t += ", " + o), "object" == typeof console && console.log(t));
                    '.($field['setting']['option']['field'] ? '$("#dr_'.$field['setting']['option']['field'].'").css("color", $("#dr_'.$name.'").val());' : '').'
                },
                theme: "bootstrap"
            });
		});
		</script>';

		return $this->input_format($name, $text, $str.$tips);
	}

    /**
     * 字段表单显示
     *
     * @param	string	$field	字段数组
     * @param	array	$value	值
     * @return  string
     */
    public function show($field, $value = null) {


        return $this->input_format($field['fieldname'], $field['name'], '<div class="form-control-static"><span style="color:'.$value.'" class="label label-danger"> &nbsp;&nbsp;&nbsp; </span></div>');
    }
	
}