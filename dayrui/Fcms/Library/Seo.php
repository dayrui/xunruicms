<?php namespace Phpcmf\Library;
/**
 * {{www.xunruicms.com}}
 * {{迅睿内容管理框架系统}}
 * 本文件是框架系统文件，二次开发时不可以修改本文件，可以通过继承类方法来重写此文件
 **/

/**
 * seo输出
 */

class Seo {

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

        if (IS_CLIENT) {
            // 终端模式下
            $cdata = \Phpcmf\Service::R(WRITEPATH.'config/app_client_seo.php');
            if ($cdata && isset($cdata[IS_CLIENT]) && $cdata[IS_CLIENT]) {
                $seo = [
                    'meta_title' => $cdata[IS_CLIENT]['SITE_TITLE'],
                    'meta_keywords' => $cdata[IS_CLIENT]['SITE_KEYWORDS'],
                    'meta_description' => $cdata[IS_CLIENT]['SITE_DESCRIPTION']
                ];
            }
        }

        !$seo['meta_title'] && $seo['meta_title'] = SITE_NAME;

        if ($seo['meta_title'] && strpos($seo['meta_title'], '{page}') !== false) {
            $page = max(1, intval($_GET['page']));
            if ($page > 1) {
                $seo['meta_title'] = str_replace(array('[', ']'), '', $seo['meta_title']);
                $seo['meta_title'] = str_replace('{join}', SITE_SEOJOIN, $seo['meta_title']);
                $seo['meta_title'] = str_replace('{page}', $page, $seo['meta_title']);
            } else {
                $seo['meta_title'] = preg_replace('/\[.+\]/U', '', $seo['meta_title']);
            }
        }

        return $this->get_seo_value([], $seo);
    }


    /**
     * 模块SEO信息
     *
     * @return  array
     */
    public function module($mod) {
        return[
            'meta_title' => '内容建站插件版本需要升级之后才能显示seo信息',
        ];
    }


    /**
     * 模块搜索SEO信息
     */
    public function search($mod, $catid, $param, $page = 1) {
        return[
            'meta_title' => '内容建站插件版本需要升级之后才能显示seo信息',
        ];
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
        return[
            'meta_title' => '内容建站插件版本需要升级之后才能显示seo信息',
        ];
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
        return[
            'meta_title' => '内容建站插件版本需要升级之后才能显示seo信息',
        ];
    }

    // 替换seo信息字符
    public function get_seo_value($data, $seo) {

        $data['join'] = SITE_SEOJOIN;
        $rep = new \php5replace($data);

        foreach ($seo as $key => $value) {
            if (!$value || is_array($value)) {
                continue;
            }

            $seo[$key] = $rep->replace($value);
            $seo[$key] = str_replace(SITE_SEOJOIN.SITE_SEOJOIN, SITE_SEOJOIN, $seo[$key]);
            //$seo[$key] = htmlspecialchars(dr_clearhtml($seo[$key]));
            $seo[$key] = str_replace('"', '', $seo[$key]);
            $seo[$key] = str_replace([',,'], ',', $seo[$key]);
            $seo[$key] = trim($seo[$key], SITE_SEOJOIN);
            $seo[$key] = trim($seo[$key], ',');
            $seo[$key] = dr_safe_replace($seo[$key]);
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
            $seo['menu'] = dr_my_member_menu($seo['menu']);
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