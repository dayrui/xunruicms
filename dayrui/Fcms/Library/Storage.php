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
                            $cfg = require $this->path.$dir.'/App.php';
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