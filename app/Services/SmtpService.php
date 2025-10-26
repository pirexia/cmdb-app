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
use PHPMailer\PHPMailer\PHPMailer; // Para la prueba de conexión
use PDOException;             // Para capturar errores específicos de la base de datos.
use App\Services\EncryptionService; // Importar el nuevo servicio de cifrado.
use PHPMailer\PHPMailer\Exception as MailerException; // Para capturar errores de PHPMailer

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
    private EncryptionService $encryptionService; // Servicio de cifrado.

    /**
     * Constructor del servicio. Inyecta todas las dependencias necesarias.
     * @param array $config El array de configuración de la aplicación.
     * @param LoggerInterface $logger Instancia del logger.
     * @param callable $translator La función de traducción.
     * @param PDO $db Instancia de la conexión a la base de datos.
     * @param EncryptionService $encryptionService Servicio para cifrar/descifrar.
     */
    public function __construct(
        array $config,
        LoggerInterface $logger,
        callable $translator,
        PDO $db,
        EncryptionService $encryptionService
    ) {
        $this->config = $config;
        $this->logger = $logger;
        $this->translator = $translator;
        $this->encryptionService = $encryptionService;
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

        // Si la autenticación está desactivada, limpiar usuario y contraseña.
        if (empty($newConfig['auth_required'])) {
            $newConfig['username'] = '';
            $newConfig['password'] = '';
        } else {
            // Si la autenticación está activada y se proporciona una nueva contraseña, la ciframos.
            if (!empty($newConfig['password'])) {
                $newConfig['password'] = $this->encryptionService->encrypt($newConfig['password']);
            } else {
                // Si no se proporciona una nueva contraseña, mantenemos la existente.
                $existingConfig = $this->smtpConfigModel->getConfig();
                $newConfig['password'] = $existingConfig['password'] ?? '';
            }
        }

        try {
            // Se asume que el modelo SmtpConfig ya tiene un método saveConfig que
            // gestiona la creación o actualización de la única fila de configuración.
            $this->smtpConfigModel->saveConfig($newConfig);
            $this->logger->info($t('smtp_config_saved_log'));
            return true;
        } catch (\PDOException $e) {
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
        // Usar un array vacío como fallback si la configuración del archivo no existe.
        $config = $this->config['smtp'] ?? []; 

        if ($dbConfig) {
            $config['host'] = $dbConfig['host'];
            $config['port'] = $dbConfig['port'];
            $config['auth_required'] = (bool)$dbConfig['auth_required'];
            $config['username'] = $dbConfig['username'];
            // Descifrar la contraseña si existe.
            $config['password'] = ''; // Inicializar como vacía
            if (!empty($dbConfig['password'])) {                
                // Comprobar si la contraseña parece un hash antiguo en lugar de un valor cifrado.
                // Los hashes de password_hash() suelen empezar por '$2y$' o '$argon2i$'.
                // Los valores cifrados por nuestro servicio son base64, no empiezan con '$'.
                if (str_starts_with($dbConfig['password'], '$')) {
                    $this->logger->warning('Se ha detectado una contraseña SMTP hasheada antigua. Se ha ignorado. Por favor, guarde de nuevo la configuración SMTP para cifrarla correctamente.');
                } else {
                    // Solo intentar descifrar si no es un hash.
                    $config['password'] = $this->encryptionService->decrypt($dbConfig['password']);
                }
            }
            $config['encryption'] = $dbConfig['encryption'];
            $config['from_email'] = $dbConfig['from_email'];
            $config['from_name'] = $dbConfig['from_name'];
        }

        return $config;
    }

    /**
     * Prueba la conexión a un servidor SMTP con la configuración proporcionada.
     * @param array $config Los detalles de configuración SMTP a probar.
     * @return array ['success' => bool, 'message' => string]
     */
    public function testSmtpConnection(array $config): array
    {
        $t = $this->translator;
        $mail = new PHPMailer(true);

        try {
            // Configurar la instancia temporal de PHPMailer
            $mail->isSMTP();
            $mail->Host = $config['host'] ?? '';
            $mail->Port = (int)($config['port'] ?? 587);
            $mail->SMTPAuth = (bool)($config['auth_required'] ?? false);

            if ($mail->SMTPAuth) {
                $mail->Username = $config['username'] ?? '';
                // Para la prueba, usamos la contraseña en texto plano que viene del formulario.
                $mail->Password = $config['password'] ?? '';
            }

            if (!empty($config['encryption'])) {
                $mail->SMTPSecure = $config['encryption'];
            }

            // Deshabilitar la verificación de certificados SSL para la prueba,
            // ya que es una causa común de fallos en entornos locales/internos.
            $mail->SMTPOptions = [
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true,
                ],
            ];

            // Activar el modo debug para capturar la salida detallada
            $mail->SMTPDebug = 0; // Cambiar a 2 para depuración exhaustiva si es necesario

            // Intentar conectar
            if ($mail->smtpConnect()) {
                $mail->smtpClose(); // Cerrar la conexión si fue exitosa
                $this->logger->info($t('smtp_test_connection_successful_log', ['%host%' => $config['host']]));
                return ['success' => true, 'message' => $t('connection_successful')];
            } else {
                // Esto es un fallback, ya que smtpConnect() suele lanzar una excepción en caso de fallo.
                $this->logger->warning($t('smtp_test_connection_failed_log', ['%host%' => $config['host'], '%error%' => $mail->ErrorInfo]));
                return ['success' => false, 'message' => $t('connection_failed') . ': ' . $mail->ErrorInfo];
            }
        } catch (MailerException $e) {
            $this->logger->warning($t('smtp_test_connection_failed_log', ['%host%' => $config['host'], '%error%' => $e->getMessage()]));
            // Devolvemos el mensaje de error de PHPMailer, que es muy descriptivo.
            return ['success' => false, 'message' => $t('connection_failed') . ': ' . $e->getMessage()];
        } catch (Exception $e) {
            $this->logger->error($t('smtp_test_unexpected_error_log', ['%message%' => $e->getMessage()]));
            return ['success' => false, 'message' => $t('smtp_test_unexpected_error')];
        }
    }
}
