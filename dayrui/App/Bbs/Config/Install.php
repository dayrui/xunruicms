<?php

$rt = \Phpcmf\Service::M('comment', 'comment')->install_module('bbs', SITE_ID);
if (!$rt['code']) {
    log_message('error', '评论系统插件：'.$rt['msg']);
} else {
    $config = [
        'use' => 1,
        'cname' => '帖子',
        'ct_reply' => 1,
    ];

    $ct = \Phpcmf\Service::M()->table('app_comment')->where('name', 'module')->getRow();
    if ($ct) {
        $ct_cfg = dr_string2array($ct['value']);
        if (!$ct_cfg['bbs']) {
            \Phpcmf\Service::M()->db->table('app_comment')->where('name', 'module')->update([
                'value' => dr_array2string([
                    'bbs' => $config,
                ]),
            ]);
        }
    } else {
        \Phpcmf\Service::M()->db->table('app_comment')->insert([
            'name' => 'module',
            'value' => dr_array2string([
                'bbs' => $config,
            ]),
        ]);
    }
}


