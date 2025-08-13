<?php
// app/Controllers/ApiController.php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

// Modelos API
use App\Models\CustomFieldDefinition;
use App\Models\Model as AssetModel;

// Servicios
use App\Services\LdapService;

class ApiController
{
    private ContainerInterface $container; // Mantener por si se necesita para otros servicios
    private LoggerInterface $logger; // <--- ¡NUEVA PROPIEDAD!
    private $translator; // <-- Se necesitará para el traductor

    public function __construct(ContainerInterface $container, LoggerInterface $logger, callable $translator) // <--- ¡CAMBIO EN CONSTRUCTOR!
    {
        $this->container = $container;
        $this->logger = $logger; // <--- ASIGNACIÓN
        $this->translator = $translator; // <--- ASIGNACIÓN
    }

    /**
     * Obtiene definiciones de campos personalizados por ID de tipo de activo.
     */
    public function getCustomFieldDefinitions(Request $request, Response $response, array $args): Response
    {
        $assetTypeId = (int)$args['asset_type_id'];
        $customFieldDefinitionModel = $this->container->get(CustomFieldDefinition::class);
        $definitions = $customFieldDefinitionModel->getAll($assetTypeId);
        
        $response->getBody()->write(json_encode($definitions));
        return $response->withHeader('Content-Type', 'application/json');
    }

    /**
     * Obtiene modelos filtrados por ID de fabricante.
     */
    public function getModelsByManufacturer(Request $request, Response $response, array $args): Response
    {
        $manufacturerId = (int)$args['manufacturer_id'];
        $modelModel = $this->container->get(AssetModel::class);
        $models = $modelModel->getByManufacturerId($manufacturerId);

        $response->getBody()->write(json_encode($models));
        return $response->withHeader('Content-Type', 'application/json');
    }

    /**
     * Prueba la conexión a una fuente de usuario (LDAP/AD).
     */
    public function testSourceConnection(Request $request, Response $response, array $args): Response
    {
        $data = $request->getParsedBody();
        $t = $this->translator;

        // Obtener el servicio LdapService del contenedor
        $ldapService = $this->container->get(LdapService::class);

        $sourceConfig = [
            'host' => $data['host'] ?? '',
            'port' => (int)($data['port'] ?? 0),
            'base_dn' => $data['base_dn'] ?? '',
            'bind_dn' => $data['bind_dn'] ?? '',
            'bind_password' => $data['bind_password'] ?? '',
            'user_filter' => $data['user_filter'] ?? '',
            'group_filter' => $data['group_filter'] ?? '',
            'use_tls' => (bool)($data['use_tls'] ?? false),
            'use_ssl' => (bool)($data['use_ssl'] ?? false),
            'ca_cert_path' => $data['ca_cert_path'] ?? '',
            'timeout' => (int)($data['timeout'] ?? 0),
        ];

        // Llamar al método real de test de conexión del LdapService
        $testResult = $ldapService->testConnection($sourceConfig);

        // Asegurarse de que el logger usa $this->logger
        $this->logger->info($t('debug_api_test_connection_attempt', ['%host%' => $sourceConfig['host'], '%result%' => json_encode($testResult)]));

        $response->getBody()->write(json_encode($testResult));
        return $response->withHeader('Content-Type', 'application/json');
        // ===============================================
    }
}
