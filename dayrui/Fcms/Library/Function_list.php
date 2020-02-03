<?php namespace Phpcmf\Library;


/**
 * http://www.xunruicms.com
 * 本文件是框架系统文件，二次开发时不可以修改本文件，可以通过继承类方法来重写此文件
 **/



/**
 * 列表格式化函数库
 */

class Function_list
{

    private $uid_data = [];

    // 用于列表显示栏目
    function catid($catid, $param = [], $data = []) {

        $url = IS_ADMIN ? \Phpcmf\Service::L('router')->url(APP_DIR.'/'.$_GET['c'].'/index', ['catid' => $catid]) : dr_url_prefix(dr_cat_value(MOD_DIR, $catid, 'url'), MOD_DIR).'" target="_blank';
        $value = dr_cat_value(MOD_DIR, $catid, 'name');

        return '<a href="'.$url.'">'.dr_strcut($value, 10).'</a>';
    }

    // 用于列表显示内容
    function comment($value, $param = [], $data = []) {

        return $this->content($value, $param, $data);
    }

    // 用于列表显示内容
    function content($value, $param = [], $data = []) {

        $value = htmlspecialchars(dr_clearhtml($value));
        $title = dr_replace_emotion(dr_keyword_highlight(dr_strcut($value, 30), $param['keyword']));
        !$title && $title = '...';

        return isset($data['url']) && $data['url'] ? '<a href="'.dr_url_prefix($data['url'], MOD_DIR).'" target="_blank" class="tooltips" data-container="body" data-placement="top" data-original-title="'.$value.'" title="'.$value.'">'.$title.'</a>' : $title;
    }

    // 用于列表显示联动菜单值
    function linkage_address($value, $param = [], $data = []) {

        return dr_linkagepos('address', $value, '-');
    }

    // 用于列表显示状态
    function status($value, $param = [], $data = []) {

        return '<label>'.($value ? '<span class="label label-sm label-success">'.dr_lang('已通过') : '<span class="label label-sm label-danger">'.dr_lang('待审核')).'</span></label>';
    }

    // 用于列表显示标题
    function title($value, $param = [], $data = []) {

        $value = htmlspecialchars(dr_clearhtml($value));
        $title = ($data['thumb'] ? '<i class="fa fa-photo"></i> ' : '').dr_keyword_highlight(dr_strcut($value, 30), $param['keyword']);
        !$title && $title = '...';

        return isset($data['url']) && $data['url'] ? ('<a href="'.dr_url_prefix($data['url'], MOD_DIR).'" target="_blank" class="tooltips" data-container="body" data-placement="top" data-original-title="'.$value.'" title="'.$value.'">'.$title.'</a>'.($data['link_id'] > 0 ? '  <i class="fa fa-link font-green" title="'.dr_lang('同步链接').'"></i>' : '')) : $title;
    }

    // 用于列表显示时间日期格式
    function datetime($value, $param = [], $data = []) {
        return dr_date($value, null, 'red');
    }

    // 用于列表显示日期格式
    function date($value, $param = [], $data = []) {
        return dr_date($value, 'Y-m-d', 'red');
    }

    // 用于列表显示作者
    function author($value, $param = [], $data = []) {
        if ($value == 'guest') {
            return dr_lang('游客');
        } elseif ((isset($data['username']) || isset($data['author'])) && $data['uid']) {
            // 模块需要重新查询名字
            $member = $this->uid_data[$data['uid']] = isset($this->uid_data[$data['uid']]) && $this->uid_data[$data['uid']] ? $this->uid_data[$data['uid']] : \Phpcmf\Service::M('member')->username($data['uid']);
        } else {
            $member = $value;
        }
        return $value ? '<a class="fc_member_show" href="javascript:;" uid="'.intval($data['uid']).'" member="'.htmlspecialchars($member).'">'.dr_strcut($value, 10).'</a>' : dr_lang('游客');
    }

    // 用于列表显示作者
    function uid($uid, $param = [], $data = []) {
        // 查询username
        if (strlen($uid) > 12) {
            return dr_lang('游客');
        }
        $this->uid_data[$uid] = isset($this->uid_data[$uid]) && $this->uid_data[$uid] ? $this->uid_data[$uid] : \Phpcmf\Service::M('member')->username($uid);
        return $this->uid_data[$uid] ? '<a class="fc_member_show" href="javascript:;" uid="'.intval($uid).'" member="'.htmlspecialchars($this->uid_data[$uid]).'">'.dr_strcut($this->uid_data[$uid], 10).'</a>' : dr_lang('游客');
    }

    // 用于列表显示ip地址
    function ip($value, $param = [], $data = [], $len = 200) {
        return '<a href="http://www.ip138.com/ips138.asp?ip='.$value.'&action=2" target="_blank">'.dr_strcut(\Phpcmf\Service::L('ip')->address($value), $len).'</a>';
    }

    // url链接输出
    function url($value, $param = [], $data = []) {
        return '<a href="'.$value.'" target="_blank">'.$value.'</a>';
    }

    // 用于列表显示多文件
    function files($value, $param = [], $data = []) {
        return dr_lang($value ? '有' : '无');
    }

    // 用于列表显示单文件
    function file($value, $param = [], $data = []) {
        if ($value) {
            $file = \Phpcmf\Service::C()->get_attachment($value);
            if ($file) {
                $value = $file['url'];
            }
            $ext = trim(strtolower(strrchr($value, '.')), '.');
            if (in_array($ext, ['jpg', 'gif', 'png', 'jpeg'])) {
                $url = 'javascript:dr_preview_image(\''.$value.'\');';
                return '<a href="'.$url.'"><img src="'.ROOT_THEME_PATH.'assets/images/ext/jpg.png'.'"></a>';
            } elseif (is_file(ROOTPATH.'static/assets/images/ext/'.$ext.'.png')) {
                $file = ROOT_THEME_PATH.'assets/images/ext/'.$ext.'.png';
                $url = 'javascript:dr_preview_url(\''.dr_file($value).'\');';
                return '<a href="'.$url.'"><img src="'.$file.'"></a>';
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

    // 用于列表显示价格
    function price($value, $param = [], $data = []) {
        return '<span style="color:#ef4c2f">￥'.number_format($value, 2).'</span>';
    }

    // 用于列表显示价格、库存
    function price_quantity($value, $param = [], $data = []) {
        return '<span style="color:#ef4c2f">￥'.number_format($value, 2).'</span> / '.$data['price_quantity'];
    }


    // 用于指定插件调用
    function fstatus($value, $param = [], $data = []) {
        if (!dr_is_app('fstatus')) {
            return '[模块内容开关]插件未安装';
        }
        return '&nbsp;&nbsp;<a href="javascript:;" onclick="dr_ajax_open_close(this, \''.dr_url('fstatus/home/edit', ['id'=>$data['id'], 'mid'=>APP_DIR]).'\', 0);" class="badge badge-'.($value == 1 ? 'yes' : 'no').'"><i class="fa fa-'.($value == 1 ? 'check' : 'times').'"></i></a>';
    }

    // 单选字段name
    function radio_name($value, $param = [], $data = [], $field = []) {

        if ($field) {
            $options = dr_format_option_array($field['setting']['option']['options']);
            if ($options && isset($options[$value])) {
                return $options[$value];
            }
        }

        return $value;
    }

    // 下拉字段name值
    function select_name($value, $param = [], $data = [], $field = []) {

        if ($field) {
            $options = dr_format_option_array($field['setting']['option']['options']);
            if ($options && isset($options[$value])) {
                return $options[$value];
            }
        }

        return $value;
    }

    // checkbox字段name值
    function checkbox_name($value, $param = [], $data = [], $field = []) {

        $arr = dr_string2array($value);
        if ($field && is_array($arr)) {
            $options = dr_format_option_array($field['setting']['option']['options']);
            if ($options) {
                $rt = [];
                foreach ($options as $i => $v) {
                    if (in_array($i, $arr)) {
                        $rt[] = $v;
                    }
                }
                return implode('、', $rt);
            }
        }

        return $value;
    }

    // 联动字段name值
    function linkage_name($value, $param = [], $data = [], $field = []) {

        if ($field && $field['setting']['option']['linkage']) {
            return dr_linkagepos($field['setting']['option']['linkage'], $value, '-');
        }

        return $value;
    }


}