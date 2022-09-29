<?php namespace Phpcmf\Control\Api;
/**
 * {{www.xunruicms.com}}
 * {{迅睿内容管理框架系统}}
 * 本文件是框架系统文件，二次开发时不可以修改本文件
 **/

// 通用接口处理
class Api extends \Phpcmf\Common {

    /**
     * ip地址
     */
    public function ip_address() {

        $value = dr_safe_replace(\Phpcmf\Service::L('input')->get('value'));
        if (!$value) {
            exit(dr_lang('IP地址为空'));
        }

        list($value, $port) = explode('-', $value);
        $address = \Phpcmf\Service::L('ip')->address($value);
        echo ('<a href="https://www.baidu.com/s?wd='.$value.'&action=xunruicms" target="_blank">'.dr_lang('IP归属地：%s', $address).'</a>');
        if ($port) {
            echo '
<br>'.dr_lang('源端口号：%s', (int)$port);
        }
        exit;
    }

    /**
     * 保存浏览器定位坐标
     */
    public function map_position() {

        $value = dr_safe_replace(\Phpcmf\Service::L('input')->get('value'));
        $cookie = \Phpcmf\Service::L('input')->get_cookie('map_position');
        if ($cookie != $value) {
            \Phpcmf\Service::L('input')->set_cookie('map_position', $value, 10000);
            exit('ok');
        }

        exit('none');
    }

    /**
     * 二维码显示
     */
    public function qrcode() {

        $value = urldecode(\Phpcmf\Service::L('input')->get('text'));
        $thumb = urldecode(\Phpcmf\Service::L('input')->get('thumb'));
        $matrixPointSize = (int)\Phpcmf\Service::L('input')->get('size');
        $errorCorrectionLevel = dr_safe_replace(\Phpcmf\Service::L('input')->get('level'));

        //生成二维码图片
        require_once CMSPATH.'Library/Phpqrcode.php';
        $file = WRITEPATH.'file/qrcode-'.md5($value.$thumb.$matrixPointSize.$errorCorrectionLevel).'-qrcode.png';
        if (!IS_DEV && is_file($file)) {
            $QR = imagecreatefrompng($file);
        } else {
            \QRcode::png($value, $file, $errorCorrectionLevel, $matrixPointSize, 3);
            if (!is_file($file)) {
                exit('二维码生成失败');
            }
            $QR = imagecreatefromstring(file_get_contents($file));
            if ($thumb) {
                if (strpos($thumb, 'https://') !== false
                    && strpos($thumb, '/') !== false
                    && strpos($thumb, 'http://') !== false) {
                    exit('图片地址不规范');
                }
                $img = getimagesize($thumb);
                if (!$img) {
                    exit('此图片不是一张可用的图片');
                }
                $code = dr_catcher_data($thumb);
                if (!$code) {
                    exit('图片参数不规范');
                }
                $logo = imagecreatefromstring($code);
                $QR_width = imagesx($QR);//二维码图片宽度
                $logo_width = imagesx($logo);//logo图片宽度
                $logo_height = imagesy($logo);//logo图片高度
                $logo_qr_width = $QR_width / 4;
                $scale = $logo_width/$logo_qr_width;
                $logo_qr_height = $logo_height/$scale;
                $from_width = ($QR_width - $logo_qr_width) / 2;
                //重新组合图片并调整大小
                imagecopyresampled($QR, $logo, (int)$from_width, (int)$from_width, 0, 0, (int)$logo_qr_width, (int)$logo_qr_height, (int)$logo_width, (int)$logo_height);
                imagepng($QR, $file);
            }
        }

        // 输出图片
        ob_start();
        ob_clean();
        header("Content-type: image/png");
        $QR && imagepng($QR);
        exit;
    }

    /**
     * 搜索
     */
    public function search() {

        $dir = dr_safe_filename(\Phpcmf\Service::L('input')->get('dir'));
        if (!$dir) {
            $this->_msg(0, dr_lang('模块参数不能为空'));
        } elseif (!dr_is_module($dir)) {
            $this->goto_404_page(dr_lang('模块[%s]未安装', $dir));
        }

        // 跳转url
        dr_redirect(\Phpcmf\Service::L('Router')->search_url([], 'keyword', dr_safe_replace(\Phpcmf\Service::L('input')->get('keyword')), $dir));
    }

    /**
     * 检查关键字
     */
    public function checktitle() {

        // 获取参数
        $id = (int)\Phpcmf\Service::L('input')->get('id');
        $title = dr_safe_replace(htmlspecialchars((string)\Phpcmf\Service::L('input')->get('title')));
        $module = dr_safe_filename(\Phpcmf\Service::L('input')->get('module'));
        $cache = \Phpcmf\Service::L('cache')->get('module-'.SITE_ID.'-'.$module);

        // 判断参数
        if (!$title || !$module || !$cache) {
            exit('');
        }

        // 判断是否重复存在
        if (\Phpcmf\Service::M()->db->table(dr_module_table_prefix($module))->where('id<>'.$id)->where('title', $title)->countAllResults()) {
            exit(dr_lang('已经有相同的%s存在', isset($cache['field']['title']['name']) ? $cache['field']['title']['name'] : dr_lang('主题')));
        }

        exit('');
    }

    /**
     * 提取关键字
     */
    public function getkeywords() {
        exit(dr_get_keywords(dr_safe_replace(\Phpcmf\Service::L('input')->get('title'))));
    }

    /**
     * 存储临时表单内容
     */
    public function save_form_data() {

        if (IS_POST && $this->member && $this->member['is_admin']) {
            $rt = \Phpcmf\Service::L('cache')->init('file')->save(
                md5(\Phpcmf\Service::L('input')->get('name')),
                \Phpcmf\Service::L('input')->post('data'),
                7200
            );
            var_dump($rt);
        }

        exit;
    }

    /**
     * 删除临时表单内容
     */
    public function delete_form_data() {
        \Phpcmf\Service::L('cache')->init('file')->delete(md5(\Phpcmf\Service::L('input')->get('name', true)));
        $this->_json(1, dr_lang('清除成功'));
        exit;
    }

    /**
     * 验证码
     */
    public function captcha() {

        $code = \Phpcmf\Service::L('captcha')->create(
            max(0, intval($_GET['width'])), max(0, intval($_GET['height']))
        );

        \Phpcmf\Service::L('cache')->set_auth_data('web-captcha-'.USER_HTTP_CODE, $code, SITE_ID);

        exit;
    }

    /**
     * 汉字转换拼音
     */
    public function pinyin() {

        $name = dr_safe_replace(\Phpcmf\Service::L('input')->get('name'));
        if (!$name) {
            exit('');
        }

        $py = \Phpcmf\Service::L('pinyin')->result($name);
        if (strlen($py) > 12) {
            $sx = \Phpcmf\Service::L('pinyin')->result($name, 0);
            if ($sx) {
                exit($sx);
            }
        }

        exit($py);
    }

    /**
     * 联动菜单调用
     */
    public function linkage() {

        $code = dr_safe_replace(\Phpcmf\Service::L('input')->get('code'));
        $linkage = dr_linkage_json($code);
        if (!$linkage) {
            if (CI_DEBUG) {
                $linkage = [
                    [
                        'value' => 0,
                        'label' => '请在联动菜单管理，找到【'.$code.'】，点击一键生成按钮',
                        'children' => [],
                    ]
                ];
            } else {
                $linkage = [];
            }
        }

        echo 'var linkage_'.$code.' ='.json_encode($linkage, JSON_UNESCAPED_UNICODE).';';exit;
    }

    /**
     * Ajax调用字段属性表单
     *
     * @return void
     */
    public function field() {

        $id = (int)\Phpcmf\Service::L('input')->get('id');
        $app = dr_safe_replace(\Phpcmf\Service::L('input')->get('app'));
        $type = dr_safe_replace(\Phpcmf\Service::L('input')->get('type'));

        // 关联表
        \Phpcmf\Service::M('field')->relatedid = dr_safe_replace(\Phpcmf\Service::L('input')->get('relatedid'));
        \Phpcmf\Service::M('field')->relatedname = dr_safe_replace(\Phpcmf\Service::L('input')->get('relatedname'));

        // 获取全部字段
        $all = \Phpcmf\Service::M('field')->get_all_field();
        $data = $id ? $all[$id] : null;
        $value = $data ? $data['setting']['option'] : []; // 当前字段属性信息

        $obj = \Phpcmf\Service::L('field')->app($app);
        if (!$obj) {
            exit(json_encode(['option' => '', 'style' => '']));
        }

        list($option, $style) = $obj->option($type, $value, $all);

        exit(json_encode(['option' => $option, 'style' => $style], JSON_UNESCAPED_UNICODE));
    }

    /**
     * 动态调用模板
     */
    public function template() {

        $app = dr_safe_filename(\Phpcmf\Service::L('input')->get('app'));
        $file = dr_safe_filename(\Phpcmf\Service::L('input')->get('name'));
        $module = dr_safe_filename(\Phpcmf\Service::L('input')->get('module'));

        $data = [
            'app' => $app,
            'file' => $file,
            'module' => $module,
        ];

        if (!$file) {
            $html = 'name不能为空';
        } else {
            if ($module) {
                $this->_module_init($module);
                \Phpcmf\Service::V()->module($module);
            } elseif ($app) {
                \Phpcmf\Service::V()->module($app);
            }

            \Phpcmf\Service::V()->assign(\Phpcmf\Service::L('input')->get('', true));
            ob_start();
            \Phpcmf\Service::V()->display($file);
            $html = ob_get_contents();
            ob_clean();

            $data['call_value'] = \Phpcmf\Service::V()->call_value;
        }

        $this->_json(1, $html, $data);
    }

    /**
     * 内容关联字段数据读取
     */
    public function related() {
        $this->_json(0, dr_lang('需要安装最新版的【内容系统】插件'));
    }

    /**
     * 会员关联字段数据读取
     */
    public function members() {
        $this->_json(0, dr_lang('需要安装最新版的【用户系统】插件'));
    }

    /**
     * 退出前台账号
     */
    public function loginout() {

        // 注销授权登陆的会员
        if ($this->session()->get('member_auth_uid')) {
            \Phpcmf\Service::C()->session()->delete('member_auth_uid');
            $this->_json(0, dr_lang('当前状态无法退出账号'));
        }

        $this->_json(1, dr_lang('您的账号已退出系统'), [
            'url' => isset($_SERVER['HTTP_REFERER']) ? dr_safe_url($_SERVER['HTTP_REFERER']) : SITE_URL,
            'sso' => \Phpcmf\Service::M('member')->logout(),
        ]);
    }

    /**
     * 电脑和手机网站切换处理接口
     */
    public function client() {
        $this->_json(0, dr_lang('此功能已废弃'));
    }

}
