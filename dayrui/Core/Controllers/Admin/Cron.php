<?php namespace Phpcmf\Controllers\Admin;

/**
 * http://www.xunruicms.com
 * 本文件是框架系统文件，二次开发时不可以修改本文件
 **/

// 任务队列
class Cron extends \Phpcmf\Table
{

    private $type;

    public function __construct(...$params)
    {
        parent::__construct(...$params);
        \Phpcmf\Service::V()->assign([
            'menu' => \Phpcmf\Service::M('auth')->_admin_menu(
                [
                    '任务管理' => [\Phpcmf\Service::L('Router')->class.'/index', 'fa fa-indent'],
                    '定义任务类型' => [\Phpcmf\Service::L('Router')->class.'/add', 'fa fa-plus'],
                    'help' => [353],
                ]
            )
        ]);
        // 表单显示名称
        $this->name = dr_lang('任务队列');
        $this->is_data = 0;
        // 初始化数据表
        $this->_init([
            'table' => 'cron',
            'field' => [
                'type' => [
                    'ismain' => 1,
                    'fieldtype' => 'Text',
                    'fieldname' => 'type',
                ],
            ],
            'date_field' => 'inputtime',
            'order_by' => 'id desc,status asc',
        ]);
        // 任务类别
        $this->type = [
            //'weibo' => dr_lang('微博分享'),
            'email' => dr_lang('邮件发送'),
            'notice' => dr_lang('消息通知'),
            'ueditor_down_img' => dr_lang('远程图片'),
        ];
    }

    // 任务管理
    public function index() {

        list($tpl, $data) = $this->_List();
        if ($data['list']) {
            foreach ($data['list'] as $i => $t) {
                $data['list'][$i]['value'] = ('<pre>'.str_replace([PHP_EOL, "'", '"'], ["<br>", "", ""], var_export(dr_string2array($t['value']), true)).'</pre>');
                $t['error'] && $data['list'][$i]['error'] = ('<pre>'.str_replace([PHP_EOL, "'", '"'], ["<br>", "", ""], var_export(dr_string2array($t['error']), true)).'</pre>');
            }
        }

        $json = '';
        if (is_file(WRITEPATH.'config/cron.php')) {
            require WRITEPATH.'config/cron.php';
        }
        $my = json_decode($json, true);
        if ($my) {
            foreach ($my as $t) {
                if ($t['name'] && $t['code']) {
                    $this->type[$t['code']] = $t['name'];
                }
            }
        }

        \Phpcmf\Service::V()->assign([
            'type' => $this->type,
            'list' => $data['list']
        ]);
        \Phpcmf\Service::V()->display($tpl);
    }

    public function show() {

        list($tpl, $data) = $this->_Show(\Phpcmf\Service::L('input')->get('id'));

        \Phpcmf\Service::V()->assign([
            'show_error' => var_export(dr_string2array($data['error']), true),
            'show_value' => var_export(dr_string2array($data['value']), true),
        ]);
        \Phpcmf\Service::V()->display($tpl);exit;
    }

    // 后台删除任务
    public function del() {
        $this->_Del(
            \Phpcmf\Service::L('input')->get_post_ids(),
            null,
            null,
            \Phpcmf\Service::M()->dbprefix($this->init['table'])
        );
    }


    // 执行任务
    public function post_add() {

        $ids = \Phpcmf\Service::L('input')->get_post_ids();
        if ($ids) {
            foreach ($ids as $id) {
                \Phpcmf\Service::M('cron')->do_cron_id($id);
                //\Phpcmf\Service::L('thread')->cron(['action' => 'cron', 'id' => $id ], 1);
            }
            $this->_json(1, dr_lang('任务已提交，等待执行结果'));
        } else {
            $this->_json(0, dr_lang('所选数据不存在'));
        }
    }

    // 单个执行任务
    public function do_add() {

        $id = (int)\Phpcmf\Service::L('input')->get('id');
		if (!$id) {
			$this->_json(0, dr_lang('所选数据不存在'));
		}
		
        $rt = \Phpcmf\Service::M('cron')->do_cron_id($id);
		if (!$rt['code']) {
			$this->_json(0, $rt['msg']);
		}
		
        $this->_json(1, dr_lang('任务执行完成'));
    }


    // 任务类型
    public function add() {

        $json = '';
        if (is_file(WRITEPATH.'config/cron.php')) {
            require WRITEPATH.'config/cron.php';
        }

        $data = json_decode($json, true);

        if (IS_AJAX_POST) {

            $post = \Phpcmf\Service::L('input')->post('data', true);

            file_put_contents(WRITEPATH.'config/cron.php',
                '<?php defined(\'FCPATH\') OR exit(\'No direct script access allowed\');'.PHP_EOL.' $json=\''.json_encode($post).'\';');

            \Phpcmf\Service::L('input')->system_log('设置自定义任务类型');

            $this->_json(1, dr_lang('操作成功'));
        }

        \Phpcmf\Service::V()->assign([
            'data' => $data,
        ]);
        \Phpcmf\Service::V()->display('cron_add.html');
    }

    // 类型测试
    public function test_index() {

        $code = \Phpcmf\Service::L('input')->get('id');
        if (!$code) {
            $this->_json(0, dr_lang('代码不能为空'));
        } elseif (isset($this->type[$code])) {
            $this->_json(0, dr_lang('代码名称被系统占用，请更换代码名称'));
        }

        if (!function_exists('my_cron_'.$code)) {
            $this->_json(0, dr_lang('自定义业务操作函数[%s]未定义', 'my_cron_'.$code));
        }

        $rt = \Phpcmf\Service::M('cron')->add_cron(SITE_ID, $code, [
            'code1' => '参数1',
            'code2' => '参数2',
            'code3' => '参数3',
        ]);
        if (!$rt['code']) {
            $this->_json(0, $rt['msg']);
        }

        $this->_json(1, dr_lang('测试成功'));
    }


}
