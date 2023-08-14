<?php namespace Phpcmf\Control\Api;
/**
 * {{www.xunruicms.com}}
 * {{迅睿内容管理框架系统}}
 * 本文件是框架系统文件，二次开发时不可以修改本文件
 **/

/**
 * 运行任务接口
 */

class Run extends \Phpcmf\Common
{

    /**
     * 初始化
     */
    public function __construct($object = NULL)
    {
        parent::__construct();
        if ($object) {
            foreach ($object as $var => $value) {
                $this->$var = $value;
            }
        }
    }

	public function index() {

	    // 验证运行权限
        if (!IS_DEV) {
            if (defined('SYS_CRON_AUTH') && SYS_CRON_AUTH) {
                if (is_cli()) {
                    // cli模式
                } else {
                    // url模式
                    if (SYS_CRON_AUTH == 'cli') {
                        exit('限制CLI模式执行任务');
                    }
                    $ip = \Phpcmf\Service::L('input')->ip_address();
                    if (!$ip) {
                        if (CI_DEBUG) {
                            log_message('error', '任务执行失败：无法获取执行客户端的IP地址');
                        }
                        exit('无权限执行任务');
                        return;
                    }
                    if (SYS_CRON_AUTH != $ip) {
                        if (CI_DEBUG) {
                            log_message('error', '任务执行失败：后台设置的服务端ip（'.SYS_CRON_AUTH.'）与客户端ip（'.$ip.'）不一致');
                        }
                        exit('限制固定IP执行任务');
                        return;
                    }
                }
            }
            $run_time = 0;// 上次执行的时间
            if (is_file(WRITEPATH.'config/cron_run_time.php')) {
                $run_time = file_get_contents(WRITEPATH.'config/cron_run_time.php');
                if ($run_time && SYS_TIME - $run_time < 100) {
                    exit('未到任务执行时间');
                }
            } else {
                file_put_contents(WRITEPATH.'config/cron_run_time.php', SYS_TIME);
            }
            $fp = fopen ( WRITEPATH.'config/cron_run_time.php' , "r" );
            //加锁
            if ( flock ( $fp ,LOCK_EX | LOCK_NB)) {
                // 写入新的时间
                file_put_contents(WRITEPATH.'config/cron_run_time.php', SYS_TIME);
            } else {
                fclose( $fp );
                exit('任务正在执行中');
            }

            if (isset($_GET['is_ajax'])) {
                // 后台脚本自动任务时

            } else {
                // 服务器自动任务时
                // 自动任务锁定
                if (!is_file(WRITEPATH.'config/run_lock.php')) {
                    file_put_contents(WRITEPATH.'config/run_lock.php', 'true');
                }
            }
        } else {
            $fp = false;
        }

        // 批量执行站点动作
        foreach ($this->site_info as $siteid => $site) {
            // 删除网站首页
            if ($site['SITE_INDEX_HTML']) {
                $time = (isset($site['SITE_INDEX_TIME']) && $site['SITE_INDEX_TIME'] ? $site['SITE_INDEX_TIME'] : 10) * 3600;
                foreach ([
                             \Phpcmf\Service::L('html')->get_webpath($siteid,'site', 'index.html'),
                             \Phpcmf\Service::L('html')->get_webpath($siteid,'site', 'mobile/index.html'),
                         ] as $file) {
                    $ft = filemtime($file);
                    if ($ft && SYS_TIME - $ft > $time) {
                        unlink($file);
                    }
                }
            }
        }

        // 执行队列
        $i = \Phpcmf\Service::M('cron')->run_cron(intval($_GET['num']));

        // 最少100秒调用本程序
        //\Phpcmf\Service::L('input')->set_cookie('cron', 1, 100);

        // 定义后台执行任务的时效
        //\Phpcmf\Service::L('input')->set_cookie('admin_cron', 1, 600);

        // 任务计划
        \Phpcmf\Hooks::trigger('cron');

        // 清理缓存数据
        if (!is_file(WRITEPATH.'config/run_auto_cache_time.php')) {
            $time = SYS_TIME;
            file_put_contents(WRITEPATH.'config/run_auto_cache_time.php', $time);
        } else {
            $time = file_get_contents(WRITEPATH.'config/run_auto_cache_time.php');
        }

        // 多少天清理一次系统缓存
        $day = max(3, SYS_CACHE_CRON);
        if (SYS_TIME - $time > 3600 * 24 * $day) {
            // 缓存清理
            \Phpcmf\Service::M('cache')->update_data_cache(true);
            file_put_contents(WRITEPATH.'config/run_auto_cache_time.php', SYS_TIME);
            // 清理日志
            $map = dr_file_map(WRITEPATH.'error/');
            if ($map) {
                foreach ($map as $file) {
                    if (strpos($file, 'log-') !== false) {
                        $file = WRITEPATH.'error/'.$file;
                        $time = filectime($file);
                        if ($time && SYS_TIME - $time > 3600 * 24 * 30) {
                            @unlink($file);
                        }
                    }
                }
            }
        }

        // 项目计划
        if (is_file(MYPATH.'Config/Cron.php')) {
            require MYPATH.'Config/Cron.php';
        }

        // 为插件单独执行计划
        $local = \Phpcmf\Service::Apps();
        if ($local) {
            foreach ($local as $dir => $path) {
                if (is_file($path.'Config/Cron.php')
                    && is_file($path.'Config/App.php')
                    && is_file($path.'install.lock')) {
                    require $path.'Config/Cron.php';
                }
            }
        }

        // 自动任务执行时间
        file_put_contents(WRITEPATH.'config/run_time.php', dr_date(SYS_TIME));

        if ($fp) {
            flock ( $fp ,LOCK_UN);
            fclose( $fp );
        }

        exit('任务执行成功：Run '.($i ? $i : 'Ok'));
	}


    /**
     * 线程任务接口
     */
	public function cron() {

        $file = WRITEPATH.'thread/'.dr_safe_filename(\Phpcmf\Service::L('input')->get('auth')).'.auth';
        if (!is_file($file)) {
            log_message('error', '线程任务auth文件不存在：'.dr_now_url());
            exit('线程任务auth文件不存在'.$file);
        }

        $time = (int)file_get_contents($file);
        unlink($file);
        if (SYS_TIME - $time > 500) {
            // 500秒外无效
            log_message('error', '线程任务auth过期：'.dr_now_url());
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

                list($cache_path) = dr_avatar_path();
                $path = $cache_path.dr_avatar_dir($oauth['uid']);
                $file = $path.$oauth['uid'].'.jpg';
                if (is_file($file)) {
                    \Phpcmf\Service::M()->db->table('member_data')->where('id', $oauth['uid'])->update(['is_avatar' => 1]);
                    exit('头像已经存在');
                }

                $avatar = dr_catcher_data($oauth['avatar']);
                if ($avatar) {
                    if (!is_dir($path)) {
                        dr_mkdirs($path);
                    }
                    if (file_put_contents($file, $avatar)) {
                        \Phpcmf\Service::M()->db->table('member_data')->where('id', $oauth['uid'])->update(['is_avatar' => 1]);
                    }
                }

                break;

            case 'cron': // 队列任务

                $id = intval(\Phpcmf\Service::L('input')->get('id'));
                $rt = \Phpcmf\Service::M('cron')->do_cron_id($id);
                if (!$rt['code']) {
                    log_message('debug', '任务执行失败（'.$rt['msg'].'）：'.$rt['data']['value']);
                    exit('任务执行失败（'.$rt['msg'].'）');
                }

                break;

        }

        exit('ok');
    }
}
