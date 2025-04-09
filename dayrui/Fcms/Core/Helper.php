<?php
/**
 * {{www.xunruicms.com}}
 * {{迅睿内容管理框架系统}}
 * 本文件是框架系统文件，二次开发时不可以修改本文件
 **/



/**
 * 判断是否为空白
 * @param $value
 * @return 是否空白
 */
function dr_is_empty($value) {

    if (is_array($value)) {
        return $value ? 0 : 1;
    }

    return strlen((string)$value) ? 0 : 1;
}

/**
 * 是否301跳转
 * @return 是否301跳转
 */
function dr_is_sys_301() {

    if (defined('IS_NOT_301') && IS_NOT_301) {
        return 0;
    } elseif (defined('SYS_301') && SYS_301) {
        return 0;
    }

    return 1;
}

/**
 * 两个变量判断是否有值并返回
 * @param $a 变量1
 * @param $b 变量2
 * @return $a 有值时返回$a 否则返回$b
 */
function dr_else_value($a, $b) {
    return dr_strlen($a) ? $a : $b;
}

/**
 * 安全url过滤
 * @param $url URL地址
 * @param $is_html 是否作为html转换
 * @return 过滤后的URL地址
 */
function dr_safe_url($url, $is_html = false) {

    if (!$url) {
        return '';
    }

    $url = trim(\Phpcmf\Service::L('Security')->xss_clean((string)$url, true));
    $url = str_ireplace(['<iframe', '<', '/>'], '', $url);
    if ($is_html) {
        $url = htmlspecialchars($url);
    }

    return $url;
}

/**
 * 模糊比较两个变量
 * @param $str1 变量1
 * @param $str2 变量2
 * @return 判断两个变量是否相等
 */
function dr_diff($str1, $str2) {

    if (is_array($str1) && is_array($str2)) {
        return array_diff($str1, $str2) ? false : true;
    } elseif (dr_strlen($str1) != dr_strlen($str2)) {
        return false;
    }

    return $str1 == $str2;
}

/**
 * 返回包含数组中所有键名的一个新数组
 * @param $array 指定数组
 * @param $value 具体值
 * @param $strict 严格比较
 * @return 返回包含数组中所有键名的一个新数组
 */
function dr_array_keys($array, $value = '', $strict = false) {

    if (!$array || !is_array($array)) {
        return 0;
    }

    if ($value) {
        return array_keys($array, $value, $strict);
    } else {
        return array_keys($array);
    }
}

/**
 * 返回包含数组中指定键名的对应值
 * @param $array 指定数组
 * @param $key 数组key
 * @return 返回包含数组中指定键名的对应值
 */
function dr_array_value($array, $key) {

    if (!$array || !is_array($array)) {
        return NULL;
    } elseif (is_array($key)) {
        return NULL;
    } elseif (isset($array[$key])) {
        return $array[$key];
    } else {
        return NULL;
    }
}

/**
 * 判断存在于数组中
 * @param $var|array 指定值或数组
 * @param $array 指定数组
 * @return 判断$var是否存在于数组$array中
 */
function dr_in_array($var, $array) {

    if (!$array || !is_array($array)) {
        return 0;
    } elseif (is_array($var)) {
        return array_intersect($var, $array);
    } else {
        return in_array($var, $array);
    }
}

/**
 * 两个数组比较
 * @param $arr1 指定数组1
 * @param $arr2 指定数组2
 * @return 比较两个数组的键值,并返回交集
 */
function dr_array_intersect($arr1, $arr2) {

    if (!is_array($arr1) || !is_array($arr2)) {
        return false;
    }

    return array_intersect($arr1, $arr2);
}

/**
 * 两个数组比较
 * @param $arr1 指定数组1
 * @param $arr2 指定数组2
 * @return 比较两个数组的键名,并返回交集
 */
function dr_array_intersect_key($arr1, $arr2) {

    if (!is_array($arr1) || !is_array($arr2)) {
        return false;
    }

    return array_intersect_key($arr1, $arr2);
}

/**
 * 字符长度
 * @param $string 字符串
 * @return 返回字符串的长度
 */
function dr_strlen($string) {

    if (is_array($string)) {
        return dr_count($string);
    }

    return strlen((string)$string);
}

/**
 * 字符是否包含
 * @param $string 原字符串
 * @param $key 查询的字符串
 * @return 返回$string中是否包含$key，区分大小写
 */
function dr_strpos($string, $key) {
    return strpos((string)$string, $key);
}

/**
 * 字符是否包含
 * @param $string 原字符串
 * @param $key 查询的字符串
 * @return 返回$string中是否包含$key，不区分大小写
 */
function dr_stripos($string, $key) {
    return stripos((string)$string, $key);
}

/**
 * 上传移动文件
 * @param $tempfile 临时文件
 * @param $fullname 存储文件
 * @return 将临时文件存储到指定的目录中
 */
function dr_move_uploaded_file($tempfile, $fullname) {

    $contentType = $_SERVER['CONTENT_TYPE'] ?? getenv('CONTENT_TYPE');
    if ($contentType && $_SERVER['HTTP_CONTENT_RANGE']
        && strpos($contentType, 'multipart') !== false && strpos($_SERVER['HTTP_CONTENT_RANGE'], 'bytes') === 0) {

        // 命名一个新名称
        $value = str_replace('bytes ', '', $_SERVER['HTTP_CONTENT_RANGE']);
        list($str, $total) = explode('/', $value);
        list($str, $max) = explode('-', $str);

        // 分段名称
        $temp_file = dirname($fullname).'/'.md5($_SERVER['HTTP_CONTENT_DISPOSITION']);
        if ($total - $max < 1024) {
            // 减去误差表示分段上传完毕
            if (!file_put_contents($temp_file, file_get_contents($tempfile), FILE_APPEND)) {
                unlink($temp_file);
                return false;
            }
            // 移动最终的文件
            if (!rename($temp_file, $fullname)) {
                unlink($temp_file);
                return false;
            }
            unlink($temp_file);
            return true;
        } else {
            // 正在分段上传
            echo file_put_contents($temp_file, file_get_contents($tempfile), FILE_APPEND);exit;
        }
    } else {
        return move_uploaded_file($tempfile, $fullname);
    }
}

/**
 * html实体字符转换
 * @param $value 指定字符串
 * @param $fullname 存储文件
 * @return 用htmlspecialchars进行html转码值
 */
function dr_html2code($value) {
    return htmlspecialchars((string)$value);
}

/**
 * html实体字符转换
 * @param $value 指定字符串
 * @param $fk 强制转为utf8
 * @param $flags 用下列标记中的一个或多个作为一个位掩码
 * @return htmlspecialchars_decode进行html转码值
 */
function dr_code2html($value, $fk = false, $flags = '') {
    return dr_html_code($value, $fk, $flags);
}

/**
 * html实体字符转换
 * @param $value 指定字符串
 * @param $fk 强制转为utf8
 * @param $flags 用下列标记中的一个或多个作为一个位掩码
 * @return htmlspecialchars_decode进行html转码值
 */
function dr_html_code($value, $fk = false, $flags = '') {

    if (!$value) {
        return '';
    }

    !$flags && $flags = ENT_QUOTES | ENT_HTML401 | ENT_HTML5;

    if ($fk) {
        // 将所有HTML实体转换为它们的适用字符
        return html_entity_decode($value, $flags, 'UTF-8');
    }

    // 将特殊的HTML实体转换回字符
    return htmlspecialchars_decode($value, $flags);
}

/**
 * 快捷登录接入商信息列表
 * @return 返回文件数组
 */
function dr_oauth_list() {

    $data = [];
    $path = FCPATH.'ThirdParty/OAuth/';
    $local = dr_dir_map($path, 1);
    if ($local) {
        foreach ($local as $dir) {
            if (is_file($path.$dir.'/App.php')) {
                $data[strtolower($dir)] = require $path.$dir.'/App.php';
            }
        }
    }

    return $data;
}

/**
 * 判断是否是移动端终端
 * @return bool
 */
if (!function_exists('dr_is_mobile')) {
    function dr_is_mobile() {
        if (defined('SITE_MOBILE_NOT_PAD') && SITE_MOBILE_NOT_PAD) {
            // 判断是否为平板，将排除为移动端
            $clientkeywords = [
                'ipad',
            ];
            // 从HTTP_USER_AGENT中查找关键字
            if (preg_match("/(" . implode('|', $clientkeywords) . ")/i", strtolower((string)$_SERVER['HTTP_USER_AGENT']))){
                return false;
            }
        }
        if (isset ($_SERVER['HTTP_X_WAP_PROFILE'])) {
            // 如果有HTTP_X_WAP_PROFILE则一定是移动设备
            return true;
        } elseif (isset ($_SERVER['HTTP_USER_AGENT'])) {
            // 判断手机发送的客户端标志,兼容性有待提高
            $clientkeywords=['nokia','sony','ericsson','mot','samsung','htc','sgh','lg','sharp','sie-','philips','panasonic','alcatel','lenovo','iphone','xiaomi','ipod','blackberry','meizu','android','netfront','symbian','ucweb','windowsce','palm','operamini','operamobi','openwave','nexusone','cldc','midp','wap','mobile'];
            // 从HTTP_USER_AGENT中查找手机浏览器的关键字
            if (preg_match("/(" . implode('|', $clientkeywords) . ")/i", strtolower((string)$_SERVER['HTTP_USER_AGENT']))){
                return true;
            }
        }
        // 协议法，因为有可能不准确，放到最后判断
        if (isset ($_SERVER['HTTP_ACCEPT'])) {
            // 如果只支持wml并且不支持html那一定是移动设备
            // 如果支持wml和html但是wml在html之前则是移动设备
            if ((strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml') !== false) && (strpos($_SERVER['HTTP_ACCEPT'], 'text/html') === false || (strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml') < strpos($_SERVER['HTTP_ACCEPT'], 'text/html'))))
            {
                return true;
            }
        }
        return false;
    }
}

/**
 * 后台搜索字段过滤函数
 * @param $t 单个字段数组
 * @return 是否被搜索时可用
 */
function dr_is_admin_search_field($t) {

    if (!$t) {
        return 0;
    }

    if (!$t['ismain']) {
        return 0;
    } elseif (in_array($t['fieldtype'], [
        'Uid', 'Text', 'Textarea', 'Textbtn', 'Textselect',
        'Ueditor', 'Select', 'Radio', 'Checkbox', 'Selects', 'Editor',
        'Linkage', 'Linkages'
    ])) {
        return 1;
    }

    return 0;
}

/**
 * 通过数组值查找数组key
 * @param $array 数组
 * @param $value 指定键值
 * @return 返回键值对应的键名
 */
function dr_get_array_key($array, $value) {

    if ($array && !in_array($value, $array)) {
        return false;
    }

    $new = array_flip($array);

    return isset($new[$value]) ? $new[$value] : false;
}

/**
 * 站点信息/项目信息的字段输出（自定义模板方式）
 * @param $name 字段名
 * @param $siteid 项目/站点id
 * @return 字段值
 */
function dr_site_info($name, $siteid = SITE_ID) {
    return \Phpcmf\Service::C()->get_cache('site', $siteid, 'config', $name);
}

/**
 * 站点信息/项目信息的字段输出（自定义字段方式）
 * @param $name 字段名
 * @param $siteid 项目/站点id
 * @return 字段值
 */
function dr_site_value($name, $siteid = SITE_ID) {
    return \Phpcmf\Service::C()->get_cache('site', $siteid, 'param', $name);
}

/**
 * ftable字段输出
 * @param $id 字段id
 * @param $value 存储值
 * @param $class 给table指定class
 * @return 表格
 */
function dr_get_ftable($id, $value, $class = '') {

    $field = \Phpcmf\Service::C()->get_cache('table-field', $id);
    if (!$field) {
        return 'Ftable字段没有得到缓存数据';
    }

    return \Phpcmf\Service::L('Field')->get('ftable')->show_table($field, $value, $class);
}

/**
 * ftable字段数组
 * @param $id 字段id
 * @param $value 存储值
 * @return 表格输出
 */
function dr_get_ftable_array($id, $value) {

    $field = \Phpcmf\Service::C()->get_cache('table-field', $id);
    if (!$field) {
        return [];
    }

    $value = dr_string2array($value);
    if ($value) {
        $img = [];
        foreach ($field['setting']['option']['field'] as $n => $t) {
            if ($t['type']) {
                if ($t['type'] == 3) {
                    // 图片
                    $img[] = $n;
                }
            }
        }
        $rt = [];
        foreach ($value as $row) {
            if ($img) {
                foreach ($img as $i) {
                    $row[$i] = dr_get_file($row[$i]);
                }
            }
            $rt[] = $row;
        }
        return $rt;
    }

    return $value;
}

/**
 * 获取内容中的缩略图
 * @param $value 内容值
 * @param $num 指定获取数量
 * @return 在变量中提取img标签的图片路径到数组
 */
function dr_get_content_img($value, $num = 0) {
    return dr_get_content_url($value, 'src', 'gif|jpg|jpeg|png', $num);
}

/**
 * 获取内容中的指定标签URL地址
 * @param $value 内容值
 * @param $attr 标签值，例如src
 * @param $ext 指定扩展名，例如jpg|gif
 * @param $num 指定获取数量
 * @return 在变量中提取img标签的图片路径到数组
 */
function dr_get_content_url($value, $attr, $ext, $num = 0) {

    $rt = [];
    if (!$value) {
        return $rt;
    }

    $ext = str_replace(',', '|', $ext);
    $value = preg_replace('/\.('.$ext.')@(.*)(\'|")/iU', '.$1$3', $value);
    if (preg_match_all("/(".$attr.")=([\"|']?)([^ \"'>]+\.(".$ext."))\\2/i", $value, $imgs)) {
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
 * @param $dir 插件英文名
 * @return bool
 */
function dr_is_app($dir) {
    return is_file(dr_get_app_dir($dir).'/install.lock');
}

/**
 * 模块是否被安装
 * @param $dir 模块英文名
 * @param $siteid 站点id
 * @return bool
 */
function dr_is_module($dir, $siteid = SITE_ID) {
    return \Phpcmf\Service::L('cache')->get('module-'.$siteid, $dir) ? 1 : 0;
}

/**
 * 字符串替换函数
 * @param $str 指定字符串
 * @param $o 需要替换的值
 * @param $t 替换后的值
 * @return 进行str_replace运算
 */
function dr_rp($str, $o, $t) {
    return str_replace($o, $t, (string)$str);
}

/**
 * 替换模板参数特殊字符
 * @param $str 指定字符串
 * @param $rt 正向或者反向
 * @return 特殊字符替换
 */
function dr_rp_view($str, $rt = 0) {

    $a = [
        '=',
        ' '
    ];
    $b = [
        '_FINECMS_ET_',
        '_XUNRUICMS_SK_',
    ];

    return $rt ? str_replace($b, $a, (string)$str) : str_replace($a, $b, (string)$str);
}

/**
 * 二维码调用
 * @param $text 指定字符串
 * @param $thumb 中间图片
 * @param $level 等级字母
 * @param $size 大小值
 * @return 生成二维码图片url
 */
function dr_qrcode($text, $thumb = '', $level = 'H', $size = 5) {
    return ROOT_URL.'index.php?s=api&c=api&m=qrcode&thumb='.urlencode($thumb).'&text='.urlencode($text).'&size='.$size.'&level='.$level;
}

/**
 * 秒转化时间
 * @param $times 多少秒
 * @return 返回秒对于的时分秒值
 */
function dr_sec2time($times){

    $times = intval($times);
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
 * @param $value json字符
 * @param $limit 限定返回几个值
 * @return 格式化多文件数组
 */
function dr_get_files($value, $limit = '') {

    $data = [];
    $value = dr_string2array($value, $limit);
    if (!$value) {
        return $data;
    } elseif (!isset($value['file']) && !isset($value['id'])) {
        return $value;
    }

    $arr = isset($value['id']) && $value['id'] ? $value['id'] : $value['file'];
    foreach ($arr as $i => $file) {
        if ($file) {
            $data[] = [
                'url' => dr_get_file($file), // 对应文件的url
                'file' => $file, // 对应文件或附件id
                'title' => $value['title'][$i], // 对应标题
                'description' => $value['description'][$i], // 对应描述
            ];
        }
    }

    return $data;
}

/**
 * 格式化图片专用数组
 * @param $value json字符
 * @return 格式化图片专用数组
 */
function dr_get_image($value) {

    $data = [];
    $value = dr_string2array($value);
    if (!$value) {
        return $data;
    }

    foreach ($value as $i => $file) {
        if ($file) {
            $data[] = [
                'id' => $file, // 对应文件或附件id
                'file' => dr_get_file($file), // 对应文件的url
            ];
        }
    }

    return $data;
}

/**
 * 文件上传临时目录
 * @return 文件上传临时目录
 */
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
 * 星级显示
 * @param $num 数字
 * @param $shifen 按十分计算
 * @return 星级显示
 */
function dr_star_level($num, $shifen = 0) {

    $lv = 5;
    $shifen && $num = (int)$num/2;
    $num = floatval($num);
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
 * 格式化sql创建
 * @param $sql
 * @return 格式化sql创建
 */
function dr_format_create_sql($sql) {

    if (!$sql) {
        return '';
    }

    //$sql = trim(str_replace('ENGINE=MyISAM', 'ENGINE=InnoDB', $sql));
    $sql = trim(str_replace('CHARSET=utf8 ', 'CHARSET=utf8mb4 COLLATE utf8mb4_unicode_ci ', $sql));

    return $sql;
}

/**
 * 获取cms域名部分
 * @param $url 指定url
 * @return 从指定url中获取cms域名部分
 */
function dr_cms_domain_name($url) {

    if (!$url) {
        return '';
    }

    $param = parse_url($url);
    if (isset($param['host']) && $param['host']) {
        return $param['host'];
    }

    return $url;
}

/**
 * 多语言输出
 * @param $param 指定文字
 * @return 将指定文字转换成系统对于的语言文字
 */
function dr_lang(...$param) {

    if (empty($param)) {
        return '';
    }

    // 取第一个作为语言名称
    $string = $param[0];
    unset($param[0]);

    // 调用语言包内容
    $string = \Phpcmf\Service::L('lang')->text($string);
    if ($param) {
        foreach ($param as $k => $t) {
            $param[$k] = \Phpcmf\Service::L('lang')->text($t);
        }
        return vsprintf($string, $param);
    }

    return $string;
}

/**
 * 获取终端列表
 * @return 获取终端列表
 */
function dr_client_data() {

    $rt = [
        'pc' => 'PC端',
        'mobile' => '移动端',
    ];
    $rt2 = \Phpcmf\Service::R(WRITEPATH.'config/app_client.php');
    if ($rt2) {
        $rt = $rt + $rt2;
    }

    return $rt;
}

// 获取url在导航的id
function dr_navigator_id($type, $markid) {
    return (int)\Phpcmf\Service::L('cache')->get('navigator-'.SITE_ID.'-url', $type, $markid);
}

/**
 * 格式化编辑器内容数据
 * @param $value 指定文字
 * @param $title title标题值
 * @return 将UEDITOR_IMG_TITLE替换成指定的标题
 */
function dr_ueditor_html($value, $title = '') {

    if (!$value) {
        return '';
    }

    return UEDITOR_IMG_TITLE ? str_replace(UEDITOR_IMG_TITLE, $title, htmlspecialchars_decode($value)) : htmlspecialchars_decode($value);
}

/**
 * 获取域名部分
 * @param $url
 * @return 从$url中获取到域名
 */
function dr_get_domain_name($url) {

    if (!$url) {
        return '';
    }

    list($url) = explode(':', str_replace(['https://', 'http://', '/'], '', $url));

    return $url;
}

/**
 * 按百分比分割数组
 * @param $data 数组
 * @param $num 分成几等分
 * @return 将数组按百分比等分划分
 */
function dr_save_bfb_data($data, $num = 100) {

    $cache = [];
    $count = dr_count($data);
    if ($count > $num) {
        $pagesize = ceil($count/$num);
        for ($i = 1; $i <= $num; $i ++) {
            $cache[$i] = array_slice($data, ($i - 1) * $pagesize, $pagesize);
        }
    } else {
        for ($i = 1; $i <= $count; $i ++) {
            $cache[$i] = array_slice($data, ($i - 1), 1);
        }
    }

    return $cache;
}

/**
 * 会员头像存储目录
 * @param $uid
 * @return 按uid进行分配头像存储目录
 */
function dr_avatar_dir($uid) {

    if (!$uid) {
        return '';
    }

    $uid = abs(intval($uid));
    $uid = sprintf("%09d", $uid); //前边加0补齐9位，例如UID为31的用户变成 000000031
    $dir1 = substr($uid, 0, 3);  //取左边3位，即 000
    $dir2 = substr($uid, 3, 2);  //取4-5位，即00
    $dir3 = substr($uid, 5, 2);  //取6-7位，即00

    // 下面拼成用户头像路径，即000/00/00/
    return $dir1.'/'.$dir2.'/'.$dir3.'/';
}

/**
 * 会员头像存储路径
 * @return 返回头像存储目录和对于的访问url
 */
function dr_avatar_path() {

    //$config = \Phpcmf\Service::C()->get_cache('site', SITE_ID, 'image');
    $config = [
        'avatar_url' => defined('SYS_AVATAR_URL') ? SYS_AVATAR_URL : '',
        'avatar_path' => defined('SYS_AVATAR_PATH') ? SYS_AVATAR_PATH : '',
    ];

    if (!$config['avatar_path'] || !$config['avatar_url']) {
        return [
            SYS_UPLOAD_PATH.'member/',
            SYS_UPLOAD_URL.'member/'
        ];
    } elseif ((strpos($config['avatar_path'], '/') === 0 || strpos($config['avatar_path'], ':') !== false) && is_dir($config['avatar_path'])) {
        // 相对于根目录
        return [
            rtrim($config['avatar_path'], DIRECTORY_SEPARATOR).'/',
            trim($config['avatar_url'], '/').'/'
        ];
    } else {
        // 在当前网站目录
        return [
            ROOTPATH.trim($config['avatar_path'], '/').'/',
            (SYS_ATTACHMENT_REL ? FC_NOW_HOST : ROOT_URL).trim($config['avatar_path'], '/').'/'
        ];
    }
}

/**
 * 会员头像
 *
 * @param   $uid
 * @param   $fix 是否加时间戳后缀
 * @return  会员头像url
 */
if (!function_exists('dr_avatar')) {
    function dr_avatar($uid, $fix = 1) {

        if ($uid) {
            list($cache_path, $cache_url) = dr_avatar_path();
            $file = dr_avatar_dir($uid).$uid.'.jpg';
            // 钩子处理
            $rs = \Phpcmf\Hooks::trigger_callback('avatar_get', $cache_path, $file);
            if ($rs && isset($rs['code']) && $rs['code'] && $rs['msg']) {
                return $rs['msg'];
            }
            if (is_file($cache_path.$file)) {
                return $cache_url.$file.($fix ? '?time='.filemtime($cache_path.$file) : '');
            } elseif (is_file($cache_path.$uid.'.jpg')) {
                return $cache_url.$uid.'.jpg'.($fix ? '?time='.filemtime($cache_path.$uid.'.jpg') : '');
            }
        }

        return ROOT_THEME_PATH.'assets/images/avatar.png';
    }
}

/**
 * 调用会员详细信息（自定义字段需要手动格式化）
 *
 * @param   $uid    会员uid
 * @param   $name   输出字段
 * @param   $cache  缓存时间
 * @return  用户详情数组
 */
function dr_member_info($uid, $name = '', $cache = -1) {

    $data = \Phpcmf\Service::L('cache')->get_data('member-info-'.$uid);
    if (!$data) {
        $data = \Phpcmf\Service::M('member')->get_member($uid);
        if ($data) {
            $data['salt'] = '***';
            $data['password'] = '***';
        }
        SYS_CACHE && \Phpcmf\Service::L('cache')->set_data('member-info-'.$uid, $data, $cache > 0 ? $cache : SYS_CACHE_SHOW * 3600);
    }

    return $name ? $data[$name] : $data;
}

/**
 * 调用会员详细信息（自定义字段需要手动格式化）
 *
 * @param   $username   会员账号
 * @param   $name   输出字段
 * @param   $cache  缓存时间
 * @return  用户详情数组
 */
function dr_member_username_info($username, $name = '', $cache = -1) {

    $data = \Phpcmf\Service::L('cache')->get_data('member-info-name-'.$username);
    if (!$data) {
        $data = \Phpcmf\Service::M('member')->get_member(0, $username);
        if ($data) {
            $data['salt'] = '***';
            $data['password'] = '***';
        }
        SYS_CACHE && \Phpcmf\Service::L('cache')->set_data('member-info-name-'.$username, $data, $cache > 0 ? $cache : SYS_CACHE_SHOW * 3600);
    }

    return $name ? $data[$name] : $data;
}

/**
 * 执行函数
 */
function dr_list_function($func, $value, $param = [], $data = [], $field = [], $name = '') {

    if (!$func) {
        $dfunc = [
            'Uid' => 'uid',
            'Files' => 'files',
            'Image' => 'image',
            'Ueditor' => 'content',
            'Editor' => 'content',
            'Score' => 'score',
            'Date' => 'datetime',
            'Radio' => 'radio_name',
            'Select' => 'select_name',
            'Checkbox' => 'checkbox_name',
            'Linkage' => 'linkage_name',
            'Linkages' => 'linkages_name',
        ];
        $dname = [
            'title' => 'title',
            'catid' => 'catid',
            'author' => 'author',
            'displayorder' => 'save_text_value',
        ];
        if ($name && isset($dname[$name]) && $dname[$name]) {
            $func = $dname[$name];
        } elseif ($field['fieldtype'] && isset($dfunc[$field['fieldtype']]) && $dfunc[$field['fieldtype']]) {
            $func = $dfunc[$field['fieldtype']];
        } elseif (!$value) {
            return '';
        } else {
            return htmlspecialchars((string)$value);
        }
    }

    $obj = \Phpcmf\Service::L('Function_list');
    if (method_exists($obj, $func)) {
        return call_user_func_array([$obj, $func], [$value, $param, $data, $field]);
    } elseif (dr_is_call_function($func)) {
        return call_user_func_array($func, [$value, $param, $data, $field]);
    } else {
        log_message('debug', '你没有定义字段列表回调函数：'.$func);
    }

    return htmlspecialchars((string)$value);
}



/**
 * 联动菜单包屑导航
 *
 * @param   string  $code   联动菜单代码
 * @param   intval  $id     id
 * @param   string  $symbol 间隔符号
 * @param   string  $url    url地址格式，必须存在[linkage]，否则返回不带url的字符串
 * @param   string  $html   格式替换
 * @return  string
 */
function dr_linkagepos($code, $id, $symbol = ' > ', $url = '', $html = '') {

    if (!$code || !$id) {
        return '';
    }

    $url = $url ? urldecode($url) : '';

    $data = dr_linkage($code, $id, 0);
    if (!$data) {
        return '';
    }

    $name = [];
    $array = explode(',', $data['pids']);
    $array[] = $data['ii'];

    foreach ($array as $ii) {
        if ($ii) {
            $data = dr_linkage($code, $ii, 0);
            if ($url) {
                $curl = str_replace(
                    ['[linkage]', '{linkage}', '[id]', '{id}', '[iid]', '{iid}'],
                    [$data['id'], $data['id'], $data['ii'], $data['ii'], $data['iid'], $data['iid']],
                    $url
                );
                $name[] = ($html ? str_replace(['[url]', '[name]'], array($curl, $data['name']), $html) : "<a href=\"".$curl."\">{$data['name']}</a>");
            } else {
                $name[] = $data['name'];
            }
        }
    }

    return implode($symbol, array_unique($name));
}

/**
 * 联动菜单调用
 *
 * @param   string  $code   菜单代码
 * @param   intval  $id     菜单id
 * @param   intval  $level  调用级别，1表示顶级，2表示第二级，等等
 * @param   string  $name   菜单名称，如果有显示它的值，否则返回数组
 * @return  array
 */
function dr_linkage($code, $id, $level = 0, $name = '') {

    if (!$id) {
        return false;
    }

    // id 查询
    if (is_numeric($id)) {
        $id = dr_linkage_id($code, $id);
        if (!$id) {
            return false;
        }
    }

    $data = \Phpcmf\Service::L('cache')->get_file('data-'.$id, 'linkage/'.SITE_ID.'_'.$code.'/');
    if (!$data) {
        return false;
    }

    $pids = explode(',', $data['pids']);
    if ($level == 0) {
        return $name ? $data[$name] : $data;
    }

    if (!$pids) {
        return $name ? $data[$name] : $data;
    }

    $i = 1;
    foreach ($pids as $pid) {
        if ($pid) {
            if ($i == $level) {
                $link = dr_linkage($code, $pid, 0);
                return $name ? $link[$name] : $link;
            }
            $i++;
        }
    }

    return $name ? $data[$name] : $data;
}

/**
 * 联动菜单json数据
 *
 * @param   string  $code   菜单代码
 * @param   intval  $pid    菜单父级id或者别名
 * @return  array
 */
function dr_linkage_json($code) {

    if (!$code) {
        return [];
    }

    return \Phpcmf\Service::L('cache')->get_file('json', 'linkage/'.SITE_ID.'_'.$code.'/');
}

/**
 * 联动菜单列表数据
 *
 * @param   string  $code   菜单代码
 * @param   intval  $pid    菜单父级id或者别名
 * @return  array
 */
function dr_linkage_list($code, $pid) {

    if (!$code) {
        return false;
    }

    if ($pid && !is_numeric($pid)) {
        // 别名情况时获取id号
        $pid = dr_linkage_cname($code, $pid);
    }

    return \Phpcmf\Service::L('cache')->get_file('list-'.$pid, 'linkage/'.SITE_ID.'_'.$code.'/');
}

/**
 * 联动菜单的id号获取
 *
 * @param   string  $code   菜单代码
 * @param   string  $cname  别名
 * @return  array
 */
function dr_linkage_id($code, $cname) {

    if (!$code || !$cname) {
        return false;
    }

    $ids = \Phpcmf\Service::L('cache')->get_file('id', 'linkage/'.SITE_ID.'_'.$code.'/');
    if (isset($ids[$cname]) && $ids[$cname]) {
        return $ids[$cname];
    }

    return false;
}

/**
 * 联动菜单的别名获取
 *
 * @param   string  $code   菜单代码
 * @param   int     $id     id
 * @return  array
 */
function dr_linkage_cname($code, $id) {

    if (!$code || !$id) {
        return 0;
    }

    $ids = array_flip(\Phpcmf\Service::L('cache')->get_file('id', 'linkage/'.SITE_ID.'_'.$code.'/'));
    if (isset($ids[$id]) && $ids[$id]) {
        return $ids[$id];
    }

    return 0;
}

/**
 * 联动菜单的最大层级
 *
 * @param   string  $code   菜单代码
 * @return  array
 */
function dr_linkage_level($code) {

    if (!$code) {
        return 0;
    }

    return (int)\Phpcmf\Service::L('cache')->get_file('level', 'linkage/'.SITE_ID.'_'.$code.'/');
}


/**
 * 支付表单调用
 * mark     表名-主键id-字段id
 * value    支付金额
 * title    支付说明
 * */
function dr_payform($mark, $value = 0, $title = '', $url = '',  $remove_div  = 1) {
    if (!dr_is_app('pay')) {
        return '没有安装「支付系统」插件';
    }
    return \Phpcmf\Service::M('Pay', 'pay')->payform($mark, $value, $title, $url, $remove_div);
}

/**
 * 字段表单调用
 * field    字段配置
 * value    默认值
 * remove_div 移除div区域
 * load_js 重新加载js文件
 * */
function dr_fieldform($field, $value = '', $remove_div  = 1, $load_js = 0) {

    if (!$field) {
        return '字段数据不存在';
    }

    $field = dr_string2array($field);
    if (!$field['fieldtype']) {
        return '字段类别不存在';
    }

    $f = \Phpcmf\Service::L('Field')->get($field['fieldtype']);
    $f->remove_div = $remove_div;
    if ($load_js) {
        $f->set_load_js($field['fieldtype'], 0);
    }

    return $f->input($field, $value);
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
 * @param   intval  $id
 * @return  array
 */
function dr_block($id, $type = 0, $site = 0) {
    return \Phpcmf\Service::C()->get_cache('block-'.($site ? $site : SITE_ID), $id, $type);
}

// 是否是微信公众号
function dr_is_weixin_app() {
    $user = (string)$_SERVER['HTTP_USER_AGENT'];
    if (strpos($user, 'MicroMessenger')) {
        if (strpos($user, 'WindowsWechat')) {
            return 0;
        }
        return 1;
    }
    return 0;
}

/**
 * 全局变量调用
 *
 * @param   string  $name   别名
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
function dr_thumb_path($img = '') {


    $path = '';
    if ($img) {
        $md5 = md5($img);
        $path = substr($md5, 0, 1).substr($md5, -1, 1)
            .'/'.substr($md5, 2, 1).substr($md5, -2, 1).'/'.$md5;
    }

    $config = \Phpcmf\Service::C()->get_cache('site', SITE_ID, 'image');
    if (!$config['cache_path'] || !$config['cache_url']) {
        return [
            ROOTPATH.'uploadfile/thumb/',
            (SYS_ATTACHMENT_REL ? FC_NOW_HOST : ROOT_URL).'uploadfile/thumb/',
            $config['ext'],
            $path
        ];
    }

    if ((strpos($config['cache_path'], '/') === 0 || strpos($config['cache_path'], ':') !== false) && is_dir($config['cache_path'])) {
        // 相对于根目录
        return [
            rtrim($config['cache_path'], DIRECTORY_SEPARATOR).'/',
            trim($config['cache_url'], '/').'/',
            $config['ext'],
            $path
        ];
    } else {
        // 在当前网站目录
        return [
            ROOTPATH.trim($config['cache_path'], '/').'/',
            (SYS_ATTACHMENT_REL ? FC_NOW_HOST : ROOT_URL).trim($config['cache_path'], '/').'/',
            $config['ext'],
            $path
        ];
    }
}

// 缩略图
function dr_thumb($img, $width = 0, $height = 0, $water = 0, $mode = 'auto', $webimg = 0) {

    if (!$img) {
        return dr_url_rel(ROOT_THEME_PATH.'assets/images/nopic.gif');
    } elseif (is_array($img)) {
        return IS_DEV ? '文件参数不能是数组' : dr_url_rel(ROOT_THEME_PATH.'assets/images/nopic.gif');
    } elseif (!$width && !$height && !$water) {
        return dr_get_file($img).(IS_DEV ? '#没有设置高宽参数，将以原图输出' : '');
    } elseif (is_numeric($img) || $webimg) {

        list($cache_path, $cache_url, $ext, $path) = dr_thumb_path($img);

        // 强制缩略图水印
        if (defined('SITE_THUMB_WATERMARK') && SITE_THUMB_WATERMARK) {
            $water = 1;
        }

        if (!IS_DEV) {
            // 非开发者模式下读取缓存
            $cache_file = $path.'/'.$width.'x'.$height.($water ? '_water' : '').'_'.$mode.'.'.($ext ? 'webp' : 'jpg');
            if (is_file($cache_path.$cache_file)) {
                return dr_url_rel($cache_url.$cache_file);
            }
        }

        // 钩子处理
        $rs = \Phpcmf\Hooks::trigger_callback('thumb_get', $cache_path, $cache_file);
        if ($rs && isset($rs['code']) && $rs['code'] && $rs['msg']) {
            return $rs['msg'];
        }

        return dr_url_rel(\Phpcmf\Service::L('image')->thumb($img, $width, $height, $water, $mode, $webimg));
    }

    $file = dr_file($img);
    if ($file && CI_DEBUG && !is_numeric($img)) {
        $file.= '#图片不是数字id号，dr_thumb函数无法进行缩略图处理';
    }

    return $file ? $file : dr_url_rel(ROOT_THEME_PATH.'assets/images/nopic.gif');
}


/**
 * 文件真实地址
 */
function dr_get_file($id, $full = 0) {

    if (!$id) {
        return IS_DEV ? '文件参数没有值' : '';
    } elseif (is_array($id)) {
        return IS_DEV ? '文件参数不能是数组' : '';
    }

    if (is_numeric($id)) {
        // 表示附件id
        $info = \Phpcmf\Service::C()->get_attachment($id);
        if ($info['url']) {
            return $full ? $info['url'] : dr_url_rel($info['url']);
        }
    }

    $file = dr_file($id, $full);

    return $file ? $file : $id;
}

/**
 * 文件下载地址
 */
function dr_down_file($id, $name = '') {

    if (!$id) {
        return IS_DEV ? '文件参数不能为空' : '';
    } elseif (is_array($id)) {
        return IS_DEV ? '文件参数不能是数组' : '';
    }

    if (defined('SC_HTML_FILE')) {
        return dr_web_prefix("index.php?s=api&c=file&m=down&id=".$id."&name=".urlencode($name));
    }

    $sn = md5($id);
    \Phpcmf\Service::L('cache')->set_auth_data('down-file-'.$sn, [
        'id' => $id,
        'name' => $name,
    ]);

    return dr_web_prefix("index.php?s=api&c=file&m=down&id=".$sn);
}

/**
 * 根据附件信息获取文件地址
 *
 * @param   array   $data
 * @return  string
 */
function dr_get_file_url($data, $w = 0, $h = 0) {

    if (!$data) {
        return IS_DEV ? '文件信息不存在' : '';
    } elseif ($data['remote']) {
        $remote = \Phpcmf\Service::C()->get_cache('attachment', $data['remote']);
        if ($remote) {
            return $remote['url'].$data['attachment'];
        } else {
            return IS_DEV ? '自定义附件（'.$data['remote'].'）的配置已经不存在' : '';
        }
    } elseif ($w && $h && dr_is_image($data['fileext'])) {
        //return dr_thumb($data['id'], $w, $h, 0, 'crop');
        return dr_get_file($data['id']);
    }

    return SYS_UPLOAD_URL.$data['attachment'];
}

/**
 * 任意字段的选项值（用于options参数的字段，如复选框、下拉选择框、单选按钮）
 *
 * @param   intval  $id
 * @return  array
 */
function dr_field_options($id) {

    if (!$id) {
        return '';
    }

    $data = \Phpcmf\Service::L('cache')->get_data('field-info-'.$id);
    if (!$data) {
        $field = \Phpcmf\Service::C()->get_cache('table-field', $id);
        if (!$field) {
            return '';
        }
        $data = dr_format_option_array($field['setting']['option']['options']);
        if (!$data) {
            return '';
        }
        // 存储缓存
        \Phpcmf\Service::L('cache')->set_data('field-info-'.$id, $data, 10000);
    }

    return $data;
}

/**
 * 任意字段的属性数组
 *
 * @param   intval  $id
 * @return  array
 */
function dr_field_setting(...$param) {

    if (empty($param)) {
        return [];
    }

    // 取第一个作为字段id
    $id = $param[0];
    unset($param[0]);

    if (!$id) {
        return [];
    }

    $data = \Phpcmf\Service::L('cache')->get_data('field-setting-'.$id);
    if (!$data) {
        $field = \Phpcmf\Service::C()->get_cache('table-field', $id);
        if (!$field) {
            return [];
        }
        $data = dr_string2array($field['setting']);
        if (!$data) {
            return [];
        }
        // 存储缓存
        \Phpcmf\Service::L('cache')->set_data('field-setting-'.$id, $data, 10000);
    }

    if (!$param) {
        return $data;
    }

    return dr_get_param_var($data, $param);
}

function dr_get_param_var($return, $param = []) {

    if (!$param) {
        return $return;
    }

    if (!is_array($param)) {
        $param = [$param];
    }

    foreach ($param as $v) {
        $var = (!$v ? 0 : dr_safe_replace($v));
        if (isset($return[$var])) {
            $return = $return[$var];
        } else {
            return null;
        }
    }

    return $return;
}

function dr_rp_param_var($tpl, $param) {

    if (empty($param) || !is_array($param)) {
        return $tpl;
    }

    foreach ($param as $var => $val) {
        if (is_array($val)) {
            continue;
        }
        $tpl = str_replace('$'.$var, (string)$val, $tpl);
        $tpl = str_replace('{'.$var.'}', (string)$val, $tpl);
    }

    return $tpl;
}

// 提醒说明
function dr_notice_info() {

    if (is_file(WRITEPATH . 'config/notice.php')) {
        $data = \Phpcmf\Service::R(WRITEPATH . 'config/notice.php');
        if (is_array($data) && $data) {
            foreach ($data as $i => $t) {
                if ($t['name']) {
                    $data[$i]['name'] = dr_lang($data[$i]['name']);
                }
            }
            return $data;
        }
    }

    return [
        1 => [
            'name' => dr_lang('系统'),
            'icon' => 'fa fa-bell-o',
        ],
        2 => [
            'name' => dr_lang('用户'),
            'icon' => 'fa fa-user',
        ],
        3 => [
            'name' => dr_lang('内容'),
            'icon' => 'fa fa-th-large',
        ],
        4 => [
            'name' => dr_lang('应用'),
            'icon' => 'fa fa-puzzle-piece',
        ],
        5 => [
            'name' => dr_lang('交易'),
            'icon' => 'fa fa-rmb',
        ],
        6 => [
            'name' => dr_lang('订单'),
            'icon' => 'fa fa-shopping-cart',
        ],
    ];
}

// 提醒说明更新
function dr_notice_update($id, $name = '', $icon = '') {

    if (!$id) {
        return;
    }

    $data = dr_notice_info();
    if ($name) {
        $data[$id] = [
            'name' => $name,
            'icon' => $icon,
        ];
    } else {
        unset($data[$id]);
    }

    \Phpcmf\Service::R(WRITEPATH . 'config/notice.php', true);
    \Phpcmf\Service::L('config')->file(WRITEPATH . 'config/notice.php', '设置自定义消息类型')->to_require($data);
}

// 提醒图标
function dr_notice_icon($type, $c = '') {

    $data = dr_notice_info();
    if ($data && isset($data[$type]) && $data[$type]) {
        return '<i class="'.$data[$type]['icon'].'"></i>';
    }

    return '<i class="fa fa-envelope"></i>';
}

/**
 * 验证用户权限(废弃)
 * my   我的authid
 * auth 目标权限组
 * return 1有权限 0无权限
 */
function dr_member_auth($my, $auth) {
    return 1;
}

/**
 * 用于用户权限取取反值(废弃)
 */
function dr_member_auth_id($authid, $postid) {
    return [];
}

/**
 * 获取折扣价格值
 * @param $value 价格值
 * @param $zhe 折扣值
 * @return 折扣计算后的值
 */
function dr_zhe_price($value, $zhe) {

    if (!$value) {
        return 0;
    }

    return (float)max(0, (int)$value * ($zhe/100));
}

/**
 * 获取价格值
 * @param $value 价格值
 * @param $num 小数位
 * @return 计算后的值
 */
function dr_price_value($value, $num = 2) {
    return $value ? number_format(floatval($value), (int)$num) : 0;
}

/**
 * sku 获取属性值名称
 * @param $value 字段值
 * @param $sku sku数组
 * @param $name 属性key
 * @return 属性名称
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
 * @param $value 字段值
 * @param $number 小数位
 * @param $join 连接符号
 * @param $zhe 折扣值
 * @return 最终计算值
 */
function dr_sku_price($value, $number = 2, $join = ' - ', $zhe = 0) {

    $value = dr_string2array($value);
    if (!$value || !is_array($value['value'])) {
        return 0;
    }

    $price = [];
    $number = (int)$number;
    foreach ($value['value'] as $t) {
        $price[] = (float)$t['price'];
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
 * sku 获取名称
 * @param $key sku字符串
 * @param $data 主题数组
 * @param $type 默认
 * @return 属性名称
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
            if ($sku && in_array("{$gid}_{$vid}", $sku)) {
                $value[$gname] = $vname;
                $string[] = $gname.'：'.$vname;
            }

        }
    }

    return $type ? [$value, implode(' ', $string)] : implode(' ', $string);
}


/**
 * 下一个升级值
 * @param $array 用户组数组
 * @param $id 组id号
 * @return 下一个升级值
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
 * @param $ip 运行者ip地址
 * @return 返回逻辑值
 */
function dr_html_auth($ip = 0) {

    if (is_cli()) {
        return 1;//
    }

    if ($ip) {
        // 存储值
        return \Phpcmf\Service::L('cache')->set_auth_data(md5('html_auth'.(strlen($ip) > 5 ? $ip : \Phpcmf\Service::L('input')->ip_address())), 1);
    } else {
        // 读取判断
        $rt = \Phpcmf\Service::L('cache')->get_auth_data(md5('html_auth'.\Phpcmf\Service::L('input')->ip_address()));
        if ($rt) {
            return 1; // 有效
        } else {
            return 0;
        }
    }
}

/**
 * 付款方式显示
 * @param $name 支付名
 * @return 返回数组
 */
function dr_pay_type_html($name) {
    return dr_pay_name($name);
}

/**
 * 付款方式显示
 * @param $name 支付名
 * @return 返回数组
 */
function dr_pay_name($name) {
    if (!dr_is_app('pay')) {
        return '没有安装「支付系统」插件';
    }
    return \Phpcmf\Service::M('Pay', 'pay')->payname($name);
}

/**
 * 付款方式的名称
 * @param $name 支付名
 * @return 返回数组
 */
function dr_pay_type($name) {
    if (!dr_is_app('pay')) {
        return '没有安装「支付系统」插件';
    }
    return dr_clearhtml(\Phpcmf\Service::M('Pay', 'pay')->paytype($name));
}

/**
 * 付款状态的名称
 * @param $data 支付记录数据
 * @return 返回状态
 */
function dr_pay_status($data) {
    if (!dr_is_app('pay')) {
        return '没有安装「支付系统」插件';
    }
    return dr_clearhtml(\Phpcmf\Service::M('Pay', 'pay')->paystatus($data));
}

/**
 * 付款金额显示
 * @param $data 价格值
 * @param $v 小数位
 * @return 返回带html的金额值标签
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
 * @param $value 字符串
 * @return 返回字符串
 */
function dr_clear_empty($value) {

    if (!$value) {
        return '';
    }

    return str_replace(['　', ' '], '', trim($value));
}

/**
 * 列表字段进行排序筛选
 * @param $field 字段列表数组
 * @return 返回过滤后的数组
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
        $t && $sys_field[$t['fieldname']] = $t;
    }

    $rt = [];
    foreach ($value as $name => $t) {
        if ($t && $t['name']) {
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
 * 两数组追加合并
 * @param $a1 数组1
 * @param $a2 数组2
 * @return 返回合并后的数组
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
 * 两数组覆盖合并
 * @param $a1 1是老数据
 * @param $a2 2是新数据
 * @return 返回处理后的数组
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
 * 判断是否启用了建站系统插件
 */
function dr_is_use_module() {

    if (!IS_USE_MODULE) {
        return 0;
    }

    if (is_file(IS_USE_MODULE.'/install.lock')) {
        return IS_USE_MODULE;
    }

    return 0;
}

/**
 * 站点表前缀
 * @param $table 表名
 * @param $siteid 站点id
 * @return 返回当前站点对应的表名称
 */
function dr_site_table_prefix($table, $siteid = SITE_ID) {
    return $siteid.'_'.$table;
}

/**
 * 模块表前缀
 * @param $dir 模块目录
 * @param $siteid 站点id
 * @return 返回当前站点对应的表名称
 */
function dr_module_table_prefix($dir, $siteid = SITE_ID) {
    return $siteid.'_'.$dir;
}

/**
 * 模块表单前缀
 * @param $dir 模块目录
 * @param $table 表名
 * @param $siteid 站点id
 * @return 返回当前站点对应的表名称
 */
function dr_mform_table_prefix($dir, $table, $siteid = SITE_ID) {
    return $siteid.'_'.$dir.'_form_'.$table;
}

/**
 * 网站表单表前缀
 * @param $dir 表单名
 * @param $siteid 站点id
 * @return 返回当前站点对应的表名称
 */
function dr_form_table_prefix($dir, $siteid = SITE_ID) {
    return $siteid.'_form_'.$dir;
}

/**
 * 返回图标
 * @param $value 原定的图标
 * @return 如没有原地图标就返回默认图标
 */
function dr_icon($value) {
    return $value ? $value : 'fa fa-table';
}

/**
 * 完整的文件URL
 * @param $url 文件参数
 * @param $full 是否补全绝对域名
 * @return 返回文件的完整url地址
 */
function dr_file($url, $full = 0) {

    if (!$url || dr_strlen($url) == 1) {
        return '';
    } elseif (substr($url, 0, 7) == 'http://' || substr($url, 0, 8) == 'https://') {
        return $full ? $url : dr_url_rel($url);
    } elseif (substr($url, 0, 1) == '/') {
        $url = ROOT_URL.substr($url, 1);
        return $full ? $url : dr_url_rel($url);
    }

    $url = SYS_UPLOAD_URL.$url;
    return $full ? $url : dr_url_rel($url);
}

/**
 * 根据文件扩展名获取文件预览信息
 * @param $value 文件路径参数
 * @param $id 文件id值
 * @return 返回文件可预览的img标签
 */
function dr_file_preview_html($value, $id = 0) {

    if (!$value) {
        return '';
    }

    $ext = trim(strtolower(strrchr($value, '.')), '.');
    if (dr_is_image($ext)) {
        $value = dr_file($value);
        if ($id && ((isset($_POST['is_admin']) && intval($_POST['is_admin']) == 1) || IS_ADMIN)) {
            return '<a href="javascript:dr_preview_image(\''.$value.'\');"><img src="'.$value.'?r='.SYS_TIME.'"></a>
            </div>
            <div class="mpreview">
            <a title="'.dr_lang('剪辑图片').'" href="javascript:dr_iframe(\''.dr_lang('剪辑').'\', \''.(IS_ADMIN ? SELF.'?c=api' : 'index.php?s=api&c=file').'&m=image_edit&id='.$id.'\', \'80%\', 0, \'nogo\');"><i class="fa fa-edit"></i></a>
            
            ';
            return '<a href="javascript:dr_iframe(\''.dr_lang('剪辑').'\', \''.(IS_ADMIN ? SELF.'?c=api' : 'index.php?s=api&c=file').'&m=image_edit&id='.$id.'\', \'80%\', 0, \'nogo\');"><img src="'.$value.'"></a>';
        } else {
            return '<a href="javascript:dr_preview_image(\''.$value.'\');"><img src="'.$value.'"></a>';
        }
    } elseif ($ext == 'mp4') {
        $value = dr_file($value);
        return '<a href="javascript:dr_preview_video(\''.$value.'\')"><img src="'.ROOT_THEME_PATH.'assets/images/ext/mp4.png'.'"></a>';
    } elseif (is_file(ROOTPATH.'static/assets/images/ext/'.$ext.'.png')) {
        $file = ROOT_THEME_PATH.'assets/images/ext/'.$ext.'.png';
        return '<a href="javascript:dr_preview_url(\''.dr_file($value).'\');"><img src="'.$file.'"></a>';
    } else {
        $file = ROOT_THEME_PATH.'assets/images/ext/url.png';
        return '<a href="javascript:dr_preview_url(\''.$value.'\');"><img src="'.$file.'"></a>';
    }
}

// 用于附件列表查看时
function dr_file_list_preview_html($t) {
    if (dr_is_image($t['fileext'])) {
        $img = '<img class="rs-load" src="'.ROOT_THEME_PATH.'assets/images/loading-1.gif" rs-src="'.dr_get_file_url($t, 50, 50).'">';
        return '<a href="javascript:dr_preview_image(\''.dr_get_file_url($t).'\');">'.$img.'</a>';
    } elseif ($t['fileext'] == 'mp4') {
        return '<a href="javascript:dr_preview_video(\''.dr_get_file_url($t).'\');"><img src="'.ROOT_THEME_PATH.'assets/images/ext/'.$t['fileext'].'.png"></a>';
    } elseif (is_file(ROOTPATH.'static/assets/images/ext/'.$t['fileext'].'.png')) {
        return '<a href="javascript:dr_preview_url(\''.dr_get_file_url($t).'\');"><img src="'.ROOT_THEME_PATH.'assets/images/ext/'.$t['fileext'].'.png"></a>';
    } else {
        return '<a href="javascript:dr_preview_url(\''.dr_get_file_url($t).'\');"><img src="'.ROOT_THEME_PATH.'assets/images/ext/error.png"></a>';
    }
}

if (! function_exists('dr_is_image')) {
    /**
     * 文件是否是图片
     * @param $value 文件路径参数
     * @return 判断这个是否是一张图片
     */
    function dr_is_image($value)
    {
        if (!$value) {
            return false;
        }

        return in_array(
            strpos($value, '.') !== false ? trim(strtolower(strrchr($value, '.')), '.') : $value,
            ['jpg', 'gif', 'png', 'jpeg', 'webp']
        );
    }
}

/**
 * 格式化复选框\单选框\选项值
 * @param $value 参数
 * @return 字符串转换为数组
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
            $data[htmlspecialchars($v)] = htmlspecialchars($n);
        }
    }

    return $data;
}

/**
 * 字段输出表单（废弃）
 */
function dr_field_input($name, $type, $option, $value = '', $id = 0) {
    return '';
}

/**
 * 目录列表获取
 *
 * @param   $source_dir  源目录
 * @param   $directory_depth 目录纵深 0全目录 1当前目录
 * @param   $hidden    是否包含隐藏目录
 * @return  整个目录名的数组格式
 */
function dr_dir_map($source_dir, $directory_depth = 0, $hidden = FALSE) {

    if ($source_dir && $fp = opendir($source_dir)) {

        $filedata = [];
        $new_depth = $directory_depth - 1;
        $source_dir = rtrim($source_dir, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;

        while (FALSE !== ($file = readdir($fp))) {
            if ($file === '.' OR $file === '..'
                OR ($hidden === FALSE && $file[0] === '.')
                OR !is_dir($source_dir.$file)) {
                continue;
            }
            if (($directory_depth < 1 OR $new_depth > 0)
                && is_dir($source_dir.$file)) {
                $filedata[$file] = dr_dir_map($source_dir.DIRECTORY_SEPARATOR.$file, $new_depth, $hidden);
            } else {
                $filedata[] = $file;
            }
        }
        closedir($fp);
        return $filedata;
    }

    return [];
}

/**
 * 文件列表获取
 *
 * @param   $source_dir  源目录
 * @param   $directory_depth 目录纵深 0全目录 1当前目录
 * @param   $hidden    是否包含隐藏目录
 * @return  整个文件名的数组格式
 */
function dr_file_map($source_dir) {

    if ($source_dir && $fp = opendir($source_dir)) {

        $filedata = [];
        $source_dir = rtrim($source_dir, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;

        while (FALSE !== ($file = readdir($fp))) {
            if ($file === '.' OR $file === '..'
                OR $file[0] === '.'
                OR !is_file($source_dir.$file)) {
                continue;
            }
            $filedata[] = $file;
        }
        closedir($fp);
        return $filedata;
    }

    return [];
}

/**
 * 数据返回统一格式
 * @param $code 状态码 0失败 >1表示成功
 * @param $msg 提示文字
 * @param $data 传输数组
 * @param $extend 根附加数组
 * @return 返回统一的数组格式
 */
function dr_return_data($code, $msg = '', $data = [], $extend = []) {

    $rt = [
        'code'  => $code,
        'msg'   => $msg,
        'data'  => $data,
    ];
    if ($extend) {
        foreach ($extend as $i => $t) {
            if (!isset($rt[$i])) {
                $rt[$i] = $t;
            }
        }
    }

    return $rt;
}

/**
 * 提交表单默认隐藏域
 * @param $data 可填充的隐藏域数组格式
 * @return 表单隐藏域控件代码
 */
function dr_form_hidden($data = []) {

    $form = '<input name="is_form" type="hidden" value="1">'.PHP_EOL;
    $form.= '<input name="is_admin" type="hidden" value="'.(\Phpcmf\Service::C()->member && \Phpcmf\Service::C()->member['is_admin'] ? 1 : 0).'">'.PHP_EOL;
    $form.= '<input name="is_tips" type="hidden" value="">'.PHP_EOL;
    $form.= '<input name="'.csrf_token().'" type="hidden" value="'.csrf_hash().'">'.PHP_EOL;
    if ($data) {
        foreach ($data as $name => $value) {
            $form.= '<input name="'.$name.'" id="dr_'.$name.'" type="hidden" value="'.$value.'">'.PHP_EOL;
        }
    }

    return $form;
}

/**
 * 验证csrf字符串
 */
function dr_get_csrf_token() {

    $code = \Phpcmf\Service::C()->session()->get('auth_csrf_token');
    if (!$code) {
        $code = bin2hex(random_bytes(16));
        //\Phpcmf\Service::L('cache')->set_auth_data('csrf_token', $code, 1);
        \Phpcmf\Service::C()->session()->set('auth_csrf_token', $code);
    }

    return $code;
}

/**
 * 搜索表单隐藏域
 * @param $p 可填充的隐藏域数组格式
 * @return 表单隐藏域控件代码
 */
function dr_form_search_hidden($p = []) {

    $form = '';
    if ($_GET['app']) {
        $form.= '<input name="app" type="hidden" value="'.$_GET['app'].'">'.PHP_EOL;
        $form.= '<input name="s" type="hidden" value="'.(IS_MEMBER ? 'member' : APP_DIR).'">'.PHP_EOL;
    } elseif (IS_API && !APP_DIR) {
        $form.= '<input name="s" type="hidden" value="api">'.PHP_EOL;
    } else {
        $form.= '<input name="s" type="hidden" value="'.APP_DIR.'">'.PHP_EOL;
    }
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
 * @param   $string 参数
 * @return  加密后的字符串
 */
function dr_base64_encode($string) {

    if (!$string) {
        return '';
    }

    $data = base64_encode($string);
    $data = str_replace(['+', '/', '='], ['-', '_', ''], $data);

    return $data;
}

/**
 * Base64解密
 *
 * @param   $string 参数
 * @return  解密后的值
 */
function dr_base64_decode($string) {

    if (!$string) {
        return '';
    }

    $data = str_replace(['-', '_'], ['+', '/'], $string);
    $mod4 = strlen($data) % 4;
    $mod4 && $data.= substr('====', $mod4);

    return base64_decode($data);
}

/**
 * 网站风格目录
 *
 * @return  网站风格目录数组
 */
function dr_get_theme() {

    if (!function_exists('dr_dir_map')) {
        return ['default'];
    }

    return array_diff(dr_dir_map(ROOTPATH.'static/', 1), ['assets']);
}

/**
 * 获取6位数字随机验证码
 */
function dr_randcode() {
    return \Phpcmf\Service::L('Form')->get_rand_value();
}

/**
 * 删除目录及目录下面的所有文件
 *
 * @param    $dir        路径
 * @param   $is_all     包括删除当前目录
 * @return  如果成功则返回 TRUE，失败则返回 FALSE
 */
function dr_dir_delete($path, $del_dir = FALSE, $htdocs = FALSE, $_level = 0)
{

    if (!$path) {
        return false;
    }

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
                unlink($filepath);
            }
        }
    }

    closedir($current_dir);
    $_level > 0  && rmdir($path); // 删除子目录

    return $del_dir && $_level == 0 ? rmdir($path) : TRUE;
}


/**
 * 基于本地存储的加解密算法
 * @param $string 传入字符串
 * @param $operation DECODE是解密，否则是加密
 * @return 返回加解密后的值
 */
function dr_authcode($string, $operation = 'DECODE') {

    if (!$string) {
        return '';
    }

    is_array($string) && $string = dr_array2string($string);

    if ($operation == 'DECODE') {
        // 解密
        return \Phpcmf\Service::L('cache')->get_auth_data($string, 1);
    } else {
        // 加密
        \Phpcmf\Service::L('cache')->set_auth_data(md5($string), $string, 1);
        return md5($string);
    }
}

/**
 * 当前浏览器的URL
 */
function dr_now_url() {
    return \Phpcmf\Service::L('input')->xss_clean(IS_ADMIN ? str_replace(FC_NOW_HOST, '/', FC_NOW_URL) : FC_NOW_URL);
}

/**
 * 验证码图片获取
 * @param $width 宽度
 * @param $height 高度
 * @param $url 废弃
 * @return 返回验证码img标签的格式
 */
function dr_code($width, $height, $url = '') {
    $url = dr_web_prefix('index.php?s=api&c=api&m=captcha&width='.$width.'&height='.$height);
    return '<img align="absmiddle" style="cursor:pointer;" onclick="this.src=\''.$url.'&t=\'+Math.random();" src="'.$url.'" />';
}

/**
 * 排序字符串转换操作
 * @param $name 字段名称
 * @return 根据浏览器order参数返回对应的字符串
 */
function dr_sorting($name) {

    $value = $_GET['order'] ? $_GET['order'] : '';
    if (!$value || !$name) {
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
 * @param $url 指定url地址
 * @return 把url中的order参数移除
 */
function dr_member_order($url) {

    if (!$url) {
        return '';
    }

    $data = explode('&', $url);
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
 * 用户等级 显示星星
 *
 * @param    $num
 * @param   $starthreshold  星星数在达到此阈值(设为 N)时，N 个星星显示为 1 个月亮、N 个月亮显示为 1 个太阳。
 * @return  img标签值
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
 * 动态调用模板
 * @param $id div控件的ID名
 * @param $filename 模板文件名
 * @param $param_str 附加URL参数
 * @return 返回ajax调用代码
 */
function dr_ajax_template($id, $filename, $param_str = '') {
    $error = IS_DEV && !defined('SC_HTML_FILE') ? ', error: function(HttpRequest, ajaxOptions, thrownError) {  var msg = HttpRequest.responseText;layer.open({ type: 1, title: "'.dr_lang('系统故障').'", fix:true, shadeClose: true, shade: 0, area: [\'50%\', \'50%\'],  content: "<div style=\"padding:10px;\">"+msg+"</div>"  }); } ' : '';
    return "<script type=\"text/javascript\"> $.ajax({ type: \"GET\", url:\"".dr_web_prefix("index.php?s=api&c=api&m=template&format=jsonp&name={$filename}&".$param_str)."\", dataType: \"jsonp\", success: function(data){ $(\"#{$id}\").html(data.msg); } {$error} });</script>";
}

/**
 * https进行post数据
 * @param $url 请求地址
 * @param $param 请求参数数组
 * @return 返回信息
 */
function dr_post_json_data($url, $param = []) {

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
    curl_setopt ( $ch, CURLOPT_POSTFIELDS, $param);
    curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, true );
    $response = curl_exec ( $ch );
    if ($error=curl_error($ch)){
        return dr_return_data(0, $error);
    }

    curl_close($ch);
    $data = json_decode($response, true);
    if (!$data) {
        return dr_return_data(0, $response);
    }

    return dr_return_data(1, 'ok', $data);
}

/**
 * 调用远程数据 curl获取
 *
 * @param   $url
 * @param   $timeout 超时时间，0不超时
 * @param   $is_log 0表示请求失败不记录到系统日志中
 * @param   $ct 0表示不尝试重试，1表示重试一次
 * @return  请求结果值
 */
function dr_catcher_data($url, $timeout = 0, $is_log = true, $ct = 0) {

    if (!$url) {
        return '';
    }

    // 获取本地文件
    if (strpos($url, 'file://')  === 0) {
        return file_get_contents($url);
    } elseif (strpos($url, '/')  === 0 && is_file(WEBPATH.$url)) {
        return file_get_contents(WEBPATH.$url);
    } elseif (!dr_is_url($url)) {
        if (CI_DEBUG && $is_log) {
            log_message('error', '获取远程数据失败['.$url.']：地址前缀要求是http开头');
        }
        return '';
    }

    // curl模式
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        if (substr($url, 0, 8) == "https://") {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // 跳过证书检查
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, true); // 从证书中检查SSL加密算法是否存在
        }
        if ($ct) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'User-Agent: Mozilla/5.0 (X11; Linux x86_64; rv:40.0)' . 'Gecko/20100101 Firefox/40.0',
                'Accept: */*',
                'X-Requested-With: XMLHttpRequest',
                'Referer: '.$url,
                'Accept-Language: pt-BR,en-US;q=0.7,en;q=0.3',
            ));
            curl_setopt($ch, CURLOPT_USERAGENT,'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.13) Gecko/20080311 Firefox/2.0.0.13');
        }
        ///
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1 );
        // 最大执行时间
        $timeout && curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        $data = curl_exec($ch);
        $code = curl_getinfo($ch,CURLINFO_HTTP_CODE);
        $errno = curl_errno($ch);
        if (CI_DEBUG && $errno && $is_log) {
            log_message('error', '获取远程数据失败['.$url.']：（'.$errno.'）'.curl_error($ch));
        }
        curl_close($ch);
        if ($code == 200) {
            return $data;
        } elseif ($errno == 35) {
            // 当服务器不支持时改为普通获取方式
        } else {
            if (!$ct) {
                // 尝试重试
                if (preg_match_all('/[\x{4e00}-\x{9fa5}]/u', $url, $mt)) {
                    foreach ($mt[0] as $t) {
                        $url = str_replace($t, urlencode($t), $url);
                    }
                }
                if (strpos($url, ' ')) {
                    $url = str_replace(' ', '%20', $url);
                }
                return dr_catcher_data($url, $timeout, $is_log, 1);
            } elseif (CI_DEBUG && $code && $is_log) {
                log_message('error', '获取远程数据失败['.$url.']http状态：'.$code);
            }
            return '';
        }
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
        $data = file_get_contents($url, 0, stream_context_create([
            $ptl => $opt[$ptl]
        ]));
    } else {
        $data = file_get_contents($url);
    }

    return $data;
}


/**
 * 伪静态代码处理
 *
 * @param   $params 参数数组
 * @param   $search 搜索配置
 * @return  string
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
            if (is_array($v)) {
                continue;
            }
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
 * @param   $params 参数字符串
 * @return  参数数组
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
            $rt[$name] = !strcasecmp($array[$i], $default) ? '' : urldecode($array[$i]);
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
 * @param   $params 参数数组
 * @return  组合后的字符串
 */
function dr_rewrite_encode($params, $join = '-', $field = []) {

    if (!$params) {
        return '';
    }

    !$join && $join = '-';
    $field = array_flip(dr_format_option_array($field));
    $url = '';
    foreach ($params as $i => $t) {
        if (is_array($t)) {
            continue;
        }
        $i = isset($field[$i]) && $field[$i] ? $field[$i] : $i;
        $url.= $join.$i.$join.urlencode($t);
    }

    return trim($url, $join);
}

/**
 * 伪静态代码转换为数组
 *
 * @param   $params 参数字符串
 * @return  数组参数
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
        $i%2 == 0 && $return[$name] = isset($array[$k+1]) ? urldecode($array[$k+1]) : '';
        $i ++;
    }

    return $return;
}


/**
 * 安全过滤格式化搜索关键词参数
 * @param $s 参数
 * @return 处理后的值
 */
function dr_get_keyword($s) {
    return dr_safe_keyword($s);
}
if (!function_exists('dr_safe_keyword')) {
    /**
     * 安全过滤格式化搜索关键词参数
     * @param $s 参数
     * @return 处理后的值
     */
    function dr_safe_keyword($s) {

        if (dr_is_empty($s)) {
            return '';
        }

        return str_replace('\%', '%', \Phpcmf\Service::M()->db->escapeLikeStringDirect(htmlspecialchars(trim(str_replace(['+', ' ', '_'], '%', urldecode((string)$s)), '%'))));
    }
}

/**
 * 安全过滤函数
 * @param $string 参数
 * @param $diy 自定义过滤数组配置
 * @return 处理后的值
 */
function dr_safe_replace($string, $diy = []) {

    if (dr_is_empty($string)) {
        return '';
    }

    $replace = ['%20', '%27', '%2527', '*', "'", '"', ';', '<', '>', "{", '}'];
    $diy && is_array($diy) && $replace = dr_array2array($replace, $diy);
    $diy && !is_array($diy) && $replace[] = $diy;

    return str_replace($replace, '', (string)$string);
}

/**
 * 安全过滤文件及目录名称函数
 * @param $string 参数
 * @return 处理后的值
 */
function dr_safe_filename($string) {

    if (dr_is_empty($string)) {
        return '';
    }

    return str_replace(
        ["/", '\\', ' ', '<', '>', "{", '}', ';', ':', '[', ']', '\'', '"', '#', '*', '?', '..'],
        '',
        (string)$string
    );
}

/**
 * 安全过滤用户名函数
 * @param $string 参数
 * @return 处理后的值
 */
function dr_safe_username($string) {

    if (dr_is_empty($string)) {
        return '';
    }

    return str_replace(
        ['..', "/", '\\', ' ', "#",'\'', '"'],
        '',
        (string)$string
    );
}

/**
 * 安全过滤密码函数
 * @param $string 参数
 * @return 处理后的值
 */
function dr_safe_password($string) {

    if (dr_is_empty($string)) {
        return '';
    } elseif (strlen($string) > 100) {
        return substr($string, 0, 100);
    }

    return trim($string);
}

/**
 * 后台移除http和https协议
 * @param $url 地址
 * @return 处理后的值
 */
function dr_rm_http($url) {

    if (!$url) {
        return '';
    }

    return IS_ADMIN ? str_replace(['http:', 'https:'], '', $url) : $url;
}

/**
 * 将路径进行安全转换变量模式
 * @param $path 目录名
 * @return 处理后的值
 */
function dr_safe_replace_path($path) {

    if (!$path) {
        return '';
    }

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
            'cache/',
            '/',
            'dayrui/App/',
            'template/',
            'dayrui/',
            'dayrui/My/',
        ],
        $path
    );
}

/**
 * 字符截取
 * @param $string 字符串
 * @param $limit 长度限制
 * @param $dot 超出的填充字符串
 * @return 处理后的值
 */
function dr_strcut($string, $limit = '100', $dot = '...') {

    if (!$string) {
        return '';
    }

    $a = 0;
    if ($limit && strpos((string)$limit, ',')) {
        list($a, $length) = explode(',', $limit);
        $length = (int)$length;
    } else {
        $length = (int)$limit;
    }

    if (strlen($string) <= $length || !$length) {
        return $string;
    }

    if (function_exists('mb_substr')) {
        $strcut = mb_substr($string, $a, $length);
    } else {
        $n = $tn = $noc = 0;
        $string = str_replace(['&amp;', '&quot;', '&lt;', '&gt;'], ['&', '"', '<', '>'], $string);
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
        $strcut = str_replace(['&', '"', '<', '>'], ['&amp;', '&quot;', '&lt;', '&gt;'], $strcut);
    }

    $strcut == $string && $dot = '';

    return $strcut . $dot;
}

/**
 * 单词截取
 * @param $string 字符串
 * @param $maxchar 长度限制
 * @param $end 超出的填充字符串
 * @return 处理后的值
 */
function dr_wordcut($text, $maxchar, $end = '...') {

    if (!$text) {
        return '';
    }

    if (mb_strlen($text) > $maxchar || $text == '') {
        $words = preg_split('/\s/', $text);
        $output = '';
        $i      = 0;
        while (1) {
            $length = mb_strlen($output) + mb_strlen($words[$i]);
            if ($length > $maxchar) {
                break;
            } else {
                $output .= " " . $words[$i];
                ++$i;
            }
        }
        $output .= $end;
    } else {
        $output = $text;
    }

    return trim((string)$output);
}

/**
 * 随机颜色
 * @return  随机后的颜色值
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
 * @param   $time   时间戳
 * @param  $formt 时间太长时的格式输出
 * @return  string
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
            return dr_lang('刚刚');
        } else {
            return dr_lang('%s秒前', intval(floor($dTime / 10) * 10));
        }
    } elseif ($dTime < 3600 ) {
        return dr_lang('%s分钟前', intval($dTime/60));
    } elseif( $dTime >= 3600 && $dDay == 0  ){
        return dr_lang('%s小时前', intval($dTime/3600));
    } elseif( $dDay > 0 && $dDay<=7 ){
        return dr_lang('%s天前', intval($dDay));
    } elseif( $dDay > 7 &&  $dDay <= 30 ){
        return dr_lang('%s周前', intval($dDay/7));
    } elseif( $dDay > 30 && $dDay < 180){
        return dr_lang('%s个月前', intval($dDay/30));
    } elseif( $dDay >= 180 && $dDay < 360){
        return dr_lang('半年前');
    } elseif ($dYear == 0) {
        return dr_date($sTime);
    } else {
        return date($formt, $sTime);
    }
}

/**
 * 时间显示函数
 *
 * @param   $time   时间戳
 * @param   $format 格式与date函数一致
 * @param   $color  当天显示颜色
 * @return  string
 */
function dr_date($time = '', $format = SITE_TIME_FORMAT, $color = '') {

    if (!$time) {
        return '';
    }

    if (!is_numeric($time)) {
        $new = strtotime(dr_clearhtml($time));
        if (is_numeric($new)) {
            $time = $new;
        } else {
            return IS_DEV ? '参数（'.$time.'）不是时间戳格式' : '';
        }
    }

    if (!$time) {
        return '';
    }

    !$format && $format = SITE_TIME_FORMAT;
    !$format && $format = 'Y-m-d H:i:s';

    $string = date($format, $time);

    return $color && $time >= strtotime(date('Y-m-d 00:00:00')) && $time <= strtotime(date('Y-m-d 23:59:59')) ? '<font color="' . $color . '">' . $string . '</font>' : $string;
}



/**
 * 将对象转换为数组
 *
 * @param   $obj    数组对象
 * @return  array
 */
function dr_object2array($obj) {

    if (!$obj) {
        return [];
    }

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
 * 数组截取
 * @param $arr 数组值
 * @param $limit 长度限制
 * @return 处理后的数组
 */
function dr_arraycut($arr, $limit) {

    if (!$arr) {
        return [];
    } elseif (!is_array($arr)) {
        return [];
    }

    $limit = (string)$limit;
    if (strpos($limit, ',')) {
        list($a, $b) = explode(',', $limit);
    } else {
        $a = 0;
        $b = $limit;
    }

    return array_slice($arr, $a, $b, true);
}

/**
 * 将字符串转换为数组
 *
 * @param   $data   字符串
 * @return  array
 */
function dr_string2array($data, $limit = '') {

    if (!$data) {
        return [];
    } elseif (is_array($data)) {
        $rt = $data;
    } else {
        $rt = json_decode($data, true);
        //if (!$rt && IS_DEV) {
            // 存在安全隐患时改为开发模式下执行
            //$rt = unserialize(stripslashes($data));
        //}
    }

    if (is_array($rt) && $limit) {
        return dr_arraycut($rt, $limit);
    }

    return $rt;
}

/**
 * 将数组转换为字符串
 *
 * @param   $data   数组
 * @return  string
 */
function dr_array2string($data) {

    if (!$data) {
        return '';
    }

    if (is_array($data)) {
        $str = json_encode($data, JSON_UNESCAPED_UNICODE | 320);
        if (!$str) {
            if (IS_DEV) {
                log_message('debug', 'json_encode转换失败：'.json_last_error_msg());
            }
            return '';
        }
        return $str;
    } else {
        return $data;
    }
}

/**
 * 递归创建目录
 *
 * @param   $dir    目录名称
 * @return  bool|void
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
 * @param   $fileSize   大小
 * @param   $round      保留小数位
 * @return  string
 */
function dr_format_file_size($fileSize, $round = 2) {

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

    return round($value, $round) . $unit[$i];
}

/**
 * 关键字高亮显示
 *
 * @param   $string     字符串
 * @param   $keyword    关键字，可数组
 * @return  string
 */
function dr_keyword_highlight($string, $keyword, $rule = '') {

    if (!$keyword || !$string) {
        return $string;
    }

    if (is_array($keyword)) {
        $arr = $keyword;
    } else {
        $arr = explode(' ', trim(str_replace('%', ' ', urldecode($keyword)), '%'));
    }

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
 * 正则替换和过滤内容
 *
 * @param   $html
 * @return 过滤后的字符串
 */
function dr_preg_html($html){

    if (!$html) {
        return '';
    }

    $p = array("/<[a|A][^>]+(topic=\"true\")+[^>]*+>#([^<]+)#<\/[a|A]>/",
        "/<[a|A][^>]+(data=\")+([^\"]+)\"[^>]*+>[^<]*+<\/[a|A]>/",
        "/<[img|IMG][^>]+(src=\")+([^\"]+)\"[^>]*+>/");
    $t = array('topic{data=$2}','$2','img{data=$2}');
    $html = preg_replace($p, $t, $html);
    $html = strip_tags($html, "<br/>");

    return $html;
}

/**
 * 格式化微博内容中url内容的长度（废弃）
 */
function _format_feed_content_url_length($match) {
    return '<a href="'.$match[1].'" target="_blank">'.$match[1].'</a>';
}

/**
 * 二维码地址生成
 * @param $text 二维码的文字
 * @param $uid 中间用户头像的uid
 * @param $level 码块的大小等级
 * @param $size 二维码的大小
 * @return 返回二维码地址
 */
function dr_qrcode_url($text, $uid = 0, $level = 'L', $size = 5) {
    return ROOT_URL.'index.php?s=api&c=api&m=qrcode&uid='.urlencode($uid).'&text='.urlencode($text).'&size='.$size.'&level='.$level;
}

/**
 * 过滤非排序参数的法字段
 * @param $str 字符串
 * @param $order 排序方式
 * @return 过滤后的值
 */
function dr_get_order_string($str, $order) {

    if ($str && (substr_count($str, ' ') >= 2
            || strpos($str, '(') !== FALSE
            || strpos($str, 'undefined') === 0
            || strpos($str, ')') !== FALSE )) {
        return $order;
    }

    return $str ? $str : ($order ? $order : 'id desc');

}

/**
 * 两价格的折扣值
 * @param $price 当前价格
 * @param $nowprice 以前的价格
 * @return 计算后的值
 */
function dr_discount($price, $nowprice) {

    if ($nowprice <= 0) {
        return 0;
    }

    return round(10 / ($price / $nowprice), 1);
}

/**
 * 根据两点间的经纬度计算距离
 * @param $new 当前坐标
 * @param $to 目标坐标
 * @param $mark 单位
 * @return 返回距离
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
 * 计算某个经纬度的周围某段距离的正方形的四个点
 * @param $lng float 经度
 * @param $lat float 纬度
 * @param $distance float 该点所在圆的半径，该圆与此正方形内切，默认值为0.5千米
 * @return array 正方形的四个点的经纬度坐标
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

/**
 * 获取当前模板目录
 */
function dr_tpl_path($is_member = IS_MEMBER) {

    $tpl = dr_get_app_tpl(APP_DIR && APP_DIR != 'member' ? APP_DIR : '');
    $path = $is_member ? $tpl.(\Phpcmf\Service::IS_MOBILE_TPL() && is_dir($tpl.'mobile/'.SITE_TEMPLATE.'/') ? 'mobile' : 'pc').'/'.SITE_TEMPLATE.'/member/' : $tpl.(\Phpcmf\Service::V()->_is_mobile && is_dir($tpl.'mobile/'.SITE_TEMPLATE.'/') ? 'mobile' : 'pc').'/'.SITE_TEMPLATE.'/home/';

    APP_DIR && APP_DIR != 'member' && $path.= APP_DIR.'/';

    return $path;
}

/**
 * 数组随机排序，并截取数组
 * @param $arr
 * @param $num 数量
 * @return 新数组
 */
function dr_array_rand($arr, $num = 0) {

    if (!$arr or !is_array($arr)) {
        return [];
    }

    shuffle($arr);

    return $num ? dr_arraycut($arr, $num) : $arr;
}

/**
 * 数组的指定元素大小排序
 * @param $arr
 * @param $key KEY键名
 * @param $type 排序方式 asc desc
 * @return 新数组
 */
function dr_array_sort($arr, $key, $type = 'asc') {

    if (!is_array($arr)) {
        return [];
    }

    uasort($arr, function($a, $b) use ($key, $type) {
        if (!isset($a[$key])) {
            return 0;
        } elseif ($a[$key] == $b[$key]) {
            return 0;
        }
        if ($type == 'asc') {
            return ($a[$key] < $b[$key]) ? -1 : 1;
        } else {
            return ($a[$key] > $b[$key]) ? -1 : 1;
        }
    });

    return $arr;
}


/**
 * 获取网站表单发布页面需要的变量值
 */
function dr_get_form_post_value($table, $siteid = SITE_ID) {

    $rt = [
        'form' => dr_form_hidden(),
        'debug' => 'debug返回正常',
    ];

    $form = \Phpcmf\Service::L('cache')->get('form-'.$siteid, $table);
    if (!$form) {
        $rt['debug'] = '网站表单【'.$table.'】不存在';
        return $rt;
    }

    $rt['form_name'] = $form['name'];
    $rt['form_table'] = $form['table'];
    $rt['form_cache'] = $form;

    // 是否有验证码
    if ($form['setting']['post_code']) {
        $member = \Phpcmf\Service::C()->member;
        if (!$member) {
            $auth = [0];
        } else {
            $auth = $member['groupid'];
            if (!$auth) {
                $auth = [0]; // 没有用户组的视为游客
            }
        }
        $value = [];
        foreach ($auth as $k) {
            if (isset($form['setting']['post_code'][$k])) {
                $value[] = (int)$form['setting']['post_code'][$k];
            }
        }
        $rt['is_post_code'] =  $value ? max($value) : 0;
    } else {
        $rt['is_post_code'] =  0;
    }

    // 返回url
    $rt['rt_url'] =  $form['setting']['rt_url'] ? $form['setting']['rt_url'] : (defined('SC_HTML_FILE') ? '' : dr_now_url());

    // 初始化自定义字段类
    $field = $form['field'];
    $my_field = $sys_field = $diy_field = [];

    $field = dr_array_sort($field, 'displayorder');
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

    $rt['post_url'] = dr_web_prefix('index.php?s=form&c='.$table.'&m=post');

    return $rt;
}


/**
 * 获取模块表单发布页面需要的变量值
 */
function dr_get_mform_post_value($mid, $table, $cid, $siteid = SITE_ID) {

    $rt = [
        'form' => dr_form_hidden(),
        'debug' => 'debug返回正常',
    ];

    $module = \Phpcmf\Service::L('cache')->get('module-'.$siteid.'-'.$mid);
    if (!$module) {
        $rt['debug'] = '模块【'.$mid.'】不存在';
        return $rt;
    }

    $form = $module['form'][$table];
    if (!$form) {
        $rt['debug'] = '模块【'.$mid.'】表单【'.$table.'】不存在';
        return $rt;
    }

    $rt['form_name'] = $form['name'];
    $rt['form_table'] = $form['table'];
    $rt['form_cache'] = $form;
    $rt['module_cache'] = $module;

    // 是否有验证码
    $rt['is_post_code'] = $form['setting']['is_post_code'] ? 0 : 1;

    // 返回url
    $rt['rt_url'] =  $form['setting']['rt_url'] ? $form['setting']['rt_url'] : (defined('SC_HTML_FILE') ? '' : dr_now_url());

    // 初始化自定义字段类
    $field = $form['field'];
    $my_field = $sys_field = $diy_field = [];

    $field = dr_array_sort($field, 'displayorder');
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
    $rt['post_url'] = dr_web_prefix('index.php?s='.$mid.'&c='.$table.'&m=post&cid='.$cid);

    return $rt;
}

/**
 * 获取用户注册页面需要的变量值
 */
function dr_get_register_value($groupid = 0, $url = '') {

    $rt = [
        'form' => dr_form_hidden(['back' => $url]),
        'debug' => 'debug返回正常',
    ];

    !$groupid && $groupid = (int)\Phpcmf\Service::C()->member_cache['register']['groupid'];
    if (!$groupid) {
        $rt['debug'] = dr_lang('无效的用户组');
        return $rt;
    } elseif (!\Phpcmf\Service::C()->member_cache['group'][$groupid]['register']) {
        $rt['debug'] = dr_lang('用户组[%s]不允许注册', \Phpcmf\Service::C()->member_cache['group'][$groupid]['name']);
        return $rt;
    }

    // 初始化自定义字段类
    \Phpcmf\Service::L('Field')->app('member');

    // 获取该组可用注册字段
    $field = [];
    if (\Phpcmf\Service::C()->member_cache['group'][$groupid]['register_field']) {
        foreach (\Phpcmf\Service::C()->member_cache['group'][$groupid]['register_field'] as $fname) {
            $field[$fname] = \Phpcmf\Service::C()->member_cache['field'][$fname];
            $field[$fname]['ismember'] = 1;
        }
    }

    // 初始化自定义字段类
    $my_field = $sys_field = $diy_field = [];

    $field = dr_array_sort($field, 'displayorder');
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
    $rt['register'] = \Phpcmf\Service::C()->member_cache['register'];

    return $rt;
}

/**
 * 获取当前模板文件路径
 * @param $file 模板名
 * @return 返回完整名
 */
function dr_tpl_file($file) {
    return dr_tpl_path().$file;
}

/**
 * 兼容统计count函数
 */
function dr_count($array_or_countable, $mode = COUNT_NORMAL){
    return is_array($array_or_countable) || is_object($array_or_countable) ? count($array_or_countable, $mode) : 0;
}

/**
 * 给地址补全https或者http前缀
 */
function dr_http_prefix($url) {
    return (defined('SYS_HTTPS') && SYS_HTTPS ? 'https://' : 'http://').$url;
}


/**
 * 转换url
 * @param $Url 指定地址
 * @param $domian 指定域名 或者 模块目录
 * @param int|string $siteid 站点id号
 * @return 新的url
 */
function dr_to_url($url, $domian = '', $siteid = SITE_ID) {

    $url = dr_url_prefix($url, '', $siteid, 1);
    if ($domian) {
        $url = str_replace(SITE_URL, dr_http_prefix($domian), $url);
    }

    return $url;
}

/**
 * 获取对应的手机端地址
 * @param $url 任意域名
 * @return 新的url
 */
function dr_mobile_url($url = SITE_MURL) {

    $url = dr_url_prefix($url);
    $arr = parse_url($url);
    $host = $arr['host'];
    $domain = require WRITEPATH.'config/domain_client.php';
    if (!$domain) {
        return IS_DEV ? '【开发者模式下】未找到域名的终端配置文件' : $url;
    } elseif (!isset($domain[$host])) {
        return IS_DEV ? '【开发者模式下】未找到PC域名['.$host.']所对应的移动端域名' : $url;
    }

    return dr_url_prefix(str_replace($host, $domain[$host], $url));
}

/**
 * 是否是完整的url
 * @param $url
 * @return boolean
 */
function dr_is_url($url) {

    if (!$url) {
        return false;
    } elseif (strpos((string)$url, 'http://') === 0) {
        return true;
    } elseif (strpos((string)$url, 'https://') === 0) {
        return true;
    }

    return false;
}

/**
 * 补全url
 * @param $url
 * @param string $domain   指定域名或者模块目录
 * @param int|string $siteid    站点ID
 * @param string $is_mobile  是否指定为移动端
 * @return 新的url
 */
function dr_url_prefix($url, $domain = '', $siteid = SITE_ID, $is_mobile = '') {

    !$url && $url = '';

    if ($url && dr_is_url($url)) {
        // 本身就是绝对域名
    } else {
        // 相对域名
        strlen($is_mobile) == 0 && $is_mobile = \Phpcmf\Service::IS_MOBILE();

        if (is_array($domain) && isset($domain['setting']['html_domain']) && $domain['setting']['html_domain']) {
            $domain = $is_mobile && $domain['setting']['html_domain'] ? $domain['setting']['html_domain'] : $domain['setting']['html_domain'];
            $domain = dr_http_prefix($domain);
        }

        in_array($domain, ['MOD_DIR', 'share']) && $domain = '';

        // 判断是否是模块，如果domain不是http开头
        if ($domain && !dr_is_url($url)) {
            if (is_dir(dr_get_app_dir($domain))) {
                $mod = \Phpcmf\Service::L('cache')->get('module-'.$siteid.'-'.$domain);
                $domain = $mod && $mod['domain'] ? (\Phpcmf\Service::IS_MOBILE() && $mod['mobile_domain'] ? $mod['mobile_domain'] : $mod['domain']) : '';
            }
            // 域名是不是http开通
            if (!dr_is_url($domain)) {
                $domain = '';
            }
        }

        // 指定域名判断
        if (!$domain) {
            if (defined('IS_CLIENT') && IS_CLIENT) {
                // 来自客户端
                $domain = CLIENT_URL;
            } elseif (\Phpcmf\Service::C()->site_info[$siteid]['SITE_URL']) {
                // 存在多站点时
                $domain = $is_mobile ? \Phpcmf\Service::C()->site_info[$siteid]['SITE_MURL'] : \Phpcmf\Service::C()->site_info[$siteid]['SITE_URL'];
                if (WEB_DIR && strpos($domain, WEB_DIR) !== false && strpos($url, WEB_DIR) !== false) {
                    // 目录模式防止目录重复出现
                    $url = str_replace(WEB_DIR, '/', $url);
                }
            } else {
                $domain = $is_mobile ? SITE_MURL : SITE_URL;
            }
        }
        $url = dr_is_url($url) ? $url : rtrim($domain, '/').'/'.ltrim($url, '/');
    }

    // url地址替换动作
    if (defined('SYS_URL_REPLACE') && SYS_URL_REPLACE) {
        $arr = explode(',', SYS_URL_REPLACE);
        if ($arr) {
            foreach ($arr as $t) {
                list($a, $b) = explode('|', $t);
                if ($a) {
                    $url = str_replace($a, $b, $url);
                }
            }
        }
    }

    return $url;
}

/**
 * 补全相对路径
 * @param $url
 * @return 新的url
 */
function dr_web_prefix($url) {
    if ($url && dr_is_url($url)) {
        return dr_url_rel($url);
    } else {
        $url = (string)$url;
        return WEB_DIR.($url ? ltrim($url, '/') : '');
    }
}

/**
 * url转为完整路径 URL补全
 * @param $url
 * @param $prefix 指定替换域名/
 * @return 新的url
 */
function dr_url_full($url, $prefix = '') {
    return dr_url_prefix($url, $prefix);
}

/**
 * url转为相对路径
 * @param $url
 * @param $prefix 将指定字符串替换成/
 * @return 新的url
 */
function dr_url_rel($url, $prefix = '') {

    if ((IS_API_HTTP && (!defined('SYS_API_REL') || !SYS_API_REL)) || IS_ADMIN) {
        return $url;
    } elseif (defined('SYS_URL_REL') && SYS_URL_REL) {
        $surl = FC_NOW_HOST;
        if (defined('SC_HTML_FILE') && SITE_IS_MOBILE_HTML
            && \Phpcmf\Service::V()->_is_mobile
            && strpos(SITE_MURL, SITE_URL) === false
        ) {
            // 静态生成，移动端域名模式下
            $surl = SITE_MURL;
        }
        $url = str_replace([$surl, SITE_URL], '/', $url);
        if (IS_DEV && dr_is_url($url)) {
            $url.= '#系统开启了相对路径模式，本地址是站外域名，不能转为相对路径（在关闭开发者模式后不显示这句话）';
        }
        $prefix && $url = str_replace($prefix, '/', $url);
    }

    return $url;
}

/**
 * 内容中的转为相对路径
 * @param $text
 * @param $prefix 将指定字符串替换成/
 * @param $attr 将指定替换哪些标签 ['href', 'src']
 * @return 新的内容
 */
function dr_text_rel($text, $prefix = '', $attr = ['href', 'src']) {

    if ((IS_API_HTTP && (!defined('SYS_API_REL') || !SYS_API_REL)) || IS_ADMIN) {
        return $text;
    } elseif (defined('SYS_URL_REL') && SYS_URL_REL) {
        $surl = FC_NOW_HOST;
        if (defined('SC_HTML_FILE') && SITE_IS_MOBILE_HTML
            && \Phpcmf\Service::V()->_is_mobile
            && strpos(SITE_MURL, SITE_URL) === false
        ) {
            // 静态生成，移动端域名模式下
            $surl = SITE_MURL;
        }
        foreach ($attr as $a) {
            $text = str_replace($a.'="'.$surl, $a.'="/', $text);
            $text = str_replace($a.'=\''.$surl, $a.'="/', $text);
            $text = str_replace($a.'="'.SITE_URL, $a.'="/', $text);
            $text = str_replace($a.'=\''.SITE_URL, $a.'="/', $text);
        }
        if ($prefix) {
            $surl = $prefix;
            foreach ($attr as $a) {
                $text = str_replace($a.'="'.$surl, $a.'="/', $text);
                $text = str_replace($a.'=\''.$surl, $a.'="/', $text);
            }
        }
    }

    return $text;
}

/**
 * 内容中的转为完整路径,地址补全绝对路径
 * @param $text
 * @param $url 将/替换成哪个地址
 * @param $attr 将指定替换哪些标签 ['href', 'src']
 * @return 新的内容
 */
function dr_text_full($text, $url = SITE_URL, $attr = ['href', 'src']) {

    foreach ($attr as $a) {
        $text = str_replace($a.'="/', $a.'="'.$url, $text);
        $text = str_replace($a.'="/', $a.'=\''.$url, $text);
    }

    return $text;
}

/**
 * 计算用户组到期时间
 * @param $days  天数
 * @param $dtype  到期换算单位
 * @param $ntime  时间基数，默认为当前时间
 * @return 是否到期
 */
function dr_member_group_etime($days, $dtype, $ntime = 0) {

    if (!$days) {
        return 0;
    }

    if (!$ntime) {
        $ntime = SYS_TIME;
    }

    if ($dtype == 1) {
        return strtotime('+'.$days.' month', $ntime);
    } elseif ($dtype == 2) {
        return strtotime('+'.$days.' year', $ntime);
    } else {
        return strtotime('+'.$days.' day', $ntime);
    }
}

/**
 * 用户组到期时间单位
 * @param $dtype  到期换算单位
 * @return 单位
 */
function dr_member_group_dtype($dtype) {

    if ($dtype == 1) {
        return dr_lang('月');
    } elseif ($dtype == 2) {
        return dr_lang('年');
    } else {
        return dr_lang('天');
    }
}

/**
 * 处理带Emoji的数据，HTML转为emoji码
 * @param $msg  转换字符串
 * @return 新的字符串
 */
function dr_html2emoji($msg){

    if (!$msg) {
        return '';
    }

    if (substr($msg, 0, 1) == '"' && substr($msg, -1, 1) == '"') {

        $txt = json_decode(str_replace('|', '\\', $msg));
        if ($txt !== NULL) {
            $msg = $txt;
        }

        return trim($msg, '"');
    } else {
        return $msg;
    }
}

/**
 * 处理带Emoji的数据，写入数据库前的emoji转为HTML（废除）
 */
function dr_emoji2html($msg) {
    return $msg; // utf8mb4模式下原样输出
}

/**
 * 过滤emoji表情
 * @param type $str
 * @return 新的字符串
 */
function dr_clear_emoji($str){

    if (!$str) {
        return '';
    }

    return dr_clear_empty(dr_html2emoji(preg_replace_callback('/[\xf0-\xf7].{3}/', function($r) { return '';}, $str)));
}


/**
 * 将同步代码转为数组（废除）
 * @param string 同步代码字符串
 */
function dr_member_sync_url($string) {

    if (!$string) {
        return [];
    }

    if (preg_match_all('/src="(.+)"/iU', $string, $match)) {
        return $match[1];
    }

    return [];
}

/**
 * 文字转换拼音
 * @param $str
 * @return 新的字符串
 */
function dr_text2py($str) {
    return \Phpcmf\Service::L('pinyin')->result((string)$str);
}

/**
 * 将html转化为纯文字
 * @param $str
 * @param $cn 是否纯中文
 * @return 新的字符串
 */
function dr_html2text($str, $cn = false) {

    $str = dr_clearhtml($str);
    if ($cn && preg_match_all('/[\x{4e00}-\x{9fff}]+/u', $str, $mt)) {
        return join('', $mt[0]);
    }

    $text = "";
    $start = 1;
    for ($i=0;$i<strlen($str);$i++) {
        if ($start==0 && $str[$i]==">") {
            $start = 1;
        } elseif($start==1) {
            if ($str[$i]=="<") {
                $start = 0;
                $text.= " ";
            } elseif(ord($str[$i])>31) {
                $text.= $str[$i];
            }
        }
    }

    return $text;
}

/**
 * 批量 htmlspecialchars
 */
function dr_htmlspecialchars($param) {

    if (!$param) {
        return is_array($param) ? [] : '';
    } elseif (is_array($param)) {
        foreach ($param as $a => $t) {
            if ($t && !is_array($t)) {
                $param[$a] = htmlspecialchars($t);
            }
        }
    } else {
        $param = htmlspecialchars($param);
    }

    return $param;
}

/**
 * 当前是否是根目录
 */
function dr_is_root_path() {

    if (!isset($_SERVER['SCRIPT_FILENAME'])) {
        return false;
    }

    $path = dirname($_SERVER['SCRIPT_FILENAME']);

    return is_file($path.'/static/assets/global/css/admin.min.css')  && is_file($path.'/static/assets/js/cms.js');
}

/**
 * 检查目录权限
 * @param $dir 目录地址
 * @return 逻辑值
 */
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
        unlink($dir.'test.html');
        return 1;
    }
}

/**
 * 存储调试信息
 * @param file 存储文件
 * @param data 打印变量
 * @return 无
 */
function dr_debug($file, $data) {
    dr_mkdirs(WRITEPATH.'debuglog/');
    $debug = debug_backtrace();
    file_put_contents(WRITEPATH.'debuglog/'.dr_safe_filename($file).'.txt', var_export([
            '时间' => dr_date(SYS_TIME, 'Y-m-d H:i:s'),
            '终端' => (string)$_SERVER['HTTP_USER_AGENT'],
            '文件' => $debug[0]['file'],
            '行号' => $debug[0]['line'],
            '地址' => FC_NOW_URL,
            '变量' => $data,
        ], true).PHP_EOL.'=========================================================='.PHP_EOL, FILE_APPEND);
}

// 正则替换方法
class php5replace {

    private $data;

    function __construct($data) {
        $this->data = $data;
    }

    // 替换常量值
    function php55_replace_var($value) {
        if (defined($value[1])) {
            // 常量
            return constant($value[1]);
        } else {
            // 数组
            $val = \Phpcmf\Service::C()->get_cache('site', SITE_ID, 'config', $value[1]);
            return call_user_func(function($arg) {
                return $arg;
            }, ($val ? $val : '""'));
        }
    }

    // 替换数组变量值
    function php55_replace_data($value) {
        if (isset($value[2]) && $value[2] && isset($this->data[$value[1]]) && is_array($this->data[$value[1]])
            && isset($this->data[$value[1]][$value[2]])) {
            return $this->data[$value[1]][$value[2]];
        }
        return $this->data[$value[1]];
    }

    // 替换函数值
    function php55_replace_function($value) {
        if (!dr_is_safe_function($value[1])) {
            return '函数['.$value[1].']不安全，禁止在此处使用';
        } elseif (function_exists($value[1])) {
            // 执行函数体
            $param = '';
            if ($value[2]) {
                $p = $value[2] == '$data' ? $this->data : $value[2];
                $param = is_array($p) ? ['data' => $p] : explode(',', $p);
                foreach ($param as $i => $t) {
                    if (!is_array($t) && strpos($t, '$') === 0) {
                        $param[$i] = $this->data[substr($t, 1)];
                    }
                }
            }
            return $param ? call_user_func_array($value[1], $param) : call_user_func($value[1]);
        } else {
            return '函数['.$value[1].']未定义';
        }

        return $value[0];
    }

    // 替换全部
    function replace($value) {

        $value = preg_replace_callback('#{\$([a-z_0-9]+)}#U', [$this, 'php55_replace_data'], $value);
        $value = preg_replace_callback('#{\$([a-z_0-9]+)\.([a-z_0-9]+)}#U', [$this, 'php55_replace_data'], $value);
        $value = preg_replace_callback('#{([a-z_0-9]+)\((.*)\)}#Ui', [$this, 'php55_replace_function'], $value);
        $value = preg_replace_callback('#{([A-Z_]+)}#U', [$this, 'php55_replace_var'], $value);
        $value = preg_replace_callback('#{([a-z_0-9]+)}#U', [$this, 'php55_replace_data'], $value);
        $value = preg_replace_callback('#{([a-z_0-9]+)\.([a-z_0-9]+)}#U', [$this, 'php55_replace_data'], $value);

        return $value;
    }

}


/**
 * 转为utf8编码格式
 * @param $str 来源字符串
 */
function dr_code2utf8($str) {

    if (function_exists('mb_convert_encoding')) {
        return mb_convert_encoding($str, 'UTF-8', 'GBK');
    } elseif (function_exists('iconv')) {
        return iconv('GBK', 'UTF-8', $str);;
    }

    return $str;
}

////////////////////////////////////////////////////////////


// 函数安全性判断
if (!function_exists('dr_is_safe_function')) {
    function dr_is_safe_function($func) {

        if (stripos('apache_child_terminate, apache_setenv, define_syslog_variables, escapeshellarg, escapeshellcmd, eval, exec, fp, fput, ftp_connect, ftp_exec, ftp_get, ftp_login, ftp_nb_fput, ftp_put, ftp_raw, ftp_rawlist, highlight_file, ini_alter, ini_get_all, ini_restore, inject_code, mysql_pconnect, openlog, passthru, php_uname, phpAds_remoteInfo, phpAds_XmlRpc, phpAds_xmlrpcDecode, phpAds_xmlrpcEncode, popen, posix_getpwuid, posix_kill, posix_mkfifo, posix_setpgid, posix_setsid, posix_setuid, posix_setuid, posix_uname, proc_close, proc_get_status, proc_nice, proc_open, proc_terminate, shell_exec, syslog, system, xmlrpc_entity_decode', $func) !== false) {
            return false;
        }

        return true;
    }
}

// 回调函数安全性判断
if (!function_exists('dr_is_call_function')) {
    function dr_is_call_function($func) {

        if (strpos($func, 'dr_') === 0
            or strpos($func, 'my_') === 0
            or strpos($func, 'cloud_') === 0) {
            if (function_exists($func)) {
                return true;
            }
        } else {
            log_message('error', '回调函数【'.$func.'】必须以dr_或者my_开头!');
        }

        return false;
    }
}

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
     * @param   string  $str
     * @return  string
     */
    function dr_clearhtml($str) {

        if (is_array($str) || !$str) {
            return '';
        }

        $str = strip_tags((string)$str);
        $str = dr_code2html($str);
        $str = str_replace(
            ['&nbsp;', '&amp;', '&quot;', '&#039;', '&ldquo;', '&rdquo;', '&mdash;', '&lt;', '&gt;', '&middot;', '&hellip;'],
            [' ', '&', '"', "'", '“', '”', '—', '<', '>', '·', '…'], $str
        );

        $str = preg_replace("/\<[a-z]+(.*)\>/iU", "", (string)$str);
        $str = preg_replace("/\<\/[a-z]+\>/iU", "", (string)$str);
        $str = str_replace(array(PHP_EOL, chr(13), chr(10), '&nbsp;'), '', $str);

        return trim($str);
    }
}

/**
 * 提取描述信息过滤函数
 */
function dr_filter_description($value, $data = [], $old = []) {
    return dr_get_description($value, 0);
}

if (! function_exists('dr_get_description')) {
    /**
     * 提取描述信息
     */
    function dr_get_description($text, $limit = 0) {

        $rs = \Phpcmf\Hooks::trigger_callback('cms_get_description', $text);
        if ($rs && isset($rs['code']) && $rs['code'] && $rs['msg']) {
            $text = $rs['msg'];
        }

        if (!$limit) {
            $limit = isset(\Phpcmf\Service::C()->module['setting']['desc_limit']) && \Phpcmf\Service::C()->module['setting']['desc_limit'] ? \Phpcmf\Service::C()->module['setting']['desc_limit'] : 200;
        }

        if (isset(\Phpcmf\Service::C()->module['setting']['desc_clear']) && \Phpcmf\Service::C()->module['setting']['desc_clear']) {
            $text = str_replace(' ', '', $text);
            $text = str_replace('　', '', $text);
        }

        return trim(dr_strcut(dr_clearhtml($text), $limit, ''));
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

        $rs = \Phpcmf\Hooks::trigger_callback('cms_get_keywords', $kw, $siteid);
        if ($rs && isset($rs['code']) && $rs['code'] && $rs['msg']) {
            return $rs['msg'];
        }

        if (is_file(FCPATH.'ThirdParty/WordAnalysis/phpanalysis.class.php')) {
            require_once FCPATH.'ThirdParty/WordAnalysis/phpanalysis.class.php';
            \PhpAnalysis::$loadInit = false;
            $pa = new \PhpAnalysis ( 'utf-8', 'utf-8', false );
            $pa->LoadDict ();
            $pa->SetSource ($kw);
            $pa->StartAnalysis ( true );

            $tags = $pa->GetFinallyKeywords (20);
            if ($tags) {
                return $tags;
            }
        }

        return '';
    }
}


if (! function_exists('dr_redirect'))
{
    /**
     * 跳转地址
     */
    function dr_redirect($url = '', $method = 'auto', $code = 0) {

        if ($url == FC_NOW_URL) {
            return; // 防止重复定向
        }

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


if (! function_exists('dr_redirect_safe_check'))
{
    /**
     * 跳转地址安全检测
     */
    function dr_redirect_safe_check($url) {
        return $url;
    }
}

if ( ! function_exists('dr_directory_map'))
{
    /**
     * 目录列表获取（废除）
     * @param   $source_dir  源目录
     * @param   $directory_depth 目录纵深 0全目录 1当前目录
     * @param   $hidden    是否包含隐藏目录
     * @return  整个目录名的数组格式
     */
    function dr_directory_map($source_dir, $directory_depth = 0, $hidden = FALSE)
    {
        return dr_dir_map($source_dir, $directory_depth, $hidden);
    }
}


if (! function_exists('remove_invisible_characters')) {
    /**
     * 移除不规则的字符串
     */
    function remove_invisible_characters($str, $urlEncoded = true)
    {
        $nonDisplayables = [];

        // every control character except newline (dec 10),
        // carriage return (dec 13) and horizontal tab (dec 09)
        if ($urlEncoded) {
            $nonDisplayables[] = '/%0[0-8bcef]/';  // url encoded 00-08, 11, 12, 14, 15
            $nonDisplayables[] = '/%1[0-9a-f]/';   // url encoded 16-31
        }

        $nonDisplayables[] = '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]+/S';   // 00-08, 11, 12, 14-31, 127

        do {
            $str = preg_replace($nonDisplayables, '', $str, -1, $count);
        } while ($count);

        return $str;
    }
}

// 评论名称
if (!function_exists('dr_comment_cname')) {
    function dr_comment_cname($name) {

        if (!$name) {
            return dr_lang('评论');
        }

        return dr_lang($name);
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

//////////////////////////简化函数///////////////////////////

function XR_L($name, $namespace = '') {
    return \Phpcmf\Service::L($name, $namespace);
}

function XR_M($name = '', $namespace = '') {
    return \Phpcmf\Service::M($name, $namespace);
}

function XR_H($name, $namespace) {
    return \Phpcmf\Service::H($name, $namespace);
}

function XR_R($name, $clear = false) {
    return \Phpcmf\Service::R($name, $clear);
}

function XR_C() {
    return \Phpcmf\Service::C();
}

function XR_V() {
    return \Phpcmf\Service::V();
}

function XR_DB() {
    return \Phpcmf\Service::M()->db;
}