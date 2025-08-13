<?php
// app/Services/CsvTemplateService.php

namespace App\Services;

use Psr\Log\LoggerInterface;
use League\Csv\Writer;
use Exception;

use App\Models\AssetType;
use App\Models\CustomFieldDefinition;

/**
 * Clase CsvTemplateService
 * Este servicio es responsable de generar plantillas CSV descargables
 * para diferentes tipos de entidades (activos, fabricantes, contratos, etc.).
 * Las plantillas incluyen las cabeceras de los campos y datos de ejemplo.
 */
class CsvTemplateService
{
    private LoggerInterface $logger;
    private $translator;
    private AssetType $assetTypeModel;
    private CustomFieldDefinition $customFieldDefinitionModel;

    private array $templates = [];

    public function __construct(
        LoggerInterface $logger,
        callable $translator,
        AssetType $assetTypeModel,
        CustomFieldDefinition $customFieldDefinitionModel
    ) {
        $this->logger = $logger;
        $this->translator = $translator;
        $this->assetTypeModel = $assetTypeModel;
        $this->customFieldDefinitionModel = $customFieldDefinitionModel;
        $this->defineTemplates();
    }

    /**
     * Define las cabeceras y los datos de ejemplo para cada tipo de entidad.
     * Todas las plantillas ahora usan una única clave 'headers'.
     */
    private function defineTemplates(): void
    {
        $t = $this->translator;

        $this->templates = [
            'assets' => [
                'headers' => [ // Cabeceras estándar para activos (se extenderán dinámicamente).
                    $t('asset_name'),
                    $t('serial_number'),
                    $t('asset_type_name'),
                    $t('manufacturer_name'),
                    $t('model_name'),
                    $t('status_name'),
                    $t('location_name'),
                    $t('department_name'),
                    $t('acquisition_format_name'),
                    $t('acquisition_provider_name'),
                    $t('purchase_date'),
                    $t('purchase_price'),
                    $t('warranty_end_date'),
                    $t('maintenance_end_date'),
                    $t('eol_date'),
                    $t('mainstream_support_end_date'),
                    $t('extended_support_end_date'),
                    $t('sale_date'),
                    $t('residual_value'),
                    $t('asset_description'),
                ],
                'example_data' => [
                    [
                        'Laptop de Prueba', 'SN123456789', 'Ordenador Personal', 'HP', 'EliteBook G8', 'Activo',
                        'Oficina Principal', 'IT', 'Compra', 'PC Componentes',
                        '2023-01-15', '1200.50', '2026-01-15', '2027-01-15',
                        '2030-01-15', '2028-01-15', '2029-01-15', '2031-01-15',
                        '100.00', 'Laptop de prueba para desarrollo.'
                    ],
                    [
                        'Servidor Web Prod', 'SRV987654321', 'Servidor', 'Dell', 'PowerEdge R740', 'En Producción',
                        'Centro de Datos', 'IT', 'Alquiler', 'Dell España',
                        '2022-03-01', '0.00', '2025-03-01', '2026-03-01',
                        '2032-03-01', '2027-03-01', '2028-03-01', '',
                        '0.00', 'Servidor principal de aplicaciones web.'
                    ]
                ]
            ],
            // Otros maestros ahora usan 'headers' directamente
            'manufacturers' => [
                'headers' => [$t('name'), $t('description')],
                'example_data' => [
                    ['Dell', 'Fabricante de equipos informáticos.'],
                    ['HP', 'Fabricante de ordenadores y periféricos.']
                ]
            ],
            'providers' => [
                'headers' => [$t('name'), $t('contact_person'), $t('phone'), $t('email_address')],
                'example_data' => [
                    ['PC Componentes', 'Juan Pérez', '912345678', 'info@pccomponentes.com'],
                    ['Dell España', 'María García', '900123456', 'ventas@dell.es']
                ]
            ],
            'contracts' => [
                'headers' => [
                    $t('contract_number'),
                    $t('contract_type_name'),
                    $t('provider_name'),
                    $t('start_date'),
                    $t('end_date'),
                    $t('annual_cost'),
                    $t('description')
                ],
                'example_data' => [
                    ['LIC-SQL-2024', 'Licencia', 'Microsoft', '2024-01-01', '2025-01-01', '1200.00', 'Licencia de SQL Server para 1 año.'],
                    ['MANT-SRV-001', 'Mantenimiento', 'Dell España', '2023-06-01', '2026-06-01', '500.00', 'Contrato de mantenimiento para servidor PowerEdge.']
                ]
            ],
            'asset_types' => [
                'headers' => [$t('name'), $t('description')],
                'example_data' => [
                    ['Ordenador Personal', 'Equipos de escritorio y portátiles.'],
                    ['Servidor', 'Máquinas para alojar servicios.']
                ]
            ],
            'asset_statuses' => [
                'headers' => [$t('name'), $t('description')],
                'example_data' => [
                    ['Activo', 'En uso y operativo.'],
                    ['En Reparación', 'Activo actualmente en proceso de reparación.']
                ]
            ],
            'contract_types' => [
                'headers' => [$t('name'), $t('description')],
                'example_data' => [
                    ['Licencia', 'Contratos de uso de software.'],
                    ['Mantenimiento', 'Contratos de soporte y mantenimiento de hardware/software.']
                ]
            ],
            'locations' => [
                'headers' => [$t('name'), $t('description')],
                'example_data' => [
                    ['Oficina Principal', 'Sede central de la empresa.'],
                    ['Centro de Datos', 'Ubicación de los servidores.']
                ]
            ],
            'departments' => [
                'headers' => [$t('name'), $t('description')],
                'example_data' => [
                    ['IT', 'Departamento de Tecnologías de la Información.'],
                    ['Finanzas', 'Departamento de Contabilidad y Finanzas.']
                ]
            ],
            'acquisition_formats' => [
                'headers' => [$t('name'), $t('description')],
                'example_data' => [
                    ['Compra', 'Activo adquirido mediante compra directa.'],
                    ['Alquiler', 'Activo adquirido mediante contrato de alquiler.']
                ]
            ],
        ];
    }

    /**
     * Genera el contenido CSV para una plantilla específica.
     * Para 'assets', puede incluir cabeceras de campos personalizados si se proporciona assetTypeId.
     *
     * @param string $entityType El tipo de entidad para la que generar la plantilla (ej. 'assets', 'manufacturers').
     * @param int|null $assetTypeId Opcional. ID del tipo de activo si entityType es 'assets' y se quieren campos personalizados.
     * @return string El contenido del archivo CSV.
     * @throws Exception Si el tipo de entidad no está definido o hay un error al obtener campos personalizados.
     */
    public function generateTemplate(string $entityType, ?int $assetTypeId = null): string
    {
        $t = $this->translator;

        if (!isset($this->templates[$entityType])) {
            $this->logger->error($t('csv_template_not_defined', ['%entity_type%' => $entityType]));
            throw new Exception($t('csv_template_not_defined', ['%entity_type%' => $entityType]));
        }

        $template = $this->templates[$entityType];
        $headers = $template['headers']; // Todas las plantillas ahora tienen 'headers' directamente.
        $exampleData = $template['example_data'];

        // Lógica específica para añadir campos personalizados a la plantilla de 'assets'.
        if ($entityType === 'assets' && $assetTypeId !== null) {
            $customFieldDefinitions = $this->customFieldDefinitionModel->getAll($assetTypeId);
            
            if ($customFieldDefinitions === false) {
                $this->logger->error($t('error_loading_custom_field_definitions_for_template', ['%type_id%' => $assetTypeId]));
                throw new Exception($t('error_loading_custom_field_definitions_for_template', ['%type_id%' => $assetTypeId]));
            }

            // Añadir las cabeceras de los campos personalizados a las cabeceras base de activos.
            foreach ($customFieldDefinitions as $def) {
                $headers[] = $def['nombre_campo'] . 
                             ' (' . $def['tipo_dato'] . 
                             ($def['unidad'] ? ' - ' . $def['unidad'] : '') . 
                             ($def['es_requerido'] ? ' *' : '') . ')';
            }
            
            // Ajustar los datos de ejemplo para incluir columnas vacías para los campos personalizados.
            // Necesitamos saber cuántas cabeceras base teníamos antes de añadir las personalizadas.
            $numBaseHeaders = count($this->templates['assets']['headers']); // Contar las cabeceras base originales.
            $numCustomHeaders = count($headers) - $numBaseHeaders;
            
            $extendedExampleData = [];
            foreach ($exampleData as $row) {
                // Para cada fila de datos de ejemplo base, añadir N columnas vacías para los campos personalizados.
                for ($i = 0; $i < $numCustomHeaders; $i++) {
                    $row[] = ''; 
                }
                $extendedExampleData[] = $row;
            }
            $exampleData = $extendedExampleData; // Usar los datos de ejemplo extendidos.
        }

        $writer = Writer::createFromString('');
        $writer->setDelimiter(';');
        $writer->setOutputBOM(Writer::BOM_UTF8);

        $writer->insertOne($headers); // Insertar la fila de cabeceras finales.

        foreach ($exampleData as $row) {
            $writer->insertOne($row); // Insertar los datos de ejemplo extendidos.
        }

        return $writer->toString();
    }

    /**
     * Obtiene la lista de tipos de entidad para los que hay plantillas disponibles.
     * @return array Un array de nombres de entidad (claves del array $templates).
     */
    public function getAvailableTemplates(): array
    {
        return array_keys($this->templates);
    }
}
