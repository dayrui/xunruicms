<?php

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