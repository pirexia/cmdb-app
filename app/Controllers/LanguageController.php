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
    private LanguageService $languageService; // <-- 2. Añadir propiedad

    public function __construct(
        SessionService $sessionService,
        LanguageService $languageService, // <-- 3. Inyectar en el constructor
        array $config
    ) {
        $this->languageService = $languageService; // <-- 4. Asignar
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

        // 5. Usar LanguageService para validar si el idioma está activo en la BBDD
        $activeLanguages = $this->languageService->getActiveLanguages();
        $availableLangCodes = array_column($activeLanguages, 'codigo_iso');

        if (in_array($langCode, $availableLangCodes)) {
            $this->sessionService->set('lang', $langCode);
            // El mensaje flash se mostrará en el nuevo idioma en la siguiente carga de página.
            // La clave 'language_changed_successfully' será traducida por el middleware.
            $this->sessionService->addFlashMessage('success', 'language_changed_successfully');
        } else {
            $this->sessionService->addFlashMessage('danger', $this->translate('invalid_language'));
        }

        // Redirigir a la página anterior o al dashboard
        $referer = $request->getHeaderLine('Referer');
        if (!empty($referer) && filter_var($referer, FILTER_VALIDATE_URL)) { // Validar URL por seguridad
            return $response->withHeader('Location', $referer)->withStatus(302);
        }
        return $response->withHeader('Location', '/dashboard')->withStatus(302);
    }

    /**
     * Helper de traducción para usar dentro del controlador (si es necesario para mensajes flash específicos).
     * @param string $key
     * @param array $replacements
     * @param string|null $langCode
     * @return string
     */
    private function translate(string $key, array $replacements = [], ?string $langCode = null): string
    {
        $langConfig = $this->config['lang'];
        $defaultLang = $this->config['app']['default_language'] ?? 'es';
        $currentLang = $langCode ?? $this->sessionService->getUserLanguage() ?? $defaultLang;
        $translations = [];

        $langFilePath = $langConfig[$currentLang] ?? null;
        if (!$langFilePath || !file_exists($langFilePath)) {
            $langFilePath = $langConfig[$defaultLang];
        }
        
        if (file_exists($langFilePath)) {
            $translations = require $langFilePath;
        }

        $text = $translations[$key] ?? $key;
        foreach ($replacements as $placeholder => $value) {
            $text = str_replace("%{$placeholder}%", (string) $value, $text);
        }
        return $text;
    }
}
