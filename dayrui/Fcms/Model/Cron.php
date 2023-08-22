<?php namespace Phpcmf\Model;
/**
 * {{www.xunruicms.com}}
 * {{迅睿内容管理框架系统}}
 * 本文件是框架系统文件，二次开发时不可以修改本文件，可以通过继承类方法来重写此文件
 **/

class Cron extends \Phpcmf\Model {

    // 入队任务
    public function add_cron($site, $type, $data) {

        $rt = $this->table('cron')->insert([
            'site' => $site,
            'type' => $type,
            'value' => dr_array2string($data),
            'status' => 0,
            'error' => '',
            'updatetime' => 0,
            'inputtime' => SYS_TIME,
        ]);

        // 当服务器开启了自动任务，那么只入库不运行任务；未开启时直接执行
        if ($rt['code'] && !is_file(WRITEPATH.'config/run_lock.php')) {
            \Phpcmf\Service::L('thread')->cron(['action' => 'cron', 'id' => $rt['code'] ]);
        }

        return $rt;
    }

    // 存储renw
    public function save_cron($cron, $data) {

        if ((!$data['value'] && !$data['error']) || $cron['status'] >= 9) {
            // 没有错 或者 超时 就删除记录
            $this->table('cron')->delete($cron['id']);
            return dr_return_data(1, 'ok');
        } else {
            $this->table('cron')->update($cron['id'], [
                'value' => dr_array2string($data['value']),
                'error' => dr_array2string($data['error']),
                'status' => $cron['status'] + 1,
                'updatetime' => SYS_TIME,
            ]);
            return dr_return_data(0, $cron['id'].'#'.(is_array($data['error']) ? implode('、', $data['error']) : $data['error']), $cron);
        }
    }

    // 执行本任务
    public function do_cron($cron) {

        $value = dr_string2array($cron['value']);
        switch ($cron['type']) {

            case 'notice':
                // 通知消息
                list($error, $value) = \Phpcmf\Service::L('notice')->cron_notice($cron['site'], $value);

                // 加入队列并执行
                return $this->save_cron($cron, [
                    'error' => $error,
                    'value' => $value,
                ]);
                break;

            default:

                if (!$cron['type']) {
                    log_message('debug', '任务查询（'.$cron['id'].'）类型type不存在');
                    return dr_return_data(0, '任务查询（'.$cron['id'].'）类型type不存在');
                }

                if (strpos($cron['type'], '::')) {
                    list($app, $name) = explode('::', $cron['type']);
                    if (dr_is_app($app)) {
                        $file = dr_get_app_dir($app).'Cron/'.ucfirst($name).'.php';
                    } else {
                        log_message('debug', '任务查询（'.$cron['id'].'）类型【'.$cron['type'].'】插件不存在：'.$app);
                        return dr_return_data(0, '任务查询（'.$cron['id'].'）类型【'.$cron['type'].'】插件不存在');
                    }
                } else {
                    $file = MYPATH.'Cron/'.ucfirst($cron['type']).'.php';
                }

                if (is_file($file)) {
                    $error = require $file;
                    // 加入队列并执行
                    return $this->save_cron($cron, [
                        'error' => $error,
                        'value' => $error ? $value : [],
                    ]);
                } else {
                    log_message('debug', '任务查询（'.$cron['id'].'）类型【'.$cron['type'].'】程序文件不存在：'.$file);
                    return dr_return_data(0, '任务查询（'.$cron['id'].'）类型【'.$cron['type'].'】程序文件不存在');
                }

                break;
        }

    }

    // 执行本任务
    public function do_cron_id($id) {
        $cron = $this->table('cron')->get($id);
        return $this->do_cron($cron);
    }

    // 运行脚本
    public function run_cron($num = 20) {

        $crons = $this->table('cron')->getAll($num ? $num : 20);
        if (!$crons) {
            return 0;
        }

        foreach ($crons as $cron) {
            if (isset($_GET['is_cdn'])) {
                $this->do_cron($cron);
            } else {
                \Phpcmf\Service::L('thread')->cron(['action' => 'cron', 'id' => $cron['id'] ]);
            }
        }

        return dr_count($crons);

    }

}