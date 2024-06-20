<?php namespace Phpcmf\Library;
/**
 * {{www.xunruicms.com}}
 * {{迅睿内容管理框架系统}}
 * 本文件是框架系统文件，二次开发时不可以修改本文件，可以通过继承类方法来重写此文件
 **/

/**
 * 文件操作
 */
class File {

    /**
     * 复制文件
     * $fromFile  要复制谁
     * $toFile    复制到那
     */
    public function copy_file($fromFile, $toFile) {
        $this->_create_folder($toFile);
        $folder1 = opendir($fromFile);
        while ($folder1 && $f1 = readdir($folder1)) {
            if ($f1 != "." && $f1 != "..") {
                $path2 = "{$fromFile}/{$f1}";
                if (is_file($path2)) {
                    $file = $path2;
                    $newfile = "{$toFile}/{$f1}";
                    copy($file, $newfile);
                } elseif (is_dir($path2)) {
                    $toFiles = $toFile.'/'.$f1;
                    $this->copy_file($path2, $toFiles);
                }
            }
        }
    }

    // 复制目录
    // 源文件目录$basedir，源文件目录$filepath，新文件目录$savepath
    public function copy_dir($basedir, $filepath, $savepath){
        if ($dh = opendir($basedir)) {
            while (($file = readdir($dh)) !== false) {
                if (strpos($file, '.') !== 0){
                    if (!is_dir($basedir."/".$file)) {
                        $fl = str_replace($filepath, '', $basedir."/".$file);
                        dr_mkdirs(dirname($savepath.$fl));
                        $code = file_get_contents($basedir."/".$file);
                        file_put_contents($savepath.$fl, $code);
                    }else{
                        $dirname = $basedir."/".$file;
                        $this->copy_dir($dirname, $filepath, $savepath);
                    }
                }
            }
            closedir($dh);
        }
    }

    /**
     * 递归创建文件夹
     */
    public function _create_folder($dir, $mode = 0777){
        if (is_dir($dir) || mkdir($dir, $mode)) {
            return true;
        }
        if (!$this->_create_folder(dirname($dir), $mode)) {
            return false;
        }
        return mkdir($dir, $mode);
    }

    /**
     * sql执行文件插入
     */
    public function add_sql_cache($sql) {

        if (!$sql) {
            return;
        }

        $file = WRITEPATH.'temp/sql.cache';
        $data = is_file($file) ? json_decode(file_get_contents($file)) : [];
        if (dr_in_array($sql, $data)) {
            return ;
        }

        $data[] = $sql;
        file_put_contents($file, dr_array2string($data));

        return;
    }

    /**
     * sql执行文件插入
     */
    public function get_sql_cache() {
        $file = WRITEPATH.'temp/sql.cache';
        return is_file($file) ? json_decode(file_get_contents($file)) : array();
    }

    /**
     * base64
     */
    public function base64_image($image_file) {
        $image_info = getimagesize($image_file);
        $image_data = fread(fopen($image_file, 'r'), filesize($image_file));
        $base64_image = 'data:' . $image_info['mime'] . ';base64,' . chunk_split(base64_encode($image_data));
        return $base64_image;
    }
	
	protected function _zip_error($code) {
		
		$error = [
			1 => '不支持多磁盘zip压缩包',
			2 => '重命名临时文件失败',
			3 => '关闭zip压缩包失败',
			4 => '寻址错误',
			5 => '读取错误',
			6 => '写入错误',
			7 => 'CRC校验失败',
			8 => 'zip压缩包已关闭',
			9 => '没有文件',
			10 => '文件已经存在',
			11 => '不能打开文件',
			12 => '创建临时文件失败',
			13 => 'Zlib错误',
			14 => '内存分配失败',
			15 => '条目已被改变',
			16 => '不支持的压缩方式',
			17 => '过早的EOF',
			18 => '无效的参数',
			19 => '不是一个zip压缩包',
			20 => 'Internal',
			21 => 'Zip压缩包不一致',
			22 => '不能移除文件',
			23 => '条目已被删除',
		];
		return isset($error[$code]) ? $error[$code] : $code;
	}

    // zip解压
    public function unzip($zipfile, $path = '') {

        if (!class_exists('ZipArchive')) {
            return 0;
        }

        !$path && $path = dirname($zipfile); // 当前目录

        $zip = new \ZipArchive;//新建一个ZipArchive的对象
        /*
        通过ZipArchive的对象处理zip文件
        $zip->open这个方法的参数表示处理的zip文件名。
        如果对zip文件对象操作成功，$zip->open这个方法会返回TRUE
        */
        if ($zip->open($zipfile) === TRUE) {
            $zip->extractTo($path);//假设解压缩到在当前路径下images文件夹的子文件夹php
            $zip->close();//关闭处理的zip文件
            return 1;
        }

        return 0;
    }

    // zip压缩
    public function zip($zfile, $path, $remove = []) {

        if (!class_exists('ZipArchive')) {
            return dr_lang('PHP环境不支持ZipArchive类');
        } elseif (!$path) {
            return dr_lang('目录参数为空');
        }

        $zpath = dirname($zfile);
        if (!is_dir($zpath)) {
            dr_mkdirs($zpath);
        }

        $zip = new \ZipArchive;
		$code = $zip->open($zfile, \ZipArchive::OVERWRITE | \ZipArchive::CREATE);
        if ($code !== TRUE) {
            return dr_lang('创建Zip文件失败#%s', $this->_zip_error($code));
        }

        $this->_create_zip(opendir($path), $zip, $path, '', $remove);
        $zip->close();

        if (!is_file($zfile)) {
            return dr_lang('文件压缩失败');
        }

        return '';
    }

    /*压缩多级目录
        $openFile:目录句柄
        $zipObj:Zip对象
        $sourceAbso:源文件夹路径
    */
    protected function _create_zip($openFile, $zipObj, $sourceAbso, $newRelat = '', $remove = []) {
        while (($file = readdir($openFile)) != false) {
            if ($file=="." || $file=="..") {
                continue;
            }
            /*源目录路径(绝对路径)*/
            $sourceTemp = $sourceAbso.'/'.$file;
            /*目标目录路径(相对路径)*/
            $newTemp = $newRelat=='' ? $file : $newRelat.'/'.$file;
            if (is_dir($sourceTemp)) {
                if ($remove) {
                    $rk = 0;
                    foreach ($remove as $r) {
                        if (substr($r, 0, 1) == '*') {
                            if (strpos($sourceTemp, trim($r,'*')) !== false) {
                                $rk = 1;
                                break;
                            }
                        } else {
                            if ($sourceTemp == $r) {
                                $rk = 1;
                                break;
                            }
                        }
                    }
                    if ($rk) {
                        continue;
                    }
                }
                //echo '创建'.$newTemp.'文件夹<br/>';
                $zipObj->addEmptyDir($newTemp);/*这里注意：php只需传递一个文件夹名称路径即可*/
                $this->_create_zip(opendir($sourceTemp), $zipObj, $sourceTemp, $newTemp, $remove);
            }
            if (is_file($sourceTemp)) {
                //echo '创建'.$newTemp.'文件<br/>';
                $zipObj->addFile($sourceTemp, $newTemp);
            }
        }
    }

    /*下载文件
    储存路径
    访问地址
    文件名含扩展名
    */
    public function down($file, $url, $name) {
        //大文件在读取内容未结束时会被超时处理，导致下载文件不全。
        set_time_limit(0);
        $handle = fopen($file,"rb");
        if (FALSE === $handle) {
            \Phpcmf\Service::C()->_msg(0, dr_lang('文件已经损坏'));
        }
        $size = min(defined('SYS_ATTACHMENT_DOWN_SIZE') ? (int)SYS_ATTACHMENT_DOWN_SIZE : 50, 50);
        $filesize = filesize($file);
        if ($filesize > 1024 * 1024 * $size) {
            // 大文件转向
            if (IS_DEV) {
                log_message('debug', '由于文件大于'.$size.'MB，重命名文件功能将失效，下载地址将跳转到文件本身的地址');
            }
            dr_redirect($url);
        } else {
            header('Content-Type: application/octet-stream');
            header("Accept-Ranges:bytes");
            header("Accept-Length:".$filesize);
            header("Content-Disposition: attachment; filename=".urlencode($name));

            while (!feof($handle)) {
                $contents = fread($handle, 4096);
                echo $contents;
                ob_flush();  //把数据从PHP的缓冲中释放出来
                flush();      //把被释放出来的数据发送到浏览器
            }

            fclose($handle);
        }
    }
}