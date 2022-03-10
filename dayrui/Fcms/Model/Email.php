<?php namespace Phpcmf\Model;
/**
 * {{www.xunruicms.com}}
 * {{迅睿内容管理框架系统}}
 * 本文件是框架系统文件，二次开发时不可以修改本文件，可以通过继承类方法来重写此文件
 **/

class Email extends \Phpcmf\Model {

    protected $site_name;

    /**
     * 发件人名称
     */
    public function site_name($siteid) {
        if (isset(\Phpcmf\Service::C()->site_info[$siteid]['SITE_NAME'])) {
            $this->site_name = \Phpcmf\Service::C()->site_info[$siteid]['SITE_NAME'];
        }
        return $this;
    }

    /**
     * 邮件发送
     */
    public function sendmail($tomail, $subject, $msg, $data = []) {

        if (!$tomail) {
            return dr_return_data(0, dr_lang('第一个参数不能为空'));
        } elseif (!$subject) {
            return dr_return_data(0, dr_lang('第二个参数不能为空'));
        } elseif (!$msg) {
            return dr_return_data(0, dr_lang('第三个参数不能为空'));
        }

        $cache = \Phpcmf\Service::L('cache')->get('email');
        if (!$cache) {
            return dr_return_data(0, dr_lang('无邮件smtp配置'));
        }

        $content = $msg;
        if (strlen($msg) <= 30 && trim(strtolower(strrchr($msg, '.')), '.') == 'html') {
            $my = WEBPATH.'config/notice/email/'.$msg;
            $default = CONFIGPATH.'notice/email/'.$msg;
            $content = is_file($my) ? file_get_contents($my) : file_get_contents($default);
            if (!$content) {
                log_message('error', '邮件模板不存在：'.$msg);
                return dr_return_data(0, dr_lang('邮件模板[#%s]不存在', $msg));
            }
            ob_start();
            extract($data, EXTR_PREFIX_SAME, 'data');
            $file = \Phpcmf\Service::V()->code2php($content);
            require $file;
            $content = ob_get_clean();
        }

        $dmail = \Phpcmf\Service::L('email');
        foreach ($cache as $data) {
            $dmail->set(array(
                'host' => trim($data['host']),
                'user' => trim($data['user']),
                'pass' => trim($data['pass']),
                'port' => $data['port'],
                'from' => $data['user'],
            ));
            if ($dmail->send($tomail, $subject, $content, $this->site_name)) {
                return dr_return_data(1, 'ok');
            }
        }

        return dr_return_data(0, '邮件发送失败：'.$dmail->error);
    }

    // 缓存
    public function cache($site = SITE_ID) {

        $data = $this->table('mail_smtp')->order_by('displayorder asc')->getAll();
        $cache = [];
        if ($data) {
            foreach ($data as $t) {
                $cache[$t['id']] = $t;
            }
        }

        \Phpcmf\Service::L('cache')->set_file('email', $cache);
    }

}