<?php namespace Phpcmf\Library;


/**
 * http://www.xunruicms.com
 * 本文件是框架系统文件，二次开发时不可以修改本文件，可以通过继承类方法来重写此文件
 **/


/**
 * 存储
 */
class Storage {

    public $ci;

    // 存储对象
    protected $object;

    private function _init($attachment) {

        // 选择存储策略
        if ($attachment['type']) {
            // 云存储
            switch ($attachment['type']) {

                case 1:
                    $this->object = new \Phpcmf\ThirdParty\Storage\Ftp();
                    break;

                case 2:
                    $this->object = new \Phpcmf\ThirdParty\Storage\Oss();
                    break;

                case 3:
                    $this->object = new \Phpcmf\ThirdParty\Storage\Qcloud();
                    break;

                case 4:
                    $this->object = new \Phpcmf\ThirdParty\Storage\Baidu();
                    break;

                case 5:
                    $this->object = new \Phpcmf\ThirdParty\Storage\Qiniu();
                    break;

                default:
                    // 遍历
                    $path = FCPATH.'ThirdParty/Storage/';
                    $local = dr_dir_map($path, 1);
                    foreach ($local as $dir) {
                        if (is_file($path.$dir.'/App.php')) {
                            $cfg = require $path.$dir.'/App.php';
                            if ($cfg['id'] && $cfg['id'] == $attachment['type']) {
                                $newClassName2 = '\\Phpcmf\\ThirdParty\\Storage\\'.ucfirst($dir);
                                $this->object = new $newClassName2;
                            }
                        }
                    }
                    if (!$this->object) {
                        exit(dr_array2string(dr_return_data(0, '云存储类型['.$attachment['type'].']对应的程序不存在')));
                    }
                    break;
            }
        } else {
            // 本地存储
            $this->object = new \Phpcmf\ThirdParty\Storage\Local();
        }

    }

    // 文件上传
    public function upload($type, $data, $file_path, $attachment, $watermarkk) {

        $this->_init($attachment);
        return $this->object->init($attachment, $file_path)->upload($type, $data, $watermarkk);
    }

    // 文件删除
    public function delete($attachment, $filename) {

        $this->_init($attachment);
        return $this->object->init($attachment, $filename)->delete();
    }
}