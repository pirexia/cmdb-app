<?php
/**
 * app/Controllers/ImportController.php
 *
 * Este controlador gestiona la lógica de importación masiva de datos a través de archivos CSV.
 * Maneja la descarga de plantillas, la subida de archivos y el procesamiento de los datos.
 */

namespace App\Controllers;

// --- Importaciones de Clases ---
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use League\Plates\Engine as PlatesEngine;
use App\Services\SessionService;
use Psr\Log\LoggerInterface;
use App\Services\CsvTemplateService;  // Servicio para generar plantillas CSV
use App\Services\CsvImporterService; // Servicio para procesar la importación CSV
use App\Models\AssetType;             // Modelo para obtener tipos de activo
use App\Models\Manufacturer;          // Modelo para obtener fabricantes
use App\Models\Model as ModelAsset;   // Modelo para crear nuevos modelos de activos
use App\Models\CustomFieldDefinition; // Modelo para obtener definiciones de campos personalizados
use Exception;                        // Para manejar excepciones generales
use PDOException;                     // Para capturar errores específicos de la base de datos
use League\Csv\Writer;                // Para generar archivos de log CSV

/**
 * Clase ImportController
 * Gestiona el flujo completo del módulo de importación masiva: desde la interfaz
 * para descargar plantillas hasta el procesamiento de los archivos subidos.
 */
class ImportController
{
    private PlatesEngine $view;
    private SessionService $sessionService;
    private LoggerInterface $logger;
    private CsvTemplateService $csvTemplateService;
    private CsvImporterService $csvImporterService;
    private AssetType $assetTypeModel;
    private Manufacturer $manufacturerModel;
    private ModelAsset $modelAssetModel;
    private $translator;

    /**
     * Constructor del controlador. Inyecta todas las dependencias necesarias
     * para la gestión de vistas, sesiones, logs y servicios de importación.
     */
    public function __construct(
        PlatesEngine $view,
        SessionService $sessionService,
        LoggerInterface $logger,
        CsvTemplateService $csvTemplateService,
        callable $translator,
        AssetType $assetTypeModel,
        CsvImporterService $csvImporterService,
        Manufacturer $manufacturerModel,
        ModelAsset $modelAssetModel
    ) {
        $this->view = $view;
        $this->sessionService = $sessionService;
        $this->logger = $logger;
        $this->csvTemplateService = $csvTemplateService;
        $this->translator = $translator;
        $this->assetTypeModel = $assetTypeModel;
        $this->csvImporterService = $csvImporterService;
        $this->manufacturerModel = $manufacturerModel;
        $this->modelAssetModel = $modelAssetModel;
    }

    /**
     * Muestra la página principal del módulo de importación, con opciones para
     * descargar plantillas y subir archivos CSV.
     * @param Request $request La solicitud HTTP.
     * @param Response $response La respuesta HTTP.
     * @return Response La respuesta HTTP con la vista renderizada.
     */
    public function showImportOptions(Request $request, Response $response): Response
    {
        $t = $this->translator;
        $availableTemplates = $this->csvTemplateService->getAvailableTemplates();
        
        $assetTypes = $this->assetTypeModel->getAll() ?: [];
        if ($assetTypes === false) {
            $this->logger->error($t('error_loading_asset_types_for_import_templates'));
            $assetTypes = [];
        }

        $flashMessages = $this->sessionService->getFlashMessages();

        $html = $this->view->render('admin/import/index', [
            'pageTitle' => $t('bulk_import_module'),
            'availableTemplates' => $availableTemplates,
            'assetTypes' => $assetTypes,
            'flashMessages' => $flashMessages
        ]);

        $response->getBody()->write($html);
        return $response;
    }

    /**
     * Maneja la descarga de una plantilla CSV específica.
     * @param Request $request La solicitud HTTP.
     * @param Response $response La respuesta HTTP.
     * @param array $args Argumentos de la ruta, incluyendo 'entity_type' y 'asset_type_id'.
     * @return Response La respuesta HTTP con el contenido del CSV para descarga.
     */
    public function downloadTemplate(Request $request, Response $response, array $args): Response
    {
        $entityType = $args['entity_type'];
        $assetTypeId = $args['asset_type_id'] ?? null;
        $t = $this->translator;

        try {
            $csvContent = $this->csvTemplateService->generateTemplate(
                $entityType,
                ($entityType === 'assets' ? (int)$assetTypeId : null)
            );
            
            $filename = $t('csv_template_filename', ['%entity_type%' => $t($entityType)]);
            if ($entityType === 'assets' && $assetTypeId !== null) {
                $selectedAssetType = $this->assetTypeModel->getById((int)$assetTypeId);
                if ($selectedAssetType) {
                    $filename = $t('csv_template_filename_asset_type', ['%asset_type_name%' => $selectedAssetType['nombre']]);
                }
            }

            $response = $response->withHeader('Content-Type', 'text/csv')
                                 ->withHeader('Content-Disposition', "attachment; filename=\"{$filename}\"")
                                 ->withHeader('Pragma', 'no-cache')
                                 ->withHeader('Expires', '0');
            
            $response->getBody()->write($csvContent);
            return $response;

        } catch (Exception $e) {
            $this->logger->error($t('error_generating_csv_template', ['%entity_type%' => $entityType, '%message%' => $e->getMessage()]));
            $this->sessionService->addFlashMessage('danger', $t('error_generating_csv_template_flash', ['%entity_type%' => $t($entityType)]));
            return $response->withHeader('Location', '/admin/import')->withStatus(302);
        }
    }

    /**
     * Muestra el formulario para subir un archivo CSV para una entidad específica.
     * @param Request $request La solicitud HTTP.
     * @param Response $response La respuesta HTTP.
     * @param array $args Argumentos de la ruta, incluyendo 'entity_type'.
     * @return Response La respuesta HTTP con la vista del formulario de subida.
     */
    public function showUploadForm(Request $request, Response $response, array $args): Response
    {
        $entityType = $args['entity_type'];
        $t = $this->translator;

        if (!in_array($entityType, $this->csvTemplateService->getAvailableTemplates())) {
            $this->sessionService->addFlashMessage('danger', $t('invalid_import_entity_type', ['%entity_type%' => $entityType]));
            return $response->withHeader('Location', '/admin/import')->withStatus(302);
        }

        $flashMessages = $this->sessionService->getFlashMessages();
        
        $assetTypes = [];
        if ($entityType === 'assets') {
             $assetTypes = $this->assetTypeModel->getAll() ?: [];
        }

        $html = $this->view->render('admin/import/upload', [
            'pageTitle' => $t('upload_csv_for_entity', ['%entity_type%' => $t($entityType)]),
            'entityType' => $entityType,
            'assetTypes' => $assetTypes,
            'flashMessages' => $flashMessages
        ]);

        $response->getBody()->write($html);
        return $response;
    }

    /**
     * Procesa la subida del archivo CSV y realiza la importación de datos.
     * @param Request $request La solicitud HTTP (incluye el archivo subido).
     * @param Response $response La respuesta HTTP.
     * @param array $args Argumentos de la ruta, incluyendo 'entity_type'.
     * @return Response La respuesta HTTP con una redirección.
     */
    public function processUpload(Request $request, Response $response, array $args): Response
    {
        $entityType = $args['entity_type'];
        $t = $this->translator;
        $uploadedFiles = $request->getUploadedFiles();
        $csvFile = $uploadedFiles['csv_file'] ?? null;

        // Validaciones iniciales del archivo subido.
        if (!$csvFile || $csvFile->getError() !== UPLOAD_ERR_OK) {
            $this->sessionService->addFlashMessage('danger', $t('no_file_uploaded_or_error'));
            return $response->withHeader('Location', "/admin/import/{$entityType}/upload")->withStatus(302);
        }

        if ($csvFile->getClientMediaType() !== 'text/csv' && $csvFile->getClientMediaType() !== 'application/vnd.ms-excel') {
            $this->sessionService->addFlashMessage('danger', $t('invalid_file_type_csv_expected'));
            return $response->withHeader('Location', "/admin/import/{$entityType}/upload")->withStatus(302);
        }

        try {
            $uploadDir = sys_get_temp_dir();
            $tempFilePath = $uploadDir . DIRECTORY_SEPARATOR . uniqid('csv_import_') . '.csv';
            $csvFile->moveTo($tempFilePath);

            $data = $request->getParsedBody();
            $assetTypeId = (int)($data['asset_type_id'] ?? 0) ?: null;

            // --- FASE 1: ANÁLISIS ---
            $analysisResult = $this->csvImporterService->importCsv($tempFilePath, $entityType, $assetTypeId);

            // Si se requiere confirmación del usuario para crear modelos
            if (isset($analysisResult['status']) && $analysisResult['status'] === 'requires_confirmation') {
                $this->sessionService->set('import_confirmation_data', [
                    'temp_file_path' => $tempFilePath,
                    'entity_type' => $entityType,
                    'asset_type_id' => $assetTypeId,
                    'new_models' => $analysisResult['new_models'],
                ]);
                return $response->withHeader('Location', '/admin/import/confirm-models')->withStatus(302);
            }

            // Si no se requiere confirmación, el resultado es el resumen final.
            $importSummary = $analysisResult;

            // Eliminar el archivo temporal solo después de una importación completa y exitosa.
            // En el caso de confirmación, se mantiene para la segunda fase.
            if (file_exists($tempFilePath)) { unlink($tempFilePath); }

            // --- GENERACIÓN DEL LOG DE IMPORTACIÓN DESCARGABLE ---
            $logWriter = Writer::createFromString('');
            $logWriter->setDelimiter(';');
            $logWriter->setOutputBOM(Writer::BOM_UTF8);
            $logWriter->insertOne(['row', 'status', 'message', 'data']);

            foreach ($importSummary['results'] as $result) {
                $rowDataString = json_encode($result['data'], JSON_UNESCAPED_UNICODE);
                $logWriter->insertOne([$result['row'], $result['status'], $result['message'], $rowDataString]);
            }
            $logContent = $logWriter->toString();

            $tempLogPath = $uploadDir . DIRECTORY_SEPARATOR . uniqid('import_log_') . '.csv';
            file_put_contents($tempLogPath, $logContent);

            $this->sessionService->set('import_summary', $importSummary);
            $this->sessionService->set('import_log_path', $tempLogPath);

        } catch (Exception $e) {
            $this->logger->error($t('import_unexpected_error', ['%entity_type%' => $entityType, '%message%' => $e->getMessage()]));
            $this->sessionService->addFlashMessage('danger', $t('import_unexpected_error_flash', ['%message%' => $e->getMessage()]));
            return $response->withHeader('Location', '/admin/import')->withStatus(302);
        }

        return $response->withHeader('Location', '/admin/import/results')->withStatus(302);
    }

    /**
     * Muestra la página para confirmar la creación de nuevos modelos.
     */
    public function showConfirmModels(Request $request, Response $response): Response
    {
        $t = $this->translator;
        $confirmationData = $this->sessionService->get('import_confirmation_data');

        if (!$confirmationData || empty($confirmationData['new_models'])) {
            $this->sessionService->addFlashMessage('warning', $t('import_no_confirmation_needed'));
            return $response->withHeader('Location', '/admin/import')->withStatus(302);
        }

        $html = $this->view->render('admin/import/confirm_models', [
            'pageTitle' => $t('import_confirm_new_models_title'),
            'newModels' => $confirmationData['new_models'],
            'entityType' => $confirmationData['entity_type'],
            'flashMessages' => $this->sessionService->getFlashMessages(),
        ]);

        $response->getBody()->write($html);
        return $response;
    }

    /**
     * Procesa la importación final después de que el usuario ha confirmado la creación de nuevos modelos.
     */
    public function processConfirmedImport(Request $request, Response $response): Response
    {
        $t = $this->translator;
        $confirmationData = $this->sessionService->get('import_confirmation_data');

        if (!$confirmationData) {
            $this->sessionService->addFlashMessage('danger', $t('import_session_data_lost'));
            return $response->withHeader('Location', '/admin/import')->withStatus(302);
        }

        // --- FASE 2: CREACIÓN DE NUEVOS MODELOS ---
        try {
            foreach ($confirmationData['new_models'] as $modelInfo) {
                $manufacturer = $this->manufacturerModel->getByName($modelInfo['manufacturer_name']);
                if ($manufacturer) {
                    $this->modelAssetModel->create($manufacturer['id'], $modelInfo['model_name']);
                    $this->logger->info($t('import_model_created_on_confirm', ['%model_name%' => $modelInfo['model_name'], '%manufacturer_name%' => $modelInfo['manufacturer_name']]));
                }
            }
        } catch (Exception $e) {
            $this->logger->error($t('import_error_creating_models_on_confirm', ['%message%' => $e->getMessage()]));
            $this->sessionService->addFlashMessage('danger', $t('import_error_creating_models_on_confirm_flash', ['%message%' => $e->getMessage()]));
            if (file_exists($confirmationData['temp_file_path'])) { unlink($confirmationData['temp_file_path']); }
            $this->sessionService->remove('import_confirmation_data');
            return $response->withHeader('Location', '/admin/import')->withStatus(302);
        }

        // --- FASE 3: IMPORTACIÓN FINAL ---
        $importSummary = $this->csvImporterService->importCsv(
            $confirmationData['temp_file_path'],
            $confirmationData['entity_type'],
            $confirmationData['asset_type_id']
        );

        if (file_exists($confirmationData['temp_file_path'])) { unlink($confirmationData['temp_file_path']); }
        $this->sessionService->remove('import_confirmation_data');

        // Generar log y mostrar resultados (código duplicado de processUpload, se podría refactorizar)
        $logWriter = Writer::createFromString('');
        $logWriter->setDelimiter(';');
        $logWriter->setOutputBOM(Writer::BOM_UTF8);
        $logWriter->insertOne(['row', 'status', 'message', 'data']);
        foreach ($importSummary['results'] as $result) {
            $logWriter->insertOne([$result['row'], $result['status'], $result['message'], json_encode($result['data'], JSON_UNESCAPED_UNICODE)]);
        }
        $tempLogPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('import_log_') . '.csv';
        file_put_contents($tempLogPath, $logWriter->toString());

        $this->sessionService->set('import_summary', $importSummary);
        $this->sessionService->set('import_log_path', $tempLogPath);

        return $response->withHeader('Location', '/admin/import/results')->withStatus(302);
    }

    /**
     * Muestra la página de resultados de la importación en un modal.
     */
    public function showImportResults(Request $request, Response $response): Response
    {
        $t = $this->translator;
        $importSummary = $this->sessionService->get('import_summary');
        $logPath = $this->sessionService->get('import_log_path');

        if (!$importSummary) {
            $this->sessionService->addFlashMessage('info', $t('no_import_results_found'));
            return $response->withHeader('Location', '/admin/import')->withStatus(302);
        }

        $flashMessages = $this->sessionService->getFlashMessages();
        
        $html = $this->view->render('admin/import/results', [
            'pageTitle' => $t('import_results'),
            'importSummary' => $importSummary,
            'logPath' => $logPath,
            'flashMessages' => $flashMessages
        ]);

        $response->getBody()->write($html);
        return $response;
    }

    /**
     * Permite la descarga del archivo de log de importación.
     */
    public function downloadImportLog(Request $request, Response $response): Response
    {
        $logPath = $this->sessionService->get('import_log_path');
        $t = $this->translator;

        if (!$logPath || !file_exists($logPath)) {
            $this->sessionService->addFlashMessage('danger', $t('import_log_file_not_found'));
            return $response->withHeader('Location', '/admin/import')->withStatus(302);
        }

        $filename = 'import_log_' . date('Ymd_His') . '.csv';

        $csvContent = file_get_contents($logPath);

        // Eliminar el archivo de log temporalmente
        unlink($logPath);
        $this->sessionService->remove('import_log_path');
        $this->sessionService->remove('import_summary'); // Limpiar el resumen de la sesión

        // Enviar el archivo como descarga
        $response->getBody()->write($csvContent);
        return $response->withHeader('Content-Type', 'text/csv')
                        ->withHeader('Content-Disposition', "attachment; filename=\"{$filename}\"")
                        ->withHeader('Pragma', 'no-cache')
                        ->withHeader('Expires', '0');
    }
}
