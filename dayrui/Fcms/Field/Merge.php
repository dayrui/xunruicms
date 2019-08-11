<?php namespace Phpcmf\Field;

/* *
 *
 * Copyright [2019] [李睿]
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * 本文件是框架系统文件，二次开发时不建议修改本文件
 *
 * */


class Merge extends \Phpcmf\Library\A_Field {
	
	/**
     * 构造函数
     */
    public function __construct(...$params) {
        parent::__construct(...$params);
        $this->close_xss = 1; // 关闭xss验证
		$this->fieldtype = ['TEXT'];
		$this->defaulttype = 'TEXT';
    }
	
	/**
	 * 字段相关属性参数
	 *
	 * @param	array	$value	值
	 * @param	array	$field	字段集合
	 * @return  string
	 */
	public function option($option, $field = NULL) {
		$Merge = [];
		$option['value'] = isset($option['value']) ? $option['value'] : '';
		if ($field) {
			foreach ($field as $t) {
				if ($t['fieldtype'] == 'Merge') {
					$t['setting'] = dr_string2array($t['setting']);
					if (preg_match_all('/\{(.+)\}/U', $t['setting']['option']['value'], $value)) {
						foreach ($value[1] as $v) {
							$Merge[] = $v;
						}
					}
				}
			}
			$_field = [];
			$_field[] = '<option value=""> -- </option>';
			foreach ($field as $t) {
                $t['fieldtype'] != 'Merge' && !@in_array($t['fieldname'], $Merge) && $_field[] = '<option value="'.$t['fieldname'].'">'.$t['name'].'</option>';
			}
			$_field = @implode('', @array_unique($_field));
		}
		
		return ['
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
					<span class="help-block">'.dr_lang('分组规则不支持html标签，注意每个字段只能存在于一个分组中，否则会出错；此字段只能用于模块中').'</span>
                    </div>
                </div>
				<script type="text/javascript">
				$(function() {
					$("#fxx").change(function(){
						var value = $(this).val();
						var fvalue = $("#fvalue").val();
						var text = $("#fxx").find("option:selected").text();
						$("#fxx option[value=\'"+value+"\']").remove();
						$("#fvalue").val(fvalue+"\n{"+value+"}");
					});
				}); 
				</script>
				'];
	}
	
	/**
	 * create_sql
	 */
	public function create_sql($name, $value, $cname) {
		
	}
	
	/**
	 * alter_sql
	 */
	public function alter_sql($name, $value, $cname) {
		
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

        return $this->input_format($field['fieldname'], $field['name'], $field['setting']['option']['value']);
    }
}