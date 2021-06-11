<?php namespace Phpcmf\Library;
/**
 * {{www.xunruicms.com}}
 * {{迅睿内容管理框架系统}}
 * 本文件是框架系统文件，二次开发时不可以修改本文件，可以通过继承类方法来重写此文件
 **/

/**
 * seo输出
 */

class Seo
{

    /**
     * 首页SEO信息
     *
     * @return  array
     */
    public function index() {

        $seo = [
            'meta_title' => \Phpcmf\Service::C()->get_cache('site', SITE_ID, 'seo', 'SITE_TITLE'),
            'meta_keywords' => \Phpcmf\Service::C()->get_cache('site', SITE_ID, 'seo', 'SITE_KEYWORDS'),
            'meta_description' => \Phpcmf\Service::C()->get_cache('site', SITE_ID, 'seo', 'SITE_DESCRIPTION')
        ];

        !$seo['meta_title'] && $seo['meta_title'] = SITE_NAME;

        return $seo;
    }


    /**
     * 模块SEO信息
     *
     * @return  array
     */
    public function module($mod) {

        $seo = [];

        $seo['meta_title'] =  $mod['site'][SITE_ID]['module_title'] ? $mod['site'][SITE_ID]['module_title'] : $mod['name'].SITE_SEOJOIN.SITE_NAME;
        $seo['meta_keywords'] = $mod['site'][SITE_ID]['module_keywords'];

        $seo['meta_title'] = htmlspecialchars(dr_clearhtml($seo['meta_title']));
        $seo['meta_description'] = $mod['site'][SITE_ID]['module_description'];
        $seo['meta_description'] = htmlspecialchars(dr_clearhtml($seo['meta_description']));


        if (!$seo['meta_keywords']) {
            // 留空时使用主站seo
            $seo['meta_keywords'] = \Phpcmf\Service::C()->get_cache('site', SITE_ID, 'seo', 'SITE_KEYWORDS');
        }

        if (!$seo['meta_description']) {
            // 留空时使用主站seo
            $seo['meta_description'] = \Phpcmf\Service::C()->get_cache('site', SITE_ID, 'seo', 'SITE_DESCRIPTION');
        }

        return $seo;
    }


    /**
     * 模块搜索SEO信息
     */
    public function search($mod, $catid, $param, $page = 1) {

        $seo = [];
        $seo['meta_keywords'] = '';

        $data['page'] = $page > 1 ? $page : '';
        $data['param'] = '';
        $data['keyword'] = '';
        $data['modulename'] = $data['modname'] = $mod['dirname'] == 'share' ? '': dr_lang($mod['name']);

        $param_value = [];
        if ($param['keyword']) {
            $param_value['keyword'] = $data['keyword'] = urldecode($param['keyword']);
            unset($param['keyword']);
        }
        if ($param['groupid']) {
            $data['groupid'] = $param['groupid'];
        }
        if ($param['updatetime']) {
            $param_value['updatetime'] = $param['updatetime'];
        }

        if ($catid) {
            $t = dr_get_cat_pname($mod, $catid, SITE_SEOJOIN);
            if ($t) {
                $param_value['catid'] = $t;
            }
            unset($param['catid']);
            unset($param['catdir']);
        }

        if ($param) {
            $myfield = $mod['field'];
            if ($catid) {
                $cat_field = $mod['category'][$catid]['field'];
                $cat_field && $myfield = dr_array22array($myfield, $cat_field);
            }

            $seofield = $myfield;
            foreach ($param as $name => $value) {
                $now_field = [];
                if (isset($myfield[$name])) {
                    // 模块字段
                    $now_field = $myfield[$name];
                } elseif ($name == 'flag') {
                    // 推荐位
                    if ($value) {
                        $flag = $mod['setting']['flag'];
                        $arr = explode('|', $value);
                        if ($arr) {
                            foreach ($arr as $a) {
                                if (isset($flag[$a]) && $flag[$a]) {
                                    $param_value[$name][] = $flag[$a]['name'];
                                }
                            }
                        }
                    }
                    continue;
                } elseif ($name == 'groupid') {
                    // 会员组名称
                    if ($value) {
                        $param_value[$name] = \Phpcmf\Service::C()->member_cache['group'][$value]['name'];
                    }
                    continue;
                } elseif (isset(\Phpcmf\Service::C()->member_cache['field'][$name])) {
                    // 会员字段
                    $now_field = \Phpcmf\Service::C()->member_cache['field'][$name];
                    $seofield[$name] = $now_field;
                }
                // 按字段属性组合
                if ($now_field) {
                    switch ($now_field['fieldtype']) {

                        case 'Radio':
                        case 'Select':
                        case 'Checkbox':
                            $opt = dr_format_option_array($now_field['setting']['option']['options']);
                            $arr = explode('|', $value);
                            if ($arr) {
                                foreach ($arr as $a) {
                                    if (isset($opt[$a]) && $opt[$a]) {
                                        $param_value[$name][] = $opt[$a];
                                    }
                                }
                            }
                            break;

                        case 'Linkages':
                        case 'Linkage':
                            $arr = explode('|', $value);
                            if ($arr) {
                                foreach ($arr as $a) {
                                    $param_value[$name][] = dr_linkagepos($now_field['setting']['option']['linkage'], $a, SITE_SEOJOIN);
                                }
                            }
                            break;

                        default:
                            $value && $param_value[$name] = $value;
                            break;
                    }
                }
            }
        }

        $meta_title = $mod['site'][SITE_ID]['search_title'] ? $mod['site'][SITE_ID]['search_title'] : '['.dr_lang('第%s页', '{page}').'{join}][{keyword}{join}][{param}{join}]{modulename}{join}{SITE_NAME}';
        $seo['param_value'] = [];
        if ($param_value) {
            $str = [];
            $seofield['catid'] = $seofield['catdir'] = [ 'name' => dr_lang('栏目') ];
            $seofield['groupid'] = ['name' => dr_lang('用户组')];
            $seofield['flag'] = ['name' => dr_lang('推荐位')];
            $seofield['keyword'] = ['name' => dr_lang('关键词')];
            $seofield['updatetime'] = ['name' => dr_lang('更新时间')];
            $db = \Phpcmf\Service::C()->content_model;
            if ($db) {
                list($seofield, $param_value) = $db->_format_search_param_value($seofield, $param_value);
            }
            foreach ($param_value as $f => $t) {
                $seo['param_value'][$f] = [
                    'name' => $seofield[$f]['name'],
                    'value' => is_array($t) ? implode('|', $t) : $t,
                    'value_array' => is_array($t) ? $t : [],
                ];
                $str[$f] = is_array($t) ? implode('|', $t) : $t;
            }

            $seo['meta_keywords'].= implode(',', $str).',';

            // 避免重复keyword
            if (isset($str['keyword']) && strpos($meta_title, 'keyword') !== false) {
                unset($str['keyword']);
            }
            $data['param'] = implode(SITE_SEOJOIN, $str);
        }

        if (preg_match_all('/\[.*\{(.+)\}.*\]/U', $meta_title, $m)) {
            $new = '';
            $replace = '';
            foreach ($m[1] as $i => $field) {
                $replace.= $m[0][$i];
                if (isset($data[$field]) && strlen($data[$field])) {
                    $new.= str_replace(['[', ']'], '', $m[0][$i]);
                }
            }
            $meta_title = str_replace($replace, $new, $meta_title);
        }

        return $this->get_seo_value($data, [
            'meta_title' => $meta_title,
            'param_value' => $seo['param_value'],
            'meta_keywords' => $seo['meta_keywords'] ? $seo['meta_keywords'] : (isset($mod['site'][SITE_ID]['search_keywords']) && $mod['site'][SITE_ID]['search_keywords'] ? $mod['site'][SITE_ID]['search_keywords'] : \Phpcmf\Service::C()->get_cache('site', SITE_ID, 'seo', 'SITE_KEYWORDS')),
            'meta_description' => isset($mod['site'][SITE_ID]['search_description']) && $mod['site'][SITE_ID]['search_description'] ? $mod['site'][SITE_ID]['search_description'] : \Phpcmf\Service::C()->get_cache('site', SITE_ID, 'seo', 'SITE_DESCRIPTION'),
        ]);
    }

    /**
     * 模块栏目SEO信息
     *
     * @param   array   $mod
     * @param   array   $cat
     * @param   intval  $page
     * @return  array
     */
    public function category($mod, $catid, $page = 1) {

        $cat = $mod['category'][$catid];
        $cat['page'] = intval($page);
        $cat['name'] = $cat['catname'] = $cat['name'];
        $cat['catpname'] = dr_get_cat_pname($mod, $catid, SITE_SEOJOIN);
        $cat['modulename'] = $cat['modname'] = $mod['dirname'] == 'share' ? '': dr_lang($mod['name']);

        if (!$mod['share'] && (!isset($mod['site'][SITE_ID]['is_cat']) || !$mod['site'][SITE_ID]['is_cat'])) {
            // 独立模块统一规则模式
            $seo = $mod['site'][SITE_ID];
        } else {
            $seo = $cat['setting']['seo'];
        }

        $meta_title = $seo['list_title'] ? $seo['list_title'] : '['.dr_lang('第%s页', '{page}').'{join}]{modulename}{join}{SITE_NAME}';
        $meta_title = $page > 1 ? str_replace(array('[', ']'), '', $meta_title) : preg_replace('/\[.+\]/U', '', $meta_title);

        return $this->get_seo_value($cat, [
            'meta_title' => $meta_title,
            'meta_keywords' => isset($seo['list_keywords']) && $seo['list_keywords'] ? $seo['list_keywords'] : ($mod['site'][SITE_ID]['module_keywords'] ? $mod['site'][SITE_ID]['module_keywords'] : \Phpcmf\Service::C()->get_cache('site', SITE_ID, 'seo', 'SITE_KEYWORDS')),
            'meta_description' => isset($seo['list_description']) && $seo['list_description'] ? $seo['list_description'] : ($mod['site'][SITE_ID]['module_description'] ? $mod['site'][SITE_ID]['module_description'] : \Phpcmf\Service::C()->get_cache('site', SITE_ID, 'seo', 'SITE_DESCRIPTION')),
        ]);
    }

    /**
     * 模块内容SEO信息
     *
     * @param   array   $mod
     * @param   array   $cat
     * @param   intval  $page
     * @return  array
     */
    public function show($mod, $data, $page = 1) {

        $cat = $mod['category'][$data['catid']];
        $data['page'] = $page;
        $data['name'] = $data['catname'] = $cat['name'];
        $data['title'] = dr_clearhtml($data['title']);
        $data['catname'] = $cat['name'];
        $data['catpname'] = dr_get_cat_pname($mod, $data['catid'], SITE_SEOJOIN);
        $data['modulename'] = $data['modname'] = dr_lang($mod['name']);
        $data['modulename'] = $data['modname'] = $mod['dirname'] == 'share' ? '': dr_lang($mod['name']);

        $data['keywords'] = htmlspecialchars(dr_safe_replace(dr_clearhtml($data['keywords'])));
        $data['description'] = htmlspecialchars(dr_safe_replace(dr_clearhtml($data['description'])));

        $meta_title = isset($mod['site'][SITE_ID]['show_title']) && $mod['site'][SITE_ID]['show_title'] ? $mod['site'][SITE_ID]['show_title'] : '['.dr_lang('第%s页', '{page}').'{join}]{title}{join}{catpname}{join}{modulename}{join}{SITE_NAME}';
        $meta_title = $page > 1 ? str_replace(['[', ']'], '', $meta_title) : preg_replace('/\[.+\]/U', '', $meta_title);

        return $this->get_seo_value($data, [
            'meta_title' => $meta_title,
            'meta_keywords' => isset($mod['site'][SITE_ID]['show_keywords']) && $mod['site'][SITE_ID]['show_keywords'] ? $mod['site'][SITE_ID]['show_keywords'] : $data['keywords'],
            'meta_description' => isset($mod['site'][SITE_ID]['show_description']) && $mod['site'][SITE_ID]['show_description'] ? $mod['site'][SITE_ID]['show_description'] : $data['description'],
        ]);
    }

    // 替换seo信息字符
    public function get_seo_value($data, $seo) {

        $data['join'] = SITE_SEOJOIN;
        $rep = new \php5replace($data);

        foreach ($seo as $key => $value) {
            if (!$value || is_array($value)) {
                continue;
            }
            $seo[$key] = preg_replace_callback('#{([A-Z_]+)}#U', array($rep, 'php55_replace_var'), $value);
            $seo[$key] = preg_replace_callback('#{([a-z_0-9]+)}#U', array($rep, 'php55_replace_data'), $seo[$key]);
            $seo[$key] = preg_replace_callback('#{([a-z_0-9]+)\((.*)\)}#Ui', array($rep, 'php55_replace_function'), $seo[$key]);
            $seo[$key] = str_replace(SITE_SEOJOIN.SITE_SEOJOIN, SITE_SEOJOIN, $seo[$key]);
            $seo[$key] = htmlspecialchars(dr_clearhtml($seo[$key]));
            $seo[$key] = str_replace('"', '', $seo[$key]);
            $seo[$key] = str_replace([',,', '%'], ',', $seo[$key]);
            $seo[$key] = trim($seo[$key], SITE_SEOJOIN);
            $seo[$key] = trim($seo[$key], ',');
        }

        return $seo;
    }

    // 用户中心seo
    public function member($menu) {

        $seo = [
            'menu' => $menu['url'],
            'page_bar' => '<div class="page-bar">
                <ul class="page-breadcrumb">
                    <li>
                        <i class="fa fa-home"></i>
                        <a href="'.dr_member_url('/').'">'.dr_lang('用户中心').'</a>
                        <i class="fa fa-angle-right"></i>
                    </li>
                    {value}
                </ul>
            </div>',
        ];

        // 自定义菜单显示
        if (function_exists('dr_my_member_menu')) {
            $seo['menu'] = dr_my_member_menu( $seo['menu']);
        }

        list($uri1, $uri2) = \Phpcmf\Service::L('router')->member_uri();
        $uri = isset($menu['uri'][$uri1]) ? $uri1 : (isset($menu['uri'][$uri2]) ? $uri2 : '');

        if (!$uri && APP_DIR && APP_DIR != 'member') {
            // 来自内容模块的菜单全部归结于内容下
            $uri = APP_DIR.'/home/index';
        }

        $seo['mymenu'] = []; // 当前菜单id和pid

        if ($menu['uri'][$uri]) {
            $seo['page_bar'] = str_replace('{value}', '
                    <li>
                        <i class="'.dr_icon($menu['uri'][$uri]['picon']).'"></i>
                        <span>'.dr_lang($menu['uri'][$uri]['pname']).'</span>
                        <i class="fa fa-angle-right"></i>
                    </li>
                    <li>
                        <i class="'.dr_icon($menu['uri'][$uri]['icon']).'"></i>
                        <a href="'.dr_member_url($uri).'">'.dr_lang($menu['uri'][$uri]['name']).'</a>
                    </li>
                    ', $seo['page_bar']);
            $seo['mymenu'] = [$menu['uri'][$uri]['id'], $menu['uri'][$uri]['pid']]; // 当前菜单id和pid
            $seo['meta_name'] = $menu['uri'][$uri]['name'];
            $seo['meta_title'] = $menu['uri'][$uri]['name'].SITE_SEOJOIN.$menu['uri'][$uri]['pname'].SITE_SEOJOIN.dr_lang('用户中心');
        } else {
            $seo['meta_title'] = $seo['meta_name'] = dr_lang('用户中心');
        }

        $seo['page_bar'] = str_replace('{value}', '', $seo['page_bar']);
        $seo['meta_keywords'] = \Phpcmf\Service::C()->get_cache('site', SITE_ID, 'seo', 'SITE_KEYWORDS');
        $seo['meta_description'] = \Phpcmf\Service::C()->get_cache('site', SITE_ID, 'seo', 'SITE_DESCRIPTION');

        return $seo;
    }

}