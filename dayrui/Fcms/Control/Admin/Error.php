<?php namespace Phpcmf\Control\Admin;
/**
 * {{www.xunruicms.com}}
 * {{迅睿内容管理框架系统}}
 * 本文件是框架系统文件，二次开发时不可以修改本文件
 **/

class Error extends \Phpcmf\Common {

	public function index() {

		$time = (int)strtotime(\Phpcmf\Service::L('input')->get('time'));
		!$time && $time = SYS_TIME;

        $list = [];
        $total = 0;
		$file = WRITEPATH.'error/log-'.date('Y-m-d', $time).'.php';
        if (is_file($file)) {
            if (filesize($file) > 1024*1024*2) {
                $list[] = [
                    'time' => date('Y-m-d', $time),
                    'type' => '<span class="label label-warning"> '.dr_lang('提醒').' </span>',
                    'message' => '此日志文件大于2MB，请使用Ftp等工具查看此文件：'.$file,
                ];
            } else {
                $c = file_get_contents($file);
                $data = explode(PHP_EOL, trim(str_replace('<?php defined(\'BASEPATH\') OR exit(\'No direct script access allowed\'); ?>'.PHP_EOL.PHP_EOL, '', str_replace(array(chr(13), chr(10)), PHP_EOL, $c)), PHP_EOL));
                $data && $data = array_reverse($data);

                $page = max(1, (int)\Phpcmf\Service::L('input')->get('page'));
                //$total = max(0, count($data));
                $total = max(0, substr_count($c, '- '.date('Y-m-d', $time).' '));
                $limit = ($page - 1) * SYS_ADMIN_PAGESIZE;

                $i = $j = 0;

                foreach ($data as $t) {
                    if ($t && $i >= $limit && $j < SYS_ADMIN_PAGESIZE) {
                        $v = explode(' --> ', $t);
                        $time2 = $v ? explode(' - ', $v[0]) : [ 0 => '', 1 => '' ];
                        if ($time2[1]) {
                            $value = [
                                'time' => $time2[1] ? $time2[1] : '',
                                'type' => '',
                            ];
                            if ($time2[0] == 'DEBUG') {
                                $value['type'] = '<span class="label label-success"> '.dr_lang('调试').' </span>';
                            } elseif ($time2[0] == 'INFO') {
                                $value['type'] = '<span class="label label-default"> '.dr_lang('信息').' </span>';
                            } elseif ($time2[0] == 'WARNING') {
                                $value['type'] = '<span class="label label-warning"> '.dr_lang('提醒').' </span>';
                            } else {
                                $value['type'] = '<span class="label label-danger"> '.dr_lang('错误').' </span>';
                            }
                            if (strpos((string)$v[1], '{br}')) {
                                // phpcmf模式
                                $vv = explode('{br}', $v[1]);
                                $value['message'] = $vv[0];
                                unset($vv[0]);
                                $value['json'] = str_replace("'", '\\\'', $vv[1]);
                                unset($vv[1]);
                                $value['info'] = '错误：'.$value['message'].'<br>';
                                foreach ($vv as $p) {
                                    $value['info'].= $p.'<br>';
                                }
                                $value['info'] = str_replace("'", '\\\'', $value['info']);
                            } else {
                                // ci4模式
                                $value['message'] = str_replace([PHP_EOL, chr(13), chr(10)], ' ', htmlentities((string)$v[1]));
                                if (preg_match('/'.$value['time'].' \-\->(.*)\{main\}/sU', $c, $mt)) {
                                    $value['info'] = str_replace("'", '\\\'', str_replace([PHP_EOL, chr(13), chr(10)], '<br>', $mt[1]));
                                }
                            }
                            $value['message'] = str_replace("'", '\\\'', $value['message']);
                            $list[] = $value;
                            $j ++;
                        }
                    }
                    $i ++;
                }
            }
        }

		$time = date('Y-m-d', $time);

		\Phpcmf\Service::V()->assign([
		    'menu' => \Phpcmf\Service::M('auth')->_admin_menu(
                [
                    '系统日志' => ['error/index', 'fa fa-shield'],
                ]
            ),
			'list' => $list,
			'time' => $time,
			'total' => $total,
			'mypages' => \Phpcmf\Service::L('input')->page(\Phpcmf\Service::L('Router')->url(\Phpcmf\Service::L('Router')->class.'/index', ['time' => $time]), $total, 'admin')
        ]);
		\Phpcmf\Service::V()->display('error_log.html');
	}

	public function log_show() {

        $time = dr_safe_filename($_GET['time']);
        !$time && $time = date('Y-m-d');
        $file = WRITEPATH.'error/log-'.$time.'.php';
        if (!$file) {
            exit('文件不存在：'.$file);
        }
        if (filesize($file) > 1024*1024*2) {
            exit('此日志文件大于2MB，请使用Ftp等工具查看此文件：'.$file);
        }
        $code = file_get_contents($file);
        \Phpcmf\Service::V()->assign([
            'file' => $file,
            'code' => $code,
            'menu' => [],
        ]);

        \Phpcmf\Service::V()->display('error_file.html');exit;
    }

    public function del() {

        $time = dr_safe_filename($_GET['time']);
        !$time && $time = date('Y-m-d');
        $file = WRITEPATH.'error/log-'.$time.'.php';
        unlink($file);

        $this->_json(1, dr_lang('操作成功'));
    }

}
