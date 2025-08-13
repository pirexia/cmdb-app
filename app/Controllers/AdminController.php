<?php
// app/Controllers/AdminController.php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use League\Plates\Engine as PlatesEngine;
use App\Services\SessionService;
use App\Services\AuthService;

class AdminController
{
    private PlatesEngine $view;
    private SessionService $sessionService;
    private AuthService $authService;
    private array $config;
    private $translator; // <-- ¡NUEVA PROPIEDAD!

    public function __construct(
        PlatesEngine $view,
        SessionService $sessionService,
        AuthService $authService,
        array $config,
        callable $translator // <-- ¡NUEVO ARGUMENTO!
    ) {
        $this->view = $view;
        $this->sessionService = $sessionService;
        $this->authService = $authService;
        $this->config = $config;
        $this->translator = $translator; // <-- ASIGNACIÓN
    }

    public function showUsers(Request $request, Response $response): Response
    {
        $t = $this->translator; // Accede a la función de traducción
        $this->sessionService->addFlashMessage('warning', $t('admin_section_warning') ?? 'Esta es una sección solo para administradores.'); // Traducir mensaje flash

        $html = $this->view->render('admin/users', [
            'pageTitle' => $t('user_administration'), // Traducir pageTitle
            'flashMessages' => $this->sessionService->getFlashMessages(),
        ]);
        $response->getBody()->write($html);
        return $response;
    }
    // Otros métodos de administración aquí
}
