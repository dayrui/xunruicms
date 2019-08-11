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
 * www.xunruicms.com
 *
 * */
class Ueditor extends \Phpcmf\Library\A_Field {

    /**
     * 构造函数
     */
    public function __construct(...$params) {
        parent::__construct(...$params);
        $this->close_xss = 1; // 关闭xss验证
        $this->fieldtype = ['MEDIUMTEXT' => ''];
        $this->defaulttype = 'MEDIUMTEXT';
    }

    /**
     * 字段相关属性参数
     *
     * @param	array	$value	值
     * @return  string
     */
    public function option($option) {

        $option['mode'] = isset($option['mode']) ? $option['mode'] : 1;
        $option['page'] = isset($option['page']) ? $option['page'] : 0;
        $option['tool'] = isset($option['tool']) ? $option['tool'] : '\'bold\', \'italic\', \'underline\'';
        $option['mode2'] = isset($option['mode2']) ? $option['mode2'] : $option['mode'];
        $option['tool2'] = isset($option['tool2']) ? $option['tool2'] : $option['tool'];
        $option['mode3'] = isset($option['mode3']) ? $option['mode3'] : $option['mode'];
        $option['tool3'] = isset($option['tool3']) ? $option['tool3'] : $option['tool'];
        $option['value'] = isset($option['value']) ? $option['value'] : '';
        $option['width'] = isset($option['width']) ? $option['width'] : '100%';
        $option['height'] = isset($option['height']) ? $option['height'] : 300;
        $option['fieldtype'] = isset($option['fieldtype']) ? $option['fieldtype'] : '';
        $option['autofloat'] = isset($option['autoheight']) ? $option['autoheight'] : 0;
        $option['autoheight'] = isset($option['autoheight']) ? $option['autoheight'] : 0;
        $option['fieldlength'] = isset($option['fieldlength']) ? $option['fieldlength'] : '';
        $option['watermark'] = isset($option['watermark']) ? $option['watermark'] : '';
        $option['show_bottom_boot'] = isset($option['show_bottom_boot']) ? $option['show_bottom_boot'] : '';

        $wm = \Phpcmf\Service::C()->get_cache('site', SITE_ID, 'watermark', 'ueditor') ? '<div class="form-group">
                    <label class="col-md-2 control-label">'.dr_lang('图片水印').'</label>
                    <div class="col-md-9">
                        <div class="form-control-static">
                            '.dr_lang('系统强制开启水印').'
                        </div>
                    </div>
                </div>' : '<div class="form-group">
                    <label class="col-md-2 control-label">'.dr_lang('图片水印').'</label>
                    <div class="col-md-9" style="padding-left: 35px;">
                        <div class="radio-list">
                            <label class="radio-inline"><input type="radio" value="1" name="data[setting][option][watermark]" '.($option['watermark'] == 1 ? 'checked' : '').' > '.dr_lang('开启').'</label>
                            <label class="radio-inline"><input type="radio" value="0" name="data[setting][option][watermark]" '.($option['watermark'] == 0 ? 'checked' : '').' > '.dr_lang('关闭').'</label>
                        </div>
                    </div>
                </div>';

        return ['<div class="form-group">
                    <label class="col-md-2 control-label">'.dr_lang('下载远程图片').'</label>
                    <div class="col-md-9" style="padding-left: 35px;">
                        <div class="radio-list">
                            <label class="radio-inline"><input type="radio" value="1" name="data[setting][option][down_img]" '.($option['down_img'] == 1 ? 'checked' : '').' > '.dr_lang('自动').'</label>
                            <label class="radio-inline"><input type="radio" value="0" name="data[setting][option][down_img]" '.($option['down_img'] == 0 ? 'checked' : '').' > '.dr_lang('手动').'</label>
                        </div>
                    </div>
                </div>'.$wm.
                '
                <div class="form-group">
                    <label class="col-md-2 control-label">'.dr_lang('底部工具栏').'</label>
                    <div class="col-md-9" style="padding-left: 35px;">
                        <div class="radio-list">
                            <label class="radio-inline"><input type="radio" value="1" name="data[setting][option][show_bottom_boot]" '.($option['show_bottom_boot'] == 1 ? 'checked' : '').' > '.dr_lang('开启').'</label>
                            <label class="radio-inline"><input type="radio" value="0" name="data[setting][option][show_bottom_boot]" '.($option['show_bottom_boot'] == 0 ? 'checked' : '').' > '.dr_lang('关闭').'</label>
                        </div>
                    </div>
                </div>
                <div class="form-group hidden">
                    <label class="col-md-2 control-label">'.dr_lang('编辑器类型').'</label>
                    <div class="col-md-9" style="padding-left: 35px;">
                        <div class="radio-list">
                            <label class="radio-inline"><input type="radio" value="1" name="data[setting][option][mini]" '.($option['mini'] == 1 ? 'checked' : '').' > '.dr_lang('Umeditor').'</label>
                            <label class="radio-inline"><input type="radio" value="0" name="data[setting][option][mini]" '.($option['mini'] == 0 ? 'checked' : '').' > '.dr_lang('Ueditor').'</label>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-md-2 control-label">'.dr_lang('移动端强制UM').'</label>
                    <div class="col-md-9" style="padding-left: 35px;">
                        <div class="radio-list">
                            <label class="radio-inline"><input type="radio" value="0" name="data[setting][option][mobile_mini]" '.($option['mobile_mini'] == 0 ? 'checked' : '').' > '.dr_lang('开启').'</label>
                            <label class="radio-inline"><input type="radio" value="1" name="data[setting][option][mobile_mini]" '.($option['mobile_mini'] == 1 ? 'checked' : '').' > '.dr_lang('关闭').'</label>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-md-2 control-label">'.dr_lang('固定工具栏').'</label>
                    <div class="col-md-9" style="padding-left: 35px;">
                        <div class="radio-list">
                            <label class="radio-inline"><input type="radio" value="1" name="data[setting][option][autofloat]" '.($option['autofloat'] == 1 ? 'checked' : '').' > '.dr_lang('开启').'</label>
                            <label class="radio-inline"><input type="radio" value="0" name="data[setting][option][autofloat]" '.($option['autofloat'] == 0 ? 'checked' : '').' > '.dr_lang('关闭').'</label>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-md-2 control-label">'.dr_lang('自动伸长高度').'</label>
                    <div class="col-md-9" style="padding-left: 35px;">
                        <div class="radio-list">
                            <label class="radio-inline"><input type="radio" value="1" name="data[setting][option][autoheight]" '.($option['autoheight'] == 1 ? 'checked' : '').' > '.dr_lang('开启').'</label>
                            <label class="radio-inline"><input type="radio" value="0" name="data[setting][option][autoheight]" '.($option['autoheight'] == 0 ? 'checked' : '').' > '.dr_lang('关闭').'</label>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-md-2 control-label">'.dr_lang('分页标签').'</label>
                    <div class="col-md-9" style="padding-left: 35px;">
                        <div class="radio-list">
                            <label class="radio-inline"><input type="radio" value="1" name="data[setting][option][page]" '.($option['page'] ? 'checked' : '').' > '.dr_lang('开启').'</label>
                            <label class="radio-inline"><input type="radio" value="0" name="data[setting][option][page]" '.(!$option['page'] ? 'checked' : '').' > '.dr_lang('关闭').'</label>
                        </div>
                    </div>
                </div>
            
				<div class="form-group">
                    <label class="col-md-2 control-label">'.dr_lang('后台编辑器模式').'</label>
                    <div class="col-md-9" style="padding-left: 35px;">
                        <div class="radio-list">
                            <label class="radio-inline"><input type="radio" value="1" name="data[setting][option][mode]" '.($option['mode'] == 1 ? 'checked' : '').' onclick="$(\'#bjqms1\').hide()"> '.dr_lang('完整').'</label>
                            <label class="radio-inline"><input type="radio" value="2" name="data[setting][option][mode]" '.($option['mode'] == 2 ? 'checked' : '').' onclick="$(\'#bjqms1\').hide()"> '.dr_lang('精简').'</label>
                            <label class="radio-inline"><input type="radio" value="3" name="data[setting][option][mode]" '.($option['mode'] == 3 ? 'checked' : '').' onclick="$(\'#bjqms1\').show()"> '.dr_lang('自定义').'</label>
                        </div>
                    </div>
                </div>
				<div class="form-group" id="bjqms1" '.($option['mode'] < 3 ? 'style="display:none"' : '').'>
                    <label class="col-md-2 control-label">'.dr_lang('工具栏').'</label>
                    <div class="col-md-9">
                    <textarea name="data[setting][option][tool]" style="height:90px;" class="form-control">'.$option['tool'].'</textarea>
					<span class="help-block">'.dr_lang('必须严格按照Ueditor工具栏格式\'fullscreen\', \'source\', \'|\', \'undo\', \'redo\'').'</span>
                    </div>
                </div>
				<div class="form-group">
                    <label class="col-md-2 control-label">'.dr_lang('前端编辑器模式').'</label>
                    <div class="col-md-9" style="padding-left: 35px;">
                        <div class="radio-list">
                            <label class="radio-inline"><input type="radio" value="1" name="data[setting][option][mode2]" '.($option['mode2'] == 1 ? 'checked' : '').' onclick="$(\'#bjqms2\').hide()"> '.dr_lang('完整').'</label>
                            <label class="radio-inline"><input type="radio" value="2" name="data[setting][option][mode2]" '.($option['mode2'] == 2 ? 'checked' : '').' onclick="$(\'#bjqms2\').hide()"> '.dr_lang('精简').'</label>
                            <label class="radio-inline"><input type="radio" value="3" name="data[setting][option][mode2]" '.($option['mode2'] == 3 ? 'checked' : '').' onclick="$(\'#bjqms2\').show()"> '.dr_lang('自定义').'</label>
                        </div>
                    </div>
                </div>
				<div class="form-group" id="bjqms2" '.($option['mode2'] < 3 ? 'style="display:none"' : '').'>
                    <label class="col-md-2 control-label">'.dr_lang('工具栏').'</label>
                    <div class="col-md-9">
                    <textarea name="data[setting][option][tool2]" style="height:90px;" class="form-control">'.$option['tool2'].'</textarea>
					<span class="help-block">'.dr_lang('必须严格按照Ueditor工具栏格式\'fullscreen\', \'source\', \'|\', \'undo\', \'redo\'').'</span>
                    </div>
                </div>
				<div class="form-group">
                    <label class="col-md-2 control-label">'.dr_lang('移动端编辑器模式').'</label>
                    <div class="col-md-9" style="padding-left: 35px;">
                        <div class="radio-list">
                            <label class="radio-inline"><input type="radio" value="1" name="data[setting][option][mode3]" '.($option['mode3'] == 1 ? 'checked' : '').' onclick="$(\'#bjqms3\').hide()"> '.dr_lang('完整').'</label>
                            <label class="radio-inline"><input type="radio" value="2" name="data[setting][option][mode3]" '.($option['mode3'] == 2 ? 'checked' : '').' onclick="$(\'#bjqms3\').hide()"> '.dr_lang('精简').'</label>
                            <label class="radio-inline"><input type="radio" value="3" name="data[setting][option][mode3]" '.($option['mode3'] == 3 ? 'checked' : '').' onclick="$(\'#bjqms3\').show()"> '.dr_lang('自定义').'</label>
                        </div>
                    </div>
                </div>
				<div class="form-group" id="bjqms3" '.($option['mode3'] < 3 ? 'style="display:none"' : '').'>
                    <label class="col-md-2 control-label">'.dr_lang('工具栏').'</label>
                    <div class="col-md-9">
                    <textarea name="data[setting][option][tool3]" style="height:90px;" class="form-control">'.$option['tool3'].'</textarea>
					<span class="help-block">'.dr_lang('必须严格按照Ueditor工具栏格式\'fullscreen\', \'source\', \'|\', \'undo\', \'redo\'').'</span>
                    </div>
                </div>'.$this->attachment(isset($option['attachment']) ? $option['attachment'] : 0).'
                <div class="form-group">
                    <label class="col-md-2 control-label">'.dr_lang('默认存储值').'</label>
                    <div class="col-md-9">
                        <label><input id="field_default_value" type="text" class="form-control" size="20" value="'.$option['value'].'" name="data[setting][option][value]"></label>
                        <label>'.$this->member_field_select().'</label>
                        <span class="help-block">'.dr_lang('也可以设置会员表字段，表示用当前登录会员信息来填充这个值').'</span>
                    </div>
                </div>
				'.$this->field_type($option['fieldtype'], $option['fieldlength']),

            '<div class="form-group">
                    <label class="col-md-2 control-label">'.dr_lang('控件宽度').'</label>
                    <div class="col-md-9">
                        <label><input type="text" class="form-control" name="data[setting][option][width]" value="'.$option['width'].'"></label>
					    <span class="help-block">'.dr_lang('[整数]表示固定宽带；[整数%]表示百分比').'</span>
                    </div>
                </div>
				<div class="form-group">
                    <label class="col-md-2 control-label">'.dr_lang('控件高度').'</label>
                    <div class="col-md-9">
                        <label><input type="text" class="form-control" name="data[setting][option][height]" value="'.$option['height'].'"></label>
					    <label>px</label>
                    </div>
                </div>'
        ];
    }

    /**
     * 字段入库值
     */
    public function insert_value($field) {

        $value = \Phpcmf\Service::L('Field')->post[$field['fieldname']];

        // 第一张作为缩略图
        $slt = isset($_POST['data']['thumb']) && isset($_POST['is_auto_thumb'])  && !$_POST['data']['thumb'] && $_POST['is_auto_thumb'];
        // 是否下载图片
        $yct = $field['setting']['option']['down_img'] || (isset($_POST['is_auto_down_img']) && $_POST['is_auto_down_img']);

        if (($yct || $slt) && preg_match_all("/(src)=([\"|']?)([^ \"'>]+\.(gif|jpg|jpeg|png))\\2/i", $value, $imgs)) {
            foreach ($imgs[3] as $img) {
                if (strpos($img, '/api/ueditor/') !== false
                    || strpos($img, '/api/umeditor/') !== false) {
                    continue;
                }
                // 下载图片
                if ($yct && strpos($img, 'http') === 0) {
                    if (dr_is_app('mfile') && \Phpcmf\Service::M('mfile', 'mfile')->check_upload(\Phpcmf\Service::C()->uid)) {
                        //用户存储空间已满
                    } else {
                        // 正常下载
                        // 判断域名白名单
                        $arr = parse_url($img);
                        $domain = $arr['host'];
                        if ($domain) {
                            $sites = WRITEPATH.'config/domain_site.php';
                            if (isset($sites[$domain])) {
                                // 过滤站点域名
                            } elseif (strpos(SYS_UPLOAD_URL, $domain) !== false) {
                                // 过滤附件白名单
                            } else {
                                $zj = 0;
                                $remote = \Phpcmf\Service::C()->get_cache('attachment');
                                if ($remote) {
                                    foreach ($remote as $t) {
                                        if (strpos($t['url'], $domain) !== false) {
                                            $zj = 1;
                                            break;
                                        }
                                    }
                                }
                                if ($zj == 0) {
                                    // 可以下载文件
                                    // 下载远程文件
                                    $rt = \Phpcmf\Service::L('upload')->down_file([
                                        'url' => $img,
                                        'attachment' => \Phpcmf\Service::M('Attachment')->get_attach_info(intval($field['setting']['option']['attachment'])),
                                    ]);
                                    if ($rt['code']) {
                                        $att = \Phpcmf\Service::M('Attachment')->save_data($rt['data'], 'ueditor_down_img');
                                        if ($att['code']) {
                                            // 归档成功
                                            $value = str_replace($img, $rt['data']['url'], $value);
                                            $img = $att['code'];
                                        }

                                    }
                                }
                            }

                        }

                    }
                }
                // 缩略图
                if ($slt && !\Phpcmf\Service::L('Field')->data[1]['thumb']) {
                    \Phpcmf\Service::L('Field')->data[1]['thumb'] = $img;
                }
            }
        }

        // 提取描述信息
        if (isset($_POST['data']['description']) && isset($_POST['is_auto_description']) && !$_POST['data']['description']) {
            \Phpcmf\Service::L('Field')->data[1]['description'] = trim(dr_strcut(dr_clearhtml($value), 200));
        }


        \Phpcmf\Service::L('Field')->data[$field['ismain']][$field['fieldname']] = htmlspecialchars($value);
    }

    /**
     * 字段输出
     *
     * @param	array	$value	数据库值
     * @return  string
     */
    public function output($value) {
        return htmlspecialchars_decode($value);
    }

    /**
     * 字段显示
     *
     * @return  string
     */
    public function show($field, $value = null) {
        $html = '
        <div class="portlet  bordered light">
        <div class="portlet-body">
        <div class="scroller" style="width:'.(\Phpcmf\Service::_is_mobile() ? '100%' : ($field['setting']['option']['width'] ? $field['setting']['option']['width'].(is_numeric($field['setting']['option']['width']) ? 'px' : '') : '100%')).';height:'.($field['setting']['option']['height'] ? $field['setting']['option']['height'] : '300').'px" data-always-visible="1" data-rail-visible="1">
        '.htmlspecialchars_decode($value).'                
        </div>
        </div>
        </div>';
        return $this->input_format($field['fieldname'], $field['name'], $html);
    }

    /**
     * 字段表单输入
     *
     */
    public function input($field, $value = '') {

        // 字段禁止修改时就返回显示字符串
        if ($this->_not_edit($field, $value)) {
            return $this->show($field, $value);
        }

        $is_mobile = \Phpcmf\Service::C()->_is_mobile();

        // 字段存储名称
        $name = $field['fieldname'];

        // 字段显示名称
        $text = ($field['setting']['validate']['required'] ? '<span class="required" aria-required="true"> * </span>' : '').$field['name'];

        // 表单宽度设置
        $width = $is_mobile ? '100%' : ($field['setting']['option']['width'] ? $field['setting']['option']['width'] : '100%');

        // 表单高度设置
        $height = $field['setting']['option']['height'] ? $field['setting']['option']['height'] : '300';

        // 字段提示信息
        $tips = $field['setting']['validate']['tips'] ? '<span class="help-block" id="dr_'.$name.'_tips">'.$field['setting']['validate']['tips'].'</span>' : '';

        // 字段默认值
        $value = htmlspecialchars_decode(strlen($value) ? $value : $this->get_default_value($field['setting']['option']['value']));

        // 输出
        $str = '';

        $is_mini = 0;

        $uri = \Phpcmf\Service::L('router')->uri();
        APP_DIR != 'member' && $uri = str_replace('member/', '', $uri);

        $pagebreak = (int)$field['setting']['option']['page'] ? ', \'pagebreak\'' : '';

        if ($is_mini) {
            // 防止重复加载JS
            if (!defined('PHPCMF_FIELD_UEDITOR_MINI')) {
                $str.= '
            <link href="/api/umeditor/themes/default/_css/umeditor.css" type="text/css" rel="stylesheet" />
            <script type="text/javascript">var ueditmini_url = "/";</script>
            <script type="text/javascript" src="/api/umeditor/editor_api.js"></script>
            <script type="text/javascript" src="/api/umeditor/umeditor.config.js"></script>
			<script type="text/javascript" src="'.LANG_PATH.'umeditor.js"></script>
			';
                define('PHPCMF_FIELD_UEDITOR_MINI', 1);
            }
            $tool = IS_ADMIN ? "'fullscreen', 'source', '|', " : ''; // 后台引用时显示html工具栏
            // 编辑器模式
            if (IS_ADMIN) {
                $mode = $field['setting']['option']['mode'];
            } else {
                if ($is_mobile) {
                    $mode = $field['setting']['option']['mode3'] ? $field['setting']['option']['mode3'] : $field['setting']['option']['mode'];
                    $field['setting']['option']['tool'] = $field['setting']['option']['tool3'] ? $field['setting']['option']['tool3'] : $field['setting']['option']['tool'];
                } else {
                    $mode = $field['setting']['option']['mode2'] ? $field['setting']['option']['mode2'] : $field['setting']['option']['mode'];
                    $field['setting']['option']['tool'] = $field['setting']['option']['tool2'] ? $field['setting']['option']['tool2'] : $field['setting']['option']['tool'];
                }

            }
            // 编辑器工具
            switch ($mode) {
                case 3: // 自定义
                    $tool.= $field['setting']['option']['tool'];
                    break;
                case 2: // 精简
                    $tool.= "'undo', 'redo', '|',
						'bold', 'italic', 'underline', 'strikethrough','|', 'pasteplain', 'forecolor', 'fontfamily', 'fontsize','|','link', 'unlink'$pagebreak";
                    break;
                case 1: // 默认
                    $tool.= "'source | undo redo | bold italic underline strikethrough | superscript subscript | forecolor backcolor | removeformat |',
            'insertorderedlist insertunorderedlist | selectall cleardoc paragraph | fontfamily fontsize' ,
            '| justifyleft justifycenter justifyright justifyjustify |',
            'link unlink | emotion image video  | map',
            '| horizontal print preview  fullscreen', 'drafts', 'formula'";
                    break;
            }
            $str.= "
		<script name=\"data[$name]\" type=\"text/plain\" id=\"dr_$name\">$value</script>
		<script type=\"text/javascript\">
		    var editorOption = {
				UMEDITOR_HOME_URL: \"/api/umeditor/\",
                imagePath:\"\" ,
				imageUrl:\"/index.php?s=api&c=file&m=umeditor&attachment=".intval($field['setting']['option']['attachment'])."&is_wm=".$field['setting']['option']['watermark']."&rid=".($uri.'/id:'.(int)$_GET['id'])."&\",
				lang: \"".SITE_LANGUAGE."\",
				toolbar: [
					$tool
				],
				initialContent:\"\",
				initialFrameWidth: \"".$width.(is_numeric($width) ? 'px' : '')."\",
				initialFrameHeight: \"{$height}\",
				initialStyle:\"body{font-size:14px}\",
				wordCount:false,
				elementPathEnabled:false,
				autoFloatEnabled:".($field['setting']['option']['autofloat'] ? 'true' : 'false').",
				autoHeightEnabled:".($field['setting']['option']['autoheight'] ? 'true' : 'false').",
				charset:\"utf-8\",
			};
            var um = UM.getEditor(\"dr_$name\", editorOption);
		</script>
		";
        } else {
            // 防止重复加载JS
            if (!defined('PHPCMF_FIELD_UEDITOR')) {
                $str.= '
			<script type="text/javascript" src="/api/ueditor/ueditor.config.js"></script>
			<script type="text/javascript" src="/api/ueditor/ueditor.all.js"></script>
			<script type="text/javascript" src="'.LANG_PATH.'ueditor.js"></script>
			';
                define('PHPCMF_FIELD_UEDITOR', 1);
            }

            $tool = IS_ADMIN ? "'fullscreen', 'source', '|', " : ''; // 后台引用时显示html工具栏

            // 编辑器模式
            if (IS_ADMIN) {
                $mode = $field['setting']['option']['mode'];
            } else {
                if ($is_mobile) {
                    $mode = $field['setting']['option']['mode3'] ? $field['setting']['option']['mode3'] : $field['setting']['option']['mode'];
                    $field['setting']['option']['tool'] = $field['setting']['option']['tool3'] ? $field['setting']['option']['tool3'] : $field['setting']['option']['tool'];
                } else {
                    $mode = $field['setting']['option']['mode2'] ? $field['setting']['option']['mode2'] : $field['setting']['option']['mode'];
                    $field['setting']['option']['tool'] = $field['setting']['option']['tool2'] ? $field['setting']['option']['tool2'] : $field['setting']['option']['tool'];
                }

            }
            // 编辑器工具
            switch ($mode) {
                case 3: // 自定义
                    $tool.= $field['setting']['option']['tool'];
                    break;
                case 2: // 精简
                    $tool.= "'undo', 'redo', '|',
						'bold', 'italic', 'underline', 'strikethrough','|', 'pasteplain', 'forecolor', 'fontfamily', 'fontsize','|', 'link', 'simpleupload'$pagebreak";
                    break;
                case 1: // 默认
                    $tool.= "'undo', 'redo', '|',
            'bold', 'italic', 'underline', 'fontborder', 'strikethrough', 'superscript', 'subscript', 'removeformat', 'formatmatch', 'autotypeset', 'blockquote', 'pasteplain', '|', 'forecolor', 'backcolor', 'insertorderedlist', 'insertunorderedlist', 'selectall', 'cleardoc', '|',
            'rowspacingtop', 'rowspacingbottom', 'lineheight', '|',
            'customstyle', 'paragraph', 'fontfamily', 'fontsize', '|',
            'directionalityltr', 'directionalityrtl', 'indent', '|',
            'justifyleft', 'justifycenter', 'justifyright', 'justifyjustify', '|', 'touppercase', 'tolowercase', '|',
            'link', 'unlink', 'anchor', '|', 'imagenone', 'imageleft', 'imageright', 'imagecenter', '|',
            'simpleupload', 'insertimage', 'emotion', 'scrawl', 'insertvideo', 'attachment', 'map', 'insertframe', 'insertcode', 'template', 'background', '|',
            'horizontal', 'date', 'time', 'spechars', 'snapscreen', 'wordimage', '|',
            'inserttable', 'deletetable', 'insertparagraphbeforetable', 'insertrow', 'deleterow', 'insertcol', 'deletecol', 'mergecells', 'mergeright', 'mergedown', 'splittocells', 'splittorows', 'splittocols', 'charts', '|',
            'print', 'preview', 'searchreplace', 'drafts'$pagebreak";
                    break;
            }

            $str.= "
		<script name=\"data[$name]\" type=\"text/plain\" id=\"dr_$name\">$value</script>
		<script type=\"text/javascript\">
			var editorOption = {
				UEDITOR_HOME_URL: \"/api/ueditor/\",
				serverUrl:\"/index.php?s=api&c=file&m=ueditor&attachment=".intval($field['setting']['option']['attachment'])."&is_wm=".$field['setting']['option']['watermark']."&rid=".($uri.'/id:'.(int)$_GET['id'])."&\",
				lang: \"".SITE_LANGUAGE."\",
				toolbars: [
					[ $tool ]
				],
				initialContent:\"\",
				initialFrameWidth: \"".$width.(is_numeric($width) ? 'px' : '')."\",
				initialFrameHeight: \"{$height}\",
				initialStyle:\"body{font-size:14px}\",
				wordCount:false,
				elementPathEnabled:false,
				autoFloatEnabled:".($field['setting']['option']['autofloat'] ? 'true' : 'false').",
				autoHeightEnabled:".($field['setting']['option']['autoheight'] ? 'true' : 'false').",
				charset:\"utf-8\",
			};
			var editor = new baidu.editor.ui.Editor(editorOption);
			editor.render(\"dr_$name\");
		</script>
		";
        }


        if ($field['setting']['option']['show_bottom_boot']) {

            $str.= '<div class="mt-checkbox-inline" style="margin-top: 10px;">';
            $str.= '     <label style="margin-bottom: 0;" class="mt-checkbox mt-checkbox-outline">
                  <input name="is_auto_thumb" type="checkbox" checked value="1"> 提取第一个图片为缩略图 <span></span>
                 </label>';
            $str.= '
                 <label style="margin-bottom: 0;" class="mt-checkbox mt-checkbox-outline">
                  <input name="is_auto_description" type="checkbox" checked value="1"> 提取前200字为描述信息 <span></span>
                 </label>';
            if (!$field['setting']['option']['down_img']) {
                $str.= '
                 <label style="margin-bottom: 0;" class="mt-checkbox mt-checkbox-outline">
                  <input name="is_auto_down_img" type="checkbox" checked value="1"> 下载远程图片 <span></span>
                 </label>';
            }
            $str.= '</div>';
        }


        return $this->input_format($name, $text, $str.$tips);
    }
}