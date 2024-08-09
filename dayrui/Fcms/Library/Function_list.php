<?php namespace Phpcmf\Library;
/**
 * {{www.xunruicms.com}}
 * {{迅睿内容管理框架系统}}
 * 本文件是框架系统文件，二次开发时不可以修改本文件，可以通过继承类方法来重写此文件
 **/

/**
 * 列表格式化函数库
 */

class Function_list {

    protected $select_js;

    protected $uid_data = [];
    protected $cid_data = [];

    // 用于列表显示栏目
    public function catid($catid, $param = [], $data = [], $field = []) {

        if (!$catid) {
            return '';
        }

        $mid = isset(\Phpcmf\Service::C()->module['mid']) ? \Phpcmf\Service::C()->module['mid'] : (defined('MOD_DIR') ? MOD_DIR : '');
        $url = IS_ADMIN ? \Phpcmf\Service::L('router')->url(APP_DIR.'/'.$_GET['c'].'/index', ['catid' => $catid]) : dr_url_prefix(dr_cat_value($mid, $catid, 'url'), $mid).'" target="_blank';
        $value = dr_cat_value($mid, $catid, 'name');

        return '<a href="'.$url.'">'.$value.'</a>';
    }

    // 用于列表显示副栏目
    public function catids($value, $param = [], $data = [], $field = []) {

        if (!$value) {
            return '';
        }

        $rt = [];
        $arr = dr_string2array($value);
        $mid = isset(\Phpcmf\Service::C()->module['mid']) ? \Phpcmf\Service::C()->module['mid'] : (defined('MOD_DIR') ? MOD_DIR : '');
        if ($arr) {
            foreach ($arr as $catid) {
                $url = IS_ADMIN ? \Phpcmf\Service::L('router')->url(APP_DIR.'/'.$_GET['c'].'/index', ['catid' => $catid]) : dr_url_prefix(dr_cat_value($mid, $catid, 'url'), $mid).'" target="_blank';
                $value = dr_cat_value($mid, $catid, 'name');
                $rt[] = '<a href="'.$url.'">'.$value.'</a>';
            }
        }

        return $rt ? implode('&nbsp;', $rt) : '';
    }

    // 用于列表显示内容
    public function comment($value, $param = [], $data = [], $field = []) {

        if (!$value) {
            return '';
        }

        return $this->content($value, $param, $data);
    }

    // 用于列表显示内容
    public function content($value, $param = [], $data = [], $field = []) {

        if (!$value) {
            return '';
        }

        $mid = defined('MOD_DIR') ? MOD_DIR : '';
        $value = htmlspecialchars(dr_clearhtml($value));
        $title = dr_keyword_highlight($value, $param['keyword']);
        !$title && $title = '...';

        return isset($data['url']) && $data['url'] ? '<a href="'.dr_url_prefix($data['url'], $mid).'" target="_blank" class="tooltips" data-container="body" data-placement="top" data-original-title="'.$value.'" title="'.$value.'">'.$title.'</a>' : $title;
    }

    // 用于列表显示联动菜单值
    public function linkage_address($value, $param = [], $data = [], $field = []) {

        if (!$value) {
            return '';
        }

        return dr_linkagepos('address', $value, '-');
    }

    // 用于列表显示状态
    public function status($value, $param = [], $data = [], $field = []) {

        if (!$value) {
            $html = '<span class="label label-sm label-danger">'.dr_lang('待审核');
        } elseif ($value == 1) {
            $html = '<span class="label label-sm label-success">'.dr_lang('已通过');
        } else {
            $html = '<span class="label label-sm label-warning">'.dr_lang('未通过') ;
        }

        return '<label>'.$html.'</span></label>';
    }

    // 用于列表显示标题
    public function title($value, $param = [], $data = [], $field = []) {

        if (!$value) {
            return '';
        }

        $mid = defined('MOD_DIR') ? MOD_DIR : '';
        $value = htmlspecialchars(dr_clearhtml($value));
        $title = ($data['thumb'] ? '<i class="fa fa-photo"></i> ' : '').dr_keyword_highlight($value, $param['keyword']);
        !$title && $title = '...';

        return isset($data['url']) && $data['url'] ? ('<a href="'.dr_url_prefix($data['url'], $mid).'" target="_blank" class="tooltips" data-container="body" data-placement="top" data-original-title="'.$value.'" title="'.$value.'">'.$title.'</a>'.($data['link_id'] > 0 ? '  <i class="fa fa-link font-green" title="'.dr_lang('同步链接').'"></i>' : '')) : $title;
    }

    // 用于列表显示时间日期格式
    public function datetime($value, $param = [], $data = [], $field = []) {

        if (!$value) {
            return '';
        }

        return dr_date($value, null, 'red');
    }

    // 用于列表显示日期格式
    public function date($value, $param = [], $data = [], $field = []) {

        if (!$value) {
            return '';
        }

        return dr_date($value, 'Y-m-d', 'red');
    }

    // 用于列表显示作者
    public function author($value, $param = [], $data = [], $field = []) {

        if ($value == 'guest') {
            return dr_lang('游客');
        } elseif ((isset($data['username']) || isset($data['author'])) && $data['uid']) {
            // 模块需要重新查询名字
            $member = $this->uid_data[$data['uid']] = isset($this->uid_data[$data['uid']]) && $this->uid_data[$data['uid']] ? $this->uid_data[$data['uid']] : \Phpcmf\Service::M('member')->username($data['uid']);
            if (!$value) {
                $value = $member;
            }
        } else {
            $member = $value;
        }

        return $value ? '<a class="fc_member_show" href="javascript:;" uid="'.intval($data['uid']).'" member="'.dr_htmlspecialchars($member).'">'.dr_htmlspecialchars($value).'</a>' : dr_lang('游客');
    }

    // 用于列表显示作者
    public function uid($uid, $param = [], $data = [], $field = []) {

        // 查询username
        if (strlen($uid) > 12) {
            return dr_lang('游客');
        }

        $this->uid_data[$uid] = isset($this->uid_data[$uid]) && $this->uid_data[$uid] ? $this->uid_data[$uid] : \Phpcmf\Service::M('member')->username($uid);

        return $this->uid_data[$uid] ? '<a class="fc_member_show" href="javascript:;" uid="'.intval($uid).'" member="'.dr_htmlspecialchars($this->uid_data[$uid]).'">'.dr_htmlspecialchars($this->uid_data[$uid]).'</a>' : dr_lang('游客');
    }

    // 头像
    public function avatar_uid($value, $param = [], $data = [], $field = []) {

        $uid = isset($data['uid']) ? $data['uid'] : 0;

        return '<a class="fc_member_show" href="javascript:;" uid="'.$uid.'"><img class="img-circle" src="'.dr_avatar($uid).'" style="width:30px;height:30px"></a>';
    }

    // 头像
    public function avatar($value, $param = [], $data = [], $field = []) {
        return '<a class="fc_member_show" href="javascript:;" uid="'.$value.'"><img class="img-circle" src="'.dr_avatar($value).'" style="width:30px;height:30px"></a>';
    }

    // 用于列表关联主题
    public function ctitle($cid, $param = [], $data = [], $field = []) {

        // 查询username
        if (!$cid) {
            return dr_lang('未关联');
        }

        $mid = defined('MOD_DIR') ? MOD_DIR : '';
        $this->cid_data[$cid] = isset($this->cid_data[$cid]) && $this->cid_data[$cid] ? $this->cid_data[$cid] : \Phpcmf\Service::M()->table_site($mid)->get($cid);

        return $this->cid_data[$cid] ? $this->title($this->cid_data[$cid]['title'], $param, $this->cid_data[$cid]) : dr_lang('关联主题不存在');
    }

    // 用于列表显示ip地址
    public function ip($value, $param = [], $data = [], $field = []) {

        if ($value) {
            list($value) = explode('-', $value);
            return '<a href="https://www.baidu.com/s?wd='.$value.'&action=xunruicms" target="_blank">'.\Phpcmf\Service::L('ip')->address($value).'</a>';
        }

        return dr_lang( '无');
    }

    // url链接输出
    public function url($value, $param = [], $data = [], $field = []) {
        return '<a href="'.$value.'" target="_blank">'.$value.'</a>';
    }

    // 用于列表显示单个图片
    public function img($value, $param = [], $data = []) {

        if ($value) {
            $file = \Phpcmf\Service::C()->get_attachment($value);
            if ($file) {
                $value = $file['url'];
            }
            $url = 'javascript:dr_preview_image(\''.dr_file($value).'\');';
            return '<a class="thumbnail" style="display: inherit;" href="'.$url.'"><img style="width:30px" src="'.dr_thumb($value, 100, 100).'"></a>';
        }

        return dr_lang('无');
    }

    // 用于列表显示图片专用
    public function image($value, $param = [], $data = []) {

        if ($value) {
            $rt = [];
            $arr = dr_get_files($value);
            foreach ($arr as $t) {
                $file = \Phpcmf\Service::C()->get_attachment($t);
                if ($file) {
                    $value = $file['url'];
                } else {
                    $value = $t;
                }
                $url = 'javascript:dr_preview_image(\''.$value.'\');';
                $rt[] = '<a class="thumbnail" style="display: inherit;" href="'.$url.'"><img style="width:30px" src="'.dr_thumb($value, 100, 100).'"></a>';
            }
            return implode('', $rt);
        }

        return dr_lang('无');
    }

    // 用于列表显示多文件
    public function files($value, $param = [], $data = [], $field = []) {

        if ($value) {
            $rt = [];
            $arr = dr_get_files($value);
            foreach ($arr as $t) {
                $file = \Phpcmf\Service::C()->get_attachment($t['file']);
                if ($file) {
                    $value = $file['url'];
                } else {
                    $value = (string)$t['file'];
                }
                $ext = trim(strtolower(strrchr($value, '.')), '.');
                if (dr_is_image($ext)) {
                    $url = 'javascript:dr_preview_image(\''.dr_file($value).'\');';
                    $rt[] = '<a href="'.$url.'"><img width="25" src="'.ROOT_THEME_PATH.'assets/images/ext/jpg.png'.'"></a>';
                } elseif (is_file(ROOTPATH.'static/assets/images/ext/'.$ext.'.png')) {
                    $file = ROOT_THEME_PATH.'assets/images/ext/'.$ext.'.png';
                    $url = 'javascript:dr_preview_url(\''.dr_file($value).'\');';
                    $rt[] = '<a href="'.$url.'"><img width="25" src="'.$file.'"></a>';
                } elseif (strpos($value, 'http://') === 0) {
                    $file = ROOT_THEME_PATH.'assets/images/ext/url.png';
                    $url = 'javascript:dr_preview_url(\''.$value.'\');';
                    $rt[] = '<a href="'.$url.'"><img src="'.$file.'"></a>';
                } else {
                    $rt[] = $value;
                }
            }
            return implode('', $rt);
        }

        return dr_lang('无');
    }

    // 用于列表显示单文件
    public function file($value, $param = [], $data = [], $field = []) {

        if ($value) {
            $file = \Phpcmf\Service::C()->get_attachment($value);
            if ($file) {
                $value = $file['url'];
            } else {
                $value = (string)$value;
            }
            $ext = trim(strtolower(strrchr($value, '.')), '.');
            if (dr_is_image($ext)) {
                $url = 'javascript:dr_preview_image(\''.dr_file($value).'\');';
                return '<a href="'.$url.'"><img width="25" src="'.ROOT_THEME_PATH.'assets/images/ext/jpg.png'.'"></a>';
            } elseif (is_file(ROOTPATH.'static/assets/images/ext/'.$ext.'.png')) {
                $file = ROOT_THEME_PATH.'assets/images/ext/'.$ext.'.png';
                $url = 'javascript:dr_preview_url(\''.dr_file($value).'\');';
                return '<a href="'.$url.'"><img width="25" src="'.$file.'"></a>';
            } elseif (strpos($value, 'http://') === 0) {
                $file = ROOT_THEME_PATH.'assets/images/ext/url.png';
                $url = 'javascript:dr_preview_url(\''.$value.'\');';
                return '<a href="'.$url.'"><img src="'.$file.'"></a>';
            } else {
                return $value;
            }
        }

        return dr_lang('无');
    }

    // 用于列表显示用户组
    public function group($value, $param = [], $data = [], $field = []) {

        $user = dr_member_info($data['id']);
        if ($user && $user['group']) {
            $i = 0;
            $rt = '';
            $color = ['blue', 'red', 'green', 'dark', 'yellow'];
            foreach ($user['group'] as $t) {
                $cs = isset($color[$i]) && $color[$i] ? $color[$i] : 'default';
                $rt.= '<label class="btn btn-xs '.$cs.'">'.$t['group_name'].'</label>';
                $i++;
            }
            return $rt;
        }

        return dr_lang('无');
    }

    // 用于列表显示价格
    public function price($value, $param = [], $data = [], $field = []) {

        if (dr_is_empty($value)) {
            return '';
        }

        return '<span style="color:#ef4c2f">￥'.number_format(floatval($value), 2).'</span>';
    }

    // 用于列表显示价格
    public function money($value, $param = [], $data = [], $field = []) {

        if (dr_is_empty($value)) {
            return '';
        }

        if (dr_is_app('pay') && \Phpcmf\Service::C()->_is_admin_auth('pay/paylog/index')) {
            return '<a href="'.\Phpcmf\Service::M('auth')->_menu_link_url('pay/paylog/index', 'pay/paylog/index', ['field'=>'uid','keyword'=>$data['id']]).'" style="color:#ef4c2f">'.number_format($value, 2).'</a>';
        }

        return '<span style="color:#ef4c2f">'.number_format(floatval($value), 2).'</span>';
    }

    // 用于列表显示积分
    public function score($value, $param = [], $data = [], $field = []) {

        if (dr_is_empty($value)) {
            return '';
        }

        if (dr_is_app('scorelog') && \Phpcmf\Service::C()->_is_admin_auth('scorelog/home/index')) {
            return '<a href="'.\Phpcmf\Service::M('auth')->_menu_link_url('scorelog/home/index', 'scorelog/home/index', ['field'=>'uid','keyword'=>$data['id']]). '" style="color:#2f5fef">' .intval($value).'</a>';
        }

        return '<span style="color:#2f5fef">'.intval($value).'</span>';
    }

    // 用于列表显示价格、库存
    public function price_quantity($value, $param = [], $data = [], $field = []) {

        if (dr_is_empty($value)) {
            return '';
        }

        return '<span style="color:#ef4c2f">￥'.number_format($value, 2).'</span> / '.$data['price_quantity'];
    }

    // 用于指定插件调用
    public function fstatus($value, $param = [], $data = [], $field = []) {

        if (!dr_is_app('fstatus')) {
            return '[模块内容开关]插件未安装';
        }
        if (IS_ADMIN && $field
            && $field['setting']['show_admin']
            && !dr_in_array(1, \Phpcmf\Service::C()->admin['roleid'])
            && dr_array_intersect(\Phpcmf\Service::C()->admin['roleid'], $field['setting']['show_admin'])) {
            // 后台时 判断管理员权限
            return '<a href="javascript:;" onclick="dr_tips(0, \''.dr_lang('无权限操作').'\');" value="'.(string)$value.'" class="badge badge-'.($value ? "yes" : "no").'"><i class="fa fa-'.($value ? "check" : "times").'"></i></a>';
        } else {
            return '<a href="javascript:;" onclick="dr_ajax_open_close(this, \''.dr_url('fstatus/home/edit', ['id'=>$data['id'], 'mid'=>APP_DIR]).'\', 0);" class="badge badge-'.($value == 1 ? 'yes' : 'no').'"><i class="fa fa-'.($value == 1 ? 'check' : 'times').'"></i></a>';
        }
    }

    // 单选字段name
    public function radio_name($value, $param = [], $data = [], $field = []) {

        if (dr_is_empty($value)) {
            return '';
        }

        if ($field) {
            $options = dr_format_option_array($field['setting']['option']['options']);
            if ($options && isset($options[$value])) {
                return $options[$value];
            }
        }

        return $value;
    }

    // 下拉字段name值
    public function select_name($value, $param = [], $data = [], $field = []) {

        if (dr_is_empty($value)) {
            return '';
        }

        if ($field) {
            $options = dr_format_option_array($field['setting']['option']['options']);
            if ($options && isset($options[$value])) {
                return $options[$value];
            }
        }

        return $value;
    }

    // checkbox字段name值
    public function checkbox_name($value, $param = [], $data = [], $field = []) {

        if (dr_is_empty($value)) {
            return '';
        }

        $arr = dr_string2array($value);
        if ($field && is_array($arr)) {
            $options = dr_format_option_array($field['setting']['option']['options']);
            if ($options) {
                $rt = [];
                foreach ($options as $i => $v) {
                    if (dr_in_array($i, $arr)) {
                        $rt[] = $v;
                    }
                }
                return implode('、', $rt);
            }
        }

        return $value;
    }

    // 联动字段name值
    public function linkage_name($value, $param = [], $data = [], $field = []) {

        if (!$value) {
            return '';
        }

        if ($field && $field['setting']['option']['linkage']) {
            return dr_linkagepos($field['setting']['option']['linkage'], $value, '-');
        }

        return $value;
    }

    // 联动多项字段name值
    public function linkages_name($value, $param = [], $data = [], $field = []) {

        if (!$value) {
            return '';
        }

        if ($field && $field['setting']['option']['linkage']) {
            $rt = [];
            $values = dr_string2array($value);
            foreach ($values as $value) {
                $rt[] = dr_linkagepos($field['setting']['option']['linkage'], $value, '-');
            }
            return implode('、', $rt);
        }

        return $value;
    }

    // 实时存储时间值
    public function save_time_value($value, $param = [], $data = [], $field = []) {

        $uri = \Phpcmf\Service::L('router')->uri('save_value_edit');
        $url = (IS_MEMBER ? dr_member_url($uri) : dr_url($uri)).'&id='.$data['id'].'&after='; //after是回调函数
        $html = '<input type="text" class="form-control" placeholder="" value="'.dr_date($value).'" onblur="dr_ajax_save(dr_strtotime(this.value), \''.$url.'\', \''.$field['fieldname'].'\')">';

        \Phpcmf\Service::C()->session()->set('function_list_save_text_value', \Phpcmf\Service::C()->uid);

        return $html;
    }

    // 实时存储文本值
    public function save_text_value($value, $param = [], $data = [], $field = []) {

        if (IS_ADMIN && $field
            && $field['setting']['show_admin']
            && !dr_in_array(1, \Phpcmf\Service::C()->admin['roleid'])
            && dr_array_intersect(\Phpcmf\Service::C()->admin['roleid'], $field['setting']['show_admin'])) {
            // 后台时 判断管理员权限
            $html = '<input type="text" class="form-control" readonly="readonly" value="'.htmlspecialchars((string)$value).'">';
        } else {
            $uri = \Phpcmf\Service::L('router')->uri('save_value_edit');
            $url = (IS_MEMBER ? dr_member_url($uri) : dr_url($uri)).'&id='.$data['id'].'&after='; //after是回调函数
            $html = '<input type="text" class="form-control" placeholder="" value="'.htmlspecialchars((string)$value).'" onblur="dr_ajax_save(encodeURIComponent(this.value), \''.$url.'\', \''.$field['fieldname'].'\')">';
            \Phpcmf\Service::C()->session()->set('function_list_save_text_value', \Phpcmf\Service::C()->uid);
        }

        return $html;
    }

    // 实时存储选择值
    public function save_select_value($value, $param = [], $data = [], $field = []) {

        $uri = \Phpcmf\Service::L('router')->uri('save_value_edit');
        $url = (IS_MEMBER ? dr_member_url($uri) : dr_url($uri)).'&name='.$field['fieldname'].'&id='.$data['id'].'&after='; //after是回调函数
        if (IS_ADMIN && $field
            && $field['setting']['show_admin']
            && !dr_in_array(1, \Phpcmf\Service::C()->admin['roleid'])
            && dr_array_intersect(\Phpcmf\Service::C()->admin['roleid'], $field['setting']['show_admin'])) {
            // 后台时 判断管理员权限
            $html = '<a href="javascript:;" onclick="dr_tips(0, \''.dr_lang('无权限操作').'\');" value="'.(string)$value.'" class="badge badge-'.($value ? "yes" : "no").'"><i class="fa fa-'.($value ? "check" : "times").'"></i></a>';
        } else {
            $html = '<a href="javascript:;" onclick="dr_ajax_list_open_close(this, \''.$url.'\');" value="'.(string)$value.'" class="badge badge-'.($value ? "yes" : "no").'"><i class="fa fa-'.($value ? "check" : "times").'"></i></a>';
            if (!$this->select_js) {
                $html.= '<script>function dr_ajax_list_open_close(e, url) {
            var obj = $(e);
            var val = 0;
            if (obj.attr("value") == 1) {
                val = 0;
            } else {
                val = 1;
            }
            url+="&value="+val;
            $.ajax({
                type: "GET",
                url: url,
                dataType: "json",
                success: function (json) {
                    if (json.code == 1) {
                        if (val == 0) {
                            obj.attr(\'class\', \'badge badge-no\');
                            obj.html(\'<i class="fa fa-times"></i>\');
                        } else {
                            obj.attr(\'class\', \'badge badge-yes\');
                            obj.html(\'<i class="fa fa-check"></i>\');
                        }
                        obj.attr("value", val);
                    }
                    dr_tips(json.code, json.msg);
                },
                error: function(HttpRequest, ajaxOptions, thrownError) {
                    dr_ajax_admin_alert_error(HttpRequest, ajaxOptions, thrownError);
                }
            });
}</script> ';
                $this->select_js = 1;
            }
            \Phpcmf\Service::C()->session()->set('function_list_save_text_value', \Phpcmf\Service::C()->uid);
        }

        return $html;
    }

    // text
    public function text($value, $param = [], $data = [], $field = []) {

        if (!$value) {
            return '';
        }

        return dr_clearhtml($value);
    }

    // 编码
    public function code($value, $param = [], $data = [], $field = []) {

        if (!$value) {
            return '';
        }

        return htmlspecialchars($value);
    }

    // 原样输出
    public function get_value($value, $param = [], $data = [], $field = []) {
        return $value;
    }
}