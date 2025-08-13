<?php
// app/Controllers/SourceController.php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use League\Plates\Engine as PlatesEngine;
use App\Services\SessionService;
use Psr\Log\LoggerInterface;
use App\Models\Source; // El modelo para fuentes de usuario
use Exception; // Para manejar errores generales
use PDOException; // Para errores de base de datos

class SourceController
{
    private PlatesEngine $view;
    private SessionService $sessionService;
    private LoggerInterface $logger;
    private Source $sourceModel;
    private $translator; // Para la función t()
    // private LdapService $ldapService; // Lo inyectaremos cuando lo creemos para el test de conexión

    public function __construct(
        PlatesEngine $view,
        SessionService $sessionService,
        LoggerInterface $logger,
        Source $sourceModel,
        callable $translator
        // LdapService $ldapService // Se inyectará cuando se cree
    ) {
        $this->view = $view;
        $this->sessionService = $sessionService;
        $this->logger = $logger;
        $this->sourceModel = $sourceModel;
        $this->translator = $translator;
        // $this->ldapService = $ldapService;
    }

    /**
     * Muestra la lista de fuentes de usuario.
     */
    public function listSources(Request $request, Response $response): Response
    {
        $sources = $this->sourceModel->getAll();
        $t = $this->translator;

        if ($sources === false) {
            $this->sessionService->addFlashMessage('danger', $t('error_loading_sources') ?? 'Error al cargar las fuentes de usuario.');
            $this->logger->error("Error al obtener todas las fuentes de usuario.");
            $sources = [];
        }

        $flashMessages = $this->sessionService->getFlashMessages();

        $html = $this->view->render('admin/sources/list', [ // Nueva vista
            'pageTitle' => $t('user_sources_management') ?? 'Gestión de Fuentes de Usuario',
            'sources' => $sources,
            'flashMessages' => $flashMessages
        ]);

        $response->getBody()->write($html);
        return $response;
    }

    /**
     * Muestra el formulario para crear/editar una fuente de usuario.
     */
    public function showForm(Request $request, Response $response, array $args): Response
    {
        $sourceId = $args['id'] ?? null;
        $source = null;
        $t = $this->translator;

        if ($sourceId) {
            $source = $this->sourceModel->getById((int)$sourceId);
            if (!$source) {
                $this->sessionService->addFlashMessage('danger', $t('source_not_found') ?? 'Fuente de usuario no encontrada.');
                return $response->withHeader('Location', '/admin/sources')->withStatus(302);
            }
        }

        // Tipos de fuente disponibles para el select
        $sourceTypes = [
            'local' => $t('source_type_local'),
            'ldap' => 'LDAP',
            'activedirectory' => 'Active Directory'
        ];

        $flashMessages = $this->sessionService->getFlashMessages();

        $html = $this->view->render('admin/sources/form', [ // Nueva vista
            'pageTitle' => ($source ? $t('edit') : $t('create')) . ' ' . $t('user_source'),
            'source' => $source,
            'sourceTypes' => $sourceTypes,
            'flashMessages' => $flashMessages
        ]);

        $response->getBody()->write($html);
        return $response;
    }

    /**
     * Procesa la creación o actualización de una fuente de usuario.
     */
    public function processForm(Request $request, Response $response, array $args): Response
    {
        $sourceId = $args['id'] ?? null;
        $data = $request->getParsedBody();
        $t = $this->translator;

        $sourceData = [
            'nombre_friendly' => trim($data['nombre_friendly'] ?? ''),
            'tipo_fuente'     => trim($data['tipo_fuente'] ?? ''),
            'host'            => trim($data['host'] ?? null),
            'port'            => (int)($data['port'] ?? 0) ?: null,
            'base_dn'         => trim($data['base_dn'] ?? null),
            'bind_dn'         => trim($data['bind_dn'] ?? null),
            'bind_password'   => $data['bind_password'] ?? null, // No trim() si la contraseña puede tener espacios iniciales/finales
            'user_filter'     => trim($data['user_filter'] ?? null),
            'group_filter'    => trim($data['group_filter'] ?? null),
            'use_tls'         => isset($data['use_tls']) && $data['use_tls'] == '1',
            'use_ssl'         => isset($data['use_ssl']) && $data['use_ssl'] == '1',
            'ca_cert_path'    => trim($data['ca_cert_path'] ?? null),
            'timeout'         => (int)($data['timeout'] ?? 0) ?: null,
            'activo'          => isset($data['activo']) && $data['activo'] == '1',
        ];

        // Validación básica
        if (empty($sourceData['nombre_friendly']) || empty($sourceData['tipo_fuente'])) {
            $this->sessionService->addFlashMessage('danger', $t('source_name_type_required') ?? 'Nombre amigable y Tipo de fuente son obligatorios.');
            $redirectUrl = $sourceId ? "/admin/sources/edit/{$sourceId}" : "/admin/sources/create";
            return $response->withHeader('Location', $redirectUrl)->withStatus(302);
        }

        // Validaciones específicas para LDAP/AD
        if (in_array($sourceData['tipo_fuente'], ['ldap', 'activedirectory'])) {
            if (empty($sourceData['host']) || empty($sourceData['base_dn']) || empty($sourceData['user_filter'])) {
                $this->sessionService->addFlashMessage('danger', $t('ldap_ad_required_fields') ?? 'Para LDAP/AD: Host, Base DN y Filtro de usuario son obligatorios.');
                $redirectUrl = $sourceId ? "/admin/sources/edit/{$sourceId}" : "/admin/sources/create";
                return $response->withHeader('Location', $redirectUrl)->withStatus(302);
            }
        }
        
        $success = false;
        $errorMessageFlash = $t('error_saving_source'); // Mensaje por defecto

        try {
            if ($sourceId) {
                // Si la contraseña no se envía en la actualización, no cambiarla en la DB
                if (empty($sourceData['bind_password'])) {
                    unset($sourceData['bind_password']); // No actualizar password si está vacío
                }
                $success = $this->sourceModel->update((int)$sourceId, $sourceData);
            } else {
                $newSourceId = $this->sourceModel->create($sourceData);
                $success = ($newSourceId !== false);
            }

            if ($success) {
                $this->sessionService->addFlashMessage('success', $t('source_saved_successfully'));
                return $response->withHeader('Location', '/admin/sources')->withStatus(302);
            } else {
                $this->sessionService->addFlashMessage('danger', $errorMessageFlash);
                $redirectUrl = $sourceId ? "/admin/sources/edit/{$sourceId}" : "/admin/sources/create";
                return $response->withHeader('Location', $redirectUrl)->withStatus(302);
            }

        } catch (PDOException $e) {
            $this->logger->error("PDOException en SourceController::processForm: " . $e->getMessage() . " Code: " . $e->getCode());
            
            if ($e->getCode() == '23000' && str_contains($e->getMessage(), 'Duplicate entry')) {
                $errorMessageFlash = $t('source_name_already_exists') ?? 'Ya existe una fuente con ese nombre amigable.';
            } else {
                $errorMessageFlash = $t('database_error_saving_source', ['%message%' => $e->getMessage()]) ?? 'Error de base de datos al guardar la fuente: ' . $e->getMessage();
            }
            $success = false;
            $this->sessionService->addFlashMessage('danger', $errorMessageFlash);
            $redirectUrl = $sourceId ? "/admin/sources/edit/{$sourceId}" : "/admin/sources/create";
            return $response->withHeader('Location', $redirectUrl)->withStatus(302);

        } catch (Exception $e) {
            $this->logger->error("Excepción general en SourceController::processForm: " . $e->getMessage() . " File: " . $e->getFile() . " Line: " . $e->getLine());
            $errorMessageFlash = $t('unexpected_error_saving_source') ?? 'Ocurrió un error inesperado al guardar la fuente.';
            $success = false;
            $this->sessionService->addFlashMessage('danger', $errorMessageFlash);
            $redirectUrl = $sourceId ? "/admin/sources/edit/{$sourceId}" : "/admin/sources/create";
            return $response->withHeader('Location', $redirectUrl)->withStatus(302);
        }
    }

    /**
     * Procesa la eliminación de una fuente de usuario.
     */
    public function processDelete(Request $request, Response $response, array $args): Response
    {
        $sourceId = (int)$args['id'];
        $t = $this->translator;

        $source = $this->sourceModel->getById($sourceId);
        if (!$source) {
            $this->sessionService->addFlashMessage('danger', $t('source_not_found_for_deletion') ?? 'Fuente de usuario no encontrada para eliminar.');
            return $response->withHeader('Location', '/admin/sources')->withStatus(302);
        }

        // No permitir eliminar la fuente "Local" por defecto
        if ($source['tipo_fuente'] === 'local') {
            $this->sessionService->addFlashMessage('danger', $t('cannot_delete_local_source') ?? 'No se puede eliminar la fuente de usuario local por defecto.');
            return $response->withHeader('Location', '/admin/sources')->withStatus(302);
        }

        try {
            if ($this->sourceModel->delete($sourceId)) {
                $this->sessionService->addFlashMessage('success', $t('source_deleted_successfully'));
            } else {
                $this->sessionService->addFlashMessage('danger', $t('error_deleting_source_general') ?? 'Error al eliminar la fuente de usuario. Motivo desconocido.');
            }
        } catch (PDOException $e) {
            $originalExceptionMessage = $e->getMessage();
            $originalExceptionCode = $e->getCode();
            
            $this->logger->error("PDOException en SourceController::processDelete: " . $originalExceptionMessage . " Code: " . $originalExceptionCode);

            if ($originalExceptionCode == '23000' && str_contains($originalExceptionMessage, '1451')) { // 1451 para FK
                $this->sessionService->addFlashMessage('danger', $t('source_has_associated_users') ?? 'No se puede eliminar la fuente. Tiene usuarios asociados.');
            } else {
                $this->sessionService->addFlashMessage('danger', $t('database_error_deleting_source', ['%message%' => $originalExceptionMessage]) ?? 'Error de base de datos al eliminar la fuente: ' . $originalExceptionMessage);
            }
        } catch (Exception $e) {
            $this->logger->error("Excepción general en SourceController::processDelete: " . $e->getMessage() . " File: " . $e->getFile() . " Line: " . $e->getLine());
            $this->sessionService->addFlashMessage('danger', $t('unexpected_error_deleting_source') ?? 'Ocurrió un error inesperado al eliminar la fuente.');
        }
        
        return $response->withHeader('Location', "/admin/sources")->withStatus(302);
    }
}
