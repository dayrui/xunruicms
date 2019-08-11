<?php namespace Phpcmf\Field;

/* *
 *
 * Copyright [2019] [李睿]
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * 本文件是框架系统文件，二次开发时不建议修改本文件
 *
 * */


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
			</div>'.$this->attachment(isset($option['attachment']) ? $option['attachment'] : 0).'',

			''];
	}
	
	/**
	 * 字段输出
	 */
	public function output($value) {
	
		$data = array();
		$value = dr_string2array($value);
		if (!$value) {
            return $data;
        } elseif (!isset($value['file'])) {
            return $value;
        }
		
		foreach ($value['file'] as $i => $file) {
			$data[] = array(
				'file' => $file, // 对应文件或附件id
				'title' => $value['title'][$i], // 对应标题
				'description' => $value['description'][$i], // 对应描述
			);
		}
		
		return $data;
	}
	
	/**
	 * 获取附件id
	 */
	public function get_attach_id($value) {


		$data = array();
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

		/*
		// 第一张作为缩略图
		if (isset($_POST['data']['thumb']) && !$_POST['data']['thumb'] && isset($data['file'][0]) && $data['file'][0]) {
            $info = \Phpcmf\Service::C()->get_attachment($data['file'][0]);
			in_array($info['fileext'], array('jpg', 'jpeg', 'png', 'gif')) && \Phpcmf\Service::L('Field')->data[1]['thumb'] = $data['file'][0];
            unset($info);
		}*/

		\Phpcmf\Service::L('Field')->data[$field['ismain']][$field['fieldname']] = dr_array2string($data);
	}
	
	/**
	 * 附件处理
	 */
	public function attach($data, $_data) {
		
		$data = dr_string2array($data);
		$_data = dr_string2array($_data);

        !isset($_data['file']) && $_data = array('file' => NULL);
        !isset($data['file']) && $data = array('file' => NULL);

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
        $size = intval($field['setting']['option']['size']) * 1024 * 1024;

        $p = IS_ADMIN ? dr_authcode([
            'size' => intval($field['setting']['option']['size']),
            'exts' => $field['setting']['option']['ext'],
            'attachment' => $field['setting']['option']['attachment'],
        ], 'ENCODE') : 0;
        $url = '/index.php?s=api&c=file&siteid=' . SITE_ID . '&m=upload&p=' . $p . '&fid=' . $field['id'];

        // 显示模板
        $tpl = '<tr class="template-download files_row">';
        $tpl .= '<td style="text-align:center;">';
        $tpl .= '<span class="files_row_preview preview">{preview}</span>';
        $tpl .= '</td>';
        $tpl .= '<td class="files_show_info">';
        $tpl .= '<div class="row">';

        $tpl .= '<div class="col-md-6 hidden-mobile files_show_name">';
        $tpl .= '<input type="hidden" class="files_row_id" name="data[' . $name . '][id][]" value="{id}">';
        $tpl .= '<input class="form-control files_row_name" {disabled} type="text" name="data[' . $name . '][file][]" value="{filepath}">';
        $tpl .= '</div>';
        $tpl .= '<div class="col-md-6 col-xs-12 files_show_title">';
        $tpl .= '<input class="form-control files_row_title" type="text" name="data[' . $name . '][title][]" value="{title}">';
        $tpl .= '</div>';

        if ($field['setting']['option']['desc']) {
        $tpl.= '<div class="col-md-12 files_show_description" style="margin-top: 10px;">';
        $tpl.= '<textarea class="form-control files_row_description" name="data['.$name.'][description][]">{description}</textarea>';
        $tpl.= '</div>';
        }
		$tpl.= '</div>';
		$tpl.= '</td>';

		$tpl.= '<td style="text-align:center;">';
		$tpl.= '<label><button onclick="dr_file_remove(this)" type="button" class="btn red file_delete btn-sm"><i class="fa fa-trash"></i></button></label>';
		$tpl.= '<label><button onclick="dr_file_edit_'.$name.'(this)" type="button" class="fileinput-button btn green file_edit btn-sm"><i class="fa fa-edit"></i>{upload}</button></label>';
		$tpl.= '</td>';
		$tpl.= '</tr>';



		
		// 已保存数据
		$val = '';
		if ($value) {
			$value = dr_string2array($value);
            if (isset($value['title']) && $value['title']) {
                foreach ($value['title'] as $i => $title) {
                    $id = $value['id'][$i] ? $value['id'][$i] : $value['file'][$i];
                    $file = \Phpcmf\Service::C()->get_attachment($id);
                    $description = $value['description'][$i] ? $value['description'][$i] : '';
                    if ($file) {
                        $disabled = 'readonly';
                        $preview = dr_file_preview_html($file['url']);
                        $filepath = dr_strcut($file['attachment'], 30);
                        $upload = '<input type="file" name="file_data">';
                    } else {
                        $disabled = '';
                        $filepath = $id;
                        $preview = dr_file_preview_html($id);
                        $upload = '';
                        $id = '';
                    }
                    $val.= str_replace(
                        ['{title}', '{description}', '{id}', '{filepath}', '{disabled}', '{preview}', '{upload}'],
                        [$title, $description, $id, $filepath, $disabled, $preview, $upload],
                        $tpl
                    );
                }
            }
		}

		$use = '<button type="button" class="btn red btn-sm fileinput-unused">
						<i class="fa fa-folder-open"></i>
						<span> '.dr_lang('浏览').' </span>
					</button>';
        $use.= $field['setting']['option']['input'] ? '<button style="margin-left: 5px;" type="button" class="btn green btn-sm fileinput-url">
						<i class="fa fa-edit"></i>
						<span> '.dr_lang('地址').' </span>
					</button>' : '';
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
			<p class="finecms-file-ts">'.$ts.'</p>
			<table role="presentation" class="table table-striped table-fc-upload clearfix">
				<tbody id="fileupload_'.$name.'_files" class="files">'.$val.'</tbody>
			</table>
		';

		if (!defined('PHPCMF_FIELD_FILE')) {
			$str.= '
				
			<link href="'.ROOT_THEME_PATH.'assets/global/plugins/jquery-file-upload/css/jquery.fileupload.css" rel="stylesheet" type="text/css" />
			<link href="'.ROOT_THEME_PATH.'assets/global/plugins/jquery-file-upload/css/jquery.fileupload-ui.css" rel="stylesheet" type="text/css" />
			
			<script src="'.ROOT_THEME_PATH.'assets/global/plugins/jquery-ui/jquery-ui.min.js" type="text/javascript"></script>
			<script src="'.ROOT_THEME_PATH.'assets/global/plugins/jquery-file-upload/js/vendor/jquery.ui.widget.js" type="text/javascript"></script>
			<script src="'.ROOT_THEME_PATH.'assets/global/plugins/jquery-file-upload/js/jquery.iframe-transport.js" type="text/javascript"></script>
			<script src="'.ROOT_THEME_PATH.'assets/global/plugins/jquery-file-upload/js/jquery.fileupload.js" type="text/javascript"></script>
			
			';
			define('PHPCMF_FIELD_FILE', 1);//防止重复加载JS
		}

		$ext = !$field['setting']['option']['ext'] || $field['setting']['option']['ext'] == '*' ? '' : 'acceptFileTypes: /(\.|\/)('.str_replace(',', '|', $field['setting']['option']['ext']).')$/i,';

		$str.= '
		
<script type="text/javascript">

$(function() {
    $("#fileupload_'.$name.'_files").sortable();
	// 未使用的附件
	$(\'#fileupload_'.$name.' .fileinput-unused\' ).click(function(){
		var c = $(\'#fileupload_'.$name.' .files_row\').length;
		var url = "/index.php?s=api&c=file&m=input_file_list&p='.$p.'&fid='.$field['id'].'&ct="+c+"&rand=" + Math.random();
		layer.open({
			type: 2,
			title: \'<i class="fa fa-folder-open"></i> '.dr_lang('浏览').'\',
            fix:true,
            scrollbar: false,
            shadeClose: true,
			shade: 0,
			area: ["60%", "70%"],
			btn: ["'.dr_lang('确定').'"],
			yes: function(index, layero){
				var body = layer.getChildFrame(\'body\', index);
				 // 延迟加载
				var loading = layer.load(2, {
					time: 10000000
				});
				$.ajax({type: "POST",dataType:"json", url: url, data: $(body).find(\'#myform\').serialize(),
					success: function(json) {
						layer.close(loading);
						if (json.code == 1) {
							layer.close(index);
							var numCount = '.$count.';
							var numItems = $(\'#fileupload_'.$name.'_files .files_row\').length;
							if(numItems + json.data.count > numCount){
								dr_tips(0, \''.dr_lang('上传文件不能超过%s个', $count).'\');
								return false;
							};
							var temp = \''.$tpl.'\';
							var html = "";
							for(var i in json.data.result){
								var tpl = temp;
								var v = json.data.result[i];
								tpl = tpl.replace(/\{preview\}/g, v.preview);
								tpl = tpl.replace(/\{id\}/g, v.id);
								tpl = tpl.replace(/\{disabled\}/g, v.disabled);
								tpl = tpl.replace(/\{filepath\}/g, v.file);
								tpl = tpl.replace(/\{title\}/g, v.name);
								tpl = tpl.replace(/\{description\}/g, "");
								tpl = tpl.replace(/\{upload\}/g, v.upload);
								html+= tpl;
							}
							$(\'#fileupload_'.$name.'_files\').append(html);
         					fileupload_'.$name.'_edit();
							dr_tips(1, json.msg);
						} else {
							dr_tips(0, json.msg);
	
						}
						return false;
					}
				});
				
				return false;
			},
            success: function(layero, index){
                // 主要用于权限验证
                var body = layer.getChildFrame(\'body\', index);
                var json = $(body).html();
                if (json.indexOf(\'"code":0\') > 0 && json.length < 150){
                    var obj = JSON.parse(json);
                    layer.close(index);
                    dr_tips(0, obj.msg);
                }
                if (json.indexOf(\'"code":1\') > 0 && json.length < 150){
                    var obj = JSON.parse(json);
                    layer.close(index);
                    dr_tips(1, obj.msg);
                }
            },
			content: url+\'&is_ajax=1\'
		});
	});
	
     // 输入地址
	$(\'#fileupload_'.$name.' .fileinput-url\' ).click(function(){
		var url = "/index.php?s=api&c=file&siteid='.SITE_ID.'&fid='.$field['id'].'&p='.$p.'&m=input_file_url";
		layer.open({
			type: 2,
			title: \'<i class="fa fa-edit"></i> '.dr_lang('输入文件地址').'\',
            fix:true,
            scrollbar: false,
            shadeClose: true,
			shade: 0,
			area: ["50%", "45%"],
			btn: ["'.dr_lang('确定').'"],
			yes: function(index, layero){
				var body = layer.getChildFrame(\'body\', index);
				 // 延迟加载
				var loading = layer.load(2, {
					time: 10000000
				});
				$.ajax({type: "POST",dataType:"json", url: url, data: $(body).find(\'#myform\').serialize(),
					success: function(json) {
						layer.close(loading);
						if (json.code == 1) {
							layer.close(index);
							var tpl = \''.$tpl.'\';
							tpl = tpl.replace(/\{preview\}/g, json.data.preview);
							tpl = tpl.replace(/\{id\}/g, json.data.id);
							tpl = tpl.replace(/\{disabled\}/g, json.data.disabled);
							tpl = tpl.replace(/\{filepath\}/g, json.data.file);
							tpl = tpl.replace(/\{title\}/g, json.data.name);
							tpl = tpl.replace(/\{description\}/g, "");
							tpl = tpl.replace(/\{upload\}/g, json.data.upload);
							$(\'#fileupload_'.$name.'_files\').append(tpl);
         					fileupload_'.$name.'_edit();
							dr_tips(1, json.msg);
						} else {
							dr_tips(0, json.msg);
	
						}
						return false;
					}
				});
				return false;
			},
			success: function(layero, index){
			    // 主要用于权限验证
                var body = layer.getChildFrame(\'body\', index);
                var json = $(body).html();
                if (json.indexOf(\'"code":0\') > 0 && json.length < 150){
                    var obj = JSON.parse(json);
                    layer.close(index);
                    dr_tips(0, obj.msg);
                }
                if (json.indexOf(\'"code":1\') > 0 && json.length < 150){
                    var obj = JSON.parse(json);
                    layer.close(index);
                    dr_tips(1, obj.msg);
                }
				var numItems = $(\'#fileupload_'.$name.'_files .files_row\').length;
				if(numItems >= '.($count).'){
					dr_tips(0, \''.dr_lang('上传文件不能超过%s个', $count).'\');
					layer.close(index);
					return false;
				}
			},
			content: url+\'&is_ajax=1\'
		});
	});
    // 初始化上传组件
	$(\'#fileupload_'.$name.'\').fileupload({
		disableImageResize: false,
		autoUpload: true,
		maxFileSize: '.$size.',
		'.$ext.'
		url: \''.$url.'\',
		dataType: \'json\',
		progressall: function (e, data) {
			// 上传进度条 all
			var progress = parseInt(data.loaded / data.total * 100, 10);
			$("#fileupload_'.$name.' .fileupload-progress").show();
			$("#fileupload_'.$name.' .fileupload-progress").removeClass("fade");
			$("#fileupload_'.$name.' .progress-bar-success").attr("style", "width: "+progress+"%");
		},
		add: function (e, data) {
			var myItems = data.originalFiles.length;
			var numItems = $(\'#fileupload_'.$name.'_files .files_row\').length;
            if(numItems + myItems > '.$count.'){
                dr_tips(0, \''.dr_lang('上传文件不能超过%s个', $count).'\');
                return false;
            }
            data.submit();
		},
		done: function (e, data) {
         
            dr_tips(data.result.code, data.result.msg);
			$("#fileupload_'.$name.' .fileupload-progress").hide();
			$("#fileupload_'.$name.' .fileupload-progress").addClass("fade");
            if (data.result.code == 0) {
            	return false;
            }
         
			var tpl = \''.$tpl.'\';
			tpl = tpl.replace(/\{preview\}/g, data.result.info.preview);
			tpl = tpl.replace(/\{id\}/g, data.result.id);
			tpl = tpl.replace(/\{disabled\}/g, "disabled");
			tpl = tpl.replace(/\{filepath\}/g, data.result.info.file);
			tpl = tpl.replace(/\{title\}/g, data.result.info.name);
			tpl = tpl.replace(/\{description\}/g, "");
			tpl = tpl.replace(/\{upload\}/g, "<input type=\"file\" name=\"file_data\"></button>");
			$(\'#fileupload_'.$name.'_files\').append(tpl);
         
         	fileupload_'.$name.'_edit();
        },
	});
    
	fileupload_'.$name.'_edit();
});

// 修改组件
function fileupload_'.$name.'_edit() {
	$(\'#fileupload_'.$name.'_files .file_edit\').fileupload({
		disableImageResize: false,
		autoUpload: true,
		maxFileSize: '.$size.',
		'.$ext.'
		url: \''.$url.'\',
		dataType: \'json\',
		progressall: function (e, data) {
			// 上传进度条 all
			var progress = parseInt(data.loaded / data.total * 100, 10);
			$("#fileupload_'.$name.' .fileupload-progress").show();
			$("#fileupload_'.$name.' .fileupload-progress").removeClass("fade");
			$("#fileupload_'.$name.' .progress-bar-success").attr("style", "width: "+progress+"%");
		},
		done: function (e, data) {
         
			$("#fileupload_'.$name.' .fileupload-progress").addClass("fade");
			$("#fileupload_'.$name.' .fileupload-progress").hide();
            dr_tips(data.result.code, data.result.msg);
            if (data.result.code == 0) {
            	return false;
            }
         
        	$(this).parents(".files_row").find(".files_row_id").val(data.result.id);
        	$(this).parents(".files_row").find(".files_row_name").val(data.result.info.file);
        	$(this).parents(".files_row").find(".files_row_preview").html(data.result.info.preview);
         
        },
	});
}
// 修改URL
function dr_file_edit_'.$name.'(e) {
	var obj = $(e).parents(".files_row");
    var file = obj.find(".files_row_name").val();
    var name = obj.find(".files_row_title").val();
	var only = obj.find(".files_row_name").attr("readonly");
	if (only == "readonly" || only == true) {
		return;
	}
	var url = "/index.php?s=api&c=file&m=input_file_url&siteid='.SITE_ID.'&fid='.$field['id'].'&p='.$p.'&file="+file+"&name="+name;
		layer.open({
			type: 2,
			title: \'<i class="fa fa-edit"></i> '.dr_lang('修改文件地址').'\',
            fix:true,
            scrollbar: false,
            shadeClose: true,
			shade: 0,
			area: ["50%", "45%"],
			btn: ["'.dr_lang('确定').'"],
			yes: function(index, layero){
	
				var body = layer.getChildFrame(\'body\', index);
				 // 延迟加载
				var loading = layer.load(2, {
					time: 10000000
				});
				$.ajax({type: "POST",dataType:"json", url: url, data: $(body).find(\'#myform\').serialize(),
					success: function(json) {
						layer.close(loading);
						if (json.code == 1) {
							layer.close(index);
							var tpl = \''.$tpl.'\';
							tpl = tpl.replace(/\{preview\}/g, json.data.preview);
							tpl = tpl.replace(/\{id\}/g, json.data.id);
							tpl = tpl.replace(/\{disabled\}/g, json.data.disabled);
							tpl = tpl.replace(/\{filepath\}/g, json.data.file);
							tpl = tpl.replace(/\{title\}/g, json.data.name);
							tpl = tpl.replace(/\{upload\}/g, json.data.upload);
							obj.remove();
							$(\'#fileupload_'.$name.'_files\').append(tpl);
         					fileupload_'.$name.'_edit();
							dr_tips(1, json.msg);
						} else {
							dr_tips(0, json.msg);
	
						}
						return false;
					}
				});
				return false;
			},
            success: function(layero, index){
                // 主要用于权限验证
                var body = layer.getChildFrame(\'body\', index);
                var json = $(body).html();
                if (json.indexOf(\'"code":0\') > 0 && json.length < 150){
                    var obj = JSON.parse(json);
                    layer.close(index);
                    dr_tips(0, obj.msg);
                }
                if (json.indexOf(\'"code":1\') > 0 && json.length < 150){
                    var obj = JSON.parse(json);
                    layer.close(index);
                    dr_tips(1, obj.msg);
                }
            },
			content: url+\'&is_ajax=1\'
		});
}

</script>
		
		';


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


        if ($value) {

            $value = dr_string2array($value);
            if (isset($value['title']) && $value['title']) {
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
                foreach ($value['title'] as $i => $title) {
                    $id = $value['id'][$i] ? $value['id'][$i] : $value['file'][$i];
                    $file = \Phpcmf\Service::C()->get_attachment($id);
                    $description = $value['description'][$i] ? $value['description'][$i] : '';
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
                        [$title, $description, $value, $filepath, $preview, $upload],
                        $tpl
                    );
                }
                $html.= '</tbody>';
                $html.= '</table>';
            }
        }

        return $this->input_format($field['fieldname'], $field['name'], $html);
    }
	
}