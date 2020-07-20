<?php namespace Phpcmf\Field;


/**
 * http://www.xunruicms.com
 * 本文件是框架系统文件，二次开发时不可以修改本文件，可以通过继承类方法来重写此文件
 **/



class File extends \Phpcmf\Library\A_Field {
	
	/**
     * 构造函数
     */
    public function __construct(...$params) {
        parent::__construct(...$params);
		$this->fieldtype = array('VARCHAR' => '255'); // TRUE表全部可用字段类型,自定义格式为 array('可用字段类型名称' => '默认长度', ... )
		$this->defaulttype = 'VARCHAR'; // 当用户没有选择字段类型时的缺省值
    }
	
	/**
	 * 字段相关属性参数
	 *
	 * @param	array	$value	值
	 * @return  string
	 */
	public function option($option) {
		

		return [
			'
            <div class="form-group">
                <label class="col-md-2 control-label">'.dr_lang('首图作为缩略图').'</label>
                <div class="col-md-9">
                <input type="checkbox" name="data[setting][option][stslt]" '.($option['stslt'] ? 'checked' : '').' value="1" data-on-text="'.dr_lang('开启').'" data-off-text="'.dr_lang('关闭').'" data-on-color="success" data-off-color="danger" class="make-switch" data-size="small">
                <span class="help-block">'.dr_lang('当缩略图字段为空时，用本字段的图片来填充（仅对模块字段有效）').'</span>
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
                <input type="checkbox" name="data[setting][option][chunk]" '.($option['chunk'] ? 'checked' : '').' value="1" data-on-text="'.dr_lang('开启').'" data-off-text="'.dr_lang('关闭').'" data-on-color="success" data-off-color="danger" class="make-switch" data-size="small">
                <span class="help-block">'.dr_lang('当文件太大时可以采取分段上传，可以提示上传效率').'</span>
                </div>
            </div>
			'.$this->attachment($option).'
			'
			,
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
            </div>'
		];
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
		if ($data === $_data) {
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

		// 存在缩略图值时
		if (!$value && $field['fieldname'] == 'thumb' && isset(\Phpcmf\Service::L('Field')->data[1]['thumb']) && \Phpcmf\Service::L('Field')->data[1]['thumb']) {
			return;
		}

        if ($value && $field['setting']['option']['stslt'] && !\Phpcmf\Service::L('Field')->data[1]['thumb']) {
            $info = \Phpcmf\Service::C()->get_attachment($value);
            if ($info && in_array($info['fileext'], ['jpg', 'jpeg', 'png', 'gif'])) {
                \Phpcmf\Service::L('Field')->data[1]['thumb'] = $value;
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
		$text = ($field['setting']['validate']['required'] ? '<span class="required" aria-required="true"> * </span>' : '').$field['name'];

		// 字段提示信息
		$tips = ($name == 'title' && APP_DIR) || $field['setting']['validate']['tips'] ? '<span class="help-block" id="dr_'.$field['fieldname'].'_tips">'.$field['setting']['validate']['tips'].'</span>' : '';

        // 表单附加参数
        $attr = $field['setting']['validate']['formattr'];

		$ts = dr_lang('上传格式要求：%s（%s）', str_replace(',', '、', $field['setting']['option']['ext']), intval($field['setting']['option']['size']).'MB');

        $p = dr_authcode([
            'size' => intval($field['setting']['option']['size']),
            'exts' => $field['setting']['option']['ext'],
            'attachment' => $field['setting']['option']['attachment'],
            'image_reduce' => $field['setting']['option']['image_reduce'],
        ], 'ENCODE');

		// 显示模板
		$tpl = '<div  id="dr_'.$name.'_files_row" class="file_row_html files_row">';
		$tpl.= '<div>';
		$tpl.= '<div class="files_row_preview preview">{preview}</div>';
		$tpl.= '</div>';
		$tpl.= '<input type="hidden" '.$attr.' id="dr_'.$name.'" class="files_row_id" name="data['.$name.']" value="{id}" />';
		$tpl.= '</div>';

		// 已保存数据
		$val = '';
		$file_url = '';
		$show_delete = 0;
		if ($value) {
			$file = \Phpcmf\Service::C()->get_attachment($value);
			if ($file) {
				$preview = dr_file_preview_html($file['url']);
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
            'size' => intval($field['setting']['option']['size']) * 1024 * 1024,
            'url' =>  '/index.php?s=api&c=file&token='.dr_get_csrf_token().'&siteid='.SITE_ID.'&m=upload&p='.$p.'&fid='.$field['id'],
            'unused_url' => '/index.php?s=api&c=file&m=input_file_list&p=' . $p . '&fid=' . $field['id'],
            'input_url' => '/index.php?s=api&c=file&m=input_file_url&token='.dr_get_csrf_token().'&siteid='.SITE_ID.'&p='.$p.'&fid='.$field['id'].'&file='.$file_url.'&one=1',
            'tpl' => $tpl,
            'area' => \Phpcmf\Service::C()->_is_mobile() ? ["95%", "90%"] : ["70%", "70%"],
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
					<span class="btn blue btn-sm fileinput-button">
						<i class="fa fa-plus"></i>
						<span> '.dr_lang('上传').' </span>
						<input type="file" name="file_data"> 
					</span>
					
					'.$use.'
					
					<span class="fileupload-process"> </span>
				</div>
				<div class="col-lg-12 fileupload-progress fade" style="display:none">
					<div class="progress progress-striped active" role="progressbar" aria-valuemin="0" aria-valuemax="100">
						<div class="progress-bar progress-bar-success" style="width:0%;"> </div>
					</div>
				</div>
			</div>';
		if (!$field['setting']['option']['tips']) {
            $str.= '<p class="finecms-file-ts">'.$ts.'</p>';
        }
        $str.= '<div id="fileupload_'.$name.'_files" class="files">'.$val.'</div>';

        if (!$this->is_load_js($field['filetype'])) {
			$str.= '
			<link href="'.ROOT_THEME_PATH.'assets/global/plugins/jquery-fileupload/css/jquery.fileupload.css?v='.CMF_UPDATE_TIME.'" rel="stylesheet" type="text/css" />
			<script src="'.ROOT_THEME_PATH.'assets/global/plugins/jquery-fileupload/js/jquery.fileupload.js?v='.CMF_UPDATE_TIME.'" type="text/javascript"></script>
			';
            $this->set_load_js($field['filetype'], 1);
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
            $file = \Phpcmf\Service::C()->get_attachment($value);
            if ($file) {
                $preview = dr_file_preview_html($file['url']);
            } else {
                $preview = dr_file_preview_html($value);
            }
            $html = '<div class="files_row_preview" style="width: 70px; height: 70px;">'.$preview.'</div>';
        }

        return $this->input_format($field['fieldname'], $field['name'], $html);
    }
}