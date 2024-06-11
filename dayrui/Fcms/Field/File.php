<?php namespace Phpcmf\Field;
/**
 * {{www.xunruicms.com}}
 * {{迅睿内容管理框架系统}}
 * 本文件是框架系统文件，二次开发时不可以修改本文件，可以通过继承类方法来重写此文件
 **/

class File extends \Phpcmf\Library\A_Field {
	
	/**
     * 构造函数
     */
    public function __construct(...$params) {
        parent::__construct(...$params);
        $this->fieldtype = TRUE;
		$this->defaulttype = 'VARCHAR'; // 当用户没有选择字段类型时的缺省值
    }
	
	/**
	 * 字段相关属性参数
	 *
	 * @param	array	$value	值
	 * @return  string
	 */
	public function option($option) {

        $mthumb = '';
	    if (\Phpcmf\Service::M('field')->relatedname == 'module') {
	        $mthumb = '<div class="form-group">
                <label class="col-md-2 control-label">'.dr_lang('首图作为缩略图').'</label>
                <div class="col-md-9">
                <input type="checkbox" name="data[setting][option][stslt]" '.($option['stslt'] ? 'checked' : '').' value="1" data-on-text="'.dr_lang('已开启').'" data-off-text="'.dr_lang('已关闭').'" data-on-color="success" data-off-color="danger" class="make-switch" data-size="small">
                <span class="help-block">'.dr_lang('当缩略图字段为空时，用本字段的图片来填充（仅对模块字段有效）').'</span>
                </div>
            </div>';
        }
        if ($this->field && $this->field['fieldname'] != 'thumb') {
            $mthumb = '';
        }

		return [$this->field_type($option['fieldtype'], $option['fieldlength']).$this->_search_field().
			$mthumb.'
            <div class="form-group">
                <label class="col-md-2 control-label">'.dr_lang('手动输入').'</label>
                <div class="col-md-9">
                    <input type="checkbox" name="data[setting][option][input]" '.($option['input'] ? 'checked' : '').' value="1"  data-on-text="'.dr_lang('已开启').'" data-off-text="'.dr_lang('已关闭').'" data-on-color="success" data-off-color="danger" class="make-switch" data-size="small">
                    <span class="help-block">'.dr_lang('开启将会出现手动输入文件地址按钮').'</span>
                </div>
            </div>
			<div class="form-group">
				<label class="col-md-2 control-label">'.dr_lang('扩展名').'</label>
				<div class="col-md-9">
					<label><input type="text" class="form-control" size="40" name="data[setting][option][ext]" value="'.$option['ext'].'"></label>
					<span class="help-block">'.dr_lang('格式：jpg,gif,png,exe,html,php,rar,zip').'</span>
				</div>
			</div>
			<div class="form-group">
				<label class="col-md-2 control-label">'.dr_lang('文件大小').'</label>
				<div class="col-md-9">
					<label><input type="text" class="form-control" value="'.$option['size'].'" name="data[setting][option][size]"></label>
					<span class="help-block">'.dr_lang('单位MB').'</span>
				</div>
			</div>
			<div class="form-group">
                <label class="col-md-2 control-label">'.dr_lang('分段上传').'</label>
                <div class="col-md-9">
                <input type="checkbox" name="data[setting][option][chunk]" '.($option['chunk'] ? 'checked' : '').' value="1" data-on-text="'.dr_lang('已开启').'" data-off-text="'.dr_lang('已关闭').'" data-on-color="success" data-off-color="danger" class="make-switch" data-size="small">
                <span class="help-block">'.dr_lang('当文件太大时可以采取分段上传，可以提升上传效率').'</span>
                </div>
            </div>
            <div class="form-group">
                <label class="col-md-2 control-label">'.dr_lang('显示浏览附件').'</label>
                <div class="col-md-9">
                <input type="checkbox" name="data[setting][option][unused]" '.($option['unused'] ? 'checked' : '').' value="1" data-off-text="'.dr_lang('已开启').'" data-on-text="'.dr_lang('已关闭').'" data-off-color="success" data-on-color="danger" class="make-switch" data-size="small">
                <span class="help-block">'.dr_lang('允许用户选取自己已经上传的附件').'</span>
                </div>
            </div>
			'.$this->attachment($option).'
			'
			,
			'
            <div class="form-group">
                <label class="col-md-2 control-label">'.dr_lang('提示扩展名显示').'</label>
                <div class="col-md-9">
                   <div class="mt-radio-inline">
                    <label class="mt-radio mt-radio-outline"><input type="radio"  name="data[setting][option][is_ext_tips]" value="0" '.(!$option['is_ext_tips'] ? 'checked' : '').' /> '.dr_lang('已显示').' <span></span></label>
                    <label class="mt-radio mt-radio-outline"><input type="radio"  name="data[setting][option][is_ext_tips]" value="1" '.($option['is_ext_tips'] ? 'checked' : '').' /> '.dr_lang('已关闭').' <span></span></label>
                </div><span class="help-block">'.dr_lang('提示字段上传的扩展名和大小限制的文本信息').'</span>
                </div>
            </div>'
		];
	}

    /**
     * 验证字段属性
     */
    public function edit_config($post) {

        if (!isset($post['setting']['option']['ext']) || !$post['setting']['option']['ext']) {
            return dr_return_data(0, dr_lang('扩展名必须填写'));
        } elseif (!isset($post['setting']['option']['size']) || !$post['setting']['option']['size']) {
            return dr_return_data(0, dr_lang('文件大小必须填写'));
        }

        return dr_return_data(1, 'ok');
    }
	
	/**
	 * 字段输出
	 */
	public function output($value) {
        return $value;
	}
	
	/**
	 * 获取附件id
	 */
	public function get_attach_id($value) {
	
		$data = [];
		if (!$value || !is_numeric($value)) {
            return $data;
        }
		
		$data[] = $value;
		
		return $data;
	}
	
	/**
	 * 附件处理
	 */
	public function attach($data, $_data) {

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
                [$_data]
            ];
		}
		
		// 当无旧数据且有新数据表示增加新附件
		if ($data && !$_data) {
            return [
                [$data],
                []
            ];
		}
		
		// 剩下的情况就是删除旧文件增加新文件
        return [
            [$data],
            [$_data]
        ];
	}


	/**
	 * 字段入库值
	 *
	 * @param	array	$field	字段信息
	 * @return  void
	 */
	public function insert_value($field) {

		$value = \Phpcmf\Service::L('Field')->post[$field['fieldname']];

		// 存在缩略图值时，自己就是缩略图
		if (!$value && $field['fieldname'] == 'thumb' && isset(\Phpcmf\Service::L('Field')->data[$field['ismain']]['thumb']) && \Phpcmf\Service::L('Field')->data[$field['ismain']]['thumb']) {
			return;
		}

		// 提取缩略图
        if ($value && $field['setting']['option']['stslt']) {
            $_field = \Phpcmf\Service::L('form')->fields;
            if (isset($_field['thumb']) && $_field['thumb']['fieldtype'] == 'File' && !\Phpcmf\Service::L('Field')->data[$_field['thumb']['ismain']]['thumb']) {
                $info = \Phpcmf\Service::C()->get_attachment($value, 1);
                if ($info && in_array($info['fileext'], ['jpg', 'jpeg', 'png', 'gif'])) {
                    \Phpcmf\Service::L('Field')->data[$_field['thumb']['ismain']]['thumb'] = $value;
                }
            }
        }

		\Phpcmf\Service::L('Field')->data[$field['ismain']][$field['fieldname']] = $value;
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

        // 表单附加参数
        $attr = $field['setting']['validate']['formattr'];

		$ts = dr_lang('上传格式要求：%s（%s）', str_replace(',', '、', (string)$field['setting']['option']['ext']), ($field['setting']['option']['size']).'MB');

        $p = dr_authcode([
            'size' => ($field['setting']['option']['size']),
            'exts' => $field['setting']['option']['ext'],
            'attachment' => $field['setting']['option']['attachment'],
            'image_reduce' => $field['setting']['option']['image_reduce'],
        ], 'ENCODE');

		// 显示模板
		$tpl = '<div id="dr_'.$name.'_files_row" class="file_row_html files_row">';
		$tpl.= '<div class="files_row_preview preview">{preview}</div>';
		$tpl.= '<input type="hidden" '.$attr.' id="dr_'.$name.'" class="files_row_id" name="data['.$name.']" value="{id}" />';
		$tpl.= '</div>';

		// 已保存数据
		$val = '';
		$file_url = '';
		$show_delete = 0;
		if ($value) {
			$file = \Phpcmf\Service::C()->get_attachment($value, 1);
			if ($file) {
                $preview = dr_file_preview_html($file['url'], $file['id']);
				$filepath = $file['attachment'];
				$title = $file['filename'];
				$upload = '';
			} else {
				$file_url = $filepath = $value;
				$preview = dr_file_preview_html($value);
				$upload = '';
				$title = '';
			}
			$val.= str_replace(
				['{title}', '{id}', '{filepath}', '{preview}', '{upload}'],
				[$title, $value, dr_strcut($filepath, 30), $preview, $upload],
				$tpl
			);
            $show_delete = 1;
		} else {
			$val.= '<input type="hidden" '.$attr.' id="dr_'.$name.'" name="data['.$name.']" value="" />';
		}

        $json = json_encode([
            'name' => $name,
            'ext' => !$field['setting']['option']['ext'] || $field['setting']['option']['ext'] == '*' ? 'null' : ' /(\.|\/)('.str_replace(',', '|', $field['setting']['option']['ext']).')$/i',
            'size' => floatval($field['setting']['option']['size']) * 1024 * 1024,
            'url' =>  dr_web_prefix(''.(IS_ADMIN ? SELF.'?c=api' : 'index.php?s=api&c=file').'&is_iframe=1&token='.dr_get_csrf_token()).'&siteid='.SITE_ID.'&m=upload&p='.$p.'&fid='.$field['id'],
            'unused_url' => dr_web_prefix(''.(IS_ADMIN ? SELF.'?c=api' : 'index.php?s=api&c=file').'&m=input_file_list&is_iframe=1&p=' . $p . '&fid=' . $field['id']),
            'input_url' => dr_web_prefix(''.(IS_ADMIN ? SELF.'?c=api' : 'index.php?s=api&c=file').'&m=input_file_url&is_iframe=1&token='.dr_get_csrf_token().'&siteid='.SITE_ID.'&p='.$p.'&fid='.$field['id'].'&file='.urlencode($file_url).'&one=1'),
            'tpl' => $tpl,
            'area' => \Phpcmf\Service::IS_MOBILE_USER() ? ["95%", "90%"] : ["80%", "80%"],
            'url_area' => \Phpcmf\Service::IS_MOBILE_USER() ? ["95%", "90%"] : ["50%", "300px"],
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
        $use.= '<button onclick="fileupload_file_remove(\''.$name.'\')" style="margin-left: 5px;'.(!$show_delete ? 'display:none' : '').'" type="button" class="btn red btn-sm fileinput-delete">
						<i class="fa fa-trash"></i>
						<span> '.dr_lang('删除').' </span>
					</button>';

		// 表单输出
		$str = '
			<div class="row fileupload-buttonbar" id="fileupload_'.$name.'">
				<div class="col-lg-12">
					<button class="btn blue btn-sm fileinput-button">
						<i class="fa fa-plus"></i>
						<span> '.dr_lang('上传').' </span>
						<input type="file" name="file_data"> 
					</button>
					
					'.$use.'
					
					<span class="fileupload-process"> </span>
				</div>
				<div class="col-lg-12 fileupload-progress fade" style="display:none">
					<div class="progress progress-striped active" role="progressbar" aria-valuemin="0" aria-valuemax="100">
						<div class="progress-bar progress-bar-success" style="width:0%;"> </div>
					</div>
				</div>
			</div>';
        if (!$field['setting']['option']['is_ext_tips']) {
            $str.= '<p class="finecms-file-ts">'.$ts.'</p>';
        }
        $str.= '<div id="fileupload_'.$name.'_files" class="files">'.$val.'</div>';

        if (!$this->is_load_js('File')) {
            $str.= '
			<link href="'.ROOT_THEME_PATH.'assets/global/plugins/jquery-fileupload/css/jquery.fileupload.css?v='.CMF_UPDATE_TIME.'" rel="stylesheet" type="text/css" />
			<script src="'.ROOT_THEME_PATH.'assets/global/plugins/jquery-fileupload/js/jquery.fileupload.min.js?v='.CMF_UPDATE_TIME.'" type="text/javascript"></script>
			';
            $this->set_load_js('File', 1);
        }

		$str.= '<script type="text/javascript">
        $(function() {
            fileupload_file_init('.$json.');
        });
        </script>';

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

        if ($value) {
            // 显示模板
            $file = \Phpcmf\Service::C()->get_attachment($value, 1);
            if ($file) {
                $preview = dr_file_preview_html($file['url'], $file['id']);
            } else {
                $preview = dr_file_preview_html($value);
            }
            $html = '<div class="files_row_preview preview">'.$preview.'</div>';
        }

        return $this->input_format($field['fieldname'], $field['name'], $html);
    }
}