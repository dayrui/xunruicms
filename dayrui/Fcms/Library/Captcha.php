<?php namespace Phpcmf\Library;
/**
 * {{www.xunruicms.com}}
 * {{迅睿内容管理框架系统}}
 * 本文件是框架系统文件，二次开发时不可以修改本文件，可以通过继承类方法来重写此文件
 **/

/**
 * 验证码类
 */
class Captcha
{

    protected $code;
    protected $charset = 'adefhkmnprstwyADEFGHKMNPRSTVWY683457';
    protected $width;
    protected $height;
    protected $img;
    protected $font;
    protected $fontsize = 16;
    protected $fontcolor;
    protected $randstring = ['*', '@', '$', '%', '&', '!'];

    public function __construct(...$params) {
        $this->font = WRITEPATH.'captcha.ttf';
        if (!is_file($this->font)) {
            // 加载老版本的字体文件
            $this->font = ROOTPATH.'config/font/1.ttf';
        }
    }

    // todo
    public function create($width = 120, $height = 32) {

        $this->_code();

        $this->width = min(200, $width ? $width : 120);
        $this->height = min(100, ($height ? $height : 32) - 2);

        $this->_bg();
        $this->_line();
        $this->_font();
        $this->_show();

        return $this->code;
    }

    //生成随机验证码
    protected function _code() {

        $code = '';
        $charset_len = strlen($this->charset) - 1;
        for ($i = 0; $i < 4; $i++) {
            $code .= $this->charset[rand(1, $charset_len)];
        }

        $this->code = trim($code);
    }

    //生成背景
    protected function _bg() {

        $this->img = imagecreatetruecolor($this->width, $this->height);
        $this->fontcolor = imagecolorallocate($this->img, mt_rand(0,180), mt_rand(0,180), mt_rand(0,180));
        $color = imagecolorallocate($this->img, 255, 255, 255);
        imagecolortransparent($this->img, $this->fontcolor);
        imagefill($this->img, 0, 0, $color);
    }

    //生成文字
    protected function _font() {
        $_x = $this->width / 4;
        for ($i=0; $i<4; $i++) {
            imagettftext($this->img, $this->fontsize, mt_rand(-30,30), $_x*$i+mt_rand(1,5), $this->height / 1.4, $this->fontcolor, $this->font, $this->code[$i]);
            //imagestring($this->img, $font, ($i==0 ? $_x/3 : 0) + $_x*$i+mt_rand(3,5),$this->height / (3 + mt_rand(1,9)/4),$this->code[$i], $this->fontcolor);
        }
    }

    //生成线条
    protected function _line() {
        for ($i=0;$i<5;$i++) {
            $color = imagecolorallocate($this->img, mt_rand(0,180), mt_rand(0,180), mt_rand(0,180));
            imageline(
                $this->img,
                mt_rand(0, $this->width),
                mt_rand(0, $this->height),
                mt_rand(0, $this->width),
                mt_rand(0, $this->height),
                $color
            );
        }
        for ($i=0;$i<10;$i++) {
            $color = imagecolorallocate($this->img, mt_rand(100,255), mt_rand(100,255), mt_rand(100,255));
            imagestring(
                $this->img,
                1,
                mt_rand(0, $this->width),
                mt_rand(0, $this->height),
                $this->randstring[rand(0, 5)],
                $color
            );
        }
    }

    //显示
    protected function _show() {
		ob_start();
        ob_clean(); //关键代码，防止出现'图像因其本身有错无法显示'的问题。
        header('Content-type:image/png');
        imagepng($this->img);
        imagedestroy($this->img);
    }
}
