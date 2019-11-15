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
  `area` mediumint(8) unsigned DEFAULT NULL COMMENT \'所在地\',
  `xiaoqumingcheng` varchar(255) DEFAULT NULL COMMENT \'小区名称\',
  `dizhi` varchar(255) DEFAULT NULL COMMENT \'地址\',
  `weizhi_lng` decimal(9,6) DEFAULT NULL COMMENT \'位置\',
  `weizhi_lat` decimal(9,6) DEFAULT NULL COMMENT \'位置\',
  `zujinleixing` varchar(255) DEFAULT NULL COMMENT \'租金类型\',
  `zujin` varchar(255) DEFAULT NULL COMMENT \'租金\',
  `suozaiceng` varchar(255) DEFAULT NULL COMMENT \'所在层\',
  `zongceng` varchar(255) DEFAULT NULL COMMENT \'总层\',
  `chaoxiang` varchar(255) DEFAULT NULL COMMENT \'朝向\',
  `zhuangxiu` varchar(255) DEFAULT NULL COMMENT \'装修\',
  `mianji` varchar(255) DEFAULT NULL COMMENT \'面积\',
  `wei` varchar(255) DEFAULT NULL COMMENT \'卫\',
  `ting` varchar(255) DEFAULT NULL COMMENT \'厅\',
  `shi` varchar(255) DEFAULT NULL COMMENT \'室\',
  `huxing` varchar(255) DEFAULT NULL COMMENT \'户型\',
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
  `images` text COMMENT \'房屋图片\',
  `peitao` varchar(255) DEFAULT NULL COMMENT \'配套\',
  `lianxiren` varchar(255) DEFAULT NULL COMMENT \'联系人\',
  `lianxidianhua` varchar(255) DEFAULT NULL COMMENT \'联系电话\',
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
                            'name' => '所在地',
                            'fieldname' => 'area',
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
                    5 =>
                        array (
                            'name' => '小区名称',
                            'fieldname' => 'xiaoqumingcheng',
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
                    6 =>
                        array (
                            'name' => '地址',
                            'fieldname' => 'dizhi',
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
                    7 =>
                        array (
                            'name' => '位置',
                            'fieldname' => 'weizhi',
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
                                            'level' => '',
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
                    8 =>
                        array (
                            'name' => '租金类型',
                            'fieldname' => 'zujinleixing',
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
                                            'options' => '押一付三
押一付二
押一付一
半年付
年付
面议',
                                            'value' => '',
                                            'fieldtype' => '',
                                            'fieldlength' => '',
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
                            'name' => '租金',
                            'fieldname' => 'zujin',
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
                    10 =>
                        array (
                            'name' => '所在层',
                            'fieldname' => 'suozaiceng',
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
                                            'width' => '50',
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
                            'name' => '总层',
                            'fieldname' => 'zongceng',
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
                                            'width' => '50',
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
                            'name' => '朝向',
                            'fieldname' => 'chaoxiang',
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
                                            'options' => '南北通透
东西向
朝南
朝北
朝东
朝西',
                                            'value' => '',
                                            'fieldtype' => '',
                                            'fieldlength' => '',
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
                    13 =>
                        array (
                            'name' => '装修',
                            'fieldname' => 'zhuangxiu',
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
                                            'options' => '毛坯
简装修
中等装修
精装修
豪华装修',
                                            'value' => '',
                                            'fieldtype' => '',
                                            'fieldlength' => '',
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
                            'name' => '面积',
                            'fieldname' => 'mianji',
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
                                            'width' => '50',
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
                            'name' => '卫',
                            'fieldname' => 'wei',
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
                                            'width' => '50',
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
                            'name' => '厅',
                            'fieldname' => 'ting',
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
                                            'width' => '50',
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
                            'name' => '室',
                            'fieldname' => 'shi',
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
                                            'width' => '50',
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
                    18 =>
                        array (
                            'name' => '户型',
                            'fieldname' => 'huxing',
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
                                            'options' => '一居
二居
三居
四居
四居以上',
                                            'value' => '',
                                            'fieldtype' => '',
                                            'fieldlength' => '',
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
                    19 =>
                        array (
                            'name' => '房屋状况',
                            'fieldname' => 'fwzk',
                            'fieldtype' => 'Group',
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
                                            'value' => '<label>室: </label><label>{shi}</label><label>厅:</label> <label>{ting}</label>  <label>卫: </label><label>{wei}</label>  <label>面积: </label><label>{mianji}</label><label>平米，所在层: </label><label>{suozaiceng}</label>  <label>总层:</label> <label>{zongceng}</label> <br> <label>户型: </label>{huxing}  <label>朝向: </label>{chaoxiang}  <label>装修:</label> {zhuangxiu}',
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
                            'name' => '配套',
                            'fieldname' => 'peitao',
                            'fieldtype' => 'Checkbox',
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
                                            'options' => '拎包入住
家电齐全
可上网
可做饭
可洗澡
空调房
可看电视
有暖气
有车位',
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
                    1 =>
                        array (
                            'name' => '联系人',
                            'fieldname' => 'lianxiren',
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
                    2 =>
                        array (
                            'name' => '联系电话',
                            'fieldname' => 'lianxidianhua',
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
                    3 =>
                        array (
                            'name' => '房屋图片',
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
                                            'input' => '1',
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
                            'displayorder' => '8',
                        ),
                    4 =>
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
                            'displayorder' => '9',
                        ),
                ),
        ),
);