<?php namespace Phpcmf\Control\Api;
/**
 * {{www.xunruicms.com}}
 * {{迅睿内容管理框架系统}}
 * 本文件是框架系统文件，二次开发时不可以修改本文件
 **/

// 系统默认伪静态处理
class Rewrite extends \Phpcmf\Common
{

	// test
	public function test() {
		$this->_jsonp(1, '服务器支持伪静态功能，可以自定义URL规则和解析规则了');
	}

    /**
     * 跳转地址
     */
    public function url() {

        $url = urldecode(\Phpcmf\Service::L('input')->get('url'));
		$arr = [ 0 => [], 1 => []];
		for ($i = 1 ; $i < 10; $i ++) {
			$arr[0][] = urldecode(\Phpcmf\Service::L('input')->get('p'.$i));
			$arr[1][] = '$'.$i;
		}
        dr_redirect(dr_url_prefix(str_replace($arr[1], $arr[0], $url)), 'location', '301');
    }

    public function license() {

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
