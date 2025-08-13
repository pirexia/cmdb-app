<?php
// app/Controllers/ModelController.php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use League\Plates\Engine as PlatesEngine;
use App\Services\SessionService;
use Psr\Log\LoggerInterface;
use App\Models\Model; // El modelo que vamos a gestionar
use App\Models\Manufacturer; // Para obtener la lista de fabricantes
use Exception; // Para manejar errores de subida de archivos
use PDOException; // Asegúrate de que esta línea esté presente

class ModelController
{
    private PlatesEngine $view;
    private SessionService $sessionService;
    private LoggerInterface $logger;
    private Model $modelModel; // Instancia del modelo de Modelos
    private Manufacturer $manufacturerModel; // Instancia del modelo de Fabricantes
    private array $config; // Para rutas de uploads
    private $translator; // Propiedad para guardar la función de traducción

    public function __construct(
        PlatesEngine $view,
        SessionService $sessionService,
        LoggerInterface $logger,
        Model $modelModel,
        Manufacturer $manufacturerModel,
        array $config,
        callable $translator
    ) {
        $this->view = $view;
        $this->sessionService = $sessionService;
        $this->logger = $logger;
        $this->modelModel = $modelModel;
        $this->manufacturerModel = $manufacturerModel;
        $this->config = $config;
        $this->translator = $translator;
    }

    /**
     * Muestra la lista de modelos.
     */
    public function listModels(Request $request, Response $response): Response
    {
        $models = $this->modelModel->getAll();
        $t = $this->translator;
        if ($models === false) {
            $this->sessionService->addFlashMessage('danger', $t('error_loading_models'));
            $this->logger->error("Error al obtener todos los modelos.");
            $models = [];
        }

        $flashMessages = $this->sessionService->getFlashMessages();

        $html = $this->view->render('masters/models/list', [
            'pageTitle' => $t('administer_models'),
            'models' => $models,
            'flashMessages' => $flashMessages
        ]);

        $response->getBody()->write($html);
        return $response;
    }

    /**
     * Muestra el formulario para crear/editar un modelo.
     */
    public function showForm(Request $request, Response $response, array $args): Response
    {
        $modelId = $args['id'] ?? null;
        $model = null;
        $t = $this->translator;

        if ($modelId) {
            $model = $this->modelModel->getById((int)$modelId);
            if (!$model) {
                $this->sessionService->addFlashMessage('danger', $t('model_not_found'));
                return $response->withHeader('Location', '/admin/masters/model')->withStatus(302);
            }
        }

        $manufacturers = $this->manufacturerModel->getAll();
        if ($manufacturers === false) {
            $this->sessionService->addFlashMessage('danger', $t('error_loading_manufacturers_models'));
            $this->logger->error("Error al obtener fabricantes para el formulario de modelos.");
            $manufacturers = [];
        }

        $flashMessages = $this->sessionService->getFlashMessages();

        $html = $this->view->render('masters/models/form', [
            'pageTitle' => ($model ? $t('edit') : $t('create')) . ' ' . $t('model'),
            'model' => $model,
            'manufacturers' => $manufacturers,
            'flashMessages' => $flashMessages
        ]);

        $response->getBody()->write($html);
        return $response;
    }

    /**
     * Procesa la creación o actualización de un modelo.
     */
    public function processForm(Request $request, Response $response, array $args): Response
    {
        $modelId = $args['id'] ?? null;
        $data = $request->getParsedBody();
        $t = $this->translator;

        $fabricanteId = (int)($data['id_fabricante'] ?? 0);
        $nombre = trim($data['nombre'] ?? '');
        $descripcion = trim($data['descripcion'] ?? null);
        $imagenMasterRuta = trim($data['current_image_path'] ?? null);

        // Validación básica
        if (empty($fabricanteId) || empty($nombre)) {
            $this->sessionService->addFlashMessage('danger', $t('manufacturer_and_name_required'));
            $redirectUrl = $modelId ? "/admin/masters/model/edit/{$modelId}" : "/admin/masters/model/create";
            return $response->withHeader('Location', $redirectUrl)->withStatus(302);
        }

        // Manejo de la subida de imagen
        $uploadedFiles = $request->getUploadedFiles();
        $newImageFile = $uploadedFiles['imagen_master'] ?? null;

        if ($newImageFile && $newImageFile->getError() === UPLOAD_ERR_OK) {
            $uploadDir = $this->config['paths']['uploads'];
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0775, true);
            }

            $filename = uniqid('model_') . '.' . pathinfo($newImageFile->getClientFilename(), PATHINFO_EXTENSION);
            $newImagePath = rtrim($uploadDir, '/') . '/' . $filename;

            try {
                $newImageFile->moveTo($newImagePath);
                $imagenMasterRuta = '/uploads/' . $filename;
                // Eliminar imagen antigua si existe y se subió una nueva
                if ($modelId && $model = $this->modelModel->getById((int)$modelId) && $model['imagen_master_ruta']) {
                    $oldImagePath = $this->config['paths']['uploads'] . ltrim($model['imagen_master_ruta'], '/uploads');
                    if (file_exists($oldImagePath)) {
                        unlink($oldImagePath);
                        $this->logger->info($t('model_old_image_deleted', ['%id%' => $model['id'], '%path%' => $oldImagePath]));
                    }
                }
            } catch (Exception $e) {
                $this->sessionService->addFlashMessage('danger', $t('error_uploading_image', ['%message%' => $e->getMessage()]));
                $this->logger->error("Error al subir imagen de modelo: " . $e->getMessage());
                $redirectUrl = $modelId ? "/admin/masters/model/edit/{$modelId}" : "/admin/masters/model/create";
                return $response->withHeader('Location', $redirectUrl)->withStatus(302);
            }
        }

        $success = false;
        $errorMessageFlash = $t('error_saving_model_generic'); // Mensaje por defecto

        try {
            if ($modelId) {
                $success = $this->modelModel->update((int)$modelId, $fabricanteId, $nombre, $descripcion, $imagenMasterRuta);
            } else {
                $success = $this->modelModel->create($fabricanteId, $nombre, $descripcion, $imagenMasterRuta);
            }
        } catch (PDOException $e) { // <-- Captura PDOException
            $this->logger->error("PDOException en ModelController::processForm: " . $e->getMessage() . " Code: " . $e->getCode());
            
            if ($e->getCode() == '23000' && str_contains($e->getMessage(), 'Duplicate entry')) {
                $errorMessageFlash = $t('model_name_exists_for_manufacturer');
            } else {
                $errorMessageFlash = $t('database_error_saving_model', ['%message%' => $e->getMessage()]);
            }
            $success = false;
        } catch (Exception $e) { // <-- Captura cualquier otra excepción
            $this->logger->error("Excepción general en ModelController::processForm: " . $e->getMessage() . " File: " . $e->getFile() . " Line: " . $e->getLine());
            $errorMessageFlash = $t('unexpected_error_saving_model');
            $success = false;
        }

        if ($success) {
            $this->sessionService->addFlashMessage('success', $t('model_saved_successfully'));
            return $response->withHeader('Location', '/admin/masters/model')->withStatus(302);
        } else {
            $this->sessionService->addFlashMessage('danger', $errorMessageFlash);
            $redirectUrl = $modelId ? "/admin/masters/model/edit/{$modelId}" : "/admin/masters/model/create";
            return $response->withHeader('Location', $redirectUrl)->withStatus(302);
        }
    }

    /**
     * Procesa la eliminación de un modelo.
     */
    public function processDelete(Request $request, Response $response, array $args): Response
    {
        $modelId = (int)$args['id'];
        $t = $this->translator;

        $model = $this->modelModel->getById($modelId);
        if (!$model) {
            $this->sessionService->addFlashMessage('danger', $t('model_not_found_for_deletion'));
            return $response->withHeader('Location', '/admin/masters/model')->withStatus(302);
        }

        // Eliminar archivo de imagen asociado si existe
        if ($model['imagen_master_ruta']) {
            $imagePath = $this->config['paths']['uploads'] . ltrim($model['imagen_master_ruta'], '/uploads');
            if (file_exists($imagePath)) {
                try {
                    unlink($imagePath);
                    $this->logger->info($t('model_image_deleted', ['%path%' => $imagePath]));
                } catch (Exception $e) {
                    $this->logger->error($t('error_deleting_model_image', ['%id%' => $modelId, '%message%' => $e->getMessage()]));
                }
            }
        }

        try { // <-- ¡AÑADIDO TRY-CATCH PARA LA ELIMINACIÓN!
            if ($this->modelModel->delete($modelId)) {
                $this->sessionService->addFlashMessage('success', $t('model_deleted_successfully'));
            } else {
                $this->sessionService->addFlashMessage('danger', $t('error_deleting_model_general') ?? 'Error al eliminar el modelo. Motivo desconocido.'); // Nueva clave genérica
            }
        } catch (PDOException $e) { // <-- Captura PDOException
            $originalExceptionMessage = $e->getMessage();
            $originalExceptionCode = $e->getCode();
            
            $this->logger->error("PDOException en ModelController::processDelete: " . $originalExceptionMessage . " Code: " . $originalExceptionCode);

            // Código 23000 y mensaje 1451 para violación de clave foránea
            if ($originalExceptionCode == '23000' && str_contains($originalExceptionMessage, '1451')) {
                $this->sessionService->addFlashMessage('danger', $t('model_has_associated_assets') ?? 'No se puede eliminar el modelo. Tiene activos asociados.'); // Nueva clave
            } else {
                $this->sessionService->addFlashMessage('danger', $t('database_error_deleting_model', ['%id%' => $modelId, '%message%' => $originalExceptionMessage]) ?? 'Error de base de datos al eliminar el modelo: ' . $originalExceptionMessage); // Nueva clave
            }
        } catch (Exception $e) { // <-- Captura cualquier otra excepción general
            $this->logger->error("Excepción general en ModelController::processDelete: " . $e->getMessage() . " File: " . $e->getFile() . " Line: " . $e->getLine());
            $this->sessionService->addFlashMessage('danger', $t('unexpected_error_deleting_model') ?? 'Ocurrió un error inesperado al eliminar el modelo.'); // Nueva clave
        }
        
        return $response->withHeader('Location', "/admin/masters/model")->withStatus(302);
    }
}
