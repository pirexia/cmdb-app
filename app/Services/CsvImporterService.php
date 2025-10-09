<?php
// app/Services/CsvImporterService.php

namespace App\Services;

use Psr\Log\LoggerInterface;
use League\Csv\Reader; // Librería para leer archivos CSV
use Exception;        // Para manejo de excepciones generales
use PDOException;     // Para capturar errores de base de datos

// Importar todos los modelos que pueden ser objetivo de importación
use App\Models\Asset;
use App\Models\Manufacturer;
use App\Models\Provider;
use App\Models\Contract;
use App\Models\AssetType;
use App\Models\AssetStatus;
use App\Models\ContractType;
use App\Models\Location;
use App\Models\Department;
use App\Models\AcquisitionFormat;
use App\Models\Model; // Para modelos de activos (ej. EliteBook G8)
use App\Models\CustomFieldDefinition; // Modelo para definiciones de campos personalizados
use App\Models\CustomFieldValue; // Modelo para valores de campos personalizados
use App\Models\Sequence; // NUEVO: Para secuencias
use App\Services\LogService; // Para auditoría
use App\Services\SessionService; // Para obtener el ID de usuario


/**
 * Clase CsvImporterService
 * Este servicio es responsable de leer, validar y procesar archivos CSV
 * para la importación masiva de datos en la base de datos.
 */
class CsvImporterService
{
    private LoggerInterface $logger;
    private $translator;

    private Asset $assetModel;
    private Manufacturer $manufacturerModel;
    private Provider $providerModel;
    private Contract $contractModel;
    private AssetType $assetTypeModel;
    private AssetStatus $assetStatusModel;
    private ContractType $contractTypeModel;
    private Location $locationModel;
    private Department $departmentModel;
    private AcquisitionFormat $acquisitionFormatModel;
    private Model $modelModel;
    private CustomFieldValue $customFieldValueModel;
    private CustomFieldDefinition $customFieldDefinitionModel;
    private Sequence $sequenceModel; // NUEVO
    private LogService $logService;
    private SessionService $sessionService;


    public function __construct(
        LoggerInterface $logger,
        callable $translator,
        Asset $assetModel,
        Manufacturer $manufacturerModel,
        Provider $providerModel,
        Contract $contractModel,
        AssetType $assetTypeModel,
        AssetStatus $assetStatusModel,
        ContractType $contractTypeModel,
        Location $locationModel,
        Department $departmentModel,
        AcquisitionFormat $acquisitionFormatModel,
        Model $modelModel,
        CustomFieldValue $customFieldValueModel,
        CustomFieldDefinition $customFieldDefinitionModel,
        Sequence $sequenceModel,
        LogService $logService,
        SessionService $sessionService
    ) {
        $this->logger = $logger;
        $this->translator = $translator;
        $this->assetModel = $assetModel;
        $this->manufacturerModel = $manufacturerModel;
        $this->providerModel = $providerModel;
        $this->contractModel = $contractModel;
        $this->assetTypeModel = $assetTypeModel;
        $this->assetStatusModel = $assetStatusModel;
        $this->contractTypeModel = $contractTypeModel;
        $this->locationModel = $locationModel;
        $this->departmentModel = $departmentModel;
        $this->acquisitionFormatModel = $acquisitionFormatModel;
        $this->modelModel = $modelModel;
        $this->customFieldValueModel = $customFieldValueModel; // Asignar la dependencia
        $this->customFieldDefinitionModel = $customFieldDefinitionModel;
        $this->sequenceModel = $sequenceModel; // NUEVO
        $this->logService = $logService;
        $this->sessionService = $sessionService;
    }

    /**
     * Procesa un archivo CSV para importar datos a una entidad específica.
     * @param string $filePath La ruta temporal del archivo CSV subido.
     * @param string $entityType El tipo de entidad a importar (ej. 'assets', 'manufacturers').
     * @param int|null $assetTypeId Para importación de activos, el ID del tipo de activo si la plantilla es específica.
     * @return array Un array con el resumen de la importación y los resultados fila por fila.
     *               O un array indicando que se requiere confirmación para crear nuevos modelos.
     * @throws Exception Si el archivo no se puede leer o el tipo de entidad no es soportado.
     */
    public function importCsv(string $filePath, string $entityType, ?int $assetTypeId = null): array
    {
        $t = $this->translator;
        
        $reader = Reader::createFromPath($filePath, 'r');
        $reader->setDelimiter(';');
        $reader->setHeaderOffset(0);

        $totalRows = 0;
        $successfulRows = 0;
        $failedRows = 0;
        $results = []; // Almacena el resultado de cada fila
        $newModelsToCreate = []; // Almacena los nuevos modelos detectados

        // Obtener un nuevo iterador para la fase de análisis.
        $records = $reader->getRecords(); 

        foreach ($records as $offset => $record) {
            $rowData = $record;
            $rowNumber = $offset + 2; // Fila actual en el fichero (1-indexed, +1 por cabecera, +1 por 0-indexed)

            // --- FASE DE ANÁLISIS: Detectar nuevos modelos sin lanzar excepción ---
            if ($entityType === 'assets') {
                $newModelInfo = $this->analyzeAssetRowForNewModel($rowData, $rowNumber);
                if ($newModelInfo) {
                    // Evitar duplicados en la lista de modelos a crear
                    if (!in_array($newModelInfo, $newModelsToCreate)) {
                        $newModelsToCreate[] = $newModelInfo;
                    }
                }
            }
        }

        // Si se detectaron nuevos modelos, detener el proceso y pedir confirmación.
        if (!empty($newModelsToCreate)) {
            return [
                'status' => 'requires_confirmation',
                'new_models' => $newModelsToCreate,
            ];
        }

        // --- FASE DE IMPORTACIÓN: Si no hay nuevos modelos, proceder como antes ---
        // Obtener un nuevo iterador para la fase de importación para asegurar que empezamos desde el principio.
        $records = $reader->getRecords();
        foreach ($records as $offset => $record) {
            $rowData = $record;
            $rowNumber = $offset + 2;
            $totalRows++; // Incrementar el contador total de filas procesadas
             try {
                $result = $this->processRow($entityType, $rowData, $rowNumber, $assetTypeId);
                $results[] = $result; // Añadir el resultado de la fila al array
                if ($result['status'] === 'new' || $result['status'] === 'updated') {
                    $successfulRows++;
                } else {
                    $failedRows++;
                }
            } catch (Exception $e) {
                $failedRows++;
                $results[] = [
                    'status' => 'error',
                    'message' => $t('import_row_unexpected_error', ['%message%' => $e->getMessage()]),
                    'row' => $rowNumber,
                    'data' => $rowData // Guardar los datos de la fila con error
                ];
                $this->logger->error($t('import_row_processing_error_log', ['%row%' => $rowNumber, '%entity_type%' => $entityType, '%message%' => $e->getMessage()]));
            }
        }
        
        return [
            'total_rows' => $totalRows,
            'successful_rows' => $successfulRows,
            'failed_rows' => $failedRows,
            'results' => $results // Devolver los resultados detallados de cada fila
        ];
    }
    
    /**
     * Analiza una fila de activo para detectar si se necesita crear un nuevo modelo.
     * No lanza excepción si el modelo no existe, en su lugar, devuelve la información.
     * @return array|null ['manufacturer_name' => string, 'model_name' => string] o null si no hay nuevo modelo.
     */
    private function analyzeAssetRowForNewModel(array $rowData, int $rowNumber): ?array
    {
        $t = $this->translator;
        try {
            $manufacturerName = $rowData[$t('manufacturer_name')] ?? '';
            if (empty($manufacturerName)) {
                return null; // No se puede determinar el modelo sin fabricante
            }

            $manufacturer = $this->manufacturerModel->getByName($manufacturerName);
            if (!$manufacturer) {
                return null; // El fabricante debe existir, la validación principal lo detectará
            }

            $modelName = $rowData[$t('model_name')] ?? '';
            if (empty($modelName)) {
                return null; // No hay nombre de modelo para crear
            }

            $model = $this->modelModel->getByNameAndManufacturerId($modelName, $manufacturer['id']);
            if (!$model) {
                // ¡Modelo no encontrado! Devolver información para su creación.
                return ['manufacturer_name' => $manufacturerName, 'model_name' => $modelName];
            }
        } catch (Exception $e) {
            // Ignorar otras excepciones durante el análisis, se capturarán en la importación real.
        }
        return null;
    }

    /**
     * Procesa una única fila de datos del CSV y la inserta/actualiza en la base de datos.
     * @param string $entityType Tipo de entidad.
     * @param array $rowData Datos de la fila.
     * @param int $rowNumber Número de fila para mensajes de error.
     * @param int|null $assetTypeId Para activos, el ID del tipo de activo.
     * @return array ['status' => string, 'message' => string, 'row' => int, 'data' => array]
     */
    private function processRow(string $entityType, array $rowData, int $rowNumber, ?int $assetTypeId): array
    {
        $t = $this->translator;

        try {
            switch ($entityType) {
                case 'assets':
                    $status = $this->importAssetRow($rowData, $rowNumber, $assetTypeId);
                    break;
                case 'manufacturers':
                    $status = $this->importManufacturerRow($rowData, $rowNumber);
                    break;
                case 'providers':
                    $status = $this->importProviderRow($rowData, $rowNumber);
                    break;
                case 'contracts':
                    $status = $this->importContractRow($rowData, $rowNumber);
                    break;
                case 'asset_types':
                case 'asset_statuses':
                case 'contract_types':
                case 'locations':
                case 'departments':
                case 'acquisition_formats':
                    $status = $this->importGenericMasterRow($entityType, $rowData, $rowNumber);
                    break;
                default:
                    $this->logger->warning($t('import_unsupported_entity_type', ['%entity_type%' => $entityType]));
                    return [
                        'status' => 'error',
                        'message' => $t('import_unsupported_entity_type_flash'),
                        'row' => $rowNumber,
                        'data' => $rowData
                    ];
            }
            return [
                'status' => $status,
                'message' => $t('import_' . $status . '_success_row', ['%row%' => $rowNumber, '%entity_type%' => $t($entityType)]),
                'row' => $rowNumber,
                'data' => $rowData
            ];
        } catch (PDOException $e) {
            $this->logger->error($t('import_db_error_row', ['%row%' => $rowNumber, '%entity_type%' => $entityType, '%message%' => $e->getMessage(), '%code%' => $e->getCode()]));
            
            $message = '';
            if ($e->getCode() == '23000') {
                if (str_contains($e->getMessage(), 'uq_numero_serie_tipo_activo') || str_contains($e->getMessage(), "'numero_serie'")) {
                    $message = $t('import_serial_number_exists_row', ['%row%' => $rowNumber]);
                } elseif (str_contains($e->getMessage(), '1452')) {
                    $message = $t('import_fk_missing_parent_row', ['%row%' => $rowNumber, '%message%' => $e->getMessage()]);
                } elseif (str_contains($e->getMessage(), '1451')) {
                    $message = $t('import_fk_has_children_row', ['%row%' => $rowNumber, '%message%' => $e->getMessage()]);
                }
            }
            return [
                'status' => 'error',
                'message' => $message ?: $t('import_generic_db_error_row', ['%row%' => $rowNumber, '%message%' => $e->getMessage()]),
                'row' => $rowNumber,
                'data' => $rowData
            ];
        } catch (Exception $e) {
            $this->logger->error($t('import_logic_error_row', ['%row%' => $rowNumber, '%entity_type%' => $entityType, '%message%' => $e->getMessage()]));
            return [
                'status' => 'error',
                'message' => $t('import_validation_error_row', ['%row%' => $rowNumber, '%message%' => $e->getMessage()]),
                'row' => $rowNumber,
                'data' => $rowData
            ];
        }
    }

    /**
     * Lógica de importación para una fila de activo.
     * @param array $rowData Datos de la fila.
     * @param int $rowNumber Número de fila para mensajes de error.
     * @param int|null $assetTypeId ID del tipo de activo si la plantilla es específica.
     * @return string 'new' o 'updated'
     * @throws Exception
     */
    private function importAssetRow(array $rowData, int $rowNumber, ?int $assetTypeId): string
    {
        $t = $this->translator;
        $status = 'new';
        
        // Mapeo de cabeceras traducidas a nombres de columna de la DB para el modelo Asset
        $assetData = [
            'nombre'                       => $rowData[$t('asset_name')] ?? '',
            'numero_serie'                 => $rowData[$t('serial_number')] ?? null,
            'descripcion'                  => $rowData[$t('asset_description')] ?? null,
            'fecha_compra'                 => $this->formatDateForDb($rowData[$t('purchase_date')] ?? null),
            'precio_compra'                => $this->formatDecimalForDb($rowData[$t('purchase_price')] ?? null),
            'fecha_fin_garantia'           => $this->formatDateForDb($rowData[$t('warranty_end_date')] ?? null),
            'fecha_fin_mantenimiento'      => $this->formatDateForDb($rowData[$t('maintenance_end_date')] ?? null),
            'fecha_fin_vida'               => $this->formatDateForDb($rowData[$t('eol_date')] ?? null),
            'fecha_fin_soporte_mainstream' => $this->formatDateForDb($rowData[$t('mainstream_support_end_date')] ?? null),
            'fecha_fin_soporte_extended'   => $this->formatDateForDb($rowData[$t('extended_support_end_date')] ?? null),
            'fecha_venta'                  => $this->formatDateForDb($rowData[$t('sale_date')] ?? null),
            'valor_residual'               => $this->formatDecimalForDb($rowData[$t('residual_value')] ?? null),
        ];

        // --- Resolución de IDs a partir de Nombres (para FKs) ---
        $assetTypeName = $rowData[$t('asset_type_name')] ?? '';
        if (empty($assetTypeName)) {
            throw new Exception($t('import_asset_type_name_required_row', ['%row%' => $rowNumber]));
        }
        $assetType = $this->assetTypeModel->getByName(strtolower(trim($assetTypeName)));
        if (!$assetType) {
            throw new Exception($t('import_asset_type_not_found_row', ['%row%' => $rowNumber, '%name%' => $assetTypeName]));
        }
        $assetData['id_tipo_activo'] = $assetType['id'];
        
        // ... (Resolución de otros FKs para Manufacturer, Model, Status, Location, etc.)
        // Se asume que getByName() existe para los modelos correspondientes.
        $manufacturerName = $rowData[$t('manufacturer_name')] ?? '';
        if (!empty($manufacturerName)) {
            $manufacturer = $this->manufacturerModel->getByName(strtolower(trim($manufacturerName)));
            if (!$manufacturer) {
                throw new Exception($t('import_manufacturer_not_found_row', ['%row%' => $rowNumber, '%name%' => $manufacturerName]));
            }
            $assetData['id_fabricante'] = $manufacturer['id'];
            
            $modelName = $rowData[$t('model_name')] ?? '';
            if (!empty($modelName)) {
                $model = $this->modelModel->getByNameAndManufacturerId(strtolower(trim($modelName)), $manufacturer['id']);
                // En este punto, el modelo ya debería existir (creado en el paso de confirmación).
                // Si aún no existe, es un error.
                if (!$model) {
                    throw new Exception($t('import_model_not_found_for_manufacturer_row', ['%row%' => $rowNumber, '%model_name%' => $modelName, '%manufacturer_name%' => $manufacturerName]));
                }
                $assetData['id_modelo'] = $model['id'];
            }
        }

        $statusName = $rowData[$t('status_name')] ?? '';
        if (!empty($statusName)) {
            $status = $this->assetStatusModel->getByName(strtolower(trim($statusName)));
            if (!$status) { throw new Exception($t('import_status_not_found_row', ['%row%' => $rowNumber, '%name%' => $statusName])); }
            $assetData['id_estado'] = $status['id'];
        }

        $locationName = $rowData[$t('location_name')] ?? '';
        if (!empty($locationName)) {
            $location = $this->locationModel->getByName(strtolower(trim($locationName)));
            if (!$location) { throw new Exception($t('import_location_not_found_row', ['%row%' => $rowNumber, '%name%' => $locationName])); }
            $assetData['id_ubicacion'] = $location['id'];
        }
        
        $departmentName = $rowData[$t('department_name')] ?? '';
        if (!empty($departmentName)) {
            $department = $this->departmentModel->getByName(strtolower(trim($departmentName)));
            if (!$department) { throw new Exception($t('import_department_not_found_row', ['%row%' => $rowNumber, '%name%' => $departmentName])); }
            $assetData['id_departamento'] = $department['id'];
        }
        
        $acquisitionFormatName = $rowData[$t('acquisition_format_name')] ?? '';
        if (!empty($acquisitionFormatName)) {
            $acquisitionFormat = $this->acquisitionFormatModel->getByName(strtolower(trim($acquisitionFormatName)));
            if (!$acquisitionFormat) { throw new Exception($t('import_acquisition_format_not_found_row', ['%row%' => $rowNumber, '%name%' => $acquisitionFormatName])); }
            $assetData['id_formato_adquisicion'] = $acquisitionFormat['id'];
        }
        
        $acquisitionProviderName = $rowData[$t('acquisition_provider_name')] ?? '';
        if (!empty($acquisitionProviderName)) {
            $provider = $this->providerModel->getByName(strtolower(trim($acquisitionProviderName)));
            if (!$provider) { throw new Exception($t('import_provider_not_found_row', ['%row%' => $rowNumber, '%name%' => $acquisitionProviderName])); }
            $assetData['id_proveedor_adquisicion'] = $provider['id'];
        }
        
        // --- Campos Personalizados (si la plantilla es específica) ---
        // Se asume que el nombre de la cabecera es el que contiene el nombre del campo.
        $customFieldValues = [];
        if ($assetTypeId !== null) {
            $customFieldDefinitions = $this->customFieldDefinitionModel->getAll($assetTypeId);
            foreach ($customFieldDefinitions as $def) {
                // Construir la cabecera del CSV tal como se genera en CsvTemplateService
                $headerName = $def['nombre_campo'] . ' (' . $def['tipo_dato'] . ($def['unidad'] ? ' - ' . $def['unidad'] : '') . ($def['es_requerido'] ? ' *' : '') . ')';
                $customFieldValue = $rowData[$headerName] ?? null;

                if ($def['es_requerido'] && empty($customFieldValue)) {
                    throw new Exception($t('import_custom_field_required_row', ['%row%' => $rowNumber, '%field_name%' => $def['nombre_campo']]));
                }
                $customFieldValues[$def['id']] = $customFieldValue;
            }
        }
        
        if (empty($assetData['nombre'])) { throw new Exception($t('import_asset_name_required_row', ['%row%' => $rowNumber])); }
        if (empty($assetData['id_tipo_activo'])) { throw new Exception($t('import_asset_type_id_required_row', ['%row%' => $rowNumber])); }
        if (empty($assetData['id_estado'])) { throw new Exception($t('import_status_id_required_row', ['%row%' => $rowNumber])); }

        $existingAsset = null;
        $providedSerialNumber = $assetData['numero_serie'];

        // Estrategia de importación:
        // 1. Si se proporciona un número de serie en el CSV.
        if (!empty($providedSerialNumber)) {
            $existingAsset = $this->assetModel->getBySerialNumberAndAssetType(
                $providedSerialNumber,
                $assetData['id_tipo_activo']
            );

            if ($existingAsset) {
                // Actualizar el activo existente.
                $status = 'updated';
                $oldData = $existingAsset;
                $this->assetModel->update($existingAsset['id'], $assetData);
                $assetId = $existingAsset['id'];
                $this->logger->info($t('import_asset_updated_log', ['%row%' => $rowNumber, '%identifier%' => $assetData['numero_serie']]));
                if ($this->sessionService->get('user_id')) {
                    $this->logService->logChange($assetId, $this->sessionService->get('user_id'), 'MODIFICACION', $oldData, $assetData);
                }
            } else {
                // Crear un nuevo activo con el número de serie proporcionado.
                $status = 'new';
                $assetId = $this->assetModel->create($assetData);
                $this->logger->info($t('import_asset_created_log', ['%row%' => $rowNumber, '%identifier%' => $assetData['numero_serie']]));
                if ($this->sessionService->get('user_id') && $assetId) {
                    $newData = $this->assetModel->getById($assetId);
                    $this->logService->logChange($assetId, $this->sessionService->get('user_id'), 'CREACION', null, $newData);
                }
            }
        } else { // 2. Si NO se proporciona un número de serie en el CSV.
            $existingAsset = $this->assetModel->getByNameAndAssetType(
                $assetData['nombre'],
                $assetData['id_tipo_activo']
            );

            if ($existingAsset) {
                // Error: Ya existe un activo con el mismo nombre y tipo, y no se puede actualizar sin número de serie.
                throw new Exception($t('import_asset_exists_no_serial_row', ['%row%' => $rowNumber, '%name%' => $assetData['nombre']]));
            } else {
                // Crear un nuevo activo generando un número de serie.
                $status = 'new';
                $nextId = $this->sequenceModel->getNextValue('asset_serial_null');
                $assetData['numero_serie'] = 'SN#null#' . str_pad($nextId, 8, '0', STR_PAD_LEFT);
                $assetId = $this->assetModel->create($assetData);
                $this->logger->info($t('import_asset_created_log', ['%row%' => $rowNumber, '%identifier%' => $assetData['numero_serie']]));
                if ($this->sessionService->get('user_id') && $assetId) {
                    $newData = $this->assetModel->getById($assetId);
                    $this->logService->logChange($assetId, $this->sessionService->get('user_id'), 'CREACION', null, $newData);
                }
            }
        }

        if ($assetId && $assetTypeId !== null && !empty($customFieldValues)) {
            foreach ($customFieldValues as $defId => $value) {
                $this->customFieldValueModel->createOrUpdate($assetId, $defId, $value);
            }
        }

        return $status;
    }

    /**
     * Lógica de importación para una fila de fabricante.
     * @param array $rowData Datos de la fila.
     * @param int $rowNumber Número de fila para mensajes de error.
     * @return string 'new' o 'updated'
     * @throws Exception
     */
    private function importManufacturerRow(array $rowData, int $rowNumber): string
    {
        $t = $this->translator;
        $status = 'new';
        $name = strtolower(trim($rowData[$t('name')] ?? ''));
        $description = $rowData[$t('description')] ?? null;

        if (empty($name)) {
            throw new Exception($t('import_name_required_row', ['%row%' => $rowNumber]));
        }

        $userId = $this->sessionService->get('user_id');

        $existing = $this->manufacturerModel->getByName($name);
        if ($existing) {
            $oldData = $existing;
            $newData = ['nombre' => $name, 'descripcion' => $description];
            $this->manufacturerModel->update($existing['id'], $name, $description);
            $status = 'updated';
            $this->logger->info($t('import_manufacturer_updated_log', ['%row%' => $rowNumber, '%name%' => $name]));
            if ($userId) { $this->logService->logChange($existing['id'], $userId, 'MODIFICACION', $oldData, $newData); }
        } else {
            $newId = $this->manufacturerModel->create($name, $description);
            if ($userId && $newId) {
                $newData = $this->manufacturerModel->getById($newId);
                // El log de auditoría es para activos, así que aquí solo logueamos en el fichero
                // Para una auditoría completa de maestros se necesitaría una tabla de logs genérica.
            }
            $this->logger->info($t('import_manufacturer_created_log', ['%row%' => $rowNumber, '%name%' => $name]));
        }
        return $status;
    }
    
    /**
     * Lógica de importación para una fila de proveedor.
     * @param array $rowData Datos de la fila.
     * @param int $rowNumber Número de fila para mensajes de error.
     * @return string 'new' o 'updated'
     * @throws Exception
     */
    private function importProviderRow(array $rowData, int $rowNumber): string
    {
        $t = $this->translator;
        $status = 'new';
        $name = strtolower(trim($rowData[$t('name')] ?? ''));
        $contact = $rowData[$t('contact_person')] ?? null;
        $phone = $rowData[$t('phone')] ?? null;
        $email = $rowData[$t('email_address')] ?? null;

        if (empty($name)) { throw new Exception($t('import_name_required_row', ['%row%' => $rowNumber])); }
        if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) { throw new Exception($t('import_invalid_email_format_row', ['%row%' => $rowNumber])); }

        $userId = $this->sessionService->get('user_id');

        $existing = $this->providerModel->getByName($name);
        if ($existing) {
            $oldData = $existing;
            $newData = ['nombre' => $name, 'contacto' => $contact, 'telefono' => $phone, 'email' => $email];
            $this->providerModel->update($existing['id'], $name, $contact, $phone, $email);
            $status = 'updated';
            $this->logger->info($t('import_provider_updated_log', ['%row%' => $rowNumber, '%name%' => $name]));
            // Log de auditoría no aplica directamente a proveedores en la tabla log_activos
        } else {
            $newId = $this->providerModel->create($name, $contact, $phone, $email);
            if ($userId && $newId) {
                $newData = $this->providerModel->getById($newId);
                // Log de auditoría no aplica
            }
            $this->logger->info($t('import_provider_created_log', ['%row%' => $rowNumber, '%name%' => $name]));
        }
        return $status;
    }

    /**
     * Lógica de importación para una fila de contrato.
     * @param array $rowData Datos de la fila.
     * @param int $rowNumber Número de fila para mensajes de error.
     * @return string 'new' o 'updated'
     * @throws Exception
     */
    private function importContractRow(array $rowData, int $rowNumber): string
    {
        $t = $this->translator;
        $status = 'new';
        $contractNumber = strtolower(trim($rowData[$t('contract_number')] ?? ''));
        $contractTypeName = strtolower(trim($rowData[$t('contract_type_name')] ?? ''));
        $providerName = strtolower(trim($rowData[$t('provider_name')] ?? ''));
        $startDate = $this->formatDateForDb($rowData[$t('start_date')] ?? null);
        $endDate = $this->formatDateForDb($rowData[$t('end_date')] ?? null);
        $annualCost = $this->formatDecimalForDb($rowData[$t('annual_cost')] ?? null);
        $description = $rowData[$t('description')] ?? null;

        if (empty($contractNumber) || empty($contractTypeName) || empty($startDate) || empty($endDate)) {
            throw new Exception($t('import_contract_required_fields_row', ['%row%' => $rowNumber]));
        }

        $contractType = $this->contractTypeModel->getByName($contractTypeName);
        if (!$contractType) { throw new Exception($t('import_contract_type_not_found_row', ['%row%' => $rowNumber, '%name%' => $rowData[$t('contract_type_name')]])); }        
        $provider = $this->providerModel->getByName($providerName);
        if (!$provider) { throw new Exception($t('import_provider_not_found_row', ['%row%' => $rowNumber, '%name%' => $rowData[$t('provider_name')]])); }

        $contractData = [
            'numero_contrato' => $contractNumber,
            'id_tipo_contrato' => $contractType['id'],
            'id_proveedor' => $provider['id'],
            'fecha_inicio' => $startDate,
            'fecha_fin' => $endDate,
            'costo_anual' => $annualCost,
            'descripcion' => $description
        ];

        $userId = $this->sessionService->get('user_id');

        $existing = $this->contractModel->getByContractNumber($contractNumber);
        if ($existing) {
            $oldData = $existing;
            $this->contractModel->update($existing['id'], $contractData);
            $status = 'updated';
            $this->logger->info($t('import_contract_updated_log', ['%row%' => $rowNumber, '%number%' => $contractNumber]));
            // Log de auditoría no aplica directamente a contratos en la tabla log_activos
        } else {
            $newId = $this->contractModel->create($contractData);
            if ($userId && $newId) {
                $newData = $this->contractModel->getById($newId);
                // Log de auditoría no aplica
            }
            $this->logger->info($t('import_contract_created_log', ['%row%' => $rowNumber, '%number%' => $contractNumber]));
        }
        return $status;
    }

    /**
     * Lógica de importación para una fila de maestro genérico (AssetType, AssetStatus, etc.).
     * @param string $entityType Tipo de entidad (ej. 'asset_types').
     * @param array $rowData Datos de la fila.
     * @param int $rowNumber Número de fila para mensajes de error.
     * @return string 'new' o 'updated'
     * @throws Exception
     */
    private function importGenericMasterRow(string $entityType, array $rowData, int $rowNumber): string
    {
        $t = $this->translator;
        $status = 'new';
        $name = strtolower(trim($rowData[$t('name')] ?? ''));
        $description = $rowData[$t('description')] ?? null;

        if (empty($name)) {
            throw new Exception($t('import_name_required_row', ['%row%' => $rowNumber]));
        }

        $model = null;
        switch ($entityType) {
            case 'asset_types': $model = $this->assetTypeModel; break;
            case 'asset_statuses': $model = $this->assetStatusModel; break;
            case 'contract_types': $model = $this->contractTypeModel; break;
            case 'locations': $model = $this->locationModel; break;
            case 'departments': $model = $this->departmentModel; break;
            case 'acquisition_formats': $model = $this->acquisitionFormatModel; break;
            default:
                throw new Exception($t('import_unsupported_generic_master', ['%entity_type%' => $entityType]));
        }

        $userId = $this->sessionService->get('user_id');

        $existing = $model->getByName($name);
        if ($existing) {
            $oldData = $existing;
            $newData = ['nombre' => $name, 'descripcion' => $description];
            $model->update($existing['id'], $name, $description);
            $status = 'updated';
            $this->logger->info($t('import_generic_master_updated_log', ['%row%' => $rowNumber, '%entity_type%' => $entityType, '%name%' => $name]));
            // Log de auditoría no aplica directamente a maestros en la tabla log_activos
        } else {
            $newId = $model->create($name, $description);
            if ($userId && $newId) {
                $newData = $model->getById($newId);
                // Log de auditoría no aplica
            }
            $this->logger->info($t('import_generic_master_created_log', ['%row%' => $rowNumber, '%entity_type%' => $entityType, '%name%' => $name]));
        }
        return $status;
    }

    /**
     * Convierte una fecha del formato 'd/m/Y' o 'd-m-Y' al formato 'Y-m-d' de la base de datos.
     * Si el formato es inválido o la fecha está vacía, devuelve null.
     * @param string|null $dateString La fecha del CSV.
     * @return string|null La fecha en formato 'Y-m-d' o null.
     */
    private function formatDateForDb(?string $dateString): ?string
    {
        if (empty($dateString)) {
            return null;
        }
        // Reemplazar guiones por barras para unificar el separador
        $dateString = str_replace('-', '/', $dateString);
        $date = \DateTime::createFromFormat('d/m/Y', $dateString);
        if ($date && $date->format('d/m/Y') === $dateString) {
            return $date->format('Y-m-d');
        }
        return null; // Devuelve null si el formato es incorrecto
    }

    /**
     * Convierte un valor numérico del CSV al formato adecuado para la base de datos.
     * Reemplaza la coma decimal por un punto y convierte cadenas vacías a NULL.
     * @param string|null $decimalString El valor del CSV.
     * @return string|null El valor formateado para la DB o null.
     */
    private function formatDecimalForDb(?string $decimalString): ?string
    {
        if ($decimalString === null || trim($decimalString) === '') {
            return null;
        }

        // Reemplazar la coma decimal por un punto
        $formatted = str_replace(',', '.', $decimalString);

        // Validar que el resultado es un número válido antes de devolverlo
        return is_numeric($formatted) ? $formatted : null;
    }
}
