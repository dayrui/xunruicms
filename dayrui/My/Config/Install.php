<?php
/**
 * 初始化安装数据
 */



// 不选择安装数据时不执行sql文件
if (isset($_GET['is_install_db']) && $_GET['is_install_db']) {
    \Phpcmf\Service::M('module')->install('news', null, 0, 1);
    \Phpcmf\Service::M('module')->install('photo', null, 0, 1);
    \Phpcmf\Service::M('module')->install('down', null, 0, 1);
    \Phpcmf\Service::M('module')->install('fang', null, 0, 1);
    \Phpcmf\Service::M('module')->install('bbs', null, 0, 1);
    \Phpcmf\Service::M('module')->install('demo');
    $sql = file_get_contents(MYPATH.'Config/demo.sql');
    if ($sql) {
        $sql = str_replace('{dbprefix}', \Phpcmf\Service::M()->prefix, $sql);
        \Phpcmf\Service::M()->query_all($sql);
    }
}