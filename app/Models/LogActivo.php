<?php
/**
 * app/Models/LogActivo.php
 *
 * Este modelo gestiona las interacciones con la tabla `log_activos` de la base de datos.
 * Se encarga de las operaciones de lectura (logs de auditoría) de los cambios en los activos.
 */

namespace App\Models;

use PDO;
use PDOException;

/**
 * Clase LogActivo
 * Modelo para la entidad `log_activos`.
 */
class LogActivo
{
    private PDO $db; // Propiedad para almacenar la conexión a la base de datos PDO.

    /**
     * Constructor del modelo LogActivo.
     * @param PDO $db Instancia de la conexión a la base de datos PDO.
     */
    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Obtiene todos los logs de cambios de activos, incluyendo información del usuario y del activo.
     * La consulta utiliza LEFT JOIN para evitar errores si no hay datos en las tablas de activos o usuarios.
     * @return array|false Un array de arrays asociativos con los datos de los logs, o false si ocurre un error.
     */
    public function getAllLogs()
    {
        try {
            $stmt = $this->db->query("
                SELECT
                    la.id,
                    la.id_activo,
                    a.nombre AS activo_nombre,
                    la.id_usuario,
                    u.nombre_usuario AS usuario_nombre,
                    la.tipo_operacion AS accion,
                    la.campo_modificado,
                    la.valor_anterior,
                    la.valor_nuevo,
                    la.descripcion_completa,
                    la.fecha_hora AS fecha_log
                FROM
                    log_activos la
                LEFT JOIN
                    activos a ON la.id_activo = a.id
                LEFT JOIN
                    usuarios u ON la.id_usuario = u.id
                ORDER BY
                    la.fecha_hora DESC
            ");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("MODEL ERROR: " . __CLASS__ . "::" . __FUNCTION__ . " failed: " . $e->getMessage() . " Code: " . $e->getCode());
            return false;
        }
    }
}

