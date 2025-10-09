<?php
// app/Middlewares/RequestLogMiddleware.php

namespace App\Middlewares;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Log\LoggerInterface;

/**
 * Middleware para registrar cada petición HTTP entrante usando Monolog.
 */
class RequestLogMiddleware
{
    private LoggerInterface $logger;

    /**
     * @param LoggerInterface $logger Instancia del logger inyectada por el contenedor.
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Método de invocación del middleware.
     *
     * @param Request $request La petición PSR-7.
     * @param RequestHandler $handler El siguiente manejador de peticiones en la cadena.
     * @return Response La respuesta PSR-7.
     */
    public function __invoke(Request $request, RequestHandler $handler): Response
    {
        $method = $request->getMethod();
        $uri = (string) $request->getUri();

        // Registra la petición en el nivel DEBUG.
        $this->logger->debug("Request: {$method} {$uri}");

        // Pasa la petición al siguiente middleware/manejador.
        return $handler->handle($request);
    }
}