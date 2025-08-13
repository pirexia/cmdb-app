<?php
// app/Controllers/ContractController.php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use League\Plates\Engine as PlatesEngine;
use App\Services\SessionService;
use Psr\Log\LoggerInterface;
use App\Models\Contract;        // El modelo que vamos a gestionar
use App\Models\ContractType;   // Para el SELECT de Tipos de Contrato
use App\Models\Provider;       // Para el SELECT de Proveedores
use Exception; // Para manejo de errores generales
use PDOException; // Asegúrate de que esta línea esté presente

class ContractController
{
    private PlatesEngine $view;
    private SessionService $sessionService;
    private LoggerInterface $logger;
    private Contract $contractModel;
    private ContractType $contractTypeModel; // Inyectar el modelo de Tipos de Contrato
    private Provider $providerModel;
    private $translator; // Propiedad para la función de traducción

    public function __construct(
        PlatesEngine $view,
        SessionService $sessionService,
        LoggerInterface $logger,
        Contract $contractModel,
        ContractType $contractTypeModel,
        Provider $providerModel,
        callable $translator
    ) {
        $this->view = $view;
        $this->sessionService = $sessionService;
        $this->logger = $logger;
        $this->contractModel = $contractModel;
        $this->contractTypeModel = $contractTypeModel;
        $this->providerModel = $providerModel;
        $this->translator = $translator;
    }

    /**
     * Muestra la lista de contratos.
     */
    public function listContracts(Request $request, Response $response): Response
    {
        $contracts = $this->contractModel->getAll();
        $t = $this->translator;
        if ($contracts === false) {
            $this->sessionService->addFlashMessage('danger', $t('error_loading_contracts'));
            $this->logger->error("Error al obtener todos los contratos.");
            $contracts = [];
        }

        $flashMessages = $this->sessionService->getFlashMessages();

        $html = $this->view->render('masters/contracts/list', [
            'pageTitle' => $t('administer_contracts'),
            'contracts' => $contracts,
            'flashMessages' => $flashMessages
        ]);

        $response->getBody()->write($html);
        return $response;
    }

    /**
     * Muestra el formulario para crear/editar un contrato.
     */
    public function showForm(Request $request, Response $response, array $args): Response
    {
        $contractId = $args['id'] ?? null;
        $contract = null;
        $t = $this->translator;

        if ($contractId) {
            $contract = $this->contractModel->getById((int)$contractId);
            if (!$contract) {
                $this->sessionService->addFlashMessage('danger', $t('contract_not_found'));
                return $response->withHeader('Location', '/admin/masters/contract')->withStatus(302);
            }
        }

        $contractTypes = $this->contractTypeModel->getAll() ?: [];
        $providers = $this->providerModel->getAll() ?: [];

        $flashMessages = $this->sessionService->getFlashMessages();

        $html = $this->view->render('masters/contracts/form', [
            'pageTitle' => ($contract ? $t('edit') : $t('create')) . ' ' . $t('contract'),
            'contract' => $contract,
            'contractTypes' => $contractTypes,
            'providers' => $providers,
            'flashMessages' => $flashMessages
        ]);

        $response->getBody()->write($html);
        return $response;
    }

    /**
     * Procesa la creación o actualización de un contrato.
     */
    public function processForm(Request $request, Response $response, array $args): Response
    {
        $contractId = $args['id'] ?? null;
        $data = $request->getParsedBody();
        $t = $this->translator;

        $contractData = [
            'numero_contrato' => trim($data['numero_contrato'] ?? ''),
            'id_tipo_contrato' => (int)($data['id_tipo_contrato'] ?? 0) ?: null,
            'id_proveedor'    => (int)($data['id_proveedor'] ?? 0) ?: null,
            'fecha_inicio'    => !empty($data['fecha_inicio']) ? $data['fecha_inicio'] : null,
            'fecha_fin'       => !empty($data['fecha_fin']) ? $data['fecha_fin'] : null,
            'costo_anual'     => !empty($data['costo_anual']) ? (float)$data['costo_anual'] : null,
            'descripcion'     => trim($data['descripcion'] ?? null),
        ];

        // Validación básica
        if (empty($contractData['numero_contrato']) || empty($contractData['id_tipo_contrato']) || empty($contractData['fecha_inicio']) || empty($contractData['fecha_fin'])) {
            $this->sessionService->addFlashMessage('danger', $t('contract_required_fields'));
            $redirectUrl = $contractId ? "/admin/masters/contract/edit/{$contractId}" : "/admin/masters/contract/create";
            return $response->withHeader('Location', $redirectUrl)->withStatus(302);
        }

        $success = false;
        $errorMessageFlash = $t('error_saving_contract'); // Mensaje por defecto

        try {
            if ($contractId) {
                $success = $this->contractModel->update((int)$contractId, $contractData);
            } else {
                $contractIdAfterSave = $this->contractModel->create($contractData);
                $success = ($contractIdAfterSave !== false);
            }

            if ($success) { // Si la operación principal de DB fue exitosa
                $this->sessionService->addFlashMessage('success', $t('contract_saved_successfully'));
                return $response->withHeader('Location', '/admin/masters/contract')->withStatus(302);
            } else {
                $this->sessionService->addFlashMessage('danger', $errorMessageFlash);
                $redirectUrl = $contractId ? "/admin/masters/contract/edit/{$contractId}" : "/admin/masters/contract/create";
                return $response->withHeader('Location', $redirectUrl)->withStatus(302);
            }
        } catch (PDOException $e) { // <-- Captura PDOException
            $this->logger->error("PDOException en ContractController::processForm: " . $e->getMessage() . " Code: " . $e->getCode());
            
            if ($e->getCode() == '23000' && str_contains($e->getMessage(), 'Duplicate entry')) {
                if (str_contains($e->getMessage(), 'numero_contrato')) {
                    $errorMessageFlash = $t('contract_number_exists');
                } else {
                    $errorMessageFlash = $t('duplicate_data_error');
                }
            } else {
                $errorMessageFlash = $t('database_error_saving_contract', ['%message%' => $e->getMessage()]);
            }
            $success = false;
            $this->sessionService->addFlashMessage('danger', $errorMessageFlash);
            $redirectUrl = $contractId ? "/admin/masters/contract/edit/{$contractId}" : "/admin/masters/contract/create";
            return $response->withHeader('Location', $redirectUrl)->withStatus(302);
        } catch (Exception $e) { // <-- Captura cualquier otra excepción
            $this->logger->error("Excepción general en ContractController::processForm: " . $e->getMessage() . " File: " . $e->getFile() . " Line: " . $e->getLine());
            $errorMessageFlash = $t('unexpected_error_saving_contract');
            $success = false;
            $this->sessionService->addFlashMessage('danger', $errorMessageFlash);
            $redirectUrl = $contractId ? "/admin/masters/contract/edit/{$contractId}" : "/admin/masters/contract/create";
            return $response->withHeader('Location', $redirectUrl)->withStatus(302);
        }
    }

    /**
     * Procesa la eliminación de un contrato.
     */
    public function processDelete(Request $request, Response $response, array $args): Response
    {
        $contractId = (int)$args['id'];
        $t = $this->translator;

        $contract = $this->contractModel->getById($contractId);
        if (!$contract) {
            $this->sessionService->addFlashMessage('danger', $t('contract_not_found_for_deletion'));
            return $response->withHeader('Location', '/admin/masters/contract')->withStatus(302);
        }

        try { // <-- ¡AÑADIDO TRY-CATCH PARA LA ELIMINACIÓN!
            if ($this->contractModel->delete($contractId)) {
                $this->sessionService->addFlashMessage('success', $t('contract_deleted_successfully'));
            } else {
                $this->sessionService->addFlashMessage('danger', $t('error_deleting_contract_general'));
            }
        } catch (PDOException $e) { // <-- Captura PDOException
            $originalExceptionMessage = $e->getMessage();
            $originalExceptionCode = $e->getCode();
            
            $this->logger->error("PDOException en ContractController::processDelete: " . $originalExceptionMessage . " Code: " . $originalExceptionCode);

            // Código 23000 y mensaje 1451 para violación de clave foránea
            if ($originalExceptionCode == '23000' && str_contains($originalExceptionMessage, '1451')) {
                $this->sessionService->addFlashMessage('danger', $t('contract_has_associated_assets') ?? 'No se puede eliminar el contrato. Tiene activos asociados.'); // Nueva clave
            } else {
                $this->sessionService->addFlashMessage('danger', $t('database_error_deleting_contract', ['%message%' => $originalExceptionMessage]) ?? 'Error de base de datos al eliminar el contrato: ' . $originalExceptionMessage); // Nueva clave
            }
        } catch (Exception $e) { // <-- Captura cualquier otra excepción general
            $this->logger->error("Excepción general en ContractController::processDelete: " . $e->getMessage() . " File: " . $e->getFile() . " Line: " . $e->getLine());
            $this->sessionService->addFlashMessage('danger', $t('unexpected_error_deleting_contract') ?? 'Ocurrió un error inesperado al eliminar el contrato.'); // Nueva clave
        }
        
        return $response->withHeader('Location', "/admin/masters/contract")->withStatus(302);
    }
}
