<?php


/**
 * 模块首页地址
 * $dir 模块目录
 */
function dr_module_url($dir) {

    if (defined('MOD_DIR') && $dir == MOD_DIR) {
        return MODULE_URL;
    }

    return \Phpcmf\Service::L('cache')->get('module-'.SITE_ID.'-'.$dir, 'url');
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

// 获取内容的tags
function dr_get_content_tags($value) {

    if (is_array($value)) {
        return $value;
    }

    $rt = [];
    $tag = explode(',', $value);
    foreach ($tag as $t) {
        $t = trim($t);
        if ($t) {
            // 读缓存
            if (dr_is_app('tag')) {
                $obj = \Phpcmf\Service::M('tag', 'tag');
                if (method_exists($obj, 'get_tag_url')) {
                    $url = $obj->get_tag_url($t);
                    if ($url) {
                        $rt[$t] = $url;
                    }
                }
            }
        }
    }

    return $rt;
}

// 获取内容的搜索词
function dr_get_content_kws($value, $mid = '') {

    if (is_array($value)) {
        return $value;
    }

    $rt = [];
    $mid = $mid ? $mid : (defined('MOD_DIR') ? MOD_DIR : '');
    $tag = explode(',', $value);
    foreach ($tag as $t) {
        $t = trim($t);
        if ($t) {
            $rt[$t] = \Phpcmf\Service::L('router')->search_url([], 'keyword', $t, $mid);
        }
    }

    return $rt;
}


/**
 * 内容文章显示内链
 */
function dr_content_link($tags, $content, $num = 0, $blank = 1) {

    if (!$tags || !$content) {
        return $content;
    } elseif (!is_array($tags)) {
        return $content;
    }

    foreach ($tags as $name => $url) {
        if ($name && $url) {
            $content = @preg_replace(
                '\'(?!((<.*?)|(<a.*?)|(<strong.*?)))('.str_replace(["'", '-'], ["\'", '\-'], preg_quote($name)).')(?!(([^<>]*?)>)|([^>]*?</a>)|([^>]*?</strong>))\'si',
                '<a href="'.$url.'"'.($blank ? ' target="_blank"' : '').'>'.$name.'</a>',
                $content,
                $num ? $num : -1
            );
        }
    }

    return $content;
}


// 内容加内链
function dr_neilian($content, $blank = 1, $num = 0) {

    if (!$content) {
        return '';
    }

    if (dr_is_app('tag')) {
        $obj = \Phpcmf\Service::M('tag', 'tag');
        if (method_exists($obj, 'neilian')) {
            return $obj->neilian($content, $blank, $num);
        }
    }

    return $content;
}

// 获取模块数据及自定义字段
function dr_mod_value(...$get) {

    if (empty($get)) {
        return '';
    }

    if (is_numeric($get[0]) && defined('MOD_DIR') && MOD_DIR) {
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

// 获取栏目数据及自定义字段
function dr_cat_value(...$get) {

    if (empty($get)) {
        return '';
    }

    $mid = '';
    if (is_numeric($get[0])) {
        // 值是栏目id时，表示当前模块
        if (defined('MOD_DIR') && MOD_DIR) {
            $mid = MOD_DIR;
            $name = 'module-'.SITE_ID.'-'.MOD_DIR;
        } else {
            $name = 'module-'.SITE_ID.'-share';
        }
    } else {
        // 指定模块
        $mid = $get[0];
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

    $rt = call_user_func_array([\Phpcmf\Service::C(), 'get_cache'], $param);
    if (end($param) == 'url' && $rt) {
        $rt = dr_url_prefix($rt, $mid);
    }

    return $rt;
}

// 获取共享栏目数据及自定义字段
function dr_share_cat_value($id, $field='') {

    $get = func_get_args();
    if (empty($get)) {
        return '';
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

    $rt = call_user_func_array(array(\Phpcmf\Service::C(), 'get_cache'), $param);

    return $field == 'url' && $rt ? dr_url_prefix($rt) : $rt;
}


/**
 * 模块栏目面包屑导航
 *
 * @param   intval  $catid  栏目id
 * @param   string  $symbol 面包屑间隔符号
 * @param   string  $url    是否显示URL
 * @param   string  $html   格式替换
 * @return  string
 */
function dr_catpos($catid, $symbol = ' > ', $url = true, $html= '', $dirname = 'MOD_DIR', $url_call_func = '') {

    if (!$catid) {
        return '';
    }

    $mid = $dirname == 'MOD_DIR' && defined('MOD_DIR') && MOD_DIR ? MOD_DIR : (!$dirname || $dirname == 'MOD_DIR' ? 'share' : $dirname);
    $cat = \Phpcmf\Service::L('cache')->get('module-'.SITE_ID.'-'.$mid, 'category');
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
                $murl = dr_url_prefix($cat[$id]['url'], $mid);
                //$murl = dr_url_prefix($cat[$id]['url'], MOD_DIR, SITE_ID, \Phpcmf\Service::IS_MOBILE_TPL())
            }
            $name[] = $url ? ($html ? str_replace(['[url]', '[name]'], [$murl, $cat[$id]['name']], $html): "<a href=\"{$murl}\">{$cat[$id]['name']}</a>") : $cat[$id]['name'];
        }
    }

    return implode($symbol, array_unique($name));
}


// 打赏支付
function dr_donation($id, $title = '', $dir = '', $remove_div  = 1) {
    if (!dr_is_app('pay')) {
        return '没有安装「支付系统」插件';
    }
    !$dir && $dir = defined('MOD_DIR') ? MOD_DIR : 'share';
    return \Phpcmf\Service::M('Pay', 'pay')->payform('my-shang_buy-'.$id.'_'.$dir.'-'.SITE_ID, 0, $title, '', $remove_div);
}

// 是否存在收藏夹中 1收藏了 2没有收藏
function dr_is_favorite($dir, $id, $uid = 0) {

    !$uid && $uid = \Phpcmf\Service::C()->uid;

    if (!$uid) {
        return 0;
    } elseif (!$dir) {
        return 0;
    }

    return \Phpcmf\Service::M()->db->table(dr_module_table_prefix($dir).'_favorite')->where('uid', $uid)->where('cid', $id)->countAllResults();
}


/**
 * 模块内容阅读量显示js
 *
 * @param   intval  $id
 * @return  string
 */
if (!function_exists('dr_show_hits')) {
    function dr_show_hits($id, $dom = "", $dir = 'MOD_DIR') {
        $is = $dom;
        !$dom && $dom = "dr_show_hits_{$id}";
        $html = $is ? "" : "<span class=\"{$dom}\">0</span>";
        if (defined('MODULE_MYSHOW')) {
            return $html;
        }
        $dir = $dir == 'MOD_DIR' && defined('MOD_DIR') && MOD_DIR ? MOD_DIR : $dir;
        $rt = "$(\".{$dom}\").html(data.msg);";
        if ($is) {
            $rt.= "$(\"#{$dom}\").html(data.msg);";
        }
        return $html."<script type=\"text/javascript\"> $.ajax({ type: \"GET\", url:\"".dr_web_prefix("index.php?s=api&c=module&siteid=".SITE_ID."&app=".$dir)."&m=hits&id={$id}\", dataType: \"jsonp\", success: function(data){ if (data.code) { ".$rt." } else { dr_tips(0, data.msg); } } }); </script>";
    }
}


/**
 * 栏目下级或者同级栏目
 * $data 整个栏目数组
 * $catid 当前栏目id
 */
function dr_related_cat($data, $catid) {

    if (!$data) {
        return [[], []];
    }

    $my = $data[$catid];
    $related = $parent = [];

    if ($my['child']) {
        // 当存在子栏目时就显示下级子栏目
        $parent = $my['pid'] ? $data[$my['pid']] : $my;
        foreach ($data as $t) {
            if (!$t['show']) {
                continue;
            }
            if ($t['pid'] == $my['id']) {
                $t['url'] = dr_url_prefix($t['url'], defined('MOD_DIR') ? MOD_DIR : '');
                $related[$t['id']] = $t;
            }
        }
    } elseif ($my['pid']) {
        // 当属于子栏目时就显示同级别栏目
        foreach ($data as $t) {
            if (!$t['show']) {
                continue;
            }
            if ($t['pid'] == $my['pid']) {
                $t['url'] = dr_url_prefix($t['url'], defined('MOD_DIR') ? MOD_DIR : '');
                $related[$t['id']] = $t;
                $parent = $data[$t['pid']];
            }
        }
    } else {
        // 显示顶级栏目
        if (!$data) {
            return [[], []];
        }
        $parent = $my;
        foreach ($data as $t) {
            if (!$t['show']) {
                continue;
            }
            if ($t['pid'] == 0) {
                $t['url'] = dr_url_prefix($t['url'], defined('MOD_DIR') ? MOD_DIR : '');
                $related[$t['id']] = $t;
            }
        }
    }

    $parent && $parent['url'] = dr_url_prefix($parent['url'], defined('MOD_DIR') ? MOD_DIR : '');

    return [$parent, $related];
}

/**
 * 模块栏目层次关系
 *
 * @param   array   $mod
 * @param   array   $cat
 * @param   string  $symbol
 */
function dr_get_cat_pname($mod, $catid, $symbol = '_') {

    $cat = $mod['category'][$catid];

    if (!$cat['pids']) {
        return $cat['name'];
    }

    $name = [];
    $array = explode(',', $cat['pids']);

    foreach ($array as $id) {
        if ($id && $mod['category'][$id]) {
            $name[] = $mod['category'][$id]['name'];
        }
    }

    $name[] = $cat['name'];

    $name = array_unique($name);

    krsort($name);

    return implode($symbol, $name);
}

