<?php namespace Phpcmf\Control\Admin;
/**
 * {{www.xunruicms.com}}
 * {{迅睿内容管理框架系统}}
 * 本文件是框架系统文件，二次开发时不可以修改本文件
 **/

class Debug extends \Phpcmf\Common
{

	public function index() {

        $this->_echo_msg(IS_DEV ? 1 : 0, '开发者模式：'.(IS_DEV ? '已开启' : '未开启'));
        $this->_echo_msg(1, '客户端字符串：'.$_SERVER['HTTP_USER_AGENT']);
        $this->_echo_msg(1, 'PHP版本：'.PHP_VERSION.'');
        $this->_echo_msg(1, 'MySQL版本：'.\Phpcmf\Service::M()->db->getVersion());

        $this->_echo_msg(1, '内核版本：'.FRAME_NAME.' - '.FRAME_VERSION);
        $this->_echo_msg(1, 'CMS版本：'.$this->cmf_version['version'].' - '.dr_date($this->cmf_version['downtime'], 'Y-m-d H:i:s'));
        $local = \Phpcmf\Service::Apps();
        foreach ($local as $dir => $path) {
            if (is_file($path.'Config/App.php')) {
                $key = strtolower($dir);
                $cfg = require $path.'Config/App.php';
                if (is_file($path.'Config/Version.php')) {
                    $vsn = require $path.'Config/Version.php';
                    $this->_echo_msg(1, $cfg['name'].' - 版本：'.$vsn['version'].' - '.dr_date($vsn['downtime'], 'Y-m-d H:i:s'));
                }
            }
        }
	}


    function _echo_msg($code, $msg) {
        echo '<div style="border-bottom: 1px dashed #9699a2; padding: 5px;">';
        if (!$code) {
            echo '<b style="color:red;text-decoration:none;">'.$msg.'</b>';
        } else {
            echo '<font color=green>'.$msg.'</font>';
        }
        echo '</div>';
    }
}
