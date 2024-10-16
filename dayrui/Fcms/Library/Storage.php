<?php namespace Phpcmf\Library;
/**
 * {{www.xunruicms.com}}
 * {{迅睿内容管理框架系统}}
 * 本文件是框架系统文件，二次开发时不可以修改本文件，可以通过继承类方法来重写此文件
 **/


/**
 * 存储工厂类
 */
class Storage {

    // 存储对象
    protected $object;

    private function _init($attachment) {

        // 选择存储策略
        if ($attachment['type']) {
            // 云存储
            $path = FCPATH.'ThirdParty/Storage/';
            $local = dr_dir_map($path, 1);
            if ($local) {
                foreach ($local as $dir) {
                    if (is_file($path.$dir.'/App.php')) {
                        $cfg = require $path.$dir.'/App.php';
                        if ($cfg['id'] && $cfg['id'] == $attachment['type']) {
                            $newClassName2 = '\\Phpcmf\\ThirdParty\\Storage\\'.ucfirst($dir);
                            $this->object = new $newClassName2;
                        }
                    }
                }
            }
            if (!$this->object) {
                exit(dr_array2string(dr_return_data(0, '存储类型['.$attachment['type'].']对应的程序不存在')));
            }
        } else {
            // 本地存储
            $this->object = new \Phpcmf\Library\Local();
        }
    }

    // 文件上传入口
    public function upload($type, $data, $file_path, $attachment, $watermark) {

        $this->_init($attachment);
        return $this->object->init($attachment, $file_path)->upload($type, $data, $watermark);
    }

    // 文件删除入口
    public function delete($attachment, $filename) {

        $this->_init($attachment);
        return $this->object->init($attachment, $filename)->delete();
    }

    // 文件上传到本地目录
    public function uploadfile($type, $data, $fullname, $watermark, $attachment) {

        if ($type) {
            // 移动失败
            if (!(dr_move_uploaded_file($data, $fullname) || !is_file($fullname))) {
                return dr_return_data(0, dr_lang('文件移动失败'));
            }
            $filesize = filesize($fullname);
        } else {
            $filesize = file_put_contents($fullname, $data);
        }

        if (!$filesize || !is_file($fullname)) {
            log_message('error', '文件创建失败：'.$fullname);
            return dr_return_data(0, dr_lang('文件创建失败'));
        }

        $info = [];
        // 图片处理 (严格模式下)
        if (dr_is_image($fullname)) {
            // 获取图片尺寸
            if (defined('SYS_ATTACHMENT_SAFE') && !SYS_ATTACHMENT_SAFE) {
                $img = getimagesize($fullname);
                if (!$img) {
                    // 删除文件
                    unlink($fullname);
                    return dr_return_data(0, dr_lang('此图片不是一张可用的图片'));
                }
            } else {
                $img = [0, 0];
            }
            // 图片压缩处理
            if ($attachment['image_reduce']) {
                // 处理图片大小是否溢出内存
                if ($img[0] && \Phpcmf\Service::L('image')->memory_limit($img)) {
                    CI_DEBUG && log_message('debug', '图片['.$fullname.']分辨率太大导致服务器内存溢出，无法进行压缩处理，已按原图存储');
                } else {
                    \Phpcmf\Service::L('image')->reduce($fullname, $attachment['image_reduce']);
                }
            }
            // 强制水印
            if ($watermark && ($config = \Phpcmf\Service::C()->get_cache('site', SITE_ID, 'watermark'))) {
                // 处理图片大小是否溢出内存
                if ($img[0] && \Phpcmf\Service::L('image')->memory_limit($img)) {
                    CI_DEBUG && log_message('debug', '图片['.$fullname.']分辨率太大导致服务器内存溢出，无法进行压缩处理，已按原图存储');
                } else {
                    $config['source_image'] = $fullname;
                    $config['dynamic_output'] = false;
                    \Phpcmf\Service::L('image')->watermark($config);
                }
            }
            $info = [
                'width' => $img[0],
                'height' => $img[1],
            ];
        }

        return dr_return_data(1, $filesize, $info);
    }
}


/**
 * 本地文件存储
 */
class Local {

    // 存储内容
    protected $data;

    // 文件存储路径
    protected $filename;

    // 文件存储目录
    protected $filepath;

    // 附件存储的信息
    protected $attachment;

    // 是否进行图片水印
    protected $watermark;

    // 完整的文件目录
    protected $fullpath;

    // 完整的文件路径
    protected $fullname;

    // 是否指定路径
    protected $is_diy_save_path = 0;

    // 初始化参数
    public function init($attachment, $filename) {

        if ($attachment['value']['path'] == 'null') {
            // 表示自定义save_path
            $attachment['value']['path'] = '';
            $this->filename = $filename;
            $this->filepath = dirname($filename);
            $this->is_diy_save_path = 1;
        } else {
            $this->filename = trim($filename, DIRECTORY_SEPARATOR);
            $this->filepath = dirname($filename);
            $this->filepath == '.' && $this->filepath = '';
            $this->is_diy_save_path = 0;
            if (is_dir(SYS_UPLOAD_PATH.$attachment['value']['path'])) {
                // 相对路径
                $attachment['value']['path'] = SYS_UPLOAD_PATH.$attachment['value']['path'];
            }
        }

        $this->attachment = $attachment;
        $this->fullpath = $this->attachment['value']['path'].$this->filepath;
        $this->fullname = $this->attachment['value']['path'].$this->filename;

        return $this;
    }

    // 文件上传模式
    public function upload($type, $data, $watermark) {

        $this->data = $data;
        $this->watermark = $watermark;

        // 目录不存在先创建它
        !is_dir($this->fullpath) && dr_mkdirs($this->fullpath);
        if (!is_dir($this->fullpath)) {
            log_message('error', '目录创建失败：'.$this->fullpath);
            return dr_return_data(0, dr_lang('创建目录%s失败', IS_ADMIN ? $this->fullpath : ''));
        }

        $storage = new \Phpcmf\Library\Storage();
        $rt = $storage->uploadfile($type, $this->data, $this->fullname, $watermark, $this->attachment);
        if (!$rt['code']) {
            return $rt;
        }
        // 上传成功
        return dr_return_data(1, 'ok', [
            'url' => $this->is_diy_save_path ? '指定存储路径时无法获取到访问URL地址' : $this->attachment['url'].$this->filename,
            'md5' => md5_file($this->fullname),
            'size' => $rt['msg'],
            'info' => $rt['data']
        ]);
    }

    // 删除文件
    public function delete() {
        @unlink($this->fullname);
    }

}