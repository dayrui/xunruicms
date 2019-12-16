<?php namespace Phpcmf\Controllers\Admin;

/**
 * http://www.xunruicms.com
 * 本文件是框架系统文件，二次开发时不可以修改本文件
 **/

// 生成静态
class Html extends \Phpcmf\Common
{

    public function __construct(...$params) {
        parent::__construct(...$params);

        // 生成权限文件
        if (!dr_html_auth(1)) {
            $this->_admin_msg(0, dr_lang('/cache/html/ 无法写入文件'));
        }
    }

    // 生成静态
    public function index() {

        \Phpcmf\Service::V()->assign([
            'menu' => \Phpcmf\Service::M('auth')->_admin_menu(
                [
                    '生成静态' => [\Phpcmf\Service::L('Router')->class.'/'.\Phpcmf\Service::L('Router')->method, 'fa fa-file-code-o'],
                    'help' => [417],
                ]
            ),
            'module' => \Phpcmf\Service::L('cache')->get('module-'.SITE_ID.'-content'),
        ]);
        \Phpcmf\Service::V()->display('html_index.html');
    }

    // 生成单页
    public function page_index() {

        if ($this->member_cache['auth_site'][SITE_ID]['home']) {
            $this->_json(0, '当前网站设置了访问权限，无法生成静态');
        }

        \Phpcmf\Service::V()->assign([
            'todo_url' => '/index.php?s=page&m=htmlfile',
            'count_url' =>\Phpcmf\Service::L('Router')->url('html/page_count_index'),
        ]);
        \Phpcmf\Service::V()->display('html_bfb.html');exit;
    }

    // 单页生成的数量统计
    public function page_count_index() {
        $data = dr_save_bfb_data($this->get_cache('page-'.SITE_ID, 'data'));
        if (!dr_count($data)) {
            $this->_json(0, '没有可用生成的自定义页面数据');
        }
        \Phpcmf\Service::L('cache')->set_data('page-html-file', $data, 3600);
        $this->_json(1, 'ok');
    }

    // 栏目
    public function category_index() {

        if ($this->member_cache['auth_site'][SITE_ID]['home']) {
            $this->_json(0, '当前网站设置了访问权限，无法生成静态');
        }

        $app = \Phpcmf\Service::L('input')->get('app');
        $ids = \Phpcmf\Service::L('input')->get('ids');

        \Phpcmf\Service::V()->assign([
            'todo_url' => '/index.php?'.($app ? 's='.$app.'&' : '').'c=html&m=category&ids='.$ids,
            'count_url' =>\Phpcmf\Service::L('Router')->url('html/category_count_index', ['app' => $app, 'ids' => $ids]),
        ]);
        \Phpcmf\Service::V()->display('html_bfb.html');exit;
    }

    // 获取生成的栏目
    private function _category_data($ids, $cats) {

        if (!$ids) {
            return $cats;
        }

        $rt = [];
        $arr = explode(',', $ids);
        foreach ($arr as $id) {
            if ($id && $cats[$id]) {
                $rt[$id] = $cats[$id];
            }
        }

        return $rt;
    }

    // 栏目的数量统计
    public function category_count_index() {

        $app = \Phpcmf\Service::L('input')->get('app');
        $ids = \Phpcmf\Service::L('input')->get('ids');

        if ($app) {
            $cat = $this->get_cache('module-'.SITE_ID.'-'.$app, 'category');
        } else {
            $cat = $this->get_cache('module-'.SITE_ID.'-share', 'category');
        }

        \Phpcmf\Service::L('html')->get_category_data($app, $this->_category_data($ids, $cat));
    }

    // 内容
    public function show_index() {

        if ($this->member_cache['auth_site'][SITE_ID]['home']) {
            $this->_json(0, '当前网站设置了访问权限，无法生成静态');
        }

        $app = \Phpcmf\Service::L('input')->get('app');
        $ids = \Phpcmf\Service::L('input')->get('catids');

        \Phpcmf\Service::V()->assign([
            'todo_url' => '/index.php?'.($app ? 's='.$app.'&' : '').'c=html&m=show&catids='.$ids,
            'count_url' =>\Phpcmf\Service::L('Router')->url('html/show_count_index', ['app' => $app, 'catids' => $ids, 'date_to' => \Phpcmf\Service::L('input')->get('date_to'), 'date_form' => \Phpcmf\Service::L('input')->get('date_form')]),
        ]);
        \Phpcmf\Service::V()->display('html_bfb.html');exit;
    }

    // 内容数量统计
    public function show_count_index() {
        \Phpcmf\Service::L('html')->get_show_data(\Phpcmf\Service::L('input')->get('app'), [
            'catids' => \Phpcmf\Service::L('input')->get('catids'),
            'date_to' => \Phpcmf\Service::L('input')->get('date_to'),
            'date_form' => \Phpcmf\Service::L('input')->get('date_form')
        ]);
    }

    private function _get_cat_data($app, $ids) {

    }

}
