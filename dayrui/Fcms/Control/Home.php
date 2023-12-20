<?php namespace Phpcmf\Control;
/**
 * {{www.xunruicms.com}}
 * {{迅睿内容管理框架系统}}
 * 本文件是框架系统文件，二次开发时不可以修改本文件
 **/

class Home extends \Phpcmf\Common {

	private $is_html;

	// 首页动作
	public function _index() {

        ob_start();
		\Phpcmf\Service::V()->assign([
			'indexc' => 1,
            'fix_html_now_url' => defined('IS_MOBILE') && IS_MOBILE ? SITE_MURL : SITE_URL,
		]);
        \Phpcmf\Service::V()->assign(\Phpcmf\Service::L('Seo')->index());
		\Phpcmf\Service::V()->display('index.html');
        $html = ob_get_clean();

        // 开启过首页静态时
        if ($this->site_info[SITE_ID]['SITE_INDEX_HTML'] && !defined('SC_HTML_FILE')) {
            if (IS_CLIENT) {
                // 自定义终端
                $file = \Phpcmf\Service::L('html')->get_webpath(SITE_ID, 'site', IS_CLIENT.'/index.html');
                $rt = file_put_contents($file, $html);
                !$rt && CI_DEBUG && log_message('error', '项目首页终端（'.IS_CLIENT.'）生成失败：'.$file);
            } elseif (defined('IS_MOBILE') && IS_MOBILE) {
                // 移动端，当移动端独立域名情况下才生成静态
                if (defined('SITE_IS_MOBILE_HTML') && SITE_IS_MOBILE_HTML && SITE_MURL == dr_now_url()) {
                    $mfile = \Phpcmf\Service::L('html')->get_webpath(SITE_ID, 'site', SITE_MOBILE_DIR.'/index.html');
                    $mobile = file_put_contents($mfile, $html);
                    !$mobile && CI_DEBUG && log_message('error', '项目首页移动端生成失败：'.$mfile);
                }
            } else {
                // pc
                $file = \Phpcmf\Service::L('html')->get_webpath(SITE_ID, 'site', 'index.html');
                $pc = file_put_contents($file, $html);
                !$pc && CI_DEBUG && log_message('error', '项目首页PC端首页生成失败：'.$file);
            }
        }

        echo $html;
	}

	// 首页显示
	public function index() {

	    if (IS_POST) {
	        $this->_json(0, '禁止提交，请检查提交地址是否有误');
        }

        // 挂钩点 网站首页时
        \Phpcmf\Hooks::trigger('cms_index');
        \Phpcmf\Service::L('Router')->is_redirect_url(dr_url_prefix('/'), 1);

        $this->_index();
	}

	/**
	 * 404 页面
	 */
	public function s404() {
		if (IS_DEV) {
            $uri = \Phpcmf\Service::L('input')->get('uri', true);
		    $msg = '没有找到这个页面: '.$uri;
        } else {
		    $msg = dr_lang('没有找到这个页面');
        }
		$this->goto_404_page($msg);
	}

	// 生成静态
	public function html() {

		// 判断权限
		if (!dr_html_auth()) {
            $this->_json(0, '权限验证超时，请重新执行生成');
        }

        // 标识变量
        !defined('SC_HTML_FILE') && define('SC_HTML_FILE', 1);

        // 开启ob函数
        ob_start();
		$this->is_html = 1;
        \Phpcmf\Service::V()->init('pc');
        \Phpcmf\Service::V()->assign([
            'fix_html_now_url' => SITE_URL, // 修复静态下的当前url变量
        ]);
		$this->_index();
		$html = ob_get_clean();
		$file = \Phpcmf\Service::L('html')->get_webpath(SITE_ID, 'site', 'index.html');
		$pc = file_put_contents($file, $html, LOCK_EX);
        !$pc && CI_DEBUG && log_message('error', '项目首页PC端首页生成失败：'.$file);

        if (SITE_MURL != SITE_URL) {
            // 开启ob函数
            ob_start();
            $this->is_html = 1;
            \Phpcmf\Service::V()->init('mobile');
            \Phpcmf\Service::V()->assign([
                'fix_html_now_url' => SITE_MURL, // 修复静态下的当前url变量
            ]);
            $this->_index();
            $html = ob_get_clean();
            $mfile = \Phpcmf\Service::L('html')->get_webpath(SITE_ID, 'site', SITE_MOBILE_DIR.'/index.html');
            $mobile = file_put_contents($mfile, $html, LOCK_EX);
            !$mobile && CI_DEBUG && log_message('error', '项目首页移动端生成失败：'.$mfile);
        } else {
            CI_DEBUG && log_message('error', '项目首页移动端生成失败：移动端未绑定域名');
        }

		$this->_json(1, dr_lang('电脑端 （%s），移动端 （%s）', dr_format_file_size($pc), dr_format_file_size($mobile)));
	}
}
