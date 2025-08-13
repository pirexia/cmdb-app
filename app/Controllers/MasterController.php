<?php
// app/Controllers/MasterController.php

namespace App\Controllers;

use PDO;
use PDOException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use League\Plates\Engine as PlatesEngine;
use App\Services\SessionService;
use Psr\Log\LoggerInterface;

// Importar todos los modelos de maestros para que el mapeo explícito los conozca
use App\Models\Manufacturer;
use App\Models\AssetType;
use App\Models\AssetStatus;
use App\Models\ContractType;
use App\Models\Contract;
use App\Models\Location;
use App\Models\Department;
use App\Models\Provider;
use App\Models\AcquisitionFormat;
use App\Models\Language;

class MasterController
{
    private PlatesEngine $view;
    private SessionService $sessionService;
    private LoggerInterface $logger;
    private PDO $db;
    private $translator; // <-- ¡NUEVA PROPIEDAD!

    // Mapa explícito de nombres de URL (kebab-case) a clases de modelo
    private array $masterModelMap = [
        'manufacturer'       => Manufacturer::class,
        'asset-type'         => AssetType::class,
        'asset-status'       => AssetStatus::class,
        'contract-type'      => ContractType::class,
        'location'           => Location::class,
        'department'         => Department::class,
        'provider'           => Provider::class,
        'acquisition-format' => AcquisitionFormat::class,
        'language'           => Language::class,
        'contract'           => Contract::class
    ];

    public function __construct(
        PlatesEngine $view,
        SessionService $sessionService,
        LoggerInterface $logger,
        PDO $db,
        callable $translator // <-- ¡NUEVO ARGUMENTO!
    ) {
        $this->view = $view;
        $this->sessionService = $sessionService;
        $this->logger = $logger;
        $this->db = $db;
        $this->translator = $translator; // <-- ASIGNACIÓN
    }

    /**
     * Retorna una instancia del modelo de maestro especificado.
     * @param string $masterNameKebabCase El nombre del maestro (ej. 'Manufacturer', 'AssetType')
     * @return object La instancia del modelo correspondiente
     * @throws \InvalidArgumentException Si el modelo no existe.
     */
    private function getMasterModel(string $masterNameKebabCase): object
    {
        $t = $this->translator; // Accede a la función de traducción
        if (!isset($this->masterModelMap[$masterNameKebabCase])) {
            $this->logger->error($t('master_not_mapped_error', ['%master_name%' => $masterNameKebabCase]));
            throw new \InvalidArgumentException($t('invalid_master', ['%master_name%' => $masterNameKebabCase]));
        }
        $modelClass = $this->masterModelMap[$masterNameKebabCase];

        if (!class_exists($modelClass)) {
            $this->logger->critical($t('mapped_model_class_not_found', ['%class%' => $modelClass, '%master_name%' => $masterNameKebabCase]));
            throw new \InvalidArgumentException($t('internal_error_model_not_found', ['%class%' => $modelClass, '%master_name%' => $masterNameKebabCase]));
        }

        return new $modelClass($this->db);
    }

    /**
     * Muestra la lista de elementos para un maestro dado.
     * @param Request $request
     * @param Response $response
     * @param array $args Contiene 'master_name'
     * @return Response
     */
    public function listItems(Request $request, Response $response, array $args): Response
    {
        $masterNameKebabCase = $args['master_name'];
        $t = $this->translator; // Accede a la función de traducción
        $model = null;
        try {
            $model = $this->getMasterModel($masterNameKebabCase);
        } catch (\InvalidArgumentException $e) {
            $this->sessionService->addFlashMessage('danger', $t('invalid_master', ['%master_name%' => htmlspecialchars($masterNameKebabCase)]));
            $this->logger->error("Error en MasterController::listItems - " . $e->getMessage());
            return $response->withHeader('Location', '/dashboard')->withStatus(302);
        }

        $items = $model->getAll();
        if ($items === false) {
            $this->sessionService->addFlashMessage('danger', $t('error_loading_master_data', ['%master_name%' => htmlspecialchars($masterNameKebabCase)]));
            $this->logger->error("Error al obtener todos los elementos para el maestro: $masterNameKebabCase");
            $items = [];
        }

        $flashMessages = $this->sessionService->getFlashMessages();

        // Convertir para el título de la página
        $pageTitleSuffix = ucwords(str_replace('-', ' ', $masterNameKebabCase));

        $html = $this->view->render('masters/list', [
            'pageTitle' => $t('administer') . ' ' . $pageTitleSuffix, // Traducir "Administrar"
            'masterName' => $masterNameKebabCase,
            'items' => $items,
            'flashMessages' => $flashMessages
        ]);

        $response->getBody()->write($html);
        return $response;
    }

    /**
     * Muestra el formulario para crear un nuevo elemento en un maestro.
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     */
    public function showCreateForm(Request $request, Response $response, array $args): Response
    {
        $masterName = $args['master_name'];
        $t = $this->translator; // Accede a la función de traducción
        try {
            $this->getMasterModel($masterName); // Solo para validar que el maestro existe
        } catch (\InvalidArgumentException $e) {
            $this->sessionService->addFlashMessage('danger', $t('invalid_master', ['%master_name%' => htmlspecialchars($masterName)]));
            return $response->withHeader('Location', '/dashboard')->withStatus(302);
        }

        $flashMessages = $this->sessionService->getFlashMessages();

        $html = $this->view->render('masters/form', [
            'pageTitle' => $t('create') . ' ' . ucwords(str_replace('-', ' ', $masterName)), // Traducir "Crear"
            'masterName' => $masterName,
            'item' => null, // Indica que es un formulario de creación
            'flashMessages' => $flashMessages
        ]);

        $response->getBody()->write($html);
        return $response;
    }

    /**
     * Procesa la creación de un nuevo elemento en un maestro.
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     */
    public function processCreate(Request $request, Response $response, array $args): Response
    {
        $masterName = $args['master_name'];
        $data = $request->getParsedBody();
        $nombre = trim($data['nombre'] ?? '');
        $descripcion = trim($data['descripcion'] ?? null);
        $t = $this->translator; // Accede a la función de traducción

        if (empty($nombre)) {
            $this->sessionService->addFlashMessage('danger', $t('name_is_required')); // Traducir
            return $response->withHeader('Location', "/admin/masters/{$masterName}/create")->withStatus(302);
        }

        $model = null;
        try {
            $model = $this->getMasterModel($masterName);
        } catch (\InvalidArgumentException $e) {
            $this->sessionService->addFlashMessage('danger', $t('invalid_master', ['%master_name%' => htmlspecialchars($masterName)]));
            return $response->withHeader('Location', '/dashboard')->withStatus(302);
        }

        if ($model->create($nombre, $descripcion)) {
            $this->sessionService->addFlashMessage('success', ucwords(str_replace('-', ' ', $masterName)) . ' ' . $t('created_successfully')); // Traducir
            return $response->withHeader('Location', "/admin/masters/{$masterName}")->withStatus(302);
        } else {
            $this->sessionService->addFlashMessage('danger', $t('error_creating_master_item', ['%master_name%' => ucwords(str_replace('-', ' ', $masterName))])); // Traducir
            return $response->withHeader('Location', "/admin/masters/{$masterName}/create")->withStatus(302);
        }
    }

    /**
     * Muestra el formulario para editar un elemento existente.
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     */
    public function showEditForm(Request $request, Response $response, array $args): Response
    {
        $masterName = $args['master_name'];
        $id = (int)$args['id'];
        $t = $this->translator; // Accede a la función de traducción

        $model = null;
        try {
            $model = $this->getMasterModel($masterName);
        } catch (\InvalidArgumentException $e) {
            $this->sessionService->addFlashMessage('danger', $t('invalid_master', ['%master_name%' => htmlspecialchars($masterName)]));
            return $response->withHeader('Location', '/dashboard')->withStatus(302);
        }

        $item = $model->getById($id);
        if (!$item) {
            $this->sessionService->addFlashMessage('danger', ucwords(str_replace('-', ' ', $masterName)) . ' ' . $t('not_found')); // Traducir
            return $response->withHeader('Location', "/admin/masters/{$masterName}")->withStatus(302);
        }

        $flashMessages = $this->sessionService->getFlashMessages();

        $html = $this->view->render('masters/form', [
            'pageTitle' => $t('edit') . ' ' . ucwords(str_replace('-', ' ', $masterName)), // Traducir "Editar"
            'masterName' => $masterName,
            'item' => $item,
            'flashMessages' => $flashMessages
        ]);

        $response->getBody()->write($html);
        return $response;
    }

    /**
     * Procesa la actualización de un elemento existente.
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     */
    public function processUpdate(Request $request, Response $response, array $args): Response
    {
        $masterName = $args['master_name'];
        $id = (int)$args['id'];
        $data = $request->getParsedBody();
        $nombre = trim($data['nombre'] ?? '');
        $descripcion = trim($data['descripcion'] ?? null);
        $t = $this->translator; // Accede a la función de traducción

        if (empty($nombre)) {
            $this->sessionService->addFlashMessage('danger', $t('name_is_required')); // Traducir
            return $response->withHeader('Location', "/admin/masters/{$masterName}/edit/{$id}")->withStatus(302);
        }

        $model = null;
        try {
            $model = $this->getMasterModel($masterName);
        } catch (\InvalidArgumentException $e) {
            $this->sessionService->addFlashMessage('danger', $t('invalid_master', ['%master_name%' => htmlspecialchars($masterName)]));
            return $response->withHeader('Location', '/dashboard')->withStatus(302);
        }

        if ($model->update($id, $nombre, $descripcion)) {
            $this->sessionService->addFlashMessage('success', ucwords(str_replace('-', ' ', $masterName)) . ' ' . $t('updated_successfully')); // Traducir
            return $response->withHeader('Location', "/admin/masters/{$masterName}")->withStatus(302);
        } else {
            $this->sessionService->addFlashMessage('danger', $t('error_updating_master_item', ['%master_name%' => ucwords(str_replace('-', ' ', $masterName))])); // Traducir
            return $response->withHeader('Location', "/admin/masters/{$masterName}/edit/{$id}")->withStatus(302);
        }
    }

    /**
     * Procesa la eliminación de un elemento.
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     */
    public function processDelete(Request $request, Response $response, array $args): Response
    {
        $masterName = $args['master_name'];
        $id = (int)$args['id'];
        $t = $this->translator;

        $model = null;
        try {
            $model = $this->getMasterModel($masterName);
        } catch (\InvalidArgumentException $e) {
            $this->sessionService->addFlashMessage('danger', $t('invalid_master', ['%master_name%' => htmlspecialchars($masterName)]));
            return $response->withHeader('Location', '/dashboard')->withStatus(302);
        }

        try { // <-- ¡AÑADE ESTE BLOQUE TRY-CATCH!
            if ($model->delete($id)) {
                $this->sessionService->addFlashMessage('success', ucwords(str_replace('-', ' ', $masterName)) . ' ' . $t('deleted_successfully'));
            } else {
                // Esto debería no ocurrir si deleteUser relanza excepciones, pero es un fallback
                $this->sessionService->addFlashMessage('danger', $t('error_deleting_master_item_general') ?? 'Error al eliminar el elemento. Motivo desconocido.'); // Nueva clave
            }
        } catch (\PDOException $e) { // <-- ¡CAPTURA LA PDOEXCEPTION!
            $originalExceptionMessage = $e->getMessage();
            $originalExceptionCode = $e->getCode();

            $this->logger->error("PDOException en MasterController::processDelete: " . $originalExceptionMessage . " Code: " . $originalExceptionCode);

            // Código 23000 y mensaje 1451 para violación de clave foránea
            if ($originalExceptionCode == '23000' && str_contains($originalExceptionMessage, '1451')) {
                // Mensaje específico para FK
                $this->sessionService->addFlashMessage('danger', $t('master_item_has_associated_records', ['%master_name%' => ucwords(str_replace('-', ' ', $masterName))]) ?? 'No se puede eliminar %master_name%. Tiene registros asociados.'); // Nueva clave
            } else {
                // Otro tipo de error de DB
                $this->sessionService->addFlashMessage('danger', $t('database_error_deleting_master_item', ['%master_name%' => ucwords(str_replace('-', ' ', $masterName)), '%message%' => $originalExceptionMessage]) ?? 'Error de base de datos al eliminar el elemento: ' . $originalExceptionMessage); // Nueva clave
            }
        } catch (Exception $e) { // <-- Captura cualquier otra excepción general
            $this->logger->error("Excepción general en MasterController::processDelete: " . $e->getMessage() . " File: " . $e->getFile() . " Line: " . $e->getLine());
            $this->sessionService->addFlashMessage('danger', $t('unexpected_error_deleting_master_item') ?? 'Ocurrió un error inesperado al eliminar el elemento.'); // Nueva clave
        }
        
        return $response->withHeader('Location', "/admin/masters/{$masterName}")->withStatus(302);
    }
}
