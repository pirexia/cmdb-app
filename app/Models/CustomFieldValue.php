<?php
// app/Models/CustomFieldValue.php

namespace App\Models;

use PDO;
use PDOException; // Asegúrate de que esta línea esté presente

class CustomFieldValue
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Obtiene los valores de campos personalizados para un activo específico.
     * Incluye la definición del campo.
     * @param int $assetId
     * @return array|false
     */
    public function getByAssetId(int $assetId)
    {
        try {
            $stmt = $this->db->prepare("
                SELECT cfv.id, cfv.id_activo, cfv.id_definicion_campo, cfv.valor,
                       cfd.nombre_campo, cfd.tipo_dato, cfd.es_requerido, cfd.opciones_lista, cfd.unidad, cfd.descripcion AS definicion_descripcion
                FROM campos_personalizados_valores cfv
                JOIN campos_personalizados_definicion cfd ON cfv.id_definicion_campo = cfd.id
                WHERE cfv.id_activo = :id_activo
                ORDER BY cfd.nombre_campo ASC
            ");
            $stmt->bindParam(':id_activo', $assetId, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("MODEL ERROR: " . __CLASS__ . "::" . __FUNCTION__ . " failed: " . $e->getMessage() . " Code: " . $e->getCode());
            return false;
        }
    }

    /**
     * Obtiene un valor de campo personalizado específico por su ID.
     * @param int $id
     * @return array|false
     */
    public function getById(int $id)
    {
        try {
            $stmt = $this->db->prepare("
                SELECT cfv.id, cfv.id_activo, cfv.id_definicion_campo, cfv.valor,
                       cfd.nombre_campo, cfd.tipo_dato, cfd.es_requerido, cfd.opciones_lista, cfd.unidad, cfd.descripcion AS definicion_descripcion
                FROM campos_personalizados_valores cfv
                JOIN campos_personalizados_definicion cfd ON cfv.id_definicion_campo = cfd.id
                WHERE cfv.id = :id
            ");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("MODEL ERROR: " . __CLASS__ . "::" . __FUNCTION__ . " failed: " . $e->getMessage() . " Code: " . $e->getCode());
            return false;
        }
    }

    /**
     * Crea o actualiza un valor de campo personalizado para un activo.
     * Si el valor es null o una cadena vacía, intentará guardarlo como NULL en la DB.
     * @param int $assetId
     * @param int $fieldDefinitionId
     * @param string|null $value
     * @return int ID del valor.
     * @throws PDOException Si la operación falla.
     */
    public function createOrUpdate(int $assetId, int $fieldDefinitionId, ?string $value)
    {
        try {
            // Intenta encontrar el valor existente
            $stmt = $this->db->prepare("SELECT id FROM campos_personalizados_valores WHERE id_activo = :id_activo AND id_definicion_campo = :id_definicion_campo");
            $stmt->bindParam(':id_activo', $assetId, PDO::PARAM_INT);
            $stmt->bindParam(':id_definicion_campo', $fieldDefinitionId, PDO::PARAM_INT);
            $stmt->execute();
            $existingId = $stmt->fetchColumn();

            // Determinar el tipo PDO::PARAM para el valor (NULL si es null, STR si es string)
            $paramType = ($value === null) ? PDO::PARAM_NULL : PDO::PARAM_STR;

            if ($existingId) {
                // Actualizar
                $updateStmt = $this->db->prepare("UPDATE campos_personalizados_valores SET valor = :valor WHERE id = :id");
                $updateStmt->bindValue(':valor', $value, $paramType);
                $updateStmt->bindParam(':id', $existingId, PDO::PARAM_INT);
                $updateStmt->execute();

                // === ¡AÑADE ESTA COMPROBACIÓN DESPUÉS DE CADA $stmt->execute()! ===
                if ($updateStmt->errorCode() !== '00000') {
                     $errorInfo = $updateStmt->errorInfo();
                     throw new PDOException("Database error: " . $errorInfo[2], $errorInfo[0]);
                }
                // ==================================================================
                return $existingId;
            } else {
                // Crear
                $insertStmt = $this->db->prepare("INSERT INTO campos_personalizados_valores (id_activo, id_definicion_campo, valor) VALUES (:id_activo, :id_definicion_campo, :valor)");
                $insertStmt->bindParam(':id_activo', $assetId, PDO::PARAM_INT);
                $insertStmt->bindParam(':id_definicion_campo', $fieldDefinitionId, PDO::PARAM_INT);
                $insertStmt->bindValue(':valor', $value, $paramType);
                $insertStmt->execute();

                if ($insertStmt->errorCode() !== '00000') {
                     $errorInfo = $insertStmt->errorInfo();
                     throw new PDOException("Database error: " . $errorInfo[2], $errorInfo[0]);
                }
                return $this->db->lastInsertId();
            }
        } catch (PDOException $e) {
            error_log("MODEL ERROR: " . __CLASS__ . "::" . __FUNCTION__ . " failed: " . $e->getMessage() . " Code: " . $e->getCode());
            throw $e; // Relanzar la excepción
        }
    }

    /**
     * Elimina un valor de campo personalizado específico para un activo y definición.
     * Útil para limpiar valores de campos no requeridos si se dejan en blanco.
     * @param int $assetId
     * @param int $fieldDefinitionId
     * @return bool
     * @throws PDOException Si la eliminación falla.
     */
    public function deleteAllForAssetAndDefinition(int $assetId, int $fieldDefinitionId): bool
    {
        try {
            $stmt = $this->db->prepare("DELETE FROM campos_personalizados_valores WHERE id_activo = :id_activo AND id_definicion_campo = :id_definicion_campo");
            $stmt->bindParam(':id_activo', $assetId, PDO::PARAM_INT);
            $stmt->bindParam(':id_definicion_campo', $fieldDefinitionId, PDO::PARAM_INT);
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
     * Elimina todos los valores de campos personalizados para un activo dado.
     * Útil cuando se cambia el tipo de activo de un activo existente o cuando se elimina el activo.
     * @param int $assetId
     * @return bool
     * @throws PDOException Si la eliminación falla.
     */
    public function deleteAllForAsset(int $assetId): bool
    {
        try {
            $stmt = $this->db->prepare("DELETE FROM campos_personalizados_valores WHERE id_activo = :id_activo");
            $stmt->bindParam(':id_activo', $assetId, PDO::PARAM_INT);
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
