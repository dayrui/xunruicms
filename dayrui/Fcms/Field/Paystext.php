<?php namespace Phpcmf\Field;
/**
 * {{www.xunruicms.com}}
 * {{迅睿内容管理框架系统}}
 * 本文件是框架系统文件，二次开发时不可以修改本文件，可以通过继承类方法来重写此文件
 **/

class PaysText extends \Phpcmf\Library\A_Field {

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
        return ['<div class="form-group">
			<label class="col-md-2 control-label">'.dr_lang('字段作用').' </label>
			<div class="col-md-9"><label class="form-control-static" style="color: red">'.dr_lang('本类型字段需要在Pays类型的字段中启用后才能生效').'</label></div>
		</div>'.$this->field_type($option['fieldtype'], $option['fieldlength']).'
		<div class="form-group">
			<label class="col-md-2 control-label">'.dr_lang('默认填充值').'</label>
			<div class="col-md-9">
				<label><input id="field_default_value" type="text" class="form-control" size="20" value="'.$option['value'].'" name="data[setting][option][value]"></label>
				<label>'.$this->member_field_select().'</label>
				<span class="help-block">'.dr_lang('用于字段为空时显示该填充值，并不会去主动变更数据库中的实际值；可以设置会员表字段，表示用当前登录会员信息来填充这个值').'</span>
			</div>
		</div>', '<div class="form-group">
			<label class="col-md-2 control-label">'.dr_lang('控件宽度').'</label>
			<div class="col-md-9">
				<label><input type="text" class="form-control" size="10" name="data[setting][option][width]" value="'.$option['width'].'"></label>
				<span class="help-block">'.dr_lang('[整数]表示固定宽度；[整数%]表示百分比').'</span>
			</div>
		</div>'];
    }

    /**
     * 字段入库值
     *
     * @param	array	$field	字段信息
     * @return  void
     */
    public function insert_value($field) {

    }

    /**
     * 字段表单输入
     *
     * @param	string	$field	字段数组
     * @param	array	$value	值
     * @return  string
     */
    public function input($field, $value = null) {
        return '';
    }

}