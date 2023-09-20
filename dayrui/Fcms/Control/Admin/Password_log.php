<?php namespace Phpcmf\Control\Admin;

/**
 * {{www.xunruicms.com}}
 * {{迅睿内容管理框架系统}}
 * 本文件是框架系统文件，二次开发时不可以修改本文件
 **/

class Password_log extends \Phpcmf\Common
{
	
	public function __construct() {
		parent::__construct();
		\Phpcmf\Service::V()->assign('menu', \Phpcmf\Service::M('auth')->_admin_menu(
			[
				'密码错误记录' => [\Phpcmf\Service::L('Router')->class.'/index', 'fa fa-unlock-alt'],
			]
		));
	}

	public function index() {

        $file = WRITEPATH.'password_log.php';
		$data = $list = [];
        if (filesize($file) > 1024*1024*2) {
            $list[] = [
                'time' => SYS_TIME,
                'username' => '账号',
                'message' => '此日志文件大于2MB，请使用Ftp等工具查看此文件：'.$file,
            ];
        } else {
            $code = file_get_contents($file);
            if ($code) {
                $data = explode(PHP_EOL, str_replace(array(chr(13), chr(10)), PHP_EOL, $code));
                if ($data) {
                    unset($data[0]);
                    $data = array_reverse($data);
                }
                $page = max(1, (int)\Phpcmf\Service::L('input')->get('page'));
                $limit = ($page - 1) * SYS_ADMIN_PAGESIZE;
                $i = $j = 0;
                foreach ($data as $v) {
                    $val = dr_string2array($v);
                    if ($val && $i >= $limit && $j < SYS_ADMIN_PAGESIZE) {
                        $list[] = $val;
                        $j ++;
                    }
                    $i ++;
                }
            }
        }

        $total = max(0, dr_count($data) - 1);
		
		\Phpcmf\Service::V()->assign(array(

			'list' => $list,
			'total' => $total,
			'mypages' => \Phpcmf\Service::L('input')->page(dr_url(\Phpcmf\Service::L('Router')->class.'/index'), $total, 'admin')
		));
		\Phpcmf\Service::V()->display('password_log.html');
	}


	public function del() {

		unlink(WRITEPATH.'password_log.php');

		$this->_json(1, dr_lang('操作成功'));
	}
	

}
