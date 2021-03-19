<?php namespace Phpcmf\Controllers\Member;

/**
 * http://www.xunruicms.com
 * 本文件是框架系统文件，二次开发时不可以修改本文件
 **/

class Content extends \Phpcmf\Table
{
    /**
     * 兼容跳转
     */
    public function comment() {

        if (dr_is_app('comment')) {
            dr_redirect(dr_member_url('comment/content/index'));
        } else {
            $this->_admin_msg(0, dr_lang('系统没有安装评论插件'));
        }
    }

}
