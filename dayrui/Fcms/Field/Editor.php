<?php namespace Phpcmf\Field;
/**
 * {{www.xunruicms.com}}
 * {{迅睿内容管理框架系统}}
 * 本文件是框架系统文件，二次开发时不可以修改本文件，可以通过继承类方法来重写此文件
 **/

class Editor extends \Phpcmf\Library\A_Field {

    protected $rid; // 附件入库标记字符

    /**
     * 构造函数
     */
    public function __construct(...$params) {
        parent::__construct(...$params);
        $this->fieldtype = ['MEDIUMTEXT' => ''];
        $this->defaulttype = 'MEDIUMTEXT';
        $this->rid = md5(FC_NOW_URL.\Phpcmf\Service::L('input')->get_user_agent().\Phpcmf\Service::L('input')->ip_address().\Phpcmf\Service::C()->uid);
    }

    /**
     * 字段相关属性参数
     *
     * @param   array   $value  值
     * @return  string
     */
    public function option($option) {

        if (!isset($option['attach_size']) || !$option['attach_size']) {
            $option['attach_size'] = 200;
        }
        if (!isset($option['attach_ext']) || !$option['attach_ext']) {
            $option['attach_ext'] = 'zip,rar,txt,doc';
        }
        if (!isset($option['video_ext']) || !$option['video_ext']) {
            $option['video_ext'] = 'mp4';
        }
        if (!isset($option['video_size']) || !$option['video_size']) {
            $option['video_size'] = 500;
        }
        if (!isset($option['image_ext']) || !$option['image_ext']) {
            $option['image_ext'] = 'jpg,gif,png,webp,jpeg';
        }
        if (!isset($option['imagecut_ext']) || !$option['imagecut_ext']) {
            $option['imagecut_ext'] = '';
        }
        if (!isset($option['image_size']) || !$option['image_size']) {
            $option['image_size'] = 10;
        }

        $wm = \Phpcmf\Service::C()->get_cache('site', SITE_ID, 'watermark', 'ueditor') ? '<div class="form-group">
                    <label class="col-md-2 control-label">'.dr_lang('图片水印').'</label>
                    <div class="col-md-9">
                        <div class="form-control-static">
                            '.dr_lang('系统强制开启水印').'
                        </div>
                    </div>
                </div>' : '<div class="form-group">
                    <label class="col-md-2 control-label">'.dr_lang('图片水印').'</label>
                    <div class="col-md-9">
                        <div class="mt-radio-inline">
                            <label class="mt-radio mt-radio-outline"><input type="radio" value="1" name="data[setting][option][watermark]" '.($option['watermark'] == 1 ? 'checked' : '').' > '.dr_lang('开启').' <span></span></label>
                             &nbsp; &nbsp;
                             <label class="mt-radio mt-radio-outline"><input type="radio" value="0" name="data[setting][option][watermark]" '.($option['watermark'] == 0 ? 'checked' : '').' > '.dr_lang('关闭').' <span></span></label>
                        </div>
						<span class="help-block">'.dr_lang('上传的图片会加上水印图').'</span>
                    </div>
                </div>';

        return [$this->_search_field().'
                <div class="form-group">
                    <label class="col-md-2 control-label">'.dr_lang('底部工具栏').'</label>
                    <div class="col-md-9">
                        <div class="mt-radio-inline">
                            <label class="mt-radio mt-radio-outline"><input type="radio" onclick="$(\'#sdmrx\').show()" value="1" name="data[setting][option][show_bottom_boot]" '.($option['show_bottom_boot'] == 1 ? 'checked' : '').' > '.dr_lang('开启').' <span></span></label>
                             &nbsp; &nbsp;
                            <label class="mt-radio mt-radio-outline"><input type="radio" onclick="$(\'#sdmrx\').hide()" value="0" name="data[setting][option][show_bottom_boot]" '.($option['show_bottom_boot'] == 0 ? 'checked' : '').' > '.dr_lang('关闭').' <span></span></label>
                        </div>
						<span class="help-block">'.dr_lang('编辑器底部工具栏，有截取字符选择、提取缩略图、下载远程图等控制按钮').'</span>
                    </div>
                </div>
                <div class="form-group" id="sdmrx" '.(!$option['show_bottom_boot'] ? 'style="display:none"' : '').'>
                    <label class="col-md-1 control-label"> &nbsp; &nbsp;</label>
                    <div class="col-md-9">
                        <div class="form-group">
                            <label class="col-md-2 control-label">'.dr_lang("提取描述").'</label>
                            <div class="col-md-9">
                                <input type="checkbox" name="data[setting][option][tool_select_2]" value="1" '.($option['tool_select_2'] ? 'checked' : '').' data-on-text="'.dr_lang("默认选中").'" data-off-text="'.dr_lang("默认不选").'" data-on-color="success" data-off-color="danger" class="make-switch" data-size="small">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-md-2 control-label">'.dr_lang("提取缩略图").'</label>
                            <div class="col-md-9">
                                <input type="checkbox" name="data[setting][option][tool_select_1]" value="1" '.($option['tool_select_1'] ? 'checked' : '').' data-on-text="'.dr_lang("默认选中").'" data-off-text="'.dr_lang("默认不选").'" data-on-color="success" data-off-color="danger" class="make-switch" data-size="small">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-md-2 control-label">'.dr_lang("去除站外链接").'</label>
                            <div class="col-md-9">
                                <input type="checkbox" name="data[setting][option][tool_select_4]" value="1" '.($option['tool_select_4'] ? 'checked' : '').' data-on-text="'.dr_lang("默认选中").'" data-off-text="'.dr_lang("默认不选").'" data-on-color="success" data-off-color="danger" class="make-switch" data-size="small">                             
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-md-2 control-label">'.dr_lang("下载远程图").'</label>
                            <div class="col-md-9">
                                <input type="checkbox" name="data[setting][option][tool_select_3]" value="1" '.($option['tool_select_3'] ? 'checked' : '').' data-on-text="'.dr_lang("启用").'" data-off-text="'.dr_lang("不启用").'" data-on-color="success" data-off-color="danger" class="make-switch" data-size="small">                             
                            </div>
                        </div>
                    </div>
                </div>
                <hr>
                <div class="form-group">
                    <label class="col-md-2 control-label">'.dr_lang('图片title').'</label>
                    <div class="col-md-9">
                        <div class="mt-radio-inline">
                            <label class="mt-radio mt-radio-outline"><input type="radio" value="1" name="data[setting][option][imgtitle]" '.($option['imgtitle'] >0 ? 'checked' : '').' > '.dr_lang('内容标题').' <span></span></label>
                            <label class="mt-radio mt-radio-outline"><input type="radio" value="0" name="data[setting][option][imgtitle]" '.(!$option['imgtitle'] ? 'checked' : '').' > '.dr_lang('图片名称').' <span></span></label>
                            <label class="mt-radio mt-radio-outline"><input type="radio" value="-1" name="data[setting][option][imgtitle]" '.($option['imgtitle'] < 0 ? 'checked' : '').' > '.dr_lang('不显示').' <span></span></label>
                        </div>
						<span class="help-block">'.dr_lang('将模块内容的标题作为图片title字符').'</span>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="col-md-2 control-label">'.dr_lang('图片alt').'</label>
                    <div class="col-md-9">
                        <div class="mt-radio-inline">
                            <label class="mt-radio mt-radio-outline"><input type="radio" value="1" name="data[setting][option][imgalt]" '.($option['imgalt'] >0 ? 'checked' : '').' > '.dr_lang('内容标题').' <span></span></label>
                            <label class="mt-radio mt-radio-outline"><input type="radio" value="0" name="data[setting][option][imgalt]" '.(!$option['imgalt'] ? 'checked' : '').' > '.dr_lang('图片名称').' <span></span></label>
                            <label class="mt-radio mt-radio-outline"><input type="radio" value="-1" name="data[setting][option][imgalt]" '.($option['imgalt'] < 0 ? 'checked' : '').' > '.dr_lang('不显示').' <span></span></label>
                        </div>
						<span class="help-block">'.dr_lang('将模块内容的标题作为图片alt字符').'</span>
                    </div>
                </div>
				'.$wm.
            '
                  <div class="form-group">
                    <label class="col-md-2 control-label">'.dr_lang('图片粘贴扩展名').'</label>
                    <div class="col-md-9">
                        <label><input type="text" class="form-control" name="data[setting][option][imagecut_ext]" value="'.$option['imagecut_ext'].'"></label>
                        <span class="help-block">'.dr_lang('填写用于图片的扩展名格式，只能填写单个扩展名，用于截图粘贴时储存的扩展名').'</span>
                    </div>
                </div>
                  <div class="form-group">
                    <label class="col-md-2 control-label">'.dr_lang('图片上传扩展名').'</label>
                    <div class="col-md-9">
                        <label><input type="text" class="form-control" name="data[setting][option][image_ext]" value="'.$option['image_ext'].'"></label>
                        <span class="help-block">'.dr_lang('填写用于图片上传的扩展名格式，格式：jpg,gif,png,webp,jpeg').'</span>
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-md-2 control-label">'.dr_lang('图片上传大小').'</label>
                    <div class="col-md-9">
                        <label><input type="text" class="form-control" name="data[setting][option][image_size]" value="'.$option['image_size'].'"></label>
                        <span class="help-block">'.dr_lang('填写用于图片上传的最大允许上传的大小，单位MB').'</span>
                    </div>
                </div>
            <hr>
                <div class="form-group">
                    <label class="col-md-2 control-label">'.dr_lang('附件上传大小').'</label>
                    <div class="col-md-9">
                        <label><input type="text" class="form-control" name="data[setting][option][attach_size]" value="'.$option['attach_size'].'"></label>
                        <span class="help-block">'.dr_lang('填写用于附件上传的最大允许上传的大小，单位MB').'</span>
                    </div>
                </div>
            
                <div class="form-group">
                    <label class="col-md-2 control-label">'.dr_lang('附件上传扩展名').'</label>
                    <div class="col-md-9">
                        <label><input type="text" class="form-control" name="data[setting][option][attach_ext]" value="'.$option['attach_ext'].'"></label>
                        <span class="help-block">'.dr_lang('填写用于附件上传的扩展名，格式：rar,zip').'</span>
                    </div>
                </div>
            <hr>
             <div class="form-group">
                    <label class="col-md-2 control-label">'.dr_lang('视频上传扩展名').'</label>
                    <div class="col-md-9">
                        <label><input type="text" class="form-control" name="data[setting][option][video_ext]" value="'.$option['video_ext'].'"></label>
                        <span class="help-block">'.dr_lang('填写用于视频上传的扩展名格式，格式：mp4,mov').'</span>
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-md-2 control-label">'.dr_lang('视频上传大小').'</label>
                    <div class="col-md-9">
                        <label><input type="text" class="form-control" name="data[setting][option][video_size]" value="'.$option['video_size'].'"></label>
                        <span class="help-block">'.dr_lang('填写用于视频上传的最大允许上传的大小，单位MB').'</span>
                    </div>
                </div>
                <hr>
                '.$this->attachment($option, 0).'
                
                <div class="form-group">
                    <label class="col-md-2 control-label">'.dr_lang('默认存储值').'</label>
                    <div class="col-md-9">
					<textarea id="field_default_value" style="width: 90%;height: 100px;" class="form-control" name="data[setting][option][value]">'.$option['value'].'</textarea>
					<p><label>'.$this->member_field_select().'</label>
					<span class="help-block">'.dr_lang('用于字段为空时显示该填充值，并不会去主动变更数据库中的实际值；可以设置会员表字段，表示用当前登录会员信息来填充这个值').'</span></p>
                    </div>
                </div>
                '.$this->field_type($option['fieldtype'], $option['fieldlength']),

            '<div class="form-group">
                    <label class="col-md-2 control-label">'.dr_lang('控件宽度').'</label>
                    <div class="col-md-9">
                        <label><input type="text" class="form-control" name="data[setting][option][width]" value="'.$option['width'].'"></label>
                        <span class="help-block">'.dr_lang('[整数]表示固定宽度；[整数%]表示百分比').'</span>
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

        $value = str_replace('style=""', '', (string)$_POST['data'][$field['fieldname']]);
        $value = preg_replace("/cmsattachid=\"([0-9]+)\"/U", '', $value);
        // 第一张作为缩略图
        $slt = isset($_POST['data']['thumb']) && isset($_POST['is_auto_thumb_'.$field['fieldname']]) && !$_POST['data']['thumb'] && $_POST['is_auto_thumb_'.$field['fieldname']];


        $base64 = strpos($value, ';base64,');

        // 下载远程图片
        if ($slt || $base64) {
            $temp = preg_replace('/<pre(.*)<\/pre>/siU', '', $value);
            $temp = preg_replace('/<code(.*)<\/code>/siU', '', $temp);
            if (preg_match_all("/(src)=([\"|']?)([^ \"'>]+)\\2/i", $temp, $imgs)) {
                $reps = array_unique($imgs[3]);
                usort($reps, function ($a, $b) {
                    return dr_strlen($b) - dr_strlen($a);
                });
                foreach ($reps as $img) {

                    if ($base64 && preg_match('/^(data:\s*image\/(\w+);base64,)/i', $img, $result)) {
                        // 处理图片
                        $ext = strtolower($result[2]);
                        if (!in_array($ext, ['png', 'jpg', 'jpeg', 'gif', 'webp'])) {
                            continue;
                        }
                        $content = base64_decode(str_replace($result[1], '', $img));
                        if (strlen($content) > 30000000) {
                            continue;
                        }
                        $rt = \Phpcmf\Service::L('upload')->base64_image([
                            'ext' => isset($field['setting']['option']['imagecut_ext']) && $field['setting']['option']['imagecut_ext'] ? $field['setting']['option']['imagecut_ext'] : $ext,
                            'content' => $content,
                            'watermark' => \Phpcmf\Service::C()->get_cache('site', SITE_ID, 'watermark', 'ueditor') || $field['setting']['option']['watermark'] ? 1 : 0,
                            'attachment' => \Phpcmf\Service::M('Attachment')->get_attach_info(intval($field['setting']['option']['attachment']), $field['setting']['option']['image_reduce']),
                        ]);
                        if (!$rt['code']) {
                           continue;
                        }
                        $att = \Phpcmf\Service::M('Attachment')->save_data($rt['data'], 'ueditor:'.$this->rid);
                        if ($att['code']) {
                            // 归档成功
                            $value = str_replace($img, $rt['data']['url'], $value);
                            $img = $att['code'];
                            // 标记附件
                            \Phpcmf\Service::M('Attachment')->save_ueditor_aid($this->rid, $att['code']);
                        }
                    }
                    // 缩略图
                    if ($img && $slt) {
                        $_field = \Phpcmf\Service::L('form')->fields;
                        if (isset($_field['thumb']) && $_field['thumb']['fieldtype'] == 'File' && !\Phpcmf\Service::L('Field')->data[$_field['thumb']['ismain']]['thumb']) {
                            if (!is_numeric($img)) {
                                // 下载缩略图
                                // 判断域名白名单
                                $arr = parse_url($img);
                                $domain = $arr['host'];
                                if ($domain) {
                                    $sites = \Phpcmf\Service::R(WRITEPATH.'config/domain_site.php');
                                    if (isset($sites[$domain])) {
                                        // 过滤站点域名
                                    } elseif (strpos(SYS_UPLOAD_URL, $domain) !== false) {
                                        // 过滤附件白名单
                                    } else {
                                        $file = dr_catcher_data($img, 8);
                                        if (!$file) {
                                            CI_DEBUG && log_message('debug', '服务器无法下载图片：'.$img);
                                        } else {
                                            // 尝试找一找附件库
                                            $att = \Phpcmf\Service::M()->table('attachment')->like('related', 'ueditor')->where('filemd5', md5($file))->getRow();
                                            if ($att) {
                                                $img = $att['id'];
                                            } else {
                                                // 下载归档
                                                $rt = \Phpcmf\Service::L('upload')->down_file([
                                                    'url' => html_entity_decode((string)$img),
                                                    'timeout' => 5,
                                                    'watermark' => \Phpcmf\Service::C()->get_cache('site', SITE_ID, 'watermark', 'ueditor') || $field['setting']['option']['watermark'] ? 1 : 0,
                                                    'attachment' => \Phpcmf\Service::M('Attachment')->get_attach_info(intval($field['setting']['option']['attachment']), $field['setting']['option']['image_reduce']),
                                                    'file_ext' => $ext,
                                                    'file_content' => $file,
                                                ]);
                                                if ($rt['code']) {
                                                    $att = \Phpcmf\Service::M('Attachment')->save_data($rt['data'], 'ueditor:'.$this->rid);
                                                    if ($att['code']) {
                                                        // 归档成功
                                                        $value = str_replace($img, $rt['data']['url'], $value);
                                                        $img = $att['code'];
                                                        // 标记附件
                                                        \Phpcmf\Service::M('Attachment')->save_ueditor_aid($this->rid, $att['code']);
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                            \Phpcmf\Service::L('Field')->data[$_field['thumb']['ismain']]['thumb'] = $_POST['data']['thumb'] = $img;
                        }
                    }
                }
            }
        }

        // 去除站外链接
        if (isset($_POST['is_remove_a_'.$field['fieldname']]) && $_POST['is_remove_a_'.$field['fieldname']]
            && preg_match_all("/<a (.*)href=(.+)>(.*)<\/a>/Ui", $value, $arrs)) {
            $sites = \Phpcmf\Service::R(WRITEPATH.'config/domain_site.php');
            foreach ($arrs[2] as $i => $a) {
                if (strpos($a, ' ') !== false) {
                    list($a) = explode(' ', $a);
                }
                $a = trim($a, '"');
                $a = trim($a, '\'');
                $arr = parse_url($a);
                if ($arr && $arr['host'] && !isset($sites[$arr['host']])) {
                    // 去除a标签
                    $value = str_replace($arrs[0][$i], $arrs[3][$i], $value);
                }
            }
        }

        // 提取描述信息
        if (isset($_POST['data']['description']) && isset($_POST['is_auto_description_'.$field['fieldname']])
            && $_POST['is_auto_description_'.$field['fieldname']]) {
            \Phpcmf\Service::L('Field')->data[1]['description'] = $_POST['data']['description'] = dr_get_description($value);
        }

        // 替换分页
        $value = str_replace('_ueditor_page_break_tag_', '<hr class="pagebreak">', $value);
        $value = str_replace(' style=""', '', $value);
        if (isset($field['setting']['validate']['xss']) && $field['setting']['validate']['xss']) {
            // 开启xss
            $value = \Phpcmf\Service::L('Security')->xss_clean($value);
        }
        // 入库操作
        \Phpcmf\Service::L('Field')->data[$field['ismain']][$field['fieldname']] = htmlspecialchars($value);
    }

    /**
     * 获取附件id
     */
    public function get_attach_id($value) {
        return \Phpcmf\Service::M('Attachment')->get_ueditor_aid($this->rid);
    }

    /**
     * 附件处理
     */
    public function attach($data, $_data) {

        $add = \Phpcmf\Service::M('Attachment')->get_ueditor_aid($this->rid, true);
        if (isset($_POST['data'][$this->field['fieldname']]) && $_POST['data'][$this->field['fieldname']]
            && preg_match_all("/<([a-z]+) cmsattachid=\"([0-9]+)\"/Ui", $_POST['data'][$this->field['fieldname']], $att)) {
            if ($add && is_array($add)) {
                $add = array_merge($add, $att[2]);
            } else {
                $add = $att[2];
            }
        }

        return [$add, NULL];
    }

    /**
     * 字段输出
     *
     * @param   array   $value  数据库值
     * @return  string
     */
    public function output($value) {
        return dr_ueditor_html($value, isset(\Phpcmf\Service::L('Field')->data['title']) ? \Phpcmf\Service::L('Field')->data['title'] : '');
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
        <div class="scroller" style="width:'.(\Phpcmf\Service::IS_MOBILE_USER() ? '100%' : ($field['setting']['option']['width'] ? $field['setting']['option']['width'].(is_numeric($field['setting']['option']['width']) ? 'px' : '') : '100%')).';height:'.($field['setting']['option']['height'] ? $field['setting']['option']['height'] : '300').'px" data-always-visible="1" data-rail-visible="1">
        '.htmlspecialchars_decode((string)$value).'                
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

        // 字段存储名称
        $name = $field['fieldname'];

        // 字段显示名称
        $text = ($field['setting']['validate']['required'] ? '<span class="required" aria-required="true"> * </span>' : '').dr_lang($field['name']);

        // 表单宽度设置
        $width = \Phpcmf\Service::IS_MOBILE_USER() ? '100%' : ($field['setting']['option']['width'] ? $field['setting']['option']['width'] : '100%');

        // 表单高度设置
        $height = $field['setting']['option']['height'] ? $field['setting']['option']['height'] : '300';

        // 字段提示信息
        $tips = $field['setting']['validate']['tips'] ? '<span class="help-block" id="dr_'.$name.'_tips">'.$field['setting']['validate']['tips'].'</span>' : '';

        // 字段默认值
        $value = dr_strlen($value) ? $value : $this->get_default_value($field['setting']['option']['value']);

        // 输出
        $str = '';

        // 防止重复加载JS
        if (!$this->is_load_js($field['fieldtype'])) {
            $str.= '
            <link href="'.THEME_PATH.'assets/editor/summernote.css?v='.CMF_UPDATE_TIME.'" rel="stylesheet">
            <script type="text/javascript" src="'.THEME_PATH.'assets/editor/summernote'.(IS_XRDEV ? '' : '.min').'.js?v='.CMF_UPDATE_TIME.'"></script>
            ';
            $this->set_load_js($field['fieldtype'], 1);
        }
        if (!isset($field['setting']['option']['attach_size']) || !$field['setting']['option']['attach_size']) {
            $option['attach_size'] = 200;
        }

        $p = dr_authcode([
            'size' => (!isset($field['setting']['option']['video_size']) || !$field['setting']['option']['video_size']) ? 500 : $field['setting']['option']['video_size'],
            'exts' => (!isset($field['setting']['option']['video_ext']) || !$field['setting']['option']['video_ext']) ? 'mp4' : $field['setting']['option']['video_ext'],
            'count' => 100,
            'attachment' => $field['setting']['option']['attachment'],
        ], 'ENCODE');
        $p2 = dr_authcode([
            'size' => (!isset($field['setting']['option']['image_size']) || !$field['setting']['option']['image_size']) ? 10 : $field['setting']['option']['image_size'],
            'exts' => (!isset($field['setting']['option']['image_ext']) || !$field['setting']['option']['image_ext']) ? 'jpg,gif,png,webp,jpeg' : $field['setting']['option']['image_ext'],
            'count' => 100,
            'attachment' => $field['setting']['option']['attachment'],
            'image_reduce' => $field['setting']['option']['image_reduce'],
        ], 'ENCODE');
        $p3 = dr_authcode([
            'size' => (!isset($field['setting']['option']['attach_size']) || !$field['setting']['option']['attach_size']) ? 200 : $field['setting']['option']['attach_size'],
            'exts' => (!isset($field['setting']['option']['attach_ext']) || !$field['setting']['option']['attach_ext']) ? 'zip,rar,txt,doc' : $field['setting']['option']['attach_ext'],
            'count' => 100,
            'attachment' => $field['setting']['option']['attachment'],
        ], 'ENCODE');
        $str.= "<textarea class=\"dr_ueditor\" name=\"data[$name]\" id=\"dr_$name\">$value</textarea>";

        if ($field['setting']['option']['imgtitle'] > 0) {
            $title = UEDITOR_IMG_TITLE;
        } elseif ($field['setting']['option']['imgtitle'] < 0) {
            $title = 'none';
        } else {
            $title = '';
        }
        if ($field['setting']['option']['imgalt'] > 0) {
            $alt = UEDITOR_IMG_TITLE;
        } elseif ($field['setting']['option']['imgalt'] < 0) {
            $alt = 'none';
        } else {
            $alt = '';
        }
        $wm = \Phpcmf\Service::C()->get_cache('site', SITE_ID, 'watermark', 'ueditor') || $field['setting']['option']['watermark'] ? 1 : 0;
        $str.= \Phpcmf\Service::L('js_packer')->pack("
        <script type=\"text/javascript\">
        function dr_is_auto_description_".$field['fieldname']."() {
            var v = $(\"#is_auto_description_".$field['fieldname']."\").is(\":checked\");
            if (v == true) {
                $(\"#dr_description\").prop(\"readonly\", true);
            } else {
                $(\"#dr_description\").prop(\"readonly\", false);
            }
        }
            $(function(){
            dr_is_auto_description_".$field['fieldname']."();
                $('#dr_".$name."').summernote({
                isMobileWidth: '".(\Phpcmf\Service::IS_MOBILE_USER() ? '95%' : '80%')."',
                llVideoUrl: '".dr_web_prefix(''.(IS_ADMIN ? SELF.'?c=api' : 'index.php?s=api&c=file').'&m=input_file_list&is_iframe=1&p=' . $p)."',
                llImageUrl: '".dr_web_prefix(''.(IS_ADMIN ? SELF.'?c=api' : 'index.php?s=api&c=file').'&m=input_file_list&is_iframe=1&p=' . $p2."&is_wm=".$wm)."',
                attachUrl: '".dr_web_prefix(''.(IS_ADMIN ? SELF.'?c=api' : 'index.php?s=api&c=file').'&m=input_file_list&is_iframe=1&p=' . $p3)."',
                isImageTitle:'".$title."',
                isImageAlt:'".$alt."',
                height:'".$height."',
                width:'".$width."'});
            });
            function dr_editor_down_img_".$field['fieldname']."(){
var index = layer.load(2, {
    shade: [0.3,'#fff'], //0.1透明度的白色背景
    time: 100000000
});
$.ajax({
    type: 'POST',
    url: '".dr_web_prefix(''.(IS_ADMIN ? SELF.'?c=api' : 'index.php?s=api&c=file').'&m=down_img&is_iframe=1&token='.dr_get_csrf_token().'&rid='.$this->rid.'&p=' . $p2."&is_wm=".$wm)."',
    dataType: 'json',
    data: { value: $('#dr_".$field['fieldname']."').summernote('code') },
    success: function (json) {
        layer.close(index);
        if (json.code == 0) {
            dr_cmf_tips(0, json.msg, json.data.time);
        } else {
            
            var width = '500px';
            var height = '70%';
        
            if (is_mobile_cms == 1) {
                width = '95%';
                height = '90%';
            }
        
            layer.open({
                type: 2,
                title: '',
                fix:true,
                scrollbar: false,
                maxmin: false,
                resize: true,
                shadeClose: true,
                shade: 0,
                area: [width, height],
                btn: [dr_lang('确定'), dr_lang('取消')],
                yes: function(index, layero){
                    // 延迟加载
                    var loading = layer.load(2, {
                        shade: [0.3,'#fff'], //0.1透明度的白色背景
                        time: 100000000
                    });
                    var body = layer.getChildFrame('body', index);
                    $.ajax({type: 'POST',dataType:'json', url: json.msg, data: $(body).find('#myform').serialize(),
                        success: function(json) {
                            layer.close(loading);
                            if (json.code) {
                                layer.close(index);
                                 $('#dr_".$field['fieldname']."').summernote('reset');
                                 $('#dr_".$field['fieldname']."').summernote('pasteHTML', json.data);
                                dr_cmf_tips(1, json.msg);
                            } else {
                                dr_cmf_tips(0, json.msg, json.data.time);
                            }
                            return false;
                        },
                        error: function(HttpRequest, ajaxOptions, thrownError) {
                            dr_ajax_alert_error(HttpRequest, this, thrownError);
                        }
                    });
                    return false;
                },
                success: function(layero, index){
                    // 主要用于后台权限验证
                    var body = layer.getChildFrame('body', index);
                    var json = $(body).html();
                    if (json.indexOf('\"code\":0') > 0 && json.length < 500){
                        var obj = JSON.parse(json);
                        layer.close(index);
                        dr_cmf_tips(0, obj.msg);
                    }
                },
                content: json.msg+'&is_iframe=1'
            });
            
            
        }
    },
    error: function(HttpRequest, ajaxOptions, thrownError) {
        dr_ajax_alert_error(HttpRequest, this, thrownError);
    }
});
            }
        </script>
        ", 0);


        if (isset($field['setting']['option']['show_bottom_boot']) && $field['setting']['option']['show_bottom_boot']) {
            $str.= '<div class="mt-checkbox-inline" style="margin-top: 10px;">';
            $str.= '     <label style="margin-bottom: 0;" class="mt-checkbox mt-checkbox-outline">
                  <input name="is_auto_thumb_'.$field['fieldname'].'" type="checkbox" '.($field['setting']['option']['tool_select_1'] ? 'checked' : '').' value="1"> '.dr_lang('提取第一个图片为缩略图').' <span></span>
                 </label>';
            $str.= '
                 <label style="margin-bottom: 0;" class="mt-checkbox mt-checkbox-outline">
                  <input id="is_auto_description_'.$field['fieldname'].'" onclick="dr_is_auto_description_'.$field['fieldname'].'()" name="is_auto_description_'.$field['fieldname'].'" type="checkbox" '.($field['setting']['option']['tool_select_2'] ? 'checked' : '').' value="1"> '.dr_lang('提取内容作为描述信息').' <span></span>
                 </label>';
            $str.= '
                 <label style="margin-bottom: 0;" class="mt-checkbox mt-checkbox-outline">
                  <input name="is_remove_a_'.$field['fieldname'].'" type="checkbox" '.($field['setting']['option']['tool_select_4'] ? 'checked' : '').' value="1"> '.dr_lang('去除站外链接').' <span></span>
                 </label>';
            if ($field['setting']['option']['tool_select_3']) {
                $str.= '
                 <label style="margin-bottom: 0;" class="mt-checkbox mt-checkbox-outline">
                  <a class="btn blue btn-xs" onclick="dr_editor_down_img_'.$field['fieldname'].'()"> '.dr_lang('一键下载远程图片').' </a>
                 </label>';
            }

            $str.= '</div>';
        }


        return $this->input_format($name, $text, $str.$tips);
    }
}