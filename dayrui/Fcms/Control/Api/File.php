<?php namespace Phpcmf\Control\Api;
/**
 * {{www.xunruicms.com}}
 * {{迅睿内容管理框架系统}}
 * 本文件是框架系统文件，二次开发时不可以修改本文件
 **/

// 文件操作
class File extends \Phpcmf\Common
{

    // 验证权限脚本
    public function _check_upload_auth($editor = 0) {
        return \Phpcmf\Service::L('api')->check_upload_auth($editor);
    }

    /**
     * 文件上传
     */
    public function upload() {
        \Phpcmf\Service::L('api')->upload();
    }

    /**
     * 输入一个附件
     */
    public function input_file_url() {
        \Phpcmf\Service::L('api')->input_file_url();
    }

    /**
     * 浏览文件
     */
    public function input_file_list() {
        \Phpcmf\Service::L('api')->input_file_list();
    }

    /**
     * 浏览文件
     */
    public function file_list() {
        \Phpcmf\Service::L('api')->file_list();
    }

    /**
     * 删除文件
     */
    public function file_delete() {

        $rt = \Phpcmf\Service::M('Attachment')->file_delete(
            $this->member,
            (int)\Phpcmf\Service::L('input')->get('id')
        );

        $this->_json($rt['code'], $rt['msg']);
    }

    /**
     * 下载文件
     */
    public function down() {
        \Phpcmf\Service::L('api')->down();
    }

    /**
     * 百度编辑器处理接口
     */
    public function ueditor() {
        require ROOTPATH.'api/ueditor/php/controller.php';exit;
    }

    /**
     * base64图片上传
     */
    public function upload_base64_image() {
        \Phpcmf\Service::L('api')->upload_base64_image();
    }

    /**
     * 图片编辑
     */
    public function image_edit() {
        \Phpcmf\Service::L('api')->image_edit();
    }

    /**
     * 附件改名
     */
    public function name_edit() {
        \Phpcmf\Service::L('api')->name_edit();
    }

    /**
     * 下载远程图片
     */
    public function down_img_list() {
        \Phpcmf\Service::L('api')->down_img_list();
    }

    /**
     * 下载远程图片
     */
    public function down_img_url() {
        \Phpcmf\Service::L('api')->down_img_url();
    }

    /**
     * 下载远程图片
     */
    public function down_img() {
        \Phpcmf\Service::L('api')->down_img();
    }


}
