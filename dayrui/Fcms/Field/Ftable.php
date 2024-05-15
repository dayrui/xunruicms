<?php namespace Phpcmf\Field;
/**
 * {{www.xunruicms.com}}
 * {{迅睿内容管理框架系统}}
 * 本文件是框架系统文件，二次开发时不可以修改本文件，可以通过继承类方法来重写此文件
 **/

class Ftable extends \Phpcmf\Library\A_Field {

    protected $_load_date;
    protected $_load_datetime;

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
        for ($i = 1; $i <= 30; $i++) {
            $html.= '<div class="form-group">
				<label class="col-md-2 control-label">'.dr_lang('表格第%s列', $i).'</label>
				<div class="col-md-9">
					<label>'.$this->_field_type_select($i, $option['field'][$i]['type']).'</label>
					<label><input type="text" placeholder="'.dr_lang('列名称').'" class="form-control" size="20" value="'.$option['field'][$i]['name'].'" name="data[setting][option][field]['.$i.'][name]"></label>
					<label><input type="text" placeholder="'.dr_lang('列宽度').'" class="form-control" size="20" value="'.$option['field'][$i]['width'].'" name="data[setting][option][field]['.$i.'][width]"></label>
					<label id="dr_h_type_2"><input type="text" placeholder="'.dr_lang('选项配置').'" class="form-control input-xlarge" size="20" value="'.$option['field'][$i]['option'].'" name="data[setting][option][field]['.$i.'][option]">
					</label><label>&nbsp;<a href="javascript:dr_help(\'1234\');"><i class="fa fa-question-circle"></i></a>
					</label>
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

        return [$this->_search_field().'
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
				<label class="col-md-2 control-label"></label>
				<div class="col-md-9">
					    <p>'.dr_lang('列名称：是表格列的显示名称').'</p>
					    <p>'.dr_lang('列宽度：是表格列的宽度，[整数]表示固定宽度；[整数%]表示百分比').'</p>
					    <p>'.dr_lang('选择项：仅用于下拉选择框和复选框的选项，多个选项用半角,分开').'</p>
					    <p>'.dr_lang('行名称：是表格每一行的显示名称，如果不填就按照默认行名称显示，如果默认行名称也没有填写就不显示行名').'</p>
					    <span class="help-block"> <a href="javascript:dr_help(\'644\');"> '.dr_lang('了解此字段的使用方法').'</a> </span>
                  
				</div>
			</div>
			'.$this->attachment($option).'
			',
            '<div class="form-group">
				<label class="col-md-2 control-label">'.dr_lang('控件宽度').'</label>
				<div class="col-md-9">
					<label><input type="text" class="form-control" size="10" name="data[setting][option][width]" value="'.$option['width'].'"></label>
					<span class="help-block">'.dr_lang('[整数]表示固定宽度；[整数%]表示百分比').'</span>
				</div>
			</div>'
        ];
    }

    // 属性类别
    protected function _field_type_select($id, $type) {

        $arr = [
            0 => dr_lang('不使用'),
            1 => dr_lang('文本框'),
            2 => dr_lang('下拉框'),
            4 => dr_lang('复选框'),
            3 => dr_lang('文件'),
            5 => dr_lang('日期'),
            6 => dr_lang('日期时间'),
            7 => dr_lang('多行文本'),
            //8 => dr_lang('富文本框'), 实验失败
        ];

        $html = '<select class="form-control" name="data[setting][option][field]['.$id.'][type]">';
        foreach ($arr as $i => $name) {
            $html.= '<option '.($i == $type ? 'selected' : '').' value="'.$i.'">'.$name.'</option>';
        }
        $html.= '</select>';

        return $html;
    }

    // 对应的html
    protected function _field_type_html($config, $cname, $value, $hang, $lie, $field = []) {

        $html = '';
        if ($config['type'] == 1) {
            $html.= '<input type="text" class="form-control" name="data['.$cname.']['.$hang.']['.$lie.']" value="'.htmlspecialchars((string)$value[$hang][$lie]).'">';
        } elseif ($config['type'] == 2) {
            $html = '<select class="form-control" name="data['.$cname.']['.$hang.']['.$lie.']">';
            $arr = explode(',', $config['option']);
            foreach ($arr as $name) {
                $html.= '<option '.($value[$hang][$lie] == $name ? 'selected' : '').' value="'.$name.'">'.$name.'</option>';
            }
            $html.= '</select>';
        } elseif ($config['type'] == 3) {
            // 文件
            $link = '';
            $preview = ROOT_THEME_PATH.'assets/images/ext/url.png';
            if ($value[$hang][$lie]) {
                $file = \Phpcmf\Service::C()->get_attachment((string)$value[$hang][$lie], 1);
                if ($file) {
                    $link = $file['url'];
                    if (dr_is_image($file['fileext'])) {
                        $preview = $link;
                    } elseif (is_file(ROOTPATH.'static/assets/images/ext/'.$file['fileext'].'.png')) {
                        $preview = ROOT_THEME_PATH.'assets/images/ext/'.$file['fileext'].'.png';
                    }
                }
            }
            if ($config['option'] && strpos((string)$config['option'], '-') !== false) {
                list($size, $exts) = explode('-', $config['option']);
            } else {
                $size = 10;
                $exts =  $config['option'];
            }
            $url = '/'.(IS_ADMIN ? SELF.'?c=api' : 'index.php?s=api&c=file').'&m=input_file_list&token='.dr_get_csrf_token().'&siteid='.SITE_ID.'&p='.dr_authcode([
                'size' => $size ? $size : 10,
                'exts' => $exts ? $exts : 'jpg,gif,png,jpeg',
                'count' => 1,
                'attachment' => $field['setting']['option']['attachment'],
                'image_reduce' => $field['setting']['option']['image_reduce'],
            ], 'ENCODE').'&ct=0&one=1';
            $html = '<label><input class="form-control2 form-control-file" type="hidden" name="data['.$cname.']['.$hang.']['.$lie.']" value="'.$value[$hang][$lie].'">';
            $html.= '<input class="form-control-link" type="hidden" value="'.$link.'">';
            $html.= '<input class="form-control-preview" type="hidden" value="'.$preview.'">';
            $html.= '<a href="javascript:;" onclick="dr_ftable_myfileinput(this, \''.$url.'\')" class="ftable-fileinput btn green btn-sm">'.dr_lang('上传').'</a>';
            $html.= '<a href="javascript:;" onclick="dr_ftable_myshow(this)" '.($value[$hang][$lie] ? '':'style="display:none"').' class="ftable-show btn blue btn-sm">'.dr_lang('预览').'</a>
			<a href="javascript:;" onclick="dr_ftable_mydelete(this)" '.($value[$hang][$lie] ? '':'style="display:none"').' class="ftable-delete btn red btn-sm">'.dr_lang('删除').'</a> ';
            $html.= '</label>';
        } elseif ($config['type'] == 5) {
            // 日期
            if ($config['option'] == 'SYS_TIME' && !$value[$hang][$lie]) {
                $value[$hang][$lie] = dr_date(SYS_TIME, 'Y-m-d');
            }
            $html.= '<div class="input-group date field_date_ftable_'.$cname.'">';
            $html.= '<input class="form-control" type="text" name="data['.$cname.']['.$hang.']['.$lie.']" value="'.$value[$hang][$lie].'">';
            $html.= '<span class="input-group-btn">
					<button class="btn date-set" type="button">
						<i class="fa fa-calendar"></i>
					</button>
				</span>';
            $html.= '</div>';
                $html.= '
                <script>
                $(function(){
                    $(".field_date_ftable_'.$cname.'").datepicker({
                        isRTL: false,
                        format: "yyyy-mm-dd",
                        showMeridian: true,
                        autoclose: true,
                        pickerPosition: "bottom-right",
                        todayBtn: "linked"
                    });
                });
                </script>
                ';
        } elseif ($config['type'] == 6) {
            // 日期时间
            if ($config['option'] == 'SYS_TIME' && !$value[$hang][$lie]) {
                $value[$hang][$lie] = dr_date(SYS_TIME, 'Y-m-d H:i:s');
            }
            $html.= '<div class="input-group date field_datetime_ftable_'.$cname.'">';
            $html.= '<input class="form-control" type="text" name="data['.$cname.']['.$hang.']['.$lie.']" value="'.$value[$hang][$lie].'">';
            $html.= '<span class="input-group-btn">
					<button class="btn date-set" type="button">
						<i class="fa fa-calendar"></i>
					</button>
				</span>';
            $html.= '</div>';
                $html.= '
                <script>
                $(function(){
                    $(".field_datetime_ftable_'.$cname.'").datetimepicker({
                        isRTL: false,
                        format: "yyyy-mm-dd hh:ii:ss",
                        showMeridian: true,
                        autoclose: true,
                        pickerPosition: "bottom-right",
                        todayBtn: "linked"
                    });
                });
                </script>
                ';
        } elseif ($config['type'] == 4) {
            $html = '<div class="table-scrollable" style="border: none; margin: 0!important"><div class="mt-checkbox-inline">';
            $arr = explode(',', $config['option']);
            foreach ($arr as $name) {
                $s = is_array($value[$hang][$lie]) && in_array($name, $value[$hang][$lie]) ? ' checked' : '';
                $kj = '<input type="checkbox" name="data['.$cname.']['.$hang.']['.$lie.'][]" value="'.$name.'" '.$s.' />';
                $html.= '<label class="mt-checkbox mt-checkbox-outline">'.$kj.' '.$name.' <span></span> </label>';
            }
            $html.= '</div></div>';
        } elseif ($config['type'] == 7) {
            $name = 'ftable_'.$cname.'_'.$hang.'_'.$lie;
            $html = '<textarea style="display:none" id="'.$name.'" name="data['.$cname.']['.$hang.']['.$lie.']">'.$value[$hang][$lie].'</textarea>';
            $html.= '<a href="javascript:;" onclick="dr_ftable_textareainput(\''.$name.'\')" class="ftable-fileinput btn green btn-sm">'.dr_lang('录入内容').'</a>';
        } elseif ($config['type'] == 8) {

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
        \Phpcmf\Service::L('Field')->data[$field['ismain']][$field['fieldname']] = dr_array2string(\Phpcmf\Service::L('Field')->post[$field['fieldname']]);
    }

    /**
     * 获取附件id
     */
    public function get_attach_id($value) {

        if (!$this->field) {
            return NULL;
        } elseif (!isset($this->field['setting']['option']['field'])) {
            return NULL;
        }

        $data = [];
        $value = dr_string2array($value);

        if ($value) {
            foreach ($value as $tt) {
                foreach ($this->field['setting']['option']['field'] as $lie => $t) {
                    if ($t['type'] == 3 && isset($tt[$lie]) && $tt[$lie]) {
                        $data[] = (int)$tt[$lie];
                    }
                }
            }
        }

        return $data;
    }

    /**
     * 附件处理
     */
    public function attach($data, $_data) {

        if (!$this->field) {
            return NULL;
        }

        $data = $this->get_attach_id($data);
        $_data = $this->get_attach_id($_data);

        if (!isset($_data)) {
            $_data = [];
        }

        if (!isset($data)) {
            $data = [];
        }

        // 新旧数据都无附件就跳出
        if (!$data && !$_data) {
            return NULL;
        }

        // 新旧数据都一样时表示没做改变就跳出
        if (dr_diff($data, $_data)) {
            return NULL;
        }

        // 当无新数据且有旧数据表示删除旧附件
        if (!$data && $_data) {
            return [
                [],
                $_data
            ];
        }

        // 当无旧数据且有新数据表示增加新附件
        if ($data && !$_data) {
            return [
                $data,
                []
            ];
        }

        // 剩下的情况就是删除旧文件增加新文件

        // 新旧附件的交集，表示固定的
        $intersect = array_intersect($data, $_data);

        return [
            array_diff($data, $intersect), // 固有的与新文件中的差集表示新增的附件
            array_diff($_data, $intersect), // 固有的与旧文件中的差集表示待删除的附件
        ];
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
        $text = ($field['setting']['validate']['required'] ? '<span class="required" aria-required="true"> * </span>' : '').dr_lang($field['name']);

        // 表单宽度设置
        $width = \Phpcmf\Service::IS_MOBILE_USER() ? '100%' : ($field['setting']['option']['width'] ? $field['setting']['option']['width'] : '100%');

        // 字段提示信息
        $tips = ($name == 'title' && APP_DIR) || $field['setting']['validate']['tips'] ? '<span class="help-block" id="dr_'.$field['fieldname'].'_tips">'.$field['setting']['validate']['tips'].'</span>' : '';

        // 字段默认值
        $value = dr_string2array($value);

        $this->_load_date = $this->_load_datetime = 0;

        $str = '<div class="table-scrollable" style="width:'.$width.(is_numeric($width) ? 'px !important' : '').';">';
        $str.= '<table class="table fc-sku-table table-nomargin table-bordered table-striped table-bordered table-advance" >';
        $str.= ' <thead><tr>';
        if ($field['setting']['option']['is_first_hang'] && !$field['setting']['option']['is_add']) {
            $str.= ' <th> '.dr_lang($field['setting']['option']['first_cname']).' </th>';
        }
        if ($field['setting']['option']['field']) {
            foreach ($field['setting']['option']['field'] as $t) {
                if ($t['type']) {
                    $style = $t['width'] ? 'style="width:'.$t['width'].(is_numeric($t['width']) ? 'px' : '').';"' : '';
                    $str.= ' <th '.$style.'>'.dr_lang($t['name']).'</th>';
                }
            }
        }
        if ($field['setting']['option']['is_add']) {
            $str.= ' <th width="70" style="text-align: center">';
            $str.= '<button type="button" class="btn blue btn-xs" onClick="dr_add_table_'.$name.'()"> <i class="fa fa-plus"></i> </button>';
            $str.'</th>';
        }
        $str.= ' </tr></thead>';
        $str.= ' <tbody id="dr_'.$name.'_body">';

        if ($field['setting']['option']['is_add']) {
            // 支持添加列
            $tpl = ' <tr id="dr_ftable_'.$name.'_row_{hang}">';
            if ($field['setting']['option']['field']) {
                foreach ($field['setting']['option']['field'] as $n => $t) {
                    if ($t['type']) {
                        $tpl.= ' <td>'.$this->_field_type_html($t, $name, $value, '{hang}', $n, $field).'</td>';
                    }
                }
            }
            $tpl.= ' <td style="text-align: center"><button type="button" class="btn red btn-xs" onClick="dr_del_table_'.$name.'(this)"> <i class="fa fa-trash"></i> </button></td>';
            $tpl.= ' </tr>';
            $ksids = [];
            if ($value) {
                foreach ($value as $hang => $t) {
                    $str.= ' <tr id="dr_ftable_'.$name.'_row_'.$hang.'">';
                    if ($field['setting']['option']['field']) {
                        foreach ($field['setting']['option']['field'] as $n => $t) {
                            if ($t['type']) {
                                $str.= ' <td>'.$this->_field_type_html($t, $name, $value, $hang, $n, $field).'</td>';
                            }
                        }
                    }
                    $str.= ' <td style="text-align: center"><button type="button" class="btn red btn-xs" onClick="dr_del_table_'.$name.'(this)"> <i class="fa fa-trash"></i> </button></td>';
                    $str.= ' </tr>';
                    $ksids[] = $hang;
                }
                $ksid = is_array($ksids) && $ksids ? max($ksids) : 0; // 开始ID
            }
        } else {
            // 固定列
            for ($i = 1; $i <= (int)$field['setting']['option']['count']; $i++) {

                $str.= ' <tr id="dr_ftable_'.$name.'_row_'.$i.'">';
                if ($field['setting']['option']['is_first_hang']) {
                    $str.= ' <td> '.($field['setting']['option']['hang'][$i]['name'] ? $field['setting']['option']['hang'][$i]['name'] : '未命名').' </td>';
                }
                if ($field['setting']['option']['field']) {
                    foreach ($field['setting']['option']['field'] as $n => $t) {
                        if ($t['type']) {
                            $str.= ' <td>'.$this->_field_type_html($t, $name, $value, $i, $n, $field).'</td>';
                        }
                    }
                }
                $str.= ' </tr>';
            }
        }

        $str.= ' </tbody>';
        $str.= '</table>';
        $str.= '</div>';

        if (!$this->is_load_js('Date')) {
            $str.= '
			<link href="'.ROOT_THEME_PATH.'assets/global/plugins/bootstrap-daterangepicker/daterangepicker.min.css?v='.CMF_UPDATE_TIME.'" rel="stylesheet" type="text/css" />
			<link href="'.ROOT_THEME_PATH.'assets/global/plugins/bootstrap-datepicker/css/bootstrap-datepicker.min.css?v='.CMF_UPDATE_TIME.'" rel="stylesheet" type="text/css" />
			<link href="'.ROOT_THEME_PATH.'assets/global/plugins/bootstrap-datetimepicker/css/bootstrap-datetimepicker.min.css?v='.CMF_UPDATE_TIME.'" rel="stylesheet" type="text/css" />
			
        	<script src="'.ROOT_THEME_PATH.'assets/global/plugins/moment.min.js?v='.CMF_UPDATE_TIME.'" type="text/javascript"></script>
			<script src="'.ROOT_THEME_PATH.'assets/global/plugins/bootstrap-datepicker/js/bootstrap-datepicker.finecms.js?v='.CMF_UPDATE_TIME.'" type="text/javascript"></script>
			<script src="'.ROOT_THEME_PATH.'assets/global/plugins/bootstrap-datetimepicker/js/bootstrap-datetimepicker.finecms.js?v='.CMF_UPDATE_TIME.'" type="text/javascript"></script>
			';
            $this->set_load_js('Date', 1);
        }
        if ($field['setting']['option']['is_add']) {
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
        }
        $str.= '<script> $("#dr_'.$name.'_body").sortable();';



        $str.="
            $(\"#dr_".$name."_body\").sortable();
		</script>
		";

        return $this->input_format($name, $text, $str.$tips);
    }

    /**
     * 显示输出表格
     */
    public function show_table($field, $value, $class = '') {

        // class属性
        !$class && $class = 'table fc-sku-table table-nomargin table-bordered table-striped table-bordered table-advance';

        // 字段默认值
        $value = dr_string2array($value);
        // 表单宽度设置
        $width = \Phpcmf\Service::IS_MOBILE_USER() ? '100%' : ($field['setting']['option']['width'] ? $field['setting']['option']['width'] : '100%');

        $str = '<table id="dr_table_'.$field['fieldname'].'" class="'.$class.'" style="width:'.$width.(is_numeric($width) ? 'px' : '').';">';
        $str.= ' <thead><tr>';

        if ($field['setting']['option']['is_first_hang'] && !$field['setting']['option']['is_add']) {
            $str .= ' <th> ' . dr_lang($field['setting']['option']['first_cname']) . ' </th>';
        }

        if ($field['setting']['option']['field']) {
            foreach ($field['setting']['option']['field'] as $t) {
                if ($t['type']) {
                    $style = $t['width'] ? 'style="width:'.$t['width'].(is_numeric($t['width']) ? 'px' : '').';"' : '';
                    $str.= ' <th '.$style.'>'.dr_lang($t['name']).'</th>';
                }
            }
        }

        $str.= ' </tr></thead>';
        $str.= ' <tbody>';

        $i = 1;
        foreach ($value as $ii => $val) {

            $str.= ' <tr>';
            if ($field['setting']['option']['is_first_hang'] && !$field['setting']['option']['is_add']) {
                $hname = $field['setting']['option']['hang'][$i]['name'] ? $field['setting']['option']['hang'][$i]['name'] : '未命名';
                $str .= ' <td> ' . $hname . ' </td>';
            }

            if ($field['setting']['option']['field']) {
                foreach ($field['setting']['option']['field'] as $n => $t) {
                    if ($t['type']) {
                        $td = $val[$n];
                        if ($td && $t['type'] == 3) {
                            // 图片
                            $td = '<img src="'.dr_get_file($td).'" class="ftable_img">';
                        } elseif ($td && $t['type'] == 4) {
                            // 复选
                            $td = implode('、', $td);
                        }
                        $str.= ' <td>'.$td.'</td>';
                    }
                }
            }
            $i++;
            $str.= ' </tr>';
        }

        $str.= ' </tbody>';
        $str.= '</table>';

        return $str;
    }

}