<?php namespace Phpcmf\Field;

/**
 * http://www.xunruicms.com
 * 本文件是框架系统文件，二次开发时不可以修改本文件，可以通过继承类方法来重写此文件
 **/

class Ftable extends \Phpcmf\Library\A_Field {

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

        $html = '';
        for ($i = 1; $i <= 10; $i++) {
            $html.= '<div class="form-group">
				<label class="col-md-2 control-label">'.dr_lang('表格第%s列', $i).'</label>
				<div class="col-md-9">
					<label>'.$this->_field_type_select($i, $option['field'][$i]['type']).'</label>
					<label><input type="text" placeholder="'.dr_lang('列名称').'" class="form-control" size="20" value="'.$option['field'][$i]['name'].'" name="data[setting][option][field]['.$i.'][name]"></label>
					<label><input type="text" placeholder="'.dr_lang('列宽度').'" class="form-control" size="20" value="'.$option['field'][$i]['width'].'" name="data[setting][option][field]['.$i.'][width]"></label>
					<label id="dr_h_type_2"><input type="text" placeholder="'.dr_lang('选择项').'" class="form-control" size="20" value="'.$option['field'][$i]['option'].'" name="data[setting][option][field]['.$i.'][option]"></label>
				</div>
			</div>';
        }

        $hang = '';
        $hs = (int)$option['count'];
        !$hs && $hs = 5;
        for ($i = 1; $i <= $hs; $i++) {
            $hang.= '<div class="form-group is_first_hang">
				<label class="col-md-2 control-label">'.dr_lang('表格第%s行', $i).'</label>
				<div class="col-md-9">
					<label><input type="text" placeholder="'.dr_lang('行名称').'" class="form-control" size="20" value="'.$option['hang'][$i]['name'].'" name="data[setting][option][hang]['.$i.'][name]"></label>
				</div>
			</div>';
        }

        return ['
            <script>
            $(function() {
              dr_is_first_hang('.(int)$option['is_first_hang'].');
              dr_is_add_hang('.(int)$option['is_add'].');
            });
            function dr_is_first_hang(v) {
                if (v == 1) {
                    $(".is_first_hang").show();
                } else {
                    $(".is_first_hang").hide();
                }
            }
            function dr_is_add_hang(v) {
                if (v == 1) {
                    $(".is_add_hang").hide();
                } else {
                    $(".is_add_hang").show();
                }
            }
            </script>
            <div class="form-group">
				<label class="col-md-2 control-label">'.dr_lang('无限添加行数').'</label>
				<div class="col-md-9">
                    <div class="mt-radio-inline">
                        <label class="mt-radio mt-radio-outline"><input type="radio" onclick="dr_is_add_hang(this.value)" name="data[setting][option][is_add]" value="1" '.($option['is_add'] ? 'checked' : '').'> '.dr_lang('开启').'  <span></span></label>
                        <label class="mt-radio mt-radio-outline"><input type="radio" onclick="dr_is_add_hang(this.value)" name="data[setting][option][is_add]" value="0" '.(!$option['is_add'] ? 'checked' : '').'> '.dr_lang('关闭').'  <span></span></label>
                    </div>
                    <span class="help-block">'.dr_lang('开启后可以在录入数据时自由添加行数，关闭时就只能是固定行数').'</span>
                </div>
			</div>
            <div class="form-group is_add_hang">
				<label class="col-md-2 control-label">'.dr_lang('表格的首行').'</label>
				<div class="col-md-9">
                    <div class="mt-radio-inline">
                        <label class="mt-radio mt-radio-outline"><input type="radio" onclick="dr_is_first_hang(this.value)" name="data[setting][option][is_first_hang]" value="1" '.($option['is_first_hang'] ? 'checked' : '').'> '.dr_lang('显示').'  <span></span></label>
                        <label class="mt-radio mt-radio-outline"><input type="radio" onclick="dr_is_first_hang(this.value)" name="data[setting][option][is_first_hang]" value="0" '.(!$option['is_first_hang'] ? 'checked' : '').'> '.dr_lang('隐藏').'  <span></span></label>
                    </div>
                    <span class="help-block">'.dr_lang('首行表示每行的行名').'</span>
                </div>
			</div>
			<div class="is_add_hang">
            <div class="form-group ">
				<label class="col-md-2 control-label">'.dr_lang('表格行数').'</label>
				<div class="col-md-9">
					<label><input type="text" class="form-control" size="10" name="data[setting][option][count]" value="'.$option['count'].'"></label>
					<span class="help-block">'.dr_lang('填写表格的行数，1表示只有一行表格，至少属于1行表格').'</span>
				</div>
			</div>
			<div class="form-group is_first_hang">
				<label class="col-md-2 control-label">'.dr_lang('表格首行名').'</label>
				<div class="col-md-9">
					<label><input type="text" class="form-control" size="10" name="data[setting][option][first_cname]" value="'.$option['first_cname'].'"></label>
					<span class="help-block">'.dr_lang('表格第一行的显示名称，不填写就不显示名称').'</span>
				</div>
			</div>'.$hang.'
			</div>'.$html.'
			
			<div class="form-group">
				<label class="col-md-2 control-label">'.dr_lang('名词解释').'</label>
				<div class="col-md-9">
					<div class="form-control-static">
					    <p>'.dr_lang('列名称：是表格列的显示名称').'</p>
					    <p>'.dr_lang('列宽度：是表格列的宽度，[整数]表示固定宽度；[整数%]表示百分比').'</p>
					    <p>'.dr_lang('选择项：用于下拉选择框的选项，多个选项用半角,分开').'</p>
					    <p>'.dr_lang('行名称：是表格每一行的显示名称，如果不填就按照默认行名称显示，如果默认行名称也没有填写就不显示行名').'</p>
					    <span class="help-block"> <a href="javascript:dr_help(\'644\');"> '.dr_lang('了解此字段的使用方法').'</a> </span>
                    </div>
				</div>
			</div>
			',
            '<div class="form-group">
				<label class="col-md-2 control-label">'.dr_lang('控件宽度').'</label>
				<div class="col-md-9">
					<label><input type="text" class="form-control" size="10" name="data[setting][option][width]" value="'.$option['width'].'"></label>
					<span class="help-block">'.dr_lang('[整数]表示固定宽度；[整数%]表示百分比').'</span>
				</div>
			</div>
			<div class="form-group">
				<label class="col-md-2 control-label">'.dr_lang('控件高度').'</label>
				<div class="col-md-9">
					<label><input type="text" class="form-control" size="10" name="data[setting][option][height]" value="'.$option['height'].'"></label>
					<label>px</label>
				</div>
			</div>'
        ];
    }

    private function _field_type_select($id, $type) {

        $arr = [
            0 => dr_lang('不使用'),
            1 => dr_lang('文本框'),
            2 => dr_lang('下拉选择框'),
        ];

        $html = '<select class="form-control" name="data[setting][option][field]['.$id.'][type]">';
        foreach ($arr as $i => $name) {
            $html.= '<option '.($i == $type ? 'selected' : '').' value="'.$i.'">'.$name.'</option>';
        }
        $html.= '</select>';

        return $html;
    }

    private function _field_type_html($config, $cname, $value, $hang, $lie) {

        $html = '';
        if ($config['type'] == 1) {
            $html.= '<label><input type="text" class="form-control" name="data['.$cname.']['.$hang.']['.$lie.']" value="'.$value[$hang][$lie].'"></label>';
        } elseif ($config['type'] == 2) {
            $html = '<label><select class="form-control" name="data['.$cname.']['.$hang.']['.$lie.']">';
            $arr = explode(',', $config['option']);
            foreach ($arr as $name) {
                $html.= '<option '.($value[$hang][$lie] == $name ? 'selected' : '').' value="'.$name.'">'.$name.'</option>';
            }
            $html.= '</select></label>';
        }

        return $html;
    }



    /**
     * 字段显示
     *
     * @return  string
     */
    public function show($field, $value = null) {

        // 字段默认值
        $value = dr_string2array($value);

        $str = '<div class="table-scrollable">';
        $str.= dr_get_ftable($field['id'], $value);
        $str.= '</div>';

        return $this->input_format($field['fieldname'], $field['name'], $str);
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
        $data = \Phpcmf\Service::L('Field')->post[$field['fieldname']];
        \Phpcmf\Service::L('Field')->data[$field['ismain']][$field['fieldname']] = dr_array2string($data);
    }

    /**
     * 字段表单输入
     */
    public function input($field, $value = '') {

        // 字段禁止修改时就返回显示字符串
        if ($this->_not_edit($field, $value)) {
            return $this->show($field, $value);
        }

        // 字段存储名称
        $name = $field['fieldname'];

        // 字段显示名称
        $text = ($field['setting']['validate']['required'] ? '<span class="required" aria-required="true"> * </span>' : '').$field['name'];

        // 表单宽度设置
        $width = \Phpcmf\Service::_is_mobile() ? '100%' : ($field['setting']['option']['width'] ? $field['setting']['option']['width'] : '100%');

        // 字段提示信息
        $tips = ($name == 'title' && APP_DIR) || $field['setting']['validate']['tips'] ? '<span class="help-block" id="dr_'.$field['fieldname'].'_tips">'.$field['setting']['validate']['tips'].'</span>' : '';


        // 字段默认值
        $value = dr_string2array($value);

        $str = '<div class="table-scrollable">';
        $str.= '<table class="table table-nomargin table-bordered table-striped table-bordered table-advance" style="width:'.$width.(is_numeric($width) ? 'px' : '').';">';
        $str.= ' <thead><tr>';
        if ($field['setting']['option']['is_first_hang'] && !$field['setting']['option']['is_add']) {
            $str.= ' <th> '.$field['setting']['option']['first_cname'].' </th>';
        }
        if ($field['setting']['option']['field']) {
            foreach ($field['setting']['option']['field'] as $t) {
                if ($t['type']) {
                    $style = $t['width'] ? 'style="width:'.$t['width'].(is_numeric($t['width']) ? 'px' : '').';"' : '';
                    $str.= ' <th '.$style.'>'.$t['name'].'</th>';
                }
            }
        }
        if ($field['setting']['option']['is_add']) {
            $str.= ' <th width="50" style="text-align: center">'.dr_lang('删除').'</th>';
        }
        $str.= ' </tr></thead>';
        $str.= ' <tbody id="dr_'.$name.'_body">';

        if ($field['setting']['option']['is_add']) {
            // 支持添加列
            $tpl = ' <tr>';
            if ($field['setting']['option']['field']) {
                foreach ($field['setting']['option']['field'] as $n => $t) {
                    if ($t['type']) {
                        $tpl.= ' <td>'.$this->_field_type_html($t, $name, $value, '{hang}', $n).'</td>';
                    }
                }
            }
            $tpl.= ' <td style="text-align: center"><button type="button" class="btn red btn-xs" onClick="dr_del_table_'.$name.'(this)"> <i class="fa fa-trash"></i> </button></td>';
            $tpl.= ' </tr>';
            $ksid = 0; // 开始ID
            if ($value) {
                foreach ($value as $hang => $t) {
                    $str.= ' <tr>';
                    if ($field['setting']['option']['field']) {
                        foreach ($field['setting']['option']['field'] as $n => $t) {
                            if ($t['type']) {
                                $str.= ' <td>'.$this->_field_type_html($t, $name, $value, $hang, $n).'</td>';
                            }
                        }
                    }
                    $str.= ' <td style="text-align: center"><button type="button" class="btn red btn-xs" onClick="dr_del_table_'.$name.'(this)"> <i class="fa fa-trash"></i> </button></td>';
                    $str.= ' </tr>';
                    $ksid++;
                }
            }
        } else {
            // 固定列
            for ($i = 1; $i <= (int)$field['setting']['option']['count']; $i++) {

                $str.= ' <tr>';
                if ($field['setting']['option']['is_first_hang']) {
                    $str.= ' <td> '.($field['setting']['option']['hang'][$i]['name'] ? $field['setting']['option']['hang'][$i]['name'] : '未命名').' </td>';
                }
                if ($field['setting']['option']['field']) {
                    foreach ($field['setting']['option']['field'] as $n => $t) {
                        if ($t['type']) {
                            $str.= ' <td>'.$this->_field_type_html($t, $name, $value, $i, $n).'</td>';
                        }
                    }
                }
                $str.= ' </tr>';
            }
        }


        $str.= ' </tbody>';
        $str.= '</table>';
        $str.= '</div>';
        if ($field['setting']['option']['is_add']) {
            $str.= '<div class="table-add">';
            $str.= '<button type="button" class="btn blue btn-sm" onClick="dr_add_table_'.$name.'()"> <i class="fa fa-plus"></i> '.dr_lang('添加一行').'</button>';
            $str.= '<script>
                var ks_'.$name.' = '.json_encode(['tpl' => $tpl, 'id' => $ksid]).';
                function dr_del_table_'.$name.'(e) {
                    layer.confirm(\'确定删除本条数据吗？\', {
                    shade: 0,
                    title: \'提示\',
                    }, function(index, layero){
                       layer.close(index);
                        $(e).parent().parent().remove();
                    });
                }
                function dr_add_table_'.$name.'() {
                  var tpl = ks_'.$name.'.tpl;
                  ks_'.$name.'.id ++;
            tpl = tpl.replace(/\{hang\}/g, ks_'.$name.'.id);
            $(\'#dr_'.$name.'_body\').append(tpl);
                }
</script>';
            $str.= '</div>';
        }
        $str.= '<script> $("#dr_'.$name.'_body").sortable();</script>';

        return $this->input_format($name, $text, $str.$tips);
    }

}