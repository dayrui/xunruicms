<?php namespace Phpcmf\Library;
/**
 * {{www.xunruicms.com}}
 * {{迅睿内容管理框架系统}}
 * 本文件是框架系统文件，二次开发时不可以修改本文件，可以通过继承类方法来重写此文件
 **/

/**
 * 附件类
 */
class Upload {

    protected $error;
    protected $notallowed;
    protected $down_file_ext;

    /**
     * 构造函数
     */
    public function __construct() {
        // 返回错误信息
        $this->error = [
            "SUCCESS",
            dr_lang("文件大小超出 upload_max_filesize 限制"),
            dr_lang("文件大小超出 MAX_FILE_SIZE 限制"),
            dr_lang("文件未被完整上传"),
            dr_lang("没有文件被上传"),
            dr_lang("上传文件为空"),
            "ERROR_TMP_FILE" => dr_lang("临时文件错误"),
            "ERROR_TMP_FILE_NOT_FOUND" => dr_lang("找不到临时文件"),
            "ERROR_SIZE_EXCEED" => dr_lang("文件大小超出网站限制"),
            "ERROR_TYPE_NOT_ALLOWED" => dr_lang("文件类型不允许"),
            "ERROR_SYSTEM_TYPE_NOT_ALLOWED" => dr_lang("文件类型被系统禁止上传"),
            "ERROR_CREATE_DIR" => dr_lang("目录创建失败"),
            "ERROR_DIR_NOT_WRITEABLE" => dr_lang("目录没有写权限"),
            "ERROR_FILE_MOVE" => dr_lang("文件保存时出错"),
            "ERROR_FILE_NOT_FOUND" => dr_lang("找不到上传文件"),
            "ERROR_WRITE_CONTENT" => dr_lang("写入文件内容错误"),
            "ERROR_UNKNOWN" => dr_lang("未知错误"),
            "ERROR_DEAD_LINK" => dr_lang("链接不可用"),
            "ERROR_HTTP_LINK" => dr_lang("链接不是http链接"),
            "ERROR_ATTACH_TYPE" => dr_lang("未知的存储类型"),
            "ERROR_HTTP_CONTENTTYPE" => dr_lang("链接contentType不正确")
        ];
        // 禁止以下文件上传
        $this->notallowed = ['php', 'php3', 'asp', 'jsp', 'jspx', 'aspx', 'exe', 'sh', 'phtml', 'php5', 'pht'];
        // 下载文件扩展名白名单
        $this->down_file_ext = ['jpg', 'jpeg', 'gif', 'png', 'webp', 'zip', 'rar'];
        // 自定义上传扩展名和白名单文件
        if (is_file(WEBPATH.'config/fileext.php')) {
            require WEBPATH.'config/fileext.php';
        } elseif (is_file(CONFIGPATH.'fileext.php')) {
            require CONFIGPATH.'fileext.php';
        }
    }

    // 安全验证
    public function _safe_check($file_ext, $data, $is_ext = 1) {

        // 检查系统保留文件格式
        if ($is_ext) {
            if (in_array($file_ext, $this->notallowed)) {
                return dr_return_data(0, $this->error['ERROR_SYSTEM_TYPE_NOT_ALLOWED']);
            } elseif (!$file_ext) {
                return dr_return_data(0, dr_lang('无法读取文件扩展名'));
            }
        }

        // 验证扩展名格式
        if (!preg_match('/^[a-z0-9]+$/i', $file_ext)) {
            return dr_return_data(0, dr_lang('此文件扩展名[%s]不安全，禁止上传', $file_ext));
        }

        // 是否进行严格验证
        if (defined('SYS_ATTACHMENT_SAFE') && SYS_ATTACHMENT_SAFE) {
            return dr_return_data(1, 'ok');
        }

        // 验证伪装图片
        if (in_array($file_ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'])) {
            $data = strlen($data) < 50 && @is_file($data) ? file_get_contents($data) : strtolower($data);
            if (strlen($data) < 50) {
                return dr_return_data(0, dr_lang('图片文件不规范'). (IS_DEV ? '后台-系统-附件设置-可选择附件验证为宽松模式' : ''));
            } elseif (strpos($data, '<?php') !== false) {
                return dr_return_data(0, dr_lang('此图片不安全，禁止上传'). (IS_DEV ? '后台-系统-附件设置-可选择附件验证为宽松模式' : ''));
            } elseif (strpos($data, 'eval(') !== false) {
                return dr_return_data(0, dr_lang('此图片不安全，禁止上传'). (IS_DEV ? '后台-系统-附件设置-可选择附件验证为宽松模式' : ''));
            } elseif (strpos($data, '.php') !== false) {
                return dr_return_data(0, dr_lang('此图片不安全，禁止上传'). (IS_DEV ? '后台-系统-附件设置-可选择附件验证为宽松模式' : ''));
            } elseif (strpos($data, 'base64_decode(') !== false) {
                return dr_return_data(0, dr_lang('此图片不安全，禁止上传'). (IS_DEV ? '后台-系统-附件设置-可选择附件验证为宽松模式' : ''));
            } elseif (strpos($data, '<script') !== false) {
                return dr_return_data(0, dr_lang('此图片不安全，禁止上传'). (IS_DEV ? '后台-系统-附件设置-可选择附件验证为宽松模式' : ''));
            }
        }

        return dr_return_data(1, 'ok');
    }

    /**
     * 上传文件
     */
    public function upload_file($config) {

        $file = isset($_FILES[$config['form_name']]) ? $_FILES[$config['form_name']] : null;

        if (!$file) {
            return dr_return_data(0, $this->error['ERROR_TMP_FILE_NOT_FOUND']);
        } else if (isset($file['error']) && $file['error']) {
            return dr_return_data(0, $this->_error_msg($file['error']));
        } else if (!file_exists($file['tmp_name'])) {
            return dr_return_data(0, $this->error['ERROR_TMP_FILE_NOT_FOUND']);
        } else if (!is_uploaded_file($file['tmp_name'])) {
            return dr_return_data(0, $this->error['ERROR_TMPFILE']);
        }

        $file_ext = $this->_file_ext($file['name']); // 扩展名
        $file_name = $this->_file_name($file['name']); // 文件实际名字

        // 安全验证
        $rt = $this->_safe_check($file_ext, $file["tmp_name"]);
        if (!$rt['code']) {
            return dr_return_data(0, $rt['msg']);
        }

        if (!$config['file_size']) {
            return dr_return_data(0, dr_lang('系统没有设置可上传的文件大小'));
        } elseif ($file['size'] > $config['file_size']) {
            // 文件大小限制
            return dr_return_data(0, $this->error['ERROR_SIZE_EXCEED']. ' '.($config['file_size']/1024/1024).'MB');
        } elseif ($config['file_exts'][0] != '*' && !in_array($file_ext, $config['file_exts'])) {
            // 检查是文件格式
            return dr_return_data(0, $this->error['ERROR_TYPE_NOT_ALLOWED'] . $file_ext);
        }

        // 保存目录名称
        list($file_path, $config, $diy) = $this->_rand_save_file_path($config, $file_ext, $file);

        // 开始上传存储文件
        $rt = $this->save_file('upload', $file["tmp_name"], $file_path, $config['attachment'], (int)$config['watermark']);
        if (!$rt['code']) {
            return dr_return_data(0, $rt['msg']);
        }
        if (isset($rt['fixpath']) && $rt['fixpath']) {
            // 是否返回新路径
            $file_path = $rt['fixpath'];
        }

        if ($diy) {
            $url = '自定义存储地址不提供URL';
        } else {
            $url = $config['attachment']['url'].$file_path;
        }

        return dr_return_data(1, 'ok', [
            'ext' => $file_ext,
            'url' => $url,
            'md5' => $rt['data']['md5'],
            'file' => $file_path,
            'size' => isset($rt['data']['size']) && $rt['data']['size'] ? $rt['data']['size'] : $file['size'],
            'path' => ($config['attachment']['value']['path'] && $config['attachment']['value']['path'] != 'null' ? $config['attachment']['value']['path'] : '').$file_path,
            'name' => $file_name,
            'info' => $rt['data']['info'],
            'remote' => $config['attachment']['id'],
        ]);
    }

    /**
     * 更新文件
     */
    public function update_file($config) {

        $file = isset($_FILES[$config['form_name']]) ? $_FILES[$config['form_name']] : null;

        if (!$file) {
            return dr_return_data(0, $this->error['ERROR_TMP_FILE_NOT_FOUND']);
        } else if (!file_exists($file['tmp_name'])) {
            return dr_return_data(0, $this->error['ERROR_TMP_FILE_NOT_FOUND']);
        } else if (!is_uploaded_file($file['tmp_name'])) {
            return dr_return_data(0, $this->error['ERROR_TMPFILE']);
        } else if (isset($file['error']) && $file['error']) {
            return dr_return_data(0, $this->_error_msg($file['error']));
        }

        $file_ext = $this->_file_ext($file['name']); // 扩展名
        // 安全验证
        $rt = $this->_safe_check($file_ext, $file["tmp_name"]);
        if (!$rt['code']) {
            return dr_return_data(0, $rt['msg']);
        }

        // 检查是文件格式
        if ($config['file_exts'][0] != '*' && !in_array($file_ext, $config['file_exts'])) {
            return dr_return_data(0, $this->error['ERROR_TYPE_NOT_ALLOWED'] . $file_ext);
        }

        if (!(move_uploaded_file($file["tmp_name"], $config['file_name']) || !is_file($config['file_name']))) {
            return dr_return_data(0, $this->error['ERROR_FILE_MOVE']);
        }

        return dr_return_data(1, 'ok');
    }

    /**
     * 下载文件
     */
    public function down_file($config) {

        /*
        $client = \Config\Services::curlrequest();
        $res = $client->get($config['url'], [
            'timeout' => (int)$config['timeout'],
        ]);
        if ($res->getStatusCode() == 200) {
            $data = $res->getBody();
        } else {
            log_message('error', '服务器无法下载文件：'.$config['url']);
            return dr_return_data(0, dr_lang('文件下载失败'));
        }*/

        $file_ext = isset($config['file_ext']) && $config['file_ext'] ? $config['file_ext'] : $this->_file_ext($config['url']); // 扩展名
        if (!in_array($file_ext, $this->down_file_ext)) {
            return dr_return_data(0, dr_lang('此扩展名被禁止下载'));
        }

        if (isset($config['file_content']) && $config['file_content']) {
            // 表示已经下载好了的文件
            $data = $config['file_content'];
        } else {
            $data = dr_catcher_data($config['url'], (int)$config['timeout']);
            if (!$data) {
                return dr_return_data(0, dr_lang('文件下载失败'));
            }
        }

        // 安全验证
        $rt = $this->_safe_check($file_ext, $data);
        if (!$rt['code']) {
            return dr_return_data(0, $rt['msg']);
        }

        $file_name = $this->_file_name($config['url']); // 文件实际名字
        if (!$file_ext) {
            CI_DEBUG && log_message('error', '无法获取文件扩展名：'.$config['url']);
            return dr_return_data(0, dr_lang('无法获取文件扩展名'));
        }

        // 保存目录名称
        list($file_path, $config, $diy) = $this->_rand_save_file_path($config, $file_ext, $data);

        // 开始上传存储文件
        $rt = $this->save_file('content', $data, $file_path, $config['attachment'], (int)$config['watermark']);
        if (!$rt['code']) {
            return dr_return_data(0, $rt['msg']);
        }
        if (isset($rt['fixpath']) && $rt['fixpath']) {
            // 是否返回新路径
            $file_path = $rt['fixpath'];
        }

        // 上传成功
        if ($diy) {
            $url = '自定义存储地址不提供URL';
        } else {
            $url = $config['attachment']['url'].$file_path;
        }

        return dr_return_data(1, 'ok', [
            'ext' => $file_ext,
            'url' => $url,
            'md5' => md5($data),
            'file' => $file_path,
            'size' => (int)$rt['data']['size'],
            'path' => ($config['attachment']['value']['path'] && $config['attachment']['value']['path'] != 'null' ? $config['attachment']['value']['path'] : '').$file_path,
            'name' => $file_name,
            'info' => $rt['data']['info'],
            'remote' => $config['attachment']['id'],
        ]);
    }

    /**
     * base64模式
     */
    public function base64_image($config) {

        $data = $config['content'];
        $file_ext = $config['ext'] ? $config['ext'] : 'jpg'; // 扩展名
        $file_name = isset($config['save_name']) && $config['save_name'] ? $config['save_name'] : 'base64_image'; // 文件实际名字

        // 安全验证
        $rt = $this->_safe_check($file_ext, $data);
        if (!$rt['code']) {
            return dr_return_data(0, $rt['msg']);
        }

        // 保存目录名称
        list($file_path, $config, $diy) = $this->_rand_save_file_path($config, $file_ext, $data);

        // 开始上传存储文件
        $rt = $this->save_file('content', $data, $file_path, $config['attachment'], (int)$config['watermark']);
        if (!$rt['code']) {
            return dr_return_data(0, $rt['msg']);
        }
        if (isset($rt['fixpath']) && $rt['fixpath']) {
            // 是否返回新路径
            $file_path = $rt['fixpath'];
        }

        // 上传成功
        if ($diy) {
            $url = '自定义存储地址不提供URL';
        } else {
            $url = $config['attachment']['url'].$file_path;
        }

        return dr_return_data(1, 'ok', [
            'ext' => $file_ext,
            'url' => $url,
            'md5' => md5($data),
            'file' => $file_path,
            'size' => $rt['data']['size'],
            'path' => ($config['attachment']['value']['path'] && $config['attachment']['value']['path'] != 'null' ? $config['attachment']['value']['path'] : '').$file_path,
            'name' => $file_name,
            'info' => $rt['data']['info'],
            'remote' => $config['attachment']['id'],
        ]);
    }

    /**
     * 存储文件
     */
    public function save_file($type, $data, $file_path, $attachment, $watermark = 0)  {

        // 存储目录安全验证
        if ($attachment['value']['path']
            && strpos($attachment['value']['path'], 'config') !== false) {
            return dr_return_data(0, dr_lang('存储目录不能包含config文字'));
        }

        // 按照附件存储类型来保存文件
        $storage = new \Phpcmf\Library\Storage();
        $rt = $storage->upload($type == 'upload' ? 1 : 0, $data, $file_path, $attachment, $watermark);
        if ($rt['code']) {
            if (isset($rt['fixpath']) && $rt['fixpath']) {
                // 是否返回新路径
                $file_path = $rt['fixpath'];
            }
            \Phpcmf\Hooks::trigger('upload_file', [
                'type' => $type,
                'data' => $data,
                'file_name' => $file_path,
                'file_path' => $attachment['value']['path'].$file_path,
                'attachment' => $attachment
            ]);
        }

        return $rt;
    }


    /**
     * 上传错误
     */
    protected function _error_msg($code) {
        return !$this->error[$code] ? '上传错误('.$code.')' : $this->error[$code];
    }

    /**
     * 获取文件名
     */
    public function file_name($name) {
        return $this->_file_name($name);
    }

    /**
     * 获取文件名
     */
    protected function _file_name($name) {
        strpos($name, '/') !== false && $name = trim(strrchr($name, '/'), '/');
        return substr($name, 0, strrpos($name, '.'));
    }

    public function file_ext($name) {
        return $this->_file_ext($name);
    }

    /**
     * 获取文件扩展名
     */
    protected function _file_ext($name) {

        if (strlen($name) > 300) {
            return '';
        }

        $ext = str_replace('.', '', trim(strtolower(strrchr($name, '.')), '.'));

        if (strlen($ext) > 10) {
            foreach (['gif', 'jpg', 'jpeg', 'png', 'webp'] as $t) {
                if (stripos($name, $t) !== false) {
                    return $t;
                }
            }
        }

        return $ext;
    }

    /**
     * 随机存储的文件名
     */
    protected function _rand_save_file_name($file) {
        return substr(md5(SYS_TIME.(is_array($file) ? dr_array2string($file) : $file).uniqid()), rand(0, 20), 15);
    }

    /**
     * 随机存储的文件路径
     */
    protected function _rand_save_file_path($config, $file_ext, $file) {

        $diy = 0;
        $name = '';
        if (isset($config['save_name']) && $config['save_name']) {
            if ($config['save_name'] == 'null') {
                // 按原始名称
                if (is_array($file) && isset($file['name']) && $file['name']) {
                    $name = trim(\Phpcmf\Service::L('pinyin')->result(dr_safe_filename($file['name'])), '.'.$file_ext);
                }
            } else {
                $name = $config['save_name'];
            }
        }

        // 随机新名字
        if (!$name) {
            $name = $this->_rand_save_file_name($file);
        }

        if (isset($config['save_file']) && $config['save_file']) {
            // 指定存储名称
            $diy = 1;
            $file_path = $config['save_file'];
            $config['save_file'] = dirname($file_path);
            $config['attachment']['value']['path'] = 'null';
        } else {
            if (isset($config['save_path']) && $config['save_path']) {
                // 指定存储路径
                $diy = 1;
                $path = $config['save_path'];
                $config['save_file'] = $path;
                $config['attachment']['value']['path'] = 'null';
            } else {
                if (isset($config['path']) && $config['path']) {
                    $path = $config['path'].'/'; // 按开发自定义参数
                } elseif (defined('SYS_ATTACHMENT_SAVE_TYPE') && SYS_ATTACHMENT_SAVE_TYPE) {
                    // 按后台设置目录
                    if (SYS_ATTACHMENT_SAVE_DIR) {
                        $path = str_replace(
                                ['{y}', '{m}', '{d}', '{yy}', '.'],
                                [date('Y', SYS_TIME), date('m', SYS_TIME), date('d', SYS_TIME), date('y', SYS_TIME), ''],
                                trim(SYS_ATTACHMENT_SAVE_DIR, '/')).'/';
                    } else {
                        $path = '';
                    }
                } else {
                    // 默认目录格式
                    $path = date('Ym', SYS_TIME).'/';
                }
            }
            $file_path = $path.$name.'.'.$file_ext;
        }

        return [$file_path, $config, $diy];
    }

    /**
     * 获取远程附件扩展名
     */
    public function get_image_ext($url) {

        if (strlen($url) > 300) {
            return '';
        }

        $arr = ['gif', 'jpg', 'jpeg', 'png', 'webp'];
        $ext = str_replace('.', '', trim(strtolower(strrchr($url, '.')), '.'));
        if ($ext && in_array($ext, $arr)) {
            return $ext; // 满足扩展名
        } elseif ($ext && strlen($ext) < 4) {
            //CI_DEBUG && log_message('error', '此路径不是远程图片：'.$url);
            return ''; // 表示不是图片扩展名了
        }

        foreach ($arr as $t) {
            if (stripos($url, $t) !== false) {
                return $t;
            }
        }

        $rt = getimagesize($url);
        if ($rt && $rt['mime']) {
            foreach ($arr as $t) {
                if (stripos($rt['mime'], $t) !== false) {
                    return $t;
                }
            }
        }

        CI_DEBUG && log_message('debug', '服务器无法获取远程图片的扩展名：'.dr_safe_replace($url));

        return '';
    }
}