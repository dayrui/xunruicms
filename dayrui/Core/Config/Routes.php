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

/**
 * --------------------------------------------------------------------
 * Router Setup
 * --------------------------------------------------------------------
 * The RouteCollection object allows you to modify the way that the
 * Router works, by acting as a holder for it's configuration settings.
 * The following methods can be called on the object to modify
 * the default operations.
 *
 *    $routes->defaultNamespace()
 *
 * Modifies the namespace that is added to a controller if it doesn't
 * already have one. By default this is the global namespace (\).
 *
 *    $routes->defaultController()
 *
 * Changes the name of the class used as a controller when the route
 * points to a folder instead of a class.
 *
 *    $routes->defaultMethod()
 *
 * Assigns the method inside the controller that is ran when the
 * Router is unable to determine the appropriate method to run.
 *
 *    $routes->setAutoRoute()
 *
 * Determines whether the Router will attempt to match URIs to
 * Controllers when no specific route has been defined. If false,
 * only routes that have been defined here will be available.
 */
$routes->setDefaultNamespace('Phpcmf\Controllers');
$routes->setDefaultController('Home');
$routes->setDefaultMethod('index');
$routes->setTranslateURIDashes(false);
$routes->set404Override();
$routes->setAutoRoute(true);

/**
 * --------------------------------------------------------------------
 * Route Definitions
 * --------------------------------------------------------------------
 */

// We get a performance increase by specifying the default
// route since we don't have to scan directories.


if (IS_ADMIN) {
    $routes->setDefaultNamespace('Phpcmf\Controllers\Admin');
} elseif (IS_MEMBER) {
    $routes->setDefaultNamespace('Phpcmf\Controllers\Member');
} elseif (IS_API) {
    $routes->setDefaultNamespace('Phpcmf\Controllers\Api');
}


isset($_GET['c']) && $_GET['c'] && is_string($_GET['c']) && $routes->setDefaultController(ucfirst($_GET['c']));
isset($_GET['m']) && $_GET['m'] && is_string($_GET['m']) && $routes->setDefaultMethod($_GET['m']);

$routes->add('/', $routes->getDefaultController().'::'.$routes->getDefaultMethod());

require CMSPATH.'Core/Phpcmf.php';


