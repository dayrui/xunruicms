<?php

define('FRAME_NAME', 'CodeIgniter');
define('FRAME_VERSION', CodeIgniter\CodeIgniter::CI_VERSION);

\CodeIgniter\Events\Events::on('pre_system', function () {

    /*
     * --------------------------------------------------------------------
     * Debug Toolbar Listeners.
     * --------------------------------------------------------------------
     * If you delete, they will no longer be collected.
     */
    if (CI_DEBUG)
    {

        \CodeIgniter\Events\Events::on('DBQuery', 'CodeIgniter\Debug\Toolbar\Collectors\Database::collect');
        \Config\Services::toolbar()->respond();
    }
});

// 当提交完成后执行跨站验证
if (defined('SYS_CSRF') && SYS_CSRF && IS_POST) {
    \Phpcmf\Hooks::on('cms_end', function ($rt) {
        if ($rt['code']) {
            \Config\Services::security()->updateHash();
        }
    });
}
