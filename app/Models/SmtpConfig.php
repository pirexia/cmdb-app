<?php
/**
 * app/Models/SmtpConfig.php
 *
 * Este modelo gestiona las interacciones con la base de datos
 * para la configuración SMTP de la aplicación.
 */

namespace App\Models;

use PDO;
use PDOException;
use Exception;

/**
 * Clase SmtpConfig
 * Modelo para la entidad `configuracion_smtp`.
 */
class SmtpConfig
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Obtiene la configuración SMTP de la base de datos.
     * @return array|false La configuración SMTP o false si no se encuentra.
     */
    public function getConfig()
    {
        try {
            $stmt = $this->db->query("SELECT * FROM configuracion_smtp LIMIT 1");
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("MODEL ERROR: " . __CLASS__ . "::" . __FUNCTION__ . " failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Guarda la configuración SMTP en la base de datos.
     * Si ya existe una configuración, la actualiza. Si no, la crea.
     * @param array $config Los datos de configuración SMTP.
     * @return bool True si la operación fue exitosa, false de lo contrario.
     */
    public function saveConfig(array $config)
    {
        try {
            // Verificar si ya existe una configuración
            $existing = $this->getConfig();
            
            if ($existing) {
                // Actualizar la configuración existente
                $stmt = $this->db->prepare("
                    UPDATE configuracion_smtp SET
                        host = :host,
                        port = :port,
                        auth_required = :auth_required,
                        username = :username,
                        password = :password,
                        encryption = :encryption,
                        from_email = :from_email,
                        from_name = :from_name
                    WHERE id = :id
                ");
                $stmt->bindValue(':id', $existing['id'], PDO::PARAM_INT);
            } else {
                // Crear una nueva configuración
                $stmt = $this->db->prepare("
                    INSERT INTO configuracion_smtp (
                        host, port, auth_required, username, password, encryption, from_email, from_name
                    ) VALUES (
                        :host, :port, :auth_required, :username, :password, :encryption, :from_email, :from_name
                    )
                ");
            }
            
            $stmt->bindValue(':host', $config['host'], PDO::PARAM_STR);
            $stmt->bindValue(':port', $config['port'], PDO::PARAM_INT);
            $stmt->bindValue(':auth_required', $config['auth_required'] ? 1 : 0, PDO::PARAM_INT);
            $stmt->bindValue(':username', $config['username'], PDO::PARAM_STR);
            $stmt->bindValue(':password', $config['password'], PDO::PARAM_STR); // Asumimos que ya está cifrada o en texto plano si se usa
            $stmt->bindValue(':encryption', $config['encryption'], PDO::PARAM_STR);
            $stmt->bindValue(':from_email', $config['from_email'], PDO::PARAM_STR);
            $stmt->bindValue(':from_name', $config['from_name'], PDO::PARAM_STR);
            
            $stmt->execute();

            if ($stmt->errorCode() !== '00000') {
                 $errorInfo = $stmt->errorInfo();
                 throw new PDOException("Database error: " . $errorInfo[2], $errorInfo[0]);
            }

            return true;
        } catch (PDOException $e) {
            error_log("MODEL ERROR: " . __CLASS__ . "::" . __FUNCTION__ . " failed: " . $e->getMessage() . " Code: " . $e->getCode());
            throw $e;
        }
    }
}
