<?php
/**
 * app/Services/SmtpService.php
 *
 * Este servicio gestiona la lógica de negocio para la configuración SMTP.
 * Se encarga de guardar y validar los parámetros de conexión de correo,
 * interactuando con el modelo SmtpConfig.
 */

namespace App\Services;

// --- Importaciones de Clases ---
use App\Models\SmtpConfig;    // Modelo para interactuar con la tabla de configuración SMTP.
use Psr\Log\LoggerInterface;  // Interfaz de logger para registrar eventos.
use PDO;                      // Clase de conexión a la base de datos.
use Exception;                // Para manejar excepciones generales.

/**
 * Clase SmtpService
 * Proporciona métodos para guardar la configuración SMTP de forma segura
 * y con la validación adecuada.
 */
class SmtpService
{
    private array $config;            // Configuración general de la aplicación.
    private LoggerInterface $logger;  // Instancia del logger.
    private $translator;              // Función de traducción.
    private SmtpConfig $smtpConfigModel; // Modelo para la configuración SMTP.

    /**
     * Constructor del servicio. Inyecta todas las dependencias necesarias.
     * @param array $config El array de configuración de la aplicación.
     * @param LoggerInterface $logger Instancia del logger.
     * @param callable $translator La función de traducción.
     * @param PDO $db Instancia de la conexión a la base de datos.
     */
    public function __construct(
        array $config,
        LoggerInterface $logger,
        callable $translator,
        PDO $db
    ) {
        $this->config = $config;
        $this->logger = $logger;
        $this->translator = $translator;
        $this->smtpConfigModel = new SmtpConfig($db); // Se instancia el modelo aquí.
    }

    /**
     * Guarda la configuración SMTP en la base de datos.
     * @param array $newConfig Los nuevos datos de configuración.
     * @return bool True si se guardó con éxito, false en caso de error.
     */
    public function saveSmtpConfig(array $newConfig): bool
    {
        $t = $this->translator;

        // Validar que se ha proporcionado un host y un correo de remitente.
        if (empty($newConfig['host']) || empty($newConfig['from_email'])) {
            $this->logger->error($t('smtp_required_fields_error'));
            throw new Exception($t('smtp_required_fields_error'));
        }

        // Cifrar la contraseña si se ha proporcionado.
        if (!empty($newConfig['password'])) {
            $newConfig['password'] = password_hash($newConfig['password'], PASSWORD_DEFAULT);
        } else {
            // Si la contraseña está vacía, no la guardamos y mantenemos la anterior.
            $existingConfig = $this->smtpConfigModel->getConfig();
            $newConfig['password'] = $existingConfig['password'] ?? null;
        }

        try {
            // Se asume que el modelo SmtpConfig ya tiene un método saveConfig que
            // gestiona la creación o actualización de la única fila de configuración.
            $this->smtpConfigModel->saveConfig($newConfig);
            $this->logger->info($t('smtp_config_saved_log'));
            return true;
        } catch (PDOException $e) {
            $this->logger->error($t('smtp_config_save_pdo_error', ['%message%' => $e->getMessage()]));
            return false;
        } catch (Exception $e) {
            $this->logger->error($t('smtp_config_save_general_error', ['%message%' => $e->getMessage()]));
            return false;
        }
    }

    /**
     * Obtiene la configuración SMTP actual de la base de datos.
     * @return array La configuración SMTP.
     */
    public function getSmtpConfig(): array
    {
        $dbConfig = $this->smtpConfigModel->getConfig();
        $config = $this->config['smtp']; // Fallback a la configuración del archivo

        if ($dbConfig) {
            $config['host'] = $dbConfig['host'];
            $config['port'] = $dbConfig['port'];
            $config['auth_required'] = (bool)$dbConfig['auth_required'];
            $config['username'] = $dbConfig['username'];
            $config['password'] = $dbConfig['password'];
            $config['encryption'] = $dbConfig['encryption'];
            $config['from_email'] = $dbConfig['from_email'];
            $config['from_name'] = $dbConfig['from_name'];
        }

        return $config;
    }
}
