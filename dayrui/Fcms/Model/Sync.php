<?php namespace Phpcmf\Model;
/**
 * {{www.xunruicms.com}}
 * {{迅睿内容管理框架系统}}
 * 本文件是框架系统文件，二次开发时不可以修改本文件，可以通过继承类方法来重写此文件
 **/


// 同步删除
class Sync extends \Phpcmf\Model {

    protected $sync = [
        'delete_member' => [],
        'delete_content' => [],
    ];

    public function __construct(...$params) {
        parent::__construct(...$params);

        $local = \Phpcmf\Service::Apps();
        foreach ($local as  $dir => $path) {
            if (is_file($path.'Config/Sync.php')) {
                $sync = require $path.'Config/Sync.php';
                foreach ($sync as $tid => $t) {
                    if ($t) {
                        if (!$this->sync[$tid][$dir]) {
                            $this->sync[$tid][$dir] = $t;
                        } else {
                            $this->sync[$tid][$dir] = array_merge($this->sync[$tid][$dir], $t);
                        }
                    }
                }
            }
        }

    }

    /**
     * 删除用户时的联动
     */
    public function delete_member($id) {

        if (!$id) {
            return;
        }

        if (!$this->sync['delete_member']) {
            return;
        }

        foreach ($this->sync['delete_member'] as $dir => $list) {
            foreach ($list as $model => $method) {
                \Phpcmf\Service::M($model, $dir)->$method($id);
            }
        }

    }

    /**
     * 删除模块内容时的联动
     */
    public function delete_content($id, $siteid, $dirname) {

        if (!$id) {
            return;
        }

        if (!$this->sync['delete_content']) {
            return;
        }

        foreach ($this->sync['delete_content'] as $dir => $list) {
            foreach ($list as $model => $method) {
                \Phpcmf\Service::M($model, $dir)->$method($id, $siteid, $dirname);
            }
        }

    }

}