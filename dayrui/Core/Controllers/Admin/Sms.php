<?php namespace Phpcmf\Controllers\Admin;

/* *
 *
 * Copyright [2019] [李睿]
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * www.xunruicms.com
 *
 * 本文件是框架系统文件，二次开发时不建议修改本文件
 *
 * */



class Sms extends \Phpcmf\Common
{
	
	public function __construct(...$params) {
		parent::__construct(...$params);
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

		if (IS_AJAX_POST) {

			$data = \Phpcmf\Service::L('input')->post('data', true);
			strlen($data['note']) > 30 && $this->_json(0, dr_lang('短信签名超出了范围'));

			if ($_POST['aa'] == 0) {
				unset($data['third']);
			}

			!\Phpcmf\Service::L('Config')->file($file, '短信配置文件')->to_require_one($data) && $this->_json(0, dr_lang('配置文件写入失败'));

			\Phpcmf\Service::L('input')->system_log('配置短信接口'); // 记录日志
			$this->_json(1, dr_lang('操作成功'));
		}

		\Phpcmf\Service::V()->assign(array(
			'data' => is_file($file) ? require $file : [],
		));
		\Phpcmf\Service::V()->display('sms_index.html');
	}
	
	public function add() {

		$file = WRITEPATH.'config/sms.php';
		!is_file($file) && $this->_admin_msg(0, dr_lang('没有配置短信账号，不能使用发送功能'));
		
		if (IS_AJAX_POST) {

			$data = \Phpcmf\Service::L('input')->post('data');
			strlen($data['content']) > 150 && exit($this->_json(0, dr_lang('短信内容过长，不得超过70个汉字')));

			$mobile = trim(str_replace(',,', ',', str_replace(array(PHP_EOL, chr(13), chr(10)), ',', $data['mobiles'])), ',');
			substr_count($mobile, ',') > 40 && exit($this->_json(0, dr_lang('群发一次不得超过40个，数量过多时请分批发送')));
			
			\Phpcmf\Service::L('input')->system_log('发送系统短信'); // 记录日志
			
			$rt = \Phpcmf\Service::M('member')->sendsms_text($mobile, $data['content']);
            exit($this->_json($rt['code'], $rt['msg']));
		}
		
		\Phpcmf\Service::V()->display('sms_add.html');
	}

	

}
