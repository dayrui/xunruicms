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
        $this->hasTabContent = true;
    }

    /**
     * Returns the data of this collector to be formatted in the toolbar
     *
     * @return array
     */
    public function display(): array
    {

        $vars = [];
        $tpl_var = $this->viewer->get_data();
        if ($tpl_var) {
            foreach ($tpl_var as $key => $value) {
                $vars[] = [
                    'name' => $key,
                    'value' => var_export($value, true),
                ];
            }
        }

        return [
            'vars' => $vars,
            'tips' => $this->viewer->get_load_tips(),
            'times' => [['tpl' => $this->viewer->get_view_time()]],
            'files' => $this->viewer->get_view_files(),
        ];
    }

    /**
     * Returns any information that should be shown next to the title.
     *
     * @return string
     */
    public function getBadgeValue(): int
    {
        return count($this->viewer->get_view_files());
    }
}
