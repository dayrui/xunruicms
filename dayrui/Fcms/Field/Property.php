<?php namespace Phpcmf\Field;
/**
 * {{www.xunruicms.com}}
 * {{迅睿内容管理框架系统}}
 * 本文件是框架系统文件，二次开发时不可以修改本文件，可以通过继承类方法来重写此文件
 **/

class Property extends \Phpcmf\Library\A_Field {
	
	/**
     * 构造函数
     */
    public function __construct(...$params) {
        parent::__construct(...$params);
        $this->close_xss = 1; // 关闭xss验证
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

	    unset($option['width']);
        if (!isset($option['name_value']) || !$option['name_value']) {
            $option['name_value'] = dr_lang('名称');
        }
        if (!isset($option['value_value']) || !$option['value_value']) {
            $option['value_value'] = dr_lang('值');
        }
		$str = '
        <div class="form-group">
				<label class="col-md-2 control-label">'.dr_lang('选项名称').'</label>
				<div class="col-md-9">
					<label><input type="text" class="form-control" size="20" value="'.$option['name_value'].'" name="data[setting][option][name_value]"></label>
					<label><input type="text" class="form-control" size="20" value="'.$option['value_value'].'" name="data[setting][option][value_value]"></label>
				</div>
			</div>
		<div class="form-group dr_option" id="dr_option_0">
			<label class="col-md-2 control-label">'.dr_lang('字段说明').'</label>
			<div class="col-md-9"><div class="form-control-static">'.dr_lang('选择框与复选框类型的选项值以,分隔').'</div><br>
			<a href="javascript:;" class="btn btn-sm red" onclick="dr_add_option()"><i class="fa fa-plus"></i>&nbsp;'.dr_lang('增加属性选项').'</a>
			</div>
		</div>';
        unset($option['name_value']);
        unset($option['value_value']);
        $i = 0;
		if ($option['default_value']) {
			foreach ($option['default_value'] as $i => $t) {
				$str.= '<div class="form-group dr_option" id="dr_option_'.$i.'" >';
				$str.= '<label class="col-md-2 control-label">'.dr_lang('属性名称').'</label>';
				$str.= '<div class="col-md-9"><label><input type="text" name="data[setting][option][default_value]['.$i.'][name]" value="'.$t['name'].'" class="form-control" /></label>';
				$str.= '<label>&nbsp;&nbsp;'.dr_lang('类型').'：</label><label><select class="form-control" name="data[setting][option][default_value]['.$i.'][type]">';
				$str.= '<option value="1" '.($t['type'] == 1 ? "selected" : "").'> - '.dr_lang('文本框').' - </option>';
				$str.= '<option value="2" '.($t['type'] == 2 ? "selected" : "").'> - '.dr_lang('选择框').' - </option>';
				$str.= '<option value="3" '.($t['type'] == 3 ? "selected" : "").'> - '.dr_lang('复选框').' - </option>';
				$str.= '</select></label>';
				$str.= '<label>&nbsp;&nbsp;'.dr_lang('默认值/选项值').'：</label><label><input type="text" name="data[setting][option][default_value]['.$i.'][value]" value="'.$t['value'].'" class="form-control input-xlarge"></label> <label><a onclick="$(\'#dr_option_'.$i.'\').remove()" href="javascript:;">'.dr_lang('删除').'</a></label>';
				$str.= '</div></div>';
			}
		}

		$str.= '
		<script type="text/javascript">
		var id='.$i.';
		function dr_add_option() {
			id ++;
            if ($("#dr_option_"+id).length>0) {
			    dr_tips(0, \''.dr_lang("序列生成重复，请重新添加").'\');
			    return;
			}
			var html = "";
			html+= "<div class=\"form-group dr_option\" id=\"dr_option_"+id+"\" >";
			html+= "<label class=\"col-md-2 control-label\">'.dr_lang('属性名称').' "+id+"</label>";
			html+= "<div class=\"col-md-9\">";
			html+= "<label><input type=\"text\" name=\"data[setting][option][default_value]["+id+"][name]\" value=\"\" class=\"form-control\" /></label>";
			html+= "<label>&nbsp;&nbsp;'.dr_lang('类型').'：</label><label><select class=\"form-control\" name=\"data[setting][option][default_value]["+id+"][type]\">";
			html+= "<option value=\"1\"> - '.dr_lang('文本框').' - </option>";
			html+= "<option value=\"2\"> - '.dr_lang('选择框').' - </option>";
			html+= "<option value=\"3\"> - '.dr_lang('复选框').' - </option>";
			html+= "</select></label>";
			html+= "<label>&nbsp;&nbsp;'.dr_lang('默认值/选项值').'：</label><label><input type=\"text\" name=\"data[setting][option][default_value]["+id+"][value]\" class=\"form-control input-xlarge\"></label>&nbsp;<label><a onclick=\"$(\'#dr_option_"+id+"\').remove()\" href=\"javascript:;\">'.dr_lang('删除').'</a></label>";
			html+= "</div>";
			html+= "</div>";
			$("#dr_option_rows").append(html);
		}
		</script>
		<div id="dr_option_rows"></div>
		<div class="form-group">
            <label class="col-md-2 control-label">'.dr_lang('行数模式').'</label>
            <div class="col-md-9">
                <div class="mt-radio-inline">
                    <label class="mt-radio mt-radio-outline"><input type="radio" name="data[setting][option][is_hang]" value="1" '.($option['is_hang'] ? 'checked' : '').'> '.dr_lang('固定').'  <span></span></label>
                    <label class="mt-radio mt-radio-outline"><input type="radio" name="data[setting][option][is_hang]" value="0" '.(!$option['is_hang'] ? 'checked' : '').'> '.dr_lang('无限').'  <span></span></label>
                </div>
                <span class="help-block">'.dr_lang('无限行数可以在录入数据时自由添加行数，固定行数就只能是录入上述的固定属性行数').'</span>
            </div>
        </div>
		';
		return ['
                '.$str];
	}
	
	/**
	 * 字段输出
	 */
	public function output($value) {
		return dr_string2array($value);
	}
	
	/**
	 * 字段入库值
	 */
	public function insert_value($field) {

        $data = [];
        $value = \Phpcmf\Service::L('Field')->post[$field['fieldname']];
        if ($value) {
            $i = 1;
            foreach ($value as $t) {
                $data[$i] = $t;
                $i++;
            }
        }

		\Phpcmf\Service::L('Field')->data[$field['ismain']][$field['fieldname']] = dr_array2string($data);
	}
	
	/**
	 * 字段表单输入
	 *
	 * @param	string	$cname	字段别名
	 * @param	string	$name	字段名称
	 * @param	array	$cfg	字段配置
	 * @param	string	$value	值
	 * @return  string
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
		// 字段默认值
		$value = $value ? dr_string2array($value) : array();
        $str = '';
		$str.= '	<div class="scroller_'.$name.'_files">
                <div class="scroller" data-inited="0" data-initialized="1" data-always-visible="1" data-rail-visible="1">
        
        <table class="table fc-sku-table table-striped table-bordered table-hover">
        <thead>
        <tr>
            <th width="200" style="border-left-width: 1px!important;">'.dr_lang($field['setting']['option']['name_value'] ? $field['setting']['option']['name_value'] : dr_lang('名称')).' </th>
            <th>'.dr_lang($field['setting']['option']['value_value'] ? $field['setting']['option']['value_value'] : dr_lang('值')).' </th>';
        if (!$field['setting']['option']['is_hang']) {
            $str.='
            <th width="70" style="text-align: center"> ';

            $str.= '	<a href="javascript:;" class="btn blue btn-xs" onClick="dr_add_property_'.$name.'()"> <i class="fa fa-plus"></i></a>';
            $str.='</th>';
        }
        $str.= '
        </tr>
        </thead>
        <tbody id="property_'.$name.'-sort-items" class="scroller_body">';
		$i = 0;

        unset($field['setting']['width']);
        // 固定属性选项
		if (isset($field['setting']['option']['default_value']) && $field['setting']['option']['default_value']) {
            $i = 1;
			foreach ($field['setting']['option']['default_value'] as $t) {
			    if (!isset($t['name']) && $t['name']) {
			        continue;
                }
				$str.= '<tr id="dr_items_'.$name.'_'.$i.'" class="dr_items_readonly">';
				$str.= '<td class="highlight"><input type="text" readonly class="form-control input-sm" value="'.$t['name'].'" name="data['.$name.']['.$i.'][name]"></td>';
				$str.= '<td>';
				switch ($t['type']) {
					case 1:
						$v = $value[$i]['value'] ? $value[$i]['value'] : $t['value'];
						$str.= '<input type="text" class="form-control input-sm" value="'.htmlspecialchars((string)$v).'" name="data['.$name.']['.$i.'][value]" />';
						break;
					case 2:
						$v = explode(',', $t['value']);
						$str.= '<select class="form-control" name="data['.$name.']['.$i.'][value]">';
						$str.= '<option value=""> -- </option>';
						if ($v) {
							foreach ($v as $c) {
								$selected = isset($value[$i]['value']) && $value[$i]['value'] == $c ? 'selected' : '';
								$str.= '<option value="'.$c.'" '.$selected.'> '.$c.' </option>';
							}
						}
						$str.= '</select>';
						break;
					case 3:
						$v = explode(',', $t['value']);
						if ($v) {
							foreach ($v as $c) {
								$selected = isset($value[$i]['value']) && dr_in_array($c, $value[$i]['value']) ? 'checked' : '';
								$str.= '<input type="checkbox" name="data['.$name.']['.$i.'][value][]" value="'.$c.'" ' . $selected . ' />'.$c.'';
							}
						}
				}
				$str.= '</td>';
				if (!$field['setting']['option']['is_hang']) {
                    $str.= '<td>';
                    $str.= '</td>';
                }
				$str.= '</tr>';
                unset($value[$i]);
                $i++;
			}
		}
		// 剩下自定义属性
		if ($value && !$field['setting']['option']['is_hang']) {
			foreach ($value as $t) {
                $str.= '<tr id="dr_items_'.$name.'_'.$i.'" class="dr_items_sort">';
                $str.= '<td><input type="text" class="form-control input-sm" value="'.htmlspecialchars((string)$t['name']).'" name="data['.$name.']['.$i.'][name]"></td>';
                $str.= '<td>';
                $str.= '<input type="text" class="form-control input-sm" value="'.htmlspecialchars((string)$t['value']).'" name="data['.$name.']['.$i.'][value]" />';
                $str.= '</td>';
                $str.= '<td style="text-align: center"><a class="btn btn-xs red" href="javascript:;" onclick="$(\'#dr_items_'.$name.'_'.$i.'\').remove()"> <i class="fa fa-trash"></i> </a>';
                $str.= '</td>';
                $str.= '</tr>';
                $i++;
			}
		}
		
		$str.= '
            </tbody>
        </table></div></div>';
		if (!$field['setting']['option']['is_hang']) {
            $js = \Phpcmf\Service::L('js_packer');
            $str.= $js->pack('<script type="text/javascript">
		$("#property_'.$name.'-sort-items").sortable({
  items: ".dr_items_sort"
});
        dr_slimScroll_init(".scroller_'.$name.'_files", 300);
		var id='.($i).';
		function dr_add_property_'.$name.'() {
			if ($("#dr_items_'.$name.'_"+id).length>0) {
			    id++;
			    dr_tips(0, \''.dr_lang("序列生成重复，请重新添加").'\');
			    return;
			}
			var html = "<tr id=\"dr_items_'.$name.'_"+id+"\">";
			html+= "<td><input type=\"text\" class=\"form-control input-sm\" value=\"\" name=\"data['.$name.']["+id+"][name]\"></td>";
			html+= "<td><input type=\"text\" class=\"form-control input-sm\" value=\"\" name=\"data['.$name.']["+id+"][value]\"></td>";
			html+= "<td style=\"text-align: center\"><a class=\"btn btn-xs red\" href=\"javascript:;\" onclick=\"$(\'#dr_items_'.$name.'_"+id+"\').remove()\"> <i class=\"fa fa-trash\"></i> </a></td></tr>";
			$("#property_'.$name.'-sort-items").append(html);
            dr_slimScroll_init(".scroller_'.$name.'_files", 300);
			id++;
		}
		</script>', 0);
        }

        $str.= '<span class="help-block">'.$field['setting']['validate']['tips'].'</span>';
		return $this->input_format($field['fieldname'], $text, $str);
	}


    /**
     * 字段表单显示
     *
     * @param	string	$field	字段数组
     * @param	array	$value	值
     * @return  string
     */
    public function show($field, $value = null) {

        $str = '
        <table class="table table-striped table-bordered table-advance ">
        <thead>
        <tr>
            <th width="200" style="border-left-width: 1px!important;">'.dr_lang($field['setting']['option']['name_value'] ? $field['setting']['option']['name_value'] : dr_lang('名称')).' </th>
            <th>'.dr_lang($field['setting']['option']['value_value'] ? $field['setting']['option']['value_value'] : dr_lang('值')).' </th>
     
        </tr>
        </thead>
        <tbody>';
        $i = 0;

        unset($field['setting']['width']);
        // 默认属性选项
        if ($value) {
            $value = dr_string2array($value);
            foreach ($value as $t) {

                $str.= '<tr>';
                $str.= '<td class="highlight">'.$t['name'].'</td>';
                $str.= '<td>'.$t['value'].'</td>';
                $str.= '</tr>';
                $i++;
            }
        }


        $str.= '
            </tbody>
        </table>';

        return $this->input_format($field['fieldname'], $field['name'], $str);
    }
	
}