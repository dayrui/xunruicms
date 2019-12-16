<?php namespace Phpcmf\Controllers\Admin;

/**
 * http://www.xunruicms.com
 * 本文件是框架系统文件，二次开发时不可以修改本文件
 **/

class Error extends \Phpcmf\Common
{
	public function __construct(...$params) {
		parent::__construct(...$params);
		\Phpcmf\Service::V()->assign('menu', \Phpcmf\Service::M('auth')->_admin_menu(
			[
				'系统错误' => ['error/index', 'fa fa-shield'],
                'PHP错误' => ['error_php/index', 'fa fa-bug'],
			]
		));
		
	}

	public function index() {

		$time = (int)strtotime(\Phpcmf\Service::L('input')->get('time'));
		!$time && $time = SYS_TIME;

        $list = [];
        $total = 0;
		$file = WRITEPATH.'error/log-'.date('Y-m-d', $time).'.php';
        if (is_file($file)) {

            $c = file_get_contents($file);
            $data = @explode(PHP_EOL, trim(str_replace('<?php defined(\'BASEPATH\') OR exit(\'No direct script access allowed\'); ?>'.PHP_EOL.PHP_EOL, '', str_replace(array(chr(13), chr(10)), PHP_EOL, $c)), PHP_EOL));
            $data && $data = @array_reverse($data);

            $page = max(1, (int)\Phpcmf\Service::L('input')->get('page'));
            //$total = max(0, count($data));
            $total = max(0, substr_count($c, '- '.date('Y-m-d', $time).' '));
            $limit = ($page - 1) * SYS_ADMIN_PAGESIZE;

            $i = $j = 0;

            foreach ($data as $t) {
                if ($t && $i >= $limit && $j < SYS_ADMIN_PAGESIZE) {
                    $v = @explode(' --> ', $t);
                    $time2 = $v ? @explode(' - ', $v[0]) : [1=>''];
                    if ($time2[1]) {
                        $list[] = [
                            'time' => $time2[1] ? $time2[1] : '',
                            'error' => str_replace(["'", '"'], '', str_replace([PHP_EOL, chr(13), chr(10)], ' ', $v[1])),
                            'message' => str_replace([PHP_EOL, chr(13), chr(10)], ' ', htmlentities($v[1])),
                        ];
                        $j ++;
                    }
                }
                $i ++;
            }

        }

		$time = date('Y-m-d', $time);

		\Phpcmf\Service::V()->assign(array(
			'list' => $list,
			'time' => $time,
			'total' => $total,
			'mypages'	=> \Phpcmf\Service::L('input')->page(\Phpcmf\Service::L('Router')->url(\Phpcmf\Service::L('Router')->class.'/index', ['time' => $time]), $total, 'admin')
		));
		\Phpcmf\Service::V()->display('error_log.html');
	}

	public function log_show() {

        $time = dr_safe_filename($_GET['time']);
        !$time && $time = date('Y-m-d');
        $file = WRITEPATH.'error/log-'.$time.'.php';
        if (!$file) {
            exit('文件不存在：'.$file);
        }
        $code = file_get_contents($file);
        \Phpcmf\Service::V()->assign([
            'file' => $file,
            'code' => $code,
            'menu' => [],
        ]);

        \Phpcmf\Service::V()->display('error_file.html');
    }

}
