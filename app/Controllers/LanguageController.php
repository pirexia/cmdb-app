<?php
// app/Controllers/LanguageController.php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Services\SessionService;
use App\Services\LanguageService; // <-- 1. Importar LanguageService

class LanguageController
{
    private SessionService $sessionService;
    private array $config;
    private LanguageService $languageService;
    private $translator; // <-- Propiedad para el traductor

    public function __construct(
        SessionService $sessionService,
        LanguageService $languageService,
        array $config,
        callable $translator // <-- Inyectar traductor
    ) {
        $this->languageService = $languageService;
        $this->translator = $translator; // <-- Asignar traductor
        $this->sessionService = $sessionService;
        $this->config = $config;
    }

    /**
     * Establece el idioma preferido del usuario en la sesión y redirige.
     * @param Request $request
     * @param Response $response
     * @param array $args Contiene 'lang_code'
     * @return Response
     */
    public function setLanguage(Request $request, Response $response, array $args): Response
    {
        $langCode = $args['lang_code'];
        $t = $this->translator;

        // 5. Usar LanguageService para validar si el idioma está activo en la BBDD
        $activeLanguages = $this->languageService->getActiveLanguages();
        $availableLangCodes = array_column($activeLanguages, 'codigo_iso');

        if (in_array($langCode, $availableLangCodes)) {
            $this->sessionService->set('lang', $langCode);
            // El mensaje flash se mostrará en el nuevo idioma en la siguiente carga de página.
            // La clave 'language_changed_successfully' será traducida por el middleware.
            $this->sessionService->addFlashMessage('success', 'language_changed_successfully');
        } else {
            $this->sessionService->addFlashMessage('danger', $t('invalid_language')); // Usar el traductor inyectado
        }

        // Redirigir a la página anterior o al dashboard
        $referer = $request->getHeaderLine('Referer');
        if (!empty($referer) && filter_var($referer, FILTER_VALIDATE_URL)) { // Validar URL por seguridad
            return $response->withHeader('Location', $referer)->withStatus(302);
        }
        return $response->withHeader('Location', '/dashboard')->withStatus(302);
    }
}
