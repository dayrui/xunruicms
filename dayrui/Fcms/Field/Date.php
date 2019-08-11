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


class Date extends \Phpcmf\Library\A_Field {

    /**
     * 构造函数
     */
    public function __construct(...$params) {
        parent::__construct(...$params);
        $this->fieldtype = ['INT' => 10];
        $this->defaulttype = 'INT';
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
				<label class="col-md-2 control-label">'.dr_lang('字段格式').'</label>
				<div class="col-md-9">
					<div class="mt-radio-inline">
						<label class="mt-radio  mt-radio-outline">
							<input type="radio" name="data[setting][option][format2]" value="0" '.(!$option['option']['format2'] ? 'checked' : '').'> '.dr_lang('日期时间格式').'
							<span></span>
						</label>
						<label class="mt-radio  mt-radio-outline">
							<input type="radio" name="data[setting][option][format2]" value="1" '.($option['option']['format2'] ? 'checked' : '').'> '.dr_lang('日期格式').'
							<span></span>
						</label>
					</div>
					
				</div>
			</div>
			<div class="form-group">
				<label class="col-md-2 control-label">'.dr_lang('图标显示').'</label>
				<div class="col-md-9">
					<div class="mt-radio-inline">
						<label class="mt-radio  mt-radio-outline">
							<input type="radio" name="data[setting][option][is_left]" value="0" '.(!$option['option']['is_left'] ? 'checked' : '').'> '.dr_lang('左侧图标').'
							<span></span>
						</label>
						<label class="mt-radio  mt-radio-outline">
							<input type="radio" name="data[setting][option][is_left]" value="1" '.($option['option']['is_left'] ? 'checked' : '').'> '.dr_lang('右侧图标').'
							<span></span>
						</label>
					</div>
					
				</div>
			</div>

			<div class="form-group">
				<label class="col-md-2 control-label">'.dr_lang('默认存储值').'</label>
				<div class="col-md-9">
					<label><input id="field_default_value" type="text" class="form-control" size="20" value="'.$option['value'].'" name="data[setting][option][value]"></label>
					<label>'.$this->member_field_select().'</label>
					<span class="help-block">'.dr_lang('也可以设置会员表字段，表示用当前登录会员信息来填充这个值').'</span>
				</div>
			</div>
			'.$this->field_type($option['fieldtype'], $option['fieldlength'])

            ,

            '
			<div class="form-group">
				<label class="col-md-2 control-label">'.dr_lang('控件宽度').'</label>
				<div class="col-md-9">
					<label><input type="text" class="form-control" size="10" name="data[setting][option][width]" value="'.$option['width'].'"></label>
					<span class="help-block">'.dr_lang('[整数]表示固定宽带；[整数%]表示百分比').'</span>
				</div>
			</div>
			<div class="form-group">
				<label class="col-md-2 control-label">'.dr_lang('按钮颜色').'</label>
				<div class="col-md-9">
					<label>'.$this->_color_select('color', $option['color']).'</label>
				</div>
			</div>
			'
        ];
    }

    /**
     * 创建sql语句
     */
    public function create_sql($name, $option, $cname) {
        // 无符号int 10位
        $sql = 'ALTER TABLE `{tablename}` ADD `'.$name.'` INT( 10 ) UNSIGNED NULL COMMENT \''.$cname.'\'';
        return $sql;
    }

    /**
     * 字段输出
     */
    public function output($value) {
        return dr_date($value, null, 'red');
    }

    /**
     * 字段入库值
     *
     * @param	array	$field	字段信息
     * @return  void
     */
    public function insert_value($field) {
        \Phpcmf\Service::L('Field')->data[$field['ismain']][$field['fieldname']] = strtotime(\Phpcmf\Service::L('Field')->post[$field['fieldname']]);
    }

    /**
     * 字段表单输入
     *
     * @return  string
     */
    public function input($field, $value = 0) {

        // 字段存储名称
        $name = $field['fieldname'];

        // 字段禁止修改时就返回显示字符串
        if ($this->_not_edit($field, $value)) {
            return $this->show($field, $value);
        }

        // 字段显示名称
        $text = ($field['setting']['validate']['required'] ? '<span class="required" aria-required="true"> * </span>' : '').$field['name'];

        // 表单宽度设置
        $width = \Phpcmf\Service::_is_mobile() ? '100%' : ($field['setting']['option']['width'] ? $field['setting']['option']['width'] : 200);

        // 风格
        $style = 'style="width:'.$width.(is_numeric($width) ? 'px' : '').';"';

        // 表单附加参数
        $attr = $field['setting']['validate']['formattr'];

        // 按钮颜色
        $color = $field['setting']['option']['color'] ? $field['setting']['option']['color'] : 'default';

        // 字段提示信息
        $tips = ($name == 'title' && APP_DIR) || $field['setting']['validate']['tips'] ? '<span class="help-block" id="dr_'.$field['fieldname'].'_tips">'.$field['setting']['validate']['tips'].'</span>' : '';

        // 格式显示
        $format = (int)$field['setting']['option']['format2'];

        // 是否必填
        $required =  $field['setting']['validate']['required'] ? ' required="required"' : '';

        $str = '';
        if (!defined('PHPCMF_FIELD_DATE')) {
            $str.= '
			<link href="'.ROOT_THEME_PATH.'assets/global/plugins/bootstrap-daterangepicker/daterangepicker.min.css" rel="stylesheet" type="text/css" />
			<link href="'.ROOT_THEME_PATH.'assets/global/plugins/bootstrap-datepicker/css/bootstrap-datepicker.min.css" rel="stylesheet" type="text/css" />
			<link href="'.ROOT_THEME_PATH.'assets/global/plugins/bootstrap-datetimepicker/css/bootstrap-datetimepicker.min.css" rel="stylesheet" type="text/css" />
			
        	<script src="'.ROOT_THEME_PATH.'assets/global/plugins/moment.min.js" type="text/javascript"></script>
			<script src="'.ROOT_THEME_PATH.'assets/global/plugins/bootstrap-datepicker/js/bootstrap-datepicker.finecms.js" type="text/javascript"></script>
			<script src="'.ROOT_THEME_PATH.'assets/global/plugins/bootstrap-datetimepicker/js/bootstrap-datetimepicker.finecms.js" type="text/javascript"></script>
			';
            define('PHPCMF_FIELD_DATE', 1);//防止重复加载JS
        }

        // 字段默认值
        !$value && $value = $this->get_default_value($field['setting']['option']['value']);
        if ($value == 'SYS_TIME') {
            $value = SYS_TIME;
        } elseif (strpos($value, '-') !== false) {
            $value = strtotime($value);
        }

        $value = $format ? dr_date($value, 'Y-m-d') : dr_date($value, 'Y-m-d H:i:s');
        $shuru = '<input name="data['.$name.']" type="text" '.$style.' value="'.$value.'" '.$required.' class="form-control '.$field['setting']['option']['css'].'">';
        $tubiao = '<span class="input-group-btn">
					<button class="btn '.$color.' date-set" type="button">
						<i class="fa fa-calendar"></i>
					</button>
				</span>';
        $str.= '<div class="input-group date field_date_'.$name.'">';
        $str.= $field['setting']['option']['is_left'] ? $tubiao.$shuru : $shuru.$tubiao;
        $str.= '</div>';

        if ($format) {
            // 日期
            $str.= '
			<script>
			$(function(){
				$(".field_date_'.$name.'").datepicker({
					isRTL: false,
					format: "yyyy-mm-dd",
					showMeridian: true,
					autoclose: true,
					pickerPosition: "bottom-right",
				});
			});
			</script>
			';
        } else {
            // 日期 + 时间
            $str.= '
			<script>
			$(function(){
				$(".field_date_'.$name.'").datetimepicker({
					isRTL: false,
					format: "yyyy-mm-dd hh:ii:ss",
					showMeridian: true,
					autoclose: true,
					pickerPosition: "bottom-right",
					todayBtn: true
				});
			});
			</script>
			';
        }

        APP_DIR && $name == 'updatetime' && $str.= '<label><input name="no_time" type="checkbox" value="1" /> '.dr_lang('不更新').'</label>';

        $str.= $tips;

        return $this->input_format($name, $text, '<span class="form-date input-group">'.$str.'</span>');
    }

    /**
     * 字段表单显示
     *
     * @param	string	$field	字段数组
     * @param	array	$value	值
     * @return  string
     */
    public function show($field, $value = null) {

        $value = (int)$field['setting']['option']['format2'] ? dr_date($value, 'Y-m-d') : dr_date($value, 'Y-m-d H:i:s');

        return $this->input_format($field['fieldname'], $field['name'], '<div class="form-control-static"><span> '.$value.' </span></div>');
    }
}