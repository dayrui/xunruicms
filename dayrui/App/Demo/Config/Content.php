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
  `favorites` int(10) unsigned DEFAULT \'0\' COMMENT \'收藏数量\',
  `avgsort` decimal(10,2) unsigned DEFAULT \'0.00\' COMMENT \'平均点评分数\',
  `support` int(10) unsigned DEFAULT \'0\' COMMENT \'支持数\',
  `oppose` int(10) unsigned DEFAULT \'0\' COMMENT \'反对数\',
  `donation` int(10) unsigned DEFAULT \'0\' COMMENT \'捐赠总额\',
  `displayorder` int(10) DEFAULT \'0\' COMMENT \'排序值\',
  `fkbd_total` int(10) unsigned DEFAULT \'0\' COMMENT \'表单反馈表单统计\',
  `wbsj` varchar(255) DEFAULT NULL,
  `dxwb` text,
  `baiduditu_lng` decimal(9,6) DEFAULT NULL,
  `baiduditu_lat` decimal(9,6) DEFAULT NULL,
  `danxuananniu` varchar(255) DEFAULT NULL,
  `fuxuankuang` varchar(255) DEFAULT NULL,
  `rqsjgs` int(10) unsigned DEFAULT NULL,
  `nyrgs` int(10) unsigned DEFAULT NULL,
  `xialaxuanze` varchar(255) DEFAULT NULL,
  `dgwjsc` varchar(255) DEFAULT NULL,
  `dgtpsc` text,
  `csxz` mediumint(8) unsigned DEFAULT NULL,
  `cssx` text,
  `glxzxwnr` text,
  `ysfyb` varchar(20) DEFAULT NULL,
  `dydgm` decimal(10,2) DEFAULT NULL,
  `zhxgm` decimal(9,2) DEFAULT NULL,
  `zhxgm_sku` text,
  `zhxgm_quantity` int(10) DEFAULT NULL,
  `zhxgm_sn` varchar(10) DEFAULT NULL,
  `wenben1` varchar(255) DEFAULT NULL,
  `wenben2` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `uid` (`uid`),
  KEY `catid` (`catid`,`updatetime`),
  KEY `link_id` (`link_id`),
  KEY `comments` (`comments`),
  KEY `avgsort` (`avgsort`),
  KEY `support` (`support`),
  KEY `oppose` (`oppose`),
  KEY `donation` (`donation`),
  KEY `favorites` (`favorites`),
  KEY `status` (`status`),
  KEY `updatetime` (`updatetime`),
  KEY `hits` (`hits`),
  KEY `displayorder` (`displayorder`,`updatetime`),
  KEY `fkbd_total` (`fkbd_total`)
) ENGINE=MyISAM AUTO_INCREMENT=160 DEFAULT CHARSET=utf8 COMMENT=\'内容主表\'',
            0 => 'CREATE TABLE IF NOT EXISTS `{tablename}` (
  `id` int(10) unsigned NOT NULL,
  `uid` mediumint(8) unsigned NOT NULL COMMENT \'作者uid\',
  `catid` smallint(5) unsigned NOT NULL COMMENT \'栏目id\',
  `content` mediumtext COMMENT \'内容\',
  UNIQUE KEY `id` (`id`),
  KEY `uid` (`uid`),
  KEY `catid` (`catid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT=\'内容附表\'',
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
                    1 =>
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
                    2 =>
                        array (
                            'name' => '文本事件',
                            'fieldname' => 'wbsj',
                            'fieldtype' => 'Textbtn',
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
                                            'color' => '',
                                            'icon' => '',
                                            'name' => '',
                                            'func' => '',
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
                            'name' => '多行文本',
                            'fieldname' => 'dxwb',
                            'fieldtype' => 'Textarea',
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
                                            'value' => '',
                                            'width' => '',
                                            'height' => '',
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
                            'name' => '单选按钮',
                            'fieldname' => 'danxuananniu',
                            'fieldtype' => 'Radio',
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
                                            'options' => '选项名称1|1
选项名称2|2
选项名称3|3
选项名称4|4',
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
                    5 =>
                        array (
                            'name' => '复选框',
                            'fieldname' => 'fuxuankuang',
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
                                            'options' => '选项名称1|1
选项名称2|2
选项名称3|3
选项名称4|4',
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
                    6 =>
                        array (
                            'name' => '日期时间格式',
                            'fieldname' => 'rqsjgs',
                            'fieldtype' => 'Date',
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
                                            'format2' => '0',
                                            'value' => '',
                                            'width' => '',
                                            'color' => '',
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
                            'name' => '年月日格式',
                            'fieldname' => 'nyrgs',
                            'fieldtype' => 'Date',
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
                                            'format2' => '1',
                                            'value' => '',
                                            'width' => '',
                                            'color' => '',
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
                    8 =>
                        array (
                            'name' => '下拉选择',
                            'fieldname' => 'xialaxuanze',
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
                                            'options' => '选项名称1|1
选项名称2|2
选项名称3|3
选项名称4|4',
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
                    9 =>
                        array (
                            'name' => '单单个文件上传',
                            'fieldname' => 'dgwjsc',
                            'fieldtype' => 'File',
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
                                            'ext' => 'zip',
                                            'size' => '10',
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
                    10 =>
                        array (
                            'name' => '多个图片上传',
                            'fieldname' => 'dgtpsc',
                            'fieldtype' => 'Files',
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
                                            'size' => '10',
                                            'count' => '10',
                                            'ext' => 'jpg,gif,png',
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
                    11 =>
                        array (
                            'name' => '城市联动选择',
                            'fieldname' => 'csxz',
                            'fieldtype' => 'Linkage',
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
                                            'linkage' => 'address',
                                            'value' => '',
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
                    12 =>
                        array (
                            'name' => '颜色放右边',
                            'fieldname' => 'ysfyb',
                            'fieldtype' => 'Color',
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
                                    'is_right' => '1',
                                ),
                            'displayorder' => '0',
                        ),
                    13 =>
                        array (
                            'name' => '单一的购买',
                            'fieldname' => 'dydgm',
                            'fieldtype' => 'Pay',
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
                                            'payfile' => 'buy.html',
                                            'is_finecms' => '1',
                                            'width' => 'buy.html',
                                            'color' => '',
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
                    14 =>
                        array (
                            'name' => '组合性购买',
                            'fieldname' => 'zhxgm',
                            'fieldtype' => 'Pays',
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
                                            'payfile' => 'buy.html',
                                            'is_finecms' => '1',
                                            'field' =>
                                                array (
                                                    0 => 'price',
                                                    1 => 'quantity',
                                                    2 => 'sn',
                                                ),
                                            'width' => 'buy.html',
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
                    15 =>
                        array (
                            'name' => '文本1',
                            'fieldname' => 'wenben1',
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
                    16 =>
                        array (
                            'name' => '文本2',
                            'fieldname' => 'wenben2',
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
                    17 =>
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
                            'displayorder' => '1',
                        ),
                    18 =>
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
                            'displayorder' => '2',
                        ),
                    19 =>
                        array (
                            'name' => '关联选择新闻内容',
                            'fieldname' => 'glxzxwnr',
                            'fieldtype' => 'Related',
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
                                            'module' => 'news',
                                            'limit' => '20',
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
                    20 =>
                        array (
                            'name' => '参数属性',
                            'fieldname' => 'cssx',
                            'fieldtype' => 'Property',
                            'isedit' => '1',
                            'ismain' => '1',
                            'issystem' => '0',
                            'ismember' => '1',
                            'issearch' => '0',
                            'disabled' => '0',
                            'setting' =>
                                array (
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
                                    'option' =>
                                        array (
                                            'css' => '',
                                        ),
                                    'is_right' => '0',
                                ),
                            'displayorder' => '3',
                        ),
                    21 =>
                        array (
                            'name' => '百度地图',
                            'fieldname' => 'baiduditu',
                            'fieldtype' => 'Baidumap',
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
                                            'level' => '15',
                                            'width' => '',
                                            'height' => '',
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
                            'displayorder' => '4',
                        ),
                    22 =>
                        array (
                            'name' => '文件上传组',
                            'fieldname' => 'wjscz',
                            'fieldtype' => 'Merge',
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
                                            'value' => '{dgwjsc}
{thumb}
{dgtpsc}',
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
                            'displayorder' => '7',
                        ),
                ),
            0 =>
                array (
                    0 =>
                        array (
                            'name' => '单行组合测试',
                            'fieldname' => 'dxzhcs',
                            'fieldtype' => 'Group',
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
                                            'value' => ' <label> 文本1:</label>
 <label>  {wenben1} </label>
  <label> 文本2: </label>  
<label>{wenben2}</label>',
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
                    1 =>
                        array (
                            'name' => '内容',
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
                                            'watermark' => '0',
                                            'autofloat' => '0',
                                            'autoheight' => '0',
                                            'page' => '1',
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
                            'displayorder' => '5',
                        ),
                    2 =>
                        array (
                            'name' => '选择分组',
                            'fieldname' => 'xuanzefenzu',
                            'fieldtype' => 'Merge',
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
                                            'value' => '{danxuananniu}
{fuxuankuang}
{xialaxuanze}
{csxz}',
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
                            'displayorder' => '6',
                        ),
                    3 =>
                        array (
                            'name' => '日期组',
                            'fieldname' => 'riqizu',
                            'fieldtype' => 'Merge',
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
                                            'value' => '
{rqsjgs}
{nyrgs}',
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
                            'displayorder' => '8',
                        ),
                    4 =>
                        array (
                            'name' => '文本组',
                            'fieldname' => 'wenbenzu',
                            'fieldtype' => 'Merge',
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
                                            'value' => '{wbsj}
{dxzhcs}
{dxwb}
{description}',
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
                            'displayorder' => '9',
                        ),
                    5 =>
                        array (
                            'name' => '价格字段组',
                            'fieldname' => 'jgzdz',
                            'fieldtype' => 'Merge',
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
                                            'value' => '
{dydgm}
{zhxgm}',
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
                            'displayorder' => '10',
                        ),
                ),
        ),
);