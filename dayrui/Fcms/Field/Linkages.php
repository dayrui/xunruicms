<?php namespace Phpcmf\Field;
/**
 * {{www.xunruicms.com}}
 * {{迅睿内容管理框架系统}}
 * 本文件是框架系统文件，二次开发时不可以修改本文件，可以通过继承类方法来重写此文件
 **/

class Linkages extends \Phpcmf\Library\A_Field {
	
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

		$str = '<select class="form-control" name="data[setting][option][linkage]">';
		$data = \Phpcmf\Service::M()->table('linkage')->getAll();
		if ($data) {
			$linkage = isset($option['linkage']) ? $option['linkage'] : '';
			foreach ($data as $t) {
				$str.= '<option value="'.$t['code'].'" '.($linkage == $t['code'] ? 'selected' : '').'> '.$t['name'].'（'.$t['code'].'） </option>';
			}
		}
		$str.= '</select>';

		return [$this->_search_field().'<div class="form-group">
                  	<label class="col-md-2 control-label">'.dr_lang('选择菜单').'</label>
                    <div class="col-md-9"><label>'.$str.'</label></div>
                </div>
                <div class="form-group">
                  	<label class="col-md-2 control-label">'.dr_lang('最大选择数').'</label>
                    <div class="col-md-9">
                    <label><input type="text" class="form-control" size="10" name="data[setting][option][limit]" value="'.$option['limit'].'"></label>
				<span class="help-block">'.dr_lang('最大能选择的数量限制').'</span>
				</div>
                </div>
                <div class="form-group">
                    <label class="col-md-2 control-label">'.dr_lang('强制选择最终项').'</label>
                    <div class="col-md-9">
                        <div class="mt-radio-inline">
                            <label class="mt-radio mt-radio-outline"><input type="radio" value="1" name="data[setting][option][ck_child]" '.($option['ck_child'] == 1 ? 'checked' : '').' > '.dr_lang('开启').' <span></span></label>
                             &nbsp; &nbsp;
                             <label class="mt-radio mt-radio-outline"><input type="radio" value="0" name="data[setting][option][ck_child]" '.($option['ck_child'] == 0 ? 'checked' : '').' > '.dr_lang('关闭').' <span></span></label>
                        </div>
						<span class="help-block">'.dr_lang('开启后会强制要求用户选择最终一个选项').'</span>
                    </div>
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
        if ($data) {
            foreach ($data as $t) {
                if ($t) {
                    $save[] = $t;
                }
            }
            $save = array_unique($save);
        }
        // 判断超限
        if ($field['setting']['option']['limit'] && dr_count($save) > $field['setting']['option']['limit']) {
            $save = array_slice($save, 0, $field['setting']['option']['limit']);
        }
        \Phpcmf\Service::L('Field')->data[$field['ismain']][$field['fieldname']] = dr_array2string($save);
	}


    /**
     * 验证必填字段值
     *
     * @param	string	$field	字段类型
     * @param	string	$value	字段值
     * @return
     */
    public function check_required($field, $values) {

        if (!$values) {
            // 验证值为空
            return dr_lang('%s不能为空', $field['name']);
        }

        foreach ($values as $value) {

            $code = \Phpcmf\Service::L('cache')->get('linkage-'.SITE_ID.'-'.$field['setting']['option']['linkage'].'-id', $value);
            if (!$code) {
                return dr_lang('选项无效');
            }

            $linkage = \Phpcmf\Service::L('cache')->get('linkage-'.SITE_ID.'-'.$field['setting']['option']['linkage'], $code);
            if (!$linkage) {
                return dr_lang('选项无效');
            } elseif ($field['setting']['option']['ck_child'] && $linkage['child']) {
                return dr_lang('需要选择下级选项');
            }
        }


        return '';
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

		// 联动菜单缓存
		$linkage = \Phpcmf\Service::L('cache')->get('linkage-'.SITE_ID.'-'.$field['setting']['option']['linkage']);
        if (!$linkage) {
            if (CI_DEBUG) {
                return $this->input_format($name, $text, '<div class="form-control-static" style="color:red">联动菜单【'.$field['setting']['option']['linkage'].'】没有数据数据</div>');
            }
            return $this->input_format($name, $text, '');
        }
		$linkageid = \Phpcmf\Service::L('cache')->get('linkage-'.SITE_ID.'-'.$field['setting']['option']['linkage'].'-id');
		$linkagelevel = (int)\Phpcmf\Service::L('cache')->get('linkage-'.SITE_ID.'-'.$field['setting']['option']['linkage'].'-level');
		// 最大几层
		$linklevel = $linkagelevel + 1;
		// 开始输出
		$str = '';
        if (!$this->is_load_js($field['filetype'])) {
			$str.= '<script type="text/javascript" src="'.ROOT_THEME_PATH.'assets/js/jquery.ld.js?v='.CMF_UPDATE_TIME.'"></script>';
            $this->set_load_js($field['filetype'], 1);
		}

        // 表单宽度设置
        $width = \Phpcmf\Service::IS_MOBILE_USER() ? '100%' : ($field['setting']['option']['width'] ? $field['setting']['option']['width'] : '100%');
		$str.= '<div class="dropzone-file-area" style="text-align:left" id="linkages-'.$name.'-sort-items" style="width:'.$width.(is_numeric($width) ? 'px' : '').';">';
		$level = 1;


        // 输出默认菜单
        $tpl = '<div class="linkages_'.$name.'_row" id="dr_linkages_'.$name.'_row_{id}">';
        $tpl.= '<label style="margin-right: 10px;"><a class="btn btn-sm " href="javascript:;" onclick="$(\'#dr_linkages_'.$name.'_row_{id}\').remove()"> <i class="fa fa-close"></i> </a></label>';
        $tpl.= '<input type="hidden" name="data['.$name.'][{id}]" id="dr_'.$name.'_{id}" value="{value}" />';
        $tpl.= '<input type="hidden" id="dr_'.$name.'_{id}_default" value="" />';
        $tpl.= '<span id="dr_linkages_'.$name.'_select_{id}" style="display:{display}">';
        for ($i = 1; $i <= $linklevel; $i++) {
            $style = $i > $level ? 'style="display:none"' : '';
            $tpl.= '<label style="padding-right:10px;"><select class="form-control finecms-selects-'.$name.'-{id}" name="'.$name.'-'.$i.'-{id}" id="'.$name.'-'.$i.'-{id}" width="100" '.$style.'><option value=""> -- </option></select></label>';
        }
        $tpl.= '</span>';
        $tpl.= '</div>';

        // 字段默认值
        $values = dr_string2array($value);
        if ($values) {
            foreach ($values as $id => $value) {
                if ($value) {
                    $pids = substr($linkage[$linkageid[$value]]['pids'], 2);
                    $level = substr_count($pids, ',') + 1;
                    $default = !$pids ? '["'.$value.'"]' : '["'.str_replace(',', '","', $pids).'","'.$value.'"]';

                    $str.= '<div class="linkages_'.$name.'_row" id="dr_linkages_'.$name.'_row_'.$id.'">';
                    $str.= '<label style="margin-right: 10px;"><a class="btn btn-sm " href="javascript:;" onclick="$(\'#dr_linkages_'.$name.'_row_'.$id.'\').remove()"> <i class="fa fa-close"></i> </a></label>';
                    $str.= '<input type="hidden" name="data['.$name.']['.$id.']" id="dr_'.$name.'_'.$id.'" value="'.$value.'" />';
                    $str.= '<input type="hidden" id="dr_'.$name.'_'.$id.'_default" value="'.addslashes($default).'" />';
                    $str.= '<span id="dr_linkages_'.$name.'_select_'.$id.'" style="display:none">';
                    for ($i = 1; $i <= $linklevel; $i++) {
                        $style = $i > $level ? 'style="display:none"' : '';
                        $str.= '<label style="padding-right:10px;"><select class="form-control finecms-selects-'.$name.'-'.$id.'" name="'.$name.'-'.$i.'-'.$id.'" id="'.$name.'-'.$i.'-'.$id.'" width="100" '.$style.'><option value=""> -- </option></select></label>';
                    }
                    $str.= '</span>';
                    $str.= '<label class="form-control-static" id="dr_linkages_'.$name.'_cxselect_'.$id.'">'.dr_linkagepos($field['setting']['option']['linkage'], $value, ' » ').'&nbsp;&nbsp;<a href="javascript:;" onclick="dr_linkages_select_'.$name.'('.$id.')" style="color:blue">'.dr_lang('[重新选择]').'</a></label>';
                    $str.= '</div>';
                }
            }
        }


		// 整体
		$str.= '</div>';
        $str.= '<div class="margin-top-10">	<a href="javascript:;" class="btn blue btn-sm" onClick="dr_add_linkages_'.$name.'()"> <i class="fa fa-plus"></i> '.dr_lang('添加').' </a>';
        $str.= '</div>';
        $str.= '<script type="text/javascript">
        $("#linkages-'.$name.'-sort-items").sortable();
		function dr_add_linkages_'.$name.'() {
		    var num = $("#linkages-'.$name.'-sort-items .linkages_'.$name.'_row").length;
		    if ('.(int)$field['setting']['option']['limit'].' > 0 && num >= '.(int)$field['setting']['option']['limit'].') {
		        dr_tips(0, "'.dr_lang('最多可以选择%s项', $field['setting']['option']['limit']).'");
		        return;
		    }
			var id=(num + 1) * 10;
			var html = "'.addslashes($tpl).'";
			html = html.replace(/\{id\}/g, id);
			html = html.replace(/\{display\}/g, "blank");
			html = html.replace(/\{value\}/g, "0");
			$("#linkages-'.$name.'-sort-items").append(html);
			dr_linkages_init_'.$name.'(id);
		}
		function dr_linkages_select_'.$name.'(id) {
            $("#dr_linkages_'.$name.'_select_"+id).show();
            $("#dr_linkages_'.$name.'_cxselect_"+id).hide();
			dr_linkages_init_'.$name.'(id);
        }
        function dr_linkages_init_'.$name.'(id) {
          var $ld5 = $(".finecms-selects-'.$name.'-"+id);					  
            $ld5.ld({ajaxOptions:{"url": "/index.php?s=api&c=api&m=linkage&code='.$field['setting']['option']['linkage'].'"},defaultParentId:0})
            var ld5_api = $ld5.ld("api");
            ld5_api.selected($("#dr_'.$name.'_"+id+"_default").val());
            $ld5.bind("change", function(e){
                var $target = $(e.target);
                var index = $ld5.index($target);
                $("#dr_'.$name.'_"+id).val($ld5.eq(index).show().val());
                index ++;
                $ld5.eq(index).show();
            });
            
        }
		</script><span class="help-block">'.$tips.'</span>';
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

        $str = '';
        $values = dr_string2array($value);
        if ($values) {
            foreach ($values as $value) {
                $str.= '<div class="form-control-static">'.dr_linkagepos($field['setting']['option']['linkage'], $value, ' - ').'</div>';
            }
        }

        return $this->input_format($field['fieldname'], $field['name'], $str);
    }

}