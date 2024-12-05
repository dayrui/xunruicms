<?php namespace Phpcmf\Library;
/**
 * {{www.xunruicms.com}}
 * {{迅睿内容管理框架系统}}
 * 本文件是框架系统文件，二次开发时不可以修改本文件，可以通过继承类方法来重写此文件
 **/

/**
 * 验证码类
 */
class Captcha {

    private $code;
    private $charset = 'adehkmnprswyADEFGHKMNPRSTVWY683457'; //设置随机生成因子
    private $width;
    private $height;
    private $img;
    private $font;
    private $fontsize = 16;
    private $fontcolor;
    private $randstring = ['*', '@', '$', '%', '&', '!'];

    public function __construct() {
        if (function_exists('my_captcha_ttf()')) {
            $this->font = my_captcha_ttf();
        } else {
            $this->font = WRITEPATH.'captcha.ttf';
            if (!is_file($this->font)) {
                // 加载老版本的字体文件
                $this->font = CONFIGPATH.'font/1.ttf';
                if (!is_file($this->font)) {
                    log_message('error', '验证码字体文件（'.WRITEPATH.'captcha.ttf）不存在，可能无法生成验证码');
                }
            }
        }
    }

    // todo
    public function create($width = 120, $height = 32) {

        $this->_code();

        $this->width = min(200, $width > 0 ? abs((int)$width) : 120);
        $this->height = min(100, $height > 0 ? abs((int)$height) : 32);

        $this->_bg();
        $this->_line();
        $this->_font();
        $this->_show();


        return $this->code;
    }

    //生成随机验证码
    private function _code() {

        if ($this->code) {
            return $this->code;
        }

        $code = '';
        $charset_len = strlen($this->charset) - 1;
        for ($i = 0; $i < 4; $i++) {
            $code.= $this->charset[rand(1, $charset_len)];
        }

        $this->code = trim($code);
    }

    //生成背景
    private function _bg() {

        $this->img = imagecreatetruecolor($this->width, $this->height);
        $color = imagecolorallocate($this->img,255,255,255);
        imagecolortransparent($this->img, $color);
        imagefill($this->img,0,0, $color);
    }

    //生成文字
    private function _font() {
        $_x = $this->width / 4;
        $this->fontcolor = imagecolorallocate($this->img, mt_rand(0,180), mt_rand(0,180), mt_rand(0,180));
        for ($i=0; $i<4; $i++) {
            imagettftext(
                $this->img,
                $this->fontsize,
                mt_rand(-30,30),
                intval($_x*$i+mt_rand(1,5)),
                intval($this->height / 1.4),
                $this->fontcolor,
                $this->font,
                $this->code[$i]
            );
        }
    }

    //生成干扰线条
    private function _line() {
        for ($i=0;$i<5;$i++) {
            $color = imagecolorallocate($this->img, mt_rand(0,180), mt_rand(0,180), mt_rand(0,180));
            imageline(
                $this->img,
                mt_rand(0,$this->width),
                mt_rand(0,$this->height),
                mt_rand(0,$this->width),
                mt_rand(0,$this->height),
                $color
            );
        }
        for ($i=0;$i<30;$i++) {
            $color = imagecolorallocate($this->img, mt_rand(100,255), mt_rand(100,255), mt_rand(100,255));
            imagestring(
                $this->img,
                mt_rand(1,5),
                mt_rand(0,$this->width),
                mt_rand(0,$this->height),
                $this->randstring[rand(0, 5)],
                $color
            );
        }
    }

    //显示
    private function _show() {
        @ob_start();
        @ob_clean(); //关键代码，防止出现'图像因其本身有错无法显示'的问题。
        header('Content-type:image/png');
        imagepng($this->img);
        imagedestroy($this->img);
    }
}
