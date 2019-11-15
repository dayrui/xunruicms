<?php

return array (
    'table' =>
        array (
            1 => 'CREATE TABLE IF NOT EXISTS `{tablename}` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `catid` smallint(5) unsigned NOT NULL COMMENT \'栏目id\',
  `title` varchar(255) DEFAULT NULL COMMENT \'主题\',
  `thumb` varchar(255) DEFAULT NULL COMMENT \'缩略图\',
  `keywords` varchar(255) DEFAULT NULL COMMENT \'关键字\',
  `description` text COMMENT \'描述\',
  `hits` int(10) unsigned DEFAULT NULL COMMENT \'浏览数\',
  `uid` int(10) unsigned NOT NULL COMMENT \'作者id\',
  `author` varchar(50) NOT NULL COMMENT \'作者名称\',
  `status` tinyint(2) NOT NULL COMMENT \'状态\',
  `url` varchar(255) DEFAULT NULL COMMENT \'地址\',
  `link_id` int(10) NOT NULL DEFAULT \'0\' COMMENT \'同步id\',
  `tableid` smallint(5) unsigned NOT NULL COMMENT \'附表id\',
  `inputip` varchar(15) DEFAULT NULL COMMENT \'录入者ip\',
  `inputtime` int(10) unsigned NOT NULL COMMENT \'录入时间\',
  `updatetime` int(10) unsigned NOT NULL COMMENT \'更新时间\',
  `comments` int(10) unsigned DEFAULT \'0\' COMMENT \'评论数量\',
  `avgsort` decimal(10,2) unsigned DEFAULT \'0.00\' COMMENT \'平均点评分数\',
  `displayorder` int(10) DEFAULT \'0\' COMMENT \'排序值\',
  `color` varchar(30) DEFAULT NULL COMMENT \'颜色\',
  `isflag` varchar(255) DEFAULT NULL COMMENT \'置顶\',
  `reply_info` text,
  PRIMARY KEY (`id`),
  KEY `uid` (`uid`),
  KEY `catid` (`catid`,`updatetime`),
  KEY `link_id` (`link_id`),
  KEY `comments` (`comments`),
  KEY `avgsort` (`avgsort`),
  KEY `status` (`status`),
  KEY `updatetime` (`updatetime`),
  KEY `hits` (`hits`),
  KEY `displayorder` (`displayorder`,`updatetime`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT=\'内容主表\'',
            0 => 'CREATE TABLE IF NOT EXISTS `{tablename}` (
  `id` int(10) unsigned NOT NULL,
  `uid` mediumint(8) unsigned NOT NULL COMMENT \'作者uid\',
  `catid` smallint(5) unsigned NOT NULL COMMENT \'栏目id\',
  `content` mediumtext COMMENT \'内容\',
  UNIQUE KEY `id` (`id`),
  KEY `uid` (`uid`),
  KEY `catid` (`catid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT=\'内容附表\'',
        ),
    'field' =>
        array (
            1 =>
                array (
                    0 =>
                        array (
                            'name' => '缩略图',
                            'fieldname' => 'thumb',
                            'fieldtype' => 'File',
                            'isedit' => '1',
                            'ismain' => '1',
                            'issystem' => '1',
                            'ismember' => '1',
                            'issearch' => '1',
                            'disabled' => '1',
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
                        ),
                    1 =>
                        array (
                            'name' => '关键字',
                            'fieldname' => 'keywords',
                            'fieldtype' => 'Text',
                            'isedit' => '1',
                            'ismain' => '1',
                            'issystem' => '1',
                            'ismember' => '1',
                            'issearch' => '1',
                            'disabled' => '1',
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
                                            'formattr' => ' data-role="tagsinput"',
                                        ),
                                ),
                            'displayorder' => '0',
                        ),
                    2 =>
                        array (
                            'name' => '描述',
                            'fieldname' => 'description',
                            'fieldtype' => 'Textarea',
                            'isedit' => '1',
                            'ismain' => '1',
                            'issystem' => '1',
                            'ismember' => '1',
                            'issearch' => '1',
                            'disabled' => '1',
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
                                            'filter' => 'dr_clearhtml',
                                        ),
                                ),
                            'displayorder' => '0',
                        ),
                    3 =>
                        array (
                            'name' => '置顶',
                            'fieldname' => 'isflag',
                            'fieldtype' => 'Radio',
                            'isedit' => '1',
                            'ismain' => '1',
                            'issystem' => '0',
                            'ismember' => '0',
                            'issearch' => '0',
                            'disabled' => '0',
                            'setting' =>
                                array (
                                    'option' =>
                                        array (
                                            'options' => '无|0
本版置顶|1
全局置顶|2',
                                            'value' => '',
                                            'fieldtype' => '',
                                            'fieldlength' => '',
                                            'show_type' => '0',
                                            'css' => '',
                                        ),
                                    'validate' =>
                                        array (
                                            'required' => '0',
                                            'pattern' => '',
                                            'errortips' => '',
                                            'check' => '',
                                            'filter' => '',
                                            'formattr' => '',
                                            'tips' => '',
                                        ),
                                    'is_right' => '0',
                                ),
                            'displayorder' => '1',
                        ),
                    4 =>
                        array (
                            'name' => '颜色',
                            'fieldname' => 'color',
                            'fieldtype' => 'Color',
                            'isedit' => '1',
                            'ismain' => '1',
                            'issystem' => '0',
                            'ismember' => '0',
                            'issearch' => '0',
                            'disabled' => '0',
                            'setting' =>
                                array (
                                    'option' =>
                                        array (
                                            'field' => 'title',
                                            'value' => '',
                                            'width' => '',
                                            'css' => '',
                                        ),
                                    'validate' =>
                                        array (
                                            'required' => '0',
                                            'pattern' => '',
                                            'errortips' => '',
                                            'check' => '',
                                            'filter' => '',
                                            'formattr' => '',
                                            'tips' => '',
                                        ),
                                    'is_right' => '0',
                                ),
                            'displayorder' => '2',
                        ),
                    5 =>
                        array (
                            'name' => '主题',
                            'fieldname' => 'title',
                            'fieldtype' => 'Text',
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
                            'displayorder' => '3',
                        ),
                ),
            0 =>
                array (
                    0 =>
                        array (
                            'name' => '内容',
                            'fieldname' => 'content',
                            'fieldtype' => 'Ueditor',
                            'isedit' => '1',
                            'ismain' => '0',
                            'issystem' => '1',
                            'ismember' => '1',
                            'issearch' => '1',
                            'disabled' => '0',
                            'setting' =>
                                array (
                                    'option' =>
                                        array (
                                            'mode' => 1,
                                            'width' => '100%',
                                            'height' => 400,
                                        ),
                                    'validate' =>
                                        array (
                                            'xss' => 1,
                                            'required' => 1,
                                        ),
                                ),
                            'displayorder' => '4',
                        ),
                ),
        ),
);