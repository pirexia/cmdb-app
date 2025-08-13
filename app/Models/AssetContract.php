<?php
// app/Models/AssetContract.php

namespace App\Models;

use PDO;
use PDOException; // Asegúrate de que esta línea esté presente

class AssetContract
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Obtiene todos los contratos asociados a un activo específico.
     * @param int $assetId
     * @return array|false
     */
    public function getContractsByAssetId(int $assetId)
    {
        try {
            $stmt = $this->db->prepare("
                SELECT
                    ca.id_contrato,
                    ca.id_activo,
                    ca.fecha_asociacion,
                    c.numero_contrato,
                    c.fecha_inicio,
                    c.fecha_fin,
                    ct.nombre AS tipo_contrato_nombre,
                    p.nombre AS proveedor_nombre
                FROM
                    contrato_activo ca
                JOIN contratos c ON ca.id_contrato = c.id
                LEFT JOIN tipos_contrato ct ON c.id_tipo_contrato = ct.id
                LEFT JOIN proveedores p ON c.id_proveedor = p.id
                WHERE ca.id_activo = :id_activo
                ORDER BY c.numero_contrato ASC
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
     * Asocia un contrato a un activo.
     * @param int $assetId
     * @param int $contractId
     * @return bool
     * @throws PDOException Si la asociación falla.
     */
    public function associate(int $assetId, int $contractId): bool
    {
        try {
            // Verificar si la asociación ya existe para evitar duplicados y errores
            $checkStmt = $this->db->prepare("SELECT COUNT(*) FROM contrato_activo WHERE id_activo = :id_activo AND id_contrato = :id_contrato");
            $checkStmt->bindParam(':id_activo', $assetId, PDO::PARAM_INT);
            $checkStmt->bindParam(':id_contrato', $contractId, PDO::PARAM_INT);
            $checkStmt->execute();
            if ($checkStmt->fetchColumn() > 0) {
                // Asociación ya existe, no es un error, simplemente no insertamos de nuevo
                return true;
            }

            $stmt = $this->db->prepare("INSERT INTO contrato_activo (id_activo, id_contrato) VALUES (:id_activo, :id_contrato)");
            $stmt->bindParam(':id_activo', $assetId, PDO::PARAM_INT);
            $stmt->bindParam(':id_contrato', $contractId, PDO::PARAM_INT);
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
     * Desasocia un contrato de un activo.
     * @param int $assetId
     * @param int $contractId
     * @return bool
     * @throws PDOException Si la desasociación falla.
     */
    public function disassociate(int $assetId, int $contractId): bool
    {
        try {
            $stmt = $this->db->prepare("DELETE FROM contrato_activo WHERE id_activo = :id_activo AND id_contrato = :id_contrato");
            $stmt->bindParam(':id_activo', $assetId, PDO::PARAM_INT);
            $stmt->bindParam(':id_contrato', $contractId, PDO::PARAM_INT);
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
     * Elimina todas las asociaciones de contratos para un activo dado (útil al eliminar el activo).
     * @param int $assetId
     * @return bool
     * @throws PDOException Si la eliminación falla.
     */
    public function deleteAllForAsset(int $assetId): bool
    {
        try {
            $stmt = $this->db->prepare("DELETE FROM contrato_activo WHERE id_activo = :id_activo");
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
