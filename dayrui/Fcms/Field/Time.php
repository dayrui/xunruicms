<?php namespace Phpcmf\Field;
/**
 * {{www.xunruicms.com}}
 * {{迅睿内容管理框架系统}}
 * 本文件是框架系统文件，二次开发时不可以修改本文件，可以通过继承类方法来重写此文件
 **/

class Time extends \Phpcmf\Library\A_Field {

    /**
     * 构造函数
     */
    public function __construct(...$params) {
        parent::__construct(...$params);
        $this->fieldtype = ['VARCHAR' => 100];
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
				<label class="col-md-2 control-label">'.dr_lang('字段格式').'</label>
				<div class="col-md-9">
					<div class="mt-radio-inline">
						<label class="mt-radio  mt-radio-outline">
							<input type="radio" name="data[setting][option][format2]" value="0" '.(!$option['option']['format2'] ? 'checked' : '').'> '.dr_lang('时分格式').'
							<span></span>
						</label>
						<label class="mt-radio  mt-radio-outline">
							<input type="radio" name="data[setting][option][format2]" value="1" '.($option['option']['format2'] ? 'checked' : '').'> '.dr_lang('时分秒格式').'
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
					<span class="help-block">'.dr_lang('用于字段为空时显示该填充值，并不会去主动变更数据库中的实际值；可以设置会员表字段，表示用当前登录会员信息来填充这个值').'</span>
				</div>
			</div>
			'.$this->field_type($option['fieldtype'], $option['fieldlength'])

            ,

            '
			<div class="form-group">
				<label class="col-md-2 control-label">'.dr_lang('控件宽度').'</label>
				<div class="col-md-9">
					<label><input type="text" class="form-control" size="10" name="data[setting][option][width]" value="'.$option['width'].'"></label>
					<span class="help-block">'.dr_lang('[整数]表示固定宽度；[整数%]表示百分比').'</span>
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
    public function create_sql($name, $option, $cname = '') {
        // 无符号int 10位
        $sql = 'ALTER TABLE `{tablename}` ADD `'.$name.'` VARCHAR( 100 ) DEFAULT NULL COMMENT \''.$cname.'\'';
        return $sql;
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
    public function input($field, $value = 0) {

        // 字段存储名称
        $name = $field['fieldname'];

        // 字段禁止修改时就返回显示字符串
        if ($this->_not_edit($field, $value)) {
            return $this->show($field, $value);
        }

        // 字段显示名称
        $text = ($field['setting']['validate']['required'] ? '<span class="required" aria-required="true"> * </span>' : '').dr_lang($field['name']);

        // 表单宽度设置
        $width = \Phpcmf\Service::IS_MOBILE_USER() ? '100%' : ($field['setting']['option']['width'] ? $field['setting']['option']['width'] : 100);

        // 风格
        $style = 'style="width:'.$width.(is_numeric($width) ? 'px' : '').';"';

        // 表单附加参数
        $attr = $field['setting']['validate']['formattr'];

        // 字段提示信息
        $tips = ($name == 'title' && APP_DIR) || $field['setting']['validate']['tips'] ? '<span class="help-block" id="dr_'.$field['fieldname'].'_tips">'.$field['setting']['validate']['tips'].'</span>' : '';

        // 格式显示
        $format = (int)$field['setting']['option']['format2'] ? 'H:i:s' : 'H:i';
        // 按钮颜色
        $color = $field['setting']['option']['color'] ? $field['setting']['option']['color'] : 'default';

        // 是否必填
        $required =  $field['setting']['validate']['required'] ? ' required="required"' : '';

        $str = '';
        if (!$this->is_load_js($field['fieldtype'])) {
            $str.= '
			<link href="'.ROOT_THEME_PATH.'assets/global/plugins/bootstrap-timepicker/css/bootstrap-timepicker.min.css?v='.CMF_UPDATE_TIME.'" rel="stylesheet" type="text/css" />
			<script src="'.ROOT_THEME_PATH.'assets/global/plugins/bootstrap-timepicker/js/bootstrap-timepicker.min.js?v='.CMF_UPDATE_TIME.'" type="text/javascript"></script>
			';
            $this->set_load_js($field['fieldtype'], 1);
        }

        // 字段默认值
        !$value && $value = $this->get_default_value($field['setting']['option']['value']);
        if ($value == 'SYS_TIME') {
            $value = dr_date(SYS_TIME, $format);
        }

        $str.= '<div class="input-group ">';
		$shuru = '<input name="data['.$name.']" type="text" '.$style.' value="'.$value.'" '.$required.' class="form-control timepicker field_time_'.$name.' '.$field['setting']['option']['css'].'">';
        $tubiao = '<span class="input-group-btn"><button class="btn  '.$color.'" type="button">
					<i class="fa fa-clock-o"></i>
				</button>
			</span>';
		$str.= $field['setting']['option']['is_left'] ? $tubiao.$shuru : $shuru.$tubiao;
		 
		$str.= '</div>';
        $js = \Phpcmf\Service::L('js_packer');
        $str.= $js->pack('
			<script>
			$(function(){
				$(".field_time_'.$name.'").timepicker({
					autoclose: true,
					defaultTime:"'.($value ? $value : dr_date(SYS_TIME, $format)).'",
					minuteStep: 1,
					secondStep: 1,
					showSeconds: '.($format == 'H:i:s' ? 'true' : 'false').',
					showMeridian: false
				});
				$(".timepicker").parent(".input-group").on("click", ".input-group-btn", function(e){
					$(this).parent(".input-group").find(".timepicker").timepicker("showWidget");
				});
				$( document ).scroll(function(){
					$(".field_time_'.$name.'").timepicker("place");
				});
			});
			</script>
			', 0);
        $str.= $tips;

        return $this->input_format($name, $text, '<div class="form-date input-group">'.$str.'</div>');
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