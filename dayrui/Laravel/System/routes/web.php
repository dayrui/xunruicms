<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

require CMSPATH . 'Core/Phpcmf.php';

$ns = 'Phpcmf\\'.(APP_DIR ? 'Controllers' : 'Control');
$class = 'Home';
$method = 'index';

if (IS_ADMIN) {
    $ns = 'Phpcmf\\'.(APP_DIR ? 'Controllers' : 'Control').'\\Admin';
} elseif (IS_MEMBER) {
    $ns = 'Phpcmf\\'.(APP_DIR == 'member' ? 'Controllers' : 'Controllers\\Member');
} elseif (IS_API) {
    $ns = 'Phpcmf\\'.(APP_DIR ? 'Controllers' : 'Control').'\\Api';
}

isset($_GET['c']) && $_GET['c'] && is_string($_GET['c']) && $class = ucfirst(dr_safe_filename($_GET['c']));
isset($_GET['m']) && $_GET['m'] && is_string($_GET['m']) && $method = (dr_safe_filename($_GET['m']));

$ns.= '\\'.$class;

Route::any('/', $ns.'@'.$method)->middleware('web');
