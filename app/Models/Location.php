<?php
// app/Models/Location.php

namespace App\Models;

use PDO;
use PDOException; // Asegúrate de que esta línea esté presente

class Location
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Obtiene todas las ubicaciones.
     * @return array|false
     */
    public function getAll()
    {
        try {
            $stmt = $this->db->query("SELECT id, nombre, descripcion, direccion, poblacion, codigo_postal, pais FROM ubicaciones ORDER BY nombre ASC");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("MODEL ERROR: " . __CLASS__ . "::" . __FUNCTION__ . " failed: " . $e->getMessage() . " Code: " . $e->getCode());
            return false;
        }
    }

    /**
     * Obtiene una ubicación por su nombre.
     * La búsqueda es insensible a mayúsculas/minúsculas y espacios en blanco.
     * @param string $name El nombre de la ubicación a buscar (se recomienda pasarlo en minúsculas y sin espacios extra).
     * @return array|false Un array asociativo con los datos de la ubicación, o false si no se encuentra.
     */
    public function getByName(string $name)
    {
        try {
            // Usamos LOWER() y TRIM() para hacer la búsqueda flexible
            $stmt = $this->db->prepare("SELECT id, nombre, descripcion FROM ubicaciones WHERE LOWER(TRIM(nombre)) = :nombre");
            $stmt->bindParam(':nombre', $name, PDO::PARAM_STR);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("MODEL ERROR: " . __CLASS__ . "::" . __FUNCTION__ . " failed: " . $e->getMessage() . " Code: " . $e->getCode());
            return false;
        }
    }

    /**
     * Obtiene una ubicación por su ID.
     * @param int $id
     * @return array|false
     */
    public function getById(int $id)
    {
        try {
            $stmt = $this->db->prepare("SELECT * FROM ubicaciones WHERE id = :id");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("MODEL ERROR: " . __CLASS__ . "::" . __FUNCTION__ . " failed: " . $e->getMessage() . " Code: " . $e->getCode());
            return false;
        }
    }

    /**
     * Crea una nueva ubicación.
     * @param array $data Datos de la ubicación.
     * @return int ID de la nueva ubicación
     * @throws PDOException Si la inserción falla.
     */
    public function create(array $data)
    {
        try {
            $sql = "INSERT INTO ubicaciones (nombre, descripcion, direccion, codigo_postal, poblacion, provincia, pais, latitud, longitud) 
                    VALUES (:nombre, :descripcion, :direccion, :codigo_postal, :poblacion, :provincia, :pais, :latitud, :longitud)";
            $stmt = $this->db->prepare($sql);
            
            $stmt->bindValue(':nombre', $data['nombre']);
            $stmt->bindValue(':descripcion', $data['descripcion']);
            $stmt->bindValue(':direccion', $data['direccion']);
            $stmt->bindValue(':codigo_postal', $data['codigo_postal']);
            $stmt->bindValue(':poblacion', $data['poblacion']);
            $stmt->bindValue(':provincia', $data['provincia']);
            $stmt->bindValue(':pais', $data['pais']);
            $stmt->bindValue(':latitud', $data['latitud']);
            $stmt->bindValue(':longitud', $data['longitud']);

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
     * Actualiza una ubicación existente.
     * @param int $id ID de la ubicación.
     * @param array $data Datos de la ubicación.
     * @return bool
     * @throws PDOException Si la actualización falla.
     */
    public function update(int $id, array $data)
    {
        try {
            $sql = "UPDATE ubicaciones SET 
                        nombre = :nombre, descripcion = :descripcion, direccion = :direccion, 
                        codigo_postal = :codigo_postal, poblacion = :poblacion, provincia = :provincia, 
                        pais = :pais, latitud = :latitud, longitud = :longitud 
                    WHERE id = :id";
            $stmt = $this->db->prepare($sql);

            $stmt->bindValue(':nombre', $data['nombre']);
            $stmt->bindValue(':descripcion', $data['descripcion']);
            $stmt->bindValue(':direccion', $data['direccion']);
            $stmt->bindValue(':codigo_postal', $data['codigo_postal']);
            $stmt->bindValue(':poblacion', $data['poblacion']);
            $stmt->bindValue(':provincia', $data['provincia']);
            $stmt->bindValue(':pais', $data['pais']);
            $stmt->bindValue(':latitud', $data['latitud']);
            $stmt->bindValue(':longitud', $data['longitud']);
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
     * Elimina una ubicación.
     * @param int $id
     * @return bool
     * @throws PDOException Si la eliminación falla.
     */
    public function delete(int $id)
    {
        try {
            $stmt = $this->db->prepare("DELETE FROM ubicaciones WHERE id = :id");
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
