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

        if (isset($this->lang[$text])) {
            return $this->lang[$text];
        } else {
            // 没有找到语言时，
            if (SITE_LANGUAGE != 'zh-cn' && IS_DEV) {
                $file = WRITEPATH.'lang_'.SITE_LANGUAGE.'.php';
                if (!is_file($file)) {
                    file_put_contents($file, '<?php return [];');
                }
                $lang_cache = require $file;
                if (!isset($lang_cache[$text])) {
                    $lang_cache[$text] = $text;
                    file_put_contents($file, '<?php'.PHP_EOL.'// 以下文字需要手动翻译'.PHP_EOL.PHP_EOL.' return '.var_export($lang_cache, true).';');
                }
            }
        }

        return $text;
    }

}