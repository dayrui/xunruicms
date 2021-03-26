<?php namespace Phpcmf\Model;
/**
 * http://www.xunruicms.com
 * 本文件是框架系统文件，二次开发时不可以修改本文件，可以通过继承类方法来重写此文件
 * 迅睿内容管理框架系统（简称：迅睿CMS）软件著作权登记号：2019SR0854684
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