<?php namespace Phpcmf\Field;

/**
 * http://www.xunruicms.com
 * 本文件是框架系统文件，二次开发时不可以修改本文件，可以通过继承类方法来重写此文件
 **/



class Files extends \Phpcmf\Library\A_Field {

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

        return ['
            <div class="form-group">
                <label class="col-md-2 control-label">'.dr_lang('首图作为缩略图').'</label>
                <div class="col-md-9">
                <input type="checkbox" name="data[setting][option][stslt]" '.($option['stslt'] ? 'checked' : '').' value="1" data-on-text="'.dr_lang('开启').'" data-off-text="'.dr_lang('关闭').'" data-on-color="success" data-off-color="danger" class="make-switch" data-size="small">
                <span class="help-block">'.dr_lang('当缩略图字段为空时，用本字段的首张图片来填充（仅对模块字段有效）').'</span>
                </div>
            </div>
       
            <div class="form-group">
                <label class="col-md-2 control-label">'.dr_lang('手动输入').'</label>
                <div class="col-md-9">
                    <input type="checkbox" name="data[setting][option][input]" '.($option['input'] ? 'checked' : '').' value="1"  data-on-text="'.dr_lang('开启').'" data-off-text="'.dr_lang('关闭').'" data-on-color="success" data-off-color="danger" class="make-switch" data-size="small">
                    <span class="help-block">'.dr_lang('开启将会出现手动输入文件地址按钮').'</span>
                </div>
            </div>
            <div class="form-group">
                <label class="col-md-2 control-label">'.dr_lang('描述文本').'</label>
                <div class="col-md-9">
                    <input type="checkbox" name="data[setting][option][desc]" '.($option['desc'] ? 'checked' : '').' value="1"  data-on-text="'.dr_lang('开启').'" data-off-text="'.dr_lang('关闭').'" data-on-color="success" data-off-color="danger" class="make-switch" data-size="small">
                    <span class="help-block">'.dr_lang('开启将会出现多行文本输入框').'</span>
                </div>
            </div>
			<div class="form-group">
                    <label class="col-md-2 control-label">'.dr_lang('文件大小').'</label>
                    <div class="col-md-9">
						<label><input id="field_default_value" type="text" class="form-control" value="'.$option['size'].'" name="data[setting][option][size]"></label>
						<span class="help-block">'.dr_lang('单位MB').'</span>
                    </div>
                </div>
                
			<div class="form-group">
                <label class="col-md-2 control-label">'.dr_lang('分段上传').'</label>
                <div class="col-md-9">
                <input type="checkbox" name="data[setting][option][chunk]" '.($option['chunk'] ? 'checked' : '').' value="1" data-on-text="'.dr_lang('开启').'" data-off-text="'.dr_lang('关闭').'" data-on-color="success" data-off-color="danger" class="make-switch" data-size="small">
                <span class="help-block">'.dr_lang('当文件太大时可以采取分段上传，可以提示上传效率').'</span>
                </div>
            </div>
            <div class="form-group">
				<label class="col-md-2 control-label">'.dr_lang('上传数量').'</label>
				<div class="col-md-9">
					<label><input type="text" class="form-control" value="'.$option['count'].'" name="data[setting][option][count]"></label>
					<span class="help-block">'.dr_lang('每次最多上传的文件数量').'</span>
				</div>
			</div>
			<div class="form-group">
				<label class="col-md-2 control-label">'.dr_lang('扩展名').'</label>
				<div class="col-md-9">
					<label><input type="text" class="form-control" size="40" name="data[setting][option][ext]" value="'.$option['ext'].'"></label>
					<span class="help-block">'.dr_lang('格式：jpg,gif,png,exe,html,php,rar,zip').'</span>
				</div>
			</div>
			
			'.$this->attachment($option).'',

            '<div class="form-group">
                <label class="col-md-2 control-label">'.dr_lang('显示浏览附件').'</label>
                <div class="col-md-9">
                <input type="checkbox" name="data[setting][option][unused]" '.($option['unused'] ? 'checked' : '').' value="1" data-off-text="'.dr_lang('开启').'" data-on-text="'.dr_lang('关闭').'" data-off-color="success" data-on-color="danger" class="make-switch" data-size="small">
                <span class="help-block">'.dr_lang('允许用户选取自己已经上传的附件').'</span>
                </div>
            </div>
            <div class="form-group">
                <label class="col-md-2 control-label">'.dr_lang('提示扩展名显示').'</label>
                <div class="col-md-9">
                <input type="checkbox" name="data[setting][option][tips]" '.($option['tips'] ? 'checked' : '').' value="1" data-off-text="'.dr_lang('开启').'" data-on-text="'.dr_lang('关闭').'" data-off-color="success" data-on-color="danger" class="make-switch" data-size="small">
                <span class="help-block">'.dr_lang('提示字段上传的扩展名和大小限制的文本信息').'</span>
                </div>
            </div>'];
    }

    /**
     * 字段输出
     */
    public function output($value) {

        $data = [];
        $value = dr_string2array($value);
        if (!$value) {
            return $data;
        } elseif (!isset($value['file'])) {
            return $value;
        }

        foreach ($value['file'] as $i => $file) {
            $data[] = [
                'file' => $file, // 对应文件或附件id
                'title' => $value['title'][$i], // 对应标题
                'description' => $value['description'][$i], // 对应描述
            ];
        }

        return $data;
    }

    /**
     * 获取附件id
     */
    public function get_attach_id($value) {

        $data = [];
        $value = dr_string2array($value);

        if (!$value) {
            return $data;
        } elseif (!isset($value['file'])) {
            return $value;
        }

        foreach ($value['file'] as $i => $file) {
            is_numeric($file) && $data[] = $file;
        }

        return $data;
    }

    /**
     * 字段入库值
     */
    public function insert_value($field) {

        $data = [];
        $value = \Phpcmf\Service::L('Field')->post[$field['fieldname']];
        if ($value) {
            foreach ($value['title'] as $id => $title) {
                $data['file'][$id] = $value['id'][$id] ? $value['id'][$id] : $value['file'][$id];
                $data['title'][$id] = $title;
                $data['description'][$id] = $value['description'][$id] ? $value['description'][$id] : '';
            }
        }

        if ($data['file'] && is_array($data['file'])
            && $field['setting']['option']['stslt'] && !\Phpcmf\Service::L('Field')->data[1]['thumb']) {
            $one = array_key_first($data['file']);
            if ($data['file'][$one]) {
                $info = \Phpcmf\Service::C()->get_attachment($data['file'][$one]);
                if ($info && in_array($info['fileext'], ['jpg', 'jpeg', 'png', 'gif'])) {
                    \Phpcmf\Service::L('Field')->data[1]['thumb'] = $data['file'][$one];
                }
            }
        }

        \Phpcmf\Service::L('Field')->data[$field['ismain']][$field['fieldname']] = dr_array2string($data);
    }

    /**
     * 附件处理
     */
    public function attach($data, $_data) {

        $data = dr_string2array($data);
        $_data = dr_string2array($_data);

        !isset($_data['file']) && $_data =['file' => NULL];
        !isset($data['file']) && $data = ['file' => NULL];

        // 新旧数据都无附件就跳出
        if (!$data['file'] && !$_data['file']) {
            return NULL;
        }

        // 新旧数据都一样时表示没做改变就跳出
        if ($data['file'] === $_data['file']) {
            return NULL;
        }

        // 当无新数据且有旧数据表示删除旧附件
        if (!$data['file'] && $_data['file']) {
            return [ [], $_data['file'] ];
        }

        // 当无旧数据且有新数据表示增加新附件
        if ($data['file'] && !$_data['file']) {
            return [$data['file'], []];
        }

        // 剩下的情况就是删除旧文件增加新文件

        // 新旧附件的交集，表示固定的
        $intersect = @array_intersect($data['file'], $_data['file']);

        return [
            @array_diff($data['file'], $intersect), // 固有的与新文件中的差集表示新增的附件
            @array_diff($_data['file'], $intersect), // 固有的与旧文件中的差集表示待删除的附件
        ];
    }


    /**
     * 字段表单输入
     *
     * @return  string
     */
    public function input($field, $value = '')
    {

        // 字段禁止修改时就返回显示字符串
        if ($this->_not_edit($field, $value)) {
            return $this->show($field, $value);
        }

        // 字段存储名称
        $name = $field['fieldname'];

        // 字段显示名称
        $text = ($field['setting']['validate']['required'] ? '<span class="required" aria-required="true"> * </span>' : '') . $field['name'];

        // 字段提示信息
        $tips = ($name == 'title' && APP_DIR) || $field['setting']['validate']['tips'] ? '<span class="help-block" id="dr_' . $field['fieldname'] . '_tips">' . $field['setting']['validate']['tips'] . '</span>' : '';

        $count = intval($field['setting']['option']['count']);
        $ts = dr_lang('上传格式要求：%s（%s），最多上传%s个文件', str_replace(',', '、', $field['setting']['option']['ext']), intval($field['setting']['option']['size']) . 'MB', $count);


        $p = dr_authcode([
            'size' => intval($field['setting']['option']['size']),
            'exts' => $field['setting']['option']['ext'],
            'attachment' => $field['setting']['option']['attachment'],
            'image_reduce' => $field['setting']['option']['image_reduce'],
        ], 'ENCODE');

        // 显示模板
        $tpl = '<tr class="template-download files_row">';
        $tpl .= '<td style="text-align:center;width: 80px;">';
        $tpl .= '<div class="files_row_preview preview">{preview}</div>';
        $tpl .= '</td>';
        $tpl .= '<td class="files_show_info">';
        $tpl .= '<div class="row">';
        $tpl .= '<div class="col-md-12 files_show_title_html">';
        $tpl .= '<input class="form-control files_row_title" type="text" name="data[' . $name . '][title][]" value="{title}">';
        $tpl .= '<input type="hidden" class="files_row_id" name="data[' . $name . '][id][]" value="{id}">';
        $tpl .= '<input class="files_row_name" {disabled} type="hidden" name="data[' . $name . '][file][]" value="{filepath}">';
        $tpl .= '</div>';
        if ($field['setting']['option']['desc']) {
            $tpl.= '<div class="col-md-12 files_show_description_html">';
            $tpl.= '<textarea class="form-control files_row_description" name="data['.$name.'][description][]">{description}</textarea>';
            $tpl.= '</div>';
        }
        $tpl.= '</div>';
        $tpl.= '</td>';

        $tpl.= '<td style="text-align:center;width: 80px;">';
        $tpl.= '<label><button onclick="dr_file_remove(this)" type="button" class="btn red file_delete btn-sm"><i class="fa fa-trash"></i></button></label>';
        $tpl.= '<label><button onclick="fileupload_file_edit(\''.$name.'\',this)" type="button" class="fileinput-button btn green file_edit btn-sm"><i class="fa fa-edit"></i>{upload}</button></label>';
        $tpl.= '</td>';
        $tpl.= '</tr>';

        // 已保存数据
        $val = '';
        $value = dr_get_files($value);
        if ($value) {
            foreach ($value as $i => $t) {
                $id = $t['id'] ? $t['id'] : $t['file'];
                $file = \Phpcmf\Service::C()->get_attachment($id);
                $description = $t['description'] ? htmlspecialchars($t['description']) : '';
                if ($file) {
                    $disabled = 'readonly';
                    $preview = dr_file_preview_html($file['url']);
                    $filepath = dr_strcut($file['attachment'], 30);
                    $upload = '<input type="file" name="file_data">';
                } else {
                    $disabled = '';
                    $filepath = htmlspecialchars($id);
                    $preview = dr_file_preview_html($id);
                    $upload = '';
                    $id = '';
                }
                $val.= str_replace(
                    ['{title}', '{description}', '{id}', '{filepath}', '{disabled}', '{preview}', '{upload}'],
                    [htmlspecialchars($t['title']), $description, $id, $filepath, $disabled, $preview, $upload],
                    $tpl
                );
            }
        }

        $json = json_encode([
            'name' => $name,
            'ext' => !$field['setting']['option']['ext'] || $field['setting']['option']['ext'] == '*' ? 'null' : ' /(\.|\/)('.str_replace(',', '|', $field['setting']['option']['ext']).')$/i',
            'size' => intval($field['setting']['option']['size']) * 1024 * 1024,
            'url' =>  '/index.php?s=api&c=file&token='.dr_get_csrf_token().'&siteid=' . SITE_ID . '&m=upload&p=' . $p . '&fid=' . $field['id'],
            'unused_url' => '/index.php?s=api&c=file&m=input_file_list&token='.dr_get_csrf_token().'&siteid='.SITE_ID.'&p=' . $p . '&fid=' . $field['id'],
            'input_url' => '/index.php?s=api&c=file&m=input_file_url&token='.dr_get_csrf_token().'&siteid='.SITE_ID.'&p='.$p.'&fid='.$field['id'],
            'tpl' => $tpl,
            'area' => \Phpcmf\Service::C()->_is_mobile() ? ["95%", "90%"] : ["70%", "70%"],
            'count' => $count,
            'error' => dr_lang('上传文件不能超过%s个', $count),
            'chunk' => $field['setting']['option']['chunk'] ? 20 * 1024 * 1024 : 0,
        ]);

        $use = '';
        if (!$field['setting']['option']['unused'] && \Phpcmf\Service::C()->uid) {
            $use.= '<button type="button" class="btn red btn-sm fileinput-unused">
						<i class="fa fa-folder-open"></i>
						<span> '.dr_lang('浏览').' </span>
					</button>';
        }
        if ($field['setting']['option']['input']) {
            $use.= '<button style="margin-left: 5px;" type="button" class="btn green btn-sm fileinput-url">
						<i class="fa fa-edit"></i>
						<span> '.dr_lang('地址').' </span>
					</button>';
        }

        // 表单输出
        $str = '
			<div class="row fileupload-buttonbar" id="fileupload_'.$name.'">
				<div class="col-lg-12">
					<span class="btn blue btn-sm fileinput-button">
						<i class="fa fa-plus"></i>
						<span> '.dr_lang('上传').' </span>
						<input type="file" name="file_data" multiple=""> 
					</span>
					
					'.$use.'
					
					<span class="fileupload-process"> </span>
				</div>
				<div class="col-lg-12 fileupload-progress fade" style="display:none">
					<div class="progress progress-striped active" role="progressbar" aria-valuemin="0" aria-valuemax="100">
						<div class="progress-bar progress-bar-success" style="width:0%;"> </div>
					</div>
				</div>
			</div>
			';
        if (!$field['setting']['option']['unused']) {
            $str.= '<p class="finecms-file-ts">'.$ts.'</p>';
        }
        $str.= '
			<div class="scroller_'.$name.'_files">
                <div class="scroller" data-inited="0" data-initialized="1" data-always-visible="1" data-rail-visible="1">
                    <table role="presentation" class="table table-striped table-fc-upload clearfix">
                        <tbody id="fileupload_'.$name.'_files" class="files scroller_body">'.$val.'</tbody>
                    </table>
                </div>
			</div>
		';

        if (!$this->is_load_js($field['filetype'])) {
            $str.= '
			<link href="'.ROOT_THEME_PATH.'assets/global/plugins/jquery-fileupload/css/jquery.fileupload.css?v='.CMF_UPDATE_TIME.'" rel="stylesheet" type="text/css" />
			<script src="'.ROOT_THEME_PATH.'assets/global/plugins/jquery-fileupload/js/jquery.fileupload.js?v='.CMF_UPDATE_TIME.'" type="text/javascript"></script>
			';
            $this->set_load_js($field['filetype'], 1);
        }



        $str.= '<script type="text/javascript">
            var files_json_'.$name.' = '.$json.';
        $(function() {
            fileupload_files_init(files_json_'.$name.');
        });
        </script>';


        // 输出最终表单显示
        return $this->input_format($name, $text, $str.$tips);
    }

    /**
     * 字段表单显示
     *
     * @param	string	$field	字段数组
     * @param	array	$value	值
     * @return  string
     */
    public function show($field, $value = null) {

        $html = '';
        $value = dr_get_files($value);
        if ($value) {

            // 显示模板
            $tpl = '<tr class="template-download files_row">';
            $tpl.= '<td style="text-align:center;width:90px;border-top:0">';
            $tpl.= '<span class="files_row_preview preview">{preview}</span>';
            $tpl.= '</td>';
            $tpl.= '<td style="border-top:0">';
            $tpl.= '{title} {description}';
            $tpl.= '</td>';
            $tpl.= '</tr>';
            $html.= '<table role="presentation" class="table table-striped table-fc-upload clearfix">';
            $html.= '<tbody class="files">';
            foreach ($value as $i => $t) {
                $id = $t['id'] ? $t['id'] : $t['file'];
                $file = \Phpcmf\Service::C()->get_attachment($id);
                $description = $t['description'] ? $t['description'] : '';
                if ($file) {
                    $preview = dr_file_preview_html($file['url']);
                    $filepath = $file['attachment'];
                    $upload = '';
                } else {
                    $filepath = $id;
                    $preview = dr_file_preview_html($id);
                    $upload = '';
                }
                $html.= str_replace(
                    ['{title}', '{description}', '{id}', '{filepath}', '{preview}', '{upload}'],
                    [$t['title'], $description, $value, $filepath, $preview, $upload],
                    $tpl
                );
            }
            $html.= '</tbody>';
            $html.= '</table>';
        }

        return $this->input_format($field['fieldname'], $field['name'], $html);
    }

}