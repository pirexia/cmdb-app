<?php
/**
 * phinx.php
 *
 * Fichero de configuración para la herramienta de migraciones Phinx.
 * Carga las variables de entorno desde el fichero .env para configurar
 * la conexión a la base de datos de forma dinámica.
 */

// Cargar el autoloader de Composer para tener acceso a las clases del proyecto y vendors.
require_once __DIR__ . '/vendor/autoload.php';

// Cargar las variables de entorno desde el fichero .env
try {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->load();
    $dotenv->required(['DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASS']);
} catch (Exception $e) {
    die('Error: No se pudo cargar el fichero .env. Asegúrate de que existe y tiene las variables de base dedatos. ' . $e->getMessage());
}

return
[
    'paths' => [
        'migrations' => '%%PHINX_CONFIG_DIR%%/database/migrations',
        'seeds' => '%%PHINX_CONFIG_DIR%%/database/seeds'
    ],
    'environments' => [
        'default_migration_table' => 'phinxlog',
        'default_environment' => 'production',
        'production' => [
            'adapter' => 'mysql',
            'host' => $_ENV['DB_HOST'],
            'name' => $_ENV['DB_NAME'],
            'user' => $_ENV['DB_USER'],
            'pass' => $_ENV['DB_PASS'],
            'port' => $_ENV['DB_PORT'] ?? 3306,
            'charset' => $_ENV['DB_CHARSET'] ?? 'utf8mb4',
        ],
        // Puedes añadir otros entornos como 'development' o 'testing' si lo necesitas
    ],
    'version_order' => 'creation'
];