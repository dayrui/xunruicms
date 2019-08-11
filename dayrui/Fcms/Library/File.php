<?php namespace Phpcmf\Library;

/* *
 *
 * Copyright [2019] [李睿]
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * www.xunruicms.com
 *
 * 本文件是框架系统文件，二次开发时不建议修改本文件
 *
 * */


/**
 * 文件操作
 */

class File {

    /**
     * $fromFile  要复制谁
     * $toFile    复制到那
     */
    public function copy_file($fromFile, $toFile) {
        $this->_create_folder($toFile);
        $folder1 = opendir($fromFile);
        while ($f1 = readdir($folder1)) {
            if ($f1 != "." && $f1 != "..") {
                $path2 = "{$fromFile}/{$f1}";
                if (is_file($path2)) {
                    $file = $path2;
                    $newfile = "{$toFile}/{$f1}";
                    @copy($file, $newfile);
                } elseif (is_dir($path2)) {
                    $toFiles = $toFile.'/'.$f1;
                    $this->copy_file($path2, $toFiles);
                }
            }
        }
    }

    function copy_dir($basedir, $filepath, $savepath){
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
        if (is_dir($dir) || @mkdir($dir, $mode)) {
            return true;
        }
        if (!$this->_create_folder(dirname($dir), $mode)) {
            return false;
        }
        return @mkdir($dir, $mode);
    }

    /**
     * sql执行文件插入
     */
    public function add_sql_cache($sql) {

        if (!$sql) {
            return;
        }

        $file = WRITEPATH.'temp/sql.cache';
        $data = is_file($file) ? json_decode(file_get_contents($file)) : array();
        if (in_array($sql, $data)) {
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

    // zip解压
    public function unzip($zipfile, $path = '') {

        !$path && $path = dirname($zipfile); // 当前目录

        $zip = new \ZipArchive;//新建一个ZipArchive的对象
        /*
        通过ZipArchive的对象处理zip文件
        $zip->open这个方法的参数表示处理的zip文件名。
        如果对zip文件对象操作成功，$zip->open这个方法会返回TRUE
        */
        if ($zip->open($zipfile) === TRUE)
        {
            $zip->extractTo($path);//假设解压缩到在当前路径下images文件夹的子文件夹php
            $zip->close();//关闭处理的zip文件
            return 1;
        }

        return 0;
    }

}