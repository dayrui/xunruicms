<?php namespace Phpcmf\Library;
/**
 * {{www.xunruicms.com}}
 * {{迅睿内容管理框架系统}}
 * 本文件是框架系统文件，二次开发时不可以修改本文件，可以通过继承类方法来重写此文件
 **/

/**
 * 网站HTML目录分布
 */

class Html {

    protected $webpath;

    // 网站文件生成地址
    public function get_webpath($siteid, $mid, $file = '') {

        if (!$this->webpath) {
            if (is_file(WRITEPATH.'config/webpath.php')) {
                $this->webpath = require WRITEPATH.'config/webpath.php';
            } else {
                return WEBPATH.$file;
            }
        }

        $webpath = isset($this->webpath[$siteid]['site']) && $this->webpath[$siteid]['site'] ? $this->webpath[$siteid]['site'] : WEBPATH;
        if (isset($this->webpath[$siteid][$mid]) && $this->webpath[$siteid][$mid]) {
            $webpath = $this->webpath[$siteid][$mid];
        }

        return $webpath.$file;
    }

    public function get_category_data($app, $cat, $maxsize) {
        \Phpcmf\Service::C()->_json(0, '需要安装官方版【内容静态生成】插件');
    }


    public function get_show_data($app, $param) {
        \Phpcmf\Service::C()->_json(0, '需要安装官方版【内容静态生成】插件');
    }

}