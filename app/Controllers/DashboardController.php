<?php
/**
 * app/Controllers/DashboardController.php
 *
 * Este controlador gestiona la lógica de la página principal (dashboard).
 * Es responsable de obtener las métricas clave de la CMDB y pasarlas a la vista
 * para su visualización.
 */

namespace App\Controllers;

// --- Importaciones de Clases ---
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use League\Plates\Engine as PlatesEngine;
use App\Services\SessionService;
use App\Services\AuthService;
use App\Models\Asset;         // Modelo para la gestión de activos.
use App\Models\Contract;      // Modelo para la gestión de contratos.
use App\Models\AssetType;     // Modelo para la gestión de tipos de activo.
use App\Models\AssetStatus;   // Modelo para la gestión de estados de activo.
use App\Models\ContractType;  // Modelo para la gestión de tipos de contrato.
use DateTime;                 // Clase nativa de PHP para el manejo de fechas.
use DateInterval;             // Clase nativa de PHP para la manipulación de intervalos de fechas.

/**
 * Clase DashboardController
 * Controla la lógica de la página principal del panel de control.
 */
class DashboardController
{
    private PlatesEngine $view;
    private SessionService $sessionService;
    private AuthService $authService;
    private array $config;
    private $translator;
    private Asset $assetModel;         // Propiedad para el modelo de activos.
    private Contract $contractModel;      // Propiedad para el modelo de contratos.
    private AssetType $assetTypeModel;     // Propiedad para el modelo de tipos de activo.
    private AssetStatus $assetStatusModel;   // Propiedad para el modelo de estados de activo.
    private ContractType $contractTypeModel;  // Propiedad para el modelo de tipos de contrato.

    /**
     * Constructor de la clase. Inyecta todas las dependencias necesarias.
     * @param PlatesEngine $view
     * @param SessionService $sessionService
     * @param AuthService $authService
     * @param array $config
     * @param callable $translator
     * @param Asset $assetModel
     * @param Contract $contractModel
     * @param AssetType $assetTypeModel
     * @param AssetStatus $assetStatusModel
     * @param ContractType $contractTypeModel
     */
    public function __construct(
        PlatesEngine $view,
        SessionService $sessionService,
        AuthService $authService,
        array $config,
        callable $translator,
        Asset $assetModel,
        Contract $contractModel,
        AssetType $assetTypeModel,
        AssetStatus $assetStatusModel,
        ContractType $contractTypeModel
    ) {
        $this->view = $view;
        $this->sessionService = $sessionService;
        $this->authService = $authService;
        $this->config = $config;
        $this->translator = $translator;
        $this->assetModel = $assetModel;
        $this->contractModel = $contractModel;
        $this->assetTypeModel = $assetTypeModel;
        $this->assetStatusModel = $assetStatusModel;
        $this->contractTypeModel = $contractTypeModel;
    }

    /**
     * Muestra la página principal del dashboard con métricas clave de la CMDB.
     * @param Request $request La solicitud HTTP.
     * @param Response $response La respuesta HTTP.
     * @return Response La respuesta HTTP con la vista renderizada.
     */
    public function showDashboard(Request $request, Response $response): Response
    {
        $t = $this->translator;

        // --- Obtener Métricas para el Dashboard ---
        // Se obtienen los datos de los modelos para alimentar los gráficos y resúmenes.
        $totalAssets = $this->assetModel->countAll();
        $totalContracts = $this->contractModel->countAll();
        $assetsByStatus = $this->assetModel->countByStatus();
        $assetsByType = $this->assetModel->countByType();

        // Activos y Contratos próximos a expirar (para la sección "Próximos a Caducar").
        $daysAdvance = $this->config['notifications']['days_advance'] ?? 30;
        $notificationThreshold = (new DateTime())->add(new DateInterval("P{$daysAdvance}D"))->format('Y-m-d');
        
        $expiringAssets = $this->assetModel->getExpiringAssets($notificationThreshold) ?: [];
        $expiringContracts = $this->contractModel->getExpiringContracts($notificationThreshold) ?: [];

        // Se renderiza la vista 'dashboard' con todos los datos obtenidos.
        $html = $this->view->render('dashboard', [
            'pageTitle' => $t('dashboard'),
            'flashMessages' => $this->sessionService->getFlashMessages(),
            'totalAssets' => $totalAssets,
            'totalContracts' => $totalContracts,
            'assetsByStatus' => $assetsByStatus,
            'assetsByType' => $assetsByType,
            'expiringAssets' => $expiringAssets,
            'expiringContracts' => $expiringContracts,
            'daysAdvance' => $daysAdvance,
            // Pasar los nombres de los maestros para las leyendas de los gráficos.
            'assetTypesList' => $this->assetTypeModel->getAll() ?: [],
            'assetStatusesList' => $this->assetStatusModel->getAll() ?: [],
            'contractTypesList' => $this->contractTypeModel->getAll() ?: [],
        ]);

        $response->getBody()->write($html);
        return $response;
    }
}
