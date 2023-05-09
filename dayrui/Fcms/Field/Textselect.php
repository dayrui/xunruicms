<?php namespace Phpcmf\Field;
/**
 * {{www.xunruicms.com}}
 * {{迅睿内容管理框架系统}}
 * 本文件是框架系统文件，二次开发时不可以修改本文件，可以通过继承类方法来重写此文件
 **/

class Textselect extends \Phpcmf\Library\A_Field {
	
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

        $option['options'] = isset($option['options']) ? $option['options'] : '选项名称1'.PHP_EOL.'选项名称2';


		$style = '
		<div class="form-group">
			<label class="col-md-2 control-label">'.dr_lang('控件宽度').'</label>
			<div class="col-md-9">
				<label><input type="text" class="form-control" size="10" name="data[setting][option][width]" value="'.$option['width'].'"></label>
				<span class="help-block">'.dr_lang('[整数]表示固定宽度；[整数%]表示百分比').'</span>
			</div>
		</div>
		<div class="form-group">
			<label class="col-md-2 control-label">'.dr_lang('密码框模式').'</label>
			<div class="col-md-9">
				<input type="checkbox" name="data[setting][option][ispwd]" '.($option['ispwd'] ? 'checked' : '').' value="1"  data-on-text="'.dr_lang('开启').'" data-off-text="'.dr_lang('关闭').'" data-on-color="success" data-off-color="danger" class="make-switch" data-size="small">

				<span class="help-block">'.dr_lang('开启之后它将作为密码框来显示').'</span>
			</div>
		</div>
		
		';

		$option = $this->field_type($option['fieldtype'], $option['fieldlength']).'
<div class="form-group">
				<label class="col-md-2 control-label">'.dr_lang('选项列表').'</label>
				<div class="col-md-9">
					<textarea class="form-control" name="data[setting][option][options]" style="height:150px;width:400px;">'.$option['options'].'</textarea>
					<span class="help-block">'.dr_lang('格式：选项名称1[回车换行]选项名称2....').'</span>
				</div>
			</div>
		<div class="form-group">
			<label class="col-md-2 control-label">'.dr_lang('默认填充值').'</label>
			<div class="col-md-9">
				<label><input id="field_default_value" type="text" class="form-control" size="20" value="'.$option['value'].'" name="data[setting][option][value]"></label>
				<label>'.$this->member_field_select().'</label>
				<span class="help-block">'.dr_lang('用于字段为空时显示该填充值，并不会去主动变更数据库中的实际值；可以设置会员表字段，表示用当前登录会员信息来填充这个值').'</span>
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
        if (in_array($field['setting']['option']['fieldtype'], array('INT', 'TINYINT', 'SMALLINT'))) {
			\Phpcmf\Service::L('Field')->data[$field['ismain']][$field['fieldname']] = $value ? (int)$value : 0;
		} elseif (in_array($field['setting']['option']['fieldtype'], array('DECIMAL', 'FLOAT'))) {
			\Phpcmf\Service::L('Field')->data[$field['ismain']][$field['fieldname']] = $value ? (float)$value : 0;
		} elseif ($field['setting']['option']['fieldtype'] == 'MEDIUMINT') {
			\Phpcmf\Service::L('Field')->data[$field['ismain']][$field['fieldname']] = $value ? $value : 0;
		} elseif (dr_strlen($value) == 1 && $value == '0') {
            \Phpcmf\Service::L('Field')->data[$field['ismain']][$field['fieldname']] = '0';
        } else {
			\Phpcmf\Service::L('Field')->data[$field['ismain']][$field['fieldname']] = dr_htmlspecialchars($value);
		}
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
		$text = ($field['setting']['validate']['required'] ? '<span class="required" aria-required="true"> * </span>' : '').dr_lang($field['name']);

		// 表单宽度设置
		$width = \Phpcmf\Service::IS_MOBILE_USER() ? '100%' : ($field['setting']['option']['width'] ? $field['setting']['option']['width'] : 200);

		// 风格
		$style = 'style="width:'.$width.(is_numeric($width) ? 'px' : '').';"';

		// 表单附加参数
		$attr = $field['setting']['validate']['formattr'];

		// 字段提示信息
		$tips = $field['setting']['validate']['tips'] ? '<span class="help-block" id="dr_'.$field['fieldname'].'_tips">'.$field['setting']['validate']['tips'].'</span>' : '';

		// 当字段必填时，加入html5验证标签
		$required =  $field['setting']['validate']['required'] ? ' required="required"' : '';


		// 字段默认值
		$value = dr_strlen($value) ? $value : $this->get_default_value($field['setting']['option']['value']);


        $str = '';
        if (!$this->is_load_js($field['fieldtype'])) {
            $str.= '
			<link href="'.ROOT_THEME_PATH.'assets/global/plugins/jquery.editable-select/jquery.editable-select.min.css?v='.CMF_UPDATE_TIME.'" rel="stylesheet" type="text/css" />
			<script src="'.ROOT_THEME_PATH.'assets/global/plugins/jquery.editable-select/jquery.editable-select.min.js?v='.CMF_UPDATE_TIME.'" type="text/javascript"></script>
			';
            $this->set_load_js($field['fieldtype'], 1);
        }

		//$str = '<input class="form-control '.($field['setting']['validate']['required'] ? 'dr_required' : '').' '.$field['setting']['option']['css'].'" type="'.$type.'" name="data['.$field['fieldname'].']" id="dr_'.$field['fieldname'].'" value="'.$value.'" '.$style.' '.$required.' '.$attr.' />';

        $options = dr_format_option_array($field['setting']['option']['options']);
        $str.= '
		<select id="editable-select-'.$name.'" class="form-control es-input '.($field['setting']['validate']['required'] ? 'dr_required' : '').' '.$field['setting']['option']['css'].'" name="data['.$field['fieldname'].']" id="dr_'.$field['fieldname'].'" value="'.$value.'" '.$style.' '.$required.' '.$attr.'></select>
		
		<script type="text/javascript">
		$(function () {
            var data = '.json_encode($options).';
            $.each(data, function (i, r) {
                $("#editable-select-'.$name.'").append(\'<option>\' + r + \'</option>\');
            });
            $(\'#editable-select-'.$name.'\').editableSelect({
                effects: \'slide\',//点击的时候，下拉框的过渡效果  有default，slide，fade三个值，默认是default
                filter: false,//选择option以后，是否过滤  默认 true
                duration: \'fast\',//下拉选项框展示的过度动画速度
            });
        });
        </script>
		';

		return $this->input_format($field['fieldname'], $text, $str.$tips);
	}

}