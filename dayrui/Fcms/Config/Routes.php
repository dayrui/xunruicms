<?php namespace Config;

/**
 * PHPCMF 路由文件
 */

// Create a new instance of our RouteCollection class.
$routes = Services::routes(true);

// Load the system's routing file first, so that the app and ENVIRONMENT
// can override as needed.
if (file_exists(BASEPATH.'Config/Routes.php'))
{
	require BASEPATH.'Config/Routes.php';
}

$routes->setDefaultNamespace('Phpcmf\\'.(APP_DIR ? 'Controllers' : 'Control'));
$routes->setDefaultController('Home');
$routes->setDefaultMethod('index');
$routes->setTranslateURIDashes(false);
$routes->set404Override();
$routes->setAutoRoute(true);

if (IS_ADMIN) {
    $routes->setDefaultNamespace('Phpcmf\\'.(APP_DIR ? 'Controllers' : 'Control').'\\Admin');
} elseif (IS_MEMBER) {
    $routes->setDefaultNamespace('Phpcmf\\'.(APP_DIR ? 'Controllers' : 'Control').'\\Member');
} elseif (IS_API) {
    $routes->setDefaultNamespace('Phpcmf\\'.(APP_DIR ? 'Controllers' : 'Control').'\\Api');
}


isset($_GET['c']) && $_GET['c'] && is_string($_GET['c']) && $routes->setDefaultController(ucfirst($_GET['c']));
isset($_GET['m']) && $_GET['m'] && is_string($_GET['m']) && $routes->setDefaultMethod($_GET['m']);

$routes->add('/', $routes->getDefaultController().'::'.$routes->getDefaultMethod());

require CMSPATH.'Core/Phpcmf.php';


