<?php
/**
 * run_notifications.php
 *
 * Este script de línea de comandos se utiliza para enviar notificaciones automáticas
 * de elementos próximos a expirar (activos y contratos).
 * Está diseñado para ser ejecutado como un 'cron job' en el servidor.
 *
 * Requisitos:
 * - Se ejecuta desde el directorio raíz de la aplicación (cmdb_app/).
 * - Se asume que el contenedor de dependencias (PHP-DI) está disponible.
 * - Debe tener acceso a la base de datos y a la configuración.
 * Script de línea de comandos para enviar notificaciones de caducidad.
 * Diseñado para ser ejecutado como un 'cron job'.
 * 
 * Uso: php run_notifications.php
 */

// Importaciones necesarias para las clases utilizadas en el script CLI
use Dotenv\Dotenv;
use App\Models\Asset;
use App\Models\Contract;
use App\Services\MailService;
use App\Services\NotificationService;
use PHPMailer\PHPMailer\PHPMailer;
use League\Plates\Engine as PlatesEngine;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Formatter\LineFormatter;
use Psr\Log\LoggerInterface;
declare(strict_types=1);

// Se cargan las dependencias de Composer.
// Asume que este script se ejecuta desde el directorio raíz del proyecto.
// --- 1. Bootstrap de la Aplicación ---
// Carga el autoloader de Composer y el contenedor de dependencias.
require __DIR__ . '/vendor/autoload.php';

// Se carga la configuración de la aplicación y se crea el contenedor de dependencias.
// (Este es un proceso simplificado para un script CLI).
$config_data = require __DIR__ . '/app/Config/config.php';
$container = new \DI\Container();
$containerBuilder = new \DI\ContainerBuilder();
$containerBuilder->addDefinitions(__DIR__ . '/app/bootstrap.php');
$container = $containerBuilder->build();

// Definir la configuración de la aplicación en el contenedor.
$container->set('config', function () use ($config_data) {
    return $config_data;
});
// --- 2. Configuración del Logger para CLI ---
// Añadimos un handler para que los logs también se muestren en la consola.
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

// Definición para la función de traducción global.
$container->set('translator', function (ContainerInterface $c) {
    $langConfig = $c->get('config')['lang'];
    $defaultLang = $c->get('config')['app']['default_language'] ?? 'es';
/** @var Logger $logger */
$logger = $container->get('logger');
$logger->pushHandler(new StreamHandler('php://stdout', Logger::DEBUG)); // Salida a consola

    return function (string $key, array $replacements = [], ?string $langCode = null) use ($langConfig, $defaultLang) {
        $currentLang = $langCode ?? $defaultLang;
        $translations = [];
try {
    // --- 3. Ejecución de la Lógica ---
    /** @var \App\Services\NotificationService $notificationService */
    $notificationService = $container->get(\App\Services\NotificationService::class);

        $langFilePath = $langConfig[$currentLang] ?? null;
        if (!$langFilePath || !file_exists($langFilePath)) {
            $langFilePath = $langConfig[$defaultLang];
        }
        
        if (file_exists($langFilePath)) {
            $translations = require $langFilePath;
        }
    // El servicio ahora se encarga de toda la lógica interna.
    $notificationService->processAndSendExpirationNotices();

        $text = $translations[$key] ?? $key;
        foreach ($replacements as $placeholder => $value) {
            $text = str_replace($placeholder, $value, $text);
        }
        return $text;
    };
});
    $logger->info($container->get('translator')('cron_job_notifications_finished'));
    exit(0); // Salida exitosa


// === Definir servicios necesarios de forma que LoggerInterface se instancie directamente ===

// Configuración del Logger (Monolog) - Instancia CONCRETA de Monolog\Logger
$cliLogger = new Logger('CMDB_CLI_App'); // <--- Instanciamos directamente Monolog\Logger
$logPath = $appConfig['paths']['logs'] . '/app_cli.log'; // Log CLI separado
$logLevel = $appConfig['app']['env'] === 'development' ? Logger::DEBUG : Logger::INFO;
$formatter = new LineFormatter("[%datetime%] %channel%.%level_name%: %message% %context% %extra%\n", "Y-m-d H:i>
$streamHandler = new StreamHandler($logPath, $logLevel);
$streamHandler->setFormatter($formatter);
$cliLogger->pushHandler($streamHandler);

// Pasamos el logger CONCRETO al contenedor
$container->set(LoggerInterface::class, $cliLogger); // <--- Mapeamos la interfaz al objeto CONCRETO
$container->set('logger', $cliLogger); // También por el nombre 'logger'

// Configuración de la base de datos (PDO)
$container->set('db', function (\Psr\Container\ContainerInterface $c) use ($appConfig) {
    $dbConfig = $appConfig['db'];
    $dsn = "mysql:host={$dbConfig['host']};dbname={$dbConfig['name']};charset={$dbConfig['charset']}";
    $options = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, >
    return new PDO($dsn, $dbConfig['user'], $dbConfig['pass'], $options);
});

// Configuración de PlatesEngine para plantillas de correo
$container->set(PlatesEngine::class, function (\Psr\Container\ContainerInterface $c) use ($appConfig) {
    $viewsPath = $appConfig['paths']['views'];
    $engine = new PlatesEngine($viewsPath);
    $engine->addFolder('emails', $viewsPath . '/emails');
    return $engine;
});


// Definiciones de modelos y servicios que se usarán en el script.
$container->set(App\Models\Asset::class, function (ContainerInterface $c) { return new App\Models\Asset($c->get('db')); });
$container->set(App\Models\Contract::class, function (ContainerInterface $c) { return new App\Models\Contract($c->get('db')); });
$container->set(App\Services\SmtpService::class, function (ContainerInterface $c) {
    return new App\Services\SmtpService(
        $c->get('config'),
        $c->get('logger'),
        $c->get('translator'),
        $c->get('db')
} catch (Throwable $e) {
    // Captura cualquier error fatal durante la inicialización o ejecución.
    $logger->critical(
        $container->get('translator')('cron_job_notifications_critical_error', [
            '%message%' => $e->getMessage(), 
            '%trace%' => $e->getTraceAsString()
        ])
    );
});

// Definición de MailService.
$container->set(App\Services\MailService::class, function (ContainerInterface $c) {
    return new App\Services\MailService(
        $c->get(\PHPMailer\PHPMailer\PHPMailer::class),
        $c->get('logger'),
        $c->get(\League\Plates\Engine::class),
        $c->get(App\Services\SmtpService::class) // <-- Usa el SmtpService para la configuración.
    );
});

// Definición de NotificationService.
$container->set(App\Services\NotificationService::class, function (ContainerInterface $c) {
    return new App\Services\NotificationService(
        $c->get(App\Models\Asset::class),
        $c->get(App\Models\Contract::class),
        $c->get(App\Services\MailService::class),
        $c->get('logger'),
        $c->get('config'),
        $c->get('translator')
    );
});

// --- Lógica del Script CLI ---

try {
    $logger = $container->get('logger');
    $notificationService = $container->get(App\Services\NotificationService::class);
    $config = $container->get('config');

    $recipients = $config['notifications']['recipients'];
    $daysAdvance = $config['notifications']['days_advance'] ?? 30;

    if (empty($recipients)) {
        $logger->warning($container->get('translator')('no_notification_recipients_configured'));
        exit(0);
    }

    $logger->info($container->get('translator')('cron_job_notifications_starting', ['%days%' => $daysAdvance, '%emails%' => implode(', ', $recipients)]));

    // Obtener los ítems próximos a expirar.
    $expiringAssets = $notificationService->getExpiringAssets($daysAdvance);
    $expiringContracts = $notificationService->getExpiringContracts($daysAdvance);
    
    // Si hay ítems a notificar, enviar el correo.
    if (!empty($expiringAssets) || !empty($expiringContracts)) {
        $notificationService->sendExpirationNotice($recipients, $expiringAssets, $expiringContracts, $daysAdvance);
        $logger->info($container->get('translator')('email_notification_sent_success', ['%emails%' => implode(', ', $recipients)]));
    } else {
        $logger->info($container->get('translator')('no_expiring_items', ['%s' => $daysAdvance]));
    }

    $logger->info($container->get('translator')('cron_job_notifications_finished'));
    
} catch (\Throwable $e) {
    $logger->critical($container->get('translator')('cron_job_notifications_critical_error', ['%message%' => $e->getMessage(), '%trace%' => $e->getTraceAsString()]));
    exit(1);
    exit(1); // Salida con error
}
