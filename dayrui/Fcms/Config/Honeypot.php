<?php namespace Config;
/**
 * {{www.xunruicms.com}}
 * {{迅睿内容管理框架系统}}
 * 本文件是框架系统文件，二次开发时不可以修改本文件
 **/

use CodeIgniter\Config\BaseConfig;

class Honeypot extends BaseConfig
{
    /**
     * Makes Honeypot visible or not to human
     *
     * @var boolean
     */
    public $hidden = true;

    /**
     * Honeypot Label Content
     *
     * @var string
     */
    public $label = 'Fill This Field';

    /**
     * Honeypot Field Name
     *
     * @var string
     */
    public $name = 'honeypot';

    /**
     * Honeypot HTML Template
     *
     * @var string
     */
    public $template = '<label>{label}</label><input type="text" name="{name}" value=""/>';

    /**
     * Honeypot container
     *
     * @var string
     */
    public $container = '<div style="display:none">{template}</div>';
}
