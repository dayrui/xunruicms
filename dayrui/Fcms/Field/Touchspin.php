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
class Touchspin extends \Phpcmf\Library\A_Field {
	
	/**
     * 构造函数
     */
    public function __construct(...$params) {
        parent::__construct(...$params);
		$this->fieldtype = ['INT','TINYINT','SMALLINT','MEDIUMINT','DECIMAL','FLOAT'];
		$this->defaulttype = 'INT';
    }
	
	/**
	 * 字段相关属性参数
	 *
	 * @param	array	$value	值
	 * @return  string
	 */
	public function option($option) {


		$style = '
		<div class="form-group">
			<label class="col-md-2 control-label">'.dr_lang('控件宽度').'</label>
			<div class="col-md-9">
				<label><input type="text" class="form-control" size="10" name="data[setting][option][width]" value="'.$option['width'].'"></label>
				<span class="help-block">'.dr_lang('[整数]表示固定宽带；[整数%]表示百分比').'</span>
			</div>
		</div>
		<div class="form-group">
			<label class="col-md-2 control-label">'.dr_lang('显示模式').'</label>
			<div class="col-md-9">
				<input type="checkbox" name="data[setting][option][show]" '.($option['show'] ? 'checked' : '').' value="1"  data-on-text="'.dr_lang('按钮').'" data-off-text="'.dr_lang('箭头').'" data-on-color="success" data-off-color="danger" class="make-switch" data-size="small">
				<span class="help-block">'.dr_lang('按钮模式是左右两个加减按钮；箭头模式是左边上下箭头符号').'</span>
			</div>
		</div>
		<div class="form-group">
			<label class="col-md-2 control-label">'.dr_lang('加按钮颜色').'</label>
			<div class="col-md-9">
				<label>'.$this->_color_select('up', $option['up']).'</label>
			</div>
		</div>
		<div class="form-group">
			<label class="col-md-2 control-label">'.dr_lang('减按钮颜色').'</label>
			<div class="col-md-9">
				<label>'.$this->_color_select('down', $option['down']).'</label>
			</div>
		</div>
		
		';


		$option = '
		<div class="form-group">
			<label class="col-md-2 control-label">'.dr_lang('最大值').'</label>
			<div class="col-md-9">
				<label><input type="text" class="form-control" size="10" name="data[setting][option][max]" value="'.$option['max'].'"></label>
			</div>
		</div>
		<div class="form-group">
			<label class="col-md-2 control-label">'.dr_lang('最小值').'</label>
			<div class="col-md-9">
				<label><input type="text" class="form-control" size="10" name="data[setting][option][min]" value="'.$option['min'].'"></label>
			</div>
		</div>
		<div class="form-group">
			<label class="col-md-2 control-label">'.dr_lang('步长值').'</label>
			<div class="col-md-9">
				<label><input type="text" class="form-control" size="10" name="data[setting][option][step]" value="'.$option['step'].'"></label>
			</div>
		</div>
		'.$this->field_type($option['fieldtype'], $option['fieldlength']).'
		<div class="form-group">
			<label class="col-md-2 control-label">'.dr_lang('默认填充值').'</label>
			<div class="col-md-9">
				<label><input id="field_default_value" type="text" class="form-control" size="20" value="'.$option['value'].'" name="data[setting][option][value]"></label>
				<label>'.$this->member_field_select().'</label>
				<span class="help-block">'.dr_lang('也可以设置会员表字段，表示用当前登录会员信息来填充这个值').'</span>
			</div>
		</div>
		';

		return [$option, $style];
	}

    /**
     * 字段入库值
     *
     * @param	array	$field	字段信息
     * @return  void
     */
    public function insert_value($field) {

		// 格式化入库值
		$value = \Phpcmf\Service::L('Field')->post[$field['fieldname']];
		$value = in_array($field['setting']['option']['fieldtype'], ['DECIMAL', 'FLOAT']) ? ($value ? (float)$value : 0) : ($value ? (int)$value : 0);

		// 最大值判断
        $field['setting']['option']['max'] && $value = min($field['setting']['option']['max'], $value);

		// 最小值判断
        $field['setting']['option']['min'] && $value = max($field['setting']['option']['min'], $value);

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
		
		// 字段显示名称
		$text = ($field['setting']['validate']['required'] ? '<span class="required" aria-required="true"> * </span>' : '').$field['name'];

		// 表单宽度设置
		$width = \Phpcmf\Service::_is_mobile() ? '100%' : ($field['setting']['option']['width'] ? $field['setting']['option']['width'] : 200);

		// 风格
		$style = 'style="width:'.$width.(is_numeric($width) ? 'px' : '').';"';

		// 表单附加参数
		$attr = $field['setting']['validate']['formattr'];

		// 字段提示信息
		$tips = $field['setting']['validate']['tips'] ? '<span class="help-block" id="dr_'.$field['fieldname'].'_tips">'.$field['setting']['validate']['tips'].'</span>' : '';

		// 当字段必填时，加入html5验证标签
		$required =  $field['setting']['validate']['required'] ? ' required="required"' : '';

		// 按钮颜色
		$up = $field['setting']['option']['up'] ? $field['setting']['option']['up'] : 'default';
		$down = $field['setting']['option']['down'] ? $field['setting']['option']['down'] : 'default';

		// 字段默认值
		$value = strlen($value) ? $value : $this->get_default_value($field['setting']['option']['value']);

		$str = '';

		if (!defined('PHPCMF_FIELD_TOUCHSPIN')) {
			$str.= '
				
			<link href="'.ROOT_THEME_PATH.'assets/global/plugins/bootstrap-touchspin/bootstrap.touchspin.css" rel="stylesheet" type="text/css" />
			<script src="'.ROOT_THEME_PATH.'assets/global/plugins/fuelux/js/spinner.min.js" type="text/javascript"></script>
			<script src="'.ROOT_THEME_PATH.'assets/global/plugins/bootstrap-touchspin/bootstrap.touchspin.js" type="text/javascript"></script>

			';
			define('PHPCMF_FIELD_TOUCHSPIN', 1);//防止重复加载JS
		}

		!$field['setting']['option']['max'] && $field['setting']['option']['max'] = 999999999999999;
		!$field['setting']['option']['min'] && $field['setting']['option']['min'] = 0;

		$str.= '<div '.$style.'><input class="form-control '.$field['setting']['option']['css'].'" type="text" name="data['.$field['fieldname'].']" id="dr_'.$field['fieldname'].'" value="'.$value.'" '.$required.' '.$attr.' /></div>';
		$str.= '
<script type="text/javascript">
    $(function(){
        $("#dr_'.$field['fieldname'].'").TouchSpin({
            buttondown_class: "btn '.$down.'",
            buttonup_class: "btn '.$up.'",
            verticalbuttons: '.(!$field['setting']['option']['show'] ?  'true' : 'false').',
            step: '.$field['setting']['option']['step'].',
            min: '.$field['setting']['option']['min'].',
            max: '.$field['setting']['option']['max'].'
        });
    });
</script>
		';


		return $this->input_format($field['fieldname'], $text, $str.$tips);
	}
	
}