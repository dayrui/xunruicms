<?php namespace Phpcmf\Controllers\Admin;

/**
 * http://www.xunruicms.com
 * 本文件是框架系统文件，二次开发时不可以修改本文件
 **/

class Email_log extends \Phpcmf\Common
{
	
	public function __construct(...$params) {
		parent::__construct(...$params);
		\Phpcmf\Service::V()->assign('menu', \Phpcmf\Service::M('auth')->_admin_menu(
			[
				'错误日志' => ['email_log/index', 'fa fa-calendar'],
			]
		));
	}

	public function index() {

		$data = $list = [];
		$file = WRITEPATH.'email_log.php';
		if (is_file(WRITEPATH.'email_log.php')) {
			$data = explode(PHP_EOL, str_replace(array(chr(13), chr(10)), PHP_EOL, file_get_contents($file)));
			$data = $data ? array_reverse($data) : [];
			unset($data[0]);
			$page = max(1, (int)\Phpcmf\Service::L('input')->get('page'));
			$limit = ($page - 1) * SYS_ADMIN_PAGESIZE;
			$i = $j = 0;
			foreach ($data as $v) {
				if ($i >= $limit && $j < SYS_ADMIN_PAGESIZE) {
					$list[] = $v;
					$j ++;
				}
				$i ++;
			}
		}

		$total = $data ? max(0, count($data) - 1) : 0;

		\Phpcmf\Service::V()->assign(array(
			'list' => $list,
			'total' => $total,
			'mypages'	=> \Phpcmf\Service::L('input')->page(\Phpcmf\Service::L('Router')->url('email_log/index'), $total, 'admin')
		));
		\Phpcmf\Service::V()->display('email_log.html');
	}

	public function del() {

		@unlink(WRITEPATH.'email_log.php');

		exit($this->_json(1, dr_lang('操作成功')));
	}


}
