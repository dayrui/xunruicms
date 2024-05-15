<?php namespace Phpcmf\Field;
/**
 * {{www.xunruicms.com}}
 * {{迅睿内容管理框架系统}}
 * 本文件是框架系统文件，二次开发时不可以修改本文件，可以通过继承类方法来重写此文件
 **/

class Image extends \Phpcmf\Library\A_Field {

    protected $img_ext;

    /**
     * 构造函数
     */
    public function __construct(...$params) {
        parent::__construct(...$params);
        $this->close_xss = 1; // 关闭xss验证
        $this->fieldtype = ['TEXT' => ''];
        $this->defaulttype = 'TEXT';
        $this->img_ext = 'jpg,gif,png,jpeg,svg,webp';
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

        return [$this->_search_field().$mthumb.'
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
			<div class="form-group hidden">
				<label class="col-md-2 control-label">'.dr_lang('扩展名').'</label>
				<div class="col-md-9">
					<label><input type="text" class="form-control" size="40" name="data[setting][option][ext]" value="'.$this->img_ext.'"></label>
				</div>
			</div>'.$this->attachment($option).'',

            '<div class="form-group">
			<label class="col-md-2 control-label">'.dr_lang('控件宽度').'</label>
			<div class="col-md-9">
				<label><input type="text" class="form-control" size="10" name="data[setting][option][width]" value="'.$option['width'].'"></label>
				<span class="help-block">'.dr_lang('[整数]表示固定宽度；[整数%]表示百分比').'</span>
			</div>
		</div><div class="form-group">
                <label class="col-md-2 control-label">'.dr_lang('提示扩展名显示').'</label>
                <div class="col-md-9">
                   <div class="mt-radio-inline">
                    <label class="mt-radio mt-radio-outline"><input type="radio"  name="data[setting][option][is_ext_tips]" value="0" '.(!$option['is_ext_tips'] ? 'checked' : '').' /> '.dr_lang('已显示').' <span></span></label>
                    <label class="mt-radio mt-radio-outline"><input type="radio"  name="data[setting][option][is_ext_tips]" value="1" '.($option['is_ext_tips'] ? 'checked' : '').' /> '.dr_lang('已关闭').' <span></span></label>
                </div><span class="help-block">'.dr_lang('提示字段上传的扩展名和大小限制的文本信息').'</span>
                </div>
            </div>'];
    }

    /**
     * 验证字段属性
     */
    public function edit_config($post) {

        if (!isset($post['setting']['option']['size']) || !$post['setting']['option']['size']) {
            return dr_return_data(0, dr_lang('文件大小必须填写'));
        } elseif (!isset($post['setting']['option']['count']) || !$post['setting']['option']['count']) {
            return dr_return_data(0, dr_lang('上传数量必须填写'));
        }

        return dr_return_data(1, 'ok');
    }

    /**
     * 字段输出
     */
    public function output($value) {
        return dr_string2array($value);
    }

    /**
     * 获取附件id
     */
    public function get_attach_id($value) {
        return dr_string2array($value);
    }

    /**
     * 字段入库值
     */
    public function insert_value($field) {

        $data = \Phpcmf\Service::L('Field')->post[$field['fieldname']];
        if ($data) {
            if ($field['setting']['option']['stslt']) {
                $_field = \Phpcmf\Service::L('form')->fields;
                if (isset($_field['thumb']) && $_field['thumb']['fieldtype'] == 'File' && !\Phpcmf\Service::L('Field')->data[$_field['thumb']['ismain']]['thumb']) {
                    $one = array_key_first($data);
                    if ($data[$one]) {
                        \Phpcmf\Service::L('Field')->data[1]['thumb'] = $data[$one];
                    }
                }
            }
            if (dr_count($data) > $field['setting']['option']['count']) {
                $data = array_slice($data, 0, $field['setting']['option']['count']-2);
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

    protected function _format_file_size($fileSize, $round = 2) {

        if (!$fileSize) {
            return 0;
        }

        $i = 0;
        $inv = 1 / 1024;
        $unit = [' Bytes', ' KB', ' MB', ' GB', ' TB', ' PB', ' EB', ' ZB', ' YB'];

        while ($fileSize >= 1024 && $i < 8) {
            $fileSize *= $inv;
            ++$i;
        }

        $temp = sprintf("%.2f", $fileSize);
        $value = $temp - (int) $temp ? $temp : $fileSize;

        return '<strong>'.round($value, $round).'</strong>' . $unit[$i];
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
        $text = ($field['setting']['validate']['required'] ? '<span class="required" aria-required="true"> * </span>' : '') . dr_lang($field['name']);
        // 表单宽度设置
        $width = \Phpcmf\Service::IS_MOBILE_USER() ? '100%' : ($field['setting']['option']['width'] ? $field['setting']['option']['width'] : '100%');

        // 字段提示信息
        $tips = ($name == 'title' && APP_DIR) || $field['setting']['validate']['tips'] ? '<span class="help-block" id="dr_' . $field['fieldname'] . '_tips">' . $field['setting']['validate']['tips'] . '</span>' : '';

        $size = ($field['setting']['option']['size']);
        !$size && $size = 0;
        $count = intval($field['setting']['option']['count']);

        $p = dr_authcode([
            'size' => $size,
            'exts' => $this->img_ext,
            'attachment' => $field['setting']['option']['attachment'],
            'image_reduce' => $field['setting']['option']['image_reduce'],
        ], 'ENCODE');
        $url = WEB_DIR.''.(IS_ADMIN ? SELF.'?c=api' : 'index.php?s=api&c=file').'&token='.dr_get_csrf_token().'&siteid=' . SITE_ID . '&m=upload&p=' . $p . '&fid=' . $field['id'];

        // 显示模板
        $i = 0;
        $tpl = '';
        if ($value) {
            $value = dr_string2array($value);
            if ($value) {
                foreach ($value as $id) {
                    $file = \Phpcmf\Service::C()->get_attachment($id, 1);
                    if ($file) {
                        $editname = '';
                        if ($file['uid'] == \Phpcmf\Service::C()->uid || IS_ADMIN) {
                            $editname = ' onclick="dr_iframe(\''.dr_lang('修改名称').'\', \''.dr_web_prefix((IS_ADMIN ? SELF.'?c=api' : 'index.php?s=api&c=file').'&m=name_edit&id='.$file['id']).'\', \'350px\', \'220px\');" title="'.dr_lang('修改名称').'"';
                        }
                        $tpl.= '<div id="image-'.$name.'-'.$id.'" class="dz-preview dz-processing dz-success dz-complete dz-image-preview">';
                        $tpl.=     '<div class="dz-image">';
                        $tpl.=        ' <img data-dz-thumbnail="" src="'.dr_get_file($id).'">';
                        $tpl.=     '</div>';

                        $tpl.=    ' <div class="dz-details"><div class="dz-size" onclick="dr_preview_image(\''.$file['url'].'\');" title="'.dr_lang('放大图片').'"><span data-dz-size="">'.$this->_format_file_size($file['filesize']).'</span></div><div class="dz-filename" '.$editname.'><span data-dz-name="">'.$file['filename'].'</span></div></div>';

                        $tpl.=     '<a class="dz-remove" href="javascript:dr_delete_image_'.$name.'('.$id.');" title="'.dr_lang('删除图片').'">';
                        $tpl.=      '   <i class="fa fa-times-circle"></i>';
                        $tpl.=    ' </a>';
                        $tpl.=    '<input class="dr_dropzone_'.$name.'" type="hidden" name="data['.$name.'][]" value="'.$id.'" />';
                        $tpl.= '</div>';
                        $i++;
                    }
                }
            }
        }
        $tpl.= '<input class="dr_dropzone_'.$name.'_total" type="hidden" value="'.$i.'" />';
        $ts = dr_lang('每张图片最大%s，最多上传%s张图片', $size . 'MB', intval($field['setting']['option']['count']));

        // 表单输出
        $str = '<div class="dropzone2 dropzone-file-area dropzone-images-area" id="my-dropzone-'.$name.'" style="width:'.$width.(is_numeric($width) ? 'px' : '').';">
            </div>
		';
        if (!$field['setting']['option']['is_ext_tips']) {
            $str.= '<div class="finecms-file-ts">'.$ts.'</div>';
        }

        if (!$this->is_load_js($field['fieldtype'])) {
            $str.= '
			<link href="'.ROOT_THEME_PATH.'assets/global/plugins/dropzone/dropzone.min.css?v='.CMF_UPDATE_TIME.'" rel="stylesheet" type="text/css" />
			<script src="'.ROOT_THEME_PATH.'assets/global/plugins/dropzone/dropzone.min.js?v='.CMF_UPDATE_TIME.'" type="text/javascript"></script>
			';
            $this->set_load_js($field['fieldtype'], 1);
        }

        $js = \Phpcmf\Service::L('js_packer');
        $str.= $js->pack('
		
<script type="text/javascript">

$(function() {
    $("#my-dropzone-'.$name.'").sortable();
    Dropzone.autoDiscover = false;
    $("#my-dropzone-'.$name.'").dropzone({ 
        addRemoveLinks:true,
        maxFiles:99999,
        maxFilesize: '.$size.',
        acceptedFiles: "image/*",
        dictMaxFilesExceeded: "'.dr_lang("最多只能上传%s张图片", $count).(CI_DEBUG ? '（可在自定义字段中设置本字段的个数值）' : '').'",
        dictResponseError: \'文件上传失败\',
        dictInvalidFileType: "不能上传该类型文件",
        dictFallbackMessage:"浏览器不受支持",
        dictFileTooBig:"文件过大上传文件最大支持",
        url: "'.$url.'",
        init: function() {
           this.on("addedfile", function(file) { 
                var activeFiles = this.getActiveFiles();
                var num = parseInt($(".dr_dropzone_'.$name.'_total").val());
                if (num+activeFiles.length+1 > '.$count.') {
                    this.options.maxFiles = -1;
                }
            });
            this.on("success", function(file, res) {
                var rt = JSON.parse(res);
                if(rt.code){
                     var num = parseInt($(".dr_dropzone_'.$name.'_total").val());
                     $(".dr_dropzone_'.$name.'_total").val(num+1);
                    var input = \'<input class="dr_dropzone_'.$name.'" type="hidden" name="data['.$name.'][]" value="\'+rt.id+\'" />\';
                    $(file.previewElement).append(input);
                }else{
                    dr_tips(0, rt.msg);
                    file.previewElement.classList.remove("dz-success");
                    file.previewElement.classList.add("dz-error");
                    file.previewElement.classList.add("dz-error");
                }
                 
            });
        }
     });
    $("#my-dropzone-'.$name.'").append("'.addslashes($tpl).'");
});
function dr_delete_image_'.$name.'(e) {
    var num = parseInt($(".dr_dropzone_'.$name.'_total").val());
  $(".dr_dropzone_'.$name.'_total").val(num-1);
  $("#image-'.$name.'-"+e).remove();
}
</script>
		
		', 0);

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
        $value = dr_string2array($value);
        if ($value) {
            $html.= '<div class="row">';
            foreach ($value as $id) {
                $html.= '<div class="col-sm-3 col-md-2">';
                $html.= '<a href="javascript:dr_preview_image(\''.dr_get_file($id).'\');" class="thumbnail">';
                $html.= '<img src="'.dr_get_file($id).'" style="width:100%">';
                $html.= '</a>';
                $html.= '</div>';
            }
            $html.= '</div>';
        }

        return $this->input_format($field['fieldname'], $field['name'], $html);
    }

}