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



return dr_return_data(1, 'ok');