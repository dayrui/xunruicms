<?php namespace Phpcmf\Model;

/**
 * http://www.xunruicms.com
 * 本文件是框架系统文件，二次开发时不可以修改本文件，可以通过继承类方法来重写此文件
 **/


class Cron extends \Phpcmf\Model
{
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

        // 运行任务
        $rt['code'] && \Phpcmf\Service::L('thread')->cron(['action' => 'cron', 'id' => $rt['code'] ]);

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
            return dr_return_data(0, $cron['id'].'#'.(is_array($data['error']) ? implode('、', $data['error']) : $data['error']));
        }
    }

    // 执行本任务
    public function do_cron($cron) {

        switch ($cron['type']) {

            case 'notice':
                // 通知消息
                $value = dr_string2array($cron['value']);
                list($error, $value) = \Phpcmf\Service::L('notice')->cron_notice($cron['site'], $value);

                // 加入队列并执行
                return $this->save_cron($cron, [
                    'error' => $error,
                    'value' => $value,
                ]);
                break;

            case 'ueditor_down_img':
                // 编辑器下载图片
                $img = ROOT_THEME_PATH.'assets/images/down_img.jpg?id='.$cron['id'];
                $value = dr_string2array($cron['value']);
                // 下载远程文件
                $rt = \Phpcmf\Service::L('upload')->down_file($value);
                if ($rt['code']) {
                    \Phpcmf\Service::M('attachment')->check($value['member'] ? $value['member'] : $this->member, $value['siteid'] ? $value['siteid'] : 1);
                    $att = \Phpcmf\Service::M('attachment')->save_data($rt['data'], 'ueditor_down_img');
                    if ($att['code']) {
                        // 归档成功
                        // $rt['data']['url']
                        if (strpos($value['table'], '{tableid}') !== false) {
                            for ($i = 0; $i < 200; $i ++) {
                                $table = $this->dbprefix(str_replace('{tableid}', $i, $value['table']));
                                if (!$this->db->query("SHOW TABLES LIKE '".$table."'")->getRowArray()) {
                                    break;
                                }
                                // 替换
                                $replace = '`'.$value['field'].'`=REPLACE(`'.$value['field'].'`, \''.addslashes($img).'\', \''.addslashes($rt['data']['url']).'\')';
                                \Phpcmf\Service::M()->db->query('UPDATE `'.$table.'` SET '.$replace);
                            }
                        } else {
                            $table = $this->dbprefix($value['table']);
                            if ($this->db->query("SHOW TABLES LIKE '".$table."'")->getRowArray()) {
                                $replace = '`'.$value['field'].'`=REPLACE(`'.$value['field'].'`, \''.addslashes($img).'\', \''.addslashes($rt['data']['url']).'\')';
                                \Phpcmf\Service::M()->db->query('UPDATE `'.$table.'` SET '.$replace);
                            }
                        }
                    }
                } else {
                    return $this->save_cron($cron, [
                        'error' => '远程图片下载失败：'.$rt['msg'],
                        'value' => $value,
                    ]);
                }
                return $this->save_cron($cron, [
                    'error' => '',
                    'value' => '',
                ]);
                break;

            default:

                // 尝试自定义类别
                $json = '';
                if (is_file(WRITEPATH.'config/cron.php')) {
                    require WRITEPATH.'config/cron.php';
                }
                $my = json_decode($json, true);
                if ($my) {
                    foreach ($my as $t) {
                        if ($t['name'] && $t['code'] == $cron['type']) {
                            // 找到了
                            if (function_exists('my_cron_'.$cron['type'])) {
                                $value = dr_string2array($cron['value']);
                                $rt = call_user_func_array('my_cron_'.$cron['type'], [$value]);
                                if (!$rt['code']) {
                                    // 失败
                                    return $this->save_cron($cron, [
                                        'error' => '任务['.$t['name'].']执行失败：'.$rt['msg'],
                                        'value' => $value,
                                    ]);
                                } else {
                                    // 成功
                                    return $this->save_cron($cron, [
                                        'error' => '',
                                        'value' => '',
                                    ]);
                                }
                            }
                        }
                    }
                }

                $this->table('cron')->delete($cron['id']);
                log_message('error', '任务查询（'.$cron['id'].'）类型【'.$cron['type'].'】不存在：'.FC_NOW_URL);
                return dr_return_data(0, '任务查询（'.$cron['id'].'）类型【'.$cron['type'].'】不存在');
                break;
        }

    }

    // 执行本任务
    public function do_cron_id($id) {
        $cron = $this->table('cron')->get($id);
        return $this->do_cron($cron);
    }

    // 运行脚本
    public function run_cron() {

        $crons = $this->table('cron')->getAll(20);
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

        // 遍历文件
        if ($fp = @opendir(WRITEPATH.'authcode/')) {
            while (FALSE !== ($file = readdir($fp))) {
                if ($file === '.' OR $file === '..'
                    OR $file[0] === '.'
                    OR !@is_file(WRITEPATH.'authcode/'.$file)) {
                    continue;
                }
                if (SYS_TIME - filemtime(WRITEPATH.'authcode/'.$file) > 3600 * 24) {
                    // 超过24小时删除
                    @unlink(WRITEPATH.'authcode/'.$file);
                }
            }
            closedir($fp);
        }

        return dr_count($crons);

    }

    // 删除静态网页文件
    public function clear_html_file($url, $time) {

        $file = WRITEPATH.'html/'.md5($url).'.html';
        if (!is_file($file)) {
            return;
        }

        if (filemtime($file) + $time * 3600 < SYS_TIME) {
            unlink($file);
        }
    }

}