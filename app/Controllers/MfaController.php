<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use League\Plates\Engine as PlatesEngine;
use App\Services\SessionService;
use App\Services\MfaService;
use App\Models\User;
use App\Models\Role;
use App\Services\AuthService; // <-- ¡NUEVO!

class MfaController
{
    private PlatesEngine $view;
    private SessionService $session;
    private MfaService $mfaService;
    private User $userModel;
    private Role $roleModel;
    private AuthService $authService; // <-- ¡NUEVO!
    private $translator;

    public function __construct(
        PlatesEngine $view,
        SessionService $session,
        MfaService $mfaService,
        User $userModel,
        Role $roleModel,
        AuthService $authService, // <-- ¡NUEVO!
        callable $translator
    ) {
        $this->view = $view;
        $this->session = $session;
        $this->mfaService = $mfaService;
        $this->userModel = $userModel;
        $this->roleModel = $roleModel;
        $this->authService = $authService; // <-- ¡NUEVO!
        $this->translator = $translator;
    }

    public function showMfaSetup(Request $request, Response $response): Response
    {
        $t = $this->translator;
        $userId = $this->session->get('user_id');
        $user = $this->userModel->getUserById($userId);

        $secret = $this->mfaService->generateSecretKey();
        $this->session->set('mfa_temp_secret', $secret);

        $qrCodeInline = $this->mfaService->getQrCodeInline($user['nombre_usuario'], $secret);

        $html = $this->view->render('mfa/setup', [
            'qrCodeInline' => $qrCodeInline,
            'secret' => $secret,
            'flashMessages' => $this->session->getFlashMessages()
        ]);
        $response->getBody()->write($html);
        return $response;
    }

    public function verifyMfaSetup(Request $request, Response $response): Response
    {
        $t = $this->translator;
        $data = $request->getParsedBody();
        $code = $data['mfa_code'] ?? '';
        $userId = $this->session->get('user_id');
        $secret = $this->session->get('mfa_temp_secret');

        if ($this->mfaService->verifyCode($secret, $code)) {
            $this->mfaService->enableMfaForUser($userId, $secret);
            $this->session->remove('mfa_temp_secret');
            $this->session->addFlashMessage('success', $t('mfa_enabled_successfully'));
            return $response->withHeader('Location', '/profile')->withStatus(302);
        } else {
            $this->session->addFlashMessage('danger', $t('mfa_invalid_code'));
            return $response->withHeader('Location', '/mfa/setup')->withStatus(302);
        }
    }

    public function disableMfa(Request $request, Response $response): Response
    {
        $t = $this->translator;
        $userId = $this->session->get('user_id');
        $this->mfaService->disableMfaForUser($userId);
        $this->session->addFlashMessage('success', $t('mfa_disabled_successfully'));
        return $response->withHeader('Location', '/profile')->withStatus(302);
    }

    public function showVerifyLoginForm(Request $request, Response $response): Response
    {
        $t = $this->translator;
        if (!$this->session->get('mfa_required')) {
            return $response->withHeader('Location', '/login')->withStatus(302);
        }

        $html = $this->view->render('mfa/verify_login', [
            'flashMessages' => $this->session->getFlashMessages()
        ]);
        $response->getBody()->write($html);
        return $response;
    }

    public function processVerifyLogin(Request $request, Response $response): Response
    {
        $t = $this->translator;
        $data = $request->getParsedBody();
        $code = $data['mfa_code'] ?? '';
        $userId = $this->session->get('mfa_user_id');

        if (!$userId) {
            return $response->withHeader('Location', '/login')->withStatus(302);
        }

        $user = $this->userModel->getUserById($userId);

        if ($user && $this->mfaService->verifyCode($user['mfa_secret'], $code)) {
            // Verificación exitosa, completar el inicio de sesión
            $this->authService->completeLogin($user);

            // --- ¡NUEVO! Comprobar si se debe preguntar por dispositivo de confianza ---
            $cookieConsent = $_COOKIE['cookie_consent_status'] ?? 'not_set';
            if ($cookieConsent === 'accepted') {
                // Si el usuario aceptó las cookies, lo redirigimos a la página para que decida si confía en el dispositivo
                return $response->withHeader('Location', '/mfa/trust-device')->withStatus(302);
            } else {
                // Si no ha aceptado cookies, simplemente lo logueamos
                $this->session->addFlashMessage('success', $t('welcome_back'));
                return $response->withHeader('Location', '/dashboard')->withStatus(302);
            }
        } else {
            $this->session->addFlashMessage('danger', $t('mfa_invalid_code'));
            return $response->withHeader('Location', '/mfa/verify-login')->withStatus(302);
        }
    }

    /**
     * Muestra el formulario para preguntar si se confía en el dispositivo.
     */
    public function showTrustDeviceForm(Request $request, Response $response): Response
    {
        // Asegurarse de que el usuario está logueado para llegar aquí
        if (!$this->session->get('user_id')) {
            return $response->withHeader('Location', '/login')->withStatus(302);
        }

        $html = $this->view->render('mfa/trust_device', [
            'flashMessages' => $this->session->getFlashMessages()
        ]);
        $response->getBody()->write($html);
        return $response;
    }

    /**
     * Procesa la decisión del usuario sobre confiar en el dispositivo.
     */
    public function processTrustDevice(Request $request, Response $response): Response
    {
        $t = $this->translator;
        $userId = $this->session->get('user_id');
        if (!$userId) {
            return $response->withHeader('Location', '/login')->withStatus(302);
        }

        $data = $request->getParsedBody();
        $trustDevice = $data['trust'] ?? 'no';

        if ($trustDevice === 'yes') {
            $userAgent = $request->getHeaderLine('User-Agent');
            $ipAddress = $request->getServerParams()['REMOTE_ADDR'] ?? null;

            if ($this->authService->createTrustedDevice($userId, $userAgent, $ipAddress)) {
                $this->session->addFlashMessage('success', $t('mfa_device_trusted_successfully'));
            }
        }

        // En ambos casos (sí o no), se redirige al dashboard.
        return $response->withHeader('Location', '/dashboard')->withStatus(302);
    }
}