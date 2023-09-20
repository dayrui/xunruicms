<?php namespace Phpcmf\Control\Admin;
/**
 * {{www.xunruicms.com}}
 * {{迅睿内容管理框架系统}}
 * 本文件是框架系统文件，二次开发时不可以修改本文件
 **/

class Sms_log extends \Phpcmf\Common
{
	
	public function __construct() {
		parent::__construct();
		\Phpcmf\Service::V()->assign('menu', \Phpcmf\Service::M('auth')->_admin_menu(
			[
				'短信记录' => [\Phpcmf\Service::L('Router')->class.'/index', 'fa fa-envelope'],
			]
		));
	}


	public function index() {

		$data = $list = [];
		$file = file_get_contents(WRITEPATH.'sms_log.txt');
		if ($file) {
			$data = explode(PHP_EOL, str_replace(array(chr(13), chr(10)), PHP_EOL, $file));
			$data = $data ? array_reverse($data) : [];
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

        $total = max(0, dr_count($data) - 1);
		
		\Phpcmf\Service::V()->assign(array(
			'list' => $list,
			'total' => $total,
			'mypages'	=> \Phpcmf\Service::L('input')->page(\Phpcmf\Service::L('Router')->url('sms_log/index'), $total, 'admin')
		));
		\Phpcmf\Service::V()->display('sms_log.html');
	}

	public function del() {

		unlink(WRITEPATH.'sms_log.txt');

		$this->_json(1, dr_lang('操作成功'));
	}
	

}
