<?php
// app/Models/User.php

namespace App\Models;

use PDO;
use PDOException; // Asegúrate de que esta línea esté presente

class User
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Obtiene un usuario por su nombre de usuario.
     * @param string $username
     * @return array|false
     */
    public function getUserByUsername(string $username)
    {
        try {
            $stmt = $this->db->prepare("
                SELECT id, nombre_usuario, password_hash, email, id_rol, activo, id_fuente_usuario, 
                       fuente_login_nombre, mfa_enabled, mfa_secret 
                FROM usuarios WHERE nombre_usuario = :username");
            $stmt->bindParam(':username', $username, PDO::PARAM_STR);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("MODEL ERROR: " . __CLASS__ . "::" . __FUNCTION__ . " failed: " . $e->getMessage() . " Code: " . $e->getCode());
            return false;
        }
    }

    /**
     * Obtiene un usuario por su ID.
     * @param int $id
     * @return array|false
     */
    public function getUserById(int $id)
    {
        try {
            $stmt = $this->db->prepare("
                SELECT id, nombre_usuario, email, id_rol, activo, id_fuente_usuario,
                       fuente_login_nombre, nombre, apellidos, titulo, profile_image_path, preferred_language_code,
                       mfa_enabled, mfa_secret
                FROM usuarios 
                WHERE id = :id
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
      * Obtiene un usuario por su email.
      * @param string $email
      * @return array|false
      */
    public function getUserByEmail(string $email)
    {
         try {
            $stmt = $this->db->prepare("
                SELECT id, nombre_usuario, password_hash, email, id_rol, activo, id_fuente_usuario, 
                       fuente_login_nombre, mfa_enabled, mfa_secret 
                FROM usuarios WHERE email = :email");
            $stmt->bindParam(':email', $email, PDO::PARAM_STR);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("MODEL ERROR: " . __CLASS__ . "::" . __FUNCTION__ . " failed: " . $e->getMessage() . " Code: " . $e->getCode());
            return false;
        }
    }


    /**
     * Actualiza la información de un usuario existente.
     * @param int $id
     * @param string $username
     * @param string|null $email
     * @param int $roleId
     * @param bool $activo
     * @param int $sourceId ID de la fuente de usuario (local, ldap, etc.)
     * @param string $sourceName Nombre amigable de la fuente de login
     * @param string|null $nombre
     * @param string|null $apellidos
     * @param string|null $titulo
     * @return bool
     * @throws PDOException Si la actualización falla.
     */
    public function updateUser(int $id, string $username, ?string $email, int $roleId, bool $activo, int $sourceId, string $sourceName, ?string $nombre = null, ?string $apellidos = null, ?string $titulo = null): bool
    {
        try {
            $sql = "UPDATE usuarios SET 
                        nombre_usuario = :username, 
                        email = :email, 
                        id_rol = :id_rol, 
                        activo = :activo, 
                        id_fuente_usuario = :id_fuente_usuario, 
                        fuente_login_nombre = :fuente_login_nombre,
                        nombre = :nombre,
                        apellidos = :apellidos,
                        titulo = :titulo
                    WHERE id = :id";
            
            $stmt = $this->db->prepare($sql);
            
            $stmt->bindParam(':username', $username, PDO::PARAM_STR);
            $stmt->bindValue(':email', $email, ($email === null ? PDO::PARAM_NULL : PDO::PARAM_STR));
            $stmt->bindParam(':id_rol', $roleId, PDO::PARAM_INT);
            $stmt->bindValue(':activo', $activo ? 1 : 0, PDO::PARAM_INT);
            $stmt->bindParam(':id_fuente_usuario', $sourceId, PDO::PARAM_INT);
            $stmt->bindParam(':fuente_login_nombre', $sourceName, PDO::PARAM_STR);
            $stmt->bindValue(':nombre', $nombre, ($nombre === null ? PDO::PARAM_NULL : PDO::PARAM_STR));
            $stmt->bindValue(':apellidos', $apellidos, ($apellidos === null ? PDO::PARAM_NULL : PDO::PARAM_STR));
            $stmt->bindValue(':titulo', $titulo, ($titulo === null ? PDO::PARAM_NULL : PDO::PARAM_STR));
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            
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

    /**
     * Crea un nuevo usuario.
     * @param string $username Nombre de usuario.
     * @param string|null $passwordHash Hash de la contraseña (null para usuarios no locales).
     * @param string|null $email Email del usuario.
     * @param int $roleId ID del rol.
     * @param bool $activo Estado del usuario.
     * @param int $sourceId ID de la fuente de usuario.
     * @param string $sourceName Nombre de la fuente.
     * @return int ID del nuevo usuario.
     * @throws PDOException Si la inserción falla.
     */
    public function createUser(string $username, ?string $passwordHash, ?string $email, int $roleId, bool $activo, int $sourceId, string $sourceName, ?string $nombre = null, ?string $apellidos = null, ?string $titulo = null): int
    {
        try {
            $sql = "INSERT INTO usuarios (
                        nombre_usuario, password_hash, email, id_rol, activo, 
                        id_fuente_usuario, fuente_login_nombre, nombre, apellidos, titulo
                    ) VALUES (
                        :username, :password_hash, :email, :id_rol, :activo, 
                        :id_fuente_usuario, :fuente_login_nombre, :nombre, :apellidos, :titulo
                    )";
            $stmt = $this->db->prepare($sql);

            $stmt->bindParam(':username', $username, PDO::PARAM_STR);
            $stmt->bindValue(':password_hash', $passwordHash, ($passwordHash === null ? PDO::PARAM_NULL : PDO::PARAM_STR));
            $stmt->bindValue(':email', $email, ($email === null ? PDO::PARAM_NULL : PDO::PARAM_STR));
            $stmt->bindParam(':id_rol', $roleId, PDO::PARAM_INT);
            $stmt->bindValue(':activo', $activo ? 1 : 0, PDO::PARAM_INT);
            $stmt->bindParam(':id_fuente_usuario', $sourceId, PDO::PARAM_INT);
            $stmt->bindParam(':fuente_login_nombre', $sourceName, PDO::PARAM_STR);
            $stmt->bindValue(':nombre', $nombre, ($nombre === null ? PDO::PARAM_NULL : PDO::PARAM_STR));
            $stmt->bindValue(':apellidos', $apellidos, ($apellidos === null ? PDO::PARAM_NULL : PDO::PARAM_STR));
            $stmt->bindValue(':titulo', $titulo, ($titulo === null ? PDO::PARAM_NULL : PDO::PARAM_STR));
            $stmt->execute();

            if ($stmt->errorCode() !== '00000') {
                 $errorInfo = $stmt->errorInfo();
                 throw new PDOException("Database error: " . $errorInfo[2], $errorInfo[0]);
            }

            $lastInsertId = $this->db->lastInsertId();
            if ($lastInsertId) {
                return (int)$lastInsertId;
            } else {
                throw new PDOException("Failed to retrieve lastInsertId after user creation.");
            }
        } catch (PDOException $e) {
            error_log("MODEL ERROR: " . __CLASS__ . "::" . __FUNCTION__ . " failed: " . $e->getMessage() . " Code: " . $e->getCode());
            throw $e;
        }
    }

    /**
     * Actualiza solo la contraseña de un usuario.
     * @param int $id
     * @param string $newPasswordHash
     * @return bool
     * @throws PDOException Si la actualización falla.
     */
    public function updatePassword(int $id, string $newPasswordHash)
    {
        try {
            $stmt = $this->db->prepare("UPDATE usuarios SET password_hash = :password_hash WHERE id = :id");
            $stmt->bindParam(':password_hash', $newPasswordHash, PDO::PARAM_STR);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
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

    /**
     * Actualiza la fecha de última sesión de un usuario.
     * @param int $id
     * @return bool
     * @throws PDOException Si la actualización falla.
     */
    public function updateLastLogin(int $id)
    {
        try {
            $stmt = $this->db->prepare("UPDATE usuarios SET fecha_ultima_sesion = NOW() WHERE id = :id");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
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

    /**
     * Elimina un usuario.
     * @param int $id
     * @return bool
     * @throws PDOException Si la eliminación falla.
     */
    public function deleteUser(int $id)
    {
        try {
            $stmt = $this->db->prepare("DELETE FROM usuarios WHERE id = :id");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();

            if ($stmt->errorCode() !== '00000') {
                 $errorInfo = $stmt->errorInfo();
                 throw new PDOException("Database error: " . $errorInfo[2], $errorInfo[0]);
            }
            return true;
        } catch (PDOException $e) {
            throw $e;
        }
    }

    /**
     * Obtiene todos los usuarios, posiblemente con detalles del rol y fuente de login.
     * @return array|false
     */
    public function getAllUsers()
    {
        try {
            $stmt = $this->db->query("SELECT u.id, u.nombre_usuario, u.email, u.activo, r.nombre AS rol_nombre, s.nombre_friendly AS fuente_nombre, u.fecha_creacion, u.fecha_ultima_sesion FROM usuarios u JOIN roles r ON u.id_rol = r.id LEFT JOIN fuentes_usuario s ON u.id_fuente_usuario = s.id ORDER BY u.nombre_usuario ASC"); // <-- ¡NUEVAS COLUMNAS!
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("MODEL ERROR: " . __CLASS__ . "::" . __FUNCTION__ . " failed: " . $e->getMessage() . " Code: " . $e->getCode());
            return false;
        }
    }

    /**
     * Actualiza los datos del perfil de un usuario.
     * @param int $id El ID del usuario a actualizar.
     * @param array $data Un array asociativo con los datos a actualizar (ej. ['email' => 'new@email.com']).
     * @return bool True si la actualización fue exitosa, false en caso contrario.
     * @throws PDOException Si ocurre un error en la base de datos.
     */
    public function updateProfileData(int $id, array $data): bool
    {
        if (empty($data)) {
            return true; // No hay nada que actualizar.
        }

        $allowedColumns = ['email', 'nombre', 'apellidos', 'titulo', 'profile_image_path', 'mfa_enabled', 'mfa_secret', 'preferred_language_code'];
        $setClauses = [];
        $params = ['id' => $id];

        foreach ($data as $key => $value) {
            if (in_array($key, $allowedColumns)) {
                $setClauses[] = "`$key` = :$key";
                $params[$key] = $value;
            }
        }

        if (empty($setClauses)) {
            return false; // No hay campos válidos para actualizar.
        }

        try {
            $sql = "UPDATE usuarios SET " . implode(', ', $setClauses) . " WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute($params);
        } catch (PDOException $e) {
            error_log("MODEL ERROR: " . __CLASS__ . "::" . __FUNCTION__ . " failed: " . $e->getMessage() . " Code: " . $e->getCode());
            throw $e; // Relanzar la excepción para que el controlador la maneje.
        }
    }

    /**
     * Actualiza la fecha de último cambio de contraseña para un usuario.
     * @param int $userId
     * @return bool
     */
    public function updatePasswordChangedDate(int $userId): bool
    {
        $stmt = $this->db->prepare("UPDATE usuarios SET password_changed_at = NOW() WHERE id = :id");
        $stmt->bindValue(':id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }    

    /**
     * Encuentra usuarios locales que no han iniciado sesión en un número determinado de días.
     * @param int $days Días de inactividad.
     * @return array|false
     */
    public function findInactiveForDeactivation(int $days): array|false
    {
        $stmt = $this->db->prepare("
            SELECT id, nombre_usuario, fecha_ultima_sesion
            FROM usuarios
            WHERE 
                activo = 1 AND
                id_fuente_usuario = 1 AND -- Solo usuarios locales
                (
                    fecha_ultima_sesion IS NULL AND fecha_creacion < DATE_SUB(NOW(), INTERVAL :days DAY) OR
                    fecha_ultima_sesion < DATE_SUB(NOW(), INTERVAL :days DAY)
                )
        ");
        $stmt->bindValue(':days', $days, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Desactiva un usuario.
     * @param int $userId
     * @return bool
     */
    public function deactivateUser(int $userId): bool
    {
        $stmt = $this->db->prepare("UPDATE usuarios SET activo = 0 WHERE id = :id");
        $stmt->bindValue(':id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }
}
