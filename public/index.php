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

// Configura las dependencias del contenedor
// Esto es donde le decimos a PHP-DI cómo crear instancias de nuestras clases
require __DIR__ . '/../app/bootstrap.php'; // Archivo que configuraremos a continuación

// Crea la instancia de la aplicación Slim
$app = \Slim\Factory\AppFactory::createFromContainer($container);

// Esto permite que $request->getParsedBody() funcione para JSON.
$app->addBodyParsingMiddleware();

// Añade el middleware de enrutamiento
$app->addRoutingMiddleware();

// Añade el middleware de manejo de errores
// En producción, es recomendable no mostrar los detalles de error directamente
$errorMiddleware = $app->addErrorMiddleware(
    ($config['app']['env'] === 'development'), // displayErrorDetails
    true, // logErrors
    true  // logErrorDetails
);

// Carga las rutas de la aplicación
require __DIR__ . '/../app/routes.php';

// Ejecuta la aplicación Slim
$app->run();
