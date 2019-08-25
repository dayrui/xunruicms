<?php
/**
 * 初始化安装数据
 */



// 不选择安装数据时不执行sql文件
if (isset($_GET['is_install_db']) && $_GET['is_install_db']) {
    \Phpcmf\Service::M('module')->install('news');
    $sql = file_get_contents(MYPATH.'Config/demo.sql');
    if ($sql) {
        $sql = str_replace('{dbprefix}', \Phpcmf\Service::M()->prefix, $sql);
        \Phpcmf\Service::M()->query_all($sql);
    }
}