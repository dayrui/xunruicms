<?php

/**
 * http://www.xunruicms.com
 * 本文件是框架系统文件，二次开发时不可以修改本文件
 **/


// 通过数组值查找数组key
function dr_get_array_key($array, $value) {
	if (!in_array($value, $array)) {
		return false;
	}
	$new = array_flip($array);
	return isset($new[$value]) ? $new[$value] : false;
}

// 站点信息输出
function dr_site_info($name, $siteid = SITE_ID) {
    return \Phpcmf\Service::C()->get_cache('site', $siteid, 'config', $name);
}

// ftable字段输出
function dr_get_ftable($id, $value, $class = '') {

    $field = \Phpcmf\Service::C()->get_cache('table-field', $id);
    if (!$field) {
        return 'Ftable字段没有得到缓存数据';
    }

    // class属性
    !$class && $class = 'table table-nomargin table-bordered table-striped table-bordered table-advance';

    // 字段默认值
    $value = dr_string2array($value);
    // 表单宽度设置
    $width = \Phpcmf\Service::_is_mobile() ? '100%' : ($field['setting']['option']['width'] ? $field['setting']['option']['width'] : '100%');

    $str = '<table id="dr_table_'.$field['fieldname'].'" class="'.$class.'" style="width:'.$width.(is_numeric($width) ? 'px' : '').';">';
    $str.= ' <thead><tr>';

    if ($field['setting']['option']['is_first_hang'] && !$field['setting']['option']['is_add']) {
        $str .= ' <th> ' . $field['setting']['option']['first_cname'] . ' </th>';
    }

    if ($field['setting']['option']['field']) {
        foreach ($field['setting']['option']['field'] as $t) {
            if ($t['type']) {
                $style = $t['width'] ? 'style="width:'.$t['width'].(is_numeric($t['width']) ? 'px' : '').';"' : '';
                $str.= ' <th '.$style.'>'.$t['name'].'</th>';
            }
        }
    }

    $str.= ' </tr></thead>';
    $str.= ' <tbody>';

    for ($i = 1; $i <= count($value); $i++) {

        $str.= ' <tr>';
        if ($field['setting']['option']['is_first_hang'] && !$field['setting']['option']['is_add']) {
            $hname = $field['setting']['option']['hang'][$i]['name'] ? $field['setting']['option']['hang'][$i]['name'] : '未命名';
            $str .= ' <td> ' . $hname . ' </td>';
        }

        if ($field['setting']['option']['field']) {
            foreach ($field['setting']['option']['field'] as $n => $t) {
                if ($t['type']) {
                    $str.= ' <td>'.$value[$i][$n].'</td>';
                }
            }
        }

        $str.= ' </tr>';
    }

    $str.= ' </tbody>';
    $str.= '</table>';

    return $str;

}

// 判断搜索值是否是多重选择时的选中状态 1选中 0不选
function dr_is_double_search($param, $value) {

    if (!$param) {
        return 0;
    }

    $arr = explode('|', $param);
    if (in_array($value, $arr)) {
        return 1;
    }

    return 0;
}

// 获取多重选择是的参数值
function dr_get_double_search($param, $value) {

    if (!$param) {
        return $value;
    }

    $arr = explode('|', $param);
    if (in_array($value, $arr)) {
        // 如果存在，那么久移除他
        $arr = array_merge(array_diff($arr, array($value)));
    } else {
        // 没有就加上
        $arr[] = $value;
    }

    return $arr ? @implode('|', $arr) : '';
}


// 获取内容中的缩略图
function dr_get_content_img($value, $num = 0) {

    $rt = [];
    $value = preg_replace('/\.(gif|jpg|jpeg|png)@(.*)(\'|")/iU', '.$1$3', $value);
    if (preg_match_all("/(src)=([\"|']?)([^ \"'>]+\.(gif|jpg|jpeg|png))\\2/i", $value, $imgs)) {
        $imgs[3] = array_unique($imgs[3]);
        foreach ($imgs[3] as $i => $img) {
            if ($num && $i+1 > $num) {
                break;
            }
            $rt[] = dr_file(trim($img, '"'));
        }
    }
    return $rt;
}

/**
 * 插件是否被安装
 */
function dr_is_app($dir) {
    return is_file(dr_get_app_dir($dir).'/install.lock');
}

/**
 * 模块是否被安装
 */
function dr_is_module($dir, $siteid = SITE_ID) {
    return \Phpcmf\Service::L('cache')->get('module-'.$siteid, $dir) ? 1 : 0;
}

/**
 * 字符串替换函数
 */
function dr_rp($str, $o, $t) {
    return str_replace($o, $t, $str);
}

/**
 * 二维码调用
 */
function dr_qrcode($text, $thumb = '', $level = 'H', $size = 5) {
    return ROOT_URL.'index.php?s=api&c=api&m=qrcode&thumb='.urlencode($thumb).'&text='.urlencode($text).'&size='.$size.'&level='.$level;
}

/**
 * 秒转化时间
 */
function dr_sec2time($times){
    $result = '00:00:00';
    if ($times > 0) {
        $hour = floor($times/3600);
        $minute = floor(($times-3600 * $hour)/60);
        $second = floor((($times-3600 * $hour) - 60 * $minute) % 60);
        strlen($hour) == 1 && $hour = '0'.$hour;
        strlen($minute) == 1 && $minute = '0'.$minute;
        strlen($second) == 1 && $second = '0'.$second;
        $result = $hour.':'.$minute.':'.$second;
    }
    return $result;
}

/**
 * 格式化多文件数组
 */
function dr_get_files($value) {

    $data = [];
    $value = dr_string2array($value);
    if (!$value) {
        return $data;
    } elseif (!isset($value['file'])) {
        return $value;
    }

    foreach ($value['file'] as $i => $file) {
        $data[] = [
            'file' => $file, // 对应文件或附件id
            'title' => $value['title'][$i], // 对应标题
            'description' => $value['description'][$i], // 对应描述
        ];
    }

    return $data;
}

// 文件上传临时目录
function dr_upload_temp_path() {

    if (function_exists('ini_get')) {
        $path = ini_get('upload_tmp_dir');
        if ($path) {
            return rtrim($path, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;
        }
    }

    return WRITEPATH.'temp/';
}

/**
 * 内容文章显示内链
 */
function dr_content_link($tags, $content, $num = 0) {

    if (!$tags || !$content) {
        return $content;
    } elseif (!is_array($tags)) {
        return $content;
    }

    foreach ($tags as $name => $url) {
        if ($name && $url) {
            $url = '<a href="'.$url.'" target="_blank">'.$name.'</a>';
            $content = @preg_replace('\'(?!((<.*?)|(<a.*?)|(<strong.*?)))('.str_replace(["'", '-'], ["\'", '\-'], preg_quote($name)).')(?!(([^<>]*?)>)|([^>]*?</a>)|([^>]*?</strong>))\'si',
                $url,
                $content,
                $num ? $num : -1
            );
        }
    }

    return $content;
}


// 内容加内链
function dr_neilian($content, $blank = 1, $num = 1) {

    if (!$content) {
        return '';
    }

    $tags = \Phpcmf\Service::L('cache')->get('tag-'.SITE_ID);
    if ($tags) {
        foreach ($tags as $t) {
            $url = '<a href="'.$t['url'].'" '.($blank ? 'target="_blank"' : '').'>'.$t['name'].'</a>';
            $content = @preg_replace('\'(?!((<.*?)|(<a.*?)|(<strong.*?)))('.str_replace(["'", '-'], ["\'", '\-'], preg_quote($t['name'])).')(?!(([^<>]*?)>)|([^>]*?</a>)|([^>]*?</strong>))\'si',
                $url,
                $content,
                $num ? $num : -1
            );
        }
    }

    return $content;
}

// 星级显示
function dr_star_level($num, $shifen = 0) {

    $shifen && $num = $num/2;

    $lv = 5;
    $int = min(floor($num), $lv);
    if (!$int) {
        return '<i title="'.$num.'" class="fa fa-star-o"></i><i title="'.$num.'" class="fa fa-star-o"></i><i title="'.$num.'" class="fa fa-star-o"></i><i title="'.$num.'" class="fa fa-star-o"></i><i title="'.$num.'" class="fa fa-star-o"></i>';
    }

    // 实心
    $shi = [];
    for ($i=0; $i<$int; $i++) {
        $shi[] = '<i title="'.$num.'" class="fa fa-star"></i>';
    }

    // 五星返回
    if (dr_count($shi) >= $lv) {
        return @implode('', $shi);
    }

    // 剩下的心
    if ($num - $int > 0.5) {
        $shi[]= '<i title="'.$num.'" class="fa fa-star-half-o"></i>';
    } else {
        $shi[] = '<i title="'.$num.'" class="fa fa-star-o"></i>';
    }
    $sx = $lv - dr_count($shi);
    if ($sx > 0) {
        for ($i=1; $i< $lv - $int; $i++) {
            $shi[] = '<i title="'.$num.'" class="fa fa-star-o"></i>';
        }
    }

    return @implode('', $shi);
}

/**
 * 301域名定位
 */
if (!function_exists('dr_domain_301')) {
    function dr_domain_301($domain, $uri = '') {

        !$uri && $uri = (isset($_SERVER['HTTP_X_REWRITE_URL']) && trim($_SERVER['REQUEST_URI'], '/') == SELF ? trim($_SERVER['HTTP_X_REWRITE_URL'], '/') : ($_SERVER['REQUEST_URI'] ? trim($_SERVER['REQUEST_URI'], '/') : ''));
        $url = rtrim($domain, '/').'/'.$uri;

        if ($url == dr_now_url()) {
            return;
        }

        if (IS_DEV) {
            \Phpcmf\Service::C()->_admin_msg(1, '开发者模式：正在做自动识别终端（关闭开发者模式时即可自动跳转）', $url);exit;
        }

        dr_redirect($url, 'auto', 301);exit;
    }
}

// 格式化sql创建
function dr_format_create_sql($sql) {
    return trim(str_replace('ENGINE=MyISAM', 'ENGINE=InnoDB', $sql));
}

/**
 * 获取cms域名部分
 */
function dr_cms_domain_name($url) {

    $param = parse_url($url);
    if (isset($param['host']) && $param['host']) {
        return $param['host'];
    }

    return $url;
}

/**
 * 多语言输出
 */
function dr_lang(...$param) {

    if (empty($param)) {
        return null;
    }

    // 取第一个作为语言名称
    $string = $param[0];
    unset($param[0]);

    // 调用语言包内容
    $string = \Phpcmf\Service::L('lang')->text($string);

    return $param ? vsprintf($string, $param) : $string;
}

// 判断用户中心菜单权限
function dr_member_menu_show($t) {

    if ($t['mark']) {
        list($a, $b) = explode('-', $t['mark']);
        switch ($a) {
            case 'module':
                // 判断模块当前站点是否可用
                if (!\Phpcmf\Service::C()->get_cache('module-'.SITE_ID.'-'.$b)) {
                    return 0;
                }
                break;
        }
    }

    // 判断站点显示权限
    $is_site = 0;
    if (!$t['site'] || in_array(SITE_ID, $t['site'])) {
        $is_site = 1; // 当前站可用
    }

    // 判断用户组显示权限
    if ($is_site && (!$t['group'] || array_intersect(\Phpcmf\Service::C()->member['groupid'], $t['group']))) {
        return 1;
    }

    return 0;
}

// 获取url在导航的id
function dr_navigator_id($type, $markid) {
    return (int)\Phpcmf\Service::L('cache')->get('navigator-'.SITE_ID.'-url', $type, $markid);
}

// 获取栏目数据及自定义字段
function dr_cat_value(...$get) {

    if (empty($get)) {
        return '';
    }

    if (is_numeric($get[0]) && MOD_DIR) {
        // 值是栏目id时，表示当前模块
        $name = 'module-'.SITE_ID.'-'.MOD_DIR;
    } else {
        // 指定模块
        $name = strpos($get[0], '-') ? 'module-'.$get[0] : 'module-'.SITE_ID.'-'.$get[0];
        unset($get[0]);
    }

    $i = 0;
    $param = [];
    foreach ($get as $t) {
        if ($i == 0) {
            $param[] = $name;
            $param[] = 'category';
        }
        $param[] = $t;
        $i = 1;
    }

    return call_user_func_array([\Phpcmf\Service::C(), 'get_cache'], $param);
}


// 获取栏目数据及自定义字段
function dr_page_value($id, $field, $site = SITE_ID) {

    if (empty($id)) {
        return '';
    }

    return \Phpcmf\Service::C()->get_cache('page-'.$site, 'data', $id, $field);
}

// 获取共享栏目数据及自定义字段
function dr_share_cat_value($id, $field='') {

    $get = func_get_args();
    if (empty($get)) {
        return NULL;
    }

    $i = 0;
    $param = [];
    foreach ($get as $t) {
        if ($i == 0) {
            $param[] = 'module-'.SITE_ID.'-share';
            $param[] = 'category';
        }
        $param[] = $t;
        $i = 1;
    }

    return call_user_func_array(array(\Phpcmf\Service::C(), 'get_cache'), $param);
}

// 获取域名部分
function dr_get_domain_name($url) {

    list($url) = explode(':', str_replace(['https://', 'http://', '/'], '', $url));

    return $url;
}

// 分割数组
function dr_save_bfb_data($data) {

    $cache = [];
    $count = dr_count($data);
    if ($count > 100) {
        $pagesize = ceil($count/100);
        for ($i = 1; $i <= 100; $i ++) {
            $cache[$i] = array_slice($data, ($i - 1) * $pagesize, $pagesize);
        }
    } else {
        for ($i = 1; $i <= $count; $i ++) {
            $cache[$i] = array_slice($data, ($i - 1), 1);
        }
    }

    return $cache;
}

// 会员头像路径和url
function dr_avatar_path() {

    //$config = \Phpcmf\Service::C()->get_cache('site', SITE_ID, 'image');
    $config = [
        'avatar_url' => defined('SYS_AVATAR_URL') ? SYS_AVATAR_URL : '',
        'avatar_path' => defined('SYS_AVATAR_PATH') ? SYS_AVATAR_PATH : '',
    ];

    if (!$config['avatar_path'] || !$config['avatar_url']) {
        return [ROOTPATH.'api/member/', ROOT_URL.'api/member/'];
    } elseif ((strpos($config['avatar_path'], '/') === 0 || strpos($config['avatar_path'], ':') !== false) && is_dir($config['avatar_path'])) {
        // 相对于根目录
        return [rtrim($config['avatar_path'], DIRECTORY_SEPARATOR).'/', trim($config['avatar_url'], '/').'/'];
    } else {
        // 在当前网站目录
        return [ROOTPATH.trim($config['avatar_path'], '/').'/', ROOT_URL.trim($config['avatar_path'], '/').'/'];
    }
}

/**
 * 会员头像
 *
 * @param	intval	$uid
 * @return	string
 */
function dr_avatar($uid) {

    if ($uid) {
        list($cache_path, $cache_url) = dr_avatar_path();
        if (is_file($cache_path.$uid.'.jpg')) {
            return $cache_url.$uid.'.jpg';
        }
    }

    return ROOT_THEME_PATH.'assets/images/avatar.png';
}

/**
 * 调用会员详细信息（自定义字段需要手动格式化）
 *
 * @param	intval	$uid	会员uid
 * @param	intval	$name	输出字段
 * @param	intval	$cache	缓存时间
 * @return	string
 */
function dr_member_info($uid, $name = '', $cache = -1) {

    $data = \Phpcmf\Service::L('cache')->get_data('member-info-'.$uid);
    if (!$data) {
        $data = \Phpcmf\Service::M('member')->get_member($uid);
        SYS_CACHE && \Phpcmf\Service::L('cache')->set_data('member-info-'.$uid, $data, $cache > 0 ? $cache : SYS_CACHE_SHOW * 3600);
    }

    return $name ? $data[$name] : $data;
}

function dr_member_username_info($username, $name = '', $cache = -1) {

    $data = \Phpcmf\Service::L('cache')->get_data('member-info-name-'.$username);
    if (!$data) {
        $data = \Phpcmf\Service::M('member')->get_member(0, $username);
        SYS_CACHE && \Phpcmf\Service::L('cache')->set_data('member-info-name-'.$username, $data, $cache > 0 ? $cache : SYS_CACHE_SHOW * 3600);
    }

    return $name ? $data[$name] : $data;
}


/**
 * 获取到上级邀请者的信息
 *
 * @param	intval	$uid	我的uid
 * @param	string	$name	字段信息
 * @return
 */
function dr_member_invite($uid, $name = 'uid') {
    $data = \Phpcmf\Service::M()->db->where('rid', $uid)->get('member_invite')->row_array();
    return $data[$name] ? $data[$name] : '';
}


/**
 * 执行函数
 */
function dr_list_function($func, $value, $param = [], $data = [], $field = []) {

    if (!$func) {
        return $value;
    }

    $obj = \Phpcmf\Service::L('Function_list');
    if (method_exists($obj, $func)) {
        return call_user_func_array([$obj, $func], [$value, $param, $data, $field]);
    } elseif (function_exists($func)) {
        return call_user_func_array($func, [$value, $param, $data, $field]);
    } else {
        log_message('error', '你没有定义字段列表回调函数：'.$func);
    }

    return $value;
}


/**
 * 模块栏目面包屑导航
 *
 * @param	intval	$catid	栏目id
 * @param	string	$symbol	面包屑间隔符号
 * @param	string	$url	是否显示URL
 * @param	string	$html	格式替换
 * @return	string
 */
function dr_catpos($catid, $symbol = ' > ', $url = true, $html= '', $dirname = MOD_DIR, $url_call_func = '') {

    if (!$catid) {
        return '';
    }

    $cat = \Phpcmf\Service::L('cache')->get('module-'.SITE_ID.'-'.(!$dirname || $dirname == 'MOD_DIR' ? 'share' : $dirname), 'category');
    if (!isset($cat[$catid])) {
        return '';
    }

    $name = [];
    $array = explode(',', $cat[$catid]['pids']);
    $array[] = $catid;
    foreach ($array as $id) {
        if ($id && $cat[$id]) {
            if ($url_call_func && function_exists($url_call_func)) {
                $murl = $url_call_func($cat[$id]);
            } else {
                $murl = $cat[$id]['url'];
            }
            $name[] = $url ? ($html ? str_replace(['[url]', '[name]'], [$murl, $cat[$id]['name']], $html): "<a href=\"{$murl}\">{$cat[$id]['name']}</a>") : $cat[$id]['name'];
        }
    }

    return implode($symbol, array_unique($name));
}

/**
 * 联动菜单包屑导航
 *
 * @param	string	$code	联动菜单代码
 * @param	intval	$id		id
 * @param	string	$symbol	间隔符号
 * @param	string	$url	url地址格式，必须存在[linkage]，否则返回不带url的字符串
 * @param	string	$html	格式替换
 * @return	string
 */
function dr_linkagepos($code, $id, $symbol = ' > ', $url = '', $html = '') {

    if (!$code || !$id) {
        return '';
    }

    $url = $url ? urldecode($url) : '';
    $link = \Phpcmf\Service::L('cache')->get('linkage-'.SITE_ID.'-'.$code);
    $cids = \Phpcmf\Service::L('cache')->get('linkage-'.SITE_ID.'-'.$code.'-id');
    if (is_numeric($id)) {
        // id 查询
        $id = $cids[$id];
        $data = $link[$id];
    } else {
        // 别名查询
        $data = $link[$id];
    }

    $name = [];
    $array = @explode(',', $data['pids']);
    $array[] = $data['ii'];

    foreach ($array as $ii) {
        if ($ii) {
            $name[] = $url ? ($html ? str_replace(['[url]', '[name]'], array(str_replace(['[linkage]', '{linkage}'], $cids[$ii], $url), $link[$cids[$ii]]['name']), $html) : "<a href=\"".str_replace(['[linkage]', '{linkage}'], $cids[$ii], $url)."\">{$link[$cids[$ii]]['name']}</a>") : $link[$cids[$ii]]['name'];
        }
    }

    return implode($symbol, array_unique($name));
}

/**
 * 联动菜单调用
 *
 * @param	string	$code	菜单代码
 * @param	intval	$id		菜单id
 * @param	intval	$level	调用级别，1表示顶级，2表示第二级，等等
 * @param	string	$name	菜单名称，如果有显示它的值，否则返回数组
 * @return	array
 */
function dr_linkage($code, $id, $level = 0, $name = '') {

    if (!$id) {
        return false;
    }

    $link = \Phpcmf\Service::L('cache')->get('linkage-'.SITE_ID.'-'.$code);
    $cids = \Phpcmf\Service::L('cache')->get('linkage-'.SITE_ID.'-'.$code.'-id');
    if (is_numeric($id)) {
        // id 查询
        $id = $cids[$id];
        $data = $link[$id];
    } else {
        // 别名查询
        $data = $link[$id];
    }
    $pids = @explode(',', $data['pids']);
    if ($level == 0) {
        return $name ? $data[$name] : $data;
    }

    if (!$pids) {
        return $name ? $data[$name] : $data;
    }

    $i = 1;
    foreach ($pids as $pid) {
        if ($pid) {
            $pid = $cids[$pid]; // 把id转化成cname
            if ($i == $level) {
                return $name ? $link[$pid][$name] : $link[$pid];
            }
            $i++;
        }
    }

    return $name ? $data[$name] : $data;
}

/**
 * 单页面包屑导航
 *
 * @param	intval	$id
 * @param	string	$symbol
 * @param	string	$html
 * @return	string
 */
function dr_page_catpos($id, $symbol = ' > ', $html = '') {

    if (!$id) {
        return '';
    }

    $page = \Phpcmf\Service::C()->get_cache('page-'.SITE_ID, 'data');
    if (!isset($page[$id])) {
        return '';
    }

    $name = [];
    $array = explode(',', $page[$id]['pids']);
    $array[] = $id;

    foreach ($array as $i) {
        if ($i && $page[$i]) {
            $murl = $page[$i]['url'];
            $name[] = $html ? str_replace(['[url]', '[name]'], [$murl, $page[$i]['name']], $html) : "<a href=\"{$murl}\">{$page[$i]['name']}</a>";
        }
    }

    return implode($symbol, array_unique($name));
}

/**
 * 支付表单调用
 * mark     表名-主键id-字段id
 * value    支付金额
 * title    支付说明
 * */
function dr_payform($mark, $value = 0, $title = '', $url = '',  $remove_div  = 1) {
    return \Phpcmf\Service::M('Pay')->payform($mark, $value, $title, $url, $remove_div);
}

/**
 * 字段表单调用
 * field    字段配置
 * value    默认值
 * */
function dr_fieldform($field, $value = '', $remove_div  = 1) {

    if (!$field) {
        return '字段数据不存在';
    }

    $field = dr_string2array($field);
    if (!$field['fieldtype']) {
        return '字段类别不存在';
    }

    $f = \Phpcmf\Service::L('Field')->get($field['fieldtype']);
    $f->remove_div = $remove_div;
    return $f->input($field, $value);
}

// 打赏支付
function dr_donation($id, $title = '', $dir = null, $remove_div  = 1) {
    !$dir && $dir = MOD_DIR;
    return \Phpcmf\Service::M('Pay')->payform('donation-'.$dir.'-'.$id.'-'.SITE_ID, 0, $title, '/index.php?s='.$dir.'&c=show&id='.$id, $remove_div);
}

// 是否存在收藏夹中 1收藏了 2没有收藏
function dr_is_favorite($dir, $id, $uid = 0) {

    !$uid && $uid = \Phpcmf\Service::C()->uid;

    if (!$uid) {
        return 0;
    } elseif (!$dir) {
        return 0;
    }

    return \Phpcmf\Service::M()->db->table(SITE_ID.'_'.$dir.'_favorite')->where('uid', $uid)->where('cid', $id)->countAllResults();
}

// 获取字段表单框
function dr_field_form($field, $value = '', $app = '', $remove_div  = 1) {

    if (!$field) {
        return 'field字段信息为空';
    }

    $f = \Phpcmf\Service::L('Field')->get($field['fieldtype']);
    if (!$f) {
        return 'field对象（'.$field['fieldtype'].'）不存在';
    }

    $f->remove_div = $remove_div;

    return $f->input($field, $value);
}

/**
 * 资料块内容
 *
 * @param	intval	$id
 * @return	array
 */
function dr_block($id, $type = 0, $site = 0) {
    return \Phpcmf\Service::C()->get_cache('block-'.($site ? $site : SITE_ID), $id, $type);
}

// 是否是微信公众号
function dr_is_weixin_app() {
    return strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger');
}

/**
 * 全局变量调用
 *
 * @param	string	$name	别名
 * @return
 */
function dr_var_value($name) {
    return  \Phpcmf\Service::C()->get_cache('var', $name);
}

// 获取自定义目录
function dr_get_dir_path($path) {
    if ((strpos($path, '/') === 0 || strpos($path, ':') !== false)) {
        // 相对于根目录
        return rtrim($path, DIRECTORY_SEPARATOR).'/';
    } else {
        // 在当前网站目录
        return ROOTPATH.trim($path, '/').'/';
    }
}

// 缩略图路径和url
function dr_thumb_path() {

    $config = \Phpcmf\Service::C()->get_cache('site', SITE_ID, 'image');
    if (!$config['cache_path'] || !$config['cache_url']) {
        return [ROOTPATH.'uploadfile/thumb/', ROOT_URL.'uploadfile/thumb/'];
    }

    if ((strpos($config['cache_path'], '/') === 0 || strpos($config['cache_path'], ':') !== false) && is_dir($config['cache_path'])) {
        // 相对于根目录
        return [rtrim($config['cache_path'], DIRECTORY_SEPARATOR).'/', trim($config['cache_url'], '/').'/'];
    } else {
        // 在当前网站目录
        return [ROOTPATH.trim($config['cache_path'], '/').'/', ROOT_URL.trim($config['cache_path'], '/').'/'];
    }
}

// 缩略图
function dr_thumb($img, $width = 200, $height = 200, $water = 0, $mode = 'auto') {

    if (is_numeric($img)) {

        list($cache_path, $cache_url) = dr_thumb_path();

        // 图片缩略图文件
        $cache_file = md5($img).'/'.$width.'x'.$height.($water ? '_water' : '').'_'.$mode.'.jpg';
        if (is_file($cache_path.$cache_file)) {
            return $cache_url.$cache_file;
        }

        return \Phpcmf\Service::L('image')->thumb($img, $width, $height, $water, $mode);
    }

    $file = dr_file($img);

    return $file ? $file : ROOT_THEME_PATH.'assets/images/nopic.gif';
}

// 评论名称
function dr_comment_cname($name) {

    if (!$name) {
        return dr_lang('评论');
    }

    return dr_lang($name);
}

/**
 * 文件真实地址
 *
 * @param	string	$id
 * @return  array
 */
function dr_get_file($id) {

    if (!$id) {
        return '文件参数没有值';
    }

    if (is_numeric($id)) {
        // 表示附件id
        $info = \Phpcmf\Service::C()->get_attachment($id);
        if ($info['url']) {
            return $info['url'];
        }
    }

    $file = dr_file($id);

    return $file ? $file : $id;
}

/**
 * 文件下载地址
 *
 * @param	string	$id
 * @return  array
 */
function dr_down_file($id) {

    if (!$id) {
        return '文件参数不能为空';
    }

    return ROOT_URL."index.php?s=api&c=file&m=down&id=".urlencode($id);
}

/**
 * 根据附件信息获取文件地址
 *
 * @param	array	$data
 * @return  string
 */
function dr_get_file_url($data, $w = 0, $h = 0) {

    if (!$data) {
        return '文件信息不存在';
    } elseif ($data['remote'] && ($info = \Phpcmf\Service::C()->get_cache('attachment', $data['remote']))) {
        return $info['url'].$data['attachment'];
    } elseif ($w && $h && in_array($data['fileext'], ['jpg', 'gif', 'png', 'jpeg'])) {
		return dr_thumb($data['id'], $w, $h);
	}

    return SYS_UPLOAD_URL.$data['attachment'];
}

/**
 * 任意字段的选项值（用于options参数的字段，如复选框、下拉选择框、单选按钮）
 *
 * @param	intval	$id
 * @return	array
 */
function dr_field_options($id) {

    if (!$id) {
        return NULL;
    }

    $data = \Phpcmf\Service::L('cache')->get_data('field-info-'.$id);
    if (!$data) {
        $field = \Phpcmf\Service::C()->get_cache('table-field', $id);
        if (!$field) {
            return NULL;
        }
        $data = dr_format_option_array($field['setting']['option']['options']);
        if (!$data) {
            return NULL;
        }
        // 存储缓存
        \Phpcmf\Service::L('cache')->set_data('field-info-'.$id, $data, 10000);
    }

    return $data;
}

// 提醒说明
function dr_notice_info() {

    return [

        1 => [
            'name' => dr_lang('系统'),
            'icon' => '<i class="fa fa-bell-o"></i>',
        ],
        2 => [
            'name' => dr_lang('用户'),
            'icon' => '<i class="fa fa-user"></i>',
        ],
        3 => [
            'name' => dr_lang('内容'),
            'icon' => '<i class="fa fa-th-large"></i>',
        ],
        4 => [
            'name' => dr_lang('应用'),
            'icon' => '<i class="fa fa-puzzle-piece"></i>',
        ],
        5 => [
            'name' => dr_lang('交易'),
            'icon' => '<i class="fa fa-rmb"></i>',
        ],
        6 => [
            'name' => dr_lang('订单'),
            'icon' => '<i class="fa fa-shopping-cart"></i>',
        ],

    ];
}

/**
 * 验证用户权限
 * my	我的authid
 * auth 目标权限组
 * return 1有权限 0无权限
 */
function dr_member_auth($my, $auth) {

    if (!$auth) {
        // 如果目标没有配置权限,那么返回1
        return 1;
    }

    // 返回权限
    $rt = 0;

    // 默认权限视为游客
    !$my && $my = [0];
    foreach ($my as $id) {
        if (!in_array($id, $auth)) {
            // 表示有权限
            $rt = 1;
        }
    }

    return $rt;
}

/**
 * 用于用户权限取取反值
 */
function dr_member_auth_id($authid, $postid) {

    if (!$authid) {
        // 主角都为空了,表示没有数据
        return [];
    } elseif (!$postid) {
        // 如果提交过来的id都为空,表示应该全部选择
        return $authid;
    } elseif (dr_count($authid) == dr_count($postid)) {
        // 如果两个等,表示全部选中提交过来的,我们返回空
        return [];
    }

    $rt = [];
    // 剩下的情况就是逐一比较
    foreach ($authid as $id) {
        if (!in_array($id, $postid)) {
            $rt[] = $id;
        }
    }

    return $rt;
}

// 微信端的错误码转中文解释
function dr_weixin_error_msg($code) {
    $msg = array (
        '-1' => '系统繁忙，此时请开发者稍候再试',
        '0' => '请求成功',
        '40001' => '获取access_token时AppSecret错误，或者access_token无效。',
        '40002' => '不合法的凭证类型',
        '40003' => '不合法的OpenID，请开发者确认OpenID（该用户）是否已关注公众号，或是否是其他公众号的OpenID',
        '40004' => '不合法的媒体文件类型',
        '40005' => '不合法的文件类型',
        '40006' => '不合法的文件大小',
        '40007' => '不合法的媒体文件id',
        '40008' => '不合法的消息类型',
        '40009' => '不合法的图片文件大小',
        '40010' => '不合法的语音文件大小',
        '40011' => '不合法的视频文件大小',
        '40012' => '不合法的缩略图文件大小',
        '40013' => '不合法的AppID，请开发者检查AppID的正确性，避免异常字符，注意大小写',
        '40014' => '不合法的access_token，请开发者认真比对access_token的有效性（如是否过期），或查看是否正在为恰当的公众号调用接口',
        '40015' => '不合法的菜单类型',
        '40016' => '不合法的按钮个数',
        '40017' => '不合法的按钮个数',
        '40018' => '不合法的按钮名字长度',
        '40019' => '不合法的按钮KEY长度',
        '40020' => '不合法的按钮URL长度',
        '40021' => '不合法的菜单版本号',
        '40022' => '不合法的子菜单级数',
        '40023' => '不合法的子菜单按钮个数',
        '40024' => '不合法的子菜单按钮类型',
        '40025' => '不合法的子菜单按钮名字长度',
        '40026' => '不合法的子菜单按钮KEY长度',
        '40027' => '不合法的子菜单按钮URL长度',
        '40028' => '不合法的自定义菜单使用用户',
        '40029' => '不合法的oauth_code',
        '40030' => '不合法的refresh_token',
        '40031' => '不合法的openid列表',
        '40032' => '不合法的openid列表长度',
        '40033' => '不合法的请求字符，不能包含\uxxxx格式的字符',
        '40035' => '不合法的参数',
        '40038' => '不合法的请求格式',
        '40039' => '不合法的URL长度',
        '40050' => '不合法的分组id',
        '40051' => '分组名字不合法',
        '40117' => '分组名字不合法',
        '40118' => 'media_id大小不合法',
        '40119' => 'button类型错误',
        '40120' => 'button类型错误',
        '40121' => '不合法的media_id类型',
        '40132' => '微信号不合法',
        '40137' => '不支持的图片格式',
        '41001' => '缺少access_token参数',
        '41002' => '缺少appid参数',
        '41003' => '缺少refresh_token参数',
        '41004' => '缺少secret参数',
        '41005' => '缺少多媒体文件数据',
        '41006' => '缺少media_id参数',
        '41007' => '缺少子菜单数据',
        '41008' => '缺少oauth code',
        '41009' => '缺少openid',
        '42001' => 'access_token超时，请检查access_token的有效期，请参考基础支持-获取access_token中，对access_token的详细机制说明',
        '42002' => 'refresh_token超时',
        '42003' => 'oauth_code超时',
        '43001' => '需要GET请求',
        '43002' => '需要POST请求',
        '43003' => '需要HTTPS请求',
        '43004' => '需要接收者关注',
        '43005' => '需要好友关系',
        '44001' => '多媒体文件为空',
        '44002' => 'POST的数据包为空',
        '44003' => '图文消息内容为空',
        '44004' => '文本消息内容为空',
        '45001' => '多媒体文件大小超过限制',
        '45002' => '消息内容超过限制',
        '45003' => '标题字段超过限制',
        '45004' => '描述字段超过限制',
        '45005' => '链接字段超过限制',
        '45006' => '图片链接字段超过限制',
        '45007' => '语音播放时间超过限制',
        '45008' => '图文消息超过限制',
        '45009' => '接口调用超过限制',
        '45010' => '创建菜单个数超过限制',
        '45015' => '回复时间超过限制',
        '45016' => '系统分组，不允许修改',
        '45017' => '分组名字过长',
        '45018' => '分组数量超过上限',
        '46001' => '不存在媒体数据',
        '46002' => '不存在的菜单版本',
        '46003' => '不存在的菜单数据',
        '46004' => '不存在的用户',
        '47001' => '解析JSON/XML内容错误',
        '48001' => 'api功能未授权，请确认公众号已获得该接口，可以在公众平台官网-开发者中心页中查看接口权限',
        '50001' => '用户未授权该api',
        '50002' => '用户受限，可能是违规后接口被封禁',
        '61451' => '参数错误(invalid parameter)',
        '61452' => '无效客服账号(invalid kf_account)',
        '61453' => '客服帐号已存在(kf_account exsited)',
        '61454' => '客服帐号名长度超过限制(仅允许10个英文字符，不包括@及@后的公众号的微信号)(invalid kf_acount length)',
        '61455' => '客服帐号名包含非法字符(仅允许英文+数字)(illegal character in kf_account)',
        '61456' => '客服帐号个数超过限制(10个客服账号)(kf_account count exceeded)',
        '61457' => '无效头像文件类型(invalid file type)',
        '61450' => '系统错误(system error)',
        '61500' => '日期格式错误',
        '61501' => '日期范围错误',
        '9001001' => 'POST数据参数不合法',
        '9001002' => '远端服务不可用',
        '9001003' => 'Ticket不合法',
        '9001004' => '获取摇周边用户信息失败',
        '9001005' => '获取商户信息失败',
        '9001006' => '获取OpenID失败',
        '9001007' => '上传文件缺失',
        '9001008' => '上传素材的文件类型不合法',
        '9001009' => '上传素材的文件尺寸不合法',
        '9001010' => '上传失败',
        '9001020' => '帐号不合法',
        '9001021' => '已有设备激活率低于50%，不能新增设备',
        '9001022' => '设备申请数不合法，必须为大于0的数字',
        '9001023' => '已存在审核中的设备ID申请',
        '9001024' => '一次查询设备ID数量不能超过50',
        '9001025' => '设备ID不合法',
        '9001026' => '页面ID不合法',
        '9001027' => '页面参数不合法',
        '9001028' => '一次删除页面ID数量不能超过10',
        '9001029' => '页面已应用在设备中，请先解除应用关系再删除',
        '9001030' => '一次查询页面ID数量不能超过50',
        '9001031' => '时间区间不合法',
        '9001032' => '保存设备与页面的绑定关系参数错误',
        '9001033' => '门店ID不合法',
        '9001034' => '设备备注信息过长',
        '9001035' => '设备申请参数不合法',
        '9001036' => '查询起始值begin不合法'
    );

    return $msg[$code] ? $msg[$code] : $code ;
}

// 从url获取json数据
function wx_get_https_json_data($url) {

    if (!$url) {
        return dr_return_data(0, 'url为空');
    }

    $response = file_get_contents($url);
    if (!$response) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // 跳过证书检查
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, true); // 从证书中检查SSL加密算法是否存在
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        $response = curl_exec($ch);
        if ($error=curl_error($ch)){
            return dr_return_data(0, $error);
        }
        curl_close($ch);
    }

    $data = json_decode($response, true);
    if (!$data) {
        return dr_return_data(0, $response.' 不是一个有效的json数据');
    } elseif (isset($data['errcode']) && $data['errcode']) {
        return dr_return_data(0, '错误代码（'.$data['errcode'].'）：'.$data['errmsg']);
    }

    return dr_return_data(1, 'ok', $data);
}

// 从url获取json数据
function wx_post_https_json_data($url, $param = []) {

    if (!$url) {
        return dr_return_data(0, 'url为空');
    }

    $ch = curl_init ();
    curl_setopt ( $ch, CURLOPT_URL, $url );
    curl_setopt ( $ch, CURLOPT_CUSTOMREQUEST, "POST" );
    curl_setopt ( $ch, CURLOPT_SSL_VERIFYPEER, FALSE );
    curl_setopt ( $ch, CURLOPT_SSL_VERIFYHOST, FALSE );
    curl_setopt ( $ch, CURLOPT_USERAGENT, 'Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0)' );
    curl_setopt ( $ch, CURLOPT_FOLLOWLOCATION, 1 );
    curl_setopt ( $ch, CURLOPT_AUTOREFERER, 1 );
    curl_setopt ( $ch, CURLOPT_POSTFIELDS, json_encode($param, JSON_UNESCAPED_UNICODE));
    curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, true );
    $response = curl_exec ( $ch );
    if ($error=curl_error($ch)){
        return dr_return_data(0, $error);
    }
    curl_close($ch);
    $data = json_decode($response, true);
    if (!$data) {
        return dr_return_data(0, $response);
    } elseif (isset($data['errcode']) && $data['errcode']) {
        return dr_return_data(0, '错误代码（'.dr_weixin_error_msg($data['errcode']).'）：'.$data['errmsg']);
    }

    return dr_return_data(1, 'ok', $data);
}


/**
 * 获取折扣价格值
 */
function dr_zhe_price($value, $zhe) {
    return max(0, $value * ($zhe/100));
}

/**
 * 获取价格值
 */
function dr_price_value($value, $num = 2) {
    return number_format($value, $num);
}

/**
 * sku 获取属性值名称
 */
function dr_sku_value_name($value, $sku, $name) {

    if (!$value) {
        return '字段值不存在';
    } elseif (!$sku) {
        return 'sku不能为空';
    } elseif (!$name) {
        return 'name不能为空';
    }

    $value = dr_string2array($value);
    if (!$value['value']) {
        return 'sku格式不正确';
    } elseif (!$value['value'][$sku]) {
        return 'sku值不存在';
    }

    return $value['value'][$sku][$name];
}

/**
 * sku 价格信息
 */
function dr_sku_price($value, $number = 2, $join = ' - ', $zhe = 0) {

    $value = dr_string2array($value);
    if (!$value || !is_array($value['value'])) {
        return 0;
    }

    $price = [];
    foreach ($value['value'] as $t) {
        $price[] = $t['price'];
    }

    $min = min($price);
    $max = max($price);
    if ($zhe) {
        $min = dr_zhe_price($min, $zhe);
        $max = dr_zhe_price($max, $zhe);
    }

    if ($min == $max) {
        return number_format($min, $number);
    } else {
        return number_format($min, $number).$join.number_format($max, $number);
    }
}

/**
 * sku获取名称
 */
function dr_sku_name($key, $data, $type = 0) {

    if (!$key || !is_array($data)) {
        return $type ? ['', ''] : '';
    }

    $sku = [];
    $arr = explode('_', $key);
    foreach ($arr as $i => $t) {
        $i%2 == 0 && isset($arr[$i+1]) && $sku[] = $t.'_'.$arr[$i+1];
    }


    $value = [];
    $string = [];
    foreach ($data['group'] as $gid => $gname) {
        foreach ($data['name'][$gid] as $vid => $vname) {
            if (in_array("{$gid}_{$vid}", $sku)) {
                $value[$gname] = $vname;
                $string[] = $gname.'：'.$vname;
            }

        }
    }

    return $type ? [$value, implode(' ', $string)] : implode(' ', $string);
}


/**
 * 下一个升级值
 */
function dr_level_next_value($array, $id) {

    if (!$array) {
        return [];
    }

    // 判断当前是否允许升级
    if ($id && $array[$id] && !$array[$id]['apply']) {
        return []; // 此等级不允许升级
    }

    $next = 0;
    foreach ($array as $i => $v) {
        if (!$id) {
            return $v;
        }
        if ($i == $id) {
            $next = 1;
            continue;
        }
        if ($next && $v['apply']) {
            return $v;
        }
    }

    return [];
}

/**
 * 静态生成时权限认证字符(加密)
 */
function dr_html_auth($is = 0) {

    $file = WRITEPATH.'authcode/'.md5($_SERVER['HTTP_USER_AGENT']).'.auth';
    if ($is) {
        // 存储值
        return file_put_contents($file, SYS_TIME);
    } else {
        // 读取判断
        $time = (int)file_get_contents($file);
        if (SYS_TIME - $time <= 10000) {
            return 1; // 3小时有效
        } else {
            return 0;
        }
    }
}

// 提醒图标
function dr_notice_icon($type, $c = '') {

    switch ($type) {

        case 1:
            return '<span class="label label-sm label-danger '.$c.'">
                                <i class="fa fa-bell-o"></i> 
                            </span>';
            break;

        case 2:
            return '<span class="label label-sm label-info '.$c.'">
                                <i class="fa fa-user"></i>
                            </span>';
            break;

        case 3:
            return '<span class="label label-sm label-success '.$c.'">
                                <i class="fa fa-th-large"></i>
                            </span>';
            break;

        case 4:
            return '<span class="label label-sm label-default '.$c.'">
                                <i class="fa fa-puzzle-piece"></i>
                            </span>';
            break;

        case 5:
            return '<span class="label label-sm label-warning '.$c.'">
                                <i class="fa fa-rmb"></i>
                            </span>';
            break;

        case 6:
            return '<span class="label label-sm label-success '.$c.'">
                                <i class="fa fa-shopping-cart"></i>
                            </span>';
            break;

    }

}

/**
 * 付款方式显示
 */
function dr_pay_type_html($name) {
    return dr_pay_name($name);
}

/**
 * 付款名称
 */
function dr_pay_name($name) {
    return \Phpcmf\Service::M('Pay')->payname($name);
}

/**
 * 付款方式的名称
 */
function dr_pay_type($name) {
    return dr_clearhtml(\Phpcmf\Service::M('Pay')->paytype($name));
}

/**
 * 付款状态的名称
 */
function dr_pay_status($data) {
    return dr_clearhtml(\Phpcmf\Service::M('Pay')->paystatus($data));
}

/**
 * 付款金额显示
 */
function dr_pay_money_html($data, $v = 2) {

    $html = '<span class="fc-pay-money ';

    if ($data > 0) {
        $html.= ' fc-pay-z">+ '.number_format($data, $v);
    } else {
        $html.= ' fc-pay-j">- '.number_format(abs($data), $v);
    }

    $html.= '</span>';

    return $html;
}


/**
 * 清除空白字符
 */
function dr_clear_empty($value) {
    return str_replace(['　', ' '], '', trim($value));
}

/**
 * 列表字段进行排序筛选
 */
function dr_list_field_order($field) {

    if (!$field) {
        return [];
    }

    $rt = [];
    foreach ($field as $name => $m) {
        $m['use'] && $rt[$name] = $m;
    }
	
    return $rt;
}

function dr_list_field_value($value, $sys_field, $field) {
	
	foreach ($field as $t) {
		$sys_field[$t['fieldname']] = $t;
	}
	
	$rt = [];
	foreach ($value as $name => $t) {
        if ($t['name']) {
            $rt[$name] = $sys_field[$name];
            unset($sys_field[$name]);
        }
	}
	
	if (!$sys_field) {
		return $rt;
	}
	
	foreach ($sys_field as $name => $t) {
		$rt[$name] = $t;
	}
	
	return $rt;
}

/**
 * 格式化搜索关键词参数
 */
function dr_get_keyword($s) {
    return dr_safe_replace(str_replace(['+', ' '], '%', urldecode($s)));
}

/**
 * 两数组追加合并
 */
function dr_array2array($a1, $a2) {

    $a = [];
    $a = $a1 ? $a1 : $a;
    if ($a2) {
        foreach ($a2 as $t) {
            $a[] = $t;
        }
    }

    return $a;
}

/**
 * 两数组覆盖合并，1是老数据，2是新数据
 */
function dr_array22array($a1, $a2) {

    $a = [];
    $a = $a1 ? $a1 : $a;
    if ($a2) {
        foreach ($a2 as $i => $t) {
            $a[$i] = $t;
        }
    }

    return $a;
}


/**
 * 模块表前缀
 */
function dr_module_table_prefix($dir, $siteid = SITE_ID) {
    return $siteid.'_'.$dir;;
}

/**
 * 返回图标
 */
function dr_icon($value) {
    return $value ? $value : 'fa fa-table';
}

/**
 * 完整的文件路径
 *
 * @param	string	$url
 * @return  string
 */
function dr_file($url) {

    if (!$url || strlen($url) == 1) {
        return NULL;
    } elseif (substr($url, 0, 7) == 'http://' || substr($url, 0, 8) == 'https://') {
        return $url;
    } elseif (substr($url, 0, 1) == '/') {
        return ROOT_URL.substr($url, 1);
    }

    return SYS_UPLOAD_URL . $url;
}

/**
 * 根据文件扩展名获取文件预览信息
 */
function dr_file_preview_html($value, $target = 0) {

    $ext = trim(strtolower(strrchr($value, '.')), '.');
    if (in_array($ext, ['jpg', 'gif', 'png', 'jpeg'])) {
        $value = dr_file($value);
        $url = $target ? $value.'" target="_blank' : 'javascript:dr_preview_image(\''.$value.'\');';
        return '<a href="'.$url.'"><img src="'.$value.'"></a>';
    } elseif (is_file(ROOTPATH.'static/assets/images/ext/'.$ext.'.png')) {
        $file = ROOT_THEME_PATH.'assets/images/ext/'.$ext.'.png';
        $url = $target ? $value.'" target="_blank' : 'javascript:dr_preview_url(\''.dr_file($value).'\');';
        return '<a href="'.$url.'"><img src="'.$file.'"></a>';
    } elseif (strpos($value, 'http://') === 0) {
        $file = ROOT_THEME_PATH.'assets/images/ext/url.png';
        $url = $target ? $value.'" target="_blank' : 'javascript:dr_preview_url(\''.$value.'\');';
        return '<a href="'.$url.'"><img src="'.$file.'"></a>';
    } else {
        return $value;
    }
}

// 文件是否是图片
function dr_is_image($value) {
	return in_array(trim(strtolower(strrchr($value, '.')), '.'), ['jpg', 'gif', 'png', 'jpeg']);
}

/**
 * 单页层次关系
 *
 * @param	intval	$id
 * @param	string	$symbol
 * @return	string
 */
function dr_get_page_pname($id, $symbol = '_', $page) {

    if (!$page[$id]['pids']) {
        return $page[$id]['name'];
    }

    $name = [];
    $array = explode(',', $page[$id]['pids']);

    foreach ($array as $i) {
        $i && $page[$i] && $name[] = $page[$i]['name'];
    }

    $name[] = $page[$id]['name'];

    $name = array_unique($name);

    krsort($name);

    return implode($symbol, $name);
}


/**
 * 生成静态文件的错误信息
 */
function dr_to_html_file_error($url, $file, $msg) {
    $error = '**************************************************************************'.PHP_EOL;
    $error.= $msg.''.PHP_EOL;
    $url && $error.= '地址: '.$url.''.PHP_EOL;
    $file && $error.= '文件: '.$file.''.PHP_EOL;
    $error.= '**************************************************************************'.PHP_EOL.PHP_EOL;
    file_put_contents(WRITEPATH.'html/error.log', $error, FILE_APPEND);
}


/**
 * 生成静态文件名
 */
function dr_to_html_file($url, $root = WEBPATH) {

    if (strpos($url, 'http://') === 0 || strpos($url, 'https://') === 0) {
        return '';
    } elseif (strpos($url, 'index.php') !== false) {
        return '';
    }

    return dr_format_html_file($url, $root);
}


/**
 * 格式化复选框\单选框\选项值 字符串转换为数组
 */
function dr_format_option_array($value) {

    $data = [];

    if (!$value) {
        return $data;
    }

    $options = explode(PHP_EOL, str_replace(array(chr(13), chr(10)), PHP_EOL, $value));

    foreach ($options as $t) {
        if (strlen($t)) {
            $n = $v = '';
            if (strpos($t, '|') !== FALSE) {
                list($n, $v) = explode('|', $t);
                $v = is_null($v) || !strlen($v) ? '' : trim($v);
            } else {
                $v = $n = trim($t);
            }
            $data[$v] = $n;
        }
    }

    return $data;
}


/**
 * 字段输出表单
 *
 * @param	string	$username
 * @return  intval
 */
function dr_field_input($name, $type, $option, $value = NULL, $id = 0) {
    return '';
}

/**
 * 目录扫描
 *
 * @param	string	$source_dir		Path to source
 * @param	int	$directory_depth	Depth of directories to traverse
 *						(0 = fully recursive, 1 = current dir, etc)
 * @param	bool	$hidden			Whether to show hidden files
 * @return	array
 */
function dr_dir_map($source_dir, $directory_depth = 0, $hidden = FALSE) {

    if ($fp = @opendir($source_dir)) {

        $filedata = [];
        $new_depth = $directory_depth - 1;
        $source_dir	= rtrim($source_dir, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;

        while (FALSE !== ($file = readdir($fp))) {
            if ($file === '.' OR $file === '..'
                OR ($hidden === FALSE && $file[0] === '.')
                OR !@is_dir($source_dir.$file)) {
                continue;
            }
            if (($directory_depth < 1 OR $new_depth > 0)
                && @is_dir($source_dir.$file)) {
                $filedata[$file] = dr_dir_map($source_dir.DIRECTORY_SEPARATOR.$file, $new_depth, $hidden);
            } else {
                $filedata[] = $file;
            }
        }
        closedir($fp);
        return $filedata;
    }

    return FALSE;
}

/**
 * 目录扫描
 *
 * @param	string	$source_dir		Path to source
 * @param	int	$directory_depth	Depth of directories to traverse
 *						(0 = fully recursive, 1 = current dir, etc)
 * @param	bool	$hidden			Whether to show hidden files
 * @return	array
 */
function dr_file_map($source_dir) {

    if ($fp = @opendir($source_dir)) {

        $filedata = [];
        $source_dir	= rtrim($source_dir, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;

        while (FALSE !== ($file = readdir($fp))) {
            if ($file === '.' OR $file === '..'
                OR $file[0] === '.'
                OR !@is_file($source_dir.$file)) {
                continue;
            }
            $filedata[] = $file;
        }
        closedir($fp);
        return $filedata;
    }

    return FALSE;
}

/**
 * 数据返回统一格式
 */
function dr_return_data($code, $msg = '', $data = []) {
    return array(
        'code' => $code,
        'msg' => $msg,
        'data' => $data,
    );
}

/**
 * 提交表单默认隐藏域
 */
function dr_form_hidden($data = []) {

    $form = '<input name="isform" type="hidden" value="1">'.PHP_EOL;
    $form.= '<input name="'.csrf_token().'" type="hidden" value="'.csrf_hash().'">'.PHP_EOL;
    if ($data) {
        foreach ($data as $name => $value) {
            $form.= '<input name="'.$name.'" id="dr_'.$name.'" type="hidden" value="'.$value.'">'.PHP_EOL;
        }
    }

    return $form;
}

// 验证字符串
function dr_get_csrf_token() {
    return md5(csrf_token().csrf_hash());
}

/**
 * 搜索表单隐藏域
 */
function dr_form_search_hidden($p = []) {

    $form = '';
    $_GET['app'] && $form.= '<input name="app" type="hidden" value="'.$_GET['app'].'">'.PHP_EOL;
    $form.= '<input name="s" type="hidden" value="'.$_GET['s'].'">'.PHP_EOL;
    $form.= '<input name="m" type="hidden" value="'.$_GET['m'].'">'.PHP_EOL;
    $form.= '<input name="c" type="hidden" value="'.$_GET['c'].'">'.PHP_EOL;
    if ($p) {
        foreach ($p as $name => $value) {
            $form.= '<input name="'.$name.'" type="hidden" value="'.$value.'">'.PHP_EOL;
        }
    }

    return $form;
}

/**
 * Base64加密
 *
 * @param	string	$string
 * @return	string
 */
function dr_base64_encode($string) {
    $data = base64_encode($string);
    $data = str_replace(['+', '/', '='], ['-', '_', ''], $data);
    return $data;
}

/**
 * Base64解密
 *
 * @param	string	$string
 * @return	string
 */
function dr_base64_decode($string) {
    $data = str_replace(['-', '_'], ['+', '/'], $string);
    $mod4 = strlen($data) % 4;
    $mod4 && $data.= substr('====', $mod4);
    return base64_decode($data);
}



/**
 * 网站风格目录
 *
 * @return	string|NULL
 */
function dr_get_theme() {

    if (!function_exists('dr_dir_map')) {
        return ['default'];
    }

    return array_diff(dr_dir_map(ROOTPATH.'static/', 1), ['assets', 'space']);
}


/**
 * 获取6位数字随机验证码
 */
function dr_randcode() {
    return rand(100000, 999999);
}

/**
 * 删除目录及目录下面的所有文件
 *
 * @param	string	$dir		路径
 * @param	string	$is_all		包括删除当前目录
 * @return	bool	如果成功则返回 TRUE，失败则返回 FALSE
 */

function dr_dir_delete($path, $del_dir = FALSE, $htdocs = FALSE, $_level = 0)
{
    // Trim the trailing slash
    $path = rtrim($path, '/\\');

    if ( ! $current_dir = @opendir($path))
    {
        return FALSE;
    }

    while (FALSE !== ($filename = @readdir($current_dir)))
    {
        if ($filename !== '.' && $filename !== '..')
        {
            $filepath = $path.DIRECTORY_SEPARATOR.$filename;

            if (is_dir($filepath) && $filename[0] !== '.' && ! is_link($filepath))
            {
                dr_dir_delete($filepath, $del_dir, $htdocs, $_level + 1);
            } else {
                @unlink($filepath);
            }
        }
    }

    closedir($current_dir);
    $_level > 0  && @rmdir($path); // 删除子目录

    return $del_dir && $_level == 0 ? @rmdir($path) : TRUE;
}

// 评论表情
function dr_comment_emotion() {

    // 可用表情
    $emotion = [];
    if ($fp = @opendir(ROOTPATH.'static/assets/comment/emotions/')) {
        while (FALSE !== ($file = readdir($fp))) {
            $info = pathinfo($file);
            @in_array($info['extension'], ['gif', 'png', 'jpg', 'jpeg']) && $emotion[$info['filename']] = ROOT_THEME_PATH.'assets/comment/emotions/'.$file;
        }
    }

    return $emotion;
}

/**
 * 基于本地存储的加解密算法
 */
function dr_authcode($string, $operation = 'DECODE') {

    if (!$string) {
        return '';
    }

    is_array($string) && $string = dr_array2string($string);

    $code_path = WRITEPATH.'authcode/';
    dr_mkdirs($code_path);

    if ($operation == 'DECODE') {
        // 解密
        $code_file = $code_path.dr_safe_filename($string);
        if (is_file($code_file)) {
            $rt = file_get_contents($code_file);
            if ($rt) {
                return $rt;
            }
        }
        return dr_dz_authcode($string, $operation);
    } else {
        // 加密
        dr_mkdirs($code_path);
        $rt = file_put_contents($code_path.md5($string), $string, LOCK_EX);
        if (!$rt) {
            return dr_dz_authcode($string, $operation);
        }
        return md5($string);
    }

}

// 传统算法
function dr_dz_authcode($string, $operation = 'DECODE') {

    $string = str_replace(' ', '+', $string);

    $expiry = 3600;
    $ckey_length = 4;

    $key = md5(SYS_KEY);
    $keya = md5(substr($key, 0, 16));
    $keyb = md5(substr($key, 16, 16));
    $keyc = $ckey_length ? ($operation == 'DECODE' ? substr($string, 0, $ckey_length) : substr(md5(microtime()), -$ckey_length)) : '';

    $cryptkey = $keya . md5($keya . $keyc);
    $key_length = strlen($cryptkey);

    $string = $operation == 'DECODE' ? base64_decode(substr($string, $ckey_length)) : sprintf('%010d', $expiry ? $expiry + time() : 0) . substr(md5($string . $keyb), 0, 16) . $string;
    $string_length = strlen($string);

    $result = '';
    $box = range(0, 255);

    $rndkey = [];
    for ($i = 0; $i <= 255; $i++) {
        $rndkey[$i] = ord($cryptkey[$i % $key_length]);
    }

    for ($j = $i = 0; $i < 256; $i++) {
        $j = ($j + $box[$i] + $rndkey[$i]) % 256;
        $tmp = $box[$i];
        $box[$i] = $box[$j];
        $box[$j] = $tmp;
    }

    for ($a = $j = $i = 0; $i < $string_length; $i++) {
        $a = ($a + 1) % 256;
        $j = ($j + $box[$a]) % 256;
        $tmp = $box[$a];
        $box[$a] = $box[$j];
        $box[$j] = $tmp;
        $result.= chr(ord($string[$i]) ^ ($box[($box[$a] + $box[$j]) % 256]));
    }

    if ($operation == 'DECODE') {
        if ((substr($result, 0, 10) == 0 || substr($result, 0, 10) - time() > 0) && substr($result, 10, 16) == substr(md5(substr($result, 26) . $keyb), 0, 16)) {
            return substr($result, 26);
        } else {
            return '';
        }
    } else {
        return $keyc . str_replace('=', '', base64_encode($result));
    }
}


/**
 * 当前URL
 */
function dr_now_url() {
    return FC_NOW_URL;
}


/**
 * 验证码图片获取
 */
function dr_code($width, $height, $url = '') {
    $url = '/index.php?s=api&c=api&m=captcha&width='.$width.'&height='.$height;
    return '<img align="absmiddle" style="cursor:pointer;" onclick="this.src=\''.$url.'&\'+Math.random();" src="'.$url.'" />';
}

/**
 * 排序操作
 */
function dr_sorting($name) {

    $value = $_GET['order'] ? $_GET['order'] : '';
    if (!$value) {
        return 'order_sorting';
    }

    if (strpos($value, $name) === 0 && strpos($value, 'asc') !== FALSE) {
        return 'order_sorting_asc';
    } elseif (strpos($value, $name) === 0 && strpos($value, 'desc') !== FALSE) {
        return 'order_sorting_desc';
    }

    return 'order_sorting';
}

/**
 * 移除order字符串
 */
function dr_member_order($url) {

    $data = @explode('&', $url);
    if ($data) {
        foreach ($data as $t) {
            if (strpos($t, 'order=') === 0) {
                $url = str_replace('&' . $t, '', $url);
            } elseif (strpos($t, 'action=') === 0) {
                $url = str_replace('&' . $t, '', $url);
            }
        }
    }

    return $url;
}

/**
 * 百度地图定位浏览器坐标
 */
function dr_baidu_position_js($ak = SYS_BDMAP_API) {
	$code = \Phpcmf\Service::V()->load_js((strpos(FC_NOW_URL, 'https') === 0 ? 'https' : 'http').'://api.map.baidu.com/api?v=2.0&ak='.$ak);
	$code.= '<script type="text/javascript">';
	$code.= '// 百度地图定位坐标
   var geolocation = new BMap.Geolocation();
   geolocation.getCurrentPosition(function(r){
      if(this.getStatus() == BMAP_STATUS_SUCCESS){
         $.ajax({type: "GET", url: "/index.php?s=api&c=api&m=baidu_position&value="+r.point.lng+\',\'+r.point.lat, dataType:"jsonp"});
      } else {
         alert(\'定位失败：\'+this.getStatus());
      }
   },{enableHighAccuracy: true});';
	$code.= '</script>';
	return $code;
}

/**
 * 百度地图定位浏览器坐标并设置为隐藏表单域
 */
function dr_baidu_map_form_hidden($field, $ak = SYS_BDMAP_API) {
	$code = \Phpcmf\Service::V()->load_js((strpos(FC_NOW_URL, 'https') === 0 ? 'https' : 'http').'://api.map.baidu.com/api?v=2.0&ak='.$ak);
	$code.= '<input type="hidden" id="dr_'.$field.'" name="data['.$field.']" value=""><script type="text/javascript">';
	$code.= '<script type="text/javascript">';
	$code.= '// 百度地图定位坐标
   var geolocation = new BMap.Geolocation();
   geolocation.getCurrentPosition(function(r){
      if(this.getStatus() == BMAP_STATUS_SUCCESS){
		 $("#dr_'.$field.'").val(r.point.lng+\',\'+r.point.lat);
         $.ajax({type: "GET", url: "/index.php?s=api&c=api&m=baidu_position&value="+r.point.lng+\',\'+r.point.lat, dataType:"jsonp"});
      } else {
         alert(\'定位失败：\'+this.getStatus());
      }
   },{enableHighAccuracy: true});';
	$code.= '</script>';
	return $code;
}


/**
 * 百度地图JS
 */
function dr_baidu_map_js($ak = SYS_BDMAP_API) {
	return \Phpcmf\Service::V()->load_js((strpos(FC_NOW_URL, 'https') === 0 ? 'https' : 'http').'://api.map.baidu.com/api?v=2.0&ak='.$ak);
}

/**
 * 百度地图调用
 */
function dr_baidu_map($value, $zoom = 15, $width = 600, $height = 400, $ak = SYS_BDMAP_API, $class= '', $tips = '') {

    if (!$value) {
        return '没有坐标值';
    }

    $id = 'dr_map_'.rand(0, 99);
    !$ak && $ak = SYS_BDMAP_API;
    $width = $width ? $width : '100%';
    list($lngX, $latY) = explode(',', $value);

    $js = \Phpcmf\Service::V()->load_js((strpos(FC_NOW_URL, 'https') === 0 ? 'https' : 'http').'://api.map.baidu.com/api?v=2.0&ak='.$ak);

    return $js.'<div class="'.$class.'" id="' . $id . '" style="width:' . $width . 'px; height:' . $height . 'px; overflow:hidden"></div>
	<script type="text/javascript">
	var mapObj=null;
	lngX = "' . $lngX . '";
	latY = "' . $latY . '";
	zoom = "' . $zoom . '";		
	var mapObj = new BMap.Map("'.$id.'");
	var ctrl_nav = new BMap.NavigationControl({anchor:BMAP_ANCHOR_TOP_LEFT,type:BMAP_NAVIGATION_CONTROL_LARGE});
	mapObj.addControl(ctrl_nav);
	mapObj.enableDragging();
	mapObj.enableScrollWheelZoom();
	mapObj.enableDoubleClickZoom();
	mapObj.enableKeyboard();//启用键盘上下左右键移动地图
	mapObj.centerAndZoom(new BMap.Point(lngX,latY),zoom);
	drawPoints();
	function drawPoints(){
		var myIcon = new BMap.Icon("' . ROOT_THEME_PATH . 'assets/images/mak.png", new BMap.Size(27, 45));
		var center = mapObj.getCenter();
		var point = new BMap.Point(lngX,latY);
		var marker = new BMap.Marker(point, {icon: myIcon});
		mapObj.addOverlay(marker);
		'.($tips ? 'mapObj.openInfoWindow(new BMap.InfoWindow("'.str_replace('"', '\'', $tips).'",{offset:new BMap.Size(0,-17)}),point);' : '').'
	}
	</script>';
}

/**
 * 腾讯地图调用
 */
function dr_qq_map($value, $zoom = 10, $width = 600, $height = 400, $ui = 0, $class = '') {

    if (!$value) {
        return '没有坐标值';
    }

    $ui = !$ui ? 'false' : 'true';
    $id = 'dr_qq_map_'.rand(0, 99);
    $width = $width ? $width : '100%';
    list($lngX, $latY) = explode(',', $value);
    $js = \Phpcmf\Service::V()->load_js('http://map.qq.com/api/js?v=2.exp');
    return $js.'<div class="'.$class.'" id="' . $id . '" style="width:' . $width . 'px; height:' . $height . 'px; overflow:hidden"></div>
	<script type="text/javascript">
        var center = new qq.maps.LatLng('.$latY.','.$lngX.');
        var map = new qq.maps.Map(document.getElementById(\''.$id.'\'),{
            center: center,
            disableDefaultUI: '.$ui.',
            zoom: '.$zoom.'
        });
         var anchor = new qq.maps.Point(6, 6),
            size = new qq.maps.Size(27, 45),
            origin = new qq.maps.Point(0, 0),
            icon = new qq.maps.MarkerImage(\'' . ROOT_THEME_PATH . 'assets/images/mak.png\', size, origin, anchor);
        var marker = new qq.maps.Marker({
            icon: icon,
            map: map,
            position:map.getCenter()});
	</script>';
}



/**
 * 显示星星
 *
 * @param	intval	$num
 * @param	intval	$starthreshold	星星数在达到此阈值(设为 N)时，N 个星星显示为 1 个月亮、N 个月亮显示为 1 个太阳。
 * @return	string
 */
function dr_show_stars($num, $starthreshold = 4) {

    $str = '';
    $alt = 'alt="Rank: '.$num.'"';

    for ($i = 3; $i > 0; $i--) {
        $numlevel = intval($num / pow($starthreshold, ($i - 1)));
        $num = ($num % pow($starthreshold, ($i - 1)));
        for ($j = 0; $j < $numlevel; $j++) {
            $str.= '<img align="absmiddle" src="'.ROOT_THEME_PATH.'assets/images/star_level'.$i.'.gif" '.$alt.' />';
        }
    }

    return $str;
}


/**
 * 模块评论js调用
 *
 * @param	intval	$id
 * @return	string
 */
function dr_module_comment($dir, $id) {
    $url = "/index.php?s=".$dir."&c=comment&m=index&id={$id}";
    return "<div id=\"dr_module_comment_{$id}\"></div><script type=\"text/javascript\">
	function dr_ajax_module_comment_{$id}(type, page) {
		var index = layer.load(2, { time: 10000 });
	    $.ajax({type: \"GET\", url: \"{$url}&type=\"+type+\"&page=\"+page+\"&\"+Math.random(), dataType:\"jsonp\",
            success: function (data) {
            	layer.close(index);
            	if (data.code) {
					$(\"#dr_module_comment_{$id}\").html(data.msg);
				} else {
					dr_tips(0, data.msg);
				}
            },
            error: function(HttpRequest, ajaxOptions, thrownError) {
                layer.closeAll();
                alert(\"评论调用函数返回错误：\"+HttpRequest.responseText);
            }
        });
	}
	dr_ajax_module_comment_{$id}(0, 1);
	</script>";
}

/**
 * 动态调用模板
 */
function dr_ajax_template($id, $filename) {
    return "<script type=\"text/javascript\">
		$.ajax({
			type: \"GET\",
			url:\"".ROOT_URL."index.php?s=api&c=api&m=template&name={$filename}\",
			dataType: \"jsonp\",
			success: function(data){
				$(\"#{$id}\").html(data.msg);
			}
		});
    </script>";
}


/**
 * 模块内容阅读量显示js
 *
 * @param	intval	$id
 * @return	string
 */
function dr_show_hits($id, $dom = "") {
    $is = $dom;
    !$dom && $dom = "dr_show_hits_{$id}";
    $html = $is ? "" : "<span id=\"{$dom}\">0</span>";
    return $html."<script type=\"text/javascript\">
		$.ajax({
			type: \"GET\",
			url:\"".ROOT_URL."index.php?s=api&c=module&siteid=".SITE_ID."&app=".MOD_DIR."&m=hits&id={$id}\",
			dataType: \"jsonp\",
			success: function(data){
				if (data.code) {
					$(\"#{$dom}\").html(data.msg);
				} else {
					dr_tips(0, data.msg);
				}
			}
		});
    </script>";
}

/**
 * 模块内容收藏量显示js
 *
 * @param	intval	$id
 * @return	string
 */
function dr_show_module_total($name, $id, $dom) {
    return "<script type=\"text/javascript\">
		$.ajax({
			type: \"GET\",
			url:\"".ROOT_URL."index.php?s=api&c=module&siteid=".SITE_ID."&app=".MOD_DIR."&m=mcount&name={$name}&id={$id}\",
			dataType: \"jsonp\",
			success: function(data){
				if (data.code) {
					$(\"#{$dom}\").html(data.msg);
				} else {
					dr_tips(0, data.msg);
				}
			}
		});
    </script>";
}


/**
 * 调用远程数据
 *
 * @param	string	$url
 * @param	intval	$timeout 超时时间，0不超时
 * @return	string
 */
function dr_catcher_data($url, $timeout = 0) {

    // curl模式
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        if (substr($url, 0, 8) == "https://") {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // 跳过证书检查
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, true); // 从证书中检查SSL加密算法是否存在
        }
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        // 最大执行时间
        $timeout && curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        $data = curl_exec($ch);
        $code = curl_getinfo($ch,CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($code == 200) {
            return $data;
        }
        return '';
    }

    //设置超时参数
    if ($timeout && function_exists('stream_context_create')) {
        // 解析协议
        $opt = [
            'http' => [
                'method'  => 'GET',
                'timeout' => $timeout,
            ],
            'https' => [
                'method'  => 'GET',
                'timeout' => $timeout,
            ]
        ];
        $ptl = substr($url, 0, 8) == "https://" ? 'https' : 'http';
        $data = @file_get_contents($url, 0, stream_context_create([
            $ptl => $opt[$ptl]
        ]));
    } else {
        $data = @file_get_contents($url);
    }

    return $data;
}


/**
 * 伪静态代码处理
 *
 * @param	array	$params	参数数组
 * @param	array	$search	搜索配置
 * @return	string
 */
function dr_search_rewrite_encode($params, $search) {

    if (!$params) {
        return '';
    }

    $join = $search['param_join'];
    !$join && $join = '-';

    if ($search['param_rule']) {
        // 固定模式
        $url = '';
        $default = $search['param_join_default_value'] ? $search['param_join_default_value'] : 0;
        foreach ($search['param_join_field'] as $i => $name) {
            if (!$name) {
                break;
            }
            $v = isset($params[$name]) ? $params[$name] : $default;
            $url.= $join.urlencode($v);
        }

        return trim($url, $join);
    } else {
        // 自由组合
        return dr_rewrite_encode($params, $join, $search['param_field']);
    }
}

/**
 * 伪静态代码转换为数组
 *
 * @param	string	$params	参数字符串
 * @return	array
 */
function dr_search_rewrite_decode($params, $search) {

    if (!$params) {
        return [];
    }

    $join = $search['param_join'];
    !$join && $join = '-';

    if ($search['param_rule']) {
        // 固定模式
        $rt = [];
        $array = explode($join, $params);
        $default = $search['param_join_default_value'] ? $search['param_join_default_value'] : 0;
        foreach ($search['param_join_field'] as $i => $name) {
            if (!$name) {
                break;
            }
            $rt[$name] = !strcasecmp($array[$i], $default) ? '' : $array[$i];
        }

        return $rt;
    } else {
        // 自由组合
        return dr_rewrite_decode($params, $join, $search['param_field']);
    }
}

/**
 * 伪静态代码处理
 *
 * @param	array	$params	参数数组
 * @return	string
 */
function dr_rewrite_encode($params, $join = '-', $field = []) {

    if (!$params) {
        return '';
    }

    !$join && $join = '-';
    $field = array_flip(dr_format_option_array($field));
    $url = '';
    foreach ($params as $i => $t) {
        $i = isset($field[$i]) && $field[$i] ? $field[$i] : $i;
        $url.= $join.$i.$join.urlencode($t);
    }

    return trim($url, $join);
}

/**
 * 伪静态代码转换为数组
 *
 * @param	string	$params	参数字符串
 * @return	array
 */
function dr_rewrite_decode($params, $join = '-', $field = []) {

    if (!$params) {
        return [];
    }

    $field = dr_format_option_array($field);
    !$join && $join = '-';

    $i = 0;
    $array = explode($join, $params);

    $return = [];
    foreach ($array as $k => $t) {
        $name = str_replace('$', '_', $t);
        $name = isset($field[$name]) && $field[$name] ? $field[$name] : $name;
        $i%2 == 0 && $return[$name] = isset($array[$k+1]) ? $array[$k+1] : '';
        $i ++;
    }

    return $return;
}

/**
 * 安全过滤函数
 */
function dr_safe_replace($string, $diy = null) {

    $replace = ['%20', '%27', '%2527', '*', "'", '"', ';', '<', '>', "{", '}'];
    $diy && is_array($diy) && $replace = dr_array2array($replace, $diy);
    $diy && !is_array($diy) && $replace[] = $diy;

    return str_replace($replace, '', $string);
}

/**
 * 安全过滤文件及目录名称函数
 */
function dr_safe_filename($string) {
    return str_replace(
        ['..', "/", '\\', ' ', '<', '>', "{", '}', ';', '[', ']', '\'', '"', '*', '?'],
        '',
        $string
    );
}

/**
 * 安全过滤用户名函数
 */
function dr_safe_username($string) {
    return str_replace(
        ['..', "/", '\\', ' ', "#",'\'', '"'],
        '',
        $string
    );
}

/**
 * 安全过滤密码函数
 */
function dr_safe_password($string) {
    return trim(str_replace(["'", '"', '&', '?'], '',$string));
}


/**
 * 将路径进行安全转换变量模式
 */
function dr_safe_replace_path($path) {
    return str_replace(
        [
            WRITEPATH,
            WEBPATH,
            APPSPATH,
            TPLPATH,
            FCPATH,
            MYPATH,
        ],
        [
            'WRITEPATH/',
            'WEBPATH/',
            'APPSPATH/',
            'TPLPATH/',
            'FCPATH/',
            'MYPATH/',
        ],
        $path
    );
}

/**
 * 字符截取
 *
 * @param	string	$str
 * @param	intval	$length
 * @param	string	$dot
 * @return  string
 */
function dr_strcut($string, $length = 100, $dot = '...') {

    if (!$string || strlen($string) <= $length || !$length) {
        return $string;
    }

    if (function_exists('mb_substr')) {
        $strcut = mb_substr($string, 0, $length);
    } else {
        $n = $tn = $noc = 0;
        $string = str_replace(array('&amp;', '&quot;', '&lt;', '&gt;'), array('&', '"', '<', '>'), $string);
        while ($n < strlen($string)) {
            $t = ord($string[$n]);
            if ($t == 9 || $t == 10 || (32 <= $t && $t <= 126)) {
                $tn = 1;
                $n++;
                $noc++;
            } elseif (194 <= $t && $t <= 223) {
                $tn = 2;
                $n += 2;
                $noc += 2;
            } elseif (224 <= $t && $t <= 239) {
                $tn = 3;
                $n += 3;
                $noc += 2;
            } elseif (240 <= $t && $t <= 247) {
                $tn = 4;
                $n += 4;
                $noc += 2;
            } elseif (248 <= $t && $t <= 251) {
                $tn = 5;
                $n += 5;
                $noc += 2;
            } elseif ($t == 252 || $t == 253) {
                $tn = 6;
                $n += 6;
                $noc += 2;
            } else {
                $n++;
            }
            if ($noc >= $length) {
                break;
            }
        }
        if ($noc > $length) {
            $n -= $tn;
        }

        $strcut = substr($string, 0, $n);
        $strcut = str_replace(array('&', '"', '<', '>'), array('&amp;', '&quot;', '&lt;', '&gt;'), $strcut);
    }

    $strcut == $string && $dot = '';

    return $strcut . $dot;
}

/**
 * 随机颜色
 *
 * @return	string
 */
function dr_random_color() {

    $str = '#';
    for ($i = 0; $i < 6; $i++) {
        $randNum = rand(0, 15);
        switch ($randNum) {
            case 10: $randNum = 'A';
                break;
            case 11: $randNum = 'B';
                break;
            case 12: $randNum = 'C';
                break;
            case 13: $randNum = 'D';
                break;
            case 14: $randNum = 'E';
                break;
            case 15: $randNum = 'F';
                break;
        }
        $str.= $randNum;
    }

    return $str;
}

/**
 * 友好时间显示函数
 *
 * @param	int		$time	时间戳
 * @return	string
 */
function dr_fdate($sTime, $formt = 'Y-m-d') {

    if (!$sTime) {
        return '';
    }

    //sTime=源时间，cTime=当前时间，dTime=时间差
    $cTime = time();
    $dTime = $cTime - $sTime;
    $dDay = intval(date('z',$cTime)) - intval(date('z',$sTime));
    $dYear = intval(date('Y',$cTime)) - intval(date('Y',$sTime));
    if ($dYear > 0) {
        return date($formt, $sTime);
    }

    //n秒前，n分钟前，n小时前，日期
    if ($dTime < 60 ) {
        if ($dTime < 10) {
            return '刚刚';
        } else {
            return intval(floor($dTime / 10) * 10).'秒前';
        }
    } elseif ($dTime < 3600 ) {
        return intval($dTime/60).'分钟前';
    } elseif( $dTime >= 3600 && $dDay == 0  ){
        return intval($dTime/3600).'小时前';
    } elseif( $dDay > 0 && $dDay<=7 ){
        return intval($dDay).'天前';
    } elseif( $dDay > 7 &&  $dDay <= 30 ){
        return intval($dDay/7).'周前';
    } elseif( $dDay > 30 && $dDay < 180){
        return intval($dDay/30).'个月前';
    } elseif( $dDay >= 180 && $dDay < 360){
        return '半年前';
    } elseif ($dYear==0) {
        return date('m月d日', $sTime);
    } else {
        return date($formt, $sTime);
    }
}

/**
 * 时间显示函数
 *
 * @param	int		$time	时间戳
 * @param	string	$format	格式与date函数一致
 * @param	string	$color	当天显示颜色
 * @return	string
 */
function dr_date($time = NULL, $format = SITE_TIME_FORMAT, $color = NULL) {

    $time = (int) $time;
    if (!$time) {
        return '';
    }

    !$format && $format = SITE_TIME_FORMAT;
    !$format && $format = 'Y-m-d H:i:s';

    $string = date($format, $time);
    if (strpos($string, '1970') !== FALSE) {
        return '';
    }

    return $color && $time >= strtotime(date('Y-m-d 00:00:00')) && $time <= strtotime(date('Y-m-d 23:59:59')) ? '<font color="' . $color . '">' . $string . '</font>' : $string;
}



/**
 * 将对象转换为数组
 *
 * @param	object	$obj	数组对象
 * @return	array
 */
function dr_object2array($obj) {
    $_arr = is_object($obj) ? get_object_vars($obj) : $obj;
    if ($_arr && is_array($_arr)) {
        foreach ($_arr as $key => $val) {
            $val = (is_array($val) || is_object($val)) ? dr_object2array($val) : $val;
            $arr[$key] = $val;
        }
    }
    return $arr;
}

/**
 * 将字符串转换为数组
 *
 * @param	string	$data	字符串
 * @return	array
 */
function dr_string2array($data) {

    if (is_array($data)) {
        return $data;
    } elseif (!$data) {
        return [];
    }

    $rt = json_decode($data, true);
    if ($rt) {
        return $rt;
    }

    return unserialize(stripslashes($data));
}

/**
 * 将数组转换为字符串
 *
 * @param	array	$data	数组
 * @return	string
 */
function dr_array2string($data) {
    return $data ? json_encode($data, JSON_UNESCAPED_UNICODE) : '';
}

/**
 * 递归创建目录
 *
 * @param	string	$dir	目录名称
 * @return	bool|void
 */
function dr_mkdirs($dir, $null = true) {

    if (!$dir) {
        return FALSE;
    }

    if (!is_dir($dir)) {
        dr_mkdirs(dirname($dir));
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
    }
}

/**
 * 格式化输出文件大小
 *
 * @param	int	$fileSize	大小
 * @param	int	$round		保留小数位
 * @return	string
 */
function dr_format_file_size($fileSize, $round = 2) {

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

    return round($value, $round) . $unit[$i];
}

/**
 * 关键字高亮显示
 *
 * @param	string	$string		字符串
 * @param	string	$keyword	关键字
 * @return	string
 */
function dr_keyword_highlight($string, $keyword, $rule = '') {

    if (!$keyword) {
        return $string;
    }

    $arr = explode(' ', trim(str_replace('%', ' ', urldecode($keyword)), '%'));
    if (!$arr) {
       return $string;
    }

    !$rule && $rule = '<font color=red><strong>[value]</strong></font>';
    foreach ($arr as $t) {
        $string = str_ireplace($t, str_replace('[value]', $t, $rule), $string);
    }

    return $string;
}

function dollar($value, $include_cents = TRUE) {
    if (!$include_cents) {
        return "$" . number_format($value);
    } else {
        return "$" . number_format($value, 2, '.', ',');
    }
}

/**
 *
 * 正则替换和过滤内容
 *
 * @param   $html
 */
function dr_preg_html($html){
    $p = array("/<[a|A][^>]+(topic=\"true\")+[^>]*+>#([^<]+)#<\/[a|A]>/",
        "/<[a|A][^>]+(data=\")+([^\"]+)\"[^>]*+>[^<]*+<\/[a|A]>/",
        "/<[img|IMG][^>]+(src=\")+([^\"]+)\"[^>]*+>/");
    $t = array('topic{data=$2}','$2','img{data=$2}');
    $html = preg_replace($p, $t, $html);
    $html = strip_tags($html, "<br/>");
    return $html;
}

/**
 * 格式化微博内容中url内容的长度
 * @param   string  $match 匹配后的字符串
 * @return  string  格式化后的字符串
 */
function _format_feed_content_url_length($match) {
    return '<a href="'.$match[1].'" target="_blank">'.$match[1].'</a>';
}

// 替换互动表情内容
function dr_replace_emotion($content) {

    $content = htmlspecialchars_decode($content);

    // 替换表情
    if (preg_match_all('/\[([a-z0-9]+)\]/Ui', $content, $match)) {
        foreach ($match[1] as $t) {
            is_file(ROOTPATH.'static/assets/comment/emotions/'.$t.'.gif') && $content = str_replace('['.$t.']', '<img src="'.SITE_URL.'static/assets/comment/emotions/'.$t.'.gif" />', $content);
        }
    }

    return $content;
}

// 二维码
function dr_qrcode_url($text, $uid = 0, $level = 'L', $size = 5) {
    return ROOT_URL.'index.php?c=api&m=qrcode&uid='.urlencode($uid).'&text='.urlencode($text).'&size='.$size.'&level='.$level;
}

// 过滤非法字段
function dr_get_order_string($str, $order) {

    if (substr_count($str, ' ') >= 2
        || strpos($str, '(') !== FALSE
        || strpos($str, 'undefined') === 0
        || strpos($str, ')') !== FALSE ) {
        return $order;
    }

    return $str ? $str : ($order ? $order : 'id desc');

}

// 两数折扣
function dr_discount($price, $nowprice) {

    if ($nowprice <= 0) {
        return 0;
    }

    return round(10 / ($price / $nowprice), 1);
}

/**
 *  @desc 根据两点间的经纬度计算距离
 *  @param 当前坐标
 *  @param 目标坐标
 *  @param 单位
 */
function dr_distance($new, $to, $mark = '米,千米') {

    list($lng1, $lat1) = explode(',', $new);
    list($lng2, $lat2) = explode(',', $to);

    list($lat1) = explode('|', $lat1);
    list($lat2) = explode('|', $lat2);

    $lat1 = ($lat1 * pi() ) / 180;
    $lng1 = ($lng1 * pi() ) / 180;

    $lat2 = ($lat2 * pi() ) / 180;
    $lng2 = ($lng2 * pi() ) / 180;

    $calcLongitude = $lng2 - $lng1;
    $calcLatitude = $lat2 - $lat1;
    $stepOne = pow(sin($calcLatitude / 2), 2) + cos($lat1) * cos($lat2) * pow(sin($calcLongitude / 2), 2);  $stepTwo = 2 * asin(min(1, sqrt($stepOne)));

    $earthRadius = 6367000; // approximate radius of earth in meters
    $calculatedDistance = $earthRadius * $stepTwo;
    $value = round($calculatedDistance);

    if (!$mark) {
        return $value;
    }

    $dw = '';
    $mark = @explode(',', $mark);
    if ($value < 1000) {
        $dw = isset($mark[0]) ? $mark[0] : '';
    } elseif ($value >= 1000) {
        $dw = isset($mark[1]) ? $mark[1] : '';
        $dw && $value = $value / 1000;
    }

    return $value.$dw;
}


/**
 *计算某个经纬度的周围某段距离的正方形的四个点
 *
 *@param lng float 经度
 *@param lat float 纬度
 *@param distance float 该点所在圆的半径，该圆与此正方形内切，默认值为0.5千米
 *@return array 正方形的四个点的经纬度坐标
 */
function dr_square_point($lng, $lat, $distance = 0.5){

    $r = 6371; //地球半径，平均半径为6371km
    $distance = $distance ? $distance : 1;
    $dlng =  2 * asin(sin($distance / (2 * $r)) / cos(deg2rad($lat)));
    $dlng = rad2deg($dlng);

    $dlat = $distance/$r;
    $dlat = rad2deg($dlat);

    return array(
        'left-top'=>array('lat'=> round($lat + $dlat, 6),'lng'=> round($lng-$dlng, 6)),
        'right-top'=>array('lat'=> round($lat + $dlat, 6), 'lng'=> round($lng + $dlng, 6)),
        'left-bottom'=>array('lat'=> round($lat - $dlat, 6), 'lng'=> round($lng - $dlng, 6)),
        'right-bottom'=>array('lat'=> round($lat - $dlat, 6), 'lng'=> round($lng + $dlng, 6))
    );
}

// 格式化生成文件
function dr_format_html_file($file, $root = WEBPATH) {

    if (strpos($file, 'http://') !== false) {
        return '';
    }

    $dir = dirname($file);
    $nfile = basename($file);
    if ($dir != '.' && !is_dir($root.$dir)) {
        dr_mkdirs($root.$dir, TRUE);
    }

    $hfile = str_replace('./', '', $root.$dir.'/'.$nfile);
    $hfile = str_replace('////', '/', $hfile);
    $hfile = str_replace('///', '/', $hfile);
    $hfile = str_replace('//', '/', $hfile);


    // 判断是否为目录形式
    if (strpos($nfile, '.html') === FALSE
        && strpos($nfile, '.htm') === FALSE
        && strpos($nfile, '.shtml') === FALSE) {
        $hfile = rtrim($hfile, '/').'/';
        mkdir ($hfile,0777,true);
    }

    // 如果是目录就生成一个index.html
    if (is_dir($hfile)) {
        $dir.= '/'.$nfile;
        $nfile = 'index.html';
        $hfile = str_replace('./', '', $root.$dir.'/'.$nfile);
    }

    return $hfile;
}

// 删除静态文件
function dr_delete_html_file($url, $root = WEBPATH) {

    $file = dr_format_html_file($url, $root);
    if (is_file($file)) {
        unlink($file);
    }
}

// 获取当前模板目录
function dr_tpl_path($is_member = IS_MEMBER) {

    $tpl = dr_get_app_tpl(APP_DIR && APP_DIR != 'member' ? APP_DIR : '');
    $path = $is_member ? $tpl.(\Phpcmf\Service::V()->_is_mobile && is_dir($tpl.'mobile/'.SITE_TEMPLATE.'/') ? 'mobile' : 'pc').'/'.SITE_TEMPLATE.'/member/' : $tpl.(\Phpcmf\Service::V()->_is_mobile && is_dir($tpl.'mobile/'.SITE_TEMPLATE.'/') ? 'mobile' : 'pc').'/'.SITE_TEMPLATE.'/home/';

    APP_DIR && APP_DIR != 'member' && $path.= APP_DIR.'/';

    return $path;
}

// 获取网站表单发布页面需要的变量值
function dr_get_form_post_value($table) {

    $rt = [
        'form' => dr_form_hidden(),
    ];
    $form = \Phpcmf\Service::L('cache')->get('form-'.SITE_ID, $table);
    if (!$form) {
        return $rt;
    }

    $rt['form_name'] = $form['name'];
    $rt['form_table'] = $form['table'];

    // 是否有验证码
    $rt['is_post_code'] = dr_member_auth(
        \Phpcmf\Service::C()->member_authid,
        \Phpcmf\Service::C()->member_cache['auth_site'][SITE_ID]['form'][$form['table']]['code']
    );
    $rt['rt_url'] =  $form['setting']['rt_url'] ? $form['setting']['rt_url'] : dr_now_url();

    // 初始化自定义字段类

    $field = $form['field'];
    $my_field = $sys_field = $diy_field = [];

    uasort($field, function($a, $b){
        if($a['displayorder'] == $b['displayorder']){
            return 0;
        }
        return($a['displayorder']<$b['displayorder']) ? -1 : 1;
    });

    foreach ($field as $i => $t) {
        if ($t['setting']['is_right'] == 1) {
            // 右边字段归类为系统字段
            if (IS_ADMIN) {
                $sys_field[$i] = $t;
            } else {
                $my_field[$i] = $t;
            }

        } elseif ($t['setting']['is_right'] == 2) {
            // diy字段
            $diy_field[$i] = $t;
        } else {
            $my_field[$i] = $t;
        }
    }

    $rt['myfield'] = \Phpcmf\Service::L('Field')->toform(0, $my_field, []);
    $rt['sysfield'] = \Phpcmf\Service::L('Field')->toform(0, $sys_field, []);
    $rt['diyfield'] = \Phpcmf\Service::L('Field')->toform(0, $diy_field, []);

    $rt['post_url'] = dr_url('form/'.$table.'/post');

    return $rt;
}

// 获取模块表单发布页面需要的变量值
function dr_get_mform_post_value($mid, $table, $cid) {

    $rt = [
        'form' => dr_form_hidden(),
    ];

    $module = \Phpcmf\Service::L('cache')->get('module-'.SITE_ID.'-'.$mid);
    if (!$module) {
        return $rt;
    }

    $form = $module['form'][$table];
    if (!$form) {
        return $rt;
    }

    $rt['form_name'] = $form['name'];
    $rt['form_table'] = $form['table'];

    // 是否有验证码
    $rt['is_post_code'] = dr_member_auth(
        \Phpcmf\Service::C()->member_authid,
        \Phpcmf\Service::C()->member_cache['auth_module'][SITE_ID][$mid]['form'][$form['table']]['code']
    );
    $rt['rt_url'] =  $form['setting']['rt_url'] ? $form['setting']['rt_url'] : dr_now_url();

    // 初始化自定义字段类

    $field = $form['field'];
    $my_field = $sys_field = $diy_field = [];

    uasort($field, function($a, $b){
        if($a['displayorder'] == $b['displayorder']){
            return 0;
        }
        return($a['displayorder']<$b['displayorder']) ? -1 : 1;
    });

    foreach ($field as $i => $t) {
        if ($t['setting']['is_right'] == 1) {
            // 右边字段归类为系统字段
            if (IS_ADMIN) {
                $sys_field[$i] = $t;
            } else {
                $my_field[$i] = $t;
            }

        } elseif ($t['setting']['is_right'] == 2) {
            // diy字段
            $diy_field[$i] = $t;
        } else {
            $my_field[$i] = $t;
        }
    }

    $rt['myfield'] = \Phpcmf\Service::L('Field')->toform(0, $my_field, []);
    $rt['sysfield'] = \Phpcmf\Service::L('Field')->toform(0, $sys_field, []);
    $rt['diyfield'] = \Phpcmf\Service::L('Field')->toform(0, $diy_field, []);

    $rt['post_url'] = dr_url($mid.'/'.$table.'/post', ['cid' => $cid]);

    return $rt;
}

// 获取当前模板文件路径
function dr_tpl_file($file) {
    return dr_tpl_path().$file;
}

// 兼容统计
function dr_count($array_or_countable, $mode = COUNT_NORMAL){
    return is_array($array_or_countable) || is_object($array_or_countable) ? count($array_or_countable, $mode) : 0;
}

// http模式
function dr_http_prefix($url) {
    return (defined('SYS_HTTPS') && SYS_HTTPS ? 'https://' : 'http://').$url;
}

// 补全url
function dr_url_prefix($url, $domain = '', $siteid = SITE_ID, $is_mobile = '') {

    if ($url && strpos($url, 'http') === 0) {
        return $url;
    }

    strlen($is_mobile) == 0 && $is_mobile = \Phpcmf\Service::IS_MOBILE();

    if (is_array($domain) && isset($domain['setting']['html_domain']) && $domain['setting']['html_domain']) {
        $domain = $is_mobile && $domain['setting']['html_domain'] ? $domain['setting']['html_domain'] : $domain['setting']['html_domain'];
        $domain = dr_http_prefix($domain);
    }

    in_array($domain, ['MOD_DIR', 'share']) && $domain = '';

    // 判断是否是模块，如果domain不是http开头
    if ($domain && strpos($url, 'http') === false) {
        if (is_dir(APPSPATH.ucfirst($domain))) {
            $mod = \Phpcmf\Service::L('cache')->get('module-'.$siteid.'-'.$domain);
            $domain = $mod && $mod['domain'] ? (\Phpcmf\Service::IS_MOBILE() && $mod['mobile_domain'] ? $mod['mobile_domain'] : $mod['domain']) : '';
        }
        // 域名是不是http开通
        if (strpos($domain, 'http') === false) {
            $domain = '';
        }
    }


    // 指定域名判断
    if (!$domain) {
        if (IS_CLIENT) {
            // 来自客户端
            $domain = CLIENT_URL;
        } elseif ($siteid > 1 && \Phpcmf\Service::C()->site_info[$siteid]['SITE_URL']) {
            // 存在多站点时
            $domain = $is_mobile ? \Phpcmf\Service::C()->site_info[$siteid]['SITE_MURL'] : \Phpcmf\Service::C()->site_info[$siteid]['SITE_URL'];
        } else {
            $domain = $is_mobile ? SITE_MURL : SITE_URL;
        }

    }

    return strpos($url, 'http') === 0 ? $url : rtrim($domain, '/').'/'.ltrim($url, '/');
}

// 计算用户组到期时间
function dr_member_group_etime($days) {

    if (!$days) {
        return 0;
    }

    return SYS_TIME + $days * 3600 * 24;
}

// 处理带Emoji的数据，HTML转为emoji码
function dr_html2emoji($msg){

    $txt = json_decode(str_replace('|', '\\', $msg));
    if ($txt !== null) {
        $msg = $txt;
    }

    return trim($msg, '"');
}

// 处理带Emoji的数据，写入数据库前的emoji转为HTML
function dr_emoji2html($msg) {
    return str_replace('\\', '|', json_encode($msg));;
}

/**
 * 过滤emoji表情
 * @param type $str
 * @return type
 */
function dr_clear_emoji($str){
    preg_match_all("#(\|ud[0-9a-f]{3})#ie", $str, $match) && $str = str_replace($match[1], '', $str);
    return dr_clear_empty(dr_html2emoji($str));
}

// 判断是否支持回复
function dr_comment_is_reply($reply, $member, $cuid) {

    if ($reply == 1) {
        // 都允许
        return 1;
    } elseif ($reply == 2) {
        // 仅自己
        if ($member['uid'] == $cuid) {
            // 自己的评论
            return 1;
        } elseif ($member['is_admin']) {
            return 1; // 管理员可以回复
        } else {
            return 0;
        }
    } else {
        // 禁止所有
        return 0;
    }
}

// 将同步代码转为数组
function dr_member_sync_url($string) {

    if (preg_match_all('/src="(.+)"/iU', $string, $match)) {
        return $match[1];
    }

    return [];
}


// 检查目录权限
function dr_check_put_path($dir) {

    if (!$dir) {
        return 0;
    } elseif (!is_dir($dir)) {
        return 0;
    }

    $size = file_put_contents($dir.'test.html', 'test');
    if ($size === false) {
        return 0;
    } else {
        @unlink($dir.'test.html');
        return 1;
    }
}

/**
 * 栏目下级或者同级栏目
 */
function dr_related_cat($data, $catid) {

    if (!$data) {
        return [[], []];
    }

    $my = $data[$catid];
    $related = $parent = [];

    if ($my['child']) {
        $parent = $my;
        foreach ($data as $t) {
            if (!$t['show']) {
                continue;
            }
            $t['pid'] == $my['id'] && $related[$t['id']] = $t;
        }
    } elseif ($my['pid']) {
        foreach ($data as $t) {
            if (!$t['show']) {
                continue;
            }
            if ($t['pid'] == $my['pid']) {
                $related[$t['id']] = $t;
                $parent = $my['child'] ? $my : $data[$t['pid']];
            }
        }
    } else {
        if (!$data) {
            return [[], []];
        }
        $parent = $my;
        foreach ($data as $t) {
            if (!$t['show']) {
                continue;
            }
            $t['pid'] == 0 && $related[$t['id']] = $t;
        }
    }

    return [$parent, $related];
}

/**
 * 模块栏目层次关系
 *
 * @param	array	$mod
 * @param	array	$cat
 * @param	string	$symbol
 * @return	string
 */
function dr_get_cat_pname($mod, $catid, $symbol = '_') {

    $cat = $mod['category'][$catid];

    if (!$cat['pids']) {
        return $cat['name'];
    }

    $name = [];
    $array = explode(',', $cat['pids']);

    foreach ($array as $id) {
        $id && $mod['category'][$id] && $name[] = $mod['category'][$id]['name'];
    }

    $name[] = $cat['name'];

    $name = array_unique($name);

    krsort($name);

    return implode($symbol, $name);
}

// php 5.5 以上版本的正则替换方法
class php5replace {

    private $data;

    function __construct($data) {
        $this->data = $data;
    }

    // 替换常量值
    function php55_replace_var($value) {
        $v = '';
        $val = (defined($value[1]) ? $value[1] : \Phpcmf\Service::C()->get_cache('site', SITE_ID, 'config', $value[1]));
        @eval('$v = '.($val ? $val : '""').';');
        return $v;
    }

    // 替换数组变量值
    function php55_replace_data($value) {
        return $this->data[$value[1]];
    }

    // 替换函数值
    function php55_replace_function($value) {
        if (function_exists($value[1])) {
            // 执行函数体
            $param = $value[2] == '$data' ? $this->data : $value[2];
            return call_user_func_array(
                $value[1],
                is_array($param) ? ['data' => $param] : @explode(',', $param)
            );
        } else {
            return '函数['.$value[1].']未定义';
        }

        return $value[0];
    }

}

// 模块首页地址
function dr_module_url($dir) {

    if ($dir == MOD_DIR) {
        return MODULE_URL;
    }

    return \Phpcmf\Service::L('cache')->get('module-'.SITE_ID.'-'.$dir, 'url');
}

/**
 * 获取对应的手机端地址
 */
function dr_mobile_url($url = SITE_MURL) {

    $a = parse_url($url);
    $host = $a['host'];
    $domain = require WRITEPATH.'config/domain_client.php';
    if (!$domain) {
        return IS_DEV ? '【开发者模式下】未找到域名的终端配置文件' : $url;
    } elseif (!isset($domain[$host])) {
        return IS_DEV ? '【开发者模式下】未找到PC域名['.$host.']所对应的移动端域名' : $url;
    }

    return dr_url_prefix(str_replace($host, $domain[$host], $url));
}

////////////////////////////////////////////////////////////


// 兼容性判断
if (!function_exists('gethostbyname')) {
    function gethostbyname($domain) {
        return $domain;
    }
}

// 兼容性判断
if (!function_exists('ctype_digit')) {
    function ctype_digit($num) {
        if (strpos($num, '.') !== FALSE) {
            return false;
        }
        return is_numeric($num);
    }
}

// 兼容性判断
if (!function_exists('ctype_alpha')) {
    function ctype_alpha($num) {
        if (strpos($num, '.') !== FALSE) {
            return false;
        }
        return is_numeric($num);
    }
}


// 兼容性判断
if (!function_exists('is_php')) {
    function is_php($version)
    {
        static $_is_php;
        $version = (string) $version;

        if ( ! isset($_is_php[$version]))
        {
            $_is_php[$version] = version_compare(PHP_VERSION, $version, '>=');
        }

        return $_is_php[$version];
    }
}


if (! function_exists('dr_clearhtml')) {
    /**
     * 清除HTML标记
     *
     * @param	string	$str
     * @return  string
     */
    function dr_clearhtml($str) {

        $str = str_replace(
            array('&nbsp;', '&amp;', '&quot;', '&#039;', '&ldquo;', '&rdquo;', '&mdash;', '&lt;', '&gt;', '&middot;', '&hellip;'), array(' ', '&', '"', "'", '“', '”', '—', '<', '>', '·', '…'), $str
        );

        $str = preg_replace("/\<[a-z]+(.*)\>/iU", "", $str);
        $str = preg_replace("/\<\/[a-z]+\>/iU", "", $str);
        $str = str_replace(array(PHP_EOL, chr(13), chr(10), '&nbsp;'), '', $str);
        $str = strip_tags($str);

        return trim($str);
    }
}

if (! function_exists('dr_get_keywords')) {
    /**
     * 提取关键字
     */
    function dr_get_keywords($kw, $siteid = SITE_ID)
    {

        if (!$kw) {
            return '';
        }

        $rt = [];

        //tag数据
        $tags = \Phpcmf\Service::L('cache')->get('tag-' . $siteid);
        if ($tags) {
            foreach ($tags as $t) {
                if (strpos($kw, $t['name']) !== false) {
                    $rt[] = $t['tags']; // 找到了
                }
            }
        }

        if (!$rt && function_exists('mb_convert_encoding')) {

            $kw = dr_strcut($kw, 30);
            $data = [
                'title' => $kw,
                'content' => $kw,
            ];

            $data = mb_convert_encoding(json_encode($data), 'GBK', 'UTF8');

            $baidu = \Phpcmf\ThirdParty\BaiduApi::get_data('https://aip.baidubce.com/rpc/2.0/nlp/v1/keyword', $data, 1);
            if (!$baidu['code']) {
                CI_DEBUG && log_message('error', $baidu['msg']);
            } elseif ($baidu && $baidu['data']['items']) {
                foreach ($baidu['data']['items'] as $t) {
                    $rt[] = $t['tag']; // 找到了
                }
            }
        }

        return @implode(',', $rt);;
    }
}


if (! function_exists('dr_redirect'))
{
    /**
     * 跳转地址
     */
    function dr_redirect($url = '', $method = 'auto', $code = NULL) {

        switch ($method) {
            case 'refresh':
                header('Refresh:0;url='.$url);
                break;
            default:
                header('Location: '.$url, TRUE, $code);
                break;
        }
        exit;
    }
}


if ( ! function_exists('dr_directory_map'))
{
    /**
     * Create a Directory Map
     *
     * Reads the specified directory and builds an array
     * representation of it. Sub-folders contained with the
     * directory will be mapped as well.
     *
     * @param	string	$source_dir		Path to source
     * @param	int	$directory_depth	Depth of directories to traverse
     *						(0 = fully recursive, 1 = current dir, etc)
     * @param	bool	$hidden			Whether to show hidden files
     * @return	array
     */
    function dr_directory_map($source_dir, $directory_depth = 0, $hidden = FALSE)
    {
        if ($fp = @opendir($source_dir))
        {
            $filedata	= [];
            $new_depth	= $directory_depth - 1;
            $source_dir	= rtrim($source_dir, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;

            while (FALSE !== ($file = readdir($fp)))
            {
                // Remove '.', '..', and hidden files [optional]
                if ($file === '.' OR $file === '..' OR ($hidden === FALSE && $file[0] === '.'))
                {
                    continue;
                }

                is_dir($source_dir.$file) && $file .= DIRECTORY_SEPARATOR;

                if (($directory_depth < 1 OR $new_depth > 0) && is_dir($source_dir.$file))
                {
                    $filedata[$file] = directory_map($source_dir.$file, $new_depth, $hidden);
                }
                else
                {
                    $filedata[] = $file;
                }
            }

            closedir($fp);
            return $filedata;
        }

        return FALSE;
    }
}


// 快速下单 判断是否购买// 0 没有购买 1 购买了
if (!function_exists('dr_is_buy')) {

    function dr_is_buy($fid, $id, $uid = 0, $sku = '') {

        $field = \Phpcmf\Service::C()->get_cache('table-field', $fid);
        if (!$field) {
            return 0;
        }

        $data = \Phpcmf\Service::C()->get_cache('table-pay-'.SITE_ID, $field['relatedname'].'-'.$field['relatedid']);
        if (!$data) {
            return 0;
        }

        !$uid && $uid = (int)\Phpcmf\Service::C()->uid;

        // buy-表名-主键id-字段id*** 模糊匹配
        $mid = $sku ? 'buy-'.$data['table'].'-'.$id.'-'.$field['id'].'-%-'.$sku : 'buy-'.$data['table'].'-'.$id.'-'.$field['id'].'%';

        return \Phpcmf\Service::M()->db->table('member_paylog')->where('uid', $uid)->where('status', 1)->like('mid', $mid)->countAllResults();
    }
}

if(!function_exists('mime_content_type')) {

    function mime_content_type($filename) {

        $mime_types = array(

            'txt' => 'text/plain',
            'htm' => 'text/html',
            'html' => 'text/html',
            'php' => 'text/html',
            'css' => 'text/css',
            'js' => 'application/javascript',
            'json' => 'application/json',
            'xml' => 'application/xml',
            'swf' => 'application/x-shockwave-flash',
            'flv' => 'video/x-flv',

            // images
            'png' => 'image/png',
            'jpe' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'jpg' => 'image/jpeg',
            'gif' => 'image/gif',
            'bmp' => 'image/bmp',
            'ico' => 'image/vnd.microsoft.icon',
            'tiff' => 'image/tiff',
            'tif' => 'image/tiff',
            'svg' => 'image/svg+xml',
            'svgz' => 'image/svg+xml',

            // archives
            'zip' => 'application/zip',
            'rar' => 'application/x-rar-compressed',
            'exe' => 'application/x-msdownload',
            'msi' => 'application/x-msdownload',
            'cab' => 'application/vnd.ms-cab-compressed',

            // audio/video
            'mp3' => 'audio/mpeg',
            'qt' => 'video/quicktime',
            'mov' => 'video/quicktime',

            // adobe
            'pdf' => 'application/pdf',
            'psd' => 'image/vnd.adobe.photoshop',
            'ai' => 'application/postscript',
            'eps' => 'application/postscript',
            'ps' => 'application/postscript',

            // ms office
            'doc' => 'application/msword',
            'rtf' => 'application/rtf',
            'xls' => 'application/vnd.ms-excel',
            'ppt' => 'application/vnd.ms-powerpoint',

            // open office
            'odt' => 'application/vnd.oasis.opendocument.text',
            'ods' => 'application/vnd.oasis.opendocument.spreadsheet',
        );

        $ext = strtolower(array_pop(explode('.',$filename)));
        if (array_key_exists($ext, $mime_types)) {
            return $mime_types[$ext];
        }
        elseif (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME);
            $mimetype = finfo_file($finfo, $filename);
            finfo_close($finfo);
            return $mimetype;
        }
        else {
            return 'application/octet-stream';
        }
    }
}

if (!function_exists('mb_strlen'))
{
    function mb_strlen($str)
    {
        return strlen($str);
    }
}

if (!function_exists('array_key_first')) {
    function array_key_first(array $arr) {
        foreach($arr as $key => $unused) {
            return $key;
        }
        return NULL;
    }
}

////////////////////////////////////////////////////////////


function dr_url($url, $query = [], $self = SELF) {
    return \Phpcmf\Service::L('router')->url($url, $query, $self);
}

function dr_furl($name) {
    return \Phpcmf\Service::L('router')->furl($name);
}

function dr_get_tag_url($name, $mid = '') {
    return \Phpcmf\Service::L('router')->get_tag_url($name, $mid);
}

function dr_comment_url($id, $moddir = '')  {
    return \Phpcmf\Service::L('router')->comment_url($id, $moddir);
}

function dr_form_show_url($table, $id, $page = 0)  {
    return \Phpcmf\Service::L('router')->form_show_url($table, $id, $page);
}

function dr_oauth_url($name, $type, $gourl = '') {
    return \Phpcmf\Service::L('router')->oauth_url($name, $type, $gourl);
}

function dr_member_url($url, $query = [],  $self = 'index.php') {
    return \Phpcmf\Service::L('router')->member_url($url, $query, $self);
}

function dr_search_url($params = [], $name = '', $value = '', $moddir = '') {
    return \Phpcmf\Service::L('router')->search_url($params, $name, $value, $moddir);
}
