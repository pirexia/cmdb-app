<?php
// app/Models/ArchivoAdjunto.php

namespace App\Models;

use PDO;
use PDOException;

class ArchivoAdjunto
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Obtiene todos los archivos adjuntos para un activo dado.
     * @param int $assetId
     * @return array|false
     */
    public function getByAssetId(int $assetId)
    {
        try {
            $stmt = $this->db->prepare("SELECT id, id_activo, nombre_original, ruta_almacenamiento, tipo_mime, tamano_bytes, fecha_subida FROM archivos_adjuntos WHERE id_activo = :id_activo ORDER BY fecha_subida DESC");
            $stmt->bindParam(':id_activo', $assetId, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error al obtener archivos adjuntos para activo $assetId: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Crea un nuevo registro de archivo adjunto.
     * @param array $data (id_activo, nombre_original, ruta_almacenamiento, tipo_mime, tamano_bytes, id_usuario_subida)
     * @return int|false ID del nuevo archivo adjunto o false si falla.
     */
    public function create(array $data)
    {
        try {
            $stmt = $this->db->prepare("INSERT INTO archivos_adjuntos (id_activo, nombre_original, ruta_almacenamiento, tipo_mime, tamano_bytes, id_usuario_subida) VALUES (:id_activo, :nombre_original, :ruta_almacenamiento, :tipo_mime, :tamano_bytes, :id_usuario_subida)");
            $stmt->bindValue(':id_activo', $data['id_activo'], PDO::PARAM_INT);
            $stmt->bindValue(':nombre_original', $data['nombre_original'], PDO::PARAM_STR);
            $stmt->bindValue(':ruta_almacenamiento', $data['ruta_almacenamiento'], PDO::PARAM_STR);
            $stmt->bindValue(':tipo_mime', $data['tipo_mime'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(':tamano_bytes', $data['tamano_bytes'] ?? null, PDO::PARAM_INT);
            $stmt->bindValue(':id_usuario_subida', $data['id_usuario_subida'] ?? null, PDO::PARAM_INT);
            $stmt->execute();
            return $this->db->lastInsertId();
        } catch (PDOException $e) {
            error_log("Error al crear registro de archivo adjunto: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Elimina un registro de archivo adjunto y su archivo físico.
     * @param int $id ID del archivo adjunto.
     * @return bool
     */
    public function delete(int $id)
    {
        // NOTA: La eliminación del archivo físico DEBE hacerse en el controlador o servicio
        // antes de llamar a este método de la DB, para manejar excepciones de unlink.
        try {
            $stmt = $this->db->prepare("DELETE FROM archivos_adjuntos WHERE id = :id");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error al eliminar registro de archivo adjunto: " . $e->getMessage());
            return false;
        }
    }
}
