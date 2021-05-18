<?php namespace Phpcmf\Field;
/**
 * {{www.xunruicms.com}}
 * {{迅睿内容管理框架系统}}
 * 本文件是框架系统文件，二次开发时不可以修改本文件，可以通过继承类方法来重写此文件
 **/

class Cat extends \Phpcmf\Library\A_Field {
	
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

        $_option = '';
        $_module = \Phpcmf\Service::C()->get_cache('module-'.SITE_ID.'-content');
        if ($_module) {
            $_option.= '<option value="share" '.('share' == $option['module'] ? 'selected' : '').'>'.dr_lang('共享栏目').'</option>';
            foreach ($_module as $dir => $t) {
                if (!$t['share']) {
                    $_option.= '<option value="'.$dir.'" '.($dir == $option['module'] ? 'selected' : '').'>'.$t['name'].'</option>';
                }
            }
        }

		return ['<div class="form-group">
                    <label class="col-md-2 control-label">'.dr_lang('内容模块').'</label>
                    <div class="col-md-9">
                    <label><select class="form-control" name="data[setting][option][module]">
					'.$_option.'
					</select></label>
					<span class="help-block">'.dr_lang('必须选择一个模块作为栏目的数据源').'</span>
                    </div>
                </div><div class="form-group">
                    <label class="col-md-2 control-label">'.dr_lang('选择父栏目').'</label>
                    <div class="col-md-9">
                        <input type="checkbox" name="data[setting][option][parent]" '.($option['parent'] ? 'checked' : '').' value="1"  data-on-text="'.dr_lang('开启').'" data-off-text="'.dr_lang('关闭').'" data-on-color="success" data-off-color="danger" class="make-switch" data-size="small">
                        <span class="help-block">'.dr_lang('开启之后可以选择父级栏目').'</span>
                    </div>
                </div><div class="form-group">
                <label class="col-md-2 control-label">'.dr_lang('启用选项搜索').'</label>
                <div class="col-md-9">
                <input type="checkbox" name="data[setting][option][is_search]" '.($option['is_search'] ? 'checked' : '').' value="1" data-on-text="'.dr_lang('开启').'" data-off-text="'.dr_lang('关闭').'" data-on-color="success" data-off-color="danger" class="make-switch" data-size="small">
                
					<span class="help-block">'.dr_lang('当选项值过多时，可以在选择框中搜索选项值').'</span>
                </div>
            </div>', ''];
	}

	
	/**
	 * 字段输出
	 */
	public function output($value) {
        return dr_string2array($value);
	}

	/**
	 * 字段入库值
	 *
	 * @param	array	$field	字段信息
	 * @return  void
	 */
	public function insert_value($field) {
        \Phpcmf\Service::L('Field')->data[$field['ismain']][$field['fieldname']] = intval(\Phpcmf\Service::L('Field')->post[$field['fieldname']]);
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

		// 字段提示信息
		$tips = ($name == 'title' && APP_DIR) || $field['setting']['validate']['tips'] ? '<span class="help-block" id="dr_'.$field['fieldname'].'_tips">'.$field['setting']['validate']['tips'].'</span>' : '';

		// 开始输出
		$str = '';
        $str.= '<label style="min-width: 200px">'.\Phpcmf\Service::L('Tree')->select_category(
                \Phpcmf\Service::C()->get_cache('module-'.SITE_ID.'-'.$field['setting']['option']['module'], 'category'),
                intval($value),
                ' name=\'data['.$field['fieldname'].']\' data-actions-box="true" '.(isset($field['setting']['option']['is_search']) && $field['setting']['option']['is_search'] ? ' data-live-search="true" ' : ''),
                '', (isset($field['setting']['option']['parent']) && $field['setting']['option']['parent'] ? 0 : 1), 0
            ).'</label>';
        $str.= \Phpcmf\Service::L('Field')->get('select')->get_select_search_code().'<span class="help-block">'.$tips.'</span>';
		return $this->input_format($name, $text, $str);
	}

    /**
     * 字段表单显示
     *
     * @param	string	$field	字段数组
     * @param	array	$value	值
     * @return  string
     */
    public function show($field, $value = null) {

        return $this->input_format($field['fieldname'], $field['name'], '<div class="form-control-static">'.dr_catpos($value, ' - ', false, '', $field['setting']['option']['module']).'</div>');
    }

}