<?php namespace Phpcmf\Field;
/**
 * {{www.xunruicms.com}}
 * {{迅睿内容管理框架系统}}
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

        $str2 = '<select class="form-control" name="data[setting][option][file]"><option value=""> -- </option>';
        $files = dr_file_map(CONFIGPATH.'mylinkage/', 1);
        $files2 = dr_file_map(dr_get_app_dir($this->app).'Config/mylinkage/', 1);
        $files2 && $files = dr_array2array($files2, $files);
        if ($files) {
            foreach ($files as $t) {
                $t && strpos($t, '.php') !== 0 && $str2.= '<option value="'.$t.'" '.($option['file'] == $t ? 'selected' : '').'> '.$t.' </option>';
            }
        }
        $str2.= '</select>';

		return ['<div class="form-group">
                  	<label class="col-md-2 control-label">'.dr_lang('选择菜单').'</label>
                    <div class="col-md-9"><label>'.$str.'</label></div>
                </div>
                
                <div class="form-group">
                    <label class="col-md-2 control-label">'.dr_lang('强制选择最终项').'</label>
                    <div class="col-md-9">
                        <div class="mt-radio-inline">
                            <label class="mt-radio mt-radio-outline"><input type="radio" value="1" name="data[setting][option][ck_child]" '.($option['ck_child'] == 1 ? 'checked' : '').' > '.dr_lang('开启').' <span></span></label>
                             &nbsp; &nbsp;
                             <label class="mt-radio mt-radio-outline"><input type="radio" value="0" name="data[setting][option][ck_child]" '.($option['ck_child'] == 0 ? 'checked' : '').' > '.dr_lang('关闭').' <span></span></label>
                        </div>
						<span class="help-block">'.dr_lang('开启后会强制要求用户选择最终一个选项，需要启用必须验证才会生效').'</span>
                    </div>
                </div>
                
                 <div class="form-group">
                    <label class="col-md-2 control-label">'.dr_lang('输入框显示方式').'</label>
                    <div class="col-md-9">
                        <div class="mt-radio-inline">
                         <label class="mt-radio mt-radio-outline"><input type="radio" value="1" name="data[setting][option][new]" '.($option['new'] == 1 ? 'checked' : '').' > '.dr_lang('折叠弹窗模式').' <span></span></label>
                        &nbsp; &nbsp;
                            <label class="mt-radio mt-radio-outline"><input type="radio" value="0" name="data[setting][option][new]" '.($option['new'] == 0 ? 'checked' : '').' > '.dr_lang('经典模式').' <span></span></label>
                             
                            </div>
						<span class="help-block">'.dr_lang('针对输入框效果的显示方式').'</span>
                    </div>
                </div>
			<div class="form-group">
				<label class="col-md-2 control-label">'.dr_lang('默认选择值').'</label>
				<div class="col-md-9">
					<label><input id="field_default_value" type="text" class="form-control" size="20" value="'.$option['value'].'" name="data[setting][option][value]"></label>
					<label>'.$this->member_field_select().'</label>
					<span class="help-block">'.dr_lang('用于字段为空时显示该填充值，并不会去主动变更数据库中的实际值；可以设置会员表字段，表示用当前登录会员信息来填充这个值').'</span>
				</div>
			</div>
				', ''];
	}
	
	/**
	 * 创建sql语句
	 */
	public function create_sql($name, $option, $cname = '') {
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
     * 验证字段值
     *
     * @param	string	$field	字段类型
     * @param	string	$value	字段值
     * @return
     */
    public function check_value($field, $value) {

        $value = intval($value);
        if ($value) {
            $link = dr_linkage($field['setting']['option']['linkage'], $value);
            if (!$link) {
                return dr_lang('选项无效');
            } elseif ($field['setting']['option']['ck_child'] && $link['child']) {
                return dr_lang('需要选择下级选项');
            }
        }

        return '';
    }

    /**
     * 验证必填字段值
     *
     * @param	string	$field	字段类型
     * @param	string	$value	字段值
     * @return
     */
    public function check_required($field, $value) {

        $value = intval($value);
        if (!$value) {
            // 验证值为空
            return dr_lang('%s不能为空', $field['name']);
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

        // 字段默认值
        $value = $value ? $value : $this->get_default_value($field['setting']['option']['value']);


        if ($field['setting']['option']['new']) {
            // 开始输出
            $str = '';
            if (!$this->is_load_js($field['fieldtype'])) {
                $str.= '
<link rel="stylesheet" type="text/css" href="'.ROOT_THEME_PATH.'assets/layui/css/layui.css?v='.CMF_UPDATE_TIME.'"/>
<link rel="stylesheet" type="text/css" href="'.ROOT_THEME_PATH.'assets/layui/cascader/cascader.css?v='.CMF_UPDATE_TIME.'"/>
<script src="'.ROOT_THEME_PATH.'assets/layui/layui.js?v='.CMF_UPDATE_TIME.'"></script>
<script src="'.ROOT_THEME_PATH.'assets/layui/cascader/cascader'.(IS_XRDEV ? '' : '.min').'.js?v='.CMF_UPDATE_TIME.'"></script>
            ';
                $this->set_load_js($field['fieldtype'], 1);
            }

            // 输出js支持
            $str.= '<input type="hidden" name="data['.$name.']" id="dr_'.$name.'" value="'.(int)$value.'" />
        <script src="'.WEB_DIR.'index.php?s=api&c=api&m=linkage&mid='.APP_DIR.'&file='.$field['setting']['option']['file'].'&code='.$field['setting']['option']['linkage'].'"></script>
		<script type="text/javascript">
		$(function (){
				layui.use(\'layCascader\', function () {
                var layCascader = layui.layCascader;
                layCascader({
                  elem: \'#dr_'.$name.'\',
                  value: \''.(int)$value.'\',
                    clearable: true,
                     filterable: true,
                  options: linkage_'.$field['setting']['option']['linkage'].',
                  props: {
                    checkStrictly: '.($field['setting']['option']['ck_child'] ? 'false' : 'true').',
                  }
                });
			})
				});
		</script>'.$tips;
        } else {
            // 联动菜单缓存
            $linkage = dr_linkage_list($field['setting']['option']['linkage'], 0);
            if (!$linkage) {
                if (CI_DEBUG) {
                    return $this->input_format($name, $text, '<div class="form-control-static" style="color:red">联动菜单【'.$field['setting']['option']['linkage'].'】没有数据</div>');
                }
                return $this->input_format($name, $text, '');
            }

            // 最大几层
            $linklevel = dr_linkage_level($field['setting']['option']['linkage']) + 1;

            // 开始输出
            $str = '<input type="hidden" name="data['.$name.']" id="dr_'.$name.'" value="'.(int)$value.'">';
            if (!$this->is_load_js($field['fieldtype'].'_LD')) {
                $str.= '<script type="text/javascript" src="'.ROOT_THEME_PATH.'assets/js/jquery.ld.js?v='.CMF_UPDATE_TIME.'"></script>';
                $this->set_load_js($field['fieldtype'].'_LD', 1);
            }
            $level = 1;
            $default = '';
            if ($value) {
                $link = dr_linkage($field['setting']['option']['linkage'], $value);
                if ($link) {
                    $pids = substr($link['pids'], 2);
                    $level = substr_count($pids, ',') + 1;
                    $default = !$pids ? '["'.$value.'"]' : '["'.str_replace(',', '","', $pids).'","'.$value.'"]';
                }
            }
            // 输出默认菜单
            $str.= '<span id="dr_linkage_'.$name.'_select" style="'.($value ? 'display:none' : '').'">';
            for ($i = 1; $i <= $linklevel; $i++) {
                $style = $i > $level ? 'style="display:none"' : '';
                $str.= '<label style="padding-right:10px;"><select class="form-control finecms-select-'.$name.'" name="'.$name.'-'.$i.'" id="'.$name.'-'.$i.'" width="100" '.$style.'><option value=""> -- </option></select></label>';
            }
            $str.= '<label id="dr_linkage_'.$name.'_html"></label>';
            $str.= '</span>';
            // 重新选择
            if ($value) {
                $str.= '<div id="dr_linkage_'.$name.'_cxselect">';
                $edit_html = '<div class="form-control-static" >'.dr_linkagepos($field['setting']['option']['linkage'], $value, ' » ').'&nbsp;&nbsp;<a href="javascript:;" onclick="dr_linkage_select_'.$name.'()" style="color:blue">'.dr_lang('[重新选择]').'</a></div>';
                if ($field['setting']['option']['file']) {
                    $data = dr_linkage($field['setting']['option']['linkage'], $value);
                    $file = CONFIGPATH.'mylinkage/'.$field['setting']['option']['file'];
                    $file2 = dr_get_app_dir(APP_DIR).'Config/mylinkage/'.$field['setting']['option']['file'];
                    if (is_file($file)) {
                        require $file;
                    } elseif (is_file($file2)) {
                        require $file2;
                    } else {
                        log_message('error', '联动菜单自定义程序文件【'.$field['setting']['option']['file'].'】不存在');
                        if (CI_DEBUG) {
                            $edit_html.= '联动菜单自定义程序文件【'.$field['setting']['option']['file'].'】不存在';
                        }
                    }
                }
                $str.= $edit_html;
                $str.= '</div>';
            }
            // 输出js支持
            $str.= '
		<script type="text/javascript">
			function dr_linkage_select_'.$name.'() {
				$("#dr_linkage_'.$name.'_select").show();
				$("#dr_linkage_'.$name.'_cxselect").hide();
			}
			$(function(){
				var $ld5 = $(".finecms-select-'.$name.'");					  
				$ld5.ld({ajaxOptions:{"url": "'.WEB_DIR.'index.php?s=api&c=api&m=linkage_ld&mid='.APP_DIR.'&file='.$field['setting']['option']['file'].'&code='.$field['setting']['option']['linkage'].'"},inputId:"dr_linkage_'.$name.'_html",defaultParentId:0});
				var ld5_api = $ld5.ld("api");
				ld5_api.selected('.$default.');
				$ld5.bind("change", function(e){
					var $target = $(e.target);
					var index = $ld5.index($target);
					//$("#'.$name.'-'.$i.'").remove();
					var vv = $ld5.eq(index).show().val();
					$("#dr_'.$name.'").val(vv);
					index ++;
					$ld5.eq(index).show();
					//console.log("value="+vv);
				});
			})
		</script>'.$tips;
        }


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