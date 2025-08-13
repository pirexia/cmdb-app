<?php
// app/Models/CustomFieldDefinition.php

namespace App\Models;

use PDO;
use PDOException; // Asegúrate de que esta línea esté presente

class CustomFieldDefinition
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Obtiene todas las definiciones de campos personalizados, opcionalmente por tipo de activo.
     * @param int|null $assetTypeId Si se proporciona, filtra por este tipo de activo.
     * @return array|false
     */
    public function getAll(?int $assetTypeId = null)
    {
        try {
            $sql = "SELECT cfd.id, cfd.id_tipo_activo, cfd.nombre_campo, cfd.tipo_dato, cfd.es_requerido, cfd.opciones_lista, cfd.unidad, cfd.descripcion, ta.nombre AS tipo_activo_nombre
                    FROM campos_personalizados_definicion cfd
                    JOIN tipos_activos ta ON cfd.id_tipo_activo = ta.id";
            if ($assetTypeId !== null) {
                $sql .= " WHERE cfd.id_tipo_activo = :id_tipo_activo";
            }
            $sql .= " ORDER BY ta.nombre ASC, cfd.nombre_campo ASC";

            $stmt = $this->db->prepare($sql);
            if ($assetTypeId !== null) {
                $stmt->bindParam(':id_tipo_activo', $assetTypeId, PDO::PARAM_INT);
            }
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("MODEL ERROR: " . __CLASS__ . "::" . __FUNCTION__ . " failed: " . $e->getMessage() . " Code: " . $e->getCode());
            return false;
        }
    }

    /**
     * Obtiene una definición de campo personalizado por su ID.
     * @param int $id
     * @return array|false
     */
    public function getById(int $id)
    {
        try {
            $stmt = $this->db->prepare("SELECT cfd.id, cfd.id_tipo_activo, cfd.nombre_campo, cfd.tipo_dato, cfd.es_requerido, cfd.opciones_lista, cfd.unidad, cfd.descripcion, ta.nombre AS tipo_activo_nombre
                                     FROM campos_personalizados_definicion cfd
                                     JOIN tipos_activos ta ON cfd.id_tipo_activo = ta.id
                                     WHERE cfd.id = :id");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("MODEL ERROR: " . __CLASS__ . "::" . __FUNCTION__ . " failed: " . $e->getMessage() . " Code: " . $e->getCode());
            return false;
        }
    }

    /**
     * Crea una nueva definición de campo personalizado.
     * @param array $data Los datos del campo.
     * @return int ID del nuevo campo
     * @throws PDOException Si la inserción falla.
     */
    public function create(array $data)
    {
        try {
            $stmt = $this->db->prepare("INSERT INTO campos_personalizados_definicion (id_tipo_activo, nombre_campo, tipo_dato, es_requerido, opciones_lista, unidad, descripcion) VALUES (:id_tipo_activo, :nombre_campo, :tipo_dato, :es_requerido, :opciones_lista, :unidad, :descripcion)");
            $stmt->bindValue(':id_tipo_activo', $data['id_tipo_activo'] ?? null, PDO::PARAM_INT);
            $stmt->bindValue(':nombre_campo', $data['nombre_campo'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(':tipo_dato', $data['tipo_dato'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(':es_requerido', (bool)($data['es_requerido'] ?? false) ? 1 : 0, PDO::PARAM_INT);
            $stmt->bindValue(':opciones_lista', $data['opciones_lista'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(':unidad', $data['unidad'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(':descripcion', $data['descripcion'] ?? null, PDO::PARAM_STR);
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
     * Actualiza una definición de campo personalizado existente.
     * @param int $id
     * @param array $data Los datos del campo.
     * @return bool
     * @throws PDOException Si la actualización falla.
     */
    public function update(int $id, array $data)
    {
        try {
            $stmt = $this->db->prepare("UPDATE campos_personalizados_definicion SET id_tipo_activo = :id_tipo_activo, nombre_campo = :nombre_campo, tipo_dato = :tipo_dato, es_requerido = :es_requerido, opciones_lista = :opciones_lista, unidad = :unidad, descripcion = :descripcion WHERE id = :id");
            $stmt->bindValue(':id_tipo_activo', $data['id_tipo_activo'] ?? null, PDO::PARAM_INT);
            $stmt->bindValue(':nombre_campo', $data['nombre_campo'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(':tipo_dato', $data['tipo_dato'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(':es_requerido', (bool)($data['es_requerido'] ?? false) ? 1 : 0, PDO::PARAM_INT);
            $stmt->bindValue(':opciones_lista', $data['opciones_lista'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(':unidad', $data['unidad'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(':descripcion', $data['descripcion'] ?? null, PDO::PARAM_STR);
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
     * Elimina una definición de campo personalizado.
     * @param int $id
     * @return bool
     * @throws PDOException Si la eliminación falla.
     */
    public function delete(int $id)
    {
        try {
            $stmt = $this->db->prepare("DELETE FROM campos_personalizados_definicion WHERE id = :id");
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
