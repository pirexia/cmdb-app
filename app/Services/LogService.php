<?php
/**
 * app/Services/LogService.php
 *
 * Este servicio gestiona la lógica de auditoría de la aplicación, interactuando
 * con el modelo LogActivo para registrar y recuperar el historial de cambios en los activos.
 */

namespace App\Services;

// --- Importaciones de Clases ---
use App\Models\LogActivo;      // Modelo para interactuar con la tabla de logs de activos.
use App\Models\Asset;          // Modelo de Activos, usado en logChange para obtener datos.
use App\Models\User;           // Modelo de Usuarios, usado en logChange para obtener datos.
use Psr\Log\LoggerInterface;   // Interfaz de logger (Monolog) para registrar eventos.
use PDO;                       // Clase de conexión a la base de datos.
use Exception;                 // Para manejar excepciones generales.
use PDOException;              // Para capturar errores específicos de la base de datos.

/**
 * Clase LogService
 * Proporciona métodos para registrar logs de cambios en activos y para
 * recuperar el historial de logs para el módulo de auditoría.
 */
class LogService
{
    private PDO $db;                       // Conexión a la base de datos.
    private LoggerInterface $logger;       // Instancia del logger.
    private LogActivo $logActivoModel;     // Modelo para la tabla `log_activos`.

    /**
     * Constructor del servicio. Inyecta la conexión a la base de datos y el logger.
     * @param PDO $db Instancia de la conexión a la base de datos.
     * @param LoggerInterface $logger Instancia del logger.
     */
    public function __construct(PDO $db, LoggerInterface $logger)
    {
        $this->db = $db;
        $this->logger = $logger;
        // El modelo LogActivo se crea internamente aquí, ya que no tiene dependencias.
        $this->logActivoModel = new LogActivo($this->db);
    }

    /**
     * Registra un cambio en un activo.
     * @param int $assetId ID del activo afectado.
     * @param int $userId ID del usuario que realizó el cambio.
     * @param string $operationType Tipo de operación (CREACION, MODIFICACION, ELIMINACION).
     * @param array|null $oldData Datos del activo antes de la modificación (para MODIFICACION y ELIMINACION).
     * @param array|null $newData Datos del activo después de la modificación (para CREACION y MODIFICACION).
     * @return int|false ID del nuevo registro de log o false si falla.
     */
    public function logChange(int $assetId, int $userId, string $operationType, ?array $oldData = null, ?array $newData = null): int|false
    {
        try {
            // Serializa los arrays de datos a cadenas JSON para almacenarlos en la base de datos.
            $oldDataJson = json_encode($oldData, JSON_UNESCAPED_UNICODE);
            $newDataJson = json_encode($newData, JSON_UNESCAPED_UNICODE);

            $stmt = $this->db->prepare("
                INSERT INTO log_activos (
                    id_activo, id_usuario, tipo_operacion,
                    valor_anterior, valor_nuevo, descripcion_completa
                ) VALUES (
                    :id_activo, :id_usuario, :tipo_operacion,
                    :valor_anterior, :valor_nuevo, :descripcion_completa
                )
            ");

            $stmt->bindValue(':id_activo', $assetId, PDO::PARAM_INT);
            $stmt->bindValue(':id_usuario', $userId, PDO::PARAM_INT);
            $stmt->bindValue(':tipo_operacion', $operationType, PDO::PARAM_STR);
            $stmt->bindValue(':valor_anterior', $oldDataJson, PDO::PARAM_STR);
            $stmt->bindValue(':valor_nuevo', $newDataJson, PDO::PARAM_STR);
            $stmt->bindValue(':descripcion_completa', "Operación de tipo {$operationType}", PDO::PARAM_STR);
            
            $stmt->execute();
            
            return $this->db->lastInsertId();
        } catch (PDOException $e) {
            $this->logger->error("Error al registrar log de activo en la base de datos: " . $e->getMessage());
            // No relanzamos la excepción aquí; el fallo de un log no debería detener el flujo principal.
            return false;
        }
    }
    
    /**
     * Obtiene todos los logs de cambios de activos de la base de datos
     * utilizando el modelo LogActivo.
     * @return array|false Un array de logs o false si falla.
     */
    public function getAllAssetLogs(): array|false
    {
        return $this->logActivoModel->getAllLogs();
    }
}
