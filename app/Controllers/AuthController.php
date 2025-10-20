<?php
// app/Controllers/AuthController.php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use League\Plates\Engine;
use Psr\Log\LoggerInterface;
use App\Services\AuthService;
use App\Services\SessionService;
use App\Models\Source;

class AuthController
{
    private Engine $view;
    private LoggerInterface $logger;
    private AuthService $authService;
    private SessionService $sessionService;
    private array $config;
    private $translator; // Propiedad para guardar la función de traducción
    private Source $sourceModel;

    public function __construct(
        Engine $view,
        LoggerInterface $logger,
        AuthService $authService,
        SessionService $sessionService,
        array $config,
        callable $translator,
        Source $sourceModel
    ) {
        $this->view = $view;
        $this->logger = $logger;
        $this->authService = $authService;
        $this->sessionService = $sessionService;
        $this->config = $config;
        $this->translator = $translator; // Asigna la función de traducción
        $this->sourceModel = $sourceModel;
    }

    /**
     * Muestra el formulario de inicio de sesión.
     */
    public function showLogin(Request $request, Response $response): Response
    {
        if ($this->authService->isAuthenticated()) {
            return $response->withHeader('Location', '/dashboard')->withStatus(302);
        }
        $flashMessages = $this->sessionService->getFlashMessages();
        $t = $this->translator;

        $sources = $this->sourceModel->getAll(true) ?: []; // Obtener solo fuentes activas para el login
        if (empty($sources)) {
            // Esto es un error crítico: no hay fuentes de login. Redirigir o mostrar un error fatal.
            $this->logger->critical($t('no_active_sources_for_login') ?? 'Error crítico: No hay fuentes de usuario activas configuradas para el login.'); // Nueva clave
            $this->sessionService->addFlashMessage('danger', $t('critical_error_no_login_sources') ?? 'Error crítico: No hay fuentes de usuario configuradas para iniciar sesión.'); // Nueva clave
            return $response->withHeader('Location', '/error')->withStatus(500); // Redirigir a una página de error genérico
        }

        // Usar la nueva plantilla base para autenticación
        $html = $this->view->render('auth/login_view', [
            'pageTitle' => $t('login_page_title') ?? $t('login') . ' ' . $t('in_cmdb_app'),
            'flashMessages' => $flashMessages,
            'sources' => $sources // <--- ¡Pasa las fuentes a la vista!
        ]);

        $response->getBody()->write($html);
        return $response;
    }

    /**
     * Procesa la solicitud de inicio de sesión.
     */
    public function authenticate(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();
        $username = $data['username'] ?? '';
        $password = $data['password'] ?? '';
        $sourceId = (int)($data['id_fuente_usuario'] ?? 0);
        $t = $this->translator; // Accede a la función de traducción

        if ($this->authService->authenticate($username, $password, $sourceId)) {
            // Comprobar si ahora se requiere MFA
            if ($this->sessionService->get('mfa_required')) {
                return $response->withHeader('Location', '/mfa/verify-login')->withStatus(302);
            } else {
                $this->sessionService->addFlashMessage('success', $t('welcome_back') ?? '¡Bienvenido de nuevo!');
                return $response->withHeader('Location', '/dashboard')->withStatus(302);
            }
        } else {
            $this->sessionService->addFlashMessage('danger', $t('incorrect_credentials'));
            return $response->withHeader('Location', '/login')->withStatus(302);
        }
    }

    /**
     * Cierra la sesión del usuario.
     */
    public function logout(Request $request, Response $response): Response
    {
        $this->authService->logout();
        $t = $this->translator; // Accede a la función de traducción
        $this->sessionService->addFlashMessage('info', $t('logged_out_successfully'));
        return $response->withHeader('Location', '/login')->withStatus(302);
    }

    /**
     * Muestra el formulario para solicitar la recuperación de contraseña.
     */
    public function showForgotPassword(Request $request, Response $response): Response
    {
        $flashMessages = $this->sessionService->getFlashMessages();
        $t = $this->translator; // Accede a la función de traducción
        
        $html = $this->view->render('auth/forgot_password', [
            'pageTitle' => $t('password_reset_title'),
            'flashMessages' => $flashMessages
        ]);

        $response->getBody()->write($html);
        return $response;
    }

    /**
     * Procesa la solicitud de recuperación de contraseña (envío de email).
     */
    public function processForgotPassword(Request $request, Response $response): Response
    {
        $this->logger->info('Procesando solicitud de olvido de contraseña.');
        $data = $request->getParsedBody();
        $email = filter_var($data['email'] ?? '', FILTER_VALIDATE_EMAIL);
        $t = $this->translator; // Accede a la función de traducción

        if (!$email) {
            $this->sessionService->addFlashMessage('danger', $t('invalid_email'));
            return $response->withHeader('Location', '/forgot-password')->withStatus(302);
        }

        $this->authService->initiatePasswordReset($email);

        $this->sessionService->addFlashMessage('success', $t('reset_password_link_sent'));
        return $response->withHeader('Location', '/forgot-password')->withStatus(302);
    }

    /**
     * Muestra el formulario para restablecer la contraseña usando un token.
     */
    public function showResetPassword(Request $request, Response $response, array $args): Response
    {
        $queryParams = $request->getQueryParams();
        $token = $queryParams['token'] ?? '';
        $t = $this->translator; // Accede a la función de traducción

        if (empty($token)) {
            $this->sessionService->addFlashMessage('danger', $t('token_not_provided'));
            return $response->withHeader('Location', '/login')->withStatus(302);
        }

        $tokenData = $this->authService->getTokenData($token);

        $this->logger->info("Validando token: {$token}. Datos encontrados: " . ($tokenData ? json_encode($tokenData) : 'Ninguno'));

        if (!$tokenData || $tokenData['usado'] || (new \DateTime() > new \DateTime($tokenData['fecha_expiracion']))) {
            $this->sessionService->addFlashMessage('danger', $t('password_reset_error_invalid_expired')); // Mensaje de error
            return $response->withHeader('Location', '/forgot-password')->withStatus(302);
        }

        $flashMessages = $this->sessionService->getFlashMessages();
        
        $html = $this->view->render('auth/reset_password', [
            'pageTitle' => $t('password_reset_title'),
            'token' => $token,
            'flashMessages' => $flashMessages
        ]);

        $response->getBody()->write($html);
        return $response;
    }

    /**
     * Procesa la solicitud para restablecer la contraseña.
     */
    public function processResetPassword(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();
        $token = $data['token'] ?? '';
        $password = $data['password'] ?? '';
        $confirmPassword = $data['confirm_password'] ?? '';
        $t = $this->translator; // Accede a la función de traducción

        if (empty($token) || empty($password) || empty($confirmPassword)) {
            $this->sessionService->addFlashMessage('danger', $t('all_fields_required'));
            return $response->withHeader('Location', '/reset-password?token=' . urlencode($token))->withStatus(302);
        }

        if ($password !== $confirmPassword) {
            $this->sessionService->addFlashMessage('danger', $t('passwords_do_not_match'));
            return $response->withHeader('Location', '/reset-password?token=' . urlencode($token))->withStatus(302);
        }

        if (strlen($password) < 8 ||
            !preg_match('/[A-Z]/', $password) ||
            !preg_match('/[a-z]/', $password) ||
            !preg_match('/[0-9]/', $password) ||
            !preg_match('/[^A-Za-z0-9]/', $password)
        ) {
            $this->sessionService->addFlashMessage('danger', $t('password_requirements'));
            return $response->withHeader('Location', '/reset-password?token=' . urlencode($token))->withStatus(302);
        }

        if ($this->authService->resetPassword($token, $password)) {
            $this->sessionService->addFlashMessage('success', $t('password_reset_success'));
            return $response->withHeader('Location', '/login')->withStatus(302);
        } else {
            $this->sessionService->addFlashMessage('danger', $t('password_reset_error'));
            return $response->withHeader('Location', '/reset-password?token=' . urlencode($token))->withStatus(302);
        }
    }
}
