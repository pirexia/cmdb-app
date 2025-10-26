<?php

namespace CmdbNotification\Services;

class Config
{
    private array $config;

    public function __construct(string $mainAppPath)
    {
        // Cargar .env desde la ruta de la aplicación principal
        if (file_exists($mainAppPath . '/.env')) {
            $dotenv = \Dotenv\Dotenv::createImmutable($mainAppPath);
            $dotenv->load();
        }

        // Definir la constante ROOT_PATH si no existe, para que config.php funcione.
        if (!defined('ROOT_PATH')) {
            define('ROOT_PATH', $mainAppPath);
        }

        // Cargar config.php que ahora usará las variables de .env
        $this->config = require $mainAppPath . '/app/Config/config.php';
    }

    public function get(string $key, $default = null)
    {
        $keys = explode('.', $key);
        $value = $this->config;
        foreach ($keys as $k) {
            if (!isset($value[$k])) {
                return $default;
            }
            $value = $value[$k];
        }
        return $value;
    }
}