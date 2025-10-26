<?php
/**
 * app/Controllers/LogController.php
 *
 * Este controlador gestiona la lógica de visualización de los logs de la aplicación.
 * Permite a los usuarios administradores consultar el historial de cambios de los activos.
 */

namespace App\Controllers;

// --- Importaciones de Clases ---
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use League\Plates\Engine as PlatesEngine;
use App\Services\SessionService;
use App\Services\LogService; // Servicio para obtener los logs de la base de datos
use Psr\Log\LoggerInterface;
use Exception;

/**
 * Clase LogController
 * Controla la visualización de los logs de la aplicación.
 * Solo accesible para usuarios con privilegios de administrador.
 */
class LogController
{
    private PlatesEngine $view;
    private SessionService $sessionService;
    private LoggerInterface $logger;
    private LogService $logService; // Servicio para interactuar con la tabla de logs
    private $translator;

    /**
     * Constructor de la clase. Inyecta todas las dependencias necesarias.
     * @param PlatesEngine $view
     * @param SessionService $sessionService
     * @param LoggerInterface $logger
     * @param LogService $logService
     * @param callable $translator
     */
    public function __construct(
        PlatesEngine $view,
        SessionService $sessionService,
        LoggerInterface $logger,
        LogService $logService,
        callable $translator
    ) {
        $this->view = $view;
        $this->sessionService = $sessionService;
        $this->logger = $logger;
        $this->logService = $logService;
        $this->translator = $translator;
    }

    /**
     * Muestra la lista de logs de cambios de activos.
     * @param Request $request La solicitud HTTP.
     * @param Response $response La respuesta HTTP.
     * @return Response La respuesta HTTP con la vista renderizada.
     */
    public function listLogs(Request $request, Response $response): Response
    {
        $this->logger->debug('Acceso a la página de listado de logs de auditoría.');

        $t = $this->translator;

        // Obtiene todos los logs de activos desde el servicio de logs.
        $logs = $this->logService->getAllAssetLogs();
        if ($logs === false) {
            $this->sessionService->addFlashMessage('danger', $t('error_loading_logs') ?? 'Error al cargar los logs de auditoría.');
            $this->logger->error("Error al obtener todos los logs de activos.");
            $logs = [];
        }

        $flashMessages = $this->sessionService->getFlashMessages();

        // Renderiza la vista del listado de logs.
        $html = $this->view->render('admin/logs/list', [
            'pageTitle' => $t('audit_log_title') ?? 'Log de Auditoría',
            'logs' => $logs,
            'flashMessages' => $flashMessages
        ]);

        $response->getBody()->write($html);
        return $response;
    }
}
