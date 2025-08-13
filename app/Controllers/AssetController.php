<?php
// app/Controllers/AssetController.php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use League\Plates\Engine as PlatesEngine;
use App\Services\SessionService;
use Psr\Log\LoggerInterface;
use App\Models\Asset;

// Importar todos los modelos de maestros para cargar datos en el formulario
use App\Models\AssetType;
use App\Models\Manufacturer;
use App\Models\Model as AssetModel; // Usar alias para evitar conflicto con el tipo Model (PSR-7)
use App\Models\AssetStatus;
use App\Models\Location;
use App\Models\Department;
use App\Models\AcquisitionFormat;
use App\Models\Provider;

// Importar servicios y modelos específicos
use App\Services\LogService;
use App\Models\FileAttachment;
use App\Models\Contract;
use App\Models\AssetContract;
use App\Models\CustomFieldDefinition;
use App\Models\CustomFieldValue;

use Exception; // Para manejar errores generales
use PDOException; // Asegúrate de que esta línea esté presente
use App\Middlewares\AuthMiddleware;

class AssetController
{
    private PlatesEngine $view;
    private SessionService $sessionService;
    private LoggerInterface $logger;
    private Asset $assetModel;
    private array $config;
    private $translator; // <-- Propiedad para la función de traducción

    // Modelos de maestros (ya definidos)
    private AssetType $assettypeModel;
    private Manufacturer $manufacturerModel;
    private AssetModel $assetModelModel;
    private AssetStatus $assetstatusModel;
    private Location $locationModel;
    private Department $departmentModel;
    private AcquisitionFormat $acquisitionformatModel;
    private Provider $providerModel;

    // Servicios y modelos específicos
    private LogService $logService;
    private FileAttachment $fileAttachmentModel;

    // Modelos de contratos
    private Contract $contractModel;
    private AssetContract $assetContractModel;

    // Modelos de campos personalizados
    private CustomFieldDefinition $customFieldDefinitionModel;
    private CustomFieldValue $customFieldValueModel;

    public function __construct(
        PlatesEngine $view,
        SessionService $sessionService,
        LoggerInterface $logger,
        Asset $assetModel,
        Assettype $assettypeModel,
        Manufacturer $manufacturerModel,
        AssetModel $assetModelModel,
        Assetstatus $assetstatusModel,
        Location $locationModel,
        Department $departmentModel,
        AcquisitionFormat $acquisitionformatModel,
        Provider $providerModel,
        array $config,
        LogService $logService,
        FileAttachment $fileAttachmentModel,
        Contract $contractModel,
        AssetContract $assetContractModel,
        CustomFieldDefinition $customFieldDefinitionModel,
        CustomFieldValue $customFieldValueModel,
        callable $translator
    ) {
        $this->view = $view;
        $this->sessionService = $sessionService;
        $this->logger = $logger;
        $this->assetModel = $assetModel;
        $this->config = $config;
        $this->assettypeModel = $assettypeModel;
        $this->manufacturerModel = $manufacturerModel;
        $this->assetModelModel = $assetModelModel;
        $this->assetstatusModel = $assetstatusModel;
        $this->locationModel = $locationModel;
        $this->departmentModel = $departmentModel;
        $this->acquisitionformatModel = $acquisitionformatModel;
        $this->providerModel = $providerModel;
        $this->logService = $logService;
        $this->fileAttachmentModel = $fileAttachmentModel;
        $this->contractModel = $contractModel;
        $this->assetContractModel = $assetContractModel;
        $this->customFieldDefinitionModel = $customFieldDefinitionModel;
        $this->customFieldValueModel = $customFieldValueModel;
        $this->translator = $translator;
    }

    /**
     * Muestra la lista de todos los activos.
     */
    public function listAssets(Request $request, Response $response): Response
    {
        $assets = $this->assetModel->getAll();
        $t = $this->translator;

        if ($assets === false) {
            $this->sessionService->addFlashMessage('danger', $t('error_loading_assets'));
            $this->logger->error("Error al obtener todos los activos.");
            $assets = [];
        }

        $flashMessages = $this->sessionService->getFlashMessages();

        $html = $this->view->render('assets/list', [
            'pageTitle' => $t('asset_inventory'),
            'assets' => $assets,
            'flashMessages' => $flashMessages
        ]);

        $response->getBody()->write($html);
        return $response;
    }

    /**
     * Muestra el formulario para crear o editar un activo.
     */
    public function showAssetForm(Request $request, Response $response, array $args): Response
    {
        $assetId = $args['id'] ?? null;
        $asset = null;
        $attachments = [];
        $associatedContracts = [];
        $customFieldDefinitions = [];
        $customFieldValues = [];
        $t = $this->translator;

        if ($assetId) {
            $asset = $this->assetModel->getById((int)$assetId);
            if (!$asset) {
                $this->sessionService->addFlashMessage('danger', $t('asset_not_found'));
                return $response->withHeader('Location', '/assets')->withStatus(302);
            }
            $attachments = $this->fileAttachmentModel->getByAssetId((int)$assetId) ?: [];
            $associatedContracts = $this->assetContractModel->getContractsByAssetId((int)$assetId) ?: [];
            $customFieldValues = $this->customFieldValueModel->getByAssetId((int)$assetId) ?: [];
        }

        // --- TEMPORAL: Depuración ---
        error_log("DEBUG: showAssetForm - customFieldValues ANTES de render: " . json_encode($customFieldValues));
        // --- FIN TEMPORAL ---


        // Cargar todos los datos de los maestros para los SELECTs del formulario
        $assettypes = $this->assettypeModel->getAll() ?: [];
        $manufacturers = $this->manufacturerModel->getAll() ?: [];
        $assetModels = $this->assetModelModel->getAll() ?: [];
        $assetstatuses = $this->assetstatusModel->getAll() ?: [];
        $locations = $this->locationModel->getAll() ?: [];
        $departments = $this->departmentModel->getAll() ?: [];
        $acquisitionformats = $this->acquisitionformatModel->getAll() ?: [];
        $providers = $this->providerModel->getAll() ?: [];
        $allContracts = $this->contractModel->getAll() ?: [];

        // Si se está editando o si el tipo de activo ya está seleccionado, cargar sus definiciones
        if ($asset && $asset['id_tipo_activo']) {
            $customFieldDefinitions = $this->customFieldDefinitionModel->getAll((int)$asset['id_tipo_activo']) ?: [];
        }

        $flashMessages = $this->sessionService->getFlashMessages();

        $html = $this->view->render('assets/form', [
            'pageTitle' => ($asset ? $t('edit_asset') : $t('new_asset')),
            'asset' => $asset,
            'assettypes' => $assettypes,
            'manufacturers' => $manufacturers,
            'assetModels' => $assetModels,
            'assetstatuses' => $assetstatuses,
            'locations' => $locations,
            'departments' => $departments,
            'acquisitionformats' => $acquisitionformats,
            'providers' => $providers,
            'attachments' => $attachments,
            'allContracts' => $allContracts,
            'associatedContracts' => $associatedContracts,
            'customFieldDefinitions' => $customFieldDefinitions,
            'customFieldValues' => $customFieldValues,
            'flashMessages' => $flashMessages
        ]);

        $response->getBody()->write($html);
        return $response;
    }

    /**
     * Procesa la creación o actualización de un activo.
     */
    public function processAssetForm(Request $request, Response $response, array $args): Response
    {
        $assetId = $args['id'] ?? null;
        $data = $request->getParsedBody();
        $files = $request->getUploadedFiles();
        $t = $this->translator;

        $oldAssetData = null;
        if ($assetId) {
            $oldAssetData = $this->assetModel->getById((int)$assetId);
            if (!$oldAssetData) {
                $this->sessionService->addFlashMessage('danger', $t('error_asset_to_edit_not_found'));
                return $response->withHeader('Location', '/assets')->withStatus(302);
            }
        }

        // Preparar los datos para el modelo (limpiar y validar)
        $assetData = [
            'nombre'                       => trim($data['nombre'] ?? ''),
            'numero_serie'                 => trim($data['numero_serie'] ?? null),
            'id_tipo_activo'               => (int)($data['id_tipo_activo'] ?? 0),
            'id_fabricante'                => (int)($data['id_fabricante'] ?? 0) ?: null,
            'id_modelo'                    => (int)($data['id_modelo'] ?? 0) ?: null,
            'id_estado'                    => (int)($data['id_estado'] ?? 0),
            'id_ubicacion'                 => (int)($data['id_ubicacion'] ?? 0) ?: null,
            'id_departamento'              => (int)($data['id_departamento'] ?? 0) ?: null,
            'id_formato_adquisicion'       => (int)($data['id_formato_adquisicion'] ?? 0) ?: null,
            'id_proveedor_adquisicion'     => (int)($data['id_proveedor_adquisicion'] ?? 0) ?: null,
            'fecha_compra'                 => !empty($data['fecha_compra']) ? $data['fecha_compra'] : null,
            'precio_compra'                => !empty($data['precio_compra']) ? (float)$data['precio_compra'] : null,
            'fecha_fin_garantia'           => !empty($data['fecha_fin_garantia']) ? $data['fecha_fin_garantia'] : null,
            'fecha_fin_mantenimiento'      => !empty($data['fecha_fin_mantenimiento']) ? $data['fecha_fin_mantenimiento'] : null,
            'fecha_fin_vida'               => !empty($data['fecha_fin_vida']) ? $data['fecha_fin_vida'] : null,
            'fecha_fin_soporte_mainstream' => !empty($data['fecha_fin_soporte_mainstream']) ? $data['fecha_fin_soporte_mainstream'] : null,
            'fecha_fin_soporte_extended'   => !empty($data['fecha_fin_soporte_extended']) ? $data['fecha_fin_soporte_extended'] : null,
            'fecha_venta'                  => !empty($data['fecha_venta']) ? $data['fecha_venta'] : null,
            'valor_residual'               => !empty($data['valor_residual']) ? (float)$data['valor_residual'] : null,
            'descripcion'                  => trim($data['descripcion'] ?? null),
            'imagen_ruta'                  => trim($data['current_image_path'] ?? null),
        ];

        // --- Validación Básica del Activo Principal ---
        if (empty($assetData['nombre']) || empty($assetData['id_tipo_activo']) || empty($assetData['id_estado'])) {
            $this->sessionService->addFlashMessage('danger', $t('name_type_status_required'));
            $redirectUrl = $assetId ? "/assets/edit/{$assetId}" : "/assets/create";
            return $response->withHeader('Location', $redirectUrl)->withStatus(302);
        }

        // --- Manejo de Subida de Imagen Principal del Activo ---
        $imageFile = $files['imagen_ruta'] ?? null;
        if ($imageFile && $imageFile->getError() === UPLOAD_ERR_OK) {
            $uploadDir = $this->config['paths']['uploads'];
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0775, true);
            }
            
            $filename = uniqid('asset_') . '.' . pathinfo($imageFile->getClientFilename(), PATHINFO_EXTENSION);
            $newImagePath = rtrim($uploadDir, '/') . '/' . $filename;
            
            try {
                $imageFile->moveTo($newImagePath);
                $assetData['imagen_ruta'] = '/uploads/' . $filename;
                
                if ($assetId && $oldAssetData && !empty($oldAssetData['imagen_ruta'])) {
                    $oldFullImagePath = $this->config['paths']['uploads'] . ltrim($oldAssetData['imagen_ruta'], '/uploads');
                    if (file_exists($oldFullImagePath)) {
                        unlink($oldFullImagePath);
                        $this->logger->info($t('asset_old_image_deleted', ['%id%' => $oldAssetData['id'], '%path%' => $oldFullImagePath]));
                    }
                }
            } catch (Exception $e) {
                $this->sessionService->addFlashMessage('danger', $t('error_uploading_asset_image', ['%message%' => $e->getMessage()]));
                $this->logger->error("Error al subir imagen de activo: " . $e->getMessage());
                $redirectUrl = $assetId ? "/assets/edit/{$assetId}" : "/assets/create";
                return $response->withHeader('Location', $redirectUrl)->withStatus(302);
            }
        } elseif ($assetId && (isset($data['remove_image']) && $data['remove_image'] == '1')) {
            if ($oldAssetData && !empty($oldAssetData['imagen_ruta'])) {
                $oldFullImagePath = $this->config['paths']['uploads'] . ltrim($oldAssetData['imagen_ruta'], '/uploads');
                if (file_exists($oldFullImagePath)) {
                    unlink($oldFullImagePath);
                    $this->logger->info($t('asset_image_deleted_by_user', ['%id%' => $oldAssetData['id'], '%path%' => $oldFullImagePath]));
                }
            }
            $assetData['imagen_ruta'] = null;
        }

        $success = false;
        $assetIdAfterSave = null;
        $errorMessageFlash = $t('error_saving_asset');

        try {
            // Guardar o Actualizar el Activo Principal
            if ($assetId) {
                $success = $this->assetModel->update((int)$assetId, $assetData);
                $assetIdAfterSave = (int)$assetId;
            } else {
                $assetIdAfterSave = $this->assetModel->create($assetData);
                $success = ($assetIdAfterSave !== false);
            }
            // Si la operación principal de DB fue exitosa y no hubo excepción
            if ($success) {
                // ... (El resto del código dentro del if($success) en la versión final) ...
            } else {
                // Esto se ejecuta si create/update devuelve false sin lanzar excepción
                $this->sessionService->addFlashMessage('danger', $errorMessageFlash);
                $redirectUrl = $assetId ? "/assets/edit/{$assetId}" : "/assets/create";
                return $response->withHeader('Location', $redirectUrl)->withStatus(302);
            }
        } catch (\PDOException $e) {
            // Loguear el error original detallado
            $this->logger->error("PDOException en AssetController::processForm: " . $e->getMessage() . " Code: " . $e->getCode());
            
            // Determinar el mensaje de error traducido específico para el flash
            if ($e->getCode() == '23000' && str_contains($e->getMessage(), 'Duplicate entry')) {
                if (str_contains($e->getMessage(), 'numero_serie')) {
                    $errorMessageFlash = $t('serial_number_exists');
                } else {
                    $errorMessageFlash = $t('duplicate_data_error');
                }
            } else {
                $errorMessageFlash = $t('database_error_saving_asset', ['%message%' => $e->getMessage()]);
            }
            $success = false; // La operación falló.
            
            $this->sessionService->addFlashMessage('danger', $errorMessageFlash);
            $redirectUrl = $assetId ? "/assets/edit/{$assetId}" : "/assets/create";
            return $response->withHeader('Location', $redirectUrl)->withStatus(302);
        } catch (Exception $e) {
            // Loguear el error detallado
            $this->logger->error("Excepción general en AssetController::processForm: " . $e->getMessage() . " File: " . $e->getFile() . " Line: " . $e->getLine());

            $errorMessageFlash = $t('unexpected_error_saving_asset');
            $success = false;
            $this->sessionService->addFlashMessage('danger', $errorMessageFlash);
            $redirectUrl = $assetId ? "/assets/edit/{$assetId}" : "/assets/create";
            return $response->withHeader('Location', $redirectUrl)->withStatus(302);
        }
        
        // --- Si el guardado principal del activo fue exitoso, procedemos con las operaciones relacionadas ---
        // ESTA SECCIÓN DEBE ESTAR DENTRO DEL PRIMER IF ($SUCCESS)
        if ($success) { // <-- ¡REEMPLAZA ESTE BLOQUE POR EL CÓDIGO QUE SIGUE!
            // --- Log de CREACION/MODIFICACION del activo principal ---
            if ($this->logService) {
                $this->logService->logChange(
                    $assetIdAfterSave,
                    $this->sessionService->get('user_id'),
                    $assetId ? 'MODIFICACION' : 'CREACION',
                    $oldAssetData,
                    $assetData
                );
            }

            // --- Lógica para eliminar campos personalizados si el tipo de activo cambia (solo en EDICIÓN) ---
            if ($assetId && $oldAssetData && ($assetData['id_tipo_activo'] !== $oldAssetData['id_tipo_activo'])) {
                if ($this->customFieldValueModel->deleteAllForAsset((int)$assetId)) {
                    $this->sessionService->addFlashMessage('info', $t('type_changed_custom_fields_deleted'));
                    $this->logger->info($t('log_custom_fields_deleted_type_change', ['%id%' => $assetId]));
                } else {
                    $this->sessionService->addFlashMessage('warning', $t('warning_custom_fields_not_deleted'));
                    $this->logger->error($t('log_error_deleting_custom_fields_type_change', ['%id%' => $assetId]));
                }
            }

            // --- Manejo de Valores de Campos Personalizados ---
            foreach ($data as $key => $value) {
                if (str_starts_with($key, 'custom_field_')) {
                    $definitionId = (int)str_replace('custom_field_', '', $key);

                    if ($definitionId > 0) {
                        $fieldDef = $this->customFieldDefinitionModel->getById($definitionId);
                        if (!$fieldDef) {
                            $this->logger->warning($t('custom_field_def_not_found', ['%id%' => $definitionId, '%asset_id%' => $assetIdAfterSave]));
                            continue;
                        }

                        $valueToProcess = $value ?? '';
                        $valueToSave = trim($valueToProcess);

                        if ($fieldDef['tipo_dato'] === 'booleano') {
                            $valueToSave = ($valueToSave === '1') ? '1' : '0';
                        }
                        
                        // === Validación de Campos Requeridos ===
                        if ($fieldDef['es_requerido'] == 1 && (empty($valueToSave) && $valueToSave !== '0')) {
                            $this->sessionService->addFlashMessage('danger', $t('custom_field_required', ['%field_name%' => $fieldDef['nombre_campo']]));
                            $success = false;
                            continue;
                        }

                        // === Lógica de Guardado/Actualización/Eliminación del valor en DB ===
                        if (!empty($valueToSave) || ($valueToSave === '0' && $fieldDef['tipo_dato'] === 'booleano')) {
                            $this->customFieldValueModel->createOrUpdate($assetIdAfterSave, $definitionId, $valueToSave);
                            $this->logger->info($t('custom_field_saved_log', ['%field_name%' => $fieldDef['nombre_campo'], '%id%' => $definitionId, '%asset_id%' => $assetIdAfterSave, '%value%' => $valueToSave]));
                        } else {
                            $this->customFieldValueModel->deleteAllForAssetAndDefinition($assetIdAfterSave, $definitionId);
                            $this->logger->info($t('custom_field_emptied_deleted_log', ['%field_name%' => $fieldDef['nombre_campo'], '%id%' => $definitionId, '%asset_id%' => $assetIdAfterSave]));
                        }
                    }
                }
            }

            if ($success === false) {
                 $redirectUrl = $assetId ? "/assets/edit/{$assetIdAfterSave}" : "/assets/create";
                 return $response->withHeader('Location', $redirectUrl)->withStatus(302);
            }


            // --- Manejo de Asociaciones de Contratos ---
            $selectedContractIds = is_array($data['associated_contracts'] ?? null) ? array_map('intval', $data['associated_contracts']) : [];
            
            $currentAssociatedContracts = $this->assetContractModel->getContractsByAssetId($assetIdAfterSave);
            $currentAssociatedContractIds = array_column($currentAssociatedContracts, 'id_contrato');

            $contractsToDisassociate = array_diff($currentAssociatedContractIds, $selectedContractIds);
            foreach ($contractsToDisassociate as $contractId) {
                if ($this->assetContractModel->disassociate($assetIdAfterSave, $contractId)) {
                    $this->logger->info($t('contract_disassociated_log', ['%contract_id%' => $contractId, '%asset_id%' => $assetIdAfterSave]));
                } else {
                    $this->logger->error($t('error_contract_disassociated_log', ['%contract_id%' => $contractId, '%asset_id%' => $assetIdAfterSave]));
                }
            }

            $contractsToAssociate = array_diff($selectedContractIds, $currentAssociatedContractIds);
            foreach ($contractsToAssociate as $contractId) {
                if ($this->assetContractModel->associate($assetIdAfterSave, $contractId)) {
                    $this->logger->info($t('contract_associated_log', ['%contract_id%' => $contractId, '%asset_id%' => $assetIdAfterSave]));
                } else {
                    $this->logger->error($t('error_contract_associated_log', ['%contract_id%' => $contractId, '%asset_id%' => $assetIdAfterSave]));
                }
            }

            // Redirección de éxito final
            $this->sessionService->addFlashMessage('success', $t('asset_saved_successfully'));
            return $response->withHeader('Location', '/assets')->withStatus(302);

        } else { // Si el guardado principal del activo (Asset) falló
            $this->sessionService->addFlashMessage('danger', $errorMessage);
            $redirectUrl = $assetId ? "/assets/edit/{$assetId}" : "/assets/create";
            return $response->withHeader('Location', $redirectUrl)->withStatus(302);
        }
    }


    /**
     * Procesa la eliminación de un activo.
     */
    public function processDelete(Request $request, Response $response, array $args): Response
    {
        $assetId = (int)$args['id'];
        $t = $this->translator;

        $asset = $this->assetModel->getById($assetId);
        if (!$asset) {
            $this->sessionService->addFlashMessage('danger', $t('asset_not_found_for_deletion') ?? 'Activo no encontrado para eliminar.');
            return $response->withHeader('Location', '/assets')->withStatus(302);
        }

        // Eliminar imagen principal asociada si existe
        if ($asset['imagen_ruta']) {
            $imagePath = $this->config['paths']['uploads'] . ltrim($asset['imagen_ruta'], '/uploads');
            if (file_exists($imagePath)) {
                try {
                    unlink($imagePath);
                    $this->logger->info($t('asset_image_deleted_log', ['%path%' => $imagePath]));
                } catch (Exception $e) {
                    $this->logger->error($t('error_deleting_asset_image_log', ['%asset_id%' => $assetId, '%message%' => $e->getMessage()]));
                }
            }
        }

        // --- Eliminar todos los archivos adjuntos del activo ---
        $attachmentsToDelete = $this->fileAttachmentModel->getByAssetId($assetId);
        if ($attachmentsToDelete) {
            foreach ($attachmentsToDelete as $attachment) {
                $fullPath = $this->config['paths']['uploads'] . ltrim($attachment['ruta_almacenamiento'], '/uploads');
                if (file_exists($fullPath)) {
                    try {
                        unlink($fullPath);
                        $this->logger->info($t('attachment_deleted_mass_log', ['%path%' => $fullPath]));
                    } catch (Exception $e) {
                        $this->logger->error($t('error_deleting_attachment_physical_log', ['%path%' => $fullPath, '%message%' => $e->getMessage()]));
                    }
                }
            }
        }
        // Llamar a deleteAllForAsset para limpiar registros de la DB
        $this->fileAttachmentModel->deleteAllForAsset($assetId);
        $this->logger->info($t('attachment_records_deleted_log', ['%asset_id%' => $assetId]));

        // --- Eliminar todos los valores de campos personalizados del activo ---
        if ($this->customFieldValueModel->deleteAllForAsset($assetId)) {
            $this->logger->info($t('custom_field_values_deleted_log', ['%asset_id%' => $assetId]));
        } else {
            $this->logger->error($t('error_deleting_custom_field_values_log', ['%asset_id%' => $assetId]));
        }


        // --- Eliminar todas las asociaciones de contratos del activo ---
        if ($this->assetContractModel->deleteAllForAsset($assetId)) {
            $this->logger->info($t('contract_associations_deleted_log', ['%asset_id%' => $assetId]));
        } else {
            $this->logger->error($t('error_contract_associations_deleted_log', ['%asset_id%' => $assetId]));
        }


        if ($this->assetModel->delete($assetId)) {
            $this->sessionService->addFlashMessage('success', $t('asset_deleted_successfully'));
            // Log de ELIMINACION
            if ($this->logService) {
                $this->logService->logChange(
                    $assetId,
                    $this->sessionService->get('user_id'),
                    'ELIMINACION',
                    $asset,
                    null
                );
            }
        } else {
            $this->sessionService->addFlashMessage('danger', $t('error_deleting_asset_general'));
        }
        return $response->withHeader('Location', '/assets')->withStatus(302);
    }
}
