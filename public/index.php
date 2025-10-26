<?php
/**
 * public/index.php
 * Punto de entrada principal de la aplicación CMDB.
 */

// Definir la ruta raíz del proyecto para usarla en toda la aplicación.
define('ROOT_PATH', dirname(__DIR__));

// Carga el autoloader de Composer
require __DIR__ . '/../vendor/autoload.php';

// --- CARGA DE VARIABLES DE ENTORNO ---
// Esto debe ocurrir ANTES de cargar cualquier configuración.
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Carga la configuración de la aplicación para obtener la ruta de los logs
$configData = require __DIR__ . '/../app/Config/config.php';

// Inicializa el contenedor de inyección de dependencias (PHP-DI)
$container = new \DI\Container();

// --- ORDEN DE ARRANQUE CORREGIDO ---
// 1. Configurar las dependencias del contenedor PRIMERO.
//    Esto asegura que el logger y otros servicios estén listos.
require __DIR__ . '/../app/bootstrap.php';

// Crea la instancia de la aplicación Slim
$app = \Slim\Factory\AppFactory::createFromContainer($container);

// 1. Añadir el middleware de errores PRIMERO para capturar cualquier error de arranque.
$errorMiddleware = $app->addErrorMiddleware(
    ($configData['app']['env'] === 'development'), // displayErrorDetails
    true, // logErrors
    true  // logErrorDetails
);

// 2. Añadir otros middlewares globales.
$app->addBodyParsingMiddleware();
$app->addRoutingMiddleware();

// Carga las rutas de la aplicación
require __DIR__ . '/../app/routes.php';

// Ejecuta la aplicación Slim
$app->run();
