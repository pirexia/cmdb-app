<?php
// app/Models/PasswordHistory.php

namespace App\Models;

use PDO;
use Psr\Log\LoggerInterface;

class PasswordHistory
{
    private PDO $db;
    private LoggerInterface $logger;
    private const HISTORY_LIMIT = 20;

    public function __construct(PDO $db, LoggerInterface $logger)
    {
        $this->db = $db;
        $this->logger = $logger;
    }

    /**
     * Añade un nuevo hash de contraseña al historial de un usuario.
     * También se encarga de purgar el historial si excede el límite.
     * @param int $userId
     * @param string $passwordHash
     * @return bool
     */
    public function add(int $userId, string $passwordHash): bool
    {
        $this->db->beginTransaction();
        try {
            // Insertar el nuevo hash
            $stmt = $this->db->prepare("INSERT INTO password_history (id_usuario, password_hash) VALUES (:user_id, :password_hash)");
            $stmt->execute([':user_id' => $userId, ':password_hash' => $passwordHash]);

            // Purgar hashes antiguos si se excede el límite
            $stmt = $this->db->prepare("
                DELETE FROM password_history
                WHERE id IN (
                    SELECT id FROM (
                        SELECT id FROM password_history
                        WHERE id_usuario = :user_id
                        ORDER BY fecha_creacion DESC
                        LIMIT -1 OFFSET :limit
                    ) as t
                )
            ");
            $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindValue(':limit', self::HISTORY_LIMIT, PDO::PARAM_INT);
            $stmt->execute();

            $this->db->commit();
            return true;
        } catch (\PDOException $e) {
            $this->db->rollBack();
            $this->logger->error("Error al añadir al historial de contraseñas para el usuario {$userId}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Verifica si un hash de contraseña ya existe en el historial de un usuario.
     * @param int $userId
     * @param string $newPassword La contraseña en texto plano para verificar.
     * @return bool
     */
    public function isPasswordInHistory(int $userId, string $newPassword): bool
    {
        $stmt = $this->db->prepare("SELECT password_hash FROM password_history WHERE id_usuario = :user_id");
        $stmt->execute([':user_id' => $userId]);
        $history = $stmt->fetchAll(PDO::FETCH_COLUMN);

        foreach ($history as $oldHash) {
            if (password_verify($newPassword, $oldHash)) {
                return true; // La contraseña ya ha sido usada
            }
        }
        return false; // La contraseña es nueva
    }
}