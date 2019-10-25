<?php namespace Phpcmf\Admin;

/**
 * http://www.xunruicms.com
 * 本文件是框架系统文件，二次开发时不可以修改本文件，可以通过继承类方法来重写此文件
 **/


// 文件操作控制器
class File extends \Phpcmf\Common
{
    protected $dir;
    protected $root_path;
    protected $not_root_path;
    protected $backups_dir;
    protected $exclude_dir;
    protected $backups_path;

    public function __construct(...$params)
    {
        parent::__construct(...$params);
        $this->dir = $this->_safe_path(\Phpcmf\Service::L('input')->get('dir'));
        \Phpcmf\Service::V()->assign([
            'menu' => \Phpcmf\Service::M('auth')->_admin_menu(
                [
                    '文件管理' => [trim(APP_DIR.'/'.\Phpcmf\Service::L('Router')->class.'/index', '/'), 'fa fa-folder'],
                    '修改' => ['hide:'.trim(APP_DIR.'/'.\Phpcmf\Service::L('Router')->class.'/edit', '/'), 'fa fa-edit'],
                    '创建目录/文件' => ['add:'.trim(APP_DIR.'/'.\Phpcmf\Service::L('Router')->class.'/add', '/').'{dir='.$this->dir.'}', 'fa fa-plus', '500px', '240px'],
                ]
            ),
        ]);
    }

    protected function _Image() {

        $file = $this->_safe_path(\Phpcmf\Service::L('input')->get('file'));
        $filename = $this->root_path.$file;
        if (!is_file($filename)) {
            exit(dr_lang('文件%s不存在', $file));
        }


        $vals = getimagesize($filename);
        $types = array(1 => 'gif', 2 => 'jpeg', 3 => 'png');
        $mime = (isset($types[$vals[2]])) ? 'image/'.$types[$vals[2]] : 'image/jpg';

        switch ($vals[2]) {

            case 1:
                $resource = imagecreatefromgif($filename);
                break;

            case 2:
                $resource =  imagecreatefromjpeg($filename);
                break;

            case 3:
                $resource =  imagecreatefrompng($filename);
                break;
        }

        header('Content-Disposition: filename='.$filename.';');
        header('Content-Type: '.$mime);
        header('Content-Transfer-Encoding: binary');
        header('Last-Modified: '.gmdate('D, d M Y H:i:s', time()).' GMT');

        switch ($vals[2])
        {
            case 1	:	imagegif($resource);
                break;
            case 2	:	imagejpeg($resource, NULL, 99);
                break;
            case 3	:
                imagepng($resource);
                break;
        }

        exit;
    }

    protected function _List() {

        list($path, $list) = $this->_map_file($this->dir);

        \Phpcmf\Service::V()->assign([
            'list' => $list,
            'path' => rtrim($path, DIRECTORY_SEPARATOR),
            'is_root' => !$this->dir ? 1 : 0,
            'delete' =>\Phpcmf\Service::L('Router')->url(trim(APP_DIR.'/'.\Phpcmf\Service::L('Router')->class.'/del', '/'), ['dir' => $this->dir]),
        ]);
        \Phpcmf\Service::V()->display('tpl_index.html');
    }

    protected function _Add() {

        if (IS_AJAX_POST) {

            if (!IS_EDIT_TPL) {
                $this->_json(0, dr_lang('系统不允许创建和修改模板文件'), ['field' => 'name']);
            }

            $name = dr_safe_filename(\Phpcmf\Service::L('input')->post('name'));
            if (!$name) {
                $this->_json(0, dr_lang('文件名称不能为空'), ['field' => 'name']);
            }

            $path = $this->root_path.($this->dir ? $this->dir : trim($this->dir, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;
            $file = $path.$name;

            if (strpos($name, '.') !== false) {
                // 文件
                $ext = trim(strtolower(strrchr($file, '.')), '.');
                strpos($ext, 'php') !== false && $this->_json(0, dr_lang('文件不允许'));
                file_put_contents($file, '') === false && $this->_json(0, dr_lang('文件创建失败'), ['field' => 'name']);
            } else {
                mkdir($file, 0777);
            }

            \Phpcmf\Service::L('input')->system_log('创建文件：'.$file);
            $this->_json(1, dr_lang('操作成功'));
        }

        \Phpcmf\Service::V()->assign([
            'name' => '',
        ]);
        \Phpcmf\Service::V()->display('tpl_dirname.html');
        exit;
    }

    protected function _Edit() {

        $file = $this->_safe_path(\Phpcmf\Service::L('input')->get('file'));
        $fileext = trim(strtolower(strrchr($file, '.')), '.');
        $fileext == 'php' && $this->_json(0, dr_lang('文件不允许'));
        $filename = $this->root_path.$file;

        switch (\Phpcmf\Service::L('input')->get('mtype')) {

            case 'dir':
                // 目录修改名称
                // 目录修改
                if (IS_AJAX_POST) {

                    $name = dr_safe_filename(\Phpcmf\Service::L('input')->post('name'));
                    if (!IS_EDIT_TPL) {
                        $this->_json(0, dr_lang('系统不允许创建和修改模板文件'), ['field' => 'name']);
                    } elseif (!is_dir($filename)) {
                        $this->_json(0, dr_lang('此目录不存在'));
                    } elseif (!$name) {
                        $this->_json(0, dr_lang('目录名称不能为空'), ['field' => 'name']);
                    }

                    // 开始修改
                    if ( basename($filename) != $name && !rename($filename, dirname($filename).'/'.$name)) {
                        $this->_json(0, dr_lang('重命名失败'), ['field' => 'name']);
                    }

                    \Phpcmf\Service::L('input')->system_log('目录['.$filename.']改为['.dirname($filename).'/'.$name.']');
                    $this->_json(1, dr_lang('操作成功'));
                }

                \Phpcmf\Service::V()->assign([
                    'name' => basename($file),
                ]);
                \Phpcmf\Service::V()->display('tpl_dirname.html');
                exit;
                break;

            case 'filecname':
                // 重命名文件

                $fname = str_replace('.'.$fileext, '', basename($file));

                if (IS_AJAX_POST) {

                    $name = dr_safe_filename(\Phpcmf\Service::L('input')->post('name'));
                    if (!IS_EDIT_TPL) {
                        $this->_json(0, dr_lang('系统不允许创建和修改模板文件'), ['field' => 'name']);
                    } elseif (!$name) {
                        $this->_json(0, dr_lang('文件名称不能为空'), ['field' => 'name']);
                    }

                    // 开始修改
                    if ($fname != $name && !rename($filename, dirname($filename).'/'.$name.'.'.$fileext)) {
                        $this->_json(0, dr_lang('重命名失败'), ['field' => 'name']);
                    }

                    \Phpcmf\Service::L('input')->system_log('文件['.$filename.']改为['.dirname($filename).'/'.$name.']');
                    $this->_json(1, dr_lang('操作成功'));
                }

                \Phpcmf\Service::V()->assign([
                    'name' => $fname,
                ]);
                \Phpcmf\Service::V()->display('tpl_dirname.html');
                exit;
                break;

            case 'cname':
                // 重命名文件别名

                $cname = $this->_get_name_ini($file);

                if (IS_AJAX_POST) {

                    $name = dr_safe_filename(\Phpcmf\Service::L('input')->post('name'));
                    if (!IS_EDIT_TPL) {
                        $this->_json(0, dr_lang('系统不允许创建和修改模板文件'), ['field' => 'name']);
                    } elseif (!$name) {
                        $this->_json(0, dr_lang('文件名称不能为空'), ['field' => 'name']);
                    }

                    // 开始修改
                    $this->_save_name_ini($file, $name);

                    $this->_json(1, dr_lang('操作成功'));
                }

                \Phpcmf\Service::V()->assign([
                    'name' => $cname,
                ]);
                \Phpcmf\Service::V()->display('tpl_cname.html');
                exit;
                break;

            case 'file':

                // 文件修改
                if (!is_file($filename)) {
                    $this->_admin_msg(0, dr_lang('文件%s不存在', $file));
                }

                if (in_array($fileext, ['html', 'htm', 'css', 'js', 'map', 'ini', 'php'])) {
                    // 文件内容编辑模式
                    $dir = md5($filename);
                    $bfile = intval(\Phpcmf\Service::L('input')->get('bfile'));
                    if ($bfile && is_file($this->backups_path.$dir.'/'.$bfile)) {
                        $name = dr_lang('对比历史文件：%s（左边是当前文件；右边是历史文件）', dr_date($bfile));
                        $is_diff = 1;
                        $diff_content = file_get_contents($this->backups_path.$dir.'/'.$bfile);
                    } else {
                        $name = dr_lang('文件修改');
                        $is_diff = 0;
                        $diff_content = '';
                    }

                    $content = file_get_contents($filename);
                    $backups = dr_file_map($this->backups_path.$dir.'/');

                    if (IS_AJAX_POST) {

                        $code = \Phpcmf\Service::L('input')->post('code', false);
                        if (!IS_EDIT_TPL) {
                            $this->_json(0, dr_lang('系统不允许创建和修改模板文件'), ['field' => 'name']);
                        } elseif (!$code) {
                            $this->_json(0, dr_lang('内容不能为空'));
                        }

                        // 解析模板
                        if ($fileext == 'html') {
                            // 模板解析时 预加载全部的自定义函数
                            // 执行插件自己的缓存程序
                            $local = dr_dir_map(dr_get_app_list(), 1);
                            foreach ($local as $dir) {
                                $path = dr_get_app_dir($dir);
                                if (is_file($path.'install.lock')
                                    && is_file($path.'Config/Init.php')) {
                                    require $path.'Config/Init.php';
                                }
                            }
                            ob_start();
                            require \Phpcmf\Service::V()->code2php($code, SYS_TIME, 0);
                            $html = ob_get_clean();
                        }

                        // 备份数据
                        if ($content != $code && $is_diff == 0) {
                            !is_dir($this->backups_path.$dir.'/') && mkdir($this->backups_path.$dir.'/', 0777);
                            $size = file_put_contents($this->backups_path.$dir.'/'.SYS_TIME, $content);
                            if ($size === false) {
                                $this->_json(0, dr_lang('备份目录/cache/backups/无法存储'));
                            }
                        }

                        // 替换现有的文件
                        $size = file_put_contents($filename, $code);
                        if ($size === false) {
                            $this->_json(0, dr_lang('模板目录无法写入'));
                        }

                        $cname = \Phpcmf\Service::L('input')->post('cname');
                        $cname && $this->_save_name_ini($filename, $cname);

                        \Phpcmf\Service::L('input')->system_log('修改文件内容['.$filename.']');

                        $this->_json(1, dr_lang('操作成功'));
                    }

                    switch ($fileext) {

                        case 'js':
                            $file_ext = 'javascript';
                            $file_js  = 'javascript/javascript.js';
                            break;

                        case 'css':
                            $file_ext = 'css';
                            $file_js  = 'css/css.js';
                            break;

                        default:
                            $file_ext = 'html';
                            $file_js  = 'htmlmixed/htmlmixed.js';
                            break;
                    }

                    \Phpcmf\Service::V()->assign([
                        'name' => $name,
                        'code' => $this->_get_code($content, $fileext),
                        'path' => $filename,
                        'cname' => $this->_get_name_ini($filename),
                        'backups' => $backups,
                        'file_js' => $file_js,
                        'file_ext' => $file_ext,
                        'diff_code' => htmlentities($diff_content,ENT_COMPAT,'UTF-8'),
                        'reply_url' =>\Phpcmf\Service::L('Router')->url(trim(APP_DIR.'/'.\Phpcmf\Service::L('Router')->class.'/index', '/'), ['dir' => dirname($file)]),
                        'backups_url' =>\Phpcmf\Service::L('Router')->url(trim(APP_DIR.'/'.\Phpcmf\Service::L('Router')->class.'/edit', '/'), ['mtype' => 'file', 'file' => $file]),
                        'backups_del' =>\Phpcmf\Service::L('Router')->url(trim(APP_DIR.'/'.\Phpcmf\Service::L('Router')->class.'/clear_del', '/'), ['file' => $file]),
                    ]);
                    \Phpcmf\Service::V()->display($is_diff ? 'tpl_diff.html' : 'tpl_edit.html');

                } else {

                    $reply_url =\Phpcmf\Service::L('Router')->url(trim(APP_DIR.'/'.\Phpcmf\Service::L('Router')->class.'/index', '/'), ['dir' => dirname($file)]);
                    if (IS_POST) {
                        if (!IS_EDIT_TPL) {
                            $this->_json(0, dr_lang('系统不允许创建和修改模板文件'), ['field' => 'name']);
                        }
                        $rt = \Phpcmf\Service::L('upload')->update_file([
                            'file_name' => $filename,
                            'form_name' => 'file',
                            'file_exts' => [$fileext],
                        ]);
                        !$rt['code'] && $this->_admin_msg(0, $rt['msg']);
                        \Phpcmf\Service::L('input')->system_log('上传新文件['.$filename.']');
                        $this->_admin_msg(1, dr_lang('操作成功'), $reply_url);
                    }

                    $preview = '<img src="'.ROOT_THEME_PATH.'assets/images/ext/'.$fileext.'.png'.'">';
                    in_array($fileext, ['jpg', 'gif', 'png', 'jpeg']) && $preview = '<a href="javascript:dr_preview_image(\''.\Phpcmf\Service::L('Router')->url(trim(APP_DIR.'/'.\Phpcmf\Service::L('Router')->class.'/image_index', '/'), ['file'=>$file]).'\');">'.$preview.'</a>';

                    // 文件上传模式
                    \Phpcmf\Service::V()->assign([
                        'path' => $filename,
                        'preview' => $preview,
                        'reply_url' => $reply_url,
                    ]);
                    \Phpcmf\Service::V()->display('tpl_file.html');
                }

                break;

            case 'zip':

                if (!IS_EDIT_TPL) {
                    $this->_json(0, dr_lang('系统不允许创建和修改模板文件'), ['field' => 'name']);
                } elseif ($fileext != 'zip') {
                    $this->_json(0, dr_lang('不是zip压缩文件'));
                } elseif (!\Phpcmf\Service::L('file')->unzip($filename)) {
                    // 解压zip
                    $this->_json(0, dr_lang('zip解压失败'));
                }

                \Phpcmf\Service::L('input')->system_log('Zip解压['.$filename.']');
                $this->_json(1, dr_lang('解压成功'));
                break;

        }
    }

    protected function _Del() {

        $ids = \Phpcmf\Service::L('input')->post('ids');
        if (!$ids) {
            $this->_json(0, dr_lang('还没有选择呢'));
        } elseif (!IS_EDIT_TPL) {
            $this->_json(0, dr_lang('系统不允许创建和修改模板文件'), ['field' => 'name']);
        }

        $path = $this->root_path.($this->dir ? $this->dir : trim($this->dir, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;

        helper('filesystem');

        foreach ($ids as $file) {
            $file = $this->_safe_path($file);
            if ($file) {
                if (is_dir($path.$file)) {
                    delete_files($path.$file, TRUE);
                    @rmdir($path.$file);
                    \Phpcmf\Service::L('input')->system_log('删除目录['.$path.$file.']');
                } else {
                    @unlink($path.$file);
                    \Phpcmf\Service::L('input')->system_log('删除文件['.$path.$file.']');
                }
            }

        }

        $this->_json(1, dr_lang('操作成功'));
    }

    protected function _Clear() {

        $file = $this->_safe_path(\Phpcmf\Service::L('input')->get('file'));
        $filename = $this->root_path.$file;
        if (!is_file($filename)) {
            $this->_json(0, dr_lang('文件%s不存在', $file));
        }

        $dir = md5($filename);
		\Phpcmf\Service::L('cache')->del_all('backups/'.$this->backups_dir.'/'.$dir.'/');
        @rmdir($this->backups_path.$dir.'/');

        $this->_json(1, dr_lang('操作成功'));
    }

    /**
     * 文件图标
     */
    protected function _file_icon($file) {

        $ext = trim(strtolower(strrchr($file, '.')), '.');
        if (is_file(ROOTPATH.'static/assets/images/ext/'.$ext.'.png')) {
            return ROOT_THEME_PATH.'assets/images/ext/'.$ext.'.png';
        }

        return ROOT_THEME_PATH.'assets/images/ext/url.png';
    }

    /**
     * 安全目录
     */
    protected function _safe_path($string) {
        return trim(str_replace(
            ['..', "//", ".//.", '\\', ' ', '<', '>', "{", '}'],
            '',
            $string
        ), '/');
    }

    /**
     * 目录扫描
     */
    protected function _map_file($dir) {

        $file_data = $dir_data = [];
        $dir && $dir_data = [
            [
                'id' => 0,
                'name' => '..',
                'icon' => ROOT_THEME_PATH.'assets/images/ext/folder.png',
                'size' => ' - ',
                'time' => '',
                'edit' => '',
                'file' => '',
                'cname' => '',
                'zip' => '',
                'url' =>\Phpcmf\Service::L('Router')->url(trim(APP_DIR.'/'.\Phpcmf\Service::L('Router')->class.'/index', '/'), ['dir' => trim(dirname($this->dir), '.')]),
            ]
        ];

        $source_dir	= dr_rp($this->root_path.($dir ? $dir : trim($dir, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR), ['//', DIRECTORY_SEPARATOR.DIRECTORY_SEPARATOR], ['/', DIRECTORY_SEPARATOR]);


        if ($fp = @opendir($source_dir)) {

            while (FALSE !== ($file = readdir($fp))) {
                if (in_array($file, ['.', '..', '.DS_Store', 'config.ini', 'thumb.jpg'])) {
                    continue;
                } elseif (strtolower(strrchr($file, '.')) == '.php') {
                    continue;
                } elseif ($this->not_root_path && in_array($source_dir.$file, $this->not_root_path)) {
                    continue;
                }

                $edit =\Phpcmf\Service::L('Router')->url(trim(APP_DIR.'/'.\Phpcmf\Service::L('Router')->class.'/edit', '/'), ['file' => $this->dir.'/'.$file]);
                if (is_dir($source_dir.'/'.$file)) {
                    if (!$dir && $this->exclude_dir && is_array($this->exclude_dir) && in_array($file, $this->exclude_dir)) {
                        continue;
                    }
                    // 缩略图预览图
                    $thumb = '';
                    if (is_file($source_dir.'/'.$file.'/thumb.jpg')) {
                        list($cache_path, $cache_url) = dr_thumb_path();
                        if (file_put_contents($cache_path.md5($source_dir.'/'.$file).'_thumb.jpg', file_get_contents($source_dir.'/'.$file.'/thumb.jpg'))) {
                            $thumb = $cache_url.md5($source_dir.'/'.$file).'_thumb.jpg';
                        }
                    }
                    $dir_data[] = [
                        'id' => 0,
                        'name' => $file,
                        'cname' => $this->_get_name_ini($source_dir.'/'.$file),
                        'cname_edit' => 'javascript:dr_iframe(\''.dr_lang('文件别名').'\', \''.$edit.'&mtype=cname\', \'500px\',\'240px\');',
                        'thumb' => $thumb,
                        'file' => $file,
                        'icon' => ROOT_THEME_PATH.'assets/images/ext/folder.png',
                        'size' => ' - ',
                        'time' => dr_date(filemtime($source_dir.'/'.$file), null, 'red'),
                        'zip' => '',
                        'edit' => 'javascript:dr_iframe(\''.dr_lang('目录/文件名称').'\', \''.$edit.'&mtype=dir\', \'500px\',\'240px\');',
                        'url' =>\Phpcmf\Service::L('Router')->url(trim(APP_DIR.'/'.\Phpcmf\Service::L('Router')->class.'/index', '/'), ['dir' => $this->dir.'/'.$file]),
                    ];
                } else {
                    $ext = trim(strtolower(strrchr($file, '.')), '.');
                    $edit =\Phpcmf\Service::L('Router')->url(trim(APP_DIR.'/'.\Phpcmf\Service::L('Router')->class.'/edit', '/'), ['file' => $this->dir.'/'.$file]);
                    $file_data[] = [
                        'id' => md5($file),
                        'name' => $file,
                        'cname' => $this->_get_name_ini($source_dir.'/'.$file),
                        'cname_edit' => 'javascript:dr_iframe(\'edit\', \''.$edit.'&mtype=cname\', \'500px\',\'240px\');',
                        'file' => $file,
                        'icon' => $this->_file_icon($file),
                        'size' => dr_format_file_size(filesize($source_dir.'/'.$file)),
                        'time' => dr_date(filemtime($source_dir.'/'.$file), null, 'red'),
                        'edit' => $edit.'&mtype=file',
                        'url' => $edit.'&mtype=file',
                        'zip' => $ext == 'zip' ? 'javascript:dr_ajax_url(\''.$edit.'&mtype=zip'.'\');' : '',
                        //'cname' => 'javascript:dr_iframe(\'edit\', \''.$edit.'&mtype=filecname\', \'500px\',\'240px\');',
                    ];
                }
            }

            closedir($fp);
        }

        return [$source_dir, $dir_data && $file_data ? array_merge($dir_data, $file_data) : $dir_data];
    }

    // 存储文件别名
    protected function _save_name_ini($file, $value) {

        list($dir, $path) = $this->_get_one_dirname($file);
        $id = md5($path);
        $ini = $this->root_path.$dir.'/config.ini';

        $data = json_decode(file_get_contents($ini), true);
        !$data && $data = [];
        $data[$id] = $value;

        file_put_contents($ini, json_encode($data));

        \Phpcmf\Service::L('input')->system_log('修改文件别名['.$file.']：'.$value);
    }

    // 获取单个文件别名
    protected function _get_name_ini($file) {

        list($dir, $path, $lsname) = $this->_get_one_dirname($file);
        $id = md5($path);
        $ini = $this->root_path.$dir.'/config.ini';
        $data = json_decode(file_get_contents($ini), true);

        return isset($data[$id]) ? (string)$data[$id] : $lsname;
    }

    // 获取第一个目录名称
    protected function _get_one_dirname($path) {

        $dir = trim(str_replace(['/', '\\'], '*', str_replace($this->root_path, '', $path)), '*');
        if (strpos($dir, '*')) {
            //存在子目录
            list($a) = explode('*', $dir);
            return [$a, $dir, trim(strtolower(strrchr($dir, '*')), '*')];
        }

        return [$dir, $dir, $dir];
    }

    // 格式化内容
    protected function _get_code($code, $ext) {

        if ($ext == 'js') {
            return $code;
        }

        return htmlentities($code,ENT_COMPAT,'UTF-8');
    }

}
