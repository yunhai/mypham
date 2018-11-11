<?php
ini_set('display_startup_errors', 0);
ini_set('display_errors', 0);
error_reporting(0);

session_start();

define('DS', DIRECTORY_SEPARATOR);
define('ROOT', dirname(__DIR__) . DS);

require ROOT . 'vendor/autoload.php';

use Mp\App;
use Mp\Lib\Package\Auth\Auth;

try {
    App::db()->connect();
    App::mp('request')->conduct();

    if (Auth::authenticate() === false) {
        if (empty(App::mp('login')->appId())) {
            $login = App::mp('config')->get('authorize.login.' . App::mp('request')->channel);
            if (strpos($login, '@') !== false) {
                list($action, $module) = explode('@', $login);
                $login = $module . '/' . $action;
            }

            $controller = new \Mp\Core\Controller\Controller();
            $controller->redirect(App::load('url')->full($login));
        }

        abort('UnauthorizedException');
    }

    App::invoke();
} catch (Exception $e) {
    print_r("<pre>");
    print_r($e);
    print_r("</pre>");
    App::log(print_r($e, true), 'error');

    try {
        $func = 'error404';
        $content = App::mp('view')->finalize(App::load('error', 'controller'), $func);
        App::render($content);
    } catch (Exception $e) {
        echo $content = '<b>Out of service</a>';
        App::render($content);
        exit;
    }
}
