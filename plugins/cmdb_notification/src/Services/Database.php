<?php

namespace CmdbNotification\Services;

use PDO;
use PDOException;

class Database
{
    private ?PDO $pdo = null;

    public function __construct(array $dbConfig)
    {
        $dsn = "mysql:host={$dbConfig['host']};dbname={$dbConfig['name']};charset={$dbConfig['charset']}";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try {
            $this->pdo = new PDO($dsn, $dbConfig['user'], $dbConfig['pass'], $options);
        } catch (PDOException $e) {
            error_log("Error de conexión a la base de datos en cmdb_notification: " . $e->getMessage());
            exit(1); // Salir con código de error
        }
    }

    public function getConnection(): PDO
    {
        return $this->pdo;
    }
}

