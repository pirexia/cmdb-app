<?php
// app/Models/Role.php

namespace App\Models;

use PDO;
use PDOException;

class Role
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Obtiene todos los roles.
     * @return array|false
     */
    public function getAllRoles()
    {
        try {
            $stmt = $this->db->query("SELECT id, nombre, descripcion FROM roles ORDER BY nombre ASC");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error al obtener roles: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtiene un rol por su ID.
     * @param int $id
     * @return array|false
     */
    public function getRoleById(int $id)
    {
        try {
            $stmt = $this->db->prepare("SELECT id, nombre, descripcion FROM roles WHERE id = :id");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error al obtener rol por ID: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtiene un rol por su nombre.
     * @param string $nombre
     * @return array|false
     */
    public function getRoleByName(string $nombre)
    {
        try {
            $stmt = $this->db->prepare("SELECT id, nombre, descripcion FROM roles WHERE nombre = :nombre");
            $stmt->bindParam(':nombre', $nombre, PDO::PARAM_STR);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error al obtener rol por nombre: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Crea un nuevo rol.
     * @param string $nombre
     * @param string|null $descripcion
     * @return int|false ID del nuevo rol o false si falla
     */
    public function createRole(string $nombre, ?string $descripcion = null)
    {
        try {
            $stmt = $this->db->prepare("INSERT INTO roles (nombre, descripcion) VALUES (:nombre, :descripcion)");
            $stmt->bindParam(':nombre', $nombre, PDO::PARAM_STR);
            $stmt->bindParam(':descripcion', $descripcion, PDO::PARAM_STR);
            $stmt->execute();
            return $this->db->lastInsertId();
        } catch (PDOException $e) {
            error_log("Error al crear rol: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Actualiza un rol existente.
     * @param int $id
     * @param string $nombre
     * @param string|null $descripcion
     * @return bool
     */
    public function updateRole(int $id, string $nombre, ?string $descripcion = null)
    {
        try {
            $stmt = $this->db->prepare("UPDATE roles SET nombre = :nombre, descripcion = :descripcion WHERE id = :id");
            $stmt->bindParam(':nombre', $nombre, PDO::PARAM_STR);
            $stmt->bindParam(':descripcion', $descripcion, PDO::PARAM_STR);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error al actualizar rol: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Elimina un rol.
     * @param int $id
     * @return bool
     */
    public function deleteRole(int $id)
    {
        try {
            // Considerar si es necesario verificar dependencias (usuarios asociados) antes de eliminar
            $stmt = $this->db->prepare("DELETE FROM roles WHERE id = :id");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            // MySQL/MariaDB debería dar un error de restricción de clave foránea si hay usuarios
            error_log("Error al eliminar rol: " . $e->getMessage());
            return false;
        }
    }
}
