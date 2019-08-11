<?php namespace Phpcmf\Model;

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

            case 'weibo':

                // 同步微博
                $value = dr_string2array($cron['value']);
                // 请求参数
                require_once FCPATH.'ThirdParty/Weibo/saetv2.ex.class.php';

                $siteid = max(1, intval($cron['site']));
                $config = \Phpcmf\Service::L('cache')->init_file('weibo')->get_file($siteid);
                ($siteid > 1 && $config['share'] == 1) && $config = \Phpcmf\Service::L('cache')->init_file('weibo')->get_file(1);

                // 初始化类
                $cache = \Phpcmf\Service::C()->get_cache('site', $siteid, 'weibo');
                $auth = new \SaeTClientV2($cache['key'], $cache['secret'], $config['access_token']);

                $call = $auth->upload(dr_strcut($value['content'], 250).' '.$value['url'], $value['image']);

                // 加入队列并执行
                if (isset($call['error']) && $call['error']) {
                    return $this->save_cron($cron, [
                        'error' => $call['error'].' ('.$call['error_code'].')',
                        'value' => $value,
                    ]);
                } else {
                    return $this->save_cron($cron, [
                        'error' => '',
                        'value' => '',
                    ]);
                }

                break;

            default:
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
            \Phpcmf\Service::L('thread')->cron(['action' => 'cron', 'id' => $cron['id'] ]);
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