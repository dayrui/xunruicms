<?php namespace Phpcmf\Field;
/**
 * {{www.xunruicms.com}}
 * {{迅睿内容管理框架系统}}
 * 本文件是框架系统文件，二次开发时不可以修改本文件，可以通过继承类方法来重写此文件
 **/

class Radio extends \Phpcmf\Library\A_Field {
	
	/**
     * 构造函数
     */
    public function __construct(...$params) {
        parent::__construct(...$params);
		$this->fieldtype = TRUE; // TRUE表全部可用字段类型,自定义格式为 array('可用字段类型名称' => '默认长度', ... )
		$this->defaulttype = 'VARCHAR'; // 当用户没有选择字段类型时的缺省值
    }

    // 调出字段遍历出来
    protected function _get_select_field($option, $field, $id, $at) {

        $str = '<div class="mt-checkbox-list">';
        foreach ($field as $t) {
            if ($t['disabled']) {
                continue;
            } elseif ($t['fieldtype'] == 'Merge') {
                continue;
            } elseif ($t['fieldtype'] == 'Group') {
                continue;
            }
            $str.= '<label class="mt-checkbox mt-checkbox-outline">';
            $str.= '<input type="checkbox" '.(dr_in_array($t['fieldname'], $option['field_ld'][$id][$at]) ? 'checked' : '').' name="data[setting][option][field_ld]['.$id.']['.$at.'][]" value="'.$t['fieldname'].'"> '.$t['name'].' ';
            $str.= '<span></span>';
            $str.= '</label>';
        }
        $str.= '</div>';

        return $str;
    }
	
	/**
	 * 字段相关属性参数
	 *
	 * @param	array	$value	值
	 * @return  string
	 */
	public function option($option, $field = NULL) {

        $data = dr_format_option_array($option['options']);
        if (!$data) {
            $ld = '需要保存字段配置后才能配置联动关系';
        } else {
            $ld = '<div class="table-scrollable">';
            $ld.= '<table class="table table-striped table-bordered table-advance ">';
            $ld.= '<thead>';
            $ld.= '<tr>';
            $ld.= '<th width="120">选项</th>';
            $ld.= '<th>隐藏字段</th>';
            $ld.= '</tr>';
            $ld.= '</thead>';
            $ld.= '<tbody>';
            $data['dr_null'] = '未选择时';
            foreach ($data as $id => $name) {
                $ld.= '<tr>';
                $ld.= '<td>'.$name.'</td>';
                $ld.= '<td>'.$this->_get_select_field($option, $field, $id, 'hide').'</td>';
                $ld.= '</tr>';
            }
            $ld.= '</tbody>';
            $ld.= '</table>';
            $ld.= '</div>';
        }

		$option['options'] = isset($option['options']) ? $option['options'] : 'name1|1'.PHP_EOL.'name2|2';



		return [
			'
			<div class="form-group">
				<label class="col-md-2 control-label">'.dr_lang('选项列表').'</label>
				<div class="col-md-9">
					<textarea class="form-control" name="data[setting][option][options]" style="height:150px;width:400px;">'.$option['options'].'</textarea>
					<span class="help-block">'.dr_lang('格式：选项名称|选项值[回车换行]选项名称2|值2....').'</span>
					<span class="help-block">'.dr_lang('选项值建议使用从1开始的数字，不得带符号，也可以省略不写').'</span>
				</div>
			</div>
			<div class="form-group">
				<label class="col-md-2 control-label">'.dr_lang('条件联动关联').'</label>
				<div class="col-md-9">
					<div class="mt-radio-inline">
						<label class="mt-radio  mt-radio-outline">
							<input type="radio" name="data[setting][option][is_field_ld]" onclick="$(\'#ldtjxx\').hide()" value="0" '.(!$option['is_field_ld'] ? 'checked' : '').'> '.dr_lang('关闭').'
							<span></span>
						</label>
						<label class="mt-radio  mt-radio-outline">
							<input type="radio" name="data[setting][option][is_field_ld]" onclick="$(\'#ldtjxx\').show()" value="1" '.($option['is_field_ld'] ? 'checked' : '').'> '.dr_lang('启用').'
							<span></span>
						</label>
					</div>
				</div>
			</div>
			<div class="form-group" id="ldtjxx" style="'.(!$option['is_field_ld'] ? 'display: none' : '').'">
				<label class="col-md-2 control-label">'.dr_lang('设置条件').'</label>
				<div class="col-md-9">
					'.$ld.'
					<span class="help-block">'.dr_lang('当选择某一个选项时会联动显示或隐藏指定的字段').'</span>
					<span class="help-block">'.dr_lang('需要把参与的字段属性不要设置为必填字段').'</span>
				</div>
			</div>
			<div class="form-group">
				<label class="col-md-2 control-label">'.dr_lang('默认选中项').'</label>
				<div class="col-md-9">
					<label><input id="field_default_value" type="text" class="form-control" size="20" value="'.$option['value'].'" name="data[setting][option][value]"></label>
					<label>'.$this->member_field_select().'</label>
					<span class="help-block">'.dr_lang('用于字段为空时显示该填充值，并不会去主动变更数据库中的实际值；可以设置会员表字段，表示用当前登录会员信息来填充这个值').'</span>
				</div>
			</div>'.$this->field_type($option['fieldtype'], $option['fieldlength']).'
			<div class="form-group">
				<label class="col-md-2 control-label">'.dr_lang('显示格式').'</label>
				<div class="col-md-9">
					<div class="mt-radio-inline">
						<label class="mt-radio  mt-radio-outline">
							<input type="radio" name="data[setting][option][show_type]" value="0" '.(!$option['show_type'] ? 'checked' : '').'> '.dr_lang('横排显示').'
							<span></span>
						</label>
						<label class="mt-radio  mt-radio-outline">
							<input type="radio" name="data[setting][option][show_type]" value="1" '.($option['show_type'] ? 'checked' : '').'> '.dr_lang('竖排显示').'
							<span></span>
						</label>
					</div>
					
				</div>
			</div>
			'
		];
	}

	/**
	 * 字段表单输入
	 *
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

		// 字段提示信息
		$tips = ($name == 'title' && APP_DIR) || $field['setting']['validate']['tips'] ? '<span class="help-block" id="dr_'.$field['fieldname'].'_tips">'.$field['setting']['validate']['tips'].'</span>' : '';

		// 字段默认值
		$value = dr_strlen($value) ? $value : $this->get_default_value($field['setting']['option']['value']);
		
		$str = '';

		// 显示方式
		$show_type = (int)$field['setting']['option']['show_type'];

        if ($field['setting']['option']['is_field_ld'] && $field['setting']['option']['field_ld']) {
            $str.= '<script>function field_ld_'.$name.'(v) {';
            $show = [];
            foreach ($field['setting']['option']['field_ld'] as $ii => $t) {
                if ($t['hide']) {
                    foreach ($t['hide'] as $f) {
                        if (in_array($f, $show)) {
                            continue;
                        }
                        $show[] = $f;
                        $str.= PHP_EOL.'$("#dr_row_'.$f.'").show();';
                    }
                }
            }
            foreach ($field['setting']['option']['field_ld'] as $ii => $t) {
                $str.= PHP_EOL.'if (v == "'.($ii == 'dr_null' ? '' : $ii).'") {';
                /*
                if ($t['show']) {
                    foreach ($t['show'] as $f) {
                        $str.= PHP_EOL.'$("#dr_row_'.$f.'").show();';
                    }
                }*/
                if ($t['hide']) {
                    foreach ($t['hide'] as $f) {
                        $str.= PHP_EOL.'$("#dr_row_'.$f.'").hide();';
                    }
                }
                $str.= PHP_EOL.'}';
            }
            $str.= '}';
            $str.= '$(function(){
                field_ld_'.$name.'("'.$value.'");
            });';
            $str.= '</script>';
            $field['setting']['validate']['formattr'].= ' onclick="field_ld_'.$name.'(this.value)"';
        }

		// 表单选项
		$options = dr_format_option_array($field['setting']['option']['options']);
		if ($options) {
			foreach ($options as $v => $n) {
				$s = $v == $value ? ' checked' : '';
				$kj = '<input type="radio" name="data['.$name.']" value="'.$v.'" '.$s.' '.$field['setting']['validate']['formattr'].' />';
				$str.= '<label class="mt-radio mt-radio-outline">'.$kj.' '.dr_lang($n).' <span></span> </label>';
			}
		}

		return $this->input_format($name, $text, '<div class="'.(!$show_type ? 'mt-radio-inline' : 'mt-radio-list').'">'.$str.'</div>'.$tips);
	}

    /**
     * 字段表单显示
     *
     * @param	string	$field	字段数组
     * @param	array	$value	值
     * @return  string
     */
    public function show($field, $value = null) {

        $options = dr_format_option_array($field['setting']['option']['options']);

        $str = '<div class="form-control-static"> '.(isset($options[$value]) ? dr_lang($options[$value]) : dr_lang('未选择')).' </div>';

        return $this->input_format($field['fieldname'], $field['name'], $str);
    }
}