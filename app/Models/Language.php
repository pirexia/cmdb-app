<?php
// app/Models/Language.php

namespace App\Models;

use PDO;
use PDOException;

class Language
{
    private PDO $db;
    protected string $tableName = 'idiomas';

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Obtiene todos los idiomas de la base de datos (activos e inactivos).
     * Requerido por MasterController.
     * @return array|false
     */
    public function getAll(): array|false
    {
        try {
            $stmt = $this->db->query("SELECT * FROM {$this->tableName} ORDER BY nombre ASC");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("MODEL ERROR: " . __CLASS__ . "::" . __FUNCTION__ . " failed: " . $e->getMessage() . " Code: " . $e->getCode());
            return false;
        }
    }

    /**
     * Obtiene todos los idiomas activos de la base de datos.
     *
     * @return array
     */
    public function getActiveLanguages(): array
    {
        $stmt = $this->db->prepare("SELECT id, codigo_iso, nombre FROM {$this->tableName} WHERE activo = 1 ORDER BY nombre ASC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene un idioma por su código ISO, solo si está activo.
     *
     * @param string $isoCode
     * @return array|false
     */
    public function findActiveByIsoCode(string $isoCode): array|false
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->tableName} WHERE codigo_iso = :isoCode AND activo = 1");
        $stmt->bindParam(':isoCode', $isoCode);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Crea un nuevo idioma.
     * @param array $data
     * @return int|false
     */
    public function create(array $data): int|false
    {
        try {
            $stmt = $this->db->prepare(
                "INSERT INTO {$this->tableName} (nombre, codigo_iso, activo, nombre_fichero) 
                 VALUES (:nombre, :codigo_iso, :activo, :nombre_fichero)"
            );
            $stmt->bindValue(':nombre', $data['nombre'] ?? null);
            $stmt->bindValue(':codigo_iso', $data['codigo_iso'] ?? null);
            $stmt->bindValue(':activo', $data['activo'] ?? 0, PDO::PARAM_INT);
            $stmt->bindValue(':nombre_fichero', $data['nombre_fichero'] ?? null);
            $stmt->execute();
            return (int)$this->db->lastInsertId();
        } catch (PDOException $e) {
            // Log error
            error_log("MODEL ERROR: " . __CLASS__ . "::" . __FUNCTION__ . " failed: " . $e->getMessage() . " Code: " . $e->getCode());
            return false;
        }
    }

    /**
     * Actualiza un idioma existente.
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function update(int $id, array $data): bool
    {
        try {
            $stmt = $this->db->prepare(
                "UPDATE {$this->tableName} SET 
                 nombre = :nombre, codigo_iso = :codigo_iso, activo = :activo, nombre_fichero = :nombre_fichero 
                 WHERE id = :id"
            );
            $stmt->bindValue(':nombre', $data['nombre'] ?? null);
            $stmt->bindValue(':codigo_iso', $data['codigo_iso'] ?? null);
            $stmt->bindValue(':activo', $data['activo'] ?? 0, PDO::PARAM_INT);
            $stmt->bindValue(':nombre_fichero', $data['nombre_fichero'] ?? null);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            // Log error
            error_log("MODEL ERROR: " . __CLASS__ . "::" . __FUNCTION__ . " failed: " . $e->getMessage() . " Code: " . $e->getCode());
            return false;
        }
    }

    /**
     * Obtiene un idioma por su ID.
     * @param int $id
     * @return array|false
     */
    public function getById(int $id): array|false
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->tableName} WHERE id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM {$this->tableName} WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    /**
     * Cambia el estado de un idioma (activo/inactivo).
     * @param int $id
     * @return bool
     */
    public function toggleStatus(int $id): bool
    {
        try {
            // La expresión `1 - activo` cambia 0 a 1 y 1 a 0.
            $stmt = $this->db->prepare(
                "UPDATE {$this->tableName} SET activo = 1 - activo WHERE id = :id"
            );
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("MODEL ERROR: " . __CLASS__ . "::" . __FUNCTION__ . " failed: " . $e->getMessage() . " Code: " . $e->getCode());
            // En un caso real, podrías querer relanzar la excepción
            // throw $e;
            return false;
        }
    }
}