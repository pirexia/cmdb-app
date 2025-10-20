<?php
// app/Services/LanguageService.php

namespace App\Services;

use App\Models\Language as LanguageModel;
use Psr\Log\LoggerInterface;

class LanguageService
{
    private LanguageModel $languageModel;
    private SessionService $sessionService;
    private LoggerInterface $logger;
    private array $config;
    private array $translations = [];
    private string $currentLangCode = 'en'; // Default

    public function __construct(LanguageModel $languageModel, SessionService $sessionService, LoggerInterface $logger, array $config)
    {
        $this->languageModel = $languageModel;
        $this->sessionService = $sessionService;
        $this->logger = $logger;
        $this->config = $config;
        $this->currentLangCode = $config['app']['default_language'] ?? 'en';
    }

    /**
     * Carga las traducciones para el idioma actual.
     */
    public function loadTranslations(): void
    {
        // 1. Determinar el idioma a usar (cookie > sesión > default)
        $langCode = $this->sessionService->getUserLanguage();

        // 2. Validar que el idioma está activo en la BBDD
        $language = $this->languageModel->findActiveByIsoCode($langCode);

        if (!$language || empty($language['nombre_fichero'])) {
            $this->logger->warning("Idioma '{$langCode}' no encontrado, inactivo o sin fichero. Volviendo al idioma por defecto.");
            $langCode = $this->config['app']['default_language'] ?? 'en';
            $language = $this->languageModel->findActiveByIsoCode($langCode);
        }

        $this->currentLangCode = $langCode;
        $this->sessionService->set('lang', $langCode);

        // 3. Cargar el fichero de idioma
        $langFilePath = __DIR__ . '/../Lang/' . $language['nombre_fichero'];

        if (file_exists($langFilePath)) {
            $this->translations = require $langFilePath;
        } else {
            $this->logger->error("Fichero de idioma no encontrado: {$langFilePath}");
            $this->translations = []; // Dejar vacío para que las claves se muestren como están
        }
    }

    /**
     * Devuelve el callable del traductor para inyectarlo en otros servicios y vistas.
     */
    public function getTranslator(): callable
    {
        return function (string $key, array $replacements = []) {
            $text = $this->translations[$key] ?? $key;
            foreach ($replacements as $placeholder => $value) {
                $text = str_replace("%{$placeholder}%", (string) $value, $text);
            }
            return $text;
        };
    }

    /**
     * Obtiene la lista de idiomas activos para el selector del UI.
     */
    public function getActiveLanguages(): array
    {
        return $this->languageModel->getActiveLanguages();
    }

    /**
     * Establece la cookie de preferencia de idioma del usuario.
     * @param string $langCode El código de idioma (ej. 'es', 'en').
     */
    public function setLanguageCookie(string $langCode): void
    {
        setcookie(
            'user_lang_pref',
            $langCode,
            [
                'expires' => time() + (86400 * 365), // 1 año
                'path' => '/',
                'secure' => $this->config['session']['cookie_secure'] ?? false,
                'httponly' => $this->config['session']['cookie_httponly'] ?? true,
                'samesite' => $this->config['session']['cookie_samesite'] ?? 'Lax'
            ]
        );
    }

    /**
     * Elimina la cookie de preferencia de idioma.
     */
    public function deleteLanguageCookie(): void
    {
        setcookie('user_lang_pref', '', time() - 3600, '/');
    }
}