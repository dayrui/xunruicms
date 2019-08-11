<?php
/**
 * 初始化安装数据，如果不要安装数据请删除本文件
 */

\Phpcmf\Service::M('module')->install('news');
$sql = file_get_contents(MYPATH.'Config/demo.sql');
if ($sql) {
    $sql = str_replace('{dbprefix}', \Phpcmf\Service::M()->prefix, $sql);
    \Phpcmf\Service::M()->query_all($sql);
}