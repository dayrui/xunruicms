<?php namespace Phpcmf\Control\Admin;
/**
 * {{www.xunruicms.com}}
 * {{迅睿内容管理框架系统}}
 * 本文件是框架系统文件，二次开发时不可以修改本文件
 **/

class System_log extends \Phpcmf\Common {

	public function index() {

		$time = (int)strtotime(\Phpcmf\Service::L('input')->get('time'));
		!$time && $time = SYS_TIME;
		
		$file = WRITEPATH.'log/'.date('Ym', $time).'/'.date('d', $time).'.php';

		$list = [];
		$data = explode(PHP_EOL, str_replace(array(chr(13), chr(10)), PHP_EOL, file_get_contents($file)));
		$data = array_reverse($data);

		$page = max(1, (int)\Phpcmf\Service::L('input')->get('page'));
        $total = max(0, dr_count($data) - 1);
		$limit = ($page - 1) * SYS_ADMIN_PAGESIZE;

		$i = $j = 0;
        $key = 1;
        $time = date('Y-m-d', $time);
		foreach ($data as $v) {
			if ($v && $i >= $limit && $j < SYS_ADMIN_PAGESIZE) {
                $v = dr_string2array($v);
                if ($v) {
                    \Phpcmf\Service::L('cache')->set_data('system_log_'.USER_HTTP_CODE.$time.'_'.$key, $v, 3600);
                    $list[$key] = $v;
                    $j ++;
                    $key ++;
                }
			}
			$i ++;
		}

		\Phpcmf\Service::V()->assign(array(
			'list' => $list,
			'time' => $time,
			'menu' => \Phpcmf\Service::M('auth')->_admin_menu(
                [
                    '操作日志' => [\Phpcmf\Service::L('Router')->class.'/index', 'fa fa-calendar'],
                ]
            ),
			'total' => $total,
			'mypages'	=> \Phpcmf\Service::L('input')->page(\Phpcmf\Service::L('Router')->url('system_log/index', ['time' => $time]), $total, 'admin')
		));
		\Phpcmf\Service::V()->display('system_log.html');
	}

    public function show_index() {

        $key = intval($_GET['id']);
        $time = dr_safe_filename($_GET['time']);
        !$time && $time = date('Y-m-d');

        $cache = \Phpcmf\Service::L('cache')->get_data('system_log_'.USER_HTTP_CODE.$time.'_'.$key);
        if (!$cache) {
            $this->_json(0, dr_lang('查看超时，请重新进入'));
        }

        $cache['msg'] = $cache['action'];
        if ($cache['param']) {
            $cache['msg'].= '<br><hr><pre>'.(var_export($cache['param'], true)).'</pre>';
        }
        $cache['msg'].= '<br><hr>'.htmlspecialchars((string)$cache['url']);
        $cache['msg'] = str_replace([PHP_EOL, chr(13), chr(10)], '<br>', $cache['msg']);

        echo '<link href="'.THEME_PATH.'assets/global/css/admin.min.css" rel="stylesheet" type="text/css" />
        <div style="padding: 20px">';
        echo $cache['msg'];
        exit('</div>');
    }

    public function del() {

        $time = dr_safe_filename($_GET['time']);
        !$time && $time = date('Y-m-d');

        $time = strtotime($time);
        $file = WRITEPATH.'log/'.date('Ym', $time).'/'.date('d', $time).'.php';
        unlink($file);

        $this->_json(1, dr_lang('操作成功'));
    }

}
