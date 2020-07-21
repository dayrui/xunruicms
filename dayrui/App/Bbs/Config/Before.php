<?php


if (!is_dir(dr_get_app_dir('comment'))) {
	return dr_return_data(0, '请下载官方版的评论系统插件');
}

if (!is_file(dr_get_app_dir('comment').'install.lock')) {
	$rt = \Phpcmf\Service::M('app')->install('comment');
	if (!$rt['code']) {
		return dr_return_data(0, '评论系统插件：'.$rt['msg']);
	}
}

$config = [
	'use' => 1,
	'cname' => '帖子',
	'ct_reply' => 1,
];

$ct = \Phpcmf\Service::M()->table('app_comment')->where('name', 'module')->getRow();
if ($ct) {
	$ct_cfg = dr_string2array($ct['value']);
	if (!$ct_cfg[$dir]) {
		\Phpcmf\Service::M()->db->table('app_comment')->where('name', 'module')->update([
			'value' => dr_array2string([
				$dir => $config,
			]),
		]);
	}
} else {
	\Phpcmf\Service::M()->db->table('app_comment')->insert([
		'name' => 'module',
		'value' => dr_array2string([
			$dir => $config,
		]),
	]);
}


return dr_return_data(1, 'ok');