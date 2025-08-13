<?php
// app/Models/Model.php

namespace App\Models;

use PDO;
use PDOException; // Asegúrate de que esta línea esté presente

class Model
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Obtiene todos los modelos, con información del fabricante.
     * @return array|false
     */
    public function getAll()
    {
        try {
            $stmt = $this->db->query("SELECT m.id, m.nombre, m.descripcion, m.imagen_master_ruta, f.nombre AS fabricante_nombre, m.id_fabricante
                                      FROM modelos m
                                      JOIN fabricantes f ON m.id_fabricante = f.id
                                      ORDER BY m.nombre ASC");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("MODEL ERROR: " . __CLASS__ . "::" . __FUNCTION__ . " failed: " . $e->getMessage() . " Code: " . $e->getCode());
            return false;
        }
    }

    public function getByNameAndManufacturerId(string $name, int $manufacturerId)
    {
        try {
            $stmt = $this->db->prepare("SELECT id, nombre, descripcion, imagen_master_ruta FROM modelos WHERE nombre = :nombre AND id_fabricante = :id_fabricante");
            $stmt->bindParam(':nombre', $name, PDO::PARAM_STR);
            $stmt->bindParam(':id_fabricante', $manufacturerId, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("MODEL ERROR: " . __CLASS__ . "::" . __FUNCTION__ . " failed: " . $e->getMessage() . " Code: " . $e->getCode());
            return false;
        }
    }

    /**
     * Obtiene modelos filtrados por ID de fabricante.
     * @param int $manufacturerId
     * @return array|false
     */
    public function getByManufacturerId(int $manufacturerId)
    {
        try {
            $stmt = $this->db->prepare("SELECT id, nombre FROM modelos WHERE id_fabricante = :id_fabricante ORDER BY nombre ASC");
            $stmt->bindParam(':id_fabricante', $manufacturerId, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("MODEL ERROR: " . __CLASS__ . "::" . __FUNCTION__ . " failed: " . $e->getMessage() . " Code: " . $e->getCode());
            return false;
        }
    }

    /**
     * Obtiene un modelo por su ID, con información del fabricante.
     * @param int $id
     * @return array|false
     */
    public function getById(int $id)
    {
        try {
            $stmt = $this->db->prepare("SELECT m.id, m.nombre, m.descripcion, m.imagen_master_ruta, f.nombre AS fabricante_nombre, m.id_fabricante
                                      FROM modelos m
                                      JOIN fabricantes f ON m.id_fabricante = f.id
                                      WHERE m.id = :id");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("MODEL ERROR: " . __CLASS__ . "::" . __FUNCTION__ . " failed: " . $e->getMessage() . " Code: " . $e->getCode());
            return false;
        }
    }

    /**
     * Crea un nuevo modelo.
     * @param int $fabricanteId
     * @param string $nombre
     * @param string|null $descripcion
     * @param string|null $imagenMasterRuta
     * @return int ID del nuevo modelo
     * @throws PDOException Si la inserción falla.
     */
    public function create(int $fabricanteId, string $nombre, ?string $descripcion = null, ?string $imagenMasterRuta = null)
    {
        try {
            $stmt = $this->db->prepare("INSERT INTO modelos (id_fabricante, nombre, descripcion, imagen_master_ruta) VALUES (:id_fabricante, :nombre, :descripcion, :imagen_master_ruta)");
            $stmt->bindParam(':id_fabricante', $fabricanteId, PDO::PARAM_INT);
            $stmt->bindParam(':nombre', $nombre, PDO::PARAM_STR);
            $stmt->bindParam(':descripcion', $descripcion, PDO::PARAM_STR);
            $stmt->bindParam(':imagen_master_ruta', $imagenMasterRuta, PDO::PARAM_STR);
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
     * Actualiza un modelo existente.
     * @param int $id
     * @param int $fabricanteId
     * @param string $nombre
     * @param string|null $descripcion
     * @param string|null $imagenMasterRuta
     * @return bool
     * @throws PDOException Si la actualización falla.
     */
    public function update(int $id, int $fabricanteId, string $nombre, ?string $descripcion = null, ?string $imagenMasterRuta = null)
    {
        try {
            $stmt = $this->db->prepare("UPDATE modelos SET id_fabricante = :id_fabricante, nombre = :nombre, descripcion = :descripcion, imagen_master_ruta = :imagen_master_ruta WHERE id = :id");
            $stmt->bindParam(':id_fabricante', $fabricanteId, PDO::PARAM_INT);
            $stmt->bindParam(':nombre', $nombre, PDO::PARAM_STR);
            $stmt->bindParam(':descripcion', $descripcion, PDO::PARAM_STR);
            $stmt->bindParam(':imagen_master_ruta', $imagenMasterRuta, PDO::PARAM_STR);
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
     * Elimina un modelo.
     * @param int $id
     * @return bool
     * @throws PDOException Si la eliminación falla.
     */
    public function delete(int $id)
    {
        try {
            $stmt = $this->db->prepare("DELETE FROM modelos WHERE id = :id");
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
