<?php

declare(strict_types=1);

echo "Iniciando ejecución de run_notifications.php...\n";

/**
 * run_notifications.php
 * Script de línea de comandos para enviar todas las notificaciones de la CMDB.
 * Uso: php /var/www/html/cmdb_notification/run_notifications.php
 */

require __DIR__ . '/../cmdb_app/vendor/autoload.php';

use DI\Container;
use Psr\Container\ContainerInterface;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Formatter\LineFormatter;
use League\Plates\Engine as PlatesEngine;
use PHPMailer\PHPMailer\PHPMailer;

$container = new Container();

// --- 1. Configuración ---
$container->set('config', function () {
    if (!isset($_ENV['APP_ENV'])) {
        $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../cmdb_app');
        $dotenv->load();
    }
    return require __DIR__ . '/../cmdb_app/app/Config/config.php';
});

// --- 2. Logger ---
$container->set('logger', function (ContainerInterface $c) {
    $logDir = __DIR__ . '/logs';
    if (!is_dir($logDir)) {
        // Crear el directorio si no existe
        mkdir($logDir, 0755, true);
    }
    $logPath = $logDir . '/notification.log';
    $logger = new Logger('CMDB_Notification');
    $formatter = new LineFormatter("[%datetime%] %channel%.%level_name%: %message% %context% %extra%\n", "Y-m-d H:i:s", true, true);
    $handler = new StreamHandler($logPath, Logger::DEBUG);
    $handler->setFormatter($formatter);
    $logger->pushHandler($handler);
    return $logger;
});

// --- 3. Base de Datos (PDO) ---
$container->set('db', function (ContainerInterface $c) {
    try {
        $dbConfig = $c->get('config')['db'];
        $dsn = "mysql:host={$dbConfig['host']};dbname={$dbConfig['name']};charset={$dbConfig['charset']}";
        return new PDO($dsn, $dbConfig['user'], $dbConfig['pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    } catch (PDOException $e) {
        // Si la conexión a la DB falla, es un error crítico.
        echo "Error CRÍTICO: No se pudo conectar a la base de datos. " . $e->getMessage() . "\n";
        exit(1);
    }
});

// --- 4. Traductor ---
$container->set('translator', function (ContainerInterface $c) {
    $langConfig = $c->get('config')['lang'];
    $defaultLang = $c->get('config')['app']['default_language'] ?? 'es';
    return function (string $key, array $replacements = []) use ($langConfig, $defaultLang) {
        $langFilePath = $langConfig[$defaultLang] ?? null;
        $translations = file_exists($langFilePath) ? require $langFilePath : [];
        $text = $translations[$key] ?? $key;
        foreach ($replacements as $placeholder => $value) {
            $text = str_replace($placeholder, (string) $value, $text);
        }
        return $text;
    };
});

// --- 5. Motor de Plantillas (Plates) ---
$container->set(PlatesEngine::class, function (ContainerInterface $c) {
    // Apuntar al directorio de vistas local de la app de notificaciones
    $viewsPath = __DIR__ . '/src/Views';
    $engine = new PlatesEngine($viewsPath);
    $engine->addFolder('emails', $viewsPath . '/emails');
    return $engine;
});

// --- 6. PHPMailer ---
$container->set(PHPMailer::class, function (ContainerInterface $c) {
    $mailer = new PHPMailer(true);
    try {
        $smtpConfigModel = new \App\Models\SmtpConfig($c->get('db'));
        $smtpConfig = $smtpConfigModel->getConfig() ?: [];
        if (!empty($smtpConfig['host'])) {
            $mailer->isSMTP();
            $mailer->Host = $smtpConfig['host'];
            $mailer->Port = (int)$smtpConfig['port'];
            $mailer->SMTPAuth = (bool)$smtpConfig['auth_required'];
            if ($mailer->SMTPAuth) {
                $mailer->Username = $smtpConfig['username'];
                $mailer->Password = $smtpConfig['password'];
            }
            if (!empty($smtpConfig['encryption'])) {
                $mailer->SMTPSecure = $smtpConfig['encryption'];
            }
            // Deshabilitar la verificación de certificados SSL.
            // ¡ADVERTENCIA! Esto reduce la seguridad. Usar solo si confías en el servidor SMTP.
            $mailer->SMTPOptions = [
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true,
                ],
            ];

            $mailer->setFrom($smtpConfig['from_email'], $smtpConfig['from_name']);
        }
    } catch (Exception $e) {
        // Si falla la obtención de la config SMTP, lo logueamos pero continuamos.
        // El EmailService se encargará de verificar si el mailer está configurado.
        $logger = $c->get('logger');
        $logger->error("No se pudo cargar la configuración SMTP desde la base de datos: " . $e->getMessage());
    }
    return $mailer;
});

// --- 7. Servicios y Gestor Principal ---
$container->set(CmdbNotification\Services\EmailService::class, function (ContainerInterface $c) {
    return new CmdbNotification\Services\EmailService($c->get('logger'), $c->get('translator'), $c->get(PlatesEngine::class), $c->get(PHPMailer::class));
});

$container->set(CmdbNotification\NotificationManager::class, function (ContainerInterface $c) {
    $configService = new \CmdbNotification\Services\Config(__DIR__ . '/../cmdb_app');
    
    $manager = new CmdbNotification\NotificationManager($c->get('db'), $c->get('logger'), $c->get('translator'), $c->get(CmdbNotification\Services\EmailService::class));
    $manager->addChecker(new CmdbNotification\Checkers\AssetExpirationChecker($c->get('db'), $c->get('logger'), $c->get('translator'), $configService));
    $manager->addChecker(new CmdbNotification\Checkers\ContractExpirationChecker($c->get('db'), $c->get('logger'), $c->get('translator'), $configService));
    return $manager;
});

// --- Ejecución ---
try {
    $logger = $container->get('logger');
    $logger->info("Iniciando script de notificaciones...");
    $manager = $container->get(CmdbNotification\NotificationManager::class);
    $manager->sendNotifications();
} catch (\Exception $e) {
    $logger->critical("Error fatal en el script de notificaciones: " . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
    exit(1);
}
