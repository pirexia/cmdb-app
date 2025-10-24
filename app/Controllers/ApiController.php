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
use App\Services\MailService;

class ApiController
{
    private ContainerInterface $container; // Mantener por si se necesita para otros servicios
    private LoggerInterface $logger; // <--- ¡NUEVA PROPIEDAD!
    private $translator; // <-- Se necesitará para el traductor
    private MailService $mailService;

    public function __construct(ContainerInterface $container, LoggerInterface $logger, callable $translator, MailService $mailService) // <--- ¡CAMBIO EN CONSTRUCTOR!
    {
        $this->container = $container;
        $this->logger = $logger; // <--- ASIGNACIÓN
        $this->translator = $translator; // <--- ASIGNACIÓN
        $this->mailService = $mailService;
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

    /**
     * Endpoint para probar la conexión SMTP.
     * Recibe los datos de configuración por POST y devuelve un JSON con el resultado.
     */
    public function testSmtpConnection(Request $request, Response $response): Response
    {
        $t = $this->translator;
        $data = $request->getParsedBody();

        // Obtener el SmtpService del contenedor
        $smtpService = $this->container->get(\App\Services\SmtpService::class);

        // Llamar al método de prueba
        $result = $smtpService->testSmtpConnection($data);

        $this->logger->info($t('debug_api_test_smtp_connection_attempt', [
            '%host%' => $data['host'] ?? 'N/A',
            '%result%' => $result['success'] ? 'Success' : 'Failure'
        ]));

        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function sendTestEmail(Request $request, Response $response): Response
    {
        $t = $this->translator;

        // --- Test de envío de correo independiente ---
        // Se envía siempre a una dirección fija para probar el MailService.
        $to = 'andres.matias@dachser.com';
        $subject = $t('test_email_subject');
        $template = 'test_email'; // Nombre de la plantilla de correo

        // Datos simulados para la plantilla, para no depender de un usuario real.
        $data = [
            'user' => ['nombre_usuario' => 'Usuario de Prueba'],
            'appName' => 'CMDB App'
        ];

        $success = $this->mailService->sendEmail($to, $subject, $template, $data);

        if ($success) {
            $result = ['success' => true, 'message' => $t('test_email_sent_successfully_to', ['%email%' => $to])];
        } else {
            $result = ['success' => false, 'message' => $t('test_email_failed_to_send_to', ['%email%' => $to])];
        }

        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json');
    }

    /**
     * Endpoint para geocodificar una dirección.
     */
    public function geocode(Request $request, Response $response): Response
    {
        $t = $this->translator;
        $data = $request->getParsedBody();

        $addressData = [
            'direccion'     => $data['direccion'] ?? '',
            'poblacion'     => $data['poblacion'] ?? '',
            'codigo_postal' => $data['codigo_postal'] ?? '',
            'provincia'     => $data['provincia'] ?? '',
            'pais'          => $data['pais'] ?? '',
        ];

        $result = $this->geocodeAddress($addressData);

        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json');
    }

    /**
     * Realiza una llamada a la API de Nominatim para obtener coordenadas.
     * @param array $addressData Datos de la dirección.
     * @return array ['success' => bool, 'data' => ['lat' => float, 'lon' => float]|['error' => string, 'urls' => array]]
     */
    private function geocodeAddress(array $addressData): array
    {
        $t = $this->translator;

        // --- Nueva Estrategia de Búsqueda ---
        $searchStrings = [];

        // Intento 1: Dirección completa
        $searchStrings[] = implode(', ', array_filter([$addressData['direccion'], $addressData['poblacion'], $addressData['codigo_postal'], $addressData['pais']]));

        // Intento 2: Simplificar la dirección (quitar número, piso, etc., si hay una coma)
        if (str_contains($addressData['direccion'], ',')) {
            $simplifiedStreet = explode(',', $addressData['direccion'])[0];
            $searchStrings[] = implode(', ', array_filter([$simplifiedStreet, $addressData['poblacion'], $addressData['codigo_postal'], $addressData['pais']]));
        }

        // Intento 3: Fallback a solo la población
        $searchStrings[] = implode(', ', array_filter([$addressData['poblacion'], $addressData['codigo_postal'], $addressData['pais']]));

        // Eliminar intentos vacíos o duplicados
        $searchStrings = array_unique(array_filter($searchStrings, 'trim'));

        if (empty($searchStrings)) {
            return ['success' => false, 'data' => ['error' => $t('no_address_provided') ?? 'No address provided', 'urls' => []]];
        }

        $attemptedUrls = [];

        foreach ($searchStrings as $index => $addressString) {
            $url = "https://nominatim.openstreetmap.org/search?q=" . urlencode($addressString) . "&format=json&limit=1";
            $attemptedUrls[] = $url; // Guardar todas las URLs intentadas

            $this->logger->info("Intentando geocodificación con URL: " . $url);

            $opts = ['http' => ['method' => "GET", 'header' => "User-Agent: CMDB-App/1.0\r\n"]];
            $context = stream_context_create($opts);
            $apiResponse = @file_get_contents($url, false, $context);

            if ($apiResponse) {
                $json = json_decode($apiResponse, true);
                if (!empty($json) && isset($json[0]['lat'], $json[0]['lon'])) {
                    $this->logger->info("Geocodificación exitosa para la dirección: " . $addressString);
                    // Considerar aproximado si no es el primer o segundo intento (que incluyen la calle)
                    $is_approximate = ($index > 1); // 0: completo, 1: simplificado, 2: solo población
                    return ['success' => true, 'data' => [
                        'lat' => (float)$json[0]['lat'], 
                        'lon' => (float)$json[0]['lon'],
                        'is_approximate' => $is_approximate
                    ]];
                }
            }
        }

        // Si todos los intentos fallan
        $finalAddressString = reset($searchStrings); // Usar la primera (la más completa) para el mensaje de error
        $this->logger->warning("Geocodificación fallida para la dirección: " . $finalAddressString . ". URLs intentadas: " . implode(' | ', $attemptedUrls));
        
        return ['success' => false, 'data' => [
            'error' => $t('geocoding_api_error_details', ['%address%' => $finalAddressString]) ?? 'Geocoding API could not find coordinates for the address: ' . $finalAddressString,
            'urls' => $attemptedUrls
        ]];
    }
}
