<?php
/**
 * app/bootstrap.php
 * Configuración del contenedor de inyección de dependencias (PHP-DI).
 * Este archivo define cómo PHP-DI crea y gestiona las instancias de las clases
 * y sus dependencias a lo largo de toda la aplicación.
 * El orden de las definiciones es CRÍTICO para resolver las dependencias correctamente.
 */

// --- Importaciones de Clases (Ordenadas alfabéticamente por tipo y luego por nombre) ---

// Interfaces PSR (PHP Standard Recommendation)
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

// Clases de Librerías de Terceros (Vendors)
use DI\Factory; // Para definir fábricas explícitas en PHP-DI
use League\Plates\Engine as PlatesEngine; // Motor de plantillas PlatesPHP
use Monolog\Formatter\LineFormatter;      // Para formatear los logs de Monolog
use Monolog\Handler\StreamHandler;        // Para enviar logs a un stream (ej. archivo) en Monolog
use Monolog\Logger;                       // Clase principal de Monolog para logging
use PHPMailer\PHPMailer\Exception as MailerException; // Excepciones de PHPMailer
use PHPMailer\PHPMailer\PHPMailer;        // Clase principal de PHPMailer para envío de correos
use PHPMailer\PHPMailer\SMTP;             // Clase para constantes SMTP de PHPMailer (ej. ENCRYPTION_STARTTLS)
use League\Csv\Writer;                    // Clase para la importación de CSVs

// Clases de la Aplicación (App\)
// Controladores
use App\Controllers\AdminController;
use App\Controllers\ApiController;
use App\Controllers\AssetController;
use App\Controllers\AuthController;
use App\Controllers\ContractController;
use App\Controllers\CustomFieldsController;
use App\Controllers\DashboardController;
use App\Controllers\LanguageController;
use App\Controllers\MasterController;
use App\Controllers\ModelController;
use App\Controllers\SmtpController;
use App\Controllers\UserController;
use App\Controllers\ImportController;
use App\Controllers\LogController;

// Modelos
use App\Models\AcquisitionFormat;
use App\Models\Asset;
use App\Models\AssetContract;
use App\Models\AssetStatus;
use App\Models\AssetType;
use App\Models\Contract;
use App\Models\ContractType;
use App\Models\CustomFieldDefinition;
use App\Models\CustomFieldValue;
use App\Models\Department;
use App\Models\FileAttachment;
use App\Models\Language;
use App\Models\Location;
use App\Models\Manufacturer;
use App\Models\Model; // Alias para App\Models\Model (evita conflicto con Model de PSR-7 si lo usáramos)
use App\Models\PasswordResetToken;
use App\Models\Provider;
use App\Models\Role;
use App\Models\Source; // Para la nueva gestión de fuentes de usuario
use App\Models\User;
use App\Models\LogActivo;
use App\Models\SmtpConfig;

// Servicios
use App\Services\AuthService;
use App\Services\LdapService; // Para la nueva autenticación LDAP/AD
use App\Services\LogService;
use App\Services\MailService;
use App\Services\NotificationService;
use App\Services\SessionService;
use App\Services\CsvTemplateService;
use App\Services\CsvImporterService;
use App\Services\SmtpService;

// --- 1. Cargar la Configuración de la Aplicación ---

// Se pasa directamente a las definiciones que la necesitan
if (!$container->has('config')) {
    $config_data = require __DIR__ . '/Config/config.php';
    $container->set('config', function () use ($config_data) {
        return $config_data;
    });
}

// --- 2. Definiciones de Servicios Básicos e Infraestructura (sin dependencias complejas entre sí) ---

// 2.1. Configuración de la vista (PlatesPHP)
// Esta definición es fundamental para renderizar todas las vistas HTML de la aplicación.
$container->set(PlatesEngine::class, function (ContainerInterface $c) {
    $config = $c->get('config'); // Obtiene la configuración general desde el contenedor
    $viewsPath = $config['paths']['views']; // Obtiene la ruta base de los directorios de vistas

    // Verificar si el directorio de vistas existe y es un directorio
    if (!is_dir($viewsPath)) {
        error_log("Error de configuración de PlatesPHP: El directorio de vistas '{$viewsPath}' no existe o no es un directorio.");
        // En un entorno de producción, podrías redirigir a una página de error o mostrar un mensaje genérico.
        die('Error crítico: Directorio de plantillas no encontrado. Por favor, contacte al administrador.');
    }

    $engine = new PlatesEngine($viewsPath); // Inicializa PlatesPHP con el directorio base de las vistas

    // Añadir directorios específicos para nombres cortos de plantillas (ej. 'auth/login' en lugar de 'auth/login.php')
    $engine->addFolder('layout', $viewsPath . '/layout');
    $engine->addFolder('auth', $viewsPath . '/auth');
    $engine->addFolder('admin', $viewsPath . '/admin');
    $engine->addFolder('masters', $viewsPath . '/masters');
    $engine->addFolder('partials', $viewsPath . '/partials');
    $engine->addFolder('emails', $viewsPath . '/emails'); // Para plantillas de correo

    // Registrar una función 'asset' para generar URLs de assets estáticos (CSS, JS, imágenes)
    $engine->registerFunction('asset', function (string $path) use ($config) {
        return $config['paths']['public_assets'] . '/' . ltrim($path, '/');
    });

    // Añadir datos globales a todas las vistas de PlatesPHP.
    // Esto hace que servicios como authService, sessionService y la función t() estén
    // disponibles directamente en cualquier plantilla renderizada por Plates.
    $engine->addData([        
        'sessionService' => $c->get(App\Services\SessionService::class),
        'config' => $config,
        't' => $c->get('translator'),
        // Inyectamos el contenedor para resolver AuthService de forma perezosa y romper la dependencia circular.
        'container' => $c 
    ]);

    return $engine;
});

// 2.2. Conexión a la base de datos (PDO)
// La instancia de PDO es fundamental y es una dependencia de muchos modelos.
$container->set('db', function (ContainerInterface $c) {
    $dbConfig = $c->get('config')['db']; // Obtiene la configuración de la DB
    $dsn = "mysql:host={$dbConfig['host']};dbname={$dbConfig['name']};charset={$dbConfig['charset']}";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,      // Lanza PDOException en errores
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,            // Fetch por defecto como array asociativo
        PDO::ATTR_EMULATE_PREPARES   => false,                       // Deshabilita emulación de prepares (para prepared statements reales)
    ];
    try {
        $pdo = new PDO($dsn, $dbConfig['user'], $dbConfig['pass'], $options);
        return $pdo;
    } catch (PDOException $e) {
        // Registrar error de conexión fatal y detener la aplicación
        error_log("Error FATAL de conexión a la base de datos: " . $e->getMessage());
        die('Error crítico: Fallo en la conexión a la base de datos. Por favor, contacte al administrador.');
    }
});
// Mapeo para PDO::class: Le dice a PHP-DI que cuando una clase pida 'PDO' por tipo,
// debe usar la definición del servicio 'db' que acabamos de crear.
$container->set(PDO::class, \DI\Factory(function (ContainerInterface $c) {
    return $c->get('db'); // Retorna la instancia de PDO ya definida como 'db'
}));


// 2.3. Logger (Monolog)
// Configuración del logger principal de la aplicación.
$container->set('logger', function (ContainerInterface $c) {
    $config = $c->get('config'); // Obtiene la configuración general
    $logPath = $config['paths']['logs'] . '/app.log'; // Ruta al archivo de log principal
    $app_name = $config['app']['name'] ?? 'CMDB_App_Logger'; // Nombre de la aplicación para el logger
    $logLevel = $config['app']['env'] === 'development' ? Logger::DEBUG : Logger::INFO; // Nivel de log (DEBUG en dev, INFO en prod)

    $logger = new Logger($app_name); // Crea una instancia de Monolog Logger
    $formatter = new LineFormatter(
        "[%datetime%] %channel%.%level_name%: %message% %context% %extra%\\n", // Formato de la línea de log
        "Y-m-d H:i:s", // Formato de la fecha/hora
        true,          // Incluir stack traces para excepciones
        true           // Permitir saltos de línea dentro del mensaje
    );
    $streamHandler = new StreamHandler($logPath, $logLevel); // Handler para escribir a un archivo
    $streamHandler->setFormatter($formatter); // Asigna el formateador al handler
    $logger->pushHandler($streamHandler); // Añade el handler al logger

    return $logger;
});
// Mapeo para Psr\Log\LoggerInterface: Le dice a PHP-DI que cuando una clase pida la interfaz
// LoggerInterface, debe usar la definición del servicio 'logger'.
$container->set(Psr\Log\LoggerInterface::class, \DI\Factory(function (ContainerInterface $c) {
    return $c->get('logger'); // Retorna la instancia de logger ya definida
}));


// 2.4. Servicio de Sesión (SessionService)
// Encapsula la gestión de sesiones de PHP.
$container->set(App\Services\SessionService::class, function (ContainerInterface $c) {
    $config = $c->get('config'); // El SessionService constructor espera el array de config completo
    return new App\Services\SessionService(
        $c->get('config')['session']
    );
});


// 2.5. Traductor (Translator Callable)
// Proporciona la función `t()` para la internacionalización.
$container->set('translator', function (ContainerInterface $c) {
    $langConfig = $c->get('config')['lang']; // Configuración de rutas de archivos de idioma
    $sessionService = $c->get(App\Services\SessionService::class); // Necesita el servicio de sesión para el idioma del usuario
    $defaultLang = $c->get('config')['app']['default_language'] ?? 'es'; // Idioma por defecto de la aplicación

    // La función de traducción real que se devolverá
    return function (string $key, array $replacements = [], ?string $langCode = null) use ($langConfig, $sessionService, $defaultLang) {
        // Determina el idioma actual: el que se pasa > el de la sesión > el por defecto
        $currentLang = $langCode ?? $sessionService->getUserLanguage() ?? $defaultLang;
        $translations = [];

        // Carga el archivo de idioma. Si no existe o no es válido, usa el por defecto.
        $langFilePath = $langConfig[$currentLang] ?? null;
        if (!$langFilePath || !file_exists($langFilePath)) {
            $langFilePath = $langConfig[$defaultLang]; // Fallback al idioma por defecto
        }
        
        if (file_exists($langFilePath)) {
            // Carga el array de traducciones desde el archivo PHP
            $translations = require $langFilePath;
        }

        // Obtiene el texto. Si la clave no existe, devuelve la clave literal (para depuración).
        $text = $translations[$key] ?? $key;
        
        // Reemplaza los placeholders (ej. %s, %name%) en el texto.
        foreach ($replacements as $placeholder => $value) {
            $text = str_replace($placeholder, $value, $text);
        }
        return $text;
    };
});


// 2.6. PHPMailer (Clase de envío de correos)
// Configuración de la instancia de PHPMailer.
$container->set(PHPMailer::class, function (ContainerInterface $c) {    
    // Simplemente crea una instancia de PHPMailer.
    // La configuración se aplicará dinámicamente en MailService
    // para asegurar que siempre se usen los datos más recientes de la BBDD.
    // El 'true' habilita las excepciones, que serán capturadas en MailService.
    return new PHPMailer(true);
});

// 2.7. Definición para CsvTemplateService (Importación de CSVs)
$container->set(App\Services\CsvTemplateService::class, function (ContainerInterface $c) {
    return new App\Services\CsvTemplateService(
        $c->get(Psr\Log\LoggerInterface::class),
        $c->get('translator'),
        $c->get(App\Models\AssetType::class),
        $c->get(App\Models\CustomFieldDefinition::class)
    );
});

// 2.8. Servicio LDAP (LdapService)
$container->set(App\Services\LdapService::class, function (ContainerInterface $c) {
    return new App\Services\LdapService(
        $c->get(LoggerInterface::class),
        $c->get('translator')
    );
});

// 2.9. Servicio de Logs (LogService)
$container->set(App\Services\LogService::class, function (ContainerInterface $c) {
    return new App\Services\LogService(
        $c->get(PDO::class),
        $c->get(LoggerInterface::class)
    );
});

// 2.10. Servicio SMTP (SmtpService)
$container->set(App\Services\SmtpService::class, function (ContainerInterface $c) {
    return new App\Services\SmtpService(
        $c->get('config'),
        $c->get(LoggerInterface::class),
        $c->get('translator'),
        $c->get(PDO::class)
    );
});

// --- 3. Definiciones de Modelos (Dependen principalmente de PDO 'db') ---
// Todos estos modelos necesitan una instancia de PDO en su constructor.
// Se ordenan alfabéticamente para mayor claridad.
$container->set(App\Models\AcquisitionFormat::class, function (ContainerInterface $c) { return new App\Models\AcquisitionFormat($c->get(PDO::class)); });
$container->set(App\Models\Asset::class, function (ContainerInterface $c) {
    return new App\Models\Asset($c->get(PDO::class), $c->get(Psr\Log\LoggerInterface::class));
});
$container->set(App\Models\AssetContract::class, function (ContainerInterface $c) { return new App\Models\AssetContract($c->get(PDO::class)); });
$container->set(App\Models\AssetStatus::class, function (ContainerInterface $c) { return new App\Models\AssetStatus($c->get(PDO::class)); });
$container->set(App\Models\AssetType::class, function (ContainerInterface $c) { return new App\Models\AssetType($c->get(PDO::class)); });
$container->set(App\Models\Contract::class, function (ContainerInterface $c) { return new App\Models\Contract($c->get(PDO::class)); });
$container->set(App\Models\ContractType::class, function (ContainerInterface $c) { return new App\Models\ContractType($c->get(PDO::class)); });
$container->set(App\Models\CustomFieldDefinition::class, function (ContainerInterface $c) { return new App\Models\CustomFieldDefinition($c->get(PDO::class)); });
$container->set(App\Models\CustomFieldValue::class, function (ContainerInterface $c) { return new App\Models\CustomFieldValue($c->get(PDO::class)); });
$container->set(App\Models\Department::class, function (ContainerInterface $c) { return new App\Models\Department($c->get(PDO::class)); });
$container->set(App\Models\FileAttachment::class, function (ContainerInterface $c) { return new App\Models\FileAttachment($c->get(PDO::class)); });
$container->set(App\Models\Language::class, function (ContainerInterface $c) { return new App\Models\Language($c->get(PDO::class)); });
$container->set(App\Models\Location::class, function (ContainerInterface $c) { return new App\Models\Location($c->get(PDO::class)); });
$container->set(App\Models\Manufacturer::class, function (ContainerInterface $c) { return new App\Models\Manufacturer($c->get(PDO::class)); });
$container->set(App\Models\Model::class, function (ContainerInterface $c) { return new App\Models\Model($c->get(PDO::class)); });
$container->set(App\Models\PasswordResetToken::class, function (ContainerInterface $c) { return new App\Models\PasswordResetToken($c->get(PDO::class)); });
$container->set(App\Models\Provider::class, function (ContainerInterface $c) { return new App\Models\Provider($c->get(PDO::class)); });
$container->set(App\Models\Role::class, function (ContainerInterface $c) { return new App\Models\Role($c->get(PDO::class)); });
$container->set(App\Models\Source::class, function (ContainerInterface $c) { return new App\Models\Source($c->get(PDO::class)); });
$container->set(App\Models\User::class, function (ContainerInterface $c) { return new App\Models\User($c->get(PDO::class)); });
$container->set(App\Models\LogActivo::class, function (ContainerInterface $c) { return new App\Models\LogActivo($c->get(PDO::class)); });
$container->set(App\Models\SmtpConfig::class, function (ContainerInterface $c) { return new App\Models\SmtpConfig($c->get(PDO::class)); });


// --- 4. Definiciones de Servicios Más Complejos (Dependen de los básicos y modelos) ---
// Estos servicios orquestan la lógica de negocio y dependen de otros servicios o modelos.
// Se ordenan por dependencia o alfabéticamente si no hay dependencia clara.

// Definición para CsvTemplateService
$container->set(App\Services\CsvTemplateService::class, function (ContainerInterface $c) {
    return new App\Services\CsvTemplateService(
        $c->get(Psr\Log\LoggerInterface::class),
        $c->get('translator'),
        $c->get(App\Models\AssetType::class), // Depende de AssetType
        $c->get(App\Models\CustomFieldDefinition::class) // Depende de CustomFieldDefinition
    );
});

// Definición para CsvImporterService (NUEVO)
// Inyecta todos los modelos que puede necesitar para importar diferentes entidades.
$container->set(App\Services\CsvImporterService::class, function (ContainerInterface $c) {
    return new App\Services\CsvImporterService(
        $c->get(Psr\Log\LoggerInterface::class),
        $c->get('translator'),
        $c->get(App\Models\Asset::class),
        $c->get(App\Models\Manufacturer::class),
        $c->get(App\Models\Provider::class),
        $c->get(App\Models\Contract::class),
        $c->get(App\Models\AssetType::class),
        $c->get(App\Models\AssetStatus::class),
        $c->get(App\Models\ContractType::class),
        $c->get(App\Models\Location::class),
        $c->get(App\Models\Department::class),
        $c->get(App\Models\AcquisitionFormat::class),
        $c->get(App\Models\Model::class)
    );
});

$container->set(App\Services\AuthService::class, function (ContainerInterface $c) {
    return new App\Services\AuthService(
        $c->get(App\Models\User::class),
        $c->get(App\Models\PasswordResetToken::class),
        $c->get(App\Models\Role::class),
        $c->get(App\Services\SessionService::class),
        $c->get(App\Services\MailService::class),
        $c->get(Psr\Log\LoggerInterface::class),
        $c->get('config'),
        $c->get(App\Services\LdapService::class), // La inyección de LdapService
        $c->get(App\Models\Source::class),
        $c->get('translator')
    );
});

$container->set(App\Services\MailService::class, function (ContainerInterface $c) {
    return new App\Services\MailService(
        $c->get(PHPMailer::class), // Usamos la definición de la clase PHPMailer
        $c->get(Psr\Log\LoggerInterface::class),
        $c->get(PlatesEngine::class),
        $c->get(App\Services\SmtpService::class)
    );
});


// --- 5. Definiciones de Controladores (Dependen de servicios y modelos) ---
// Todos los controladores dependen de PlatesEngine, SessionService, LoggerInterface, translator, etc.
$container->set(App\Controllers\AdminController::class, function (ContainerInterface $c) {
    return new App\Controllers\AdminController(
        $c->get(PlatesEngine::class),
        $c->get(App\Services\SessionService::class),
        $c->get(App\Services\AuthService::class),
        $c->get('config'),
        $c->get('translator')
    );
});

$container->set(App\Controllers\ApiController::class, function (ContainerInterface $c) {
    return new App\Controllers\ApiController(
        $c, // Pasamos el contenedor completo si el controlador necesita acceder a él directamente
        $c->get(Psr\Log\LoggerInterface::class),
        $c->get('translator'),
        $c->get(App\Services\MailService::class)
    );
});

$container->set(App\Controllers\AssetController::class, function (ContainerInterface $c) {
    return new App\Controllers\AssetController(
        $c->get(PlatesEngine::class),
        $c->get(App\Services\SessionService::class),
        $c->get('logger'),
        $c->get(App\Models\Asset::class),
        $c->get(App\Models\AssetType::class),
        $c->get(App\Models\Manufacturer::class),
        $c->get(App\Models\Model::class),
        $c->get(App\Models\AssetStatus::class),
        $c->get(App\Models\Location::class),
        $c->get(App\Models\Department::class),
        $c->get(App\Models\AcquisitionFormat::class),
        $c->get(App\Models\Provider::class),
        $c->get('config'),
        $c->get(App\Services\LogService::class),
        $c->get(App\Models\FileAttachment::class),
        $c->get(App\Models\Contract::class),
        $c->get(App\Models\AssetContract::class),
        $c->get(App\Models\CustomFieldDefinition::class),
        $c->get(App\Models\CustomFieldValue::class),
        $c->get('translator')
    );
});

$container->set(App\Controllers\AuthController::class, function (ContainerInterface $c) {
    return new App\Controllers\AuthController(
        $c->get(PlatesEngine::class),
        $c->get('logger'),
        $c->get(App\Services\AuthService::class),
        $c->get(App\Services\SessionService::class),
        $c->get('config'),
        $c->get('translator'),
        $c->get(App\Models\Source::class)
    );
});

$container->set(App\Controllers\ContractController::class, function (ContainerInterface $c) {
    return new App\Controllers\ContractController(
        $c->get(PlatesEngine::class),
        $c->get(App\Services\SessionService::class),
        $c->get('logger'),
        $c->get(App\Models\Contract::class),
        $c->get(App\Models\ContractType::class),
        $c->get(App\Models\Provider::class),
        $c->get('translator')
    );
});

$container->set(App\Controllers\CustomFieldsController::class, function (ContainerInterface $c) {
    return new App\Controllers\CustomFieldsController(
        $c->get(PlatesEngine::class),
        $c->get(App\Services\SessionService::class),
        $c->get('logger'),
        $c->get(App\Models\CustomFieldDefinition::class),
        $c->get(App\Models\AssetType::class),
        $c->get('translator')
    );
});

// Definición para DashboardController
$container->set(App\Controllers\DashboardController::class, function (ContainerInterface $c) {
    return new App\Controllers\DashboardController(
        $c->get(PlatesEngine::class),
        $c->get(App\Services\SessionService::class),
        $c->get(App\Services\AuthService::class),
        $c->get('config'),
        $c->get('translator'),
        $c->get(App\Models\Asset::class),
        $c->get(App\Models\Contract::class),
        $c->get(App\Models\AssetType::class),
        $c->get(App\Models\AssetStatus::class),
        $c->get(App\Models\ContractType::class)
    );
});

$container->set(App\Controllers\LanguageController::class, function (ContainerInterface $c) {
    return new App\Controllers\LanguageController(
        $c->get(App\Services\SessionService::class),
        $c->get('config')
    );
});

$container->set(App\Controllers\MasterController::class, function (ContainerInterface $c) {
    return new App\Controllers\MasterController(
        $c->get(PlatesEngine::class),
        $c->get(App\Services\SessionService::class),
        $c->get('logger'),
        $c->get('db'),
        $c->get('translator')
    );
});

$container->set(App\Controllers\ModelController::class, function (ContainerInterface $c) {
    return new App\Controllers\ModelController(
        $c->get(PlatesEngine::class),
        $c->get(App\Services\SessionService::class),
        $c->get('logger'),
        $c->get(App\Models\Model::class),
        $c->get(App\Models\Manufacturer::class),
        $c->get('config'),
        $c->get('translator')
    );
});

$container->set(App\Controllers\UserController::class, function (ContainerInterface $c) {
    return new App\Controllers\UserController(
        $c->get(PlatesEngine::class),
        $c->get(App\Services\SessionService::class),
        $c->get('logger'),
        $c->get(App\Models\User::class),
        $c->get(App\Models\Role::class),
        $c->get('translator'),
        $c->get(App\Models\Source::class)
    );
});

// Definición para ImportController
$container->set(App\Controllers\ImportController::class, function (ContainerInterface $c) {
    return new App\Controllers\ImportController(
        $c->get(PlatesEngine::class),
        $c->get(App\Services\SessionService::class),
        $c->get('logger'),
        $c->get(App\Services\CsvTemplateService::class),
        $c->get('translator'),
        $c->get(App\Models\AssetType::class),
        $c->get(App\Services\CsvImporterService::class)
    );
});

$container->set(App\Controllers\SmtpController::class, function (ContainerInterface $c) {
    return new App\Controllers\SmtpController(
        $c->get(PlatesEngine::class),
        $c->get(App\Services\SessionService::class),
        $c->get(LoggerInterface::class),
        $c->get('config'),
        $c->get('translator'),
        $c->get(App\Services\SmtpService::class)
    );
});

// Definición para SourceController
$container->set(App\Controllers\SourceController::class, function (ContainerInterface $c) {
    return new App\Controllers\SourceController(
        $c->get(PlatesEngine::class),
        $c->get(App\Services\SessionService::class),
        $c->get(Psr\Log\LoggerInterface::class),
        $c->get(App\Models\Source::class),
        $c->get('translator')
    );
});

// Definición para SourceController
$container->set(App\Controllers\SourceController::class, function (ContainerInterface $c) {
    return new App\Controllers\SourceController(
        $c->get(PlatesEngine::class),
        $c->get(App\Services\SessionService::class),
        $c->get(Psr\Log\LoggerInterface::class),
        $c->get(App\Models\Source::class),
        $c->get('translator')
    );
});


$container->set(App\Controllers\LogController::class, function (ContainerInterface $c) {
    return new App\Controllers\LogController(
        $c->get(PlatesEngine::class),
        $c->get(App\Services\SessionService::class),
        $c->get(Psr\Log\LoggerInterface::class),
        $c->get(App\Services\LogService::class),
        $c->get('translator')
    );
});
