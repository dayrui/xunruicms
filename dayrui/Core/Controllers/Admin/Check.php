<?php namespace Phpcmf\Controllers\Admin;

/* *
 *
 * Copyright [2019] [李睿]
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
 * www.xunruicms.com
 *
 * 本文件是框架系统文件，二次开发时不建议修改本文件
 *
 * */



class Check extends \Phpcmf\Common
{

    private $_list = [

        '01' => '文件上传检测',
        '02' => 'PHP环境检测',
        '03' => '目录权限检测',
        '04' => '后台入口名称检测',
        '05' => '数据库权限检测',
        '06' => '模板完整性检测',
        '07' => '数据库表结构检测',
        '08' => '程序兼容性检测',
        '09' => '网站安全性检测',
        '10' => '数据负载优化检测',
        '11' => '域名绑定检测',
        '12' => '表单form最大提交数',

    ];

	public function index() {

	    if (is_file(WRITEPATH.'install.info')) {
            @unlink(WRITEPATH.'install.info');
            @unlink(WRITEPATH.'install.error');
        }

        \Phpcmf\Service::V()->assign([
            'menu' => \Phpcmf\Service::M('auth')->_admin_menu(
                [
                    '系统体检' => [\Phpcmf\Service::L('Router')->class.'/index', 'fa fa-wrench'],
                    '系统更新' => ['cache/index', 'fa fa-refresh'],
                    'PHP环境' => [\Phpcmf\Service::L('Router')->class.'/php_index', 'fa fa-code'],
                ]
            ),
            'list' => $this->_list,
        ]);
		\Phpcmf\Service::V()->display('check_index.html');
	}

	public function do_index() {

	    $id = $_GET['id'];

	    switch ($id) {

            case '01':

                $post = intval(@ini_get("post_max_size"));
                $file = intval(@ini_get("upload_max_filesize"));

                if ($file > $post) {
                    $this->_json(0,'系统配置不合理，post_max_size值('.$post.')必须大于upload_max_filesize值('.$file.')');
                } elseif ($file < 10) {
                    $this->_json(1,'系统环境只允许上传'.$file.'MB文件，可以设置upload_max_filesize值提升上传大小');
                } elseif ($post < 10) {
                    $this->_json(1,'系统环境要求每次发布内容不能超过'.$post.'MB（含文件），可以设置post_max_size值提升发布大小');
                }

                break;


            case '02':

				if (!function_exists('mb_substr')) {
                    $this->_json(0, 'PHP不支持mbstring扩展，必须开启');
                } elseif (!function_exists('curl_init')) {
                     $this->halt('PHP不支持CURL扩展，必须开启', 0);
                } elseif (!function_exists('imagecreatetruecolor')) {
                     $this->halt('PHP的GD库版本太低，无法支持验证码图片', 0);
				} elseif (!function_exists('ini_get')) {
                    $this->_json(0, '系统函数ini_get未启用，将无法获取到系统环境参数');
                } elseif (!function_exists('gzopen')) {
                    $this->halt('zlib扩展未启用，您将无法进行在线升级、无法下载应用插件等', 0);
                } elseif (!function_exists('gzinflate')) {
                     $this->halt('函数gzinflate未启用，您将无法进行在线升级、无法下载应用插件等', 0);
                } elseif (!function_exists('fsockopen')) {
                     $this->halt('PHP不支持fsockopen，可能充值接口无法使用、手机短信无法发送、电子邮件无法发送、一键登录无法登录等', 0);
                } elseif (!function_exists('openssl_open')) {
                     $this->halt('PHP不支持openssl，可能充值接口无法使用、手机短信无法发送、电子邮件无法发送、一键登录无法登录等', 0);
                } elseif (!ini_get('allow_url_fopen')) {
                     $this->halt('allow_url_fopen未启用，远程图片无法保存、网络图片无法上传、可能充值接口无法使用、手机短信无法发送、电子邮件无法发送、一键登录无法登录等', 0);
                }
                break;

            case '03':

                list($thumb_path) = dr_thumb_path();
                list($avatar_path) = dr_avatar_path();

                $dir = array(
                    WRITEPATH => '无法生成系统缓存文件',
                    $avatar_path => '无法上传头像',
                    WRITEPATH.'data/' => '无法生成系统配置文件，会导致系统配置无效',
                    $thumb_path => '无法生成缩略图缓存文件',
                    SYS_UPLOAD_PATH => '无法上传附件',
                    APPSPATH => '无法创建模块、创建表单、下载应用插件',
                    TPLPATH => '无法创建模块模板和应用插件模板',
                );

                foreach ($dir as $path => $note) {
                    if (!dr_check_put_path($path)) {
                        $this->_json(0, $note.'【'.$path.'】');
                    }
                }

                break;

            case '04':
                if (SELF == 'admin.php') {
                    $this->halt('为了系统安全，请修改根目录admin.php的文件名', 0);
                }
                break;

            case '05':

                $list = \Phpcmf\Service::M()->db->query('show table status')->getResultArray();
                if (!$list) {
                    $this->halt("无法获取到数据表结构，需要为Mysql账号开启SHOW TABLE STATUS权限", 0);
                }

                $field = \Phpcmf\Service::M()->db->query('SHOW FULL COLUMNS FROM `'.\Phpcmf\Service::M()->dbprefix('admin').'`')->getResultArray();
                if (!$field) {
                    $this->halt("无法通获取到数据表字段结构，需要为Mysql账号开启SHOW FULL COLUMNS权限", 0);
                }

                break;

            case '06':

                // 语言文件
                $lang = file_get_contents(LANG_PATH.'lang.js');
                if (strlen($lang) < 10) {
                    $this->halt('网站语言JS文件异常：'.LANG_PATH.'lang.js', 0);
                }

                // 模板文件
                if (!is_file(TPLPATH.'pc/'.SITE_TEMPLATE.'/home/index.html')) {
                    $this->halt('网站前端模板【电脑版】不存在：TPLPATH/pc/'.SITE_TEMPLATE.'/home/index.html', 0);
                } elseif (!is_file(TPLPATH.'pc/'.SITE_TEMPLATE.'/member/index.html')) {
                    $this->halt('用户中心模板【电脑版】不存在：TPLPATH/pc/'.SITE_TEMPLATE.'/member/index.html', 0);
                } elseif (!is_file(TPLPATH.'mobile/'.SITE_TEMPLATE.'/home/index.html')) {
                    $this->halt('网站前端模板【手机版】不存在：TPLPATH/mobile/'.SITE_TEMPLATE.'/home/index.html', 1);
                } elseif (!is_file(TPLPATH.'mobile/'.SITE_TEMPLATE.'/member/index.html')) {
                    $this->halt('用户中心模板【手机版】不存在：TPLPATH/mobile/'.SITE_TEMPLATE.'/member/index.html', 1);
                }
                break;

            case '07':

                // 模块
                $module = \Phpcmf\Service::M()->table('module')->order_by('displayorder ASC,id ASC')->getAll();

                // 站点
                $prefix = \Phpcmf\Service::M()->prefix;
                foreach ($this->site as $siteid) {
                    // 升级资料库
                    $table = $prefix.$siteid.'_block';
                    if (\Phpcmf\Service::M()->db->tableExists($table)) {
                        // 创建code字段 代码
                        if (!\Phpcmf\Service::M()->db->fieldExists('code', $table)) {
                            \Phpcmf\Service::M()->query('ALTER TABLE `'.$table.'` ADD `code` VARCHAR(100) NOT NULL');
                        }
                    }
                    if ($module) {
                        foreach ($module as $m) {
                            $table = $prefix.$siteid.'_'.$m['dirname'].'_recycle';
                            if (\Phpcmf\Service::M()->db->tableExists($table)) {
                                // 创建字段 删除理由
                                if (!\Phpcmf\Service::M()->db->fieldExists('result', $table)) {
                                    \Phpcmf\Service::M()->query('ALTER TABLE `'.$table.'` ADD `result` Text NOT NULL');
                                }
                            }
                        }
                    }
                }

                $table = $prefix.'cron';
                if (!\Phpcmf\Service::M()->db->fieldExists('site', $table)) {
                    \Phpcmf\Service::M()->query('ALTER TABLE `'.$table.'` ADD `site` INT(10) NOT NULL COMMENT \'站点\'');
                }

                $table = $prefix.'member_paylog';
                if (!\Phpcmf\Service::M()->db->fieldExists('site', $table)) {
                    \Phpcmf\Service::M()->query('ALTER TABLE `'.$table.'` ADD `site` INT(10) NOT NULL COMMENT \'站点\'');
                }

                $table = $prefix.'member_scorelog';
                if (!\Phpcmf\Service::M()->db->fieldExists('username', $table)) {
                    \Phpcmf\Service::M()->query('ALTER TABLE `'.$table.'` ADD `username` VARCHAR(100) NOT NULL');
                }

                $table = $prefix.'member_explog';
                if (!\Phpcmf\Service::M()->db->fieldExists('username', $table)) {
                    \Phpcmf\Service::M()->query('ALTER TABLE `'.$table.'` ADD `username` VARCHAR(100) NOT NULL');
                }

                $table = $prefix.'member_menu';
                if (!\Phpcmf\Service::M()->db->fieldExists('site', $table)) {
                    \Phpcmf\Service::M()->query('ALTER TABLE `'.$table.'` ADD `site` TEXT NOT NULL');
                }
/*
                $table = $prefix.'email';
                if (!\Phpcmf\Service::M()->db->tableExists($table)) {
                    \Phpcmf\Service::M()->query('CREATE TABLE IF NOT EXISTS `'.$table.'` (
  `id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `value` text NOT NULL COMMENT \'配置信息\',
  `displayorder` smallint(5) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY (`displayorder`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COMMENT=\'邮件账户表\';');
                }*/

                /*
                // 创建member_notice username字段
                $table = $prefix.'member_notice';
                if (!\Phpcmf\Service::M()->db->fieldExists('username', $table)) {
                    \Phpcmf\Service::M()->query('ALTER TABLE `'.$table.'` ADD `username` VARCHAR(100) NOT NULL');
                }*/
                break;

            case '08':
                // 程序兼容性
                $local = dr_dir_map(APPSPATH, 1); // 搜索本地模块
                foreach ($local as $dir) {
                    if (is_file(APPSPATH.$dir.'/Config/App.php')) {
                        $key = strtolower($dir);
                        $file =  APPSPATH.$dir.'/Controllers/Search.php';
                        if (is_file($file)) {
                            // 替换搜索控制器
                            $code = file_get_contents($file);
                            if (strpos($code, '\Phpcmf\Home\Search') !== false) {
                                file_put_contents($file, str_replace(
                                    ['\Phpcmf\Home\Search', '_Module_Search'],
                                    ['\Phpcmf\Home\Module', '_Search'],
                                    $code
                                ));
                            }
                        }
                    }
                }
                break;

            case '09':
                //
                $local = dr_file_map(WEBPATH, 1); // 搜索根目录
                foreach ($local as $file) {
                    $ext = strtolower(substr(strrchr($file, '.'), 1));
                    if (in_array($ext, ['zip', 'rar', 'sql'])) {
                        $this->halt('文件不安全【/'.$file.'】请及时清理', 0);
                    }
                    $size = file_get_contents(WEBPATH.$file, 0, null, 0, 9286630);
                    if (strlen($size) >= 9286630) {
                        $this->halt('存在大文件文件【/'.$file.'】请及时清理', 0);
                    }
                }
                $this->_json(1,'通过');
                break;

            case '10':

                // 模块数据检测
                $rt = [];
                $module = \Phpcmf\Service::M()->table('module')->getAll();
                if ($module) {
                    foreach ($module as $m) {
                        $site = dr_string2array($m['site']);
                        $mform = \Phpcmf\Service::M()->table('module_form')->where('module', $m['dirname'])->getAll();
                        foreach ($site as $siteid => $s) {
                            $r = $this->_check_table_counts($siteid.'_'.$m['dirname'], $m['dirname'].'模块主表');
                            $r && $rt[] = $r;
                            if ($mform) {
                                foreach ($mform as $mm) {
                                    $r = $this->_check_table_counts($siteid.'_'.$m['dirname'].'_form_'.$mm['table'], $m['dirname'].'模块'.$mm['name'].'表');
                                    $r && $rt[] = $r;
                                }
                            }
                        }
                    }
                }
                if ($rt) {
                    $this->halt(implode('<br>', $rt), 0);
                }

                $this->_json(1,'正常');
                break;

            case '11':

                // 域名检测
                list($module, $data) = \Phpcmf\Service::M('Site')->domain();
                if ($data) {
                    if (!function_exists('stream_context_create')) {
                        $this->halt('函数没有被启用：stream_context_create', 0);
                    }

                    $tips = [];
                    foreach ($data as $name => $domain) {
                        $url = '';
                        if ($name == 'mobile_domain') {
                            if ($domain) {
                                $url = dr_http_prefix($domain) . '/api.php';
                            } else {
                                $tips[] = '当前站点没有绑定手机域名，可能无法使用移动端界面';
                            }
                        } elseif (strpos($name, 'module_') === 0) {
                            // 模块
                            if ($domain) {
                                $url = dr_http_prefix($domain) . '/api.php';
                            }
                        } elseif (strpos($name, 'client_') === 0) {
                            // 终端
                            if ($domain) {
                                $url = dr_http_prefix($domain) . '/api.php';
                            }
                        }

                        if ($url) {
                            $context = stream_context_create(array(
                                'http' => array(
                                    'timeout' => 5 //超时时间，单位为秒
                                )
                            ));
                            $code = file_get_contents($url, 0, $context);
                            if ($code != 'phpcmf ok') {
                                $tips[] = '域名绑定异常，无法访问：' . $url . '，可以尝试手动访问此地址，如果提示phpcmf ok就表示成功';
                            }
                        }
                    }

                    if ($tips) {

                        $this->_json(0,implode('<br>', $tips));
                    }
                }
                $this->_json(1,'通过');

                break;

            case '12':

                $value = @ini_get("max_input_vars");
                if ($value < 3000) {
                    $this->_json(1,$value.'，建议调整到10000');
                } else {
                    $this->_json(1, $value);
                }
                break;

            case '13':

                break;

        }

        $this->_json(1,'完成');
    }

	public function php_index() {
	    phpinfo();
    }


    private function halt($msg, $code) {
        $this->_json($code, $msg);
    }

    private function _check_table_counts($table, $name) {

	    $ptable = \Phpcmf\Service::M()->dbprefix($table);
	    if (!\Phpcmf\Service::M()->db->tableExists($ptable)) {
            return '数据表【'.$name.'/'.$ptable.'】不存在，请创建';
        }
	    $counts = \Phpcmf\Service::M()->table($table)->counts();
	    if ($counts > 100000) {
            return '<font color="green">数据表【'.$name.'/'.$ptable.'】数据量超过10万，会影响加载速度，建议对其进行数据优化</font>';
        }
    }

}
