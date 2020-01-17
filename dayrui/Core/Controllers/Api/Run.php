<?php namespace Phpcmf\Controllers\Api;

/**
 * http://www.xunruicms.com
 * 本文件是框架系统文件，二次开发时不可以修改本文件
 **/

/**
 * 运行任务接口
 */

class Run extends \Phpcmf\Common
{

	public function index() {

        // 未到时间
        if (\Phpcmf\Service::L('input')->get_cookie('cron')) {
            exit('未到执行时间');
        }

        // 批量执行站点动作
        foreach ($this->site_info as $siteid => $site) {
            // 删除网站首页
            if ($site['SITE_INDEX_HTML']) {
                @unlink(\Phpcmf\Service::L('html')->get_webpath($siteid,'site', 'index.html'));
                @unlink(\Phpcmf\Service::L('html')->get_webpath($siteid,'site', 'mobile/index.html'));
            }
            // 模块
            $module = \Phpcmf\Service::L('cache')->get('module-'.$siteid.'-content');
            if ($module) {
                foreach ($module as $dir => $mod) {
                    // 删除模块首页
                    if ($mod['is_index_html']) {
                        if ($mod['domain']) {
                            // 绑定域名时
                            $file = 'index.html';
                        } else {
                            $file = ltrim(\Phpcmf\Service::L('Router')->remove_domain(MODULE_URL), '/'); // 从地址中获取要生成的文件名;
                        }
                        if ($file) {
                            @unlink(\Phpcmf\Service::L('html')->get_webpath($siteid, $dir, $file));
                            @unlink(\Phpcmf\Service::L('html')->get_webpath($siteid, $dir, 'mobile/'.$file));
                        }
                    }
                    // 定时发布动作
                    $this->_module_init($dir, $siteid);
                    $times = $this->content_model->table($siteid.'_'.$dir.'_time')->where('posttime < '.SYS_TIME)->getAll();
                    if ($times) {
                        foreach ($times as $t) {
                            $rt = $this->content_model->post_time($t);
                            if (!$rt['code']) {
                                log_message('error', '定时发布（'.$t['id'].'）失败：'.$rt['msg']);
                            }
                        }
                    }
                }
            }

        }

        // 执行队列
        $i = \Phpcmf\Service::M('cron')->run_cron();

        // 3天未付款的清理
        \Phpcmf\Service::M('pay')->clear_paylog();

        // 100秒调用本程序
        \Phpcmf\Service::L('input')->set_cookie('cron', 1, 100);

        // 任务计划
        \Phpcmf\Hooks::trigger('cron');

        // 项目计划
        if (is_file(MYPATH.'Config/Cron.php')) {
            require MYPATH.'Config/Cron.php';
        }

        // 为插件单独执行计划
        $local = dr_dir_map(dr_get_app_list(), 1);
        if ($local) {
            foreach ($local as $dir) {
                $path = dr_get_app_dir($dir);
                if (is_file($path.'Config/Cron.php')
                    && is_file($path.'Config/App.php')) {
                    require $path.'Config/Cron.php';
                }
            }
        }
		
        exit('Run '.$i);
	}


    /**
     * 线程任务接口
     */
	public function cron() {

        $file = WRITEPATH.'thread/'.dr_safe_filename(\Phpcmf\Service::L('input')->get('auth')).'.auth';
        if (!is_file($file)) {
            log_message('error', '线程任务auth文件不存在：'.FC_NOW_URL);
            exit('线程任务auth文件不存在'.$file);
        }

        $time = (int)file_get_contents($file);
        @unlink($file);
        if (SYS_TIME - $time > 500) {
            // 500秒外无效
            log_message('error', '线程任务auth过期：'.FC_NOW_URL);
            exit('线程任务auth过期');
        }

        switch (\Phpcmf\Service::L('input')->get('action')) {

            case 'oauth_down_avatar': // 快捷登录下载头像

                $id = intval(\Phpcmf\Service::L('input')->get('id'));
                $oauth = \Phpcmf\Service::M()->table('member_oauth')->get($id);
                if (!$oauth) {
					exit('oauth不存在');
				} elseif (!$oauth['uid']) {
					exit('oauth没有绑定账号');
				}
				
                foreach (['png', 'jpg', 'gif', 'jpeg'] as $ext) {
                    if (is_file(ROOTPATH.'api/member/'.$oauth['uid'].'.'.$ext)) {
                        exit('头像已经存在');
                    }
                }

                $avatar = dr_catcher_data($oauth['avatar']);
                if ($avatar) {
                    @file_put_contents(ROOTPATH.'api/member/'.$oauth['uid'].'.jpg', $avatar);
                }

                if (is_file(ROOTPATH.'api/member/'.$oauth['uid'].'.jpg')) {
                    \Phpcmf\Service::M()->db->table('member_data')->where('id', $oauth['uid'])->update(['is_avatar' => 1]);
                }

                break;

            case 'cron': // 队列任务

                $id = intval(\Phpcmf\Service::L('input')->get('id'));
                $rt = \Phpcmf\Service::M('cron')->do_cron_id($id);
                if (!$rt['code']) {
                    log_message('error', '任务查询失败（'.$rt['msg'].'）：'.FC_NOW_URL);
                    exit('任务查询失败（'.$rt['msg'].'）');
                }

                break;

        }

        exit('ok');

    }
}
