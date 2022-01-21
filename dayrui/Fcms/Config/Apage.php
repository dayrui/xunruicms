<?php
/**
 * {{www.xunruicms.com}}
 * {{迅睿内容管理框架系统}}
 * 本文件是框架系统文件，二次开发时不可以修改本文件
 **/

/**
 * 后台的自定义分页标签样式
 */

if (dr_is_mobile()) {
    return array(

        // 自定义“统计”链接
        'total_link' => '共%s条', // 你希望在分页中显示“统计”链接的名字。如果你不希望显示，可以把它的值设为 FALSE
        'total_tag_open' => '<li><a>', // “统计”链接的打开标签
        'total_tag_close' => '</a></li>', // “统计”链接的关闭标签

        // 自定义“下一页”链接
        'next_link' => '下页', // 你希望在分页中显示“下一页”链接的名字。如果你不希望显示，可以把它的值设为 FALSE
        'next_tag_open' => '<li>', // “下一页”链接的打开标签
        'next_tag_close' => '</li>', // “下一页”链接的关闭标签

        // 自定义“上一页”链接
        'prev_link' => '上页', // 你希望在分页中显示“上一页”链接的名字。如果你不希望显示，可以把它的值设为 FALSE
        'prev_tag_open' => '<li>', // “上一页”链接的打开标签
        'prev_tag_close' => '</li>', // “上一页”链接的关闭标签

        // 自定义“当前页”链接
        'cur_tag_open' => '<li class="active"><a>', // “当前页”链接的打开标签
        'cur_tag_close' => '</a></li>', // “当前页”链接的关闭标签

        // 自定义“数字”链接
        'num_tag_open' => '<li>', // “数字”链接的打开标签
        'num_tag_close' => '</li>', // “数字”链接的关闭标签

        // 自定义“最后一页”链接
        'last_link' => FALSE, // 你希望在分页的右边显示“最后一页”链接的名字。如果你不希望显示，可以把它的值设为 FALSE
        'last_tag_open' => '<li>', // “最后一页”链接的打开标签
        'last_tag_close' => '</li>', // “最后一页”链接的关闭标签

        // 自定义“第一页”链接
        'first_link' => FALSE, // 你希望在分页的左边显示“第一页”链接的名字。如果你不希望显示，可以把它的值设为 FALSE
        'first_tag_open' => '<li>', // “第一页”链接的打开标签
        'first_tag_close' => '</li>', // “第一页”链接的关闭标签

        // 是否显示数字链接
        'display_pages' => FALSE,

        // 显示的分页数字个数
        'num_links' => 2,

        // 区域标签
        'full_tag_open' => '<ul class="pagination">',

        // 区域标签
        'full_tag_close' => '</ul>',


    );
} else {
    return array(

        // 自定义“统计”链接
        'total_link' => '共%s条', // 你希望在分页中显示“统计”链接的名字。如果你不希望显示，可以把它的值设为 FALSE
        'total_tag_open' => '<li><a>', // “统计”链接的打开标签
        'total_tag_close' => '</a></li>', // “统计”链接的关闭标签

        // 自定义“下一页”链接
        'next_link' => '', // 你希望在分页中显示“下一页”链接的名字。如果你不希望显示，可以把它的值设为 FALSE
        'next_tag_open' => '', // “下一页”链接的打开标签
        'next_tag_close' => '', // “下一页”链接的关闭标签

        // 自定义“上一页”链接
        'prev_link' => '', // 你希望在分页中显示“上一页”链接的名字。如果你不希望显示，可以把它的值设为 FALSE
        'prev_tag_open' => '', // “上一页”链接的打开标签
        'prev_tag_close' => '', // “上一页”链接的关闭标签

        // 自定义“当前页”链接
        'cur_tag_open' => '<li class="active"><a>', // “当前页”链接的打开标签
        'cur_tag_close' => '</a></li>', // “当前页”链接的关闭标签

        // 自定义“数字”链接
        'num_tag_open' => '<li>', // “数字”链接的打开标签
        'num_tag_close' => '</li>', // “数字”链接的关闭标签

        // 自定义“最后一页”链接
        'last_link' => '最后一页', // 你希望在分页的右边显示“最后一页”链接的名字。如果你不希望显示，可以把它的值设为 FALSE
        'last_tag_open' => '<li>', // “最后一页”链接的打开标签
        'last_tag_close' => '</li>', // “最后一页”链接的关闭标签

        // 自定义“第一页”链接
        'first_link' => '第一页', // 你希望在分页的左边显示“第一页”链接的名字。如果你不希望显示，可以把它的值设为 FALSE
        'first_tag_open' => '<li>', // “第一页”链接的打开标签
        'first_tag_close' => '</li>', // “第一页”链接的关闭标签

        // 是否显示数字链接
        'display_pages' => TRUE,

        // 显示的分页数字个数
        'num_links' => 2,

        // 区域标签
        'full_tag_open' => '<ul class="pagination">',

        // 区域标签
        'full_tag_close' => '</ul>',

    );
}

