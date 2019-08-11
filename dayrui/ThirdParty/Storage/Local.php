<?php namespace Phpcmf\ThirdParty\Storage;

// 本地文件存储
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

    // 初始化参数
    public function init($attachment, $filename) {

        if ($attachment['value']['path'] == 'null') {
            // 表示自定义save_path
            $attachment['value']['path'] = '';
            $this->filename = $filename;
            $this->filepath = dirname($filename);
        } else {
            $this->filename = trim($filename, DIRECTORY_SEPARATOR);
            $this->filepath = dirname($filename);
            $this->filepath == '.' && $this->filepath = '';
            $attachment['value']['path'] = rtrim($attachment['value']['path'], DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;
        }
        $this->attachment = $attachment;
        $this->fullpath = $this->attachment['value']['path'].$this->filepath;
        $this->fullname = $this->attachment['value']['path'].$this->filename;

        return $this;
    }

    // 文件上传模式
    public function upload($type = 0, $data, $watermark) {

        $this->data = $data;
        $this->watermark = $watermark;

        // 目录不存在先创建它
        !is_dir($this->fullpath) && dr_mkdirs($this->fullpath);
        if (!is_dir($this->fullpath)) {
            log_message('error', '目录创建失败：'.$this->fullpath);
            return dr_return_data(0, dr_lang('创建目录%s失败', IS_ADMIN ? $this->fullpath : ''));
        }

        if ($type) {
            // 移动失败
            if (!(move_uploaded_file($this->data, $this->fullname) || !is_file($this->fullname))) {
                return dr_return_data(0, dr_lang('文件移动失败'));
            }
        } else {
            $filesize = file_put_contents($this->fullname, $this->data);
            if (!$filesize || !is_file($this->fullname)) {
                log_message('error', '文件创建失败：'.$this->fullname);
                return dr_return_data(0, dr_lang('文件创建失败'));
            }
        }

        // 强制水印
        if ($this->watermark && ($config = \Phpcmf\Service::C()->get_cache('site', SITE_ID, 'watermark'))) {
            $config['source_image'] = $this->fullname;
            $config['dynamic_output'] = false;
            \Phpcmf\Service::L('image')->watermark($config);
        }

        // 上传成功
        return dr_return_data(1, 'ok', [
            'url' => $this->attachment['url'].$this->filename,
            'md5' => md5_file($this->fullname),
            'size' => $filesize,
        ]);
    }

    // 删除文件
    public function delete() {
        @unlink($this->fullname);
        //log_message('info', 'CSRF token verified');
    }

}