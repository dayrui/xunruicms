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
  `version` varchar(255) DEFAULT NULL COMMENT \'软件版本\',
  `language` varchar(255) DEFAULT NULL COMMENT \'软件语言\',
  `license` varchar(255) DEFAULT NULL COMMENT \'软件授权\',
  `os` varchar(255) DEFAULT NULL COMMENT \'适应环境\',
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
  `file` varchar(255) DEFAULT NULL COMMENT \'上传文件\',
  `siteurl` varchar(255) DEFAULT NULL COMMENT \'官方网站\',
  `demo` varchar(255) DEFAULT NULL COMMENT \'演示网站\',
  `images` text COMMENT \'更多图片\',
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
                            'name' => '软件名称',
                            'fieldname' => 'title',
                            'fieldtype' => 'Text',
                            'isedit' => '1',
                            'ismain' => '1',
                            'issystem' => '1',
                            'ismember' => '1',
                            'issearch' => '0',
                            'disabled' => '0',
                            'setting' =>
                                array (
                                    'option' =>
                                        array (
                                            'fieldtype' => 'VARCHAR',
                                            'fieldlength' => '255',
                                            'value' => '',
                                            'width' => '300',
                                            'css' => '',
                                        ),
                                    'validate' =>
                                        array (
                                            'required' => '1',
                                            'pattern' => '',
                                            'errortips' => '',
                                            'xss' => '1',
                                            'check' => '',
                                            'filter' => '',
                                            'formattr' => 'onblur="check_title();get_keywords(\'keywords\');"',
                                            'tips' => '',
                                        ),
                                    'is_right' => '0',
                                ),
                            'displayorder' => '0',
                        ),
                    1 =>
                        array (
                            'name' => '缩略图',
                            'fieldname' => 'thumb',
                            'fieldtype' => 'File',
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
                                            'ext' => 'jpg,gif,png',
                                            'size' => 10,
                                            'width' => 400,
                                            'fieldtype' => 'VARCHAR',
                                            'fieldlength' => '255',
                                        ),
                                ),
                            'displayorder' => '0',
                        ),
                    2 =>
                        array (
                            'name' => '关键字',
                            'fieldname' => 'keywords',
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
                                            'formattr' => ' data-role="tagsinput"',
                                        ),
                                ),
                            'displayorder' => '0',
                        ),
                    3 =>
                        array (
                            'name' => '描述',
                            'fieldname' => 'description',
                            'fieldtype' => 'Textarea',
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
                    4 =>
                        array (
                            'name' => '软件版本',
                            'fieldname' => 'version',
                            'fieldtype' => 'Text',
                            'isedit' => '1',
                            'ismain' => '1',
                            'issystem' => '0',
                            'ismember' => '1',
                            'issearch' => '0',
                            'disabled' => '0',
                            'setting' =>
                                array (
                                    'option' =>
                                        array (
                                            'fieldtype' => '',
                                            'fieldlength' => '',
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
                            'displayorder' => '0',
                        ),
                    5 =>
                        array (
                            'name' => '软件语言',
                            'fieldname' => 'language',
                            'fieldtype' => 'Select',
                            'isedit' => '1',
                            'ismain' => '1',
                            'issystem' => '0',
                            'ismember' => '1',
                            'issearch' => '0',
                            'disabled' => '0',
                            'setting' =>
                                array (
                                    'option' =>
                                        array (
                                            'options' => '简体中文
繁体中文
英文
多国语言',
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
                            'displayorder' => '0',
                        ),
                    6 =>
                        array (
                            'name' => '软件授权',
                            'fieldname' => 'license',
                            'fieldtype' => 'Select',
                            'isedit' => '1',
                            'ismain' => '1',
                            'issystem' => '0',
                            'ismember' => '1',
                            'issearch' => '0',
                            'disabled' => '0',
                            'setting' =>
                                array (
                                    'option' =>
                                        array (
                                            'options' => '共享版
免费版
商业版
试用版
开源软件',
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
                            'displayorder' => '0',
                        ),
                    7 =>
                        array (
                            'name' => '适应环境',
                            'fieldname' => 'os',
                            'fieldtype' => 'Checkbox',
                            'isedit' => '1',
                            'ismain' => '1',
                            'issystem' => '0',
                            'ismember' => '1',
                            'issearch' => '0',
                            'disabled' => '0',
                            'setting' =>
                                array (
                                    'option' =>
                                        array (
                                            'options' => 'Winxp
Win7
Win8
Win10
Linux
Mac',
                                            'value' => '',
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
                            'displayorder' => '0',
                        ),
                ),
            0 =>
                array (
                    0 =>
                        array (
                            'name' => '软件介绍',
                            'fieldname' => 'content',
                            'fieldtype' => 'Ueditor',
                            'isedit' => '1',
                            'ismain' => '0',
                            'issystem' => '1',
                            'ismember' => '1',
                            'issearch' => '0',
                            'disabled' => '0',
                            'setting' =>
                                array (
                                    'option' =>
                                        array (
                                            'down_img' => '0',
                                            'watermark' => '0',
                                            'show_bottom_boot' => '0',
                                            'mini' => '0',
                                            'mobile_mini' => '0',
                                            'autofloat' => '0',
                                            'autoheight' => '0',
                                            'page' => '0',
                                            'mode' => '1',
                                            'tool' => '\'bold\', \'italic\', \'underline\'',
                                            'mode2' => '1',
                                            'tool2' => '\'bold\', \'italic\', \'underline\'',
                                            'mode3' => '1',
                                            'tool3' => '\'bold\', \'italic\', \'underline\'',
                                            'attachment' => '0',
                                            'value' => '',
                                            'width' => '100%',
                                            'height' => '400',
                                            'css' => '',
                                        ),
                                    'validate' =>
                                        array (
                                            'required' => '1',
                                            'pattern' => '',
                                            'errortips' => '',
                                            'xss' => '1',
                                            'check' => '',
                                            'filter' => '',
                                            'formattr' => '',
                                            'tips' => '',
                                        ),
                                    'is_right' => '0',
                                ),
                            'displayorder' => '0',
                        ),
                    1 =>
                        array (
                            'name' => '上传文件',
                            'fieldname' => 'file',
                            'fieldtype' => 'File',
                            'isedit' => '1',
                            'ismain' => '0',
                            'issystem' => '0',
                            'ismember' => '1',
                            'issearch' => '0',
                            'disabled' => '0',
                            'setting' =>
                                array (
                                    'option' =>
                                        array (
                                            'input' => '1',
                                            'ext' => 'rar,zip',
                                            'size' => '11',
                                            'attachment' => '0',
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
                            'displayorder' => '0',
                        ),
                    2 =>
                        array (
                            'name' => '官方网站',
                            'fieldname' => 'siteurl',
                            'fieldtype' => 'Text',
                            'isedit' => '1',
                            'ismain' => '0',
                            'issystem' => '0',
                            'ismember' => '1',
                            'issearch' => '0',
                            'disabled' => '0',
                            'setting' =>
                                array (
                                    'option' =>
                                        array (
                                            'fieldtype' => '',
                                            'fieldlength' => '',
                                            'value' => '',
                                            'width' => '400',
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
                            'displayorder' => '0',
                        ),
                    3 =>
                        array (
                            'name' => '演示网站',
                            'fieldname' => 'demo',
                            'fieldtype' => 'Text',
                            'isedit' => '1',
                            'ismain' => '0',
                            'issystem' => '0',
                            'ismember' => '1',
                            'issearch' => '0',
                            'disabled' => '0',
                            'setting' =>
                                array (
                                    'option' =>
                                        array (
                                            'fieldtype' => '',
                                            'fieldlength' => '',
                                            'value' => '',
                                            'width' => '400',
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
                            'displayorder' => '0',
                        ),
                    4 =>
                        array (
                            'name' => '更多图片',
                            'fieldname' => 'images',
                            'fieldtype' => 'Files',
                            'isedit' => '1',
                            'ismain' => '0',
                            'issystem' => '0',
                            'ismember' => '1',
                            'issearch' => '0',
                            'disabled' => '0',
                            'setting' =>
                                array (
                                    'option' =>
                                        array (
                                            'size' => '10',
                                            'count' => '10',
                                            'ext' => 'jpg,jpeg,png',
                                            'attachment' => '0',
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
                            'displayorder' => '0',
                        ),
                ),
        ),
);