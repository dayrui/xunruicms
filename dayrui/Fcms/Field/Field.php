<?php

/* *
 *
 * Copyright [2018] [李睿]
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * 本文件是框架系统文件，二次开发时不建议修改本文件
 *
 * */


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
        'used' => '',
        'namespace' => '',
    ],
    [
        'id' => 'Textbtn',
        'name' => '文本事件',
        'used' => [],
        'namespace' => '',
    ],
    [
        'id' => 'Textarea',
        'name' => '多行文本',
        'namespace' => '',
    ],
    [
        'id' => 'Ueditor',
        'name' => '百度编辑器',
        'namespace' => '',
    ],
    [
        'id' => 'Baidumap',
        'name' => '百度地图',
        'namespace' => '',
    ],
    [
        'id' => 'Radio',
        'name' => '单选按钮',
        'namespace' => '',
    ],
    [
        'id' => 'Select',
        'name' => '下拉选择',
        'namespace' => '',
    ],
    [
        'id' => 'Checkbox',
        'name' => '复选框',
        'namespace' => '',
    ],
    [
        'id' => 'Color',
        'name' => '颜色选取',
        'namespace' => '',
    ],
    [
        'id' => 'Date',
        'name' => '日期时间',
        'namespace' => '',
    ],
    [
        'id' => 'Diy',
        'name' => '自定义',
        'namespace' => '',
    ],
    [
        'id' => 'File',
        'name' => '单文件',
        'namespace' => '',
    ],
    [
        'id' => 'Files',
        'name' => '多文件',
        'namespace' => '',
    ],
    [
        'id' => 'Group',
        'name' => '单行分组字段',
        'namespace' => '',
    ],
    [
        'id' => 'Merge',
        'name' => '多行分组字段',
        'namespace' => '',
    ],
    [
        'id' => 'Linkage',
        'name' => '联动菜单（单选）',
        'namespace' => '',
    ],
    [
        'id' => 'Touchspin',
        'name' => '增减量',
        'used' => [],
        'namespace' => '',
    ],
    [
        'id' => 'Property',
        'name' => '属性参数',
        'namespace' => '',
    ],
    [
        'id' => 'Redirect',
        'name' => '转向链接',
        'uesd' => ['module'],
        'namespace' => '',
    ],
    [
        'id' => 'Related',
        'name' => '内容关联',
        'namespace' => '',
    ],
    [
        'id' => 'Members',
        'name' => '用户关联',
        'namespace' => '',
    ],
    [
        'id' => 'Pay',
        'name' => '购买（单一）',
        'namespace' => '',
    ],
    [
        'id' => 'Pays',
        'name' => '购买（组合）',
        'namespace' => '',
    ],
    /*
    [
        'id' => 'Paystext',
        'name' => '购买（组合）参数',
        'namespace' => '',
    ],*/
    [
        'id' => 'Score',
        'name' => '用户组设定值',
        'namespace' => '',
    ],
];