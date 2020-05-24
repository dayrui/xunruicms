<?php namespace Phpcmf\Controllers;

/**
 * http://www.xunruicms.com
 * 本文件是框架系统文件，二次开发时不可以修改本文件
 **/

class Home extends \Phpcmf\Common
{
	private $is_html;

	// 首页动作
	public function _index() {
		\Phpcmf\Service::V()->assign([
			'indexc' => 1,
		]);
        \Phpcmf\Service::V()->assign(\Phpcmf\Service::L('Seo')->index());
		\Phpcmf\Service::V()->display('index.html');
	}

	// 首页显示
	public function index() {
        \Phpcmf\Service::L('Router')->is_redirect_url(dr_url_prefix('/'));
        $this->_index();
	}

	/**
	 * 404 页面
	 */
	public function s404() {
		$uri = \Phpcmf\Service::L('input')->get('uri', true);
		$this->goto_404_page('没有找到这个页面: '.$uri);
	}


	// 生成静态
	public function html() {

		// 判断权限
		if (!dr_html_auth()) {
            $this->_json(0, '权限验证超时，请重新执行生成');
        } elseif ($this->member_cache['auth_site'][SITE_ID]['home']) {
            $this->_json(0, '当前网站设置了访问权限，无法生成静态');
        } elseif (!$this->site_info[SITE_ID]['SITE_INDEX_HTML']) {
            $this->_json(0, '当前网站未开启首页静态功能');
        }

        // 标识变量
        !defined('SC_HTML_FILE') && define('SC_HTML_FILE', 1);

        // 开启ob函数
        ob_start();
		$this->is_html = 1;
        \Phpcmf\Service::V()->init("pc");
        \Phpcmf\Service::V()->assign([
            'fix_html_now_url' => SITE_URL, // 修复静态下的当前url变量
        ]);
		$this->_index();
		$html = ob_get_clean();
		$pc = file_put_contents(\Phpcmf\Service::L('html')->get_webpath(SITE_ID, 'site', 'index.html'), $html, LOCK_EX);

        if (SITE_IS_MOBILE_HTML) {
            // 开启ob函数
            ob_start();
            $this->is_html = 1;
            \Phpcmf\Service::V()->init("mobile");
            \Phpcmf\Service::V()->assign([
                'fix_html_now_url' => SITE_MURL, // 修复静态下的当前url变量
            ]);
            $this->_index();
            $html = ob_get_clean();
            $mfile = \Phpcmf\Service::L('html')->get_webpath(SITE_ID, 'site', 'mobile/index.html');
            $mobile = file_put_contents($mfile, $html, LOCK_EX);
            !$mobile && log_message('error', '网站首页移动端首页生成失败：'.$mfile);
        } else {
            log_message('error', '网站首页移动端首页生成失败：没有开启移动端静态');
        }

		$this->_json(1, dr_lang('电脑端 （%s），移动端 （%s）', dr_format_file_size($pc), dr_format_file_size($mobile)));
	}

}
