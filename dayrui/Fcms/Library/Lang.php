<?php namespace Phpcmf\Library;


/**
 * http://www.xunruicms.com
 * 本文件是框架系统文件，二次开发时不可以修改本文件，可以通过继承类方法来重写此文件
 **/



/**
 * 语言包
 */

class Lang {

    public $lang;

    /**
     * 加载自定义语言
     */
    public function __construct(...$params) {
        if (is_file(ROOTPATH.'api/language/'.SITE_LANGUAGE.'/lang.php')) {
            $this->lang = require ROOTPATH.'api/language/'.SITE_LANGUAGE.'/lang.php';
        } else {
            $this->lang = [];
        }
    }

    /**
     * 输出最终语言
     */
    public function text($text) {
        return isset($this->lang[$text]) ? $this->lang[$text] : $text;
    }

}