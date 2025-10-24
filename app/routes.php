<?php
/**
 * app/routes.php
 *
 * Este archivo centraliza la definición de todas las rutas de la aplicación
 * utilizando el enrutador de Slim Framework. Las rutas están organizadas
 * en grupos para una mejor estructura y aplicación de middlewares.
 */

use Slim\Routing\RouteCollectorProxy;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;

// --- Importaciones de Clases (Controladores y Middlewares) ---
use App\Controllers\AuthController;
use App\Controllers\DashboardController;
use App\Controllers\AdminController;
use App\Controllers\LanguageController;
use App\Controllers\MasterController;
use App\Controllers\ModelController;
use App\Controllers\AssetController;
use App\Controllers\ContractController;
use App\Controllers\CustomFieldsController;
use App\Controllers\ApiController;
use App\Controllers\SourceController;
use App\Controllers\ImportController;
use App\Controllers\LogController;
use App\Controllers\SmtpController;
use App\Controllers\ProfileController;
use App\Controllers\PageController;
use App\Controllers\MfaController;
use App\Middlewares\AuthMiddleware;
use App\Middlewares\RoleMiddleware;
use App\Services\AuthService;
use App\Services\SessionService;
use App\Middlewares\RequestLogMiddleware;
use App\Middlewares\LanguageMiddleware; // <-- ¡NUEVO! Importar el middleware de idioma.

// Importar modelos si es necesario dentro de las closures de las rutas API
use App\Models\CustomFieldDefinition;
use App\Models\Model as AssetModel;

// --- REGISTRO DE MIDDLEWARE GLOBAL ---
// Este middleware se añade aquí para que se ejecute en cada petición.
$app->add(RequestLogMiddleware::class);
$app->add(LanguageMiddleware::class); // <-- ¡NUEVO! Aplicar el middleware de idioma globalmente.

// --- RUTAS PÚBLICAS (Accesibles sin autenticación) ---

// Ruta principal, redirige al login si no está autenticado
$app->get('/', function (Request $request, Response $response) {
    return $response->withHeader('Location', '/login')->withStatus(302);
});

// Rutas del sistema de autenticación (Login, Forgot Password, Reset Password)
$app->group('', function (RouteCollectorProxy $group) {
    $group->get('/login', AuthController::class . ':showLogin');
    $group->post('/login', AuthController::class . ':authenticate');
    $group->get('/forgot-password', AuthController::class . ':showForgotPassword');
    $group->post('/forgot-password', AuthController::class . ':processForgotPassword');
    $group->get('/reset-password', AuthController::class . ':showResetPassword');
    $group->post('/reset-password', AuthController::class . ':processResetPassword');
});

// Ruta para cambiar el idioma (accesible públicamente)
$app->get('/set-language/{lang_code}', App\Controllers\LanguageController::class . ':setLanguage');

// Rutas para la verificación de MFA durante el login
$app->get('/mfa/verify-login', MfaController::class . ':showVerifyLoginForm');
$app->post('/mfa/verify-login', MfaController::class . ':processVerifyLogin');

// Ruta para la política de cookies
$app->get('/cookie-policy', PageController::class . ':showCookiePolicy');


// --- RUTAS PROTEGIDAS (Requieren autenticación) ---
// Estas rutas están envueltas en el middleware de autenticación.
$app->group('', function (RouteCollectorProxy $authenticatedGroup) {
    
    // Ruta del dashboard (página principal)
    $authenticatedGroup->get('/dashboard', DashboardController::class . ':showDashboard');

    // Ruta de logout (cerrar sesión)
    $authenticatedGroup->get('/logout', AuthController::class . ':logout');

    // Rutas para el Perfil de Usuario
    $authenticatedGroup->get('/profile', ProfileController::class . ':showProfile');
    $authenticatedGroup->post('/profile', ProfileController::class . ':updateProfile');
    $authenticatedGroup->get('/profile/device/revoke/{token_hash}', ProfileController::class . ':revokeDevice');

    // Rutas para la configuración de MFA
    $authenticatedGroup->get('/mfa/setup', MfaController::class . ':showMfaSetup');
    $authenticatedGroup->post('/mfa/verify-setup', MfaController::class . ':verifyMfaSetup');
    $authenticatedGroup->get('/mfa/disable', MfaController::class . ':disableMfa');
    // Nuevas rutas para el dispositivo de confianza
    $authenticatedGroup->get('/mfa/trust-device', MfaController::class . ':showTrustDeviceForm');
    $authenticatedGroup->post('/mfa/trust-device/process', MfaController::class . ':processTrustDevice');


    // Grupo de rutas para la gestión de Activos (CRUD)
    $authenticatedGroup->group('/assets', function (RouteCollectorProxy $assetGroup) {
        $assetGroup->get('', AssetController::class . ':listAssets');
        $assetGroup->get('/', AssetController::class . ':listAssets');
        $assetGroup->get('/create', AssetController::class . ':showAssetForm');
        $assetGroup->post('/create', AssetController::class . ':processAssetForm');
        $assetGroup->get('/edit/{id}', AssetController::class . ':showAssetForm');
        $assetGroup->post('/update/{id}', AssetController::class . ':processAssetForm');
        $assetGroup->post('/delete/{id}', AssetController::class . ':processDelete');
    })->add(RoleMiddleware::create('Modificacion'));

    // Grupo de rutas para la Administración (solo para rol 'Administrador')
    $authenticatedGroup->group('/admin', function (RouteCollectorProxy $adminGroup) {

        // --- RUTAS PARA MÓDULO DE IMPORTACIÓN MASIVA (ImportController) ---
        $adminGroup->group('/import', function (RouteCollectorProxy $importGroup) {
            $importGroup->get('', App\Controllers\ImportController::class . ':showImportOptions');
            $importGroup->get('/', App\Controllers\ImportController::class . ':showImportOptions');
            $importGroup->get('/confirm-models', App\Controllers\ImportController::class . ':showConfirmModels');
            $importGroup->post('/process-confirmed-import', App\Controllers\ImportController::class . ':processConfirmedImport');
            $importGroup->get('/results', App\Controllers\ImportController::class . ':showImportResults');
            $importGroup->get('/download-log', App\Controllers\ImportController::class . ':downloadImportLog');
            $importGroup->get('/template/{entity_type}[/{asset_type_id}]', App\Controllers\ImportController::class . ':downloadTemplate');
            $importGroup->get('/{entity_type}/upload', App\Controllers\ImportController::class . ':showUploadForm');
            $importGroup->post('/{entity_type}/process', App\Controllers\ImportController::class . ':processUpload');
        });

        // --- RUTAS PARA GESTIÓN DE USUARIOS (UserController) ---
        $adminGroup->get('/users', App\Controllers\UserController::class . ':listUsers');
        $adminGroup->get('/users/create', App\Controllers\UserController::class . ':showForm');
        $adminGroup->post('/users/create', App\Controllers\UserController::class . ':processForm');
        $adminGroup->get('/users/edit/{id}', App\Controllers\UserController::class . ':showForm');
        $adminGroup->post('/users/update/{id}', App\Controllers\UserController::class . ':processForm');
        $adminGroup->post('/users/delete/{id}', App\Controllers\UserController::class . ':processDelete');

        // --- RUTAS PARA LA AUDITORÍA DE LOGS (LogController) ---
        $adminGroup->group('/logs', function (RouteCollectorProxy $logGroup) {
             $logGroup->get('', LogController::class . ':listLogs');
             $logGroup->get('/', LogController::class . ':listLogs');
        });

        // --- RUTAS PARA CONFIGURACIÓN SMTP (SmtpController) ---
        $adminGroup->group('/smtp', function (RouteCollectorProxy $smtpGroup) {
             $smtpGroup->get('', SmtpController::class . ':showForm');
             $smtpGroup->get('/', SmtpController::class . ':showForm');
             $smtpGroup->post('/update', SmtpController::class . ':processForm');
        });

        // --- RUTAS PARA CAMPOS PERSONALIZADOS (CustomFieldsController) ---
        $adminGroup->group('/custom-fields', function (RouteCollectorProxy $customFieldsGroup) {
            $customFieldsGroup->get('', CustomFieldsController::class . ':listDefinitions');
            $customFieldsGroup->get('/', CustomFieldsController::class . ':listDefinitions');
            $customFieldsGroup->get('/create', CustomFieldsController::class . ':showForm');
            $customFieldsGroup->post('/create', CustomFieldsController::class . ':processForm');
            $customFieldsGroup->get('/edit/{id}', CustomFieldsController::class . ':showForm');
            $customFieldsGroup->post('/update/{id}', CustomFieldsController::class . ':processForm');
            $customFieldsGroup->post('/delete/{id}', CustomFieldsController::class . ':processDelete');
        });

        // --- RUTAS PARA MAESTROS GENÉRICOS (MasterController, ModelController, ContractController) ---
        $adminGroup->group('/masters', function (RouteCollectorProxy $masterGroup) {

            // Rutas para Modelos
            $masterGroup->group('/model', function (RouteCollectorProxy $modelGroup) {
                $modelGroup->get('', ModelController::class . ':listModels');
                $modelGroup->get('/', ModelController::class . ':listModels');
                $modelGroup->get('/create', ModelController::class . ':showForm');
                $modelGroup->post('/create', ModelController::class . ':processForm');
                $modelGroup->get('/edit/{id}', ModelController::class . ':showForm');
                $modelGroup->post('/update/{id}', ModelController::class . ':processForm');
                $modelGroup->post('/delete/{id}', ModelController::class . ':processDelete');
            });

            // Rutas para Contratos
            $masterGroup->group('/contract', function (RouteCollectorProxy $contractGroup) {
                $contractGroup->get('', ContractController::class . ':listContracts');
                $contractGroup->get('/', ContractController::class . ':listContracts');
                $contractGroup->get('/create', ContractController::class . ':showForm');
                $contractGroup->post('/create', ContractController::class . ':processForm');
                $contractGroup->get('/edit/{id}', ContractController::class . ':showForm');
                $contractGroup->post('/update/{id}', ContractController::class . ':processForm');
                $contractGroup->post('/delete/{id}', ContractController::class . ':processDelete');
            });

            // Rutas para Maestros Genéricos (ej. manufacturer, asset-type, etc.)
            $allowedMasterNames = [
                'manufacturer', 'asset-type', 'asset-status', 'contract-type',
                'location', 'department', 'provider', 'acquisition-format', 'language'
            ];
            $masterNamesRegex = implode('|', $allowedMasterNames);

            $masterGroup->get('/{master_name:' . $masterNamesRegex . '}', MasterController::class . ':listItems');
            $masterGroup->get('/{master_name:location}/detail/{id}', MasterController::class . ':showItemDetail'); // <-- NUEVA RUTA
            $masterGroup->get('/{master_name:' . $masterNamesRegex . '}/create', MasterController::class . ':showCreateForm');
            $masterGroup->post('/{master_name:' . $masterNamesRegex . '}/create', MasterController::class . ':processCreate');
            $masterGroup->get('/{master_name:' . $masterNamesRegex . '}/edit/{id}', MasterController::class . ':showEditForm');
            $masterGroup->post('/{master_name:' . $masterNamesRegex . '}/update/{id}', MasterController::class . ':processUpdate');
            $masterGroup->post('/{master_name:language}/toggle-status/{id}', MasterController::class . ':processToggleStatus'); // <-- NUEVA RUTA
            $masterGroup->post('/{master_name:' . $masterNamesRegex . '}/delete/{id}', MasterController::class . ':processDelete');
        });

        // --- RUTAS PARA GESTIÓN DE FUENTES DE USUARIO (SourceController) ---
        $adminGroup->group('/sources', function (RouteCollectorProxy $sourcesGroup) {
            $sourcesGroup->get('', App\Controllers\SourceController::class . ':listSources');
            $sourcesGroup->get('/', App\Controllers\SourceController::class . ':listSources');
            $sourcesGroup->get('/create', App\Controllers\SourceController::class . ':showForm');
            $sourcesGroup->post('/create', App\Controllers\SourceController::class . ':processForm');
            $sourcesGroup->get('/edit/{id}', App\Controllers\SourceController::class . ':showForm');
            $sourcesGroup->post('/update/{id}', App\Controllers\SourceController::class . ':processForm');
            $sourcesGroup->post('/delete/{id}', App\Controllers\SourceController::class . ':processDelete');
        });

    })->add(RoleMiddleware::create('Administrador'));

})->add(AuthMiddleware::class);

// --- RUTAS DE API (que devuelven JSON) ---
// Estas rutas también deben estar protegidas con autenticación
$app->group('/api', function (RouteCollectorProxy $apiGroup) use ($app) {

    $apiGroup->get('/custom-fields/definitions/{asset_type_id}', App\Controllers\ApiController::class . ':getCustomFieldDefinitions');
    $apiGroup->get('/models/byManufacturer/{manufacturer_id}', App\Controllers\ApiController::class . ':getModelsByManufacturer');
    $apiGroup->post('/sources/test-connection', App\Controllers\ApiController::class . ':testSourceConnection');
    $apiGroup->post('/smtp/test-connection', App\Controllers\ApiController::class . ':testSmtpConnection');
    $apiGroup->get('/test-email', App\Controllers\ApiController::class . ':sendTestEmail');
    $apiGroup->post('/geocode', App\Controllers\ApiController::class . ':geocode'); // <-- NUEVA RUTA

})->add(AuthMiddleware::class);

// Ejecutar la creación/activación del usuario administrador por defecto si está habilitado
$app->add(function (Request $request, RequestHandler $handler) use ($container) {
    $authService = $container->get(AuthService::class);
    $config = $container->get('config');

    if ($config['app']['admin_user']['enabled']) {
        $authService->ensureDefaultAdminUser();
    }
    return $handler->handle($request);
});
