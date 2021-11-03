<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/* 前后端通信相关的配置,注释只允许使用多行方式 */

return [

    /* 上传图片配置项 */
    "imageAltValue" => "name", /*图片alt属性和title属性填充值：title为内容标题字段值、name为图片名称*/
    "imageActionName" => "uploadimage", /* 执行上传图片的action名称 */
    "imageFieldName" => "upfile", /* 提交的图片表单名称 */
    "imageMaxSize" => 2048000, /* 上传大小限制，单位B */
    "imageAllowFiles" => [".png", ".jpg", ".jpeg", ".gif", ".webp"], /* 上传图片格式显示 */
    "imageCompressEnable" => false, /* 是否压缩图片,默认是true */
    "imageCompressBorder" => 1600, /* 图片压缩最长边限制 */
    "imageInsertAlign" => "none", /* 插入的图片浮动方式 */
    "imageUrlPrefix" => "", /* 图片访问路径前缀 */
    "imagePathFormat" => "/ueditor/image/{yyyy}{mm}/{time}{rand:6}", /* 上传保存路径,可以自定义保存路径和文件名格式 */
                                /* {filename} 会替换成原文件名,配置这项需要注意中文乱码问题 */
                                /* {rand:6} 会替换成随机数,后面的数字是随机数的位数 */
                                /* {time} 会替换成时间戳 */
                                /* {yyyy} 会替换成四位年份 */
                                /* {yy} 会替换成两位年份 */
                                /* {mm} 会替换成两位月份 */
                                /* {dd} 会替换成两位日期 */
                                /* {hh} 会替换成两位小时 */
                                /* {ii} 会替换成两位分钟 */
                                /* {ss} 会替换成两位秒 */
                                /* 非法字符 \ : * ? " < > | */

    /* 上传视频配置 */
    "videoActionName" => "uploadvideo", /* 执行上传视频的action名称 */
    "videoFieldName" => "upfile", /* 提交的视频表单名称 */
    "videoPathFormat" => "/ueditor/video/{yyyy}{mm}/{time}{rand:6}", /* 上传保存路径,可以自定义保存路径和文件名格式 */
    "videoUrlPrefix" => "", /* 视频访问路径前缀 */
    "videoMaxSize" => 102400000, /* 上传大小限制，单位B，默认100MB */
    "videoAllowFiles" => [
        ".flv", ".swf", ".mkv", ".avi", ".rm", ".rmvb", ".mpeg", ".mpg",
        ".ogg", ".ogv", ".mov", ".wmv", ".mp4", ".webm", ".mp3", ".wav", ".mid"], /* 上传视频格式显示 */

    /* 上传文件配置 */
    "fileActionName" => "uploadfile", /* controller里,执行上传视频的action名称 */
    "fileFieldName" => "upfile", /* 提交的文件表单名称 */
    "filePathFormat" => "/ueditor/file/{yyyy}{mm}/{time}{rand:6}", /* 上传保存路径,可以自定义保存路径和文件名格式 */
    "fileUrlPrefix" => "", /* 文件访问路径前缀 */
    "fileMaxSize" => 51200000, /* 上传大小限制，单位B，默认50MB */
    "fileAllowFiles" => [
        ".flv", ".swf", ".mkv", ".avi", ".rm", ".rmvb", ".mpeg", ".mpg",
        ".ogg", ".ogv", ".mov", ".wmv", ".mp4", ".webm", ".mp3", ".wav", ".mid",
        ".rar", ".zip", ".tar", ".gz", ".7z", ".bz2", ".cab", ".iso",
        ".doc", ".docx", ".xls", ".xlsx", ".ppt", ".pptx", ".pdf", ".txt", ".md", ".xml"
    ], /* 上传文件格式显示 */

    /* 列出目录下的图片 */
    "imageManagerActionName" => "listimage", /* 执行图片管理的action名称 */
    "imageManagerListSize" => 20, /* 每次列出文件数量 */
    "imageManagerUrlPrefix" => "", /* 图片访问路径前缀 */
    "imageManagerInsertAlign" => "none", /* 插入的图片浮动方式 */
    "imageManagerAllowFiles" => [".png", ".jpg", ".jpeg", ".gif", ".bmp"], /* 列出的文件类型 */

    /* 列出目录下的文件 */
    "showFileExt" => 1, //是否显示文件扩展名，1表示显示，0不显示
    "fileManagerActionName" => "listfile", /* 执行文件管理的action名称 */
    "fileManagerUrlPrefix" => "", /* 文件访问路径前缀 */
    "fileManagerListSize" => 20, /* 每次列出文件数量 */
    "fileManagerAllowFiles" => [
        ".flv", ".swf", ".mkv", ".avi", ".rm", ".rmvb", ".mpeg", ".mpg",
        ".ogg", ".ogv", ".mov", ".wmv", ".mp4", ".webm", ".mp3", ".wav", ".mid",
        ".rar", ".zip", ".tar", ".gz", ".7z", ".bz2", ".cab", ".iso",
        ".doc", ".docx", ".xls", ".xlsx", ".ppt", ".pptx", ".pdf", ".txt", ".md", ".xml"
    ] /* 列出的文件类型 */

];