<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2018 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
use think\facade\Route;


//Route::any('/', 'index/hello');

require CMSPATH . 'Core/Phpcmf.php';

$ns = 'Phpcmf\\'.(APP_DIR ? 'Controllers' : 'Control');
$class = 'Home';
$method = 'index';

if (IS_ADMIN) {
    $ns = 'Phpcmf\\'.(APP_DIR ? 'Controllers' : 'Control').'\\Admin';
} elseif (IS_MEMBER) {
    $ns = ('Phpcmf\\'.(APP_DIR == 'member' ? 'Controllers' : 'Controllers\\Member'));
} elseif (IS_API) {
    $ns = ('Phpcmf\\'.(APP_DIR ? 'Controllers' : 'Control').'\\Api');
}

isset($_GET['c']) && $_GET['c'] && is_string($_GET['c']) && $class = ucfirst(dr_safe_filename($_GET['c']));
isset($_GET['m']) && $_GET['m'] && is_string($_GET['m']) && $method = (dr_safe_filename($_GET['m']));

$ns.= '\\'.$class;

unset($_GET['s']);
Route::any('/', $ns.'/'.$method, 'GET|POST');