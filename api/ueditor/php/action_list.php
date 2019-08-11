<?php

/**
 * 获取已上传的文件列表
 */
include "Uploader.class.php";

/* 判断类型 */
switch ($_GET['action']) {
    /* 列出文件 */
    case 'listfile':
        $allowFiles = $CONFIG['fileManagerAllowFiles'];
        $listSize = $CONFIG['fileManagerListSize'];
        $path = $CONFIG['fileManagerListPath'];
        break;
    /* 列出图片 */
    case 'listimage':
    default:
        $allowFiles = $CONFIG['imageManagerAllowFiles'];
        $listSize = $CONFIG['imageManagerListSize'];
        $path = $CONFIG['imageManagerListPath'];
}
$allowFiles = explode('.', join("", $allowFiles));
if (!$allowFiles[0]) {
    unset($allowFiles[0]);
}

/* 获取参数 */
$size = isset($_GET['size']) ? htmlspecialchars($_GET['size']) : $listSize;
$start = isset($_GET['start']) ? htmlspecialchars($_GET['start']) : 0;
$end = $start + $size;

/* 获取我的列表 */
$data = \Phpcmf\Service::M()->db->table('attachment_data')
                ->where('uid', (int)$this->member['id'])
                ->whereIn('fileext', $allowFiles)
                ->like('related', 'ueditor:%')
                ->orderBy('inputtime desc')->limit(50)
                ->get()->getResultArray();
$files = [];
if ($data) {
    foreach ($data as $t) {
        $files[] = array(
            'url'=> dr_get_file_url($t),
            'mtime'=> $t['inputtime']
        );
    }
}

if (!count($files)) {
    return json_encode(array(
        "state" => "no match file",
        "list" => array(),
        "start" => $start,
        "total" => count($files)
    ));
}

/* 返回数据 */
$result = json_encode(array(
    "state" => "SUCCESS",
    "list" => $files,
    "start" => $start,
    "total" => count($files)
));

return $result;