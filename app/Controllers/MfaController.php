<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use League\Plates\Engine as PlatesEngine;
use App\Services\SessionService;
use App\Services\MfaService;
use App\Models\User;
use App\Models\Role;

class MfaController
{
    private PlatesEngine $view;
    private SessionService $session;
    private MfaService $mfaService;
    private User $userModel;
    private Role $roleModel;
    private $translator;

    public function __construct(
        PlatesEngine $view,
        SessionService $session,
        MfaService $mfaService,
        User $userModel,
        Role $roleModel,
        callable $translator
    ) {
        $this->view = $view;
        $this->session = $session;
        $this->mfaService = $mfaService;
        $this->userModel = $userModel;
        $this->roleModel = $roleModel;
        $this->translator = $translator;
    }

    public function showMfaSetup(Request $request, Response $response): Response
    {
        $t = $this->translator;
        $userId = $this->session->get('user_id');
        $user = $this->userModel->getUserById($userId);

        $secret = $this->mfaService->generateSecretKey();
        $this->session->set('mfa_temp_secret', $secret);

        $qrCodeUrl = $this->mfaService->getQrCodeUrl($user['nombre_usuario'], $secret);

        $html = $this->view->render('mfa/setup', [
            'qrCodeUrl' => $qrCodeUrl,
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
            $this->session->remove('mfa_required');
            $this->session->remove('mfa_user_id');

            $this->session->set('user_id', $user['id']);
            $this->session->set('username', $user['nombre_usuario']);
            $this->session->set('role_id', $user['id_rol']);
            $this->session->set('id_fuente_usuario', $user['id_fuente_usuario']);
            $this->session->set('fuente_login_nombre', $user['fuente_login_nombre']);
            
            $role = $this->roleModel->getRoleById($user['id_rol']);
            $this->session->set('role_name', $role['nombre'] ?? 'Desconocido');

            $this->userModel->updateLastLogin($user['id']);

            $this->session->addFlashMessage('success', $t('welcome_back'));
            return $response->withHeader('Location', '/dashboard')->withStatus(302);
        } else {
            $this->session->addFlashMessage('danger', $t('mfa_invalid_code'));
            return $response->withHeader('Location', '/mfa/verify-login')->withStatus(302);
        }
    }
}