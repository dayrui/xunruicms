<?php namespace Phpcmf\Field;
/**
 * {{www.xunruicms.com}}
 * {{迅睿内容管理框架系统}}
 * 本文件是框架系统文件，二次开发时不可以修改本文件，可以通过继承类方法来重写此文件
 **/

class Members extends \Phpcmf\Library\A_Field {
	
	/**
     * 构造函数
     */
    public function __construct(...$params) {
        parent::__construct(...$params);
		$this->fieldtype = ['TEXT' => '']; // TRUE表全部可用字段类型,自定义格式为 array('可用字段类型名称' => '默认长度', ... )
		$this->defaulttype = 'TEXT'; // 当用户没有选择字段类型时的缺省值
    }
	
	/**
	 * 字段相关属性参数
	 *
	 * @param	array	$value	值
	 * @return  string
	 */
	public function option($option) {

	    $group = '<div class="mt-checkbox-inline">';
	    foreach (\Phpcmf\Service::C()->member_cache['group'] as $t) {
            $group.= '<label class="mt-checkbox mt-checkbox-outline"><input type="checkbox" value="'.$t['id'].'" name="data[setting][option][group][]" '.(dr_in_array($t['id'], $option['group']) ? 'checked' : '').' /> '.dr_lang($t['name']).' <span></span></label>';
        }
	    $group.= '</div>';

		return [$this->_search_field().'
				<div class="form-group">
                    <label class="col-md-2 control-label">'.dr_lang('最大选择数').'</label>
                    <div class="col-md-9">
                    <label><input type="text" class="form-control" size="10" name="data[setting][option][limit]" value="'.$option['limit'].'"></label>
					<span class="help-block">'.dr_lang('最大能选择的数量限制').'</span>
                    </div>
                </div>
				<div class="form-group">
                    <label class="col-md-2 control-label">'.dr_lang('每页显示条数').'</label>
                    <div class="col-md-9">
                    <label><input type="text" class="form-control" size="10" name="data[setting][option][pagesize]" value="'.$option['pagesize'].'"></label>
					<span class="help-block">'.dr_lang('选择列表分页条数，按多少条数据分页').'</span>
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-md-2 control-label">'.dr_lang('指定用户组').'</label>
                    <div class="col-md-9">
                    '.$group.'
					<span class="help-block">'.dr_lang('列出指定用户组的用户列表，如果不选择时将显示全部用户').'</span>
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-md-2 control-label">'.dr_lang('友情提示').'</label>
                    <div class="col-md-9">
                        <div class="form-control-static">'.dr_lang('此字段不能参与搜索条件筛选').'</div>
                    </div>
                </div>'];
	}
	
	/**
	 * 字段输出
	 */
	public function output($value) {
		return $value;
	}
	
	/**
	 * 字段入库值
	 */
	public function insert_value($field) {
		
		$data = \Phpcmf\Service::L('Field')->post[$field['fieldname']];
		$value = !$data ? '' : implode(',', $data);
		
		\Phpcmf\Service::L('Field')->data[$field['ismain']][$field['fieldname']] = $value;
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

        $is_show = 0;

        // 字段存储名称
        $name = $field['fieldname'];
		// 字段提示信息
		$tips = isset($field['setting']['validate']['tips']) && $field['setting']['validate']['tips'] ? '<span class="help-block" id="dr_'.$name.'_tips">'.$field['setting']['validate']['tips'].'</span>' : '';

		// 区域大小
        $area = \Phpcmf\Service::IS_MOBILE_USER() ? '["95%", "90%"]' : '["50%", "65%"]';

        // 选择数量限制
        $limit = intval($field['setting']['option']['limit']);
        !$limit && $limit = 99999;
        // 字段显示名称
        $text = ($field['setting']['validate']['required'] ? '<span class="required" aria-required="true"> * </span>' : '').dr_lang($field['name']);

        $value = $value ? trim($value, ',') : '';
        $mylist = [];
        if ($value && is_string($value)) {
            $arr = explode(',', $value);
            if ($arr) {
                $value = '';
                foreach ($arr as $a) {
                    $a = intval($a);
                    if ($a) {
                        $value .= ',' . $a;
                    }
                }
                if ($value) {
                    $value = trim($value, ',');
                    $db = \Phpcmf\Service::M()->db->query('select * from '.\Phpcmf\Service::M()->dbprefix('member').' where id IN ('.$value.') order by instr("'.$value.'", id)');
                    $mylist = $db ? $db->getResultArray() : [];
                }
            }
		}

        $file = \Phpcmf\Service::V()->code2php(
            file_get_contents(is_file(MYPATH.'View/api_members_field.html') ? MYPATH.'View/api_members_field.html' : COREPATH.'View/api_members_field.html')
        );
        ob_start();
        require $file;
        $str = ob_get_clean();

        $str.= $tips;
        $js = \Phpcmf\Service::L('js_packer');
        $str.= $js->pack('
		<script type="text/javascript">
        $("#rmember_'.$name.'-sort-items").sortable();
        dr_slimScroll_init(".scroller_'.$name.'_files", 300);
		function dr_add_rmember_'.$name.'() {
		    var len = $(\'#rmember_'.$name.'-sort-items tr\').length;
		    if (len >= '.$limit.') {
		        dr_tips(0, "'.dr_lang('关联数量超限').'");
		        return;
		    }
		    var url = "/index.php?&is_iframe=1&s=api&c=api&m=members&name='.$name.'&pagesize='.intval($field['setting']['option']['pagesize']).'&group='.($field['setting']['option']['group'] ? implode(',', $field['setting']['option']['group']) : '').'";
            layer.open({
                type: 2,
                title: \'<i class="fa fa-user"></i> '.dr_lang('关联用户').'\',
                fix:true,
                shadeClose: true,
                shade: 0,
                area: '.$area.',
                btn: ["'.dr_lang('关联').'"],
                success: function (json) {
                    if (json.code == 0) {
                        layer.close();
                        dr_tips(json.code, json.msg);
                    }
                },
                yes: function(index, layero){
                    var body = layer.getChildFrame(\'body\', index);
                     // 延迟加载
                    var loading = layer.load(2, {
                        time: 10000
                    });
                    $.ajax({type: "POST",dataType:"json", url: url, data: $(body).find(\'#myform\').serialize(),
                        success: function(json) {
                            layer.close(loading);
                            if (json.code == 1) {
                                layer.close(index);
                                for(var i in json.data.ids){
                                    var vid = json.data.ids[i];
                                    if (typeof vid != "undefined") {
                                        if($("#dr_items_'.$name.'_"+vid).length>0) {
                                          dr_tips(0, "'.dr_lang('已经存在').'");
                                          return;
                                        }
                                        if ($(\'#rmember_'.$name.'-sort-items tr\').length >= '.$limit.') {
                                            dr_tips(0, "'.dr_lang('关联数量超限').'");
                                            return;
                                        }
                                    }
                                }
                                $(\'#rmember_'.$name.'-sort-items\').append(json.data.html);
                                dr_slimScroll_init(".scroller_'.$name.'_files", 300);
                                dr_tips(1, json.msg);
                            } else {
                                dr_tips(0, json.msg);
        
                            }
                            return false;
                        }
                    });
                    
                    return false;
                },
                content: url
            });
		
			
		}
		</script>', 0);
		
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

        $value = @trim($value, ',');
        $mylist = [];
        $is_show = 1;
        if ($value && is_string($value)) {
            $arr = explode(',', $value);
            if ($arr) {
                $value = '';
                foreach ($arr as $a) {
                    $a = intval($a);
                    if ($a) {
                        $value .= ',' . $a;
                    }
                }
                if ($value) {
                    $value = trim($value, ',');
                    $db = \Phpcmf\Service::M()->db->query('select * from '.\Phpcmf\Service::M()->dbprefix('member').' where id IN ('.$value.') order by instr("'.$value.'", id)');
                    $mylist = $db ? $db->getResultArray() : [];
                }
            }
        }

        $file = \Phpcmf\Service::V()->code2php(
            file_get_contents(is_file(MYPATH.'View/api_members_field.html') ? MYPATH.'View/api_members_field.html' : COREPATH.'View/api_members_field.html')
        );
        ob_start();
        require $file;
        $str = ob_get_clean();

        return $this->input_format($field['fieldname'], $field['name'], $str);
    }


}