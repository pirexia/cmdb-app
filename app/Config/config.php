<?php
/**
 * app/Config/config.php
 * Configuración principal de la aplicación.
 */

// Carga las variables de entorno si no están cargadas
if (!isset($_ENV['APP_ENV'])) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../');
    $dotenv->load();
}

return [
    'app' => [
        'env' => $_ENV['APP_ENV'] ?? 'production',
        'name' => 'CMDB App',
        'url' => 'http://cmdb-app.svc.int', // Cambia esto por la URL real de tu aplicación
        'csrf_secret' => $_ENV['CSRF_SECRET'] ?? 'fallback_secret_if_not_set',
        'default_language' => 'es', // Idioma por defecto de la aplicación
        'admin_user' => [
            'enabled' => (bool) ($_ENV['DEFAULT_ADMIN_ENABLED'] ?? 0),
            'username' => $_ENV['DEFAULT_ADMIN_USERNAME'] ?? 'admin_default',
            'password' => $_ENV['DEFAULT_ADMIN_PASSWORD'] ?? 'admin_temp_password',
            'email' => $_ENV['DEFAULT_ADMIN_EMAIL'] ?? 'admin@example.com',
        ],
    ],
    'db' => [
        'host' => $_ENV['DB_HOST'] ?? 'localhost',
        'name' => $_ENV['DB_NAME'] ?? 'cmdb_app',
        'user' => $_ENV['DB_USER'] ?? 'root',
        'pass' => $_ENV['DB_PASS'] ?? '',
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
    ],
    'paths' => [
        'uploads' => $_ENV['UPLOADS_DIR'] ?? __DIR__ . '/../../storage/uploads',
        'logs' => $_ENV['LOGS_DIR'] ?? __DIR__ . '/../../storage/logs',
        'views' => __DIR__ . '/../Views', // Ruta a las plantillas de vista
        'public_assets' => '/static', // Ruta URL base para los assets públicos
    ],
    'session' => [
        'name' => $_ENV['SESSION_NAME'] ?? 'CMDBAppSession',
        'lifetime' => (int)($_ENV['SESSION_LIFETIME'] ?? 7200),
        'cookie_httponly' => filter_var($_ENV['SESSION_COOKIE_HTTPONLY'] ?? true, FILTER_VALIDATE_BOOLEAN), // Añadir a .env
        'cookie_secure' => filter_var($_ENV['SESSION_COOKIE_SECURE'] ?? false, FILTER_VALIDATE_BOOLEAN), // Añadir a .env (IMPORTANTE: true para HTTPS)
        'cookie_samesite' => $_ENV['SESSION_COOKIE_SAMESITE'] ?? 'Lax', // Añadir a .env (Strict, Lax, None)
    ],
    'pagination' => [
        'items_per_page' => 10, // Cantidad de ítems por página en tablas
    ],
    'notifications' => [ // <--- ¡NUEVA SECCIÓN!
        'recipients' => explode(',', $_ENV['NOTIFICATION_RECIPIENTS'] ?? ''),
        'days_advance' => (int)($_ENV['NOTIFICATION_DAYS_ADVANCE'] ?? 30),
    ],
    'lang' => [ // <--- ¡NUEVA SECCIÓN!
        'es' => __DIR__ . '/../Lang/es.php',
        'en' => __DIR__ . '/../Lang/en.php',
        // Añade más idiomas aquí
    ],
];
