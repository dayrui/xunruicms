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



class System_log extends \Phpcmf\Common
{
	public function index() {


		$time = (int)strtotime(\Phpcmf\Service::L('input')->get('time'));
		!$time && $time = SYS_TIME;
		
		$file = WRITEPATH.'log/'.date('Ym', $time).'/'.date('d', $time).'.php';

		$list = [];
		$data = @explode(PHP_EOL, str_replace(array(chr(13), chr(10)), PHP_EOL, file_get_contents($file)));
		$data = @array_reverse($data);

		$page = max(1, (int)\Phpcmf\Service::L('input')->get('page'));
        $total = max(0, dr_count($data) - 1);
		$limit = ($page - 1) * SYS_ADMIN_PAGESIZE;

		$i = $j = 0;

		foreach ($data as $v) {
			if ($v && $i >= $limit && $j < SYS_ADMIN_PAGESIZE) {
				$list[] = dr_string2array($v);
				$j ++;
			}
			$i ++;
		}

		$time = date('Y-m-d', $time);

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
	

}
