<?php namespace Phpcmf\Field;
/**
 * {{www.xunruicms.com}}
 * {{迅睿内容管理框架系统}}
 * 本文件是框架系统文件，二次开发时不可以修改本文件，可以通过继承类方法来重写此文件
 **/

class Catids extends \Phpcmf\Library\A_Field {

	/**
     * 构造函数
     */
    public function __construct(...$params) {
        parent::__construct(...$params);
        $this->fieldtype = ['TEXT' => ''];
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
                  	<label class="col-md-2 control-label">'.dr_lang('重要提醒').'</label>
                    <div class="col-md-9"><label class="form-control-static">本字段名一定要是catids才能参与搜索</label></div>
                </div>
				', '<div class="form-group">
			<label class="col-md-2 control-label">'.dr_lang('控件宽度').'</label>
			<div class="col-md-9">
				<label><input type="text" class="form-control" size="10" name="data[setting][option][width]" value="'.$option['width'].'"></label>
				<span class="help-block">'.dr_lang('[整数]表示固定宽度；[整数%]表示百分比').'</span>
			</div>
		</div>'];
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
        $save = [];
        $data = \Phpcmf\Service::L('Field')->post[$field['fieldname']];
        $category = \Phpcmf\Service::C()->_get_module_member_category(\Phpcmf\Service::C()->module, 'add');
        if (!IS_ADMIN && !$category) {
            \Phpcmf\Service::C()->_json(1, dr_lang('模块[%s]没有可用栏目权限', \Phpcmf\Service::C()->module['dirname']));
        }
        if ($data) {
            foreach ($data as $t) {
                if ($t) {
                    $save[] = $t;
                    if (!IS_ADMIN && !$category[$t]) {
                        \Phpcmf\Service::C()->_json(1, dr_lang('模块[%s]没有栏目(%s)权限', \Phpcmf\Service::C()->module['dirname'], $t));
                    }
                }
            }
            $save = array_unique($save);
        }
        \Phpcmf\Service::L('Field')->data[$field['ismain']][$field['fieldname']] = dr_array2string($save);
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
        $str.= '<label style="min-width: 200px">'.\Phpcmf\Service::L('Tree')->cache(0)->select_category(
                \Phpcmf\Service::C()->module['category'],
                dr_string2array($value),
                ' name=\'data['.$field['fieldname'].'][]\'  multiple="multiple" data-actions-box="true"',
                '', 1, 1
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


        return $this->input_format($field['fieldname'], $field['name'], '<div class="form-control-static">'.dr_linkagepos($field['setting']['option']['linkage'], $value, ' - ').'</div>');
    }

}