<?php
// app/Controllers/UserController.php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use League\Plates\Engine as PlatesEngine;
use App\Services\SessionService;
use Psr\Log\LoggerInterface;
use App\Models\User; // El modelo para usuarios
use App\Models\Role; // El modelo para roles
use App\Models\Source; // <--- ¡NUEVA IMPORTACIÓN! Para las fuentes de usuario
use App\Services\MailService; // <-- ¡NUEVA IMPORTACIÓN!
use Exception; // Para manejo de errores
use PDOException;

class UserController
{
    private PlatesEngine $view;
    private SessionService $sessionService;
    private LoggerInterface $logger;
    private User $userModel;
    private Role $roleModel;
    private Source $sourceModel; // <--- ¡NUEVA PROPIEDAD! Para las fuentes de usuario
    private MailService $mailService; // <-- ¡NUEVA PROPIEDAD!
    private $translator; // Para la función t()

    public function __construct(
        PlatesEngine $view,
        SessionService $sessionService,
        LoggerInterface $logger,
        User $userModel,
        Role $roleModel,
        callable $translator,
        Source $sourceModel, // <--- ¡NUEVO ARGUMENTO!
        MailService $mailService // <-- ¡NUEVO ARGUMENTO!
    ) {
        $this->view = $view;
        $this->sessionService = $sessionService;
        $this->logger = $logger;
        $this->userModel = $userModel;
        $this->roleModel = $roleModel;
        $this->translator = $translator;
        $this->sourceModel = $sourceModel; // <--- ASIGNACIÓN
        $this->mailService = $mailService; // <-- ASIGNACIÓN
    }

    /**
     * Muestra la lista de usuarios.
     */
    public function listUsers(Request $request, Response $response): Response
    {
        $users = $this->userModel->getAllUsers();
        $t = $this->translator;

        if ($users === false) {
            $this->sessionService->addFlashMessage('danger', $t('error_loading_users'));
            $this->logger->error("Error al obtener todos los usuarios.");
            $users = [];
        }

        $flashMessages = $this->sessionService->getFlashMessages();

        $html = $this->view->render('admin/users/list', [
            'pageTitle' => $t('user_administration'),
            'users' => $users,
            'flashMessages' => $flashMessages
        ]);

        $response->getBody()->write($html);
        return $response;
    }

    /**
     * Muestra el formulario para crear/editar un usuario.
     */
    public function showForm(Request $request, Response $response, array $args): Response
    {
        $userId = $args['id'] ?? null;
        $user = null;
        $t = $this->translator;

        if ($userId) {
            $user = $this->userModel->getUserById((int)$userId);
            if (!$user) {
                $this->sessionService->addFlashMessage('danger', $t('user_not_found'));
                return $response->withHeader('Location', '/admin/users')->withStatus(302);
            }
        }

        $roles = $this->roleModel->getAllRoles() ?: [];
        if ($roles === false) {
            $this->sessionService->addFlashMessage('danger', $t('error_loading_roles'));
            $this->logger->error("Error al obtener roles para el formulario de usuarios.");
            $roles = [];
        }
        
        $sources = $this->sourceModel->getAll(false) ?: []; // Obtener todas las fuentes de usuario (activas e inactivas para edición)
        if ($sources === false) {
            $this->sessionService->addFlashMessage('danger', $t('error_loading_user_sources') ?? 'Error al cargar las fuentes de usuario.'); // Nueva clave
            $this->logger->error("Error al obtener fuentes de usuario para el formulario de usuarios.");
            $sources = [];
        }


        $flashMessages = $this->sessionService->getFlashMessages();

        $html = $this->view->render('admin/users/form', [
            'pageTitle' => ($user ? $t('edit') : $t('create')) . ' ' . $t('user'),
            'user' => $user,
            'roles' => $roles,
            'sources' => $sources, // <-- Pasa las fuentes de usuario a la vista
            'flashMessages' => $flashMessages
        ]);

        $response->getBody()->write($html);
        return $response;
    }

    /**
     * Procesa la creación o actualización de un usuario.
     */
    public function processForm(Request $request, Response $response, array $args): Response
    {
        $userId = $args['id'] ?? null;
        $data = $request->getParsedBody();
        $t = $this->translator;

        $username = trim($data['username'] ?? '');
        $email = trim($data['email'] ?? '');
        $password = $data['password'] ?? '';
        $confirmPassword = $data['confirm_password'] ?? '';
        $roleId = (int)($data['id_rol'] ?? 0);
        $activo = isset($data['activo']) && $data['activo'] == '1';
        $sourceId = (int)($data['id_fuente_usuario'] ?? 0); // <--- ¡NUEVO! ID de la fuente de usuario
        $sourceName = ''; // <--- ¡NUEVO! Nombre amigable de la fuente

        // Obtener los detalles de la fuente para el nombre amigable
        $source = $this->sourceModel->getById($sourceId);
        if ($source) {
            $sourceName = $source['nombre_friendly'];
        } else {
            $this->sessionService->addFlashMessage('danger', $t('invalid_user_source') ?? 'Fuente de usuario no válida seleccionada.'); // Nueva clave
            $redirectUrl = $userId ? "/admin/users/edit/{$userId}" : "/admin/users/create";
            return $response->withHeader('Location', $redirectUrl)->withStatus(302);
        }

        // --- Validación de Entrada Básica ---
        // Username, rol, fuente son siempre obligatorios.
        // Email solo es obligatorio si la fuente es 'local'.
        if (empty($username) || empty($roleId) || empty($sourceId) || ($source['tipo_fuente'] === 'local' && empty($email))) { // <-- ¡CORRECCIÓN AQUÍ!
            $this->sessionService->addFlashMessage('danger', $t('username_role_source_email_required_local'));
            $redirectUrl = $userId ? "/admin/users/edit/{$userId}" : "/admin/users/create";
            return $response->withHeader('Location', $redirectUrl)->withStatus(302);
        }

        // Validar el FORMATO del email SÓLO si se proporcionó un email (no es null ni vacío)
        if (!is_null($email) && $email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->sessionService->addFlashMessage('danger', $t('invalid_email_format'));
            $redirectUrl = $userId ? "/admin/users/edit/{$userId}" : "/admin/users/create";
            return $response->withHeader('Location', $redirectUrl)->withStatus(302);
        }

        // --- Validación de Contraseña (CONDICIONAL POR TIPO DE FUENTE) ---
        // La contraseña solo es requerida y gestionada si la fuente es 'local'
        if ($source['tipo_fuente'] === 'local') {
            // Contraseña es obligatoria solo en creación de usuario LOCAL
            if (empty($password) && !$userId) { // Si es nuevo usuario (no edición)
                $this->sessionService->addFlashMessage('danger', $t('password_required_for_local_user'));
                $redirectUrl = $userId ? "/admin/users/edit/{$userId}" : "/admin/users/create";
                return $response->withHeader('Location', $redirectUrl)->withStatus(302);
            }
            // Validación de coincidencia y complejidad si se ha proporcionado una contraseña
            if (!empty($password) || !empty($confirmPassword)) { // Si se rellenó alguno de los campos de pass
                if ($password !== $confirmPassword) {
                    $this->sessionService->addFlashMessage('danger', $t('passwords_do_not_match'));
                    $redirectUrl = $userId ? "/admin/users/edit/{$userId}" : "/admin/users/create";
                    return $response->withHeader('Location', $redirectUrl)->withStatus(302);
                }
                if (strlen($password) < 8 || !preg_match('/[A-Z]/', $password) || !preg_match('/[a-z]/', $password) || !preg_match('/[0-9]/', $password) || !preg_match('/[^A-Za-z0-9]/', $password)) {
                    $this->sessionService->addFlashMessage('danger', $t('password_requirements'));
                    $redirectUrl = $userId ? "/admin/users/edit/{$userId}" : "/admin/users/create";
                    return $response->withHeader('Location', $redirectUrl)->withStatus(302);
                }
            }
        } elseif (!empty($password) || !empty($confirmPassword)) { // Si la fuente NO es local, pero se intentó introducir contraseña
            $this->sessionService->addFlashMessage('warning', $t('password_not_managed_by_app'));
            $password = null; // Limpiar la contraseña para NO intentar guardarla
            $confirmPassword = null;
        }
        // --- Fin de la Validación de Contraseña ---

        $success = false;
        $errorMessageFlash = $t('error_saving_user');
        $userIdForRedirect = $userId;

        try {
            if ($userId) { // Modo Edición
                $success = $this->userModel->updateUser((int)$userId, $username, $email, $roleId, $activo, $sourceId, $sourceName);
                // Solo actualizar password si es local Y se proporcionó una nueva
                if ($success && !empty($password) && $source['tipo_fuente'] === 'local') {
                    $success = $this->userModel->updatePassword((int)$userId, password_hash($password, PASSWORD_DEFAULT));
                }
            } else { // Modo Creación
                // Para usuarios no locales, el password_hash puede ser null en la DB.
                $hashedPassword = ($source['tipo_fuente'] === 'local') ? password_hash($password, PASSWORD_DEFAULT) : null;
                $newUserId = $this->userModel->createUser($username, $hashedPassword, $email, $roleId, $activo, $sourceId, $sourceName);
                $success = ($newUserId !== false);
                if ($success) {
                    $userIdForRedirect = $newUserId;
                    // --- ¡AQUÍ ENVIAMOS EL CORREO! ---
                    // Solo enviamos correo si es un usuario local y tiene un email válido.
                    if ($source['tipo_fuente'] === 'local' && !empty($email)) {
                        $this->mailService->sendEmail(
                            $email,
                            $t('new_user_welcome_subject'),
                            'new_user_welcome', // Nombre de nuestra nueva plantilla
                            [
                                'username' => $username,
                                'appName' => 'CMDB App', // Puedes obtenerlo de la config si quieres
                                'appUrl' => 'http://cmdb-app.svc.int' // Puedes obtenerlo de la config
                            ]
                        );
                    }
                }
            }
            
            if ($success) {
                $this->sessionService->addFlashMessage('success', $t('user_saved_successfully'));
                return $response->withHeader('Location', '/admin/users')->withStatus(302);
            } else {
                $this->sessionService->addFlashMessage('danger', $errorMessageFlash);
                $redirectUrl = $userIdForRedirect ? "/admin/users/edit/{$userIdForRedirect}" : "/admin/users/create";
                return $response->withHeader('Location', $redirectUrl)->withStatus(302);
            }

        } catch (\PDOException $e) {
            $this->logger->error("PDOException en UserController::processForm: " . $e->getMessage() . " Code: " . $e->getCode());
            
            if ($e->getCode() == '23000' && str_contains($e->getMessage(), 'Duplicate entry')) {
                if (str_contains($e->getMessage(), "'email'")) {
                    $errorMessageFlash = $t('email_already_exists');
                } elseif (str_contains($e->getMessage(), "'nombre_usuario'")) {
                    $errorMessageFlash = $t('username_already_exists');
                } else {
                    $errorMessageFlash = $t('duplicate_data_error');
                }
            } else {
                $errorMessageFlash = $t('database_error_saving_user', ['%message%' => $e->getMessage()]);
            }
            $success = false;
            
            $this->sessionService->addFlashMessage('danger', $errorMessageFlash);
            $redirectUrl = $userIdForRedirect ? "/admin/users/edit/{$userIdForRedirect}" : "/admin/users/create";
            return $response->withHeader('Location', $redirectUrl)->withStatus(302);

        } catch (\Exception $e) {
            $this->logger->error("Excepción general en UserController::processForm: " . $e->getMessage() . " File: " . $e->getFile() . " Line: " . $e->getLine());

            $errorMessageFlash = $t('unexpected_error_saving_user');
            $success = false;
            $this->sessionService->addFlashMessage('danger', $errorMessageFlash);
            $redirectUrl = $userIdForRedirect ? "/admin/users/edit/{$userIdForRedirect}" : "/admin/users/create";
            return $response->withHeader('Location', $redirectUrl)->withStatus(302);
        }
    }


    /**
     * Procesa la eliminación de un usuario.
     */
    public function processDelete(Request $request, Response $response, array $args): Response
    {
        $userId = (int)$args['id'];
        $t = $this->translator;

        // No permitir que un usuario se elimine a sí mismo
        if ($userId === $this->sessionService->get('user_id')) {
            $this->sessionService->addFlashMessage('danger', $t('cannot_delete_self'));
            return $response->withHeader('Location', '/admin/users')->withStatus(302);
        }

        // --- Manejo de Usuario Administrador por Defecto (si está configurado) ---
        $defaultAdminEnabled = $this->config['app']['admin_user']['enabled'] ?? false;
        $defaultAdminUsername = $this->config['app']['admin_user']['username'] ?? null;
        
        if ($defaultAdminEnabled && $defaultAdminUsername) {
            $defaultUser = $this->userModel->getUserByUsername($defaultAdminUsername);
            if ($defaultUser && $userId === $defaultUser['id']) {
                $this->sessionService->addFlashMessage('danger', $t('cannot_delete_default_admin'));
                return $response->withHeader('Location', '/admin/users')->withStatus(302);
            }
        }


        $user = $this->userModel->getUserById($userId);
        if (!$user) {
            $this->sessionService->addFlashMessage('danger', $t('user_not_found_for_deletion'));
            return $response->withHeader('Location', '/admin/users')->withStatus(302);
        }

        try {
            if ($this->userModel->deleteUser($userId)) {
                $this->sessionService->addFlashMessage('success', $t('user_deleted_successfully'));
            } else {
                $this->sessionService->addFlashMessage('danger', $t('error_deleting_user_general'));
            }
        } catch (\PDOException $e) {
            $originalExceptionMessage = $e->getMessage();
            $originalExceptionCode = $e->getCode();
            
            $this->logger->error("PDOException en UserController::processDelete: " . $originalExceptionMessage . " Code: " . $originalExceptionCode);

            if ($originalExceptionCode == '23000' && str_contains($originalExceptionMessage, 'CONSTRAINT')) {
                $this->sessionService->addFlashMessage('danger', $t('user_has_associated_records'));
            } else {
                $this->sessionService->addFlashMessage('danger', $t('database_error_deleting_user', ['%message%' => $originalExceptionMessage]));
            }
        } catch (Exception $e) {
            $this->logger->error("Excepción general en UserController::processDelete: " . $e->getMessage() . " File: " . $e->getFile() . " Line: " . $e->getLine());
            $this->sessionService->addFlashMessage('danger', $t('unexpected_error_deleting_user'));
        }
        
        return $response->withHeader('Location', '/admin/users')->withStatus(302);
    }
}
