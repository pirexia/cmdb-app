<?php
// app/Models/PasswordResetToken.php

namespace App\Models;

use PDO;
use PDOException; // Asegúrate de que esta línea esté presente

class PasswordResetToken
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Crea un nuevo token de reseteo de contraseña.
     * @param int $userId
     * @param string $token
     * @param string $expirationDate (formato 'YYYY-MM-DD HH:MM:SS')
     * @return int ID del nuevo token
     * @throws PDOException Si la inserción falla.
     */
    public function createToken(int $userId, string $token, string $expirationDate)
    {
        try {
            $stmt = $this->db->prepare("INSERT INTO password_reset_tokens (id_usuario, token, fecha_expiracion) VALUES (:user_id, :token, :expiration_date)");
            $stmt->bindParam(':user_user_id', $userId, PDO::PARAM_INT); // Usar :id_usuario de la tabla
            $stmt->bindParam(':token', $token, PDO::PARAM_STR);
            $stmt->bindParam(':expiration_date', $expirationDate, PDO::PARAM_STR);
            $stmt->execute();

            // === ¡AÑADE ESTA COMPROBACIÓN DESPUÉS DE CADA $stmt->execute()! ===
            if ($stmt->errorCode() !== '00000') {
                 $errorInfo = $stmt->errorInfo();
                 throw new PDOException("Database error: " . $errorInfo[2], $errorInfo[0]);
            }
            // ==================================================================
            
            $lastInsertId = $this->db->lastInsertId();
            if ($lastInsertId) {
                // error_log("DEBUG: Token creado con ID: {$lastInsertId} para usuario {$userId}. Token: {$token}. Expiración: {$expirationDate}"); // Log de depuración
                return $lastInsertId;
            } else {
                // Si no se obtuvo ID y no hubo error SQL explícito, es un fallo inexplicable.
                throw new PDOException("Failed to retrieve lastInsertId after token creation for user ID {$userId}.");
            }
        } catch (PDOException $e) {
            error_log("MODEL ERROR: " . __CLASS__ . "::" . __FUNCTION__ . " failed: " . $e->getMessage() . " Code: " . $e->getCode());
            throw $e; // Relanzar la excepción
        }
    }

    /**
     * Obtiene un token de reseteo de contraseña por su valor.
     * @param string $token
     * @return array|false
     */
    public function getToken(string $token)
    {
        try {
            $stmt = $this->db->prepare("SELECT id, id_usuario, token, fecha_expiracion, usado FROM password_reset_tokens WHERE token = :token AND usado = 0");
            $stmt->bindParam(':token', $token, PDO::PARAM_STR);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("MODEL ERROR: " . __CLASS__ . "::" . __FUNCTION__ . " failed: " . $e->getMessage() . " Code: " . $e->getCode());
            return false;
        }
    }

    /**
     * Marca un token como usado.
     * @param int $id
     * @return bool
     * @throws PDOException Si la actualización falla.
     */
    public function markTokenAsUsed(int $id)
    {
        try {
            $stmt = $this->db->prepare("UPDATE password_reset_tokens SET usado = 1 WHERE id = :id");
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
     * Elimina tokens expirados o usados para limpieza.
     * @return bool
     * @throws PDOException Si la eliminación falla.
     */
    public function cleanExpiredTokens()
    {
        try {
            $stmt = $this->db->prepare("DELETE FROM password_reset_tokens WHERE fecha_expiracion < NOW() OR usado = 1");
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
