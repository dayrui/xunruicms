<?php namespace Phpcmf\Field;

/**
 * http://www.xunruicms.com
 * 本文件是框架系统文件，二次开发时不可以修改本文件，可以通过继承类方法来重写此文件
 **/


class Linkage extends \Phpcmf\Library\A_Field {
	
	/**
     * 构造函数
     */
    public function __construct(...$params) {
        parent::__construct(...$params);
		$this->fieldtype = ['mediumint' => 8];
		$this->defaulttype = 'mediumint';
    }
	
	/**
	 * 字段相关属性参数
	 *
	 * @param	array	$value	值
	 * @return  string
	 */
	public function option($option) {

		$str = '<select class="form-control" name="data[setting][option][linkage]">';
		$data = \Phpcmf\Service::M()->table('linkage')->getAll();
		if ($data) {
			$linkage = isset($option['linkage']) ? $option['linkage'] : '';
			foreach ($data as $t) {
				$str.= '<option value="'.$t['code'].'" '.($linkage == $t['code'] ? 'selected' : '').'> '.$t['name'].'（'.$t['code'].'） </option>';
			}
		}
		$str.= '</select>';

		return ['<div class="form-group">
                  	<label class="col-md-2 control-label">'.dr_lang('选择菜单').'</label>
                    <div class="col-md-9"><label>'.$str.'</label></div>
                </div>
			<div class="form-group">
				<label class="col-md-2 control-label">'.dr_lang('默认选择值').'</label>
				<div class="col-md-9">
					<label><input id="field_default_value" type="text" class="form-control" size="20" value="'.$option['value'].'" name="data[setting][option][value]"></label>
					<label>'.$this->member_field_select().'</label>
					<span class="help-block">'.dr_lang('也可以设置会员表字段，表示用当前登录会员信息来填充这个值').'</span>
				</div>
			</div>
				', ''];
	}
	
	/**
	 * 创建sql语句
	 */
	public function create_sql($name, $option, $cname) {
		$sql = 'ALTER TABLE `{tablename}` ADD `'.$name.'` mediumint( 8 ) UNSIGNED NULL COMMENT \''.$cname.'\'';
		return $sql;
	}
	
	/**
	 * 字段输出
	 */
	public function output($value) {
		return $value;
	}

	/**
	 * 字段入库值
	 *
	 * @param	array	$field	字段信息
	 * @return  void
	 */
	public function insert_value($field) {
		\Phpcmf\Service::L('Field')->data[$field['ismain']][$field['fieldname']] = (int)\Phpcmf\Service::L('Field')->post[$field['fieldname']];
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

		// 字段提示信息
		$tips = ($name == 'title' && APP_DIR) || $field['setting']['validate']['tips'] ? '<span class="help-block" id="dr_'.$field['fieldname'].'_tips">'.$field['setting']['validate']['tips'].'</span>' : '';

		// 字段默认值
		$value = strlen($value) ? $value : $this->get_default_value($field['setting']['option']['value']);

		// 联动菜单缓存
		$linkage = \Phpcmf\Service::L('cache')->get('linkage-'.SITE_ID.'-'.$field['setting']['option']['linkage']);
		$linkageid = \Phpcmf\Service::L('cache')->get('linkage-'.SITE_ID.'-'.$field['setting']['option']['linkage'].'-id');
		$linkagelevel = (int)\Phpcmf\Service::L('cache')->get('linkage-'.SITE_ID.'-'.$field['setting']['option']['linkage'].'-level');
		//
		$linklevel = $linkagelevel + 1;
		// 开始输出
		$str = '<input type="hidden" name="data['.$name.']" id="dr_'.$name.'" value="'.(int)$value.'">';
		if(!defined('PHPCMF_FIELD_LINKAGE')) {
			$str.= '<script type="text/javascript" src="'.ROOT_THEME_PATH.'assets/js/jquery.ld.js"></script>';
			define('PHPCMF_FIELD_LINKAGE', 1);
		}
		$level = 1;
		$default = '';
		if ($value) {
			$pids = substr($linkage[$linkageid[$value]]['pids'], 2);
			$level = substr_count($pids, ',') + 1;
			$default = !$pids ? '["'.$value.'"]' : '["'.str_replace(',', '","', $pids).'","'.$value.'"]';
		}
		// 输出默认菜单
		$str.= '<span id="dr_linkage_'.$name.'_select" style="'.($value ? 'display:none' : '').'">';
		for ($i = 1; $i <= $linklevel; $i++) {
			$style = $i > $level ? 'style="display:none"' : '';
			$str.= '<label style="padding-right:10px;"><select class="form-control finecms-select-'.$name.'" '.$disabled.' name="'.$name.'-'.$i.'" id="'.$name.'-'.$i.'" width="100" '.$style.'><option value=""> -- </option></select></label>';
		}
		$str.= '</span>';
		// 重新选择
		$value && $str.= '<div class="form-control-static" id="dr_linkage_'.$name.'_cxselect">'.dr_linkagepos($field['setting']['option']['linkage'], $value, ' » ').'&nbsp;&nbsp;<a href="javascript:;" onclick="dr_linkage_select_'.$name.'()" style="color:blue">'.dr_lang('[重新选择]').'</a></div>';
		// 输出js支持
		$str.= '
		<script type="text/javascript">
			function dr_linkage_select_'.$name.'() {
				$("#dr_linkage_'.$name.'_select").show();
				$("#dr_linkage_'.$name.'_cxselect").hide();
			}
			$(function(){
				var $ld5 = $(".finecms-select-'.$name.'");					  
				$ld5.ld({ajaxOptions:{"url": "/index.php?s=api&c=api&m=linkage&code='.$field['setting']['option']['linkage'].'"},defaultParentId:0})
				var ld5_api = $ld5.ld("api");
				ld5_api.selected('.$default.');
				$ld5.bind("change",onchange);
				function onchange(e){
					var $target = $(e.target);
					var index = $ld5.index($target);
					//$("#'.$name.'-'.$i.'").remove();
					$("#dr_'.$name.'").val($ld5.eq(index).show().val());
					index ++;
					$ld5.eq(index).show();
				}
			})
		</script>'.$tips;
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