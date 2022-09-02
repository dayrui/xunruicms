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
						<span class="help-block">'.dr_lang('开启后会强制要求用户选择最终一个选项，需要启用必须验证才会生效').'</span>
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
            $data = dr_string2array($data);
            if ($data) {
                foreach ($data as $t) {
                    if ($t) {
                        $save[] = $t;
                    }
                }
                $save = array_unique($save);
            }
        }
        // 判断超限
        if ($field['setting']['option']['limit'] && dr_count($save) > $field['setting']['option']['limit']) {
            $save = array_slice($save, 0, $field['setting']['option']['limit']);
        }

        \Phpcmf\Service::L('Field')->data[$field['ismain']][$field['fieldname']] = dr_array2string($save);
	}

    /**
     * 验证字段值
     *
     * @param	string	$field	字段类型
     * @param	string	$value	字段值
     * @return
     */
    public function check_value($field, $value) {

        $values = dr_string2array($value);
        if ($values) {
            foreach ($values as $value) {
                $link = dr_linkage($field['setting']['option']['linkage'], $value);
                if (!$link) {
                    return dr_lang('选项无效');
                } elseif ($field['setting']['option']['ck_child'] && $link['child']) {
                    return dr_lang('需要选择下级选项');
                }
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
    public function check_required($field, $values) {

        if (!$values) {
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
        $value = dr_string2array($value);
        if (is_array($value)) {
            $new = [];
            foreach ($value as $t) {
                $new[] = (string)$t;
            }
            $value = json_encode($new);
        } else {
            $value = '[]';
        }

        // 开始输出
        $str = '';
        if (!$this->is_load_js('Linkage')) {
            $str.= '
<link rel="stylesheet" type="text/css" href="'.ROOT_THEME_PATH.'assets/layui/css/layui.css?v='.CMF_UPDATE_TIME.'"/>
<link rel="stylesheet" type="text/css" href="'.ROOT_THEME_PATH.'assets/layui/cascader/cascader.css?v='.CMF_UPDATE_TIME.'"/>
<script src="'.ROOT_THEME_PATH.'assets/layui/layui.js?v='.CMF_UPDATE_TIME.'"></script>
<script src="'.ROOT_THEME_PATH.'assets/layui/cascader/cascader.js?v='.CMF_UPDATE_TIME.'"></script>
            ';
            $this->set_load_js('Linkage', 1);
        }

        // 输出js支持
        $str.= '<input type="hidden" name="data['.$name.']" id="dr_'.$name.'" value="'.$value.'" />
        <script src="/index.php?s=api&c=api&m=linkage&mid='.APP_DIR.'&file='.$field['setting']['option']['file'].'&code='.$field['setting']['option']['linkage'].'"></script>
		<script type="text/javascript">
				$(function (){
                    layui.use(\'layCascader\', function () {
                var layCascader = layui.layCascader;
                layCascader({
                  elem: \'#dr_'.$name.'\',
                    value: '.$value.',
                    clearable: true,
                     filterable: false,
                    maxSize: '.intval($field['setting']['option']['limit']).',
                  collapseTags: true,
                  minCollapseTagsNumber: 0,
                  options: linkage_'.$field['setting']['option']['linkage'].',
                  props: {
                    multiple: true,
                    checkStrictly: '.($field['setting']['option']['ck_child'] ? 'false' : 'true').',
                  }
                });
			})
				});
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