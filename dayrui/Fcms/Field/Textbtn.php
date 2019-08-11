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
class Textbtn extends \Phpcmf\Library\A_Field {
	
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


		$style = '
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
		<div class="form-group">
			<label class="col-md-2 control-label">'.dr_lang('图标样式').'</label>
			<div class="col-md-9">
				<label><input type="text" class="form-control" size="10" name="data[setting][option][icon]" value="'.$option['icon'].'"></label>
				<span class="help-block">'.dr_lang('例如: fa fa-user').'</span>
			</div>
		</div>
		<div class="form-group">
			<label class="col-md-2 control-label">'.dr_lang('图标名称').'</label>
			<div class="col-md-9">
				<label><input type="text" class="form-control" size="10" name="data[setting][option][name]" value="'.$option['name'].'"></label>
				
			</div>
		</div>
		<div class="form-group">
			<label class="col-md-2 control-label">'.dr_lang('点击JS函数').'</label>
			<div class="col-md-9">
				<input type="text" class="form-control Large" size="10" name="data[setting][option][func]" value="'.$option['func'].'">
				<span class="help-block">'.dr_lang('单击按钮时执行的js函数').'</span>
			</div>
		</div>
		';


		$option = $this->field_type($option['fieldtype'], $option['fieldlength']).'
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

		if ($field['setting']['option']['extend_field'] && $field['setting']['option']['extend_function']) {
			// 扩展字段函数
			list($a, $method) = explode(':', $field['setting']['option']['extend_function']);
			$obj = \Phpcmf\Service::M($a);
			if (method_exists($obj, $method)) {
                if (IS_ADMIN && isset($_POST['no_author']) && $_POST['no_author']
                    && $field['setting']['option']['extend_function'] == 'member:uid') {
                    // 不验证会员就不变更uid
                } else {
                    \Phpcmf\Service::L('Field')->data[$field['ismain']][$field['setting']['option']['extend_field']] = call_user_func([$obj, $method], $value);
                }

			} else {
				log_message('error', '扩展函数方法 '.$field['setting']['option']['extend_function'].' 不存在！');
			}
		}

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
		$tips = $field['setting']['validate']['tips'] ? '<span class="help-block" id="dr_'.$field['fieldname'].'_tips">'.$field['setting']['validate']['tips'].'</span>' : '';

		// 当字段必填时，加入html5验证标签
		$required =  $field['setting']['validate']['required'] ? ' required="required"' : '';

		// 按钮颜色
		$color = $field['setting']['option']['color'] ? $field['setting']['option']['color'] : 'default';
		
		// 函数
		$func = $field['setting']['option']['func'] ? $field['setting']['option']['func'] : 'dr_diy_func';

		// 字段默认值
		$value = strlen($value) ? $value : $this->get_default_value($field['setting']['option']['value']);

		$ipt = '<input class="form-control '.$field['setting']['option']['css'].'" type="text" name="data['.$field['fieldname'].']" id="dr_'.$field['fieldname'].'" value="'.$value.'" '.$required.' '.$attr.' />';
		$str = '
		 <div class="input-group" '.$style.'>
				'.$ipt.'
				<span class="input-group-btn">
					<a class="btn btn-success '.$color.'" href="javascript:'.$func.'(\''.$name.'\');" ><i class="'.dr_icon($field['setting']['option']['icon']).'" /></i> '.dr_lang($field['setting']['option']['name']).'</a>
				</span>
			</div>
		';



		return $this->input_format($field['fieldname'], $text, $str.$tips);
	}
	
}