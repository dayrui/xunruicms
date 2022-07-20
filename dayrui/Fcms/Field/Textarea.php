<?php namespace Phpcmf\Field;
/**
 * {{www.xunruicms.com}}
 * {{迅睿内容管理框架系统}}
 * 本文件是框架系统文件，二次开发时不可以修改本文件，可以通过继承类方法来重写此文件
 **/

class Textarea extends \Phpcmf\Library\A_Field {

	/**
     * 构造函数
     */
    public function __construct(...$params) {
        parent::__construct(...$params);
        $this->fieldtype = TRUE;
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
					<textarea id="field_default_value" style="width: 90%;height: 100px;" class="form-control" name="data[setting][option][value]">'.$option['value'].'</textarea>
					<p><label>'.$this->member_field_select().'</label>
					<span class="help-block">'.dr_lang('用于字段为空时显示该填充值，并不会去主动变更数据库中的实际值；可以设置会员表字段，表示用当前登录会员信息来填充这个值').'</span></p>
				</div>
			</div>
			'.$this->field_type($option['fieldtype'], $option['fieldlength']),
			'<div class="form-group">
				<label class="col-md-2 control-label">'.dr_lang('控件宽度').'</label>
				<div class="col-md-9">
					<label><input type="text" class="form-control" size="10" name="data[setting][option][width]" value="'.$option['width'].'"></label>
					<span class="help-block">'.dr_lang('[整数]表示固定宽度；[整数%]表示百分比').'</span>
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
        <div class="scroller" style="width:'.(\Phpcmf\Service::IS_MOBILE_USER() ? '100%' : ($field['setting']['option']['width'] ? ($field['setting']['option']['width'].(is_numeric($field['setting']['option']['width']) ? 'px' : '')) : '400px')).';height:'.($field['setting']['option']['height'] ? $field['setting']['option']['height'] : '100').'px" data-always-visible="1" data-rail-visible="1">
        '.nl2br(htmlentities((string)$value)).'                
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
		$text = ($field['setting']['validate']['required'] ? '<span class="required" aria-required="true"> * </span>' : '').dr_lang($field['name']);

		// 表单宽度设置
		$width = \Phpcmf\Service::IS_MOBILE_USER() ? '100%' : ($field['setting']['option']['width'] ? $field['setting']['option']['width'] : '100%');

		// 表单附加参数
		$attr = $field['setting']['validate']['formattr'];

		// 字段提示信息
		$tips = ($name == 'title' && APP_DIR) || $field['setting']['validate']['tips'] ? '<span class="help-block" id="dr_'.$field['fieldname'].'_tips">'.$field['setting']['validate']['tips'].'</span>' : '';

		// 表单高度设置
		$height = $field['setting']['option']['height'] ? $field['setting']['option']['height'] : '100';

		// 字段默认值
		$value = dr_strlen($value) ? $value : $this->get_default_value($field['setting']['option']['value']);

		$str = '<textarea class="form-control" style="height:'.$height.'px; width:'.$width.(is_numeric($width) ? 'px' : '').';" name="data['.$name.']" id="dr_'.$name.'" '.$attr.'>'.$value.'</textarea>';

		return $this->input_format($name, $text, $str.$tips);
	}

}