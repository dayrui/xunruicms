<?php
/**
 * 初始化安装数据
 */

// 不选择安装数据时不执行sql文件
if (isset($_GET['is_install_db']) && $_GET['is_install_db']) {
    if (is_file(APPSPATH.'Module/Config/App.php')) {
        $rt = \Phpcmf\Service::M('app')->install('module');
        if ($rt['code']) {
            \Phpcmf\Service::M('module', 'module')->install('news', null, 0, 1);
            $sql = file_get_contents(MYPATH.'Config/demo.sql');
            if ($sql) {
                $sql = str_replace('{dbprefix}', \Phpcmf\Service::M()->prefix, $sql);
                \Phpcmf\Service::M()->query_all($sql);
            }
        }
    }
}

if (is_file(APPSPATH.'Mbdy/Config/App.php')) {
    \Phpcmf\Service::M('app')->install('mbdy');
}

// 默认站点信息字段
$site_field = [];
$site_field[] = '{"name":"友情链接","fieldname":"yqlj","fieldtype":"Ftable","isedit":"1","ismain":"1","issystem":"0","ismember":"1","issearch":"0","disabled":"0","setting":{"option":{"is_add":"1","is_first_hang":"0","count":"","first_cname":"","hang":{"1":{"name":""},"2":{"name":""},"3":{"name":""},"4":{"name":""},"5":{"name":""}},"field":{"1":{"type":"1","name":"网站名称","width":"200","option":""},"2":{"type":"1","name":"网站地址","width":"","option":""},"3":{"type":"0","name":"","width":"","option":""},"4":{"type":"0","name":"","width":"","option":""},"5":{"type":"0","name":"","width":"","option":""},"6":{"type":"0","name":"","width":"","option":""},"7":{"type":"0","name":"","width":"","option":""},"8":{"type":"0","name":"","width":"","option":""},"9":{"type":"0","name":"","width":"","option":""},"10":{"type":"0","name":"","width":"","option":""}},"width":"","height":"","css":""},"validate":{"required":"0","pattern":"","errortips":"","xss":"1","check":"","filter":"","tips":"","formattr":""},"is_right":"0"},"displayorder":"0"}';
$site_field[] = '{"name":"幻灯图片","fieldname":"hdtp","fieldtype":"Ftable","isedit":"1","ismain":"1","issystem":"0","ismember":"1","issearch":"0","disabled":"0","setting":{"option":{"is_add":"1","is_first_hang":"0","count":"","first_cname":"","hang":{"1":{"name":""},"2":{"name":""},"3":{"name":""},"4":{"name":""},"5":{"name":""}},"field":{"1":{"type":"3","name":"图片","width":"200","option":""},"2":{"type":"1","name":"名称","width":"200","option":""},"3":{"type":"1","name":"跳转地址","width":"","option":""},"4":{"type":"0","name":"","width":"","option":""},"5":{"type":"0","name":"","width":"","option":""},"6":{"type":"0","name":"","width":"","option":""},"7":{"type":"0","name":"","width":"","option":""},"8":{"type":"0","name":"","width":"","option":""},"9":{"type":"0","name":"","width":"","option":""},"10":{"type":"0","name":"","width":"","option":""}},"width":"","height":"","css":""},"validate":{"required":"0","pattern":"","errortips":"","xss":"1","check":"","filter":"","tips":"","formattr":""},"is_right":"0"},"displayorder":"0"}';
foreach ($site_field as $t) {
    $value = dr_string2array($t);
    if (!$value) {
        continue;
    }
    $field = \Phpcmf\Service::L('field')->get($value['fieldtype']);
    $value['setting'] = dr_string2array($value['setting']);
    \Phpcmf\Service::M('Field')->relatedid = 1;
    \Phpcmf\Service::M('Field')->relatedname = 'site';
    \Phpcmf\Service::M('Field')->add($value, $field);
}