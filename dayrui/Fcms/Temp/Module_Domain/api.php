<?php

/**
 * 域名api
 */

header('Content-Type: text/html; charset=utf-8');

if (!is_file('{ROOTPATH}index.php')) {
    echo '当前服务器无法访问跨目录文件：';
    echo 'http://help.xunruicms.com/655.html';
    exit();
}

echo 'phpcmf ok';exit;