<?php
// app/Models/Source.php

namespace App\Models;

use PDO;
use PDOException; // Asegúrate de que esta línea esté presente

class Source
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Obtiene todas las fuentes de usuario.
     * @param bool $activeOnly Si es true, solo devuelve fuentes activas.
     * @return array|false
     */
    public function getAll(bool $activeOnly = false)
    {
        try {
            $sql = "SELECT id, nombre_friendly, tipo_fuente, host, port, base_dn, bind_dn, bind_password, user_filter, group_filter, use_tls, use_ssl, ca_cert_path, timeout, activo, fecha_creacion
                    FROM fuentes_usuario";
            if ($activeOnly) {
                $sql .= " WHERE activo = 1";
            }
            $sql .= " ORDER BY nombre_friendly ASC";

            $stmt = $this->db->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("MODEL ERROR: " . __CLASS__ . "::" . __FUNCTION__ . " failed: " . $e->getMessage() . " Code: " . $e->getCode());
            return false;
        }
    }

    /**
     * Obtiene una fuente de usuario por su ID.
     * @param int $id
     * @return array|false
     */
    public function getById(int $id)
    {
        try {
            $stmt = $this->db->prepare("SELECT id, nombre_friendly, tipo_fuente, host, port, base_dn, bind_dn, bind_password, user_filter, group_filter, use_tls, use_ssl, ca_cert_path, timeout, activo, fecha_creacion
                                     FROM fuentes_usuario WHERE id = :id");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("MODEL ERROR: " . __CLASS__ . "::" . __FUNCTION__ . " failed: " . $e->getMessage() . " Code: " . $e->getCode());
            return false;
        }
    }

    /**
     * Obtiene una fuente de usuario por su nombre amigable.
     * @param string $friendlyName
     * @return array|false
     */
    public function getSourceByName(string $friendlyName)
    {
        try {
            $stmt = $this->db->prepare("SELECT id, nombre_friendly, tipo_fuente, host, port, base_dn, bind_dn, bind_password, user_filter, group_filter, use_tls, use_ssl, ca_cert_path, timeout, activo FROM fuentes_usuario WHERE nombre_friendly = :nombre_friendly");
            $stmt->bindParam(':nombre_friendly', $friendlyName, PDO::PARAM_STR);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("MODEL ERROR: " . __CLASS__ . "::" . __FUNCTION__ . " failed: " . $e->getMessage() . " Code: " . $e->getCode());
            return false;
        }
    }

    /**
     * Crea una nueva fuente de usuario.
     * @param array $data Datos de la fuente.
     * @return int ID de la nueva fuente.
     * @throws PDOException Si la inserción falla.
     */
    public function create(array $data)
    {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO fuentes_usuario (
                    nombre_friendly, tipo_fuente, host, port, base_dn, bind_dn, bind_password,
                    user_filter, group_filter, use_tls, use_ssl, ca_cert_path, timeout, activo
                ) VALUES (
                    :nombre_friendly, :tipo_fuente, :host, :port, :base_dn, :bind_dn, :bind_password,
                    :user_filter, :group_filter, :use_tls, :use_ssl, :ca_cert_path, :timeout, :activo
                )
            ");
            $stmt->bindValue(':nombre_friendly', $data['nombre_friendly'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(':tipo_fuente', $data['tipo_fuente'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(':host', $data['host'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(':port', $data['port'] ?? null, PDO::PARAM_INT);
            $stmt->bindValue(':base_dn', $data['base_dn'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(':bind_dn', $data['bind_dn'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(':bind_password', $data['bind_password'] ?? null, PDO::PARAM_STR); // Considerar cifrar esto
            $stmt->bindValue(':user_filter', $data['user_filter'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(':group_filter', $data['group_filter'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(':use_tls', (bool)($data['use_tls'] ?? false) ? 1 : 0, PDO::PARAM_INT);
            $stmt->bindValue(':use_ssl', (bool)($data['use_ssl'] ?? false) ? 1 : 0, PDO::PARAM_INT);
            $stmt->bindValue(':ca_cert_path', $data['ca_cert_path'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(':timeout', $data['timeout'] ?? null, PDO::PARAM_INT);
            $stmt->bindValue(':activo', (bool)($data['activo'] ?? false) ? 1 : 0, PDO::PARAM_INT);
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
     * Actualiza una fuente de usuario existente.
     * @param int $id
     * @param array $data Datos de la fuente.
     * @return bool
     * @throws PDOException Si la actualización falla.
     */
    public function update(int $id, array $data)
    {
        try {
            $stmt = $this->db->prepare("
                UPDATE fuentes_usuario SET
                    nombre_friendly = :nombre_friendly, tipo_fuente = :tipo_fuente, host = :host,
                    port = :port, base_dn = :base_dn, bind_dn = :bind_dn, bind_password = :bind_password,
                    user_filter = :user_filter, group_filter = :group_filter, use_tls = :use_tls,
                    use_ssl = :use_ssl, ca_cert_path = :ca_cert_path, timeout = :timeout, activo = :activo
                WHERE id = :id
            ");
            $stmt->bindValue(':nombre_friendly', $data['nombre_friendly'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(':tipo_fuente', $data['tipo_fuente'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(':host', $data['host'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(':port', $data['port'] ?? null, PDO::PARAM_INT);
            $stmt->bindValue(':base_dn', $data['base_dn'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(':bind_dn', $data['bind_dn'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(':bind_password', $data['bind_password'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(':user_filter', $data['user_filter'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(':group_filter', $data['group_filter'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(':use_tls', (bool)($data['use_tls'] ?? false) ? 1 : 0, PDO::PARAM_INT);
            $stmt->bindValue(':use_ssl', (bool)($data['use_ssl'] ?? false) ? 1 : 0, PDO::PARAM_INT);
            $stmt->bindValue(':ca_cert_path', $data['ca_cert_path'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(':timeout', $data['timeout'] ?? null, PDO::PARAM_INT);
            $stmt->bindValue(':activo', (bool)($data['activo'] ?? false) ? 1 : 0, PDO::PARAM_INT);
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
     * Elimina una fuente de usuario.
     * @param int $id
     * @return bool
     * @throws PDOException Si la eliminación falla.
     */
    public function delete(int $id)
    {
        try {
            $stmt = $this->db->prepare("DELETE FROM fuentes_usuario WHERE id = :id");
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
