<?php namespace Phpcmf\Controllers;

/**
 * http://www.xunruicms.com
 * 本文件是框架系统文件，二次开发时不可以修改本文件
 **/


// 共享栏目生成静态
class Test extends \Phpcmf\Table
{

    public function xss() {
        $str = "{php \$api[\$key]['thumb']=dr_get_file(\$t['shoujitu']);}";
        $code = \Phpcmf\Service::L('security')->xss_clean($str);
        var_dump($code);
    }
    public function wx() {

        $url = 'https://mp.weixin.qq.com/s?src=11&timestamp=1614514381&ver=2918&signature=wQ49nqibKHpxUXilHkCNvmt7CrRmrV*bmd4sez-JJdcYxYOYY6kM-uMev1amkAr4eWiPTupVcJamUTZJ0OliseOccLr-h8ZgZkQfWg2AIOqfO*6AfORm9G05GUDVVvPl&new=1';
        $html = dr_catcher_data($url);
        $preg = '<div class="rich_media_content " id="js_content" style="visibility: hidden;">';
        if (preg_match('/'.$preg.'(.+)<\/div>/sU', $html, $mt)) {
            $body = trim($mt[1]);
            $body = str_replace(['style="display: none;"', ''], '', $body);
            $body = str_replace('data-src=', 'src=', $body);
        }
        if (preg_match('/<meta property="og:title" content="(.+)"/U', $html, $mt)) {
            $title = trim($mt[1]);
        }
        if (preg_match('/<meta property="og:image" content="(.+)"/U', $html, $mt)) {
            $thumb = trim($mt[1]);
        }


    }

	public function index() {

	    $aa = [
            'code1' => '参数1',
            'code2' => '参数2',
            'code3' => '参数3',
        ];



		\Phpcmf\Service::V()->display('test.html');
	}

	public function cat() {

        // 初始化数据表
        $this->_init([
            'table' => SITE_ID.'_help_category',
            'show_field' => 'name',
            'order_by' => 'displayorder ASC,id ASC',
        ]);

        \Phpcmf\Service::M('category')->init($this->init); // 初始化内容模型

	    for ($i=3200; $i<6000; $i++) {
            $data = [];
            $data['name'] = $i;
            $data['dirname'] = \Phpcmf\Service::L('pinyin')->result($data['name']);

            $data['pid'] = 0;
            $data['show'] = 1;
            $data['pids'] = '';
            $data['thumb'] = '';
            $data['pdirname'] = '';
            $data['childids'] = '';

            $data['setting'] = [
                'edit' => 1,
                'disabled' => 0,
                'template' => [
                    'list' => 'list.html',
                    'show' => 'show.html',
                    'category' => 'category.html',
                    'search' => 'search.html',
                    'pagesize' => 20,
                ],
                'seo' => [
                    'list_title' => '[第{page}页{join}]{name}{join}{modname}{join}{SITE_NAME}',
                    'show_title' => '[第{page}页{join}]{title}{join}{catname}{join}{modname}{join}{SITE_NAME}',
                ],
            ];
            $data['setting']['getchild'] = 0;
            $data['setting'] = dr_array2string($data['setting']);

            $rt = \Phpcmf\Service::M('Category')->insert($data);
            if (!$rt['code']) {
                $this->_json(0, $rt['msg']);
            }

        }

    }
	
}
