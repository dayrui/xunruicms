<?php namespace Phpcmf\Controllers\Admin;

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



// URL规则
class Urlrule extends \Phpcmf\Table
{
    public $type;

    public function __construct(...$params)
    {
        parent::__construct(...$params);
        $this->type = array(
            0 => dr_lang('自定义页面'),
            4 => dr_lang('关键词库'),
            1 => dr_lang('独立模块'),
            2 => dr_lang('共享模块'),
            3 => dr_lang('共享栏目'),
            //5 => dr_lang('模块表单'),
        );
        if (!dr_is_app('page')) {
            unset($this->type[0]);
        }
        if (!dr_is_app('tag')) {
            unset($this->type[4]);
        }
        \Phpcmf\Service::V()->assign([
            'menu' => \Phpcmf\Service::M('auth')->_admin_menu(
                [
                    'URL规则' => [\Phpcmf\Service::L('Router')->class.'/index', 'fa fa-link'],
                    '添加' => [\Phpcmf\Service::L('Router')->class.'/add', 'fa fa-plus'],
                    '修改' => ['hide:'.\Phpcmf\Service::L('Router')->class.'/edit', 'fa fa-edit'],
                    '伪静态' => [\Phpcmf\Service::L('Router')->class.'/rewrite_index', 'fa fa-cog'],
                    '导入' => ['add:urlrule/import_add', 'fa fa-sign-in', '60%', '70%'],
                    'help' => [418],
                ]
            ),
        ]);
        // 支持附表存储
        $this->is_data = 0;
        $this->my_field = array(
            'name' => array(
                'ismain' => 1,
                'name' => dr_lang('名称'),
                'fieldname' => 'name',
                'fieldtype' => 'Text',
                'setting' => array(
                    'option' => array(
                        'width' => 200,
                    ),
                    'validate' => array(
                        'required' => 1,
                    )
                )
            ),
        );
        // url显示名称
        $this->name = dr_lang('URL规则');
        // 初始化数据表
        $this->_init([
            'table' => 'urlrule',
            'field' => $this->my_field,
            'order_by' => 'id desc',
        ]);
    }

    // 后台查看url列表
    public function index() {
        
        $this->_List([], -1);
        \Phpcmf\Service::V()->assign('color', [
            0 => 'default',
            1 => 'info',
            2 => 'success',
            3 => 'warning',
            4 => 'danger',
            5 => '',
            6 => 'primary',
        ]);
        \Phpcmf\Service::V()->display('urlrule_index.html');
    }

    // 伪静态
    public function rewrite_index() {

        $name = $code = $note = '';
        $server = strtolower($_SERVER['SERVER_SOFTWARE']);

        if (strpos($server, 'apache') !== FALSE) {
            $name = 'Apache';
            $note = '<font color=red><b>将以下内容保存为.htaccess文件，放到网站根目录</b></font>';
            $code = 'RewriteEngine On'.PHP_EOL
                .'RewriteBase /'.PHP_EOL
                .'RewriteCond %{REQUEST_FILENAME} !-f'.PHP_EOL
                .'RewriteCond %{REQUEST_FILENAME} !-d'.PHP_EOL
                .'RewriteRule !.(js|ico|gif|jpe?g|bmp|png|css)$ /index.php [NC,L]';
        } elseif (strpos($server, 'iis/7') !== FALSE || strpos($server, 'iis/8') !== FALSE) {
            $name = $server;
            $note = '<font color=red><b>将以下内容保存为Web.config文件，放到网站根目录</b></font>';
            $code = '<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL
                .'<configuration>'.PHP_EOL
                .'    <system.webServer>'.PHP_EOL
                .'        <rewrite>'.PHP_EOL
                .'            <rules>'.PHP_EOL
                .'		<rule name="finecms" stopProcessing="true">'.PHP_EOL
                .'		    <match url="^(.*)$" />'.PHP_EOL
                .'		    <conditions logicalGrouping="MatchAll">'.PHP_EOL
                .'		        <add input="{HTTP_HOST}" pattern="^(.*)$" />'.PHP_EOL
                .'		        <add input="{REQUEST_FILENAME}" matchType="IsFile" negate="true" />'.PHP_EOL
                .'		        <add input="{REQUEST_FILENAME}" matchType="IsDirectory" negate="true" />'.PHP_EOL
                .'		    </conditions>'.PHP_EOL
                .'		    <action type="Rewrite" url="index.php" /> '.PHP_EOL
                .'                </rule>'.PHP_EOL
                .'            </rules>'.PHP_EOL
                .'        </rewrite>'.PHP_EOL
                .'    </system.webServer> '.PHP_EOL
                .'</configuration>';
        } elseif (strpos($server, 'iis/6') !== FALSE) {
            $name = $server;
            $note = '建议使用isapi_rewrite第三版,老版本的rewrite不支持RewriteCond语法<br><font color=red><b>将以下内容保存为.htaccess文件，放到网站根目录</b></font>';
            $code = 'RewriteEngine On'.PHP_EOL
                .'RewriteBase /'.PHP_EOL
                .'RewriteCond %{REQUEST_FILENAME} !-f'.PHP_EOL
                .'RewriteCond %{REQUEST_FILENAME} !-d'.PHP_EOL
                .'RewriteRule !.(js|ico|gif|jpe?g|bmp|png|css)$ /index.php';
        } elseif (strpos($server, 'nginx') !== FALSE) {
            $name = $server;
            $note = '<font color=red><b>将以下代码放到Nginx配置文件中去（如果是绑定了域名，所绑定目录也要配置下面的代码），您懂得！</b></font>';
            $code = 'location / { '.PHP_EOL
                .'    if (-f $request_filename) {'.PHP_EOL
                .'           break;'.PHP_EOL
                .'    }'.PHP_EOL
                .'    if ($request_filename ~* "\.(js|ico|gif|jpe?g|bmp|png|css)$") {'.PHP_EOL
                .'        break;'.PHP_EOL
                .'    }'.PHP_EOL
                .'    if (!-e $request_filename) {'.PHP_EOL
                .'        rewrite . /index.php last;'.PHP_EOL
                .'    }'.PHP_EOL
                .'}';
        } else {
            $name = $server;
            $note = '<font color=red><b>当前服务器不提供伪静态规则，请自己将所有页面定向到index.php文件</b></font>';
        }

        \Phpcmf\Service::V()->assign([
            'name' => $name,
            'code' => $code,
            'note' => $note,
            'count' => $code ? dr_count(explode(PHP_EOL, $code)) : 0,
        ]);
        \Phpcmf\Service::V()->display('urlrule_rewrite.html');
    }

    // 生成伪静态解析文件规则
    public function rewrite_add() {
        $rt = \Phpcmf\Service::L('router')->get_rewrite_code();
        $this->_json($rt['code'], $rt['msg'], $rt['data']);
    }

    // 后台添加url内容
    public function add() {
        $this->_Post(0);
        \Phpcmf\Service::V()->display('urlrule_add.html');
    }

    // 后台修改url内容
    public function edit() {
        $this->_Post(intval(\Phpcmf\Service::L('input')->get('id')));
        \Phpcmf\Service::V()->display('urlrule_add.html');
    }

    // 复制url
    public function copy_edit() {

        $id = intval(\Phpcmf\Service::L('input')->get('id'));
        $data = \Phpcmf\Service::M()->db->table('urlrule')->where('id', $id)->get()->getRowArray();
        !$data && $this->_josn(0, dr_lang('数据#%s不存在', $id));

        unset($data['id']);
        $data['name'].= '_copy';

        $rt = \Phpcmf\Service::M()->table('urlrule')->insert($data);

        !$rt['code'] && $this->_json(0, dr_lang($rt['msg']));
        \Phpcmf\Service::M('cache')->sync_cache('urlrule');
        $this->_json(1, dr_lang('复制成功'));
    }


    // 保存
    protected function _Save($id = 0, $data = [], $old = [], $func = null, $func2 = null) {
        return parent::_Save($id, $data, $old, function($id, $data){
            // 保存前的格式化
            $type = (int)\Phpcmf\Service::L('input')->post('type');
            $value = \Phpcmf\Service::L('input')->post('value');
            $data[1]['type'] = $type;
            $value[$type]['catjoin'] = \Phpcmf\Service::L('input')->post('catjoin') ? \Phpcmf\Service::L('input')->post('catjoin') : '/';
            $data[1]['value'] = dr_array2string($value[$type]);
            return dr_return_data(1, 'ok', $data);
        }, function ($id, $data, $old) {

            \Phpcmf\Service::M('cache')->sync_cache('urlrule');
        });
    }

    // 导出
    public function export_edit() {

        $id = intval(\Phpcmf\Service::L('input')->get('id'));
        $data = \Phpcmf\Service::M()->table('urlrule')->get($id);
        !$data && $this->_admin_msg(0, dr_lang('URL规则（%s）不存在', $id));

        \Phpcmf\Service::V()->assign([
            'data' => dr_array2string($data),
        ]);
        \Phpcmf\Service::V()->display('form_export.html');exit;
    }

    // 导入
    public function import_add() {

        if (IS_AJAX_POST) {
            $data = \Phpcmf\Service::L('input')->post('code', true);
            $data = dr_string2array($data);
            if (!is_array($data)) {
                $this->_json(0, dr_lang('导入信息验证失败'));
            } elseif (!$data['value']) {
                $this->_json(0, dr_lang('导入信息不完整'));
            }
            unset($data['id']);
            $rt = \Phpcmf\Service::M()->table('urlrule')->insert($data);
            !$rt['code'] && $this->_json(0, $rt['msg']);
            \Phpcmf\Service::M('cache')->sync_cache('urlrule');
            exit($this->_json(1, dr_lang('操作成功')));
        }

        \Phpcmf\Service::V()->assign([
            'form' => dr_form_hidden()
        ]);
        \Phpcmf\Service::V()->display('form_import.html');
        exit;
    }

    /**
     * 获取内容
     * $id      内容id,新增为0
     * */
    protected function _Data($id = 0) {

        $data = parent::_Data($id);
        $data['value'] = dr_string2array($data['value']);
        return $data;
    }

    // 后台删除url内容
    public function del() {
        $this->_Del(
            \Phpcmf\Service::L('input')->get_post_ids(),
            null,
            function ($r) {
                \Phpcmf\Service::M('cache')->sync_cache('urlrule');
            }
        );
    }

}
