<?php
// app/Middlewares/RoleMiddleware.php

namespace App\Middlewares;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use App\Services\AuthService;
use App\Services\SessionService;

class RoleMiddleware implements MiddlewareInterface
{
    private AuthService $authService;
    private SessionService $sessionService;
    private string $requiredRoleName;

    public function __construct(AuthService $authService, SessionService $sessionService, string $requiredRoleName)
    {
        $this->authService = $authService;
        $this->sessionService = $sessionService;
        $this->requiredRoleName = $requiredRoleName;
    }

    public function process(Request $request, RequestHandler $handler): Response
    {
        if (!$this->authService->isAuthenticated()) {
            // Si no está autenticado, el AuthMiddleware ya lo redirigirá.
            // Esto es una medida de seguridad extra o si este middleware se usa solo.
            $this->sessionService->addFlashMessage('danger', 'Acceso denegado: Debes iniciar sesión.');
            $response = new \Slim\Psr7\Response();
            return $response->withHeader('Location', '/login')->withStatus(302);
        }

        if (!$this->authService->hasRole($this->requiredRoleName)) {
            // Usuario autenticado pero sin el rol requerido
            $this->sessionService->addFlashMessage('danger', 'Acceso denegado: No tienes permisos suficientes para acceder a esta sección.');
            $response = new \Slim\Psr7\Response();
            // Redirigir a un dashboard o página de inicio
            return $response->withHeader('Location', '/dashboard')->withStatus(302); // O una página de "Acceso Denegado"
        }

        // Si el usuario tiene el rol, pasa la solicitud
        return $handler->handle($request);
    }

    /**
     * Helper para crear un RoleMiddleware con un rol específico.
     * Esto es útil para la inyección de dependencias en las rutas.
     * @param string $roleName
     * @return callable
     */
    public static function create(string $roleName): callable
    {
        return function (Request $request, RequestHandler $handler) use ($roleName) {
            $authService = $this->get(AuthService::class);
            $sessionService = $this->get(SessionService::class);
            $middleware = new RoleMiddleware($authService, $sessionService, $roleName);
            return $middleware->process($request, $handler);
        };
    }
}
