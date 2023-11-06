<?php
/**
 * {{www.xunruicms.com}}
 * {{迅睿内容管理框架系统}}
 * 本文件是框架系统文件，二次开发时不可以修改本文件，可以通过继承类方法来重写此文件
 **/

/**
 * 系统可用字段
 * id 文件名称
 * name 显示名称
 * used 数组 表示可以用到哪些地方
 * namespace 是哪个app专属的
 */

return [
    [
        'id' => 'Text',
        'name' => '文本字段',
    ],
    [
        'id' => 'Textbtn',
        'name' => '文本事件',
    ],
    [
        'id' => 'Textselect',
        'name' => '文本选择',
    ],
    [
        'id' => 'Textarea',
        'name' => '多行文本',
    ],
    [
        'id' => 'Editor',
        'name' => '简化编辑器',
    ],
    [
        'id' => 'Radio',
        'name' => '单选按钮',
    ],
    [
        'id' => 'Select',
        'name' => '下拉选择（单选）',
    ],
    [
        'id' => 'Selects',
        'name' => '下拉选择（多选）',
    ],
    [
        'id' => 'Checkbox',
        'name' => '复选框',
    ],
    [
        'id' => 'Color',
        'name' => '颜色选取',
    ],
    [
        'id' => 'Date',
        'name' => '日期',
    ],
    [
        'id' => 'Time',
        'name' => '时间',
    ],
    [
        'id' => 'Diy',
        'name' => '自定义',
    ],
    [
        'id' => 'File',
        'name' => '单文件',
    ],
    [
        'id' => 'Files',
        'name' => '多文件',
    ],
    [
        'id' => 'Group',
        'name' => '单行分组字段',
    ],
    [
        'id' => 'Merge',
        'name' => '多行分组字段',
        'used' => ['module', 'form', 'mform', 'site'],
    ],
    [
        'id' => 'Linkage',
        'name' => '联动菜单（单选）',
    ],
    [
        'id' => 'Linkages',
        'name' => '联动菜单（多选）',
    ],
    [
        'id' => 'Touchspin',
        'name' => '增减量',
    ],
    [
        'id' => 'Property',
        'name' => '属性参数',
    ],
    [
        'id' => 'Uid',
        'name' => '账号',
    ],
    [
        'id' => 'Pay',
        'name' => '购买（单一）',
    ],
    [
        'id' => 'Pays',
        'name' => '购买（组合）',
    ],
    [
        'id' => 'Paystext',
        'name' => '购买（组合）参数',
    ],
    [
        'id' => 'Image',
        'name' => '图片专用',
    ],
    [
        'id' => 'Ftable',
        'name' => '填写表格',
    ],


];