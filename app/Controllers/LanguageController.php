<?php
// app/Controllers/LanguageController.php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Services\SessionService;

class LanguageController
{
    private SessionService $sessionService;
    private array $config;

    public function __construct(SessionService $sessionService, array $config)
    {
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
        // Obtener los códigos de idioma disponibles de la configuración
        $availableLangs = array_keys($this->config['lang']);

        if (in_array($langCode, $availableLangs)) {
            // Primero, traducimos el mensaje con el idioma actual.
            $message = $this->translate('language_changed_successfully', ['%lang%' => strtoupper($langCode)]);
            // Luego, establecemos el nuevo idioma en la sesión.
            $this->sessionService->setUserLanguage($langCode);
            // Finalmente, añadimos el mensaje ya traducido al flash.
            $this->sessionService->addFlashMessage('success', $message);
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
        $defaultLang = $this->config['app']['default_language'];
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
            $text = str_replace($placeholder, $value, $text);
        }
        return $text;
    }
}
