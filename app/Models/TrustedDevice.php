<?php
// app/Models/TrustedDevice.php

namespace App\Models;

use PDO;
use DateTime;

/**
 * Modelo para gestionar la tabla de dispositivos de confianza (MFA).
 */
class TrustedDevice
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Crea un nuevo registro de dispositivo de confianza.
     *
     * @param int $userId
     * @param string $tokenHash
     * @param string $expirationDate
     * @param string|null $userAgent
     * @param string|null $ipAddress
     * @return bool
     */
    public function create(int $userId, string $tokenHash, string $expirationDate, ?string $userAgent, ?string $ipAddress): bool
    {
        $stmt = $this->db->prepare(
            "INSERT INTO dispositivos_confianza (id_usuario, token_hash, fecha_expiracion, user_agent, ip_address) 
             VALUES (:id_usuario, :token_hash, :fecha_expiracion, :user_agent, :ip_address)"
        );
        return $stmt->execute([
            'id_usuario' => $userId,
            'token_hash' => $tokenHash,
            'fecha_expiracion' => $expirationDate,
            'user_agent' => $userAgent,
            'ip_address' => $ipAddress
        ]);
    }

    /**
     * Busca un dispositivo por el hash de su token.
     *
     * @param string $tokenHash
     * @return array|false
     */
    public function findByTokenHash(string $tokenHash): array|false
    {
        $stmt = $this->db->prepare("SELECT * FROM dispositivos_confianza WHERE token_hash = :token_hash");
        $stmt->execute(['token_hash' => $tokenHash]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Busca todos los dispositivos de confianza para un usuario específico.
     *
     * @param int $userId
     * @return array|false
     */
    public function findByUserId(int $userId): array|false
    {
        $stmt = $this->db->prepare(
            "SELECT id, token_hash, fecha_expiracion, fecha_creacion, user_agent, ip_address FROM dispositivos_confianza WHERE id_usuario = :id_usuario ORDER BY fecha_creacion DESC"
        );
        $stmt->execute(['id_usuario' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Elimina un dispositivo de confianza por el hash de su token.
     *
     * @param string $tokenHash
     * @return bool
     */
    public function deleteByTokenHash(string $tokenHash): bool
    {
        $stmt = $this->db->prepare("DELETE FROM dispositivos_confianza WHERE token_hash = :token_hash");
        return $stmt->execute([':token_hash' => $tokenHash]);
    }

    /**
     * Elimina todos los tokens de dispositivos de confianza expirados.
     *
     * @return int El número de filas eliminadas.
     */
    public function deleteExpired(): int
    {
        $stmt = $this->db->prepare("DELETE FROM dispositivos_confianza WHERE fecha_expiracion < NOW()");
        $stmt->execute();
        return $stmt->rowCount();
    }

    /**
     * Elimina todos los dispositivos de confianza para un usuario específico.
     * Útil cuando el usuario deshabilita MFA.
     *
     * @param int $userId
     * @return int
     */
    public function deleteAllForUser(int $userId): int
    {
        $stmt = $this->db->prepare("DELETE FROM dispositivos_confianza WHERE id_usuario = :id_usuario");
        $stmt->execute(['id_usuario' => $userId]);
        return $stmt->rowCount();
    }
}