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
        Model $modelModel
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
    }

    /**
     * Procesa un archivo CSV para importar datos a una entidad específica.
     * @param string $filePath La ruta temporal del archivo CSV subido.
     * @param string $entityType El tipo de entidad a importar (ej. 'assets', 'manufacturers').
     * @param int|null $assetTypeId Para importación de activos, el ID del tipo de activo si la plantilla es específica.
     * @return array Un array con el resumen de la importación y los resultados fila por fila.
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

        $records = $reader->getRecords();

        foreach ($records as $offset => $record) {
            $totalRows++;
            $rowData = $record;
            $rowNumber = $offset + 2;

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
                if (str_contains($e->getMessage(), 'Duplicate entry')) {
                    $message = $t('import_duplicate_entry_row', ['%row%' => $rowNumber, '%message%' => $e->getMessage()]);
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
            'fecha_compra'                 => $rowData[$t('purchase_date')] ?? null,
            'precio_compra'                => $rowData[$t('purchase_price')] ?? null,
            'fecha_fin_garantia'           => $rowData[$t('warranty_end_date')] ?? null,
            'fecha_fin_mantenimiento'      => $rowData[$t('maintenance_end_date')] ?? null,
            'fecha_fin_vida'               => $rowData[$t('eol_date')] ?? null,
            'fecha_fin_soporte_mainstream' => $rowData[$t('mainstream_support_end_date')] ?? null,
            'fecha_fin_soporte_extended'   => $rowData[$t('extended_support_end_date')] ?? null,
            'fecha_venta'                  => $rowData[$t('sale_date')] ?? null,
            'valor_residual'               => $rowData[$t('residual_value')] ?? null,
        ];

        // --- Resolución de IDs a partir de Nombres (para FKs) ---
        $assetTypeName = $rowData[$t('asset_type_name')] ?? '';
        if (empty($assetTypeName)) {
            throw new Exception($t('import_asset_type_name_required_row', ['%row%' => $rowNumber]));
        }
        $assetType = $this->assetTypeModel->getByName($assetTypeName); // Necesita getByName
        if (!$assetType) {
            throw new Exception($t('import_asset_type_not_found_row', ['%row%' => $rowNumber, '%name%' => $assetTypeName]));
        }
        $assetData['id_tipo_activo'] = $assetType['id'];
        
        // ... (Resolución de otros FKs para Manufacturer, Model, Status, Location, etc.)
        // Se asume que getByName() existe para los modelos correspondientes.
        $manufacturerName = $rowData[$t('manufacturer_name')] ?? '';
        if (!empty($manufacturerName)) {
            $manufacturer = $this->manufacturerModel->getByName($manufacturerName);
            if (!$manufacturer) { throw new Exception($t('import_manufacturer_not_found_row', ['%row%' => $rowNumber, '%name%' => $manufacturerName])); }
            $assetData['id_fabricante'] = $manufacturer['id'];
        }

        $modelName = $rowData[$t('model_name')] ?? '';
        if (!empty($modelName)) {
            $model = $this->modelModel->getByName($modelName);
            if (!$model) { throw new Exception($t('import_model_not_found_row', ['%row%' => $rowNumber, '%name%' => $modelName])); }
            $assetData['id_modelo'] = $model['id'];
        }

        $statusName = $rowData[$t('status_name')] ?? '';
        if (!empty($statusName)) {
            $status = $this->assetStatusModel->getByName($statusName);
            if (!$status) { throw new Exception($t('import_status_not_found_row', ['%row%' => $rowNumber, '%name%' => $statusName])); }
            $assetData['id_estado'] = $status['id'];
        }

        $locationName = $rowData[$t('location_name')] ?? '';
        if (!empty($locationName)) {
            $location = $this->locationModel->getByName($locationName);
            if (!$location) { throw new Exception($t('import_location_not_found_row', ['%row%' => $rowNumber, '%name%' => $locationName])); }
            $assetData['id_ubicacion'] = $location['id'];
        }
        
        $departmentName = $rowData[$t('department_name')] ?? '';
        if (!empty($departmentName)) {
            $department = $this->departmentModel->getByName($departmentName);
            if (!$department) { throw new Exception($t('import_department_not_found_row', ['%row%' => $rowNumber, '%name%' => $departmentName])); }
            $assetData['id_departamento'] = $department['id'];
        }
        
        $acquisitionFormatName = $rowData[$t('acquisition_format_name')] ?? '';
        if (!empty($acquisitionFormatName)) {
            $acquisitionFormat = $this->acquisitionFormatModel->getByName($acquisitionFormatName);
            if (!$acquisitionFormat) { throw new Exception($t('import_acquisition_format_not_found_row', ['%row%' => $rowNumber, '%name%' => $acquisitionFormatName])); }
            $assetData['id_formato_adquisicion'] = $acquisitionFormat['id'];
        }
        
        $acquisitionProviderName = $rowData[$t('acquisition_provider_name')] ?? '';
        if (!empty($acquisitionProviderName)) {
            $provider = $this->providerModel->getByName($acquisitionProviderName);
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
        if (empty($assetData['numero_serie'])) { throw new Exception($t('import_serial_number_required_row', ['%row%' => $rowNumber])); }
        if (empty($assetData['id_tipo_activo'])) { throw new Exception($t('import_asset_type_id_required_row', ['%row%' => $rowNumber])); }
        if (empty($assetData['id_estado'])) { throw new Exception($t('import_status_id_required_row', ['%row%' => $rowNumber])); }

        $existingAsset = $this->assetModel->getBySerialNumber($assetData['numero_serie']);
        if ($existingAsset) {
            $this->assetModel->update($existingAsset['id'], $assetData);
            $status = 'updated';
            $assetId = $existingAsset['id'];
            $this->logger->info($t('import_asset_updated_log', ['%row%' => $rowNumber, '%serial%' => $assetData['numero_serie']]));
        } else {
            $assetId = $this->assetModel->create($assetData);
            $status = 'new';
            $this->logger->info($t('import_asset_created_log', ['%row%' => $rowNumber, '%serial%' => $assetData['numero_serie']]));
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
        $name = $rowData[$t('name')] ?? '';
        $description = $rowData[$t('description')] ?? null;

        if (empty($name)) {
            throw new Exception($t('import_name_required_row', ['%row%' => $rowNumber]));
        }

        $existing = $this->manufacturerModel->getByName($name);
        if ($existing) {
            $this->manufacturerModel->update($existing['id'], $name, $description);
            $status = 'updated';
            $this->logger->info($t('import_manufacturer_updated_log', ['%row%' => $rowNumber, '%name%' => $name]));
        } else {
            $this->manufacturerModel->create($name, $description);
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
        $name = $rowData[$t('name')] ?? '';
        $contact = $rowData[$t('contact_person')] ?? null;
        $phone = $rowData[$t('phone')] ?? null;
        $email = $rowData[$t('email_address')] ?? null;

        if (empty($name)) { throw new Exception($t('import_name_required_row', ['%row%' => $rowNumber])); }
        if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) { throw new Exception($t('import_invalid_email_format_row', ['%row%' => $rowNumber])); }

        $existing = $this->providerModel->getByName($name);
        if ($existing) {
            $this->providerModel->update($existing['id'], $name, $contact, $phone, $email);
            $status = 'updated';
            $this->logger->info($t('import_provider_updated_log', ['%row%' => $rowNumber, '%name%' => $name]));
        } else {
            $this->providerModel->create($name, $contact, $phone, $email);
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
        $contractNumber = $rowData[$t('contract_number')] ?? '';
        $contractTypeName = $rowData[$t('contract_type_name')] ?? '';
        $providerName = $rowData[$t('provider_name')] ?? '';
        $startDate = $rowData[$t('start_date')] ?? null;
        $endDate = $rowData[$t('end_date')] ?? null;
        $annualCost = $rowData[$t('annual_cost')] ?? null;
        $description = $rowData[$t('description')] ?? null;

        if (empty($contractNumber) || empty($contractTypeName) || empty($startDate) || empty($endDate)) {
            throw new Exception($t('import_contract_required_fields_row', ['%row%' => $rowNumber]));
        }

        $contractType = $this->contractTypeModel->getByName($contractTypeName);
        if (!$contractType) { throw new Exception($t('import_contract_type_not_found_row', ['%row%' => $rowNumber, '%name%' => $contractTypeName])); }
        $provider = $this->providerModel->getByName($providerName);
        if (!$provider) { throw new Exception($t('import_provider_not_found_row', ['%row%' => $rowNumber, '%name%' => $providerName])); }

        $contractData = [
            'numero_contrato' => $contractNumber,
            'id_tipo_contrato' => $contractType['id'],
            'id_proveedor' => $provider['id'],
            'fecha_inicio' => $startDate,
            'fecha_fin' => $endDate,
            'costo_anual' => (float)$annualCost,
            'descripcion' => $description
        ];

        $existing = $this->contractModel->getByNumber($contractNumber);
        if ($existing) {
            $this->contractModel->update($existing['id'], $contractData);
            $status = 'updated';
            $this->logger->info($t('import_contract_updated_log', ['%row%' => $rowNumber, '%number%' => $contractNumber]));
        } else {
            $this->contractModel->create($contractData);
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
        $name = $rowData[$t('name')] ?? '';
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

        $existing = $model->getByName($name);
        if ($existing) {
            $model->update($existing['id'], $name, $description);
            $status = 'updated';
            $this->logger->info($t('import_generic_master_updated_log', ['%row%' => $rowNumber, '%entity_type%' => $entityType, '%name%' => $name]));
        } else {
            $model->create($name, $description);
            $this->logger->info($t('import_generic_master_created_log', ['%row%' => $rowNumber, '%entity_type%' => $entityType, '%name%' => $name]));
        }
        return $status;
    }
}
