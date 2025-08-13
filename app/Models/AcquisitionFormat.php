<?php
// app/Models/AcquisitionFormat.php

namespace App\Models;

use PDO;
use PDOException; // Asegúrate de que esta línea esté presente

class AcquisitionFormat
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Obtiene todos los formatos de adquisición.
     * @return array|false
     */
    public function getAll()
    {
        try {
            $stmt = $this->db->query("SELECT id, nombre, descripcion FROM formatos_adquisicion ORDER BY nombre ASC");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("MODEL ERROR: " . __CLASS__ . "::" . __FUNCTION__ . " failed: " . $e->getMessage() . " Code: " . $e->getCode());
            return false;
        }
    }

    /**
     * Obtiene un formato de adquisición por su nombre.
     * @param string $name El nombre del fabricante a buscar.
     * @return array|false Un array asociativo con los datos del fabricante, o false si no se encuentra o ocurre un error.
     */
    public function getByName(string $name)
    {
        try {
            $stmt = $this->db->prepare("SELECT id, nombre, descripcion FROM formatos_adquisicion WHERE nombre = :nombre");
            $stmt->bindParam(':nombre', $name, PDO::PARAM_STR);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("MODEL ERROR: " . __CLASS__ . "::" . __FUNCTION__ . " failed: " . $e->getMessage() . " Code: " . $e->getCode());
            return false;
        }
    }

    /**
     * Obtiene un formato de adquisición por su ID.
     * @param int $id
     * @return array|false
     */
    public function getById(int $id)
    {
        try {
            $stmt = $this->db->prepare("SELECT id, nombre, descripcion FROM formatos_adquisicion WHERE id = :id");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("MODEL ERROR: " . __CLASS__ . "::" . __FUNCTION__ . " failed: " . $e->getMessage() . " Code: " . $e->getCode());
            return false;
        }
    }

    /**
     * Crea un nuevo formato de adquisición.
     * @param string $nombre
     * @param string|null $descripcion
     * @return int ID del nuevo formato de adquisición
     * @throws PDOException Si la inserción falla.
     */
    public function create(string $nombre, ?string $descripcion = null)
    {
        try {
            $stmt = $this->db->prepare("INSERT INTO formatos_adquisicion (nombre, descripcion) VALUES (:nombre, :descripcion)");
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
     * Actualiza un formato de adquisición existente.
     * @param int $id
     * @param string $nombre
     * @param string|null $descripcion
     * @return bool
     * @throws PDOException Si la actualización falla.
     */
    public function update(int $id, string $nombre, ?string $descripcion = null)
    {
        try {
            $stmt = $this->db->prepare("UPDATE formatos_adquisicion SET nombre = :nombre, descripcion = :descripcion WHERE id = :id");
            $stmt->bindParam(':nombre', $nombre, PDO::PARAM_STR);
            $stmt->bindParam(':descripcion', $descripcion, PDO::PARAM_STR);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();

            if ($stmt->errorCode() !== '00000') {
                 $errorInfo = $stmt->errorInfo();
                 throw new PDOException("Database error: " . $errorInfo[2], $errorInfo[0]);
            }
            return true;
        } catch (PDOException $e) {
            error_log("MODEL ERROR: " . __CLASS__ . "::" . __FUNCTION__ . " failed: " . $e->getMessage() . " Code: " . $e->getCode());
            throw $e; // Relanzar la excepción
        }
    }

    /**
     * Elimina un formato de adquisición.
     * @param int $id
     * @return bool
     * @throws PDOException Si la eliminación falla.
     */
    public function delete(int $id)
    {
        try {
            $stmt = $this->db->prepare("DELETE FROM formatos_adquisicion WHERE id = :id");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();

            if ($stmt->errorCode() !== '00000') {
                 $errorInfo = $stmt->errorInfo();
                 throw new PDOException("Database error: " . $errorInfo[2], $errorInfo[0]);
            }
            return true;
        } catch (PDOException $e) {
            error_log("MODEL ERROR: " . __CLASS__ . "::" . __FUNCTION__ . " failed: " . $e->getMessage() . " Code: " . $e->getCode());
            throw $e; // Relanzar la excepción
        }
    }
}
