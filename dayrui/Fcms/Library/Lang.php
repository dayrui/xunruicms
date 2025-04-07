<?php namespace Phpcmf\Library;
/**
 * {{www.xunruicms.com}}
 * {{迅睿内容管理框架系统}}
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
        $file = ROOTPATH.'api/language/'.SITE_LANGUAGE.'/lang.php';
        if (is_file($file)) {
            $this->lang = require $file;
        } else {
            $this->lang = [];
        }
        // 执行插件自己的语言文件
        $local = \Phpcmf\Service::Apps();
        foreach ($local as $dir => $path) {
            if (is_file($path.'install.lock')
                && is_file($path.'Config/Lang_'.SITE_LANGUAGE.'.php')) {
                $_data = require $path.'Config/Lang_'.SITE_LANGUAGE.'.php';
                if ($_data) {
                    foreach ($_data as $key => $name) {
                        $this->lang[$key] = $name;
                    }
                }
            }
        }
    }

    /**
     * 输出最终语言
     */
    public function text($text) {

        if (!$text) {
            return $text;
        }

        $text = (string)$text;
        if (IS_XRDEV) {
            $text = trim($text, '}');
            $text = trim($text, '{');
        }
        if (isset($this->lang[$text])) {
            return $this->lang[$text];
        } else {
            if (IS_XRDEV) {
                $this->_save_file($text);
                return '{'.$text.'}';
            }
            // 加载自定义语言文件接口
            if (SITE_LANGUAGE != 'zh-cn' && function_exists('my_translate_lang')) {
                $rt = call_user_func_array('my_translate_lang', [
                    $text,
                    SITE_LANGUAGE,
                ]);
                if ($rt) {
                    return $rt;
                }
            }
            // 没有找到语言时记录日志中
            if (SITE_LANGUAGE != 'zh-cn' && IS_DEV) {
                $this->_save_file($text);
            }
        }

        return $text;
    }

    private function _save_file($text) {
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