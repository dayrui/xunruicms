<?php namespace Phpcmf\Model;

/**
 * http://www.xunruicms.com
 * 本文件是框架系统文件，二次开发时不可以修改本文件，可以通过继承类方法来重写此文件
 **/


class Email extends \Phpcmf\Model
{


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