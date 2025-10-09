<?php
// app/Models/Asset.php

namespace App\Models;

use PDO;
use Psr\Log\LoggerInterface;
use PDOException; // Asegúrate de que esta línea esté presente para manejar excepciones de base de datos

/**
 * Clase Asset
 * Gestiona las operaciones CRUD (Crear, Leer, Actualizar, Eliminar) para los activos
 * en la base de datos, incluyendo sus relaciones con otras tablas.
 */
class Asset
{
    private PDO $db; // Propiedad para almacenar la conexión a la base de datos PDO
    private LoggerInterface $logger;

    /**
     * Constructor de la clase Asset.
     * @param PDO $db Instancia de la conexión a la base de datos PDO.
     */
    public function __construct(PDO $db, LoggerInterface $logger)
    {
        $this->db = $db;
        $this->logger = $logger;
    }

    /**
     * Obtiene todos los activos de la base de datos, incluyendo información de sus relaciones
     * (tipo de activo, fabricante, modelo, estado, ubicación, departamento, formato de adquisición, proveedor).
     * Los resultados se ordenan por nombre de activo.
     * @return array|false Un array de arrays asociativos con los datos de los activos, o false si ocurre un error.
     */
    public function getAll()
    {
        try {
            $stmt = $this->db->query("
                SELECT
                    a.id,
                    a.nombre,
                    a.numero_serie,
                    a.fecha_compra,
                    a.fecha_fin_garantia,
                    a.fecha_fin_mantenimiento,
                    a.fecha_fin_vida,
                    a.fecha_fin_soporte_mainstream,
                    a.fecha_fin_soporte_extended,
                    a.fecha_venta,
                    a.precio_compra,
                    a.valor_residual,
                    a.descripcion,
                    a.imagen_ruta,
                    a.fecha_creacion,
                    a.fecha_actualizacion,
                    ta.nombre AS tipo_activo_nombre,
                    f.nombre AS fabricante_nombre,
                    mo.nombre AS modelo_nombre,
                    ea.nombre AS estado_nombre,
                    u.nombre AS ubicacion_nombre,
                    d.nombre AS departamento_nombre,
                    fa.nombre AS formato_adquisicion_nombre,
                    p.nombre AS proveedor_adquisicion_nombre
                FROM
                    activos a
                LEFT JOIN tipos_activos ta ON a.id_tipo_activo = ta.id
                LEFT JOIN fabricantes f ON a.id_fabricante = f.id
                LEFT JOIN modelos mo ON a.id_modelo = mo.id
                LEFT JOIN estados_activo ea ON a.id_estado = ea.id
                LEFT JOIN ubicaciones u ON a.id_ubicacion = u.id
                LEFT JOIN departamentos d ON a.id_departamento = d.id
                LEFT JOIN formatos_adquisicion fa ON a.id_formato_adquisicion = fa.id
                LEFT JOIN proveedores p ON a.id_proveedor_adquisicion = p.id
                ORDER BY
                    a.nombre ASC
            ");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            // Registrar el error de la base de datos para depuración.
            error_log("MODEL ERROR: " . __CLASS__ . "::" . __FUNCTION__ . " failed: " . $e->getMessage() . " Code: " . $e->getCode());
            return false; // Devolver false para indicar un fallo.
        }
    }

    /**
     * Obtiene un activo por su nombre.
     * @param string $name
     * @return array|false
     */
    public function getByName(string $name)
    {
        try {
            // Búsqueda insensible a mayúsculas/minúsculas y espacios
            $stmt = $this->db->prepare("SELECT * FROM activos WHERE LOWER(TRIM(nombre)) = LOWER(TRIM(:nombre))");
            $stmt->bindParam(':nombre', $name, PDO::PARAM_STR);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("MODEL ERROR: " . __CLASS__ . "::" . __FUNCTION__ . " failed: " . $e->getMessage() . " Code: " . $e->getCode());
            return false;
        }
    }

    /**
     * Obtiene un activo por su nombre Y su tipo de activo.
     * @param string $name
     * @param int $assetTypeId
     * @return array|false
     */
    public function getByNameAndAssetType(string $name, int $assetTypeId)
    {
        try {
            // Búsqueda por la combinación de nombre y tipo de activo
            $stmt = $this->db->prepare("SELECT * FROM activos WHERE LOWER(TRIM(nombre)) = LOWER(TRIM(:nombre)) AND id_tipo_activo = :id_tipo_activo");
            $stmt->bindParam(':nombre', $name, PDO::PARAM_STR);
            $stmt->bindParam(':id_tipo_activo', $assetTypeId, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("MODEL ERROR: " . __CLASS__ . "::" . __FUNCTION__ . " failed: " . $e->getMessage() . " Code: " . $e->getCode());
            return false;
        }
    }

    /**
     * Obtiene un activo por su número de serie Y su tipo de activo.
     * @param string $serialNumber
     * @param int $assetTypeId
     * @return array|false
     */
    public function getBySerialNumberAndAssetType(string $serialNumber, int $assetTypeId)
    {
        try {
            // Búsqueda por la clave única compuesta
            $stmt = $this->db->prepare("SELECT * FROM activos WHERE numero_serie = :numero_serie AND id_tipo_activo = :id_tipo_activo");
            $stmt->bindParam(':numero_serie', $serialNumber, PDO::PARAM_STR);
            $stmt->bindParam(':id_tipo_activo', $assetTypeId, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("MODEL ERROR: " . __CLASS__ . "::" . __FUNCTION__ . " failed: " . $e->getMessage() . " Code: " . $e->getCode());
            return false;
        }
    }

    /**
     * Obtiene un activo específico por su ID, incluyendo información de sus relaciones.
     * @param int $id El ID del activo a buscar.
     * @return array|false Un array asociativo con los datos del activo, o false si no se encuentra o ocurre un error.
     */
    public function getById(int $id)
    {
        try {
            $stmt = $this->db->prepare("
                SELECT
                    a.id,
                    a.nombre,
                    a.numero_serie,
                    a.id_tipo_activo,
                    a.id_fabricante,
                    a.id_modelo,
                    a.id_estado,
                    a.id_ubicacion,
                    a.id_departamento,
                    a.id_formato_adquisicion,
                    a.id_proveedor_adquisicion,
                    a.fecha_compra,
                    a.precio_compra,
                    a.fecha_fin_garantia,
                    a.fecha_fin_mantenimiento,
                    a.fecha_fin_vida,
                    a.fecha_fin_soporte_mainstream,
                    a.fecha_fin_soporte_extended,
                    a.fecha_venta,
                    a.valor_residual,
                    a.descripcion,
                    a.imagen_ruta,
                    a.fecha_creacion,
                    a.fecha_actualizacion,
                    ta.nombre AS tipo_activo_nombre,
                    f.nombre AS fabricante_nombre,
                    mo.nombre AS modelo_nombre,
                    ea.nombre AS estado_nombre,
                    u.nombre AS ubicacion_nombre,
                    d.nombre AS departamento_nombre,
                    fa.nombre AS formato_adquisicion_nombre,
                    p.nombre AS proveedor_adquisicion_nombre
                FROM
                    activos a
                LEFT JOIN tipos_activos ta ON a.id_tipo_activo = ta.id
                LEFT JOIN fabricantes f ON a.id_fabricante = f.id
                LEFT JOIN modelos mo ON a.id_modelo = mo.id
                LEFT JOIN estados_activo ea ON a.id_estado = ea.id
                LEFT JOIN ubicaciones u ON a.id_ubicacion = u.id
                LEFT JOIN departamentos d ON a.id_departamento = d.id
                LEFT JOIN formatos_adquisicion fa ON a.id_formato_adquisicion = fa.id
                LEFT JOIN proveedores p ON a.id_proveedor_adquisicion = p.id
                WHERE a.id = :id
            ");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("MODEL ERROR: " . __CLASS__ . "::" . __FUNCTION__ . " failed: " . $e->getMessage() . " Code: " . $e->getCode());
            return false;
        }
    }

    /**
     * Crea un nuevo activo en la base de datos.
     * @param array $data Un array asociativo con los datos del activo.
     * @return int El ID del nuevo activo insertado.
     * @throws PDOException Si la inserción falla (ej. violación de restricción UNIQUE).
     */
    public function create(array $data)
    {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO activos (
                    nombre, numero_serie, id_tipo_activo, id_fabricante, id_modelo, id_estado,
                    id_ubicacion, id_departamento, id_formato_adquisicion, id_proveedor_adquisicion,
                    fecha_compra, precio_compra, fecha_fin_garantia, fecha_fin_mantenimiento,
                    fecha_fin_vida, fecha_fin_soporte_mainstream, fecha_fin_soporte_extended,
                    fecha_venta, valor_residual, descripcion, imagen_ruta
                ) VALUES (
                    :nombre, :numero_serie, :id_tipo_activo, :id_fabricante, :id_modelo, :id_estado,
                    :id_ubicacion, :id_departamento, :id_formato_adquisicion, :id_proveedor_adquisicion,
                    :fecha_compra, :precio_compra, :fecha_fin_garantia, :fecha_fin_mantenimiento,
                    :fecha_fin_vida, :fecha_fin_soporte_mainstream, :fecha_fin_soporte_extended,
                    :fecha_venta, :valor_residual, :descripcion, :imagen_ruta
                )
            ");

            // Bindeo de parámetros (se usa bindValue para manejar valores NULL correctamente)
            $stmt->bindValue(':nombre', $data['nombre'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(':numero_serie', $data['numero_serie'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(':id_tipo_activo', $data['id_tipo_activo'] ?? null, PDO::PARAM_INT);
            $stmt->bindValue(':id_fabricante', $data['id_fabricante'] ?? null, PDO::PARAM_INT);
            $stmt->bindValue(':id_modelo', $data['id_modelo'] ?? null, PDO::PARAM_INT);
            $stmt->bindValue(':id_estado', $data['id_estado'] ?? null, PDO::PARAM_INT);
            $stmt->bindValue(':id_ubicacion', $data['id_ubicacion'] ?? null, PDO::PARAM_INT);
            $stmt->bindValue(':id_departamento', $data['id_departamento'] ?? null, PDO::PARAM_INT);
            $stmt->bindValue(':id_formato_adquisicion', $data['id_formato_adquisicion'] ?? null, PDO::PARAM_INT);
            $stmt->bindValue(':id_proveedor_adquisicion', $data['id_proveedor_adquisicion'] ?? null, PDO::PARAM_INT);
            $stmt->bindValue(':fecha_compra', $data['fecha_compra'] ?? null, PDO::PARAM_STR); // DATE
            $stmt->bindValue(':precio_compra', $data['precio_compra'] ?? null, PDO::PARAM_STR); // DECIMAL
            $stmt->bindValue(':fecha_fin_garantia', $data['fecha_fin_garantia'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(':fecha_fin_mantenimiento', $data['fecha_fin_mantenimiento'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(':fecha_fin_vida', $data['fecha_fin_vida'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(':fecha_fin_soporte_mainstream', $data['fecha_fin_soporte_mainstream'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(':fecha_fin_soporte_extended', $data['fecha_fin_soporte_extended'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(':fecha_venta', $data['fecha_venta'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(':valor_residual', $data['valor_residual'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(':descripcion', $data['descripcion'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(':imagen_ruta', $data['imagen_ruta'] ?? null, PDO::PARAM_STR);

            $stmt->execute(); // Ejecuta la sentencia SQL

            // Comprobación de errores explícita después de execute().
            // Si PDO está en ERRMODE_EXCEPTION, esto solo se alcanzará si no hubo una excepción,
            // pero el error_code no es '00000' (ej. un warning o un error que no lanza excepción).
            if ($stmt->errorCode() !== '00000') {
                 $errorInfo = $stmt->errorInfo();
                 // Lanza una PDOException con el mensaje de error de la base de datos y el código SQLSTATE.
                 throw new PDOException("Database error: " . $errorInfo[2], $errorInfo[0]);
            }
            return $this->db->lastInsertId(); // Retorna el ID del último registro insertado.
        } catch (PDOException $e) {
            // Captura cualquier PDOException y la relanza después de registrarla.
            error_log("MODEL ERROR: " . __CLASS__ . "::" . __FUNCTION__ . " failed: " . $e->getMessage() . " Code: " . $e->getCode());
            throw $e; // Relanza la excepción para que el controlador la maneje.
        }
    }

    /**
     * Actualiza un activo existente en la base de datos.
     * @param int $id El ID del activo a actualizar.
     * @param array $data Un array asociativo con los nuevos datos del activo.
     * @return bool True si la actualización fue exitosa, o false si no se actualizó ninguna fila.
     * @throws PDOException Si la actualización falla (ej. violación de restricción UNIQUE).
     */
    public function update(int $id, array $data)
    {
        try {
            $stmt = $this->db->prepare("
                UPDATE activos SET
                    nombre = :nombre,
                    numero_serie = :numero_serie,
                    id_tipo_activo = :id_tipo_activo,
                    id_fabricante = :id_fabricante,
                    id_modelo = :id_modelo,
                    id_estado = :id_estado,
                    id_ubicacion = :id_ubicacion,
                    id_departamento = :id_departamento,
                    id_formato_adquisicion = :id_formato_adquisicion,
                    id_proveedor_adquisicion = :id_proveedor_adquisicion,
                    fecha_compra = :fecha_compra,
                    precio_compra = :precio_compra,
                    fecha_fin_garantia = :fecha_fin_garantia,
                    fecha_fin_mantenimiento = :fecha_fin_mantenimiento,
                    fecha_fin_vida = :fecha_fin_vida,
                    fecha_fin_soporte_mainstream = :fecha_fin_soporte_mainstream,
                    fecha_fin_soporte_extended = :fecha_fin_soporte_extended,
                    fecha_venta = :fecha_venta,
                    valor_residual = :valor_residual,
                    descripcion = :descripcion,
                    imagen_ruta = :imagen_ruta
                WHERE id = :id
            ");

            // Bindeo de parámetros (se usa bindValue para manejar valores NULL correctamente)
            $stmt->bindValue(':nombre', $data['nombre'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(':numero_serie', $data['numero_serie'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(':id_tipo_activo', $data['id_tipo_activo'] ?? null, PDO::PARAM_INT);
            $stmt->bindValue(':id_fabricante', $data['id_fabricante'] ?? null, PDO::PARAM_INT);
            $stmt->bindValue(':id_modelo', $data['id_modelo'] ?? null, PDO::PARAM_INT);
            $stmt->bindValue(':id_estado', $data['id_estado'] ?? null, PDO::PARAM_INT);
            $stmt->bindValue(':id_ubicacion', $data['id_ubicacion'] ?? null, PDO::PARAM_INT);
            $stmt->bindValue(':id_departamento', $data['id_departamento'] ?? null, PDO::PARAM_INT);
            $stmt->bindValue(':id_formato_adquisicion', $data['id_formato_adquisicion'] ?? null, PDO::PARAM_INT);
            $stmt->bindValue(':id_proveedor_adquisicion', $data['id_proveedor_adquisicion'] ?? null, PDO::PARAM_INT);
            $stmt->bindValue(':fecha_compra', $data['fecha_compra'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(':precio_compra', $data['precio_compra'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(':fecha_fin_garantia', $data['fecha_fin_garantia'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(':fecha_fin_mantenimiento', $data['fecha_fin_mantenimiento'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(':fecha_fin_vida', $data['fecha_fin_vida'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(':fecha_fin_soporte_mainstream', $data['fecha_fin_soporte_mainstream'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(':fecha_fin_soporte_extended', $data['fecha_fin_soporte_extended'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(':fecha_venta', $data['fecha_venta'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(':valor_residual', $data['valor_residual'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(':descripcion', $data['descripcion'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(':imagen_ruta', $data['imagen_ruta'] ?? null, PDO::PARAM_STR);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);

            $stmt->execute(); // Ejecuta la sentencia SQL

            // Comprobación de errores explícita después de execute().
            if ($stmt->errorCode() !== '00000') {
                 $errorInfo = $stmt->errorInfo();
                 // Lanza una PDOException con el mensaje de error de la base de datos y el código SQLSTATE.
                 throw new PDOException("Database error: " . $errorInfo[2], $errorInfo[0]);
            }
            return true; // Retorna true si la actualización fue exitosa.
        } catch (PDOException $e) {
            // Captura cualquier PDOException y la relanza después de registrarla.
            error_log("MODEL ERROR: " . __CLASS__ . "::" . __FUNCTION__ . " failed: " . $e->getMessage() . " Code: " . $e->getCode());
            throw $e; // Relanza la excepción para que el controlador la maneje.
        }
    }

    /**
     * Elimina un activo de la base de datos.
     * @param int $id El ID del activo a eliminar.
     * @return bool True si la eliminación fue exitosa.
     * @throws PDOException Si la eliminación falla (ej. por restricciones de clave foránea).
     */
    public function delete(int $id)
    {
        try {
            $stmt = $this->db->prepare("DELETE FROM activos WHERE id = :id");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();

            // Comprobación de errores explícita después de execute().
            if ($stmt->errorCode() !== '00000') {
                 $errorInfo = $stmt->errorInfo();
                 // Lanza una PDOException con el mensaje de error de la base de datos y el código SQLSTATE.
                 throw new PDOException("Database error: " . $errorInfo[2], $errorInfo[0]);
            }
            return true; // Retorna true si la eliminación fue exitosa.
        } catch (PDOException $e) {
            // Captura cualquier PDOException y la relanza después de registrarla.
            error_log("MODEL ERROR: " . __CLASS__ . "::" . __FUNCTION__ . " failed: " . $e->getMessage() . " Code: " . $e->getCode());
            throw $e; // Relanza la excepción para que el controlador la maneje.
        }
    }

    /**
     * Cuenta el número total de activos en la base de datos.
     * @return int|false El número total de activos, o false si ocurre un error.
     */
    public function countAll()
    {
        try {
            $stmt = $this->db->query("SELECT COUNT(id) FROM activos");
            return (int)$stmt->fetchColumn(); // Retorna el conteo como un entero.
        } catch (PDOException $e) {
            error_log("MODEL ERROR: " . __CLASS__ . "::" . __FUNCTION__ . " failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Cuenta el número de activos agrupados por su estado.
     * @return array|false Un array de arrays asociativos con el nombre del estado y el conteo, o false si ocurre un error.
     */
    public function countByStatus()
    {
        try {
            $stmt = $this->db->query("SELECT ea.nombre AS status_name, COUNT(a.id) AS count FROM activos a JOIN estados_activo ea ON a.id_estado = ea.id GROUP BY ea.nombre ORDER BY count DESC");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("MODEL ERROR: " . __CLASS__ . "::" . __FUNCTION__ . " failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Cuenta el número de activos agrupados por su tipo.
     * @return array|false Un array de arrays asociativos con el nombre del tipo y el conteo, o false si ocurre un error.
     */
    public function countByType()
    {
        try {
            $stmt = $this->db->query("SELECT ta.nombre AS type_name, COUNT(a.id) AS count FROM activos a JOIN tipos_activos ta ON a.id_tipo_activo = ta.id GROUP BY ta.nombre ORDER BY count DESC");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("MODEL ERROR: " . __CLASS__ . "::" . __FUNCTION__ . " failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtiene una lista de activos cuyas fechas de garantía, mantenimiento o soporte
     * están próximas a caducar (dentro de un umbral de fecha).
     * @param string $thresholdDate La fecha límite (formato 'YYYY-MM-DD').
     * @return array|false Un array de activos próximos a caducar, o false si ocurre un error.
     */
    public function getExpiringAssets(string $thresholdDate): array|false
    {
        try {
            $today = date('Y-m-d');
            $sql = "
                SELECT
                    a.id, a.nombre, a.numero_serie, ta.nombre AS tipo_activo_nombre,
                    a.fecha_fin_garantia, a.fecha_fin_mantenimiento, a.fecha_fin_soporte_mainstream, a.fecha_fin_soporte_extended, a.fecha_fin_vida
                FROM activos a
                JOIN tipos_activos ta ON a.id_tipo_activo = ta.id
                WHERE
                    (a.fecha_fin_garantia BETWEEN :today AND :threshold_date) OR
                    (a.fecha_fin_mantenimiento BETWEEN :today AND :threshold_date) OR
                    (a.fecha_fin_soporte_mainstream BETWEEN :today AND :threshold_date) OR
                    (a.fecha_fin_soporte_extended BETWEEN :today AND :threshold_date) OR
                    (a.fecha_fin_vida BETWEEN :today AND :threshold_date)
                ORDER BY a.nombre ASC
            ";
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':today', $today, PDO::PARAM_STR);
            $stmt->bindValue(':threshold_date', $thresholdDate, PDO::PARAM_STR);
            $stmt->execute();

            // --- DEBUG: Log de la consulta ---
            $debugQuery = $sql;
            $debugQuery = str_replace(':today', "'$today'", $debugQuery);
            $debugQuery = str_replace(':threshold_date', "'$thresholdDate'", $debugQuery);
            $this->logger->debug('SQL Query Executed for Assets', ['query' => preg_replace('/\s+/', ' ', $debugQuery)]);
            // --- FIN DEBUG ---

            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $this->logger->debug('DB Results for Expiring Assets', ['count' => count($results)]);
            return $results;
        } catch (PDOException $e) {
            error_log("MODEL ERROR: " . __CLASS__ . "::" . __FUNCTION__ . " failed: " . $e->getMessage());
            return false;
        }
    }
}
