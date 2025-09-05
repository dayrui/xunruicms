<?php namespace Phpcmf\Library;
/**
 * {{www.xunruicms.com}}
 * {{迅睿内容管理框架系统}}
 * 本文件是框架系统文件，二次开发时不可以修改本文件，可以通过继承类方法来重写此文件
 **/

/**
 * 用户通知处理
 */

class Notice {



    /**
     * 通知类型
     */
    public function get_type() {

        $notice_type = [
            'mobile' => [
                'file' => 'mobile',
                'name' => dr_lang('短信'),
                'help' => 'javascript:dr_help(479);',
                'height' => '100',
            ],
            'email' => [
                'file' => 'email',
                'name' => dr_lang('邮件'),
                'help' => 'javascript:dr_help(480);',
                'height' => '200',
            ]
        ];
        $local = \Phpcmf\Service::Apps();
        foreach ($local as $dir => $path) {
            if (is_file($path.'install.lock') && is_file($path.'/Config/App.php') ) {
                if (is_file($path.'/Config/Notice_type.php')) {
                    $cfg = require $path.'/Config/Notice_type.php';
                    $cfg && $notice_type[strtolower($dir)] = [
                        'name' => $cfg['name'],
                        'file' => $cfg['file'],
                        'help' => $cfg['help'],
                        'class' => $cfg['class'],
                        'height' => $cfg['height'],
                    ];
                }
            }
        }

        return $notice_type;
    }

    // 获取一些默认参数
    protected function _get_data($data, $param = []) {

        // 当前的信息变量
        $data['sys_app'] = defined('MOD_DIR') ? MOD_DIR : APP_DIR;
        $data['sys_uri'] = \Phpcmf\Service::L('router')->uri();
        $data['sys_time'] = SYS_TIME;
        $data['sys_ip'] = \Phpcmf\Service::L('input')->ip_address();
        $data['sys_ip_address'] = $data['ip_address'] = \Phpcmf\Service::L('input')->ip_address_info();
        // 自定义参数累加进去
        if ($param) {
            $data = array_merge($data, $param);
        }

        return $data;
    }

    /**
     * 发送通知动作
     *
     * $type    通知类型
     * $name    动作名称
     * $data    传入参数
     * $value   传入参数
     */
    public function send($type, $name, $data, $config, $is_send = 0) {

        $data = $this->_get_data($data);

        // 加入队列并执行
        $rt = \Phpcmf\Service::M('cron')->add_cron(SITE_ID, $type, [
            'name' => $name,
            'data' => $data,
            'config' => $config,
        ]);
        if (!$rt['code']) {
            log_message('error', '通知任务注册失败：'.$rt['msg']);
        }

        // 立即发送
        if ($is_send) {
            \Phpcmf\Service::M('cron')->do_cron_id($rt['code']);
        }

        return;
    }

    /**
     * 发送通知动作（按用户设置）
     *
     * $name    动作名称
     * $data    传入参数
     */
    public function send_notice($name, $data, $param = []) {

        if (!\Phpcmf\Service::C()->member_cache['notice'][$name]) {
            return; // 没有配置通知
        }

        $data = $this->_get_data($data, $param);

        // 加入队列并执行
        $rt = \Phpcmf\Service::M('cron')->add_cron(SITE_ID, 'notice', [
            'name' => $name,
            'data' => $data,
            'config' => \Phpcmf\Service::C()->member_cache['notice'][$name],
        ]);
        if (!$rt['code']) {
            log_message('error', '通知任务注册失败：'.$rt['msg']);
        }

        return;
    }

    /**
     * 发送通知动作（按自定义位置设置）
     *
     * $name    动作名称
     * $data    传入参数
     */
    public function send_notice_user($name, $uid, $data, $config, $is_send = 0) {

        if (!$config) {
            return; // 没有配置通知
        }

        $data = $this->_get_data($data, [
            'uid' => $uid
        ]);
        // 加入队列并执行
        $rt = \Phpcmf\Service::M('cron')->add_cron(SITE_ID, 'notice', [
            'name' => $name,
            'data' => $data,
            'config' => $config,
        ]);
        if (!$rt['code']) {
            log_message('error', '通知任务注册失败：'.$rt['msg']);
        }
        // 立即发送
        if ($is_send) {
            \Phpcmf\Service::M('cron')->do_cron_id($rt['code']);
        }

        return $rt;
    }

    // 来至队列中执行
    public function cron_email($email, $siteid, $value) {
        
        if (!$email) {
            $debug = 'email参数为空，不能发送邮件';
            $error = [
                $debug
            ];
            CI_DEBUG && log_message('debug', '通知任务（'.$value['name'].'）邮件执行失败：'.$debug);
        } else {
            $error = $this->_send_email($email, $siteid, $value);
        }

        return [$error, $error ? $value : []];
    }

    // 来至队列中执行
    public function cron_notice($siteid, $value) {

        \Phpcmf\Service::M()->siteid = $siteid;

        $error = [];
        if (!$value['data']['uid']) {
            CI_DEBUG && log_message('debug', '通知任务（'.$value['name'].'）执行失败：用户uid参数为空，任务不能执行');
            return [['用户uid参数为空，任务不能执行'], $value];
        }

        $member = dr_member_info($value['data']['uid']);
        if (!$member) {
            CI_DEBUG && log_message('debug', '通知任务（'.$value['name'].'）执行失败：用户uid('.$value['data']['uid'].')账号不存在，任务不能执行');
            return [['用户uid('.$value['data']['uid'].')账号不存在，任务不能执行'], $value];
        }

        $notice_type = $this->get_type();
        foreach ($notice_type as $nid => $tt) {
            if (isset($value['config'][$nid]) && $value['config'][$nid]) {
                if ($nid == 'mobile') {
                    // 短信通知
                    $phone = $member['phone'];
                    if (!$phone) {
                        $error[] = $debug = '用户【'.$member['username'].'】phone参数为空，不能发送短信';
                        CI_DEBUG && log_message('debug', '通知任务（'.$value['name'].'）短信执行失败：'.$debug);
                    } else {
                        $content = [];
                        if (isset($value['config']['mobile']['tpl_content']) && ($value['config']['mobile']['tpl_content'])) {
                            $content = $value['config']['mobile']['tpl_content'];
                            if (!$content) {
                                $error[] = $debug = '自定义通知内容参数tpl_content不存在';
                                CI_DEBUG && log_message('debug', '通知任务（'.$value['name'].'）短信执行失败：'.$debug);
                            }
                        } else {
                            $rt = $this->_get_tpl_content($siteid, $value['name'], 'mobile', $value['data']);
                            if (!$rt['code']) {
                                $error[] = $debug = $rt['msg'];
                                CI_DEBUG && log_message('debug', '通知任务（'.$value['name'].'）短信执行失败：'.$debug);
                            } else {
                                $content = $rt['msg'];
                            }
                        }
                        if ($content) {
                            $rt = \Phpcmf\Service::M('member')->sendsms_text($phone, $content);
                            if (!$rt['code']) {
                                $error[] = $debug = '短信通知执行错误：'.$rt['msg'];
                                CI_DEBUG && log_message('debug', '通知任务（'.$value['name'].'）短信执行失败：'.$debug);
                            } else {
                                // 成功
                                unset($value['config']['mobile']);
                            }
                        }
                    }
                } elseif ($nid == 'email') {
                    // 邮件通知
                    $email = $member['email'];
                    if (!$email) {
                        $error[] = $debug = '用户【'.$member['username'].'】的email参数为空，不能发送邮件';
                        CI_DEBUG && log_message('debug', '通知任务（'.$value['name'].'）邮件执行失败：'.$debug);
                    } else {
                        $err = $this->_send_email($email, $siteid, $value);
                        if ($err) {
                            $error = dr_array2array($err, $error);
                        }
                    }
                } else {
                    //其他
                    $class = isset($tt['class']) && $tt['class'] ? $tt['class'] : 'notice';
                    $classFile = dr_get_app_dir($nid).'Models/'.ucfirst($class).'.php';
                    if (!is_file($classFile)) {
                        CI_DEBUG && log_message('debug', '通知对象('.$nid.')类文件（'.$classFile.'）不存在');
                        return;
                    }
                    $error = \Phpcmf\Service::M($class, $nid)->send_notice($value, $siteid, $error);
                }
            }
        }


        return [$error, $error ? $value : []];
    }

    protected function _send_email($email, $siteid, $value) {

        $title = '';
        $error = [];
        $content = [];
        if (isset($value['config']['email']['tpl_content']) && ($value['config']['email']['tpl_content'])) {
            $content = $value['config']['email']['tpl_content'];
            if (!$content) {
                $error[] = $debug = '自定义通知内容参数tpl_content不存在';
                CI_DEBUG && log_message('debug', '通知任务（'.$value['name'].'）邮件执行失败：'.$debug);
            }
        } else {
            $rt = $this->_get_tpl_content($siteid, $value['name'], 'email', $value['data']);
            if (!$rt['code']) {
                $error[] = $debug = $rt['msg'];
                CI_DEBUG && log_message('debug', '通知任务（'.$value['name'].'）邮件执行失败：'.$debug);
            } else {
                $content = $rt['msg'];
            }
        }
        if ($content) {
            if (preg_match('/<title>(.+)<\/title>/U', $content, $mt)) {
                $title = $mt[1];
                $content = str_replace($mt[0], '', $content);
            }
            $rt = \Phpcmf\Service::M('email')->site_name($siteid)->sendmail($email, $title ? $title : '通知', $content);
            if (!$rt['code']) {
                $error[] = $debug = '邮件发送失败：'.$rt['msg'];
                CI_DEBUG && log_message('debug', '通知任务（'.$value['name'].'）邮件执行失败：'.$debug);
            } else {
                // 成功
                unset($value['config']['email']);
            }
        }

        return $error;
    }

    // 获取通知模板内容
    public function _get_tpl_content($siteid, $name, $type, $data) {

        /*
        if ($siteid > 1) {
            $my = \Phpcmf\Service::L('html')->get_webpath($siteid, 'site', 'config/notice/'.$type.'/'.$name.'.html');
            $my = is_file($my) ? file_get_contents($my) : '';
        } else {
            $my = '';
        }*/

        $my = \Phpcmf\Service::L('html')->get_webpath($siteid, 'site', 'config/notice/'.$type.'/'.$name.'.html');
        if (is_file($my)) {
            $content_code = file_get_contents($my);
        } else {
            $my = CONFIGPATH.'notice/'.$type.'/'.$name.'.html';
            $content_code = file_get_contents($my);
        }

        if (!$content_code) {
            return dr_return_data(0, '模板不存在【'.$my.'】');
        }

        // 替换多站点变量
        if (isset(\Phpcmf\Service::C()->site_info[$siteid]) && \Phpcmf\Service::C()->site_info[$siteid]) {
            $site_info = \Phpcmf\Service::C()->site_info[$siteid];
            $content_code = str_replace('{SITE_NAME}', $site_info['SITE_NAME'], $content_code);
            $content_code = str_replace('{SITE_URL}', $site_info['SITE_URL'], $content_code);
        }

        ob_start();
        extract($data, EXTR_OVERWRITE);
        $require_file = \Phpcmf\Service::V()->code2php($content_code);
        require $require_file;
        $code = ob_get_clean();

        return dr_return_data(1, $code);
    }

    // xml转换数组
    public function _xml_array($xml) {

        $reg = "/<(\\w+)[^>]*?>(.*?)<\\/\\1>/Us";
        if (preg_match_all($reg, $xml, $matches)) {
            $count = dr_count($matches[0]);
            $arr = array();
            for($i = 0; $i < $count; $i++) {
                $key = $matches[1][$i];
                $val = $this->_xml_array( $matches[2][$i] );  // 递归
                if (array_key_exists($key, $arr)) {
                    if (is_array($arr[$key])) {
                        if(!array_key_exists(0,$arr[$key]))
                        {
                            $arr[$key] = array($arr[$key]);
                        }
                    } else {
                        $arr[$key] = array($arr[$key]);
                    }
                    $arr[$key][] = $val;
                } else {
                    $arr[$key] = $val;
                }
            }
            return $arr;
        }else{
            return $xml;
        }
    }

}

