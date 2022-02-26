<?php namespace Phpcmf\Control\Api;
/**
 * {{www.xunruicms.com}}
 * {{迅睿内容管理框架系统}}
 * 本文件是框架系统文件，二次开发时不可以修改本文件
 **/

// 通用接口处理
class Api extends \Phpcmf\Common
{

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
        if (is_file($file)) {
            $QR = imagecreatefrompng($file);
        } else {
            \QRcode::png($value, $file, $errorCorrectionLevel, $matrixPointSize, 3);
            $QR = imagecreatefromstring(file_get_contents($file));
            if ($thumb) {
                $logo = imagecreatefromstring(dr_catcher_data($thumb));
                $QR_width = imagesx($QR);//二维码图片宽度
                $QR_height = imagesy($QR);//二维码图片高度
                $logo_width = imagesx($logo);//logo图片宽度
                $logo_height = imagesy($logo);//logo图片高度
                $logo_qr_width = $QR_width / 4;
                $scale = $logo_width/$logo_qr_width;
                $logo_qr_height = $logo_height/$scale;
                $from_width = ($QR_width - $logo_qr_width) / 2;
                //重新组合图片并调整大小
                imagecopyresampled($QR, $logo, $from_width, $from_width, 0, 0, $logo_qr_width, $logo_qr_height, $logo_width, $logo_height);
                imagepng($QR, $file);
            }
        }

        // 输出图片
        ob_start();
        ob_clean();
        header("Content-type: image/png");
        ImagePng($QR);
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
        $keyword = dr_safe_replace(\Phpcmf\Service::L('input')->get('keyword'));
        // 跳转url
        dr_redirect(\Phpcmf\Service::L('Router')->search_url([], 'keyword', $keyword, $dir));
    }

    /**
     * 检查关键字
     */
    public function checktitle() {

        // 获取参数
        $id = (int)\Phpcmf\Service::L('input')->get('id');
        $title = dr_safe_replace(htmlspecialchars(\Phpcmf\Service::L('input')->get('title')));
        $module = dr_safe_filename(\Phpcmf\Service::L('input')->get('module'));
        $cache = \Phpcmf\Service::L('cache')->get('module-'.SITE_ID.'-'.$module);

        // 判断参数
        if (!$title || !$module || !$cache) {
            exit('');
        }

        // 判断是否重复存在
        if (\Phpcmf\Service::M()->db->table(dr_module_table_prefix($module))->where('id<>', $id)->where('title', $title)->countAllResults()) {
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

        exit();
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

        $pid = (int)\Phpcmf\Service::L('input')->get('parent_id');
        $code = dr_safe_replace(\Phpcmf\Service::L('input')->get('code'));
        $linkage = dr_linkage_list($code, $pid);

        $json = [];
        $html = '';
        foreach ($linkage as $v) {
            if ($v['pid'] == $pid) {
                $json[] = [
                    'region_id' => $v['ii'],
                    'region_code' => $v['id'],
                    'region_name' => $v['name']
                ];
            }
        }

        // 最终linkage
        if (!$json) {
            $mid = dr_safe_filename(\Phpcmf\Service::L('input')->get('mid'));
            $name = dr_safe_filename(\Phpcmf\Service::L('input')->get('file'));
            if ($name) {
                $data = dr_linkage($code, $pid);
                $file = ROOTPATH.'config/mylinkage/'.$name;
                $file2 = dr_get_app_dir($mid).'Config/mylinkage/'.$name;
                if (is_file($file)) {
                    require $file;
                } elseif (is_file($file2)) {
                    require $file2;
                } else {
                    log_message('error', '联动菜单自定义程序文件【'.$name.'】不存在');
                    if (CI_DEBUG) {
                        $html = '联动菜单自定义程序文件【'.$name.'】不存在';
                    }
                }
            }
        }

        echo json_encode([
            'data' => $json,
            'html' => $html,
        ], JSON_UNESCAPED_UNICODE);exit;
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

        // 强制将模板设置为后台
        \Phpcmf\Service::V()->admin();

        // 登陆判断
        /*
        if (!$this->uid) {
            $this->_json(0, dr_lang('会话超时，请重新登录'));
        }*/

        // 参数判断
        $dirname = dr_safe_filename(\Phpcmf\Service::L('input')->get('module'));
        if (!$dirname) {
            $this->_json(0, dr_lang('module参数不存在'));
        }

        // 站点选择
        $site = max(1, (int)\Phpcmf\Service::L('input')->get('site'));
        $pagesize = (int)\Phpcmf\Service::L('input')->get('pagesize');
        if (!$pagesize) {
            $pagesize = 10;
        }

        // 模块缓存判断
        $module = $this->get_cache('module-'.$site.'-'.$dirname);
        if (!$module) {
            $this->_json(0, dr_lang('模块（%s）不存在', $dirname));
        }

        $module['field']['id'] = [
            'name' => 'Id',
            'ismain' => 1,
            'fieldtype' => 'Text',
            'fieldname' => 'id',
        ];

        if (IS_POST) {
            $ids = \Phpcmf\Service::L('input')->get_post_ids();
            if (!$ids) {
                $this->_json(0, dr_lang('没有选择项'));
            }
            $id = [];
            foreach ($ids as $i) {
                $id[] = (int)$i;
            }
            $builder = \Phpcmf\Service::M()->db->table($site.'_'.$dirname);
            $builder->whereIn('id', $id);
            $mylist = $builder->orderBy('updatetime DESC')->get()->getResultArray();
            if (!$mylist) {
                $this->_json(0, dr_lang('没有相关数据'));
            }
            $name = dr_safe_filename(\Phpcmf\Service::L('input')->get('name'));
            if (!$name) {
                $this->_json(0, dr_lang('name参数不能为空'));
            }

            $mid = $dirname;
            $ids = [];
            foreach ($mylist as $t) {
                $ids[] = $t['id'];
            }

            $file = \Phpcmf\Service::V()->code2php(
                file_get_contents(is_file(MYPATH.'View/api_related_field.html') ? MYPATH.'View/api_related_field.html' : COREPATH.'View/api_related_field.html')
            );
            ob_start();
            require $file;
            $code = ob_get_clean();
            $html = explode('<!--list-->', $code);

            $this->_json(1, dr_lang('操作成功'), ['ids' => $ids, 'html' => $html[1]]);
        }

        $my = intval(\Phpcmf\Service::L('input')->get('my'));
        $data = \Phpcmf\Service::L('input')->get('', true);
        $where = [];
        if ($my) {
            $where[] = 'uid = '.$this->uid;
        } elseif ($this->member && $this->member['adminid'] > 0) {
            $module['field']['uid'] = [
                'name' => dr_lang('账号'),
                'ismain' => 1,
                'fieldtype' => 'Text',
                'fieldname' => 'uid',
            ];
        }
        if ($data['search']) {
            $catid = (int)$data['catid'];
            if ($catid && isset($module['category'][$catid]['catids']) && $module['category'][$catid]['catids']) {
                $where[] = '`catid` in('.implode(',', $module['category'][$catid]['catids']).')';
            }
            $data['keyword'] = dr_safe_replace(urldecode($data['keyword']));
            if (isset($data['keyword']) && $data['keyword'] && $data['field'] && isset($module['field'][$data['field']])) {
                $data['keyword'] = dr_safe_replace(urldecode($data['keyword']));
                if ($data['field'] == 'id') {
                    // id搜索
                    $id = [];
                    $ids = explode(',', $data['keyword']);
                    foreach ($ids as $i) {
                        $id[] = (int)$i;
                    }
                    $where[] = 'id in('.implode(',', $id).')';
                } else if ($data['field'] == 'uid') {
                    $uid = \Phpcmf\Service::M('member')->uid($data['keyword']);
                    $where[] = 'uid = '.intval($uid);
                } else {
                    // 其他模糊搜索
                    $where[] = $data['field'].' LIKE "%'.$data['keyword'].'%"';
                }
            }
        }

        sort($module['field']);
        $rules = $data;
        $rules['page'] = '{page}';

        \Phpcmf\Service::V()->assign(array(
            'mid' => $dirname,
            'site' => $site,
            'param' => $data,
            'field' => $module['field'],
            'where' => $where ? urlencode(implode(' AND ', $where)) : '',
            'search' => dr_form_search_hidden(['search' => 1, 'is_ajax' => 1, 'module' => $dirname, 'site' => $site, 'my' => $my, 'pagesize' => $pagesize]),
            'select' => \Phpcmf\Service::L('tree')->select_category(
                $module['category'],
                $data['catid'],
                'name="catid"',
                '--'
            ),
            'urlrule' => dr_url('api/api/related', $rules, '/index.php'),
            'category' => $module['category'],
            'pagesize' => $pagesize,
        ));
        \Phpcmf\Service::V()->display('api_related.html');
    }

    /**
     * 会员关联字段数据读取
     */
    public function members() {

        if (!IS_USE_MEMBER) {
            $this->_json(0, dr_lang('需要安装【用户系统】插件'));
        }

        // 强制将模板设置为后台
        \Phpcmf\Service::V()->admin();

        // 登陆判断
        /*
        if (!$this->uid) {
            $this->_json(0, dr_lang('会话超时，请重新登录'));
        }*/

        $field = array(
            'id' => array(
                'ismain' => 1,
                'name' => 'Uid',
                'fieldname' => 'id',
                'fieldtype' => 'Text',
            ),
            'username' => array(
                'ismain' => 1,
                'name' => dr_lang('账号'),
                'fieldname' => 'username',
                'fieldtype' => 'Text',
            ),
            'email' => array(
                'ismain' => 1,
                'name' => dr_lang('邮箱'),
                'fieldname' => 'email',
                'fieldtype' => 'Text',
            ),
            'phone' => array(
                'ismain' => 1,
                'name' => dr_lang('手机'),
                'fieldname' => 'phone',
                'fieldtype' => 'Text',
            ),
            'name' => array(
                'ismain' => 1,
                'name' => dr_lang('姓名'),
                'fieldname' => 'name',
                'fieldtype' => 'Text',
            ),
        );

        if (IS_POST) {
            $ids = \Phpcmf\Service::L('input')->get_post_ids();
            if (!$ids) {
                $this->_json(0, dr_lang('没有选择项'));
            }
            $id = [];
            foreach ($ids as $i) {
                $id[] = (int)$i;
            }
            $builder = \Phpcmf\Service::M()->db->table('member');
            $builder->whereIn('id', $id);
            $mylist = $builder->orderBy('id DESC')->get()->getResultArray();
            if (!$mylist) {
                $this->_json(0, dr_lang('没有相关数据'));
            }
            $name = dr_safe_filename(\Phpcmf\Service::L('input')->get('name'));
            if (!$name) {
                $this->_json(0, dr_lang('name参数不能为空'));
            }

            $ids = [];
            foreach ($mylist as $t) {
                $ids[] = $t['id'];
            }

            $file = \Phpcmf\Service::V()->code2php(
                file_get_contents(is_file(MYPATH.'View/api_members_field.html') ? MYPATH.'View/api_members_field.html' : COREPATH.'View/api_members_field.html')
            );
            ob_start();
            require $file;
            $code = ob_get_clean();
            $html = explode('<!--list-->', $code);

            $this->_json(1, dr_lang('操作成功'), ['ids' => $ids, 'html' => $html[1]]);
        }

        $data = \Phpcmf\Service::L('input')->get('', true);
        $where = [];
        if ($data['group']) {
            // 指定用户组时
            $ids = explode(',', (string)$data['group']);
            if ($ids) {
                $arr = [];
                foreach ($ids as $gid) {
                    $gid = intval($gid);
                    if ($gid) {
                        $arr[] = $gid;
                    }
                }
                if ($arr) {
                    $where[] = '`id` IN (select uid from `'.\Phpcmf\Service::M()->dbprefix('member_group_index').'` where gid in('.implode(',', $arr).'))';
                }
            }
            $group = [];
        } else {
            $group = $this->member_cache['group'];
        }

        if ($data['search']) {
            $gid = (int)$data['groupid'];
            if ($gid) {
                $where[] = '`id` IN (select uid from `'.\Phpcmf\Service::M()->dbprefix('member_group_index').'` where gid='.$gid.')';
            }
            $data['keyword'] = dr_safe_replace(urldecode($data['keyword']));
            if (isset($data['keyword']) && $data['keyword'] && $data['field'] && isset($field[$data['field']])) {
                $data['keyword'] = dr_safe_replace(urldecode($data['keyword']));
                if ($data['field'] == 'id') {
                    // id搜索
                    $id = [];
                    $ids = explode(',', $data['keyword']);
                    foreach ($ids as $i) {
                        $id[] = (int)$i;
                    }
                    $where[] = 'id in('.implode(',', $id).')';
                } else {
                    // 其他模糊搜索
                    $where[] = $data['field'].' LIKE "%'.$data['keyword'].'%"';
                }
            }
        }

        $rules = $data;
        $rules['page'] = '{page}';

        \Phpcmf\Service::V()->assign(array(
            'where' => $where ? urlencode(implode(' AND ', $where)) : '',
            'param' => $data,
            'field' => $field,
            'group' => $group,
            'search' => dr_form_search_hidden(['search' => 1, 'is_ajax' => 1]),
            'urlrule' => dr_url('api/api/members', $rules, '/index.php'),
        ));
        \Phpcmf\Service::V()->display('api_members.html');
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
