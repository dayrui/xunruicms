<?php namespace Phpcmf\Controllers\Admin;

/**
 * http://www.xunruicms.com
 * 本文件是框架系统文件，二次开发时不可以修改本文件
 **/

class Error_php extends \Phpcmf\Common
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
		$file = WRITEPATH.'error_php/'.date('Y-m-d', $time).'.php';
        if (is_file($file)) {
            $c = file_get_contents($file);
            $data = @explode(PHP_EOL.PHP_EOL, str_replace(array(chr(13), chr(10)), PHP_EOL, $c));
            $data && $data = @array_reverse($data);

            $page = max(1, (int)\Phpcmf\Service::L('input')->get('page'));
            //$total = max(0, count($data) - 2);
            $total = max(0, substr_count($c, date('Y-m-d', $time).' '));
            $limit = ($page - 1) * SYS_ADMIN_PAGESIZE;

            $i = $j = 0;

            foreach ($data as $t) {
                if ($t  && strpos($t, date('Y-m-d', $time)) !== false && $i >= $limit && $j < SYS_ADMIN_PAGESIZE) {
                    $v = explode(PHP_EOL, $t);
                    $vtime = substr($v[1], 0, 20);
                    if (strpos($vtime, date('Y-m-d', $time)) !== 0) {
                        continue;
                    }
                    $v[5] && $t = str_replace($v[5], '', $t);
                    $json = str_replace("'", '\\\'', $v[5] ? $v[5] : '');
                    $list[] = [
                        'time' => $vtime,
                        'file' => str_replace('文件: ', '', $v[2]),
                        'url' => str_replace('文件: ', '', $v[6]),
                        'line' => str_replace('行号: ', '', $v[3]),
                        'error' => str_replace('错误: ', '', htmlentities($v[4])),
                        'info' => str_replace("'", '', str_replace([PHP_EOL], ['<br>'], $t)),
                        'json' => $json,
                    ];
                    $j ++;
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
		\Phpcmf\Service::V()->display('error_index.html');
	}

	public function show() {

        $time = dr_safe_filename($_GET['time']);
        $file = WRITEPATH.'error_php/'.$time.'.php';
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
