<?php namespace Phpcmf\Control\Admin;
/**
 * {{www.xunruicms.com}}
 * {{迅睿内容管理框架系统}}
 * 本文件是框架系统文件，二次开发时不可以修改本文件
 **/

class Sms extends \Phpcmf\Common
{
	
	public function __construct() {
		parent::__construct();
		\Phpcmf\Service::V()->assign('menu', \Phpcmf\Service::M('auth')->_admin_menu(
			[
				'短信设置' => [\Phpcmf\Service::L('Router')->class.'/index', 'fa fa-envelope'],
				'发送短信' => [\Phpcmf\Service::L('Router')->class.'/add', 'fa fa-send'],
                'help' => ['393'],
			]
		));
	}

	public function index() {

		$file = WRITEPATH.'config/sms.php';
        $cfile = WRITEPATH.'config/cache.php';
        $cache = is_file($cfile) ? require $cfile : [];

		if (IS_AJAX_POST) {

			$data = \Phpcmf\Service::L('input')->post('data');
			if ($data['note'] && strlen($data['note']) > 30) {
			    $this->_json(0, dr_lang('短信签名超出了范围'));
            }

			if (isset($_POST['aa']) && $_POST['aa'] == 0) {
				unset($data['third']);
			}

			$cache['SYS_CACHE_SMS'] = (int)\Phpcmf\Service::L('input')->post('SYS_CACHE_SMS');
            if (!\Phpcmf\Service::L('Config')->file($cfile, '缓存配置文件')->to_require_one($cache)) {
                $this->_json(0, dr_lang('配置文件写入失败'));
            }

			if (!\Phpcmf\Service::L('Config')->file($file, '短信配置文件')->to_require_one($data)) {
			    $this->_json(0, dr_lang('配置文件写入失败'));
            }

			\Phpcmf\Service::L('input')->system_log('配置短信接口'); // 记录日志
			$this->_json(1, dr_lang('操作成功'));
		}

		\Phpcmf\Service::V()->assign(array(
			'data' => is_file($file) ? require $file : [],
			'cache' => $cache,
		));
		\Phpcmf\Service::V()->display('sms_index.html');
	}
	
	public function add() {

		$file = WRITEPATH.'config/sms.php';
		if (!is_file($file)) {
		    $this->_admin_msg(0, dr_lang('没有配置短信账号，不能使用发送功能'));
        }
		
		if (IS_AJAX_POST) {

			$data = \Phpcmf\Service::L('input')->post('data');
			if ($data['content'] && strlen($data['content']) < 10) {
			    $this->_json(0, dr_lang('短信内容太短了'));
            } elseif (!$data['mobiles']) {
                $this->_json(0, dr_lang('手机号码不能为空'));
            }

			$ok++;
			$mobile = explode(',', trim(str_replace(',,', ',', str_replace(array(PHP_EOL, chr(13), chr(10)), ',', $data['mobiles'])), ','));
			foreach ($mobile as $m) {
                $rt = \Phpcmf\Service::M('member')->sendsms_text($m, $data['content']);
                if ($rt['code']) {
                    $ok ++;
                }
            }
			
			\Phpcmf\Service::L('input')->system_log('发送系统短信'); // 记录日志

            $this->_json(1, dr_lang('发送成功%s个手机', $ok));
		}
		
		\Phpcmf\Service::V()->display('sms_add.html');
	}

	

}
