<?php
// app/Models/ContractType.php

namespace App\Models;

use PDO;
use PDOException; // Asegúrate de que esta línea esté presente

class ContractType
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Obtiene todos los tipos de contrato.
     * @return array|false
     */
    public function getAll()
    {
        try {
            $stmt = $this->db->query("SELECT id, nombre, descripcion FROM tipos_contrato ORDER BY nombre ASC");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("MODEL ERROR: " . __CLASS__ . "::" . __FUNCTION__ . " failed: " . $e->getMessage() . " Code: " . $e->getCode());
            return false;
        }
    }

    /**
     * Obtiene un tipo de contrato por su nombre.
     * La búsqueda es insensible a mayúsculas/minúsculas y espacios en blanco.
     * @param string $name El nombre del tipo de contrato a buscar (se recomienda pasarlo en minúsculas y sin espacios extra).
     * @return array|false Un array asociativo con los datos del tipo de contrato, o false si no se encuentra.
     */
    public function getByName(string $name)
    {
        try {
            // Usamos LOWER() y TRIM() para hacer la búsqueda flexible y corregimos el nombre de la tabla
            $stmt = $this->db->prepare("SELECT id, nombre, descripcion FROM tipos_contrato WHERE LOWER(TRIM(nombre)) = :nombre");
            $stmt->bindParam(':nombre', $name, PDO::PARAM_STR);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("MODEL ERROR: " . __CLASS__ . "::" . __FUNCTION__ . " failed: " . $e->getMessage() . " Code: " . $e->getCode());
            return false;
        }
    }

    /**
     * Obtiene un tipo de contrato por su ID.
     * @param int $id
     * @return array|false
     */
    public function getById(int $id)
    {
        try {
            $stmt = $this->db->prepare("SELECT id, nombre, descripcion FROM tipos_contrato WHERE id = :id");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("MODEL ERROR: " . __CLASS__ . "::" . __FUNCTION__ . " failed: " . $e->getMessage() . " Code: " . $e->getCode());
            return false;
        }
    }

    /**
     * Crea un nuevo tipo de contrato.
     * @param string $nombre
     * @param string|null $descripcion
     * @return int ID del nuevo tipo de contrato
     * @throws PDOException Si la inserción falla.
     */
    public function create(string $nombre, ?string $descripcion = null)
    {
        try {
            $stmt = $this->db->prepare("INSERT INTO tipos_contrato (nombre, descripcion) VALUES (:nombre, :descripcion)");
            $stmt->bindParam(':nombre', $nombre, PDO::PARAM_STR);
            $stmt->bindParam(':descripcion', $descripcion, PDO::PARAM_STR);
            $stmt->execute();

            // === ¡AÑADE ESTA COMPROBACIÓN DESPUÉS DE CADA $stmt->execute()! ===
            if ($stmt->errorCode() !== '00000') {
                 $errorInfo = $stmt->errorInfo();
                 throw new PDOException("Database error: " . $errorInfo[2], $errorInfo[0]);
            }
            // ==================================================================
            return $this->db->lastInsertId();
        } catch (PDOException $e) {
            error_log("MODEL ERROR: " . __CLASS__ . "::" . __FUNCTION__ . " failed: " . $e->getMessage() . " Code: " . $e->getCode());
            throw $e; // Relanzar la excepción
        }
    }

    /**
     * Actualiza un tipo de contrato existente.
     * @param int $id
     * @param string $nombre
     * @param string|null $descripcion
     * @return bool
     * @throws PDOException Si la actualización falla.
     */
    public function update(int $id, string $nombre, ?string $descripcion = null)
    {
        try {
            $stmt = $this->db->prepare("UPDATE tipos_contrato SET nombre = :nombre, descripcion = :descripcion WHERE id = :id");
            $stmt->bindParam(':nombre', $nombre, PDO::PARAM_STR);
            $stmt->bindParam(':descripcion', $descripcion, PDO::PARAM_STR);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();

            // === ¡AÑADE ESTA COMPROBACIÓN DESPUÉS DE CADA $stmt->execute()! ===
            if ($stmt->errorCode() !== '00000') {
                 $errorInfo = $stmt->errorInfo();
                 throw new PDOException("Database error: " . $errorInfo[2], $errorInfo[0]);
            }
            // ==================================================================
            return true;
        } catch (PDOException $e) {
            error_log("MODEL ERROR: " . __CLASS__ . "::" . __FUNCTION__ . " failed: " . $e->getMessage() . " Code: " . $e->getCode());
            throw $e; // Relanzar la excepción
        }
    }

    /**
     * Elimina un tipo de contrato.
     * @param int $id
     * @return bool
     * @throws PDOException Si la eliminación falla.
     */
    public function delete(int $id)
    {
        try {
            $stmt = $this->db->prepare("DELETE FROM tipos_contrato WHERE id = :id");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();

            // === ¡AÑADE ESTA COMPROBACIÓN DESPUÉS DE CADA $stmt->execute()! ===
            if ($stmt->errorCode() !== '00000') {
                 $errorInfo = $stmt->errorInfo();
                 throw new PDOException("Database error: " . $errorInfo[2], $errorInfo[0]);
            }
            // ==================================================================
            return true;
        } catch (PDOException $e) {
            error_log("MODEL ERROR: " . __CLASS__ . "::" . __FUNCTION__ . " failed: " . $e->getMessage() . " Code: " . $e->getCode());
            throw $e; // Relanzar la excepción
        }
    }
}
