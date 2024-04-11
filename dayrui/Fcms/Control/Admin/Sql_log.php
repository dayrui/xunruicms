<?php namespace Phpcmf\Control\Admin;

/**
 * {{www.xunruicms.com}}
 * {{迅睿内容管理框架系统}}
 * 本文件是框架系统文件，二次开发时不可以修改本文件
 **/

class Sql_log extends \Phpcmf\Common
{
	
	public function __construct() {
		parent::__construct();
		\Phpcmf\Service::V()->assign('menu', \Phpcmf\Service::M('auth')->_admin_menu(
			[
				'前端慢查询记录' => [\Phpcmf\Service::L('Router')->class.'/index', 'fa fa-database'],
			]
		));
	}

	public function index() {

        $dir = dr_file_map(WRITEPATH.'database/Sql/');
        $time = (int)\Phpcmf\Service::L('input')->get('time');
        if (!$time) {
            $time = 'sql';
        }

        $data = $list = [];
        $file = WRITEPATH.'database/Sql/'.$time.'.txt';
        if (filesize($file) > 1024*1024*2) {
            $list[] = [
                $time,
                '',
                '10',
                'message' => '此日志文件大于2MB，请使用Ftp等工具查看此文件：'.$file,
            ];
        } else {
            $code = file_get_contents($file);
            if ($code) {
                $data = explode(PHP_EOL, str_replace(array(chr(13), chr(10)), PHP_EOL, $code));
                $data = $data ? array_reverse($data) : [];
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
			'time' => $time,
			'mdir' => $dir,
			'list' => $list,
			'total' => $total,
            'used' => is_file(WRITEPATH.'database/sql.lock'),
			'mypages' => \Phpcmf\Service::L('input')->page(dr_url(\Phpcmf\Service::L('Router')->class.'/index', ['time' => $time]), $total, 'admin')
		));
		\Phpcmf\Service::V()->display('sql_log.html');
	}

	public function add() {

		$file = WRITEPATH.'database/sql.lock';
        if (is_file($file)) {
            unlink($file);
        } else {
            $size = file_put_contents($file, 'ok');
            if (!$size) {
                dr_mkdirs(dirname($file));
                $this->_json(0, dr_lang('Cache目录权限不可写入'));
            }
        }

		$this->_json(1, dr_lang('操作成功'));
	}

	public function del() {

		dr_dir_delete(WRITEPATH.'database/Sql/');

		$this->_json(1, dr_lang('操作成功'));
	}

}
