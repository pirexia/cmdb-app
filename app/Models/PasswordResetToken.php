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
            $stmt = $this->db->prepare(
                "INSERT INTO password_reset_tokens (id_usuario, token, fecha_expiracion) VALUES (:user_id, :token, :expiration_date)"
            );
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindParam(':token', $token, PDO::PARAM_STR);
            $stmt->bindParam(':expiration_date', $expirationDate, PDO::PARAM_STR);
            $stmt->execute();

            // Si la ejecución fue exitosa, devolvemos el ID del nuevo registro.
            return $this->db->lastInsertId();
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
            // El token que llega ya está hasheado por el servicio antes de llamar a este método.
            // Por lo tanto, no debemos volver a hashearlo aquí.

            $stmt = $this->db->prepare("SELECT id, id_usuario, token, fecha_expiracion, usado FROM password_reset_tokens WHERE token = :token AND usado = 0");
            $stmt->bindParam(':token', $token, PDO::PARAM_STR);
            $stmt->execute();
            $tokenData = $stmt->fetch(PDO::FETCH_ASSOC);
            return $tokenData;

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
            // Usamos UTC_TIMESTAMP() para asegurar la consistencia de la zona horaria.
            $stmt = $this->db->prepare("DELETE FROM password_reset_tokens WHERE fecha_expiracion < UTC_TIMESTAMP() OR usado = 1");
            $stmt->execute();
            return true;
        } catch (PDOException $e) {
            error_log("MODEL ERROR: " . __CLASS__ . "::" . __FUNCTION__ . " failed: " . $e->getMessage() . " Code: " . $e->getCode());
            throw $e; // Relanzar la excepción para que el servicio pueda manejarla si es necesario.
        }
    }
}
