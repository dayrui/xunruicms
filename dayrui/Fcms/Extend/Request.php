<?php namespace Phpcmf\Extend;
/**
 * http://www.xunruicms.com
 * 本文件是框架系统文件，二次开发时不可以修改本文件
 * 迅睿内容管理框架系统（简称：迅睿CMS）软件著作权登记号：2019SR0854684
 **/


/**
 * 用于Services.php
 */

class Request extends \CodeIgniter\HTTP\IncomingRequest
{
    protected function parseRequestURI(): string
    {
        return '/'; // 这里要固定返回 / 确保cms自定义URL正常使用
    }
}