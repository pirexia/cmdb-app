<?php
// app/Services/SessionService.php

namespace App\Services;

class SessionService
{
    private array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
        $this->configureSession();
    }

    /**
     * Configura los parámetros de seguridad de la sesión.
     */
    private function configureSession(): void
    {
        ini_set('session.use_strict_mode', 1); // Previene session fixation
        ini_set('session.cookie_httponly', $this->config['cookie_httponly'] ? '1' : '0'); // Previene XSS robando cookies
        ini_set('session.cookie_secure', $this->config['cookie_secure'] ? '1' : '0');     // Solo enviar cookie por HTTPS
        ini_set('session.cookie_samesite', $this->config['cookie_samesite']);             // Previene CSRF
        ini_set('session.name', $this->config['name']);
        ini_set('session.gc_maxlifetime', (string) $this->config['lifetime']); // Duración de la sesión en segundos
    }

    /**
     * Inicia la sesión PHP si no está iniciada.
     */
    public function startSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
            $this->setSessionLifetime();
        }
    }

    /**
     * Establece el tiempo de vida de la sesión para la cookie y el GC.
     */
    private function setSessionLifetime(): void
    {
        // Renovar el ID de sesión periódicamente para prevenir secuestro de sesión
        if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > $this->config['lifetime'] / 2)) {
            session_regenerate_id(true); // Regenera el ID de sesión, true = elimina el antiguo
        }
        $_SESSION['LAST_ACTIVITY'] = time(); // Actualiza el tiempo de última actividad
    }

    /**
     * Destruye la sesión actual.
     */
    public function destroySession(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            // Vaciar todas las variables de sesión
            $_SESSION = [];

            // Eliminar la cookie de sesión
            if (ini_get("session.use_cookies")) {
                $params = session_get_cookie_params();
                setcookie(
                    session_name(),
                    '',
                    time() - 42000,
                    $params["path"],
                    $params["domain"],
                    $params["secure"],
                    $params["httponly"]
                );
            }

            // Destruir la sesión
            session_destroy();
        }
    }

    /**
     * Establece un valor en la sesión.
     * @param string $key
     * @param mixed $value
     */
    public function set(string $key, $value): void
    {
        $this->startSession(); // Asegurar que la sesión está iniciada
        $_SESSION[$key] = $value;
    }

    /**
     * Obtiene un valor de la sesión.
     * @param string $key
     * @param mixed|null $default
     * @return mixed|null
     */
    public function get(string $key, $default = null)
    {
        $this->startSession(); // Asegurar que la sesión está iniciada
        return $_SESSION[$key] ?? $default;
    }

    /**
     * Verifica si una clave existe en la sesión.
     * @param string $key
     * @return bool
     */
    public function has(string $key): bool
    {
        $this->startSession(); // Asegurar que la sesión está iniciada
        return isset($_SESSION[$key]);
    }

    /**
     * Elimina una clave de la sesión.
     * @param string $key
     */
    public function remove(string $key): void
    {
        $this->startSession(); // Asegurar que la sesión está iniciada
        unset($_SESSION[$key]);
    }

    /**
     * Añade un mensaje flash a la sesión.
     * @param string $type Tipo de mensaje (ej. 'success', 'error', 'warning', 'info')
     * @param string $message El mensaje a mostrar
     */
    public function addFlashMessage(string $type, string $message): void
    {
        $this->startSession();
        if (!isset($_SESSION['flash'])) {
            $_SESSION['flash'] = [];
        }
        $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
    }

    /**
     * Obtiene y limpia los mensajes flash de la sesión.
     * @return array
     */
    public function getFlashMessages(): array
    {
        $this->startSession();
        $messages = $_SESSION['flash'] ?? [];
        unset($_SESSION['flash']); // Limpiar mensajes después de leerlos
        return $messages;
    }

    /**
     * Obtiene el idioma preferido del usuario de la sesión, o el idioma por defecto de la app.
     * @return string Código de idioma (ej. 'es', 'en')
     */
    public function getUserLanguage(): string
    {
        $this->startSession();

        // 1. Prioridad: Sesión actual (cambio explícito del usuario)
        if ($this->has('lang')) {
            return $this->get('lang');
        }

        // 2. Segunda prioridad: Cookie de preferencia de usuario
        if (isset($_COOKIE['user_lang_pref'])) {
            return $_COOKIE['user_lang_pref'];
        }

        // 3. Fallback: Idioma por defecto de la aplicación
        return $this->config['app']['default_language'] ?? 'es';

    }

    /**
     * Establece el idioma preferido del usuario en la sesión.
     * @param string $langCode
     */
    public function setUserLanguage(string $langCode): void
    {
        $this->set('user_language', $langCode);
    }
}
