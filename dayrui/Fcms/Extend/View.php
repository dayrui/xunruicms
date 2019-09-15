<?php namespace Phpcmf\Extend;

/**
 * http://www.xunruicms.com
 * 本文件是框架系统文件，二次开发时不可以修改本文件
 **/


/**
 * Debug工具栏模板类
 */

class View extends \CodeIgniter\Debug\Toolbar\Collectors\Views
{


    /**
     * 把CI模板类改成PHPCMF模板类用于debug.
     */
    public function __construct()
    {
        $this->viewer = \Phpcmf\Service::V();
    }

}
