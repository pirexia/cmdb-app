<?php
// app/Middlewares/AuthMiddleware.php

namespace App\Middlewares;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use App\Services\AuthService;
use App\Services\SessionService; // Necesitamos el servicio de sesión para flash messages

class AuthMiddleware implements MiddlewareInterface
{
    private AuthService $authService;
    private SessionService $sessionService; // Inyectamos SessionService

    public function __construct(AuthService $authService, SessionService $sessionService)
    {
        $this->authService = $authService;
        $this->sessionService = $sessionService;
    }

    public function process(Request $request, RequestHandler $handler): Response
    {
        if (!$this->authService->isAuthenticated()) {
            // Usuario no autenticado, redirigir a la página de login
            $this->sessionService->addFlashMessage('danger', 'Debes iniciar sesión para acceder a esta página.');
            $response = new \Slim\Psr7\Response();
            return $response->withHeader('Location', '/login')->withStatus(302);
        }

        // Si el usuario está autenticado, pasa la solicitud al siguiente middleware o controlador
        return $handler->handle($request);
    }
}
