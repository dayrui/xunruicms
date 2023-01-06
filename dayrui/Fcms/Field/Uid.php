<?php namespace Phpcmf\Field;
/**
 * {{www.xunruicms.com}}
 * {{迅睿内容管理框架系统}}
 * 本文件是框架系统文件，二次开发时不可以修改本文件，可以通过继承类方法来重写此文件
 **/

class Uid extends \Phpcmf\Library\A_Field {
	
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


		$style = '
		<div class="form-group">
			<label class="col-md-2 control-label">'.dr_lang('控件宽度').'</label>
			<div class="col-md-9">
				<label><input type="text" class="form-control" size="10" name="data[setting][option][width]" value="'.$option['width'].'"></label>
				<span class="help-block">'.dr_lang('[整数]表示固定宽度；[整数%]表示百分比').'</span>
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
			<label class="col-md-2 control-label">'.dr_lang('按钮颜色').'</label>
			<div class="col-md-9">
				<label>'.$this->_color_select('color', $option['color']).'</label>
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

		return ['', $style];
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
		if ($value) {
            $value = \Phpcmf\Service::M('member')->uid($value);
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

        // 字段默认值
        if (dr_strlen($value)) {
            if (is_numeric($value)) {
                // 由uid获取用户名
                $user = \Phpcmf\Service::M('member')->username($value);
                if ($user) {
                    $value = $user;
                }
            }
        } else {
            $value = \Phpcmf\Service::C()->member ? \Phpcmf\Service::C()->member['username'] : '';
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

		// 按钮颜色
		$color = $field['setting']['option']['color'] ? $field['setting']['option']['color'] : 'default';

		$ipt = '<input class="form-control '.$field['setting']['option']['css'].'" type="text" name="data['.$field['fieldname'].']" id="dr_'.$field['fieldname'].'" value="'.$value.'" '.$required.' '.$attr.' />';
		if (IS_ADMIN) {
            $str = '
		 <div class="input-group" '.$style.'>
				'.$ipt.'
				<span class="input-group-btn">
					<a class="btn btn-success " style="border-color:'.$color.';background-color:'.$color.'" href="javascript:dr_show_member(\''.$name.'\');" ><i class="fa fa-user" /></i> '.dr_lang('资料').'</a>
				</span>
			</div>
		';
        } else {
            $str = $ipt;
        }

		return $this->input_format($field['fieldname'], $text, $str.$tips);
	}

    /**
     * 字段表单显示
     *
     * @param	string	$field	字段数组
     * @param	array	$value	值
     * @return  string
     */
    public function show($field, $value = null) {

        // 字段默认值
        if ($value) {
            $value = \Phpcmf\Service::M('member')->author($value);
        } else {
            $value = \Phpcmf\Service::C()->member ? \Phpcmf\Service::C()->member['username'] : '';
        }

        $str = '<div class="form-control-static"> '.htmlspecialchars_decode((string)$value).' </div>';

        return $this->input_format($field['fieldname'], $field['name'], $str);
    }
}