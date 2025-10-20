<?php
// app/Middlewares/LanguageMiddleware.php

namespace App\Middlewares;

use App\Services\LanguageService;
use App\Services\SessionService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use League\Plates\Engine as PlatesEngine;

class LanguageMiddleware implements MiddlewareInterface
{
    private LanguageService $languageService;
    private PlatesEngine $plates;
    private SessionService $sessionService;

    public function __construct(LanguageService $languageService, PlatesEngine $plates, SessionService $sessionService)
    {
        $this->languageService = $languageService;
        $this->plates = $plates;
        $this->sessionService = $sessionService;
    }

    public function process(Request $request, RequestHandler $handler): Response
    {
        // Cargar las traducciones basadas en la sesión o el valor por defecto
        $this->languageService->loadTranslations();

        // Hacer que el traductor y la lista de idiomas estén disponibles en todas las plantillas
        $this->plates->addData([
            't' => $this->languageService->getTranslator(),
            'activeLanguages' => $this->languageService->getActiveLanguages(),
            'currentLanguage' => $this->sessionService->get('lang', 'en') // Añadido para el selector
        ]);

        return $handler->handle($request);
    }
}