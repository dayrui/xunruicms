<?php namespace Phpcmf\Field;
/**
 * {{www.xunruicms.com}}
 * {{迅睿内容管理框架系统}}
 * 本文件是框架系统文件，二次开发时不可以修改本文件，可以通过继承类方法来重写此文件
 **/

class Group extends \Phpcmf\Library\A_Field {
	
	/**
     * 构造函数
     */
    public function __construct(...$params) {
        parent::__construct(...$params);
        $this->close_xss = 1; // 关闭xss验证
        $this->is_validate = false; // 不允许字段验证
        $this->is_edit = false; // 不允许修改字段类别
		$this->fieldtype = ''; // TRUE表全部可用字段类型,自定义格式为 array('可用字段类型名称' => '默认长度', ... )
		$this->defaulttype = ''; // 当用户没有选择字段类型时的缺省值
    }
	
	/**
	 * 字段相关属性参数
	 *
	 * @param	array	$value	值
	 * @param	array	$field	字段集合
	 * @return  string
	 */
	public function option($option, $field = NULL) {
		$group = [];
		$option['value'] = isset($option['value']) ? $option['value'] : '';
		if ($field) {
			foreach ($field as $t) {
				if ($t['fieldtype'] == 'Group') {
					$t['setting'] = dr_string2array($t['setting']);
					if (preg_match_all('/\{(.+)\}/U', $t['setting']['option']['value'], $value)) {
						foreach ($value[1] as $v) {
							$group[] = $v;
						}
					}
				}
			}
			$_field = [];
			$_field[] = '<option value=""> -- </option>';
			foreach ($field as $t) {
                $t['fieldtype'] != 'Group'
                && !dr_in_array($t['fieldname'], $group)
                && $_field[] = '<option value="'.$t['fieldname'].'">'.$t['name'].'</option>';
			}
			$_field = implode('', array_unique($_field));
		}
		
		return [$this->_search_field().'
				<div class="form-group">
                    <label class="col-md-2 control-label">'.dr_lang('可用字段').'</label>
                    <div class="col-md-9">
                    <label><select class="form-control" name="xx" id="fxx">'.$_field.'</select></label>
                    </div>
                </div>
				<div class="form-group">
                    <label class="col-md-2 control-label">'.dr_lang('分组规则').'</label>
                    <div class="col-md-9">
                    <textarea name="data[setting][option][value]" id="fvalue" style="height:120px;" class="form-control">'.$option['value'].'</textarea>
					<span class="help-block">'.dr_lang('分组规则支持html标签，注意每个字段只能存在于一个分组中，否则会出错；此字段只能用于模块中').'</span>
                    </div>
                </div>
				<script type="text/javascript">
				$(function() {
					$("#fxx").change(function(){
						var value = $(this).val();
						var fvalue = $("#fvalue").val();
						var text = $("#fxx").find("option:selected").text();
						$("#fxx option[value=\'"+value+"\']").remove();
						$("#fvalue").val(fvalue+"  "+text+": {"+value+"}");
					});
				}); 
				</script>
				'];
	}
	
	/**
	 * create_sql
	 */
	public function create_sql($name, $value, $cname = '') {
		
	}
	
	/**
	 * alter_sql
	 */
	public function alter_sql($name, $value, $cname = '') {
		
	}
	
	/**
	 * drop_sql
	 */
	public function drop_sql($name) {
		
	}

    /**
     * test
     */
    public function test_sql($tables, $field) {
        return 0;
    }
	
	/**
	 * 字段入库值
	 */
	public function insert_value($field) {
		
	}
	
	/**
	 * 字段表单输入
	 * @return  string
	 */
    public function input($field, $value = '') {

        // 字段禁止修改时就返回显示字符串
        if ($this->_not_edit($field, $value)) {
            return $this->show($field, $value);
        }

        // 字段显示名称
        $text = ($field['setting']['validate']['required'] ? '<span class="required" aria-required="true"> * </span>' : '').dr_lang($field['name']);

        // 字段提示信息
		$tips = isset($field['setting']['validate']['tips']) && $field['setting']['validate']['tips'] ? '<div class="help-block" id="dr_'.$field['fieldname'].'_tips">'.$field['setting']['validate']['tips'].'</div>' : '';

        // 字段默认值
		$value = str_replace(['{', '}'], ['{|', '|}'], $field['setting']['option']['value']);

		return $this->input_format($field['fieldname'], $text, $value.$tips);
	}
	
}