<?php
/**
 * public/index.php
 * Punto de entrada principal de la aplicación CMDB.
 */

// Carga el autoloader de Composer
require __DIR__ . '/../vendor/autoload.php';


ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Carga la configuración de la aplicación
$config = require __DIR__ . '/../app/Config/config.php';

// Inicializa el contenedor de inyección de dependencias (PHP-DI)
$container = new \DI\Container();

// Crea la instancia de la aplicación Slim
$app = \Slim\Factory\AppFactory::createFromContainer($container);

// --- REORGANIZACIÓN CRÍTICA ---
// 1. Añadir el middleware de errores PRIMERO para capturar cualquier error de arranque.
$errorMiddleware = $app->addErrorMiddleware(
    ($config['app']['env'] === 'development'), // displayErrorDetails
    true, // logErrors
    true  // logErrorDetails
);

// 2. Añadir otros middlewares globales.
$app->addBodyParsingMiddleware();
$app->addRoutingMiddleware();

// 3. Ahora, configurar las dependencias del contenedor.
require __DIR__ . '/../app/bootstrap.php';

// Carga las rutas de la aplicación
require __DIR__ . '/../app/routes.php';

// Ejecuta la aplicación Slim
$app->run();
