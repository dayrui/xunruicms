<?php namespace Phpcmf\Library;

/**
 * http://www.xunruicms.com
 * 本文件是框架系统文件，二次开发时不可以修改本文件，可以通过继承类方法来重写此文件
 **/



/**
 * 用户通知处理
 */

class Notice {

    /**
     * 发送通知动作（按用户设置）
     *
     * $name    动作名称
     * $data    传入参数
     */
    public function send_notice($name, $data) {

        if (!\Phpcmf\Service::C()->member_cache['notice'][$name]) {
            return; // 没有配置通知
        }
        // 当前的信息变量
        $data['sys_app'] = defined('MOD_DIR') ? MOD_DIR : APP_DIR;
        $data['sys_uri'] = \Phpcmf\Service::L('router')->uri();
        $data['sys_time'] = SYS_TIME;
        $data['ip_address'] = \Phpcmf\Service::L('input')->ip_address_info();
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
    public function send_notice_user($name, $uid, $data, $config) {

        if (!$config) {
            return; // 没有配置通知
        }

        $data['uid'] = $uid;
        // 当前的信息变量
        $data['sys_time'] = SYS_TIME;
        $data['ip_address'] = \Phpcmf\Service::L('input')->ip_address_info();
        // 加入队列并执行
        $rt = \Phpcmf\Service::M('cron')->add_cron(SITE_ID, 'notice', [
            'name' => $name,
            'data' => $data,
            'config' => $config,
        ]);
        if (!$rt['code']) {
            log_message('error', '通知任务注册失败：'.$rt['msg']);
        }

        return;
    }

    // 来至队列中执行
    public function cron_notice($siteid, $value) {

        $error = [];
        if (!$value['data']['uid']) {
            return [['用户uid参数为空，任务不能执行'], $value];
        }

        // 微信通知
        if ($value['config']['weixin']) {
            $rt = $this->_get_tpl_content($siteid, $value['name'], 'weixin', $value['data']);
            if (!$rt['code']) {
                $error[] = $rt['msg'];
            } else {
                $xml = $this->_xml_array($rt['msg']);
                if (!$xml || !isset($xml['xml']) || !$xml['xml']) {
                    $error[] = 'xml解析失败，检查文件格式是否正确：'.$value['name'].'.html';
                } else {
                    $content = $xml['xml'];
                    $rt = \Phpcmf\Service::M('member')->wexin_template($value['data']['uid'], $content['id'], $content['param'], $content['url']);
                    if (!$rt['code']) {
                        $error[] = '微信消息执行错误：'.$rt['msg'];
                    } else {
                        // 成功
                        unset($value['config']['weixin']);
                    }
                }
            }

        }

        // 短信通知
        if ($value['config']['mobile']) {
            $phone = $value['data']['phone'];
            if (!$phone) {
                $member = dr_member_info($value['data']['uid']);
                $phone = $member['phone'];
            }
            if (!$phone) {
                $error[] = 'phone参数为空，不能发送短信';
            } else {
                $rt = $this->_get_tpl_content($siteid, $value['name'], 'mobile', $value['data']);
                if (!$rt['code']) {
                    $error[] = $rt['msg'];
                } else {
                    $content = $rt['msg'];
                    $rt = \Phpcmf\Service::M('member')->sendsms_text($phone, $content);
                    if (!$rt['code']) {
                        $error[] = '短信通知执行错误：'.$rt['msg'];
                    } else {
                        // 成功
                        unset($value['config']['mobile']);
                    }
                }
            }

        }

        // 站内消息通知
        if ($value['config']['notice']) {
            $rt = $this->_get_tpl_content($siteid, $value['name'], 'mobile', $value['data']);
            if (!$rt['code']) {
                $error[] = $rt['msg'];
            } else {
                $content = $rt['msg'];
                \Phpcmf\Service::M('member')->notice($value['data']['uid'], max((int)$value['data']['type'], 1), $content, $value['data']['url']);
                // 成功
                unset($value['config']['notice']);
            }
        }

        // 邮件通知
        if ($value['config']['email']) {
            $email = $value['data']['email'];
            if (!$email) {
                $member = dr_member_info($value['data']['uid']);
                $email = $member['email'];
            }
            if (!$email) {
                $error[] = 'email参数为空，不能发送邮件';
            } else {
                $rt = $this->_get_tpl_content($siteid, $value['name'], 'email', $value['data']);
                if (!$rt['code']) {
                    $error[] = $rt['msg'];
                } else {
                    $title = '';
                    $content = $rt['msg'];
                    if (preg_match('/<title>(.+)<\/title>/U', $content, $mt)) {
                        $title = $mt[1];
                        $content = str_replace($mt[0], '', $content);
                    }
                    $rt = \Phpcmf\Service::M('member')->sendmail($email, $title ? $title : '通知', $content);
                    if (!$rt['code']) {
                        $error[] = '邮件发送失败：'.$rt['msg'];
                    } else {
                        // 成功
                        unset($value['config']['email']);
                    }
                }

            }
        }

        return [$error, $error ? $value : []];
    }

    // 获取通知模板内容
    private function _get_tpl_content($siteid, $name, $type, $data) {


        if ($siteid > 1) {
            $my = \Phpcmf\Service::L('html')->get_webpath($siteid, 'site', 'config/notice/'.$type.'/'.$name.'.html');
            $my = is_file($my) ? file_get_contents($my) : '';
        } else {
            $my = '';
        }

        $content = $my ? $my : file_get_contents(ROOTPATH.'config/notice/'.$type.'/'.$name.'.html');
        if (!$content) {
            return dr_return_data(0, '模板不存在【config/notice/'.$type.'/'.$name.'.html】');
        }

        ob_start();
        extract($data, EXTR_PREFIX_SAME, 'data');
        $file = \Phpcmf\Service::V()->code2php($content);
        require $file;
        $code = ob_get_clean();

        return dr_return_data(1, $code);
    }

    // xml转换数组
    private function _xml_array($xml) {

        $reg = "/<(\\w+)[^>]*?>(.*?)<\\/\\1>/Us";
        if(preg_match_all($reg, $xml, $matches))
        {
            $count = dr_count($matches[0]);
            $arr = array();
            for($i = 0; $i < $count; $i++)
            {
                $key = $matches[1][$i];
                $val = $this->_xml_array( $matches[2][$i] );  // 递归
                if(array_key_exists($key, $arr))
                {
                    if(is_array($arr[$key]))
                    {
                        if(!array_key_exists(0,$arr[$key]))
                        {
                            $arr[$key] = array($arr[$key]);
                        }
                    }else{
                        $arr[$key] = array($arr[$key]);
                    }
                    $arr[$key][] = $val;
                }else{
                    $arr[$key] = $val;
                }
            }
            return $arr;
        }else{
            return $xml;
        }

    }

}

