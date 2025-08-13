<?php
// app/Controllers/CustomFieldsController.php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use League\Plates\Engine as PlatesEngine;
use App\Services\SessionService;
use Psr\Log\LoggerInterface;
use App\Models\CustomFieldDefinition; // El modelo para definiciones
use App\Models\AssetType; // Para obtener la lista de tipos de activos
use Exception; // Para manejar errores generales
use PDOException; // Asegúrate de que esta línea esté presente

class CustomFieldsController
{
    private PlatesEngine $view;
    private SessionService $sessionService;
    private LoggerInterface $logger;
    private CustomFieldDefinition $customFieldDefinitionModel;
    private AssetType $assettypeModel;
    private $translator;

    public function __construct(
        PlatesEngine $view,
        SessionService $sessionService,
        LoggerInterface $logger,
        CustomFieldDefinition $customFieldDefinitionModel,
        AssetType $assettypeModel,
        callable $translator
    ) {
        $this->view = $view;
        $this->sessionService = $sessionService;
        $this->logger = $logger;
        $this->customFieldDefinitionModel = $customFieldDefinitionModel;
        $this->assettypeModel = $assettypeModel;
        $this->translator = $translator;
    }

    /**
     * Muestra la lista de definiciones de campos personalizados.
     */
    public function listDefinitions(Request $request, Response $response): Response
    {
        $definitions = $this->customFieldDefinitionModel->getAll();
        $t = $this->translator;
        if ($definitions === false) {
            $this->sessionService->addFlashMessage('danger', $t('error_loading_custom_fields_definitions'));
            $this->logger->error("Error al obtener todas las definiciones de campos personalizados.");
            $definitions = [];
        }

        $flashMessages = $this->sessionService->getFlashMessages();

        $html = $this->view->render('admin/custom_fields/list', [
            'pageTitle' => $t('manage_custom_fields'),
            'definitions' => $definitions,
            'flashMessages' => $flashMessages
        ]);

        $response->getBody()->write($html);
        return $response;
    }

    /**
     * Muestra el formulario para crear/editar una definición de campo personalizado.
     */
    public function showForm(Request $request, Response $response, array $args): Response
    {
        $definitionId = $args['id'] ?? null;
        $definition = null;
        $t = $this->translator;

        if ($definitionId) {
            $definition = $this->customFieldDefinitionModel->getById((int)$definitionId);
            if (!$definition) {
                $this->sessionService->addFlashMessage('danger', $t('custom_field_not_found'));
                return $response->withHeader('Location', '/admin/custom-fields')->withStatus(302);
            }
        }

        $assetTypes = $this->assettypeModel->getAll() ?: [];

        $flashMessages = $this->sessionService->getFlashMessages();

        $html = $this->view->render('admin/custom_fields/form', [
            'pageTitle' => ($definition ? $t('edit') : $t('create')) . ' ' . $t('custom_field'),
            'definition' => $definition,
            'assetTypes' => $assetTypes,
            'flashMessages' => $flashMessages
        ]);

        $response->getBody()->write($html);
        return $response;
    }

    /**
     * Procesa la creación o actualización de una definición de campo personalizado.
     */
    public function processForm(Request $request, Response $response, array $args): Response
    {
        $definitionId = $args['id'] ?? null;
        $data = $request->getParsedBody();
        $t = $this->translator;

        $fieldData = [
            'id_tipo_activo' => (int)($data['id_tipo_activo'] ?? 0) ?: null,
            'nombre_campo'   => trim($data['nombre_campo'] ?? ''),
            'tipo_dato'      => trim($data['tipo_dato'] ?? ''),
            'es_requerido'   => isset($data['es_requerido']) && $data['es_requerido'] == '1',
            'opciones_lista' => trim($data['opciones_lista'] ?? null),
            'unidad'         => trim($data['unidad'] ?? null),
            'descripcion'    => trim($data['descripcion'] ?? null),
        ];

        // Validación básica
        if (empty($fieldData['id_tipo_activo']) || empty($fieldData['nombre_campo']) || empty($fieldData['tipo_dato'])) {
            $this->sessionService->addFlashMessage('danger', $t('asset_type_field_name_data_type_required'));
            $redirectUrl = $definitionId ? "/admin/custom-fields/edit/{$definitionId}" : "/admin/custom-fields/create";
            return $response->withHeader('Location', $redirectUrl)->withStatus(302);
        }

        // Validar opciones_lista si el tipo de dato es 'lista'
        if ($fieldData['tipo_dato'] === 'lista' && empty($fieldData['opciones_lista'])) {
            $this->sessionService->addFlashMessage('danger', $t('list_options_required'));
            $redirectUrl = $definitionId ? "/admin/custom-fields/edit/{$definitionId}" : "/admin/custom-fields/create";
            return $response->withHeader('Location', $redirectUrl)->withStatus(302);
        }

        // Validación para unidad: solo si el tipo de dato es 'numero'
        if ($fieldData['tipo_dato'] === 'numero' && empty($fieldData['unidad'])) {
            $this->sessionService->addFlashMessage('danger', $t('unit_required_for_numeric_fields'));
            $redirectUrl = $definitionId ? "/admin/custom-fields/edit/{$definitionId}" : "/admin/custom-fields/create";
            return $response->withHeader('Location', $redirectUrl)->withStatus(302);
        }

        $success = false;
        $errorMessageFlash = $t('error_saving_custom_field'); // Mensaje por defecto

        try {
            if ($definitionId) {
                $success = $this->customFieldDefinitionModel->update((int)$definitionId, $fieldData);
            } else {
                $fieldIdAfterSave = $this->customFieldDefinitionModel->create($fieldData);
                $success = ($fieldIdAfterSave !== false);
            }

            if ($success) { // Si la operación principal de DB fue exitosa
                $this->sessionService->addFlashMessage('success', $t('custom_field_saved_successfully'));
                return $response->withHeader('Location', '/admin/custom-fields')->withStatus(302);
            } else {
                $this->sessionService->addFlashMessage('danger', $errorMessageFlash);
                $redirectUrl = $definitionId ? "/admin/custom-fields/edit/{$definitionId}" : "/admin/custom-fields/create";
                return $response->withHeader('Location', $redirectUrl)->withStatus(302);
            }

        } catch (PDOException $e) { // <-- Captura PDOException
            $this->logger->error("PDOException en CustomFieldsController::processForm: " . $e->getMessage() . " Code: " . $e->getCode());
            
            if ($e->getCode() == '23000' && str_contains($e->getMessage(), 'Duplicate entry')) {
                $errorMessageFlash = $t('custom_field_exists_for_type');
            } else {
                $errorMessageFlash = $t('database_error_saving_custom_field', ['%message%' => $e->getMessage()]);
            }
            $success = false;
            $this->sessionService->addFlashMessage('danger', $errorMessageFlash);
            $redirectUrl = $definitionId ? "/admin/custom-fields/edit/{$definitionId}" : "/admin/custom-fields/create";
            return $response->withHeader('Location', $redirectUrl)->withStatus(302);

        } catch (Exception $e) { // <-- Captura cualquier otra excepción
            $this->logger->error("Excepción general en CustomFieldsController::processForm: " . $e->getMessage() . " File: " . $e->getFile() . " Line: " . $e->getLine());
            $errorMessageFlash = $t('unexpected_error_saving_custom_field');
            $success = false;
            $this->sessionService->addFlashMessage('danger', $errorMessageFlash);
            $redirectUrl = $definitionId ? "/admin/custom-fields/edit/{$definitionId}" : "/admin/custom-fields/create";
            return $response->withHeader('Location', $redirectUrl)->withStatus(302);
        }
    }

    /**
     * Procesa la eliminación de una definición de campo personalizado.
     */
    public function processDelete(Request $request, Response $response, array $args): Response
    {
        $definitionId = (int)$args['id'];
        $t = $this->translator;
        
        $definition = $this->customFieldDefinitionModel->getById($definitionId);
        if (!$definition) {
            $this->sessionService->addFlashMessage('danger', $t('custom_field_not_found_for_deletion'));
            return $response->withHeader('Location', '/admin/custom-fields')->withStatus(302);
        }

        try { // <-- ¡AÑADIDO TRY-CATCH PARA LA ELIMINACIÓN!
            if ($this->customFieldDefinitionModel->delete($definitionId)) {
                $this->sessionService->addFlashMessage('success', $t('custom_field_deleted_successfully'));
            } else {
                $this->sessionService->addFlashMessage('danger', $t('error_deleting_custom_field_general') ?? 'Error al eliminar la definición del campo. Motivo desconocido.');
            }
        } catch (PDOException $e) { // <-- Captura PDOException
            $originalExceptionMessage = $e->getMessage();
            $originalExceptionCode = $e->getCode();
            
            $this->logger->error("PDOException en CustomFieldsController::processDelete: " . $originalExceptionMessage . " Code: " . $originalExceptionCode);

            // Código 23000 y mensaje 1451 para violación de clave foránea
            if ($originalExceptionCode == '23000' && str_contains($originalExceptionMessage, '1451')) {
                $this->sessionService->addFlashMessage('danger', $t('custom_field_def_has_associated_values') ?? 'No se puede eliminar la definición de campo. Tiene valores de activos asociados.'); // Nueva clave
            } else {
                $this->sessionService->addFlashMessage('danger', $t('database_error_deleting_custom_field_def', ['%message%' => $originalExceptionMessage]) ?? 'Error de base de datos al eliminar la definición de campo: ' . $originalExceptionMessage); // Nueva clave
            }
        } catch (Exception $e) { // <-- Captura cualquier otra excepción general
            $this->logger->error("Excepción general en CustomFieldsController::processDelete: " . $e->getMessage() . " File: " . $e->getFile() . " Line: " . $e->getLine());
            $this->sessionService->addFlashMessage('danger', $t('unexpected_error_deleting_custom_field_def') ?? 'Ocurrió un error inesperado al eliminar la definición de campo.'); // Nueva clave
        }
        
        return $response->withHeader('Location', '/admin/custom-fields')->withStatus(302);
    }
}
