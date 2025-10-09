<?php
// app/Models/Contract.php

namespace App\Models;

use PDO;
use PDOException; // Asegúrate de que esta línea esté presente

class Contract
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Obtiene todos los contratos con información del tipo de contrato y proveedor.
     * @return array|false
     */
    public function getAll()
    {
        try {
            $stmt = $this->db->query("
                SELECT
                    c.id,
                    c.numero_contrato,
                    c.fecha_inicio,
                    c.fecha_fin,
                    c.costo_anual,
                    c.descripcion,
                    ct.nombre AS tipo_contrato_nombre,
                    p.nombre AS proveedor_nombre
                FROM
                    contratos c
                LEFT JOIN tipos_contrato ct ON c.id_tipo_contrato = ct.id
                LEFT JOIN proveedores p ON c.id_proveedor = p.id
                ORDER BY c.numero_contrato ASC
            ");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("MODEL ERROR: " . __CLASS__ . "::" . __FUNCTION__ . " failed: " . $e->getMessage() . " Code: " . $e->getCode());
            return false;
        }
    }

    /**
     * Obtiene un contrato por su número.
     * La búsqueda es insensible a mayúsculas/minúsculas y espacios en blanco.
     * @param string $contractNumber El número de contrato a buscar (se recomienda pasarlo en minúsculas y sin espacios extra).
     * @return array|false
     */
    public function getByContractNumber(string $contractNumber): array|false
    {
        try {
            // Usamos LOWER() y TRIM() para hacer la búsqueda flexible
            $stmt = $this->db->prepare("SELECT * FROM contratos WHERE LOWER(TRIM(numero_contrato)) = :numero_contrato");
            $stmt->bindParam(':numero_contrato', $contractNumber, PDO::PARAM_STR);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("MODEL ERROR: " . __CLASS__ . "::" . __FUNCTION__ . " failed: " . $e->getMessage() . " Code: " . $e->getCode());
            return false;
        }
    }

    /**
     * Obtiene un contrato por su ID con toda su información.
     * @param int $id
     */
    public function getById(int $id)
    {
        try {
            $stmt = $this->db->prepare("
                SELECT
                    c.id,
                    c.numero_contrato,
                    c.id_tipo_contrato,
                    c.id_proveedor,
                    c.fecha_inicio,
                    c.fecha_fin,
                    c.costo_anual,
                    c.descripcion,
                    ct.nombre AS tipo_contrato_nombre,
                    p.nombre AS proveedor_nombre
                FROM
                    contratos c
                LEFT JOIN tipos_contrato ct ON c.id_tipo_contrato = ct.id
                LEFT JOIN proveedores p ON c.id_proveedor = p.id
                WHERE c.id = :id
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
     * Crea un nuevo contrato.
     * @param array $data Los datos del contrato.
     * @return int ID del nuevo contrato.
     * @throws PDOException Si la inserción falla.
     */
    public function create(array $data)
    {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO contratos (numero_contrato, id_tipo_contrato, id_proveedor, fecha_inicio, fecha_fin, costo_anual, descripcion)
                VALUES (:numero_contrato, :id_tipo_contrato, :id_proveedor, :fecha_inicio, :fecha_fin, :costo_anual, :descripcion)
            ");
            $stmt->bindValue(':numero_contrato', $data['numero_contrato'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(':id_tipo_contrato', $data['id_tipo_contrato'] ?? null, PDO::PARAM_INT);
            $stmt->bindValue(':id_proveedor', $data['id_proveedor'] ?? null, PDO::PARAM_INT);
            $stmt->bindValue(':fecha_inicio', $data['fecha_inicio'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(':fecha_fin', $data['fecha_fin'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(':costo_anual', $data['costo_anual'] ?? null, PDO::PARAM_STR);
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
     * Actualiza un contrato existente.
     * @param int $id
     * @param array $data Los datos del contrato.
     * @return bool
     * @throws PDOException Si la actualización falla.
     */
    public function update(int $id, array $data)
    {
        try {
            $stmt = $this->db->prepare("
                UPDATE contratos SET
                    numero_contrato = :numero_contrato,
                    id_tipo_contrato = :id_tipo_contrato,
                    id_proveedor = :id_proveedor,
                    fecha_inicio = :fecha_inicio,
                    fecha_fin = :fecha_fin,
                    costo_anual = :costo_anual,
                    descripcion = :descripcion
                WHERE id = :id
            ");
            $stmt->bindValue(':numero_contrato', $data['numero_contrato'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(':id_tipo_contrato', $data['id_tipo_contrato'] ?? null, PDO::PARAM_INT);
            $stmt->bindValue(':id_proveedor', $data['id_proveedor'] ?? null, PDO::PARAM_INT);
            $stmt->bindValue(':fecha_inicio', $data['fecha_inicio'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(':fecha_fin', $data['fecha_fin'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(':costo_anual', $data['costo_anual'] ?? null, PDO::PARAM_STR);
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
     * Elimina un contrato.
     * @param int $id
     * @return bool
     * @throws PDOException Si la eliminación falla.
     */
    public function delete(int $id)
    {
        try {
            $stmt = $this->db->prepare("DELETE FROM contratos WHERE id = :id");
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
    public function countAll()
    {
        try {
            $stmt = $this->db->query("SELECT COUNT(id) FROM contratos");
            return (int)$stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("MODEL ERROR: " . __CLASS__ . "::" . __FUNCTION__ . " failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtiene contratos próximos a caducar.
     * @param string $thresholdDate Fecha límite (YYYY-MM-DD)
     * @return array|false
     */
    public function getExpiringContracts(string $thresholdDate): array|false
    {
        try {
            $today = date('Y-m-d');
            $sql = "
                SELECT
                    c.id, c.numero_contrato, ct.nombre AS tipo_contrato_nombre, p.nombre AS proveedor_nombre, c.fecha_fin
                FROM contratos c
                LEFT JOIN tipos_contrato ct ON c.id_tipo_contrato = ct.id
                LEFT JOIN proveedores p ON c.id_proveedor = p.id
                WHERE c.fecha_fin BETWEEN :today AND :threshold_date
                ORDER BY c.fecha_fin ASC
            ";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':today' => $today, ':threshold_date' => $thresholdDate]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("MODEL ERROR: " . __CLASS__ . "::" . __FUNCTION__ . " failed: " . $e->getMessage());
            return false;
        }
    }
}
