<?php namespace Phpcmf\Field;

/**
 * http://www.xunruicms.com
 * 本文件是框架系统文件，二次开发时不可以修改本文件，可以通过继承类方法来重写此文件
 **/


class Image extends \Phpcmf\Library\A_Field {

    private $img_ext;

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

        return ['
      
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
		</div>'];
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
        if ($data === $_data) {
            return NULL;
        }

        // 当无新数据且有旧数据表示删除旧附件
        if (!$data && $_data) {
            return array(
                [],
                $_data
            );
        }

        // 当无旧数据且有新数据表示增加新附件
        if ($data && !$_data) {
            return array(
                $data,
                []
            );
        }

        // 剩下的情况就是删除旧文件增加新文件

        // 新旧附件的交集，表示固定的
        $intersect = @array_intersect($data, $_data);

        return array(
            @array_diff($data, $intersect), // 固有的与新文件中的差集表示新增的附件
            @array_diff($_data, $intersect), // 固有的与旧文件中的差集表示待删除的附件
        );
    }

    private function _format_file_size($fileSize, $round = 2) {

        if (!$fileSize) {
            return 0;
        }

        $i = 0;
        $inv = 1 / 1024;
        $unit = array(' Bytes', ' KB', ' MB', ' GB', ' TB', ' PB', ' EB', ' ZB', ' YB');

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
        $text = ($field['setting']['validate']['required'] ? '<span class="required" aria-required="true"> * </span>' : '') . $field['name'];
        // 表单宽度设置
        $width = \Phpcmf\Service::_is_mobile() ? '100%' : ($field['setting']['option']['width'] ? $field['setting']['option']['width'] : '100%');

        // 字段提示信息
        $tips = ($name == 'title' && APP_DIR) || $field['setting']['validate']['tips'] ? '<span class="help-block" id="dr_' . $field['fieldname'] . '_tips">' . $field['setting']['validate']['tips'] . '</span>' : '';

        $size = intval($field['setting']['option']['size']);
        $count = intval($field['setting']['option']['count']);

        $p = dr_authcode([
            'size' => intval($field['setting']['option']['size']),
            'exts' => $this->img_ext,
            'attachment' => $field['setting']['option']['attachment'],
            'image_reduce' => $field['setting']['option']['image_reduce'],
        ], 'ENCODE');
        $url = '/index.php?s=api&c=file&token='.dr_get_csrf_token().'&siteid=' . SITE_ID . '&m=upload&p=' . $p . '&fid=' . $field['id'];

        // 显示模板
        $i = 0;
        $tpl = '';
        if ($value) {
            $value = dr_string2array($value);
            if ($value) {
                foreach ($value as $id) {
                    $file = \Phpcmf\Service::C()->get_attachment($id);
                    if ($file) {
                        $tpl.= '<div id="image-'.$name.'-'.$id.'" class="dz-preview dz-processing dz-success dz-complete dz-image-preview">';
                        $tpl.=     '<div class="dz-image">';
                        $tpl.=        ' <img data-dz-thumbnail="" src="'.dr_thumb($id, 110, 110).'">';
                        $tpl.=     '</div>';

                        $tpl.=    ' <div class="dz-details"><div class="dz-size" onclick="dr_preview_image(\''.$file['url'].'\');" title="'.dr_lang('放大图片').'"><span data-dz-size="">'.$this->_format_file_size($file['filesize']).'</span></div></div>';

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
        $ts = dr_lang('每张图片最大%s，最多上传%s张图片', intval($field['setting']['option']['size']) . 'MB', intval($field['setting']['option']['count']));

        // 表单输出
        $str = '
			 <div class="dropzone dropzone-file-area" id="my-dropzone-'.$name.'" style="width:'.$width.(is_numeric($width) ? 'px' : '').';">
            </div>
			<div class="finecms-file-ts">'.$ts.'</div>
		';

        if (!defined('POSCMS_FIELD_IMAGES')) {
            $str.= '
			<link href="'.ROOT_THEME_PATH.'assets/global/plugins/dropzone/dropzone.min.css?v='.CMF_UPDATE_TIME.'" rel="stylesheet" type="text/css" />
			<script src="'.ROOT_THEME_PATH.'assets/global/plugins/dropzone/dropzone.min.js?v='.CMF_UPDATE_TIME.'" type="text/javascript"></script>
			';
            define('POSCMS_FIELD_IMAGES', 1);//防止重复加载JS
        }

        $js = \Phpcmf\Service::L('js_packer');
        $str.= $js->pack('
		
<script type="text/javascript">

$(function() {
    $("#my-dropzone-'.$name.'").sortable();
    Dropzone.autoDiscover = false;
    $("#my-dropzone-'.$name.'").dropzone({ 
        addRemoveLinks:true,
        maxFiles:'.$count.',
        maxFilesize: '.$size.',
        acceptedFiles: "image/*",
        dictMaxFilesExceeded: "'.dr_lang("最多只能上传%s张图片", $count).'",
        dictResponseError: \'文件上传失败\',
        dictInvalidFileType: "不能上传该类型文件",
        dictFallbackMessage:"浏览器不受支持",
        dictFileTooBig:"文件过大上传文件最大支持",
        url: "'.$url.'",
        init: function() {
           this.on("addedfile", function(file) { 
                var nums = $(".dr_dropzone_'.$name.'").length;
                this.options.maxFiles = '.$count.' - nums;
            });
            this.on("success", function(file, res) {
                var rt = JSON.parse(res);
                if(rt.code){
                    var input = \'<input class="dr_dropzone_'.$name.'" type="hidden" name="data['.$name.'][]" value="\'+rt.id+\'" />\';
                    $(file.previewElement).append(input);
                }else{
                    dr_tips(0, rt.msg);
                    file.previewElement.classList.remove("dz-success");
                    file.previewElement.classList.add("dz-error");
                }
                 
            });
        }
     });
    $("#my-dropzone-'.$name.'").append("'.addslashes($tpl).'");
});
function dr_delete_image_'.$name.'(e) {
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
                $html.= '<img src="'.dr_thumb($id, 200, 200).'" style="width:100%">';
                $html.= '</a>';
                $html.= '</div>';
            }
            $html.= '</div>';
        }

        return $this->input_format($field['fieldname'], $field['name'], $html);
    }

}