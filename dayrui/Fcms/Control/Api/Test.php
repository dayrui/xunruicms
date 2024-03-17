<?php namespace Phpcmf\Control\Api;
/**
 * {{www.xunruicms.com}}
 * {{迅睿内容管理框架系统}}
 * 本文件是框架系统文件，二次开发时不可以修改本文件
 **/

// 测试信息
class Test extends \Phpcmf\Common
{

    // test
    public function https() {
        echo 'xunruicms';
    }

    public function index() {

        $license = \Phpcmf\Service::R(MYPATH.'Config/License.php');
        if (isset($license['oem']) && $license['oem']) {
            echo $license['name'].' '.$license['cms'].' V'.$this->cmf_version['version'] . ' <span style="display: none">'.$license['license'].'</span>';exit;
        }

        echo 'XunRuiCMS V'.$this->cmf_version['version'].'<hr>';

        if (is_file(WEBPATH.'LICENSE')) {
            $file = WEBPATH.'LICENSE';
        } elseif (is_file(ROOTPATH.'LICENSE')) {
            $file = ROOTPATH.'LICENSE';
        } elseif (is_file(dirname(WEBPATH).'/LICENSE')) {
            $file = dirname(WEBPATH).'/LICENSE';
        } else {
            exit('<font color="red">LICENSE许可证文件不存在，请遵守迅睿MIT开源协议</font>');
        }

        echo nl2br((string)file_get_contents($file));
        exit;
    }

}
