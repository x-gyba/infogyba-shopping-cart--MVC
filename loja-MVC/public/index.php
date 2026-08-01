<?php

/**
 * Front Controller - ponto único de entrada da aplicação.
 * Todas as requisições HTTP passam por aqui (ver public/.htaccess).
 */

require dirname(__DIR__) . '/config/config.php';
require dirname(__DIR__) . '/app/Core/Autoload.php';

use App\Core\Router;
use App\Core\Session;
use App\Core\SecurityHeaders;

SecurityHeaders::apply();
Session::start();

$router = new Router();
require dirname(__DIR__) . '/routes/web.php';

$router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);