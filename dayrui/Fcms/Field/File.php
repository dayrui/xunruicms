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
			</div>'.$this->attachment(isset($option['attachment']) ? $option['attachment'] : 0).'
			'
			,
			''
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
		$size = intval($field['setting']['option']['size']) * 1024 * 1024;

        $p = IS_ADMIN ? dr_authcode([
            'size' => intval($field['setting']['option']['size']),
            'exts' => $field['setting']['option']['ext'],
            'attachment' => $field['setting']['option']['attachment'],
        ], 'ENCODE') : 0;
		$url = '/index.php?s=api&c=file&siteid='.SITE_ID.'&m=upload&p='.$p.'&fid='.$field['id'];

		// 显示模板
		$tpl = '<div style="margin-bottom: 10px;" id="dr_'.$name.'_files_row" class="template-download files_row">';
		$tpl.= '<div >';
		$tpl.= '<span class="files_row_preview preview">{preview}</span>';
		$tpl.= '</div>';
		$tpl.= '<input type="hidden" '.$attr.' id="dr_'.$name.'" class="files_row_id" name="data['.$name.']" value="{id}" />';
		$tpl.= '</div>';

		// 已保存数据
		$val = '';
		$show_delete = 0;
		if ($value) {
			$file = \Phpcmf\Service::C()->get_attachment($value);
			if ($file) {
				$preview = dr_file_preview_html($file['url']);
				$filepath = $file['attachment'];
				$title = $file['filename'];
				$upload = '';
			} else {
				$filepath = $value;
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

        $use = '<button type="button" class="btn red btn-sm fileinput-unused">
						<i class="fa fa-folder-open"></i>
						<span> '.dr_lang('浏览').' </span>
					</button>';
        $use.= $field['setting']['option']['input'] ? '<button style="margin-left: 5px;" type="button" class="btn green btn-sm fileinput-url">
						<i class="fa fa-edit"></i>
						<span> '.dr_lang('地址').' </span>
					</button>' : '';
        $use.= '<button onclick="dr_file_remove_'.$name.'()" style="margin-left: 5px;'.(!$show_delete ? 'display:none' : '').'" type="button" class="btn red btn-sm fileinput-delete">
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
			</div>
			<p class="finecms-file-ts">'.$ts.'</p>
			<div id="fileupload_'.$name.'_files" class="files">'.$val.'</div>
		';

		if (!defined('PHPCMF_FIELD_FILE')) {
			$str.= '
				
			<link href="'.ROOT_THEME_PATH.'assets/global/plugins/jquery-file-upload/css/jquery.fileupload.css" rel="stylesheet" type="text/css" />
			<link href="'.ROOT_THEME_PATH.'assets/global/plugins/jquery-file-upload/css/jquery.fileupload-ui.css" rel="stylesheet" type="text/css" />
			
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
						
							var tpl = \''.$tpl.'\';
							var html = "";
							var v = json.data.result[0];
							tpl = tpl.replace(/\{preview\}/g, v.preview);
							tpl = tpl.replace(/\{id\}/g, v.id);
							tpl = tpl.replace(/\{filepath\}/g, v.file);
							tpl = tpl.replace(/\{title\}/g, v.name);
							tpl = tpl.replace(/\{upload\}/g, v.upload);
							html+= tpl;
							$(\'#fileupload_'.$name.'_files\').html(html);
                            $(\'#fileupload_'.$name.'\').find(\'.fileinput-delete\').show();
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
		var url = "/index.php?s=api&c=file&m=input_file_url&siteid='.SITE_ID.'&p='.$p.'&fid='.$field['id'].'&one=1";
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
							tpl = tpl.replace(/\{filepath\}/g, json.data.file);
							tpl = tpl.replace(/\{title\}/g, json.data.name);
							tpl = tpl.replace(/\{upload\}/g, json.data.upload);
							$(\'#fileupload_'.$name.'_files\').html(tpl);
         					fileupload_'.$name.'_edit();
                            $(\'#fileupload_'.$name.'\').find(\'.fileinput-delete\').show();
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
    // 初始化上传组件
	$(\'#fileupload_'.$name.'\').fileupload({
		disableImageResize: false,
		autoUpload: true,
		maxFileSize: '.$size.',
		url: \''.$url.'\',
		dataType: \'json\',
		'.$ext.'
		progressall: function (e, data) {
			// 上传进度条 all
			var progress = parseInt(data.loaded / data.total * 100, 10);
			$("#fileupload_'.$name.' .fileupload-progress").show();
			$("#fileupload_'.$name.' .fileupload-progress").removeClass("fade");
			$("#fileupload_'.$name.' .progress-bar-success").attr("style", "width: "+progress+"%");
		},
		add: function (e, data) {
			$("#fileupload_'.$name.' .fileupload-progress").hide();
            data.submit();
		},
		done: function (e, data) {
         
            dr_tips(data.result.code, data.result.msg);
			$("#fileupload_'.$name.' .fileupload-progress").addClass("fade");
			$("#fileupload_'.$name.' .fileupload-progress").hide();
            if (data.result.code == 0) {
            	return false;
            }
         
			var tpl = \''.$tpl.'\';
			tpl = tpl.replace(/\{preview\}/g, data.result.info.preview);
			tpl = tpl.replace(/\{id\}/g, data.result.id);
			tpl = tpl.replace(/\{filepath\}/g, data.result.info.file);
			tpl = tpl.replace(/\{title\}/g, data.result.info.name);
			tpl = tpl.replace(/\{upload\}/g, "<input type=\"file\" name=\"file_data\"></button>");
			$(\'#fileupload_'.$name.'_files\').html(tpl);
            $(\'#fileupload_'.$name.'\').find(\'.fileinput-delete\').show();
         
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
         
			$("#fileupload_'.$name.' .fileupload-progress").hide();
			$("#fileupload_'.$name.' .fileupload-progress").addClass("fade");
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
    var file = $("#dr_'.$name.'").val();
	if ($(e).html().length > 50) {
		// 本身是文件时跳过
		return;
	}
	var url = "/index.php?s=api&c=file&m=input_file_url&one=1&siteid='.SITE_ID.'&p='.$p.'&fid='.$field['id'].'&file="+file;
		layer.open({
			type: 2,
			title: \'<i class="fa fa-edit"></i> '.dr_lang('修改文件地址').'\',
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
							tpl = tpl.replace(/\{filepath\}/g, json.data.file);
							tpl = tpl.replace(/\{title\}/g, json.data.name);
							tpl = tpl.replace(/\{upload\}/g, json.data.upload);
							obj.remove();
							$(\'#fileupload_'.$name.'_files\').append(tpl);
                            $(\'#fileupload_'.$name.'\').find(\'.fileinput-delete\').show();
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

function dr_file_remove_'.$name.'() {
  $(\'#fileupload_'.$name.'_files\').html("");
  $(\'#fileupload_'.$name.'\').find(\'.fileinput-delete\').hide();
}

</script>
		
		';

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
            $tpl = '<tr class="template-download files_row">';
            $tpl.= '<td style="text-align:center;width:90px;border-top:0">';
            $tpl.= '<span class="files_row_preview preview">{preview}</span>';
            $tpl.= '</td>';
            $tpl.= '<td style="border-top:0">';
            $tpl.= '{filepath}';
            $tpl.= '</td>';
            $tpl.= '</tr>';
            $file = \Phpcmf\Service::C()->get_attachment($value);
            if ($file) {
                $preview = dr_file_preview_html($file['url']);
                $filepath = $file['attachment'];
                $title = $file['filename'];
                $upload = '';
            } else {
                $filepath = $value;
                $preview = dr_file_preview_html($value);
                $upload = '';
                $title = '';
            }
            $html.= '<table role="presentation" class="table table-striped table-fc-upload clearfix">';
            $html.= '<tbody class="files">';
            $html.= str_replace(
                ['{title}', '{id}', '{filepath}', '{preview}', '{upload}'],
                [$title, $value, $filepath, $preview, $upload],
                $tpl
            );
            $html.= '</tbody>';
            $html.= '</table>';
        }

        return $this->input_format($field['fieldname'], $field['name'], $html);
    }
}