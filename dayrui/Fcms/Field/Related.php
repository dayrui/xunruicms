<?php namespace Phpcmf\Field;
/**
 * {{www.xunruicms.com}}
 * {{迅睿内容管理框架系统}}
 * 本文件是框架系统文件，二次开发时不可以修改本文件，可以通过继承类方法来重写此文件
 **/

class Related extends \Phpcmf\Library\A_Field {
	
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
	
		$_option = '';
		$_module = \Phpcmf\Service::C()->get_cache('module-'.SITE_ID.'-content');
		if ($_module) {
			foreach ($_module as $dir => $t) {
				$_option.= '<option value="'.$dir.'" '.($dir == $option['module'] ? 'selected' : '').'>'.$t['name'].'</option>';
			}
		}
		
		return [$this->_search_field().'<div class="form-group">
                    <label class="col-md-2 control-label">'.dr_lang('内容模块').'</label>
                    <div class="col-md-9">
                    <label><select class="form-control" name="data[setting][option][module]">
					'.$_option.'
					</select></label>
					<span class="help-block">'.dr_lang('必须选择一个模块作为关联数据源').'</span>
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-md-2 control-label">'.dr_lang('显示归属内容').'</label>
                    <div class="col-md-9">
                        <input type="checkbox" name="data[setting][option][my]" '.($option['my'] ? 'checked' : '').' value="1"  data-on-text="'.dr_lang('开启').'" data-off-text="'.dr_lang('关闭').'" data-on-color="success" data-off-color="danger" class="make-switch" data-size="small">
                        <span class="help-block">'.dr_lang('开启之后只显示当前登录的用户自己所发布的内容').'</span>
                    </div>
                </div>
				<div class="form-group">
                    <label class="col-md-2 control-label">'.dr_lang('主题显示名称').'</label>
                    <div class="col-md-9">
                    <label><input type="text" class="form-control" size="10" name="data[setting][option][title]" value="'.($option['title'] ? $option['title'] : dr_lang('主题')).'"></label>
					<span class="help-block">'.dr_lang('用于显示管理的主题字段名称，默认是：主题').'</span>
                    </div>
                </div>
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
        // 模块名称
		$module = $mid = isset($field['setting']['option']['module']) ? $field['setting']['option']['module'] : '';
		// 字段显示名称
        $text = ($field['setting']['validate']['required'] ? '<span class="required" aria-required="true"> * </span>' : '').dr_lang($field['name']);
        // 选择数量限制
        $limit = intval($field['setting']['option']['limit']);
        !$limit && $limit = 99999;
        // 输出信息
        $cname = ($field['setting']['option']['title'] ? $field['setting']['option']['title'] : dr_lang('主题'));

        if (!$module) {
            if (CI_DEBUG) {
                return $this->input_format($name, $text, '<div class="form-control-static" style="color:red">关联字段没有设置关联模块</div>');
            }
            return $this->input_format($name, $text, '');
        } elseif (!dr_is_module($module)) {
            if (CI_DEBUG) {
                return $this->input_format($name, $text, '<div class="form-control-static" style="color:red">关联字段设置的模块【'.$module.'】没有被安装</div>');
            }
            return $this->input_format($name, $text, '');
        }

        $value = trim($value, ',');
        $mylist = [];
        if ($value && is_string($value)) {
            $arr = explode(',', $value);
            if ($arr) {
                $value = '';
                foreach ($arr as $a) {
                    $a = intval($a);
                    if ($a) {
                        $value.= ','.$a;
                    }
                }
                if ($value) {
                    $value = trim($value, ',');
                    $db = \Phpcmf\Service::M()->db->query('select id,title,catid,updatetime,uid,url from '.\Phpcmf\Service::M()->dbprefix(dr_module_table_prefix($module)).' where id IN ('.$value.') order by instr("'.$value.'", id)');
                    $mylist = $db ? $db->getResultArray() : [];
                }
            }
		}

        $file = \Phpcmf\Service::V()->code2php(
            file_get_contents(is_file(MYPATH.'View/api_related_field.html') ? MYPATH.'View/api_related_field.html' : COREPATH.'View/api_related_field.html')
        );
        ob_start();
        require $file;
        $str = ob_get_clean();

		$str.= '<p>';
		$str.= '<button type="button" class="btn blue btn-sm" onClick="dr_add_related_'.$name.'()"> <i class="fa fa-plus"></i> '.dr_lang('关联内容').'</button>';
        $str.= '</p>';
        $str.= $tips;
        $js = \Phpcmf\Service::L('js_packer');
        $str.= $js->pack('
		<script type="text/javascript">
		
        dr_slimScroll_init(".scroller_'.$name.'_files", 300);
        $("#related_'.$name.'-sort-items").sortable();
		function dr_add_related_'.$name.'() {
		    var len = $(\'#related_'.$name.'-sort-items tr\').length;
		    if (len >= '.$limit.') {
		        dr_tips(0, "'.dr_lang('关联数量超限').'");
		        return;
		    }
		    var url = "'.WEB_DIR.'index.php?s=api&c=api&m=related&name='.$name.'&site='.SITE_ID.'&module='.$module.'&my='.intval($field['setting']['option']['my']).'.&pagesize='.intval($field['setting']['option']['pagesize']).'&is_ajax=1";
            layer.open({
                type: 2,
                title: \'<i class="fa fa-cog"></i> '.dr_lang('关联内容').'\',
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
                                        if ($(\'#related_'.$name.'-sort-items tr\').length >= '.$limit.') {
                                            dr_tips(0, "'.dr_lang('关联数量超限').'");
                                            return;
                                        }
                                    }
                                }
                                 $(\'#related_'.$name.'-sort-items\').append(json.data.html);
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


        $cname = ($field['setting']['option']['title'] ? $field['setting']['option']['title'] : dr_lang('主题'));
        $value = @trim($value, ',');
        $mylist = [];
        $is_show = 1;
        $module = $mid = isset($field['setting']['option']['module']) ? $field['setting']['option']['module'] : '';
        if ($value && is_string($value) && $module) {
            $arr = explode(',', $value);
            if ($arr) {
                $value = '';
                foreach ($arr as $a) {
                    $a = intval($a);
                    if ($a) {
                        $value.= ','.$a;
                    }
                }
                if ($value) {
                    $value = trim($value, ',');
                    $db = \Phpcmf\Service::M()->db->query('select id,title,catid,updatetime,uid,url from '.\Phpcmf\Service::M()->dbprefix(dr_module_table_prefix($module)).' where id IN ('.$value.') order by instr("'.$value.'", id)');
                    $mylist = $db ? $db->getResultArray() : [];
                }
            }
        }

        $file = \Phpcmf\Service::V()->code2php(
            file_get_contents(is_file(MYPATH.'View/api_related_field.html') ? MYPATH.'View/api_related_field.html' : COREPATH.'View/api_related_field.html')
        );
        ob_start();
        require $file;
        $str = ob_get_clean();

        return $this->input_format($field['fieldname'], $field['name'], $str);
    }
}