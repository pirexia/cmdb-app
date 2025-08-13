<?php
// app/Models/FileAttachment.php

namespace App\Models;

use PDO;
use PDOException; // Asegúrate de que esta línea esté presente

class FileAttachment
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Obtiene todos los archivos adjuntos para un activo específico.
     * @param int $assetId
     * @return array|false
     */
    public function getByAssetId(int $assetId)
    {
        try {
            $stmt = $this->db->prepare("
                SELECT id, id_activo, nombre_original, ruta_almacenamiento, tipo_mime, tamano_bytes, fecha_subida, id_usuario_subida
                FROM archivos_adjuntos
                WHERE id_activo = :id_activo
                ORDER BY nombre_original ASC
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
     * Obtiene un archivo adjunto por su ID.
     * @param int $id
     * @return array|false
     */
    public function getById(int $id)
    {
        try {
            $stmt = $this->db->prepare("
                SELECT id, id_activo, nombre_original, ruta_almacenamiento, tipo_mime, tamano_bytes, fecha_subida, id_usuario_subida
                FROM archivos_adjuntos
                WHERE id = :id
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
     * Crea un nuevo registro de archivo adjunto.
     * @param int $assetId
     * @param string $originalName
     * @param string $storagePath
     * @param string|null $mimeType
     * @param int|null $sizeBytes
     * @param int|null $userId
     * @return int ID del nuevo archivo
     * @throws PDOException Si la inserción falla.
     */
    public function create(int $assetId, string $originalName, string $storagePath, ?string $mimeType = null, ?int $sizeBytes = null, ?int $userId = null)
    {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO archivos_adjuntos (id_activo, nombre_original, ruta_almacenamiento, tipo_mime, tamano_bytes, id_usuario_subida)
                VALUES (:id_activo, :nombre_original, :ruta_almacenamiento, :tipo_mime, :tamano_bytes, :id_usuario_subida)
            ");
            $stmt->bindParam(':id_activo', $assetId, PDO::PARAM_INT);
            $stmt->bindParam(':nombre_original', $originalName, PDO::PARAM_STR);
            $stmt->bindParam(':ruta_almacenamiento', $storagePath, PDO::PARAM_STR);
            $stmt->bindValue(':tipo_mime', $mimeType, PDO::PARAM_STR);
            $stmt->bindValue(':tamano_bytes', $sizeBytes, PDO::PARAM_INT);
            $stmt->bindValue(':id_usuario_subida', $userId, PDO::PARAM_INT);
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
     * Elimina un archivo adjunto por su ID.
     * @param int $id
     * @return bool
     * @throws PDOException Si la eliminación falla.
     */
    public function delete(int $id)
    {
        try {
            $stmt = $this->db->prepare("DELETE FROM archivos_adjuntos WHERE id = :id");
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
     * Elimina todos los archivos adjuntos para un activo dado (útil al eliminar el activo).
     * @param int $assetId
     * @return bool
     * @throws PDOException Si la eliminación falla.
     */
    public function deleteAllForAsset(int $assetId): bool
    {
        try {
            $stmt = $this->db->prepare("DELETE FROM archivos_adjuntos WHERE id_activo = :id_activo");
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
