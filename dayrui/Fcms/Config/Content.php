<?php
/**
 * {{www.xunruicms.com}}
 * {{迅睿内容管理框架系统}}
 * 本文件是框架系统文件，二次开发时不可以修改本文件
 **/

/**
 * 模块内容表结构及字段
 */

return [
    
    'table' => [

        1 => "CREATE TABLE IF NOT EXISTS `{tablename}` (
          `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
          `catid` smallint(5) unsigned NOT NULL COMMENT '栏目id',
          `title` varchar(255) DEFAULT NULL COMMENT '主题',
          `thumb` varchar(255) DEFAULT NULL COMMENT '缩略图',
          `keywords` varchar(255) DEFAULT NULL COMMENT '关键字',
          `description` text COMMENT '描述',
          `hits` int(10) unsigned DEFAULT NULL COMMENT '浏览数',
          `uid` int(10) unsigned NOT NULL COMMENT '作者id',
          `author` varchar(50) NOT NULL COMMENT '笔名',
          `status` tinyint(2) NOT NULL COMMENT '状态(已废弃)',
          `url` varchar(255) DEFAULT NULL COMMENT '地址',
          `link_id` int(10) NOT NULL DEFAULT '0' COMMENT '同步id',
          `tableid` smallint(5) unsigned NOT NULL COMMENT '附表id',
          `inputip` varchar(200) DEFAULT NULL COMMENT '录入者ip',
          `inputtime` int(10) unsigned NOT NULL COMMENT '录入时间',
          `updatetime` int(10) unsigned NOT NULL COMMENT '更新时间',
          `displayorder` int(10) DEFAULT '0' COMMENT '排序值',
          PRIMARY KEY (`id`),
          KEY `uid` (`uid`),
          KEY `catid` (`catid`),
          KEY `link_id` (`link_id`),
          KEY `status` (`status`),
          KEY `updatetime` (`updatetime`),
          KEY `hits` (`hits`),
          KEY `category` (`catid`, `status`),
          KEY `displayorder` (`displayorder`)
        ) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT='内容主表';
        ",

        0 => "CREATE TABLE IF NOT EXISTS `{tablename}` (
          `id` int(10) unsigned NOT NULL,
          `uid` mediumint(8) unsigned NOT NULL COMMENT '作者uid',
          `catid` smallint(5) unsigned NOT NULL COMMENT '栏目id',
          `content` mediumtext COMMENT '内容',
          UNIQUE KEY `id` (`id`),
          KEY `uid` (`uid`),
          KEY `catid` (`catid`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT='内容附表';
        ",

    ],
    
    'field' => [
        
        1 => array (
            0 =>
                array (
                    'fieldname' => 'title',
                    'fieldtype' => 'Text',
                    'relatedname' => 'module',
                    'isedit' => '1',
                    'ismain' => '1',
                    'issystem' => '1',
                    'ismember' => '1',
                    'issearch' => '1',
                    'disabled' => '0',
                    'setting' =>
                        array (
                            'option' =>
                                array (
                                    'width' => 400,
                                    'fieldtype' => 'VARCHAR',
                                    'fieldlength' => '255',
                                ),
                            'validate' =>
                                array (
                                    'xss' => 1,
                                    'required' => 1,
                                    'formattr' => 'onblur="check_title();get_keywords(\'keywords\');"',
                                ),
                        ),
                    'displayorder' => '0',
                    'textname' => '主题',
                ),
            1 =>
                array (
                    'fieldname' => 'thumb',
                    'fieldtype' => 'File',
                    'relatedid' => '28',
                    'relatedname' => 'module',
                    'isedit' => '1',
                    'ismain' => '1',
                    'issystem' => 1,
                    'ismember' => '1',
                    'issearch' => '1',
                    'disabled' => '0',
                    'setting' =>
                        array (
                            'option' =>
                                array (
                                    'ext' => 'jpg,gif,png',
                                    'size' => 10,
                                    'width' => 400,
                                    'fieldtype' => 'VARCHAR',
                                    'fieldlength' => '255',
                                ),
                        ),
                    'displayorder' => '0',
                    'textname' => '缩略图',
                ),
            2 =>
                array (
                    'fieldname' => 'keywords',
                    'fieldtype' => 'Text',
                    'relatedid' => '28',
                    'relatedname' => 'module',
                    'isedit' => '1',
                    'ismain' => '1',
                    'issystem' => 1,
                    'ismember' => '1',
                    'issearch' => '1',
                    'disabled' => '0',
                    'setting' =>
                        array (
                            'option' =>
                                array (
                                    'width' => 400,
                                    'fieldtype' => 'VARCHAR',
                                    'fieldlength' => '255',
                                ),
                            'validate' =>
                                array (
                                    'xss' => 1,
                                    'formattr' => ' data-role="tagsinput"', // tag属性
                                ),
                        ),
                    'displayorder' => '0',
                    'textname' => '关键字',
                ),
            3 =>
                array (
                    'fieldname' => 'description',
                    'fieldtype' => 'Textarea',
                    'relatedid' => '28',
                    'relatedname' => 'module',
                    'isedit' => '1',
                    'ismain' => '1',
                    'issystem' => 1,
                    'ismember' => '1',
                    'issearch' => '1',
                    'disabled' => '0',
                    'setting' =>
                        array (
                            'option' =>
                                array (
                                    'width' => 500,
                                    'height' => 60,
                                    'fieldtype' => 'VARCHAR',
                                    'fieldlength' => '255',
                                ),
                            'validate' =>
                                array (
                                    'xss' => 1,
                                    'filter' => 'dr_filter_description',
                                ),
                        ),
                    'displayorder' => '0',
                    'textname' => '描述',
                ),
            4 =>
                array (
                    'fieldname' => 'author',
                    'fieldtype' => 'Text',
                    'relatedname' => 'module',
                    'isedit' => '1',
                    'ismain' => '1',
                    'issystem' => 1,
                    'ismember' => '1',
                    'issearch' => '1',
                    'disabled' => '0',
                    'setting' =>
                        array (
                            'is_right' => 1,
                            'option' =>
                                array (
                                    'width' => 200,
                                    'fieldtype' => 'VARCHAR',
                                    'fieldlength' => '255',
                                    'value' => '{name}'
                                ),
                            'validate' =>
                                array (
                                    'xss' => 1,
                                ),
                        ),
                    'displayorder' => '0',
                    'textname' => '笔名',
                ),
        ),
        
        0 =>  array (
            0 =>
                array (
                    'fieldname' => 'content',
                    'fieldtype' => 'Editor',
                    'relatedname' => 'module',
                    'isedit' => '1',
                    'ismain' => '0',
                    'issystem' => 1,
                    'ismember' => '1',
                    'issearch' => '1',
                    'disabled' => '0',
                    'setting' =>
                        array (
                            'option' =>
                                array (
                                    'mode' => 1,
                                    'show_bottom_boot' => 1,
                                    'div2p' => 1,
                                    'width' => '100%',
                                    'height' => 400,
                                ),
                            'validate' =>
                                array (
                                    'xss' => 1,
                                    'required' => 1,
                                ),
                        ),
                    'displayorder' => '0',
                    'textname' => '内容',
                ),
        ),
        
    ],
];