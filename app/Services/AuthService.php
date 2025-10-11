<?php
// app/Services/AuthService.php

namespace App\Services;

use App\Models\User;
use App\Models\PasswordResetToken;
use App\Models\Role;
use App\Models\Source;
use Psr\Log\LoggerInterface;
use DateTime;
use DateInterval;

class AuthService
{
    private User $userModel;
    private PasswordResetToken $tokenModel;
    private Role $roleModel;
    private SessionService $sessionService;
    private MailService $mailService;
    private LoggerInterface $logger;
    private array $config;
    private LdapService $ldapService; // <--- ¡NUEVA PROPIEDAD!
    private $translator;
    private Source $sourceModel;

    public function __construct(
        User $userModel,
        PasswordResetToken $tokenModel,
        Role $roleModel,
        SessionService $sessionService,        
        MailService $mailService,
        LoggerInterface $logger,
        array $config,
        LdapService $ldapService, // <--- ¡NUEVO ARGUMENTO!
        Source $sourceModel,
        callable $translator
    ) {
        $this->userModel = $userModel;
        $this->tokenModel = $tokenModel;
        $this->roleModel = $roleModel;
        $this->sessionService = $sessionService;
        $this->mailService = $mailService;
        $this->logger = $logger;
        $this->config = $config;
        $this->ldapService = $ldapService; // <--- ASIGNACIÓN
        $this->translator = $translator;
        $this->sourceModel = $sourceModel;

        // Limpiar tokens expirados cada vez que se instancie el servicio
        $this->tokenModel->cleanExpiredTokens();
    }

    /**
     * Intenta autenticar a un usuario.
     * @param string $username
     * @param string $password
     * @param int $sourceId ID de la fuente de usuario seleccionada en el login
     * @return bool True si la autenticación es exitosa, false de lo contrario.
     */
    public function authenticate(string $username, string $password, int $sourceId): bool
    {
        // 1. Obtener los detalles de la fuente de usuario seleccionada
        // Primero, necesitamos la instancia del modelo Source.
        // Como AuthService no tiene SourceModel en su constructor, lo obtenemos vía DI (patrón service locator para casos excepcionales)
        // O lo inyectamos directamente en AuthService si hay muchas interacciones.
        // Para simplificar y dado que ya inyectamos el ContainerInterface en ApiController, lo haremos aquí.
        // Si no, añadir Source $sourceModel en el constructor de AuthService.

        // Por la estructura actual de inyección, es mejor añadir Source $sourceModel al constructor de AuthService
        // Esto lo haremos en bootstrap.php.
        // Si no, sería algo como $sourceModel = $this->sessionService->getContainer()->get(Source::class); (si SessionService tuviera getContainer)

        // Asumimos que SourceModel ya está inyectado o que se puede obtener.
        // Para el AuthService, lo mejor es inyectar Source $sourceModel.
        // Lo añadiré en la definición del constructor en bootstrap.php.

        $source = $this->sourceModel->getById($sourceId); // <--- ¡CORRECCIÓN AQUÍ! Usar $this->sourceModel

        if (!$source || !$source['activo']) {
            $this->logger->warning("Intento de login fallido para usuario: $username. Fuente de usuario ID $sourceId no encontrada o inactiva.");
            return false;
        }

        $user = $this->userModel->getUserByUsername($username); // Buscar usuario en la DB local (para rol, activo, fuente)

        if (!$user) {
            // Si el usuario no existe en la DB local, pero la fuente NO es local,
            // podemos intentar autenticarlo contra el directorio y, si es exitoso,
            // crear su registro en la DB local (con rol por defecto, etc.).
            if ($source['tipo_fuente'] !== 'local') {
                $ldapAuthResult = $this->ldapService->authenticateUser($source, $username, $password);
                if ($ldapAuthResult['success']) {
                    $this->logger->info("Usuario LDAP/AD '{$username}' autenticado. Creando registro local.");
                    $defaultRole = $this->roleModel->getRoleByName('Consulta');
                    if (!$defaultRole) {
                        $this->logger->error("Rol 'Consulta' no encontrado. No se puede crear usuario externo.");
                        return false;
                    }
                    $defaultRoleId = $defaultRole['id'];

                    // Extraer datos del usuario desde LDAP/AD
                    $email = $ldapAuthResult['user_data']['email'] ?? null;
                    $nombre = $ldapAuthResult['user_data']['given_name'] ?? null;
                    $apellidos = $ldapAuthResult['user_data']['surname'] ?? null;
                    // El título (Sr./Sra.) no suele venir de AD, se deja null por defecto.
                    $titulo = null; 

                    $newUserId = $this->userModel->createUser(
                        $username, 
                        null,
                        $email,
                        $defaultRoleId,
                        true,
                        $sourceId, // id de la fuente
                        $source['nombre_friendly'], // nombre de la fuente
                        $nombre, // nuevo campo
                        $apellidos, // nuevo campo
                        $titulo // nuevo campo
                    );

                    if ($newUserId) {
                        $user = $this->userModel->getUserById($newUserId);
                        $this->logger->info("Usuario externo '{$username}' creado localmente.");
                    } else {
                        $this->logger->error("Fallo al crear registro local para usuario externo '{$username}'.");
                        return false;
                    }
                } else {
                    $this->logger->warning("Intento de login fallido para usuario externo: $username (Auth LDAP/AD fallida). Mensaje: {$ldapAuthResult['message']}");
                    return false;
                }
            } else {
                $this->logger->warning("Intento de login fallido para usuario local: $username (no encontrado en DB).");
                return false;
            }
        }

        // Si el usuario existe en DB local o acaba de ser creado (para LDAP/AD)
        if (!$user['activo']) {
            $this->logger->warning("Intento de login fallido para usuario: $username (inactivo en DB).");
            return false;
        }
        
        // Autenticar según el tipo de fuente
        if ($source['tipo_fuente'] === 'local') {
            // Autenticación local
            if (!password_verify($password, $user['password_hash'])) {
                $this->logger->warning("Intento de login fallido para usuario: $username (contraseña local incorrecta).");
                return false;
            }
            $this->logger->info("Usuario local '{$user['nombre_usuario']}' ha iniciado sesión correctamente.");

        } else { // LDAP o Active Directory
            // Si el usuario ya existe localmente y lo estamos re-autenticando contra LDAP/AD
            $ldapAuthResult = $this->ldapService->authenticateUser($source, $username, $password);
            if (!$ldapAuthResult['success']) {
                $this->logger->warning("Intento de login fallido para usuario externo: $username (Auth LDAP/AD fallida). Mensaje: {$ldapAuthResult['message']}");
                return false;
            }
            $this->logger->info("Usuario externo '{$user['nombre_usuario']}' autenticado correctamente via {$source['nombre_friendly']}.");

            // Opcional: Actualizar datos del usuario desde LDAP/AD en cada login
            $ldapData = $this->ldapService->getUserData($source, $username);
            if ($ldapData) {
                $updateData = [
                    'email' => $ldapData['email'] ?? $user['email'],
                    'nombre' => $ldapData['given_name'] ?? $user['nombre'],
                    'apellidos' => $ldapData['surname'] ?? $user['apellidos'],
                ];
                // Solo actualiza si hay cambios para evitar escrituras innecesarias
                if ($updateData['email'] !== $user['email'] || $updateData['nombre'] !== $user['nombre'] || $updateData['apellidos'] !== $user['apellidos']) {
                    $this->userModel->updateProfileData($user['id'], $updateData);
                }
            }
        }
        
        // Contraseña correcta. Ahora, comprobar si MFA está habilitado.
        if ($user['mfa_enabled']) {
            // No iniciar la sesión completa todavía. Guardar un estado temporal.
            $this->sessionService->startSession();
            $this->sessionService->set('mfa_user_id', $user['id']); // Guardar ID de usuario para verificar
            $this->sessionService->set('mfa_required', true);
            // No se establece la sesión completa hasta que se verifique el código MFA.
            return true; // Indicar éxito parcial para que el controlador redirija a la verificación MFA.
        } else {
            // MFA no está habilitado, iniciar sesión completa.
            $this->sessionService->startSession();
            $this->sessionService->set('user_id', $user['id']);
            $this->sessionService->set('username', $user['nombre_usuario']);
            $this->sessionService->set('role_id', $user['id_rol']);
            $this->sessionService->set('id_fuente_usuario', $user['id_fuente_usuario']);
            $this->sessionService->set('fuente_login_nombre', $user['fuente_login_nombre']);
            $role = $this->roleModel->getRoleById($user['id_rol']);
            $this->sessionService->set('role_name', $role['nombre'] ?? 'Desconocido');
            $this->userModel->updateLastLogin($user['id']);
        }
        
        return true;
    }

    /**
     * Cierra la sesión del usuario.
     */
    public function logout(): void
    {
        $username = $this->sessionService->get('username');
        $this->sessionService->destroySession();
        // Limpiar también cookies o sesiones específicas de MFA si se implementa
        if ($username) {
            $this->logger->info("Usuario '{$username}' ha cerrado sesión.");
        }
    }

    /**
     * Verifica si un usuario está autenticado.
     */
    public function isAuthenticated(): bool
    {
        return $this->sessionService->has('user_id');
    }

    /**
     * Obtiene el ID del usuario autenticado.
     */
    public function getAuthenticatedUserId(): ?int
    {
        return $this->sessionService->get('user_id');
    }

    /**
     * Obtiene el nombre del rol del usuario autenticado.
     */
    public function getAuthenticatedUserRoleName(): ?string
    {
        return $this->sessionService->get('role_name');
    }

    /**
     * Verifica si el usuario autenticado tiene un rol específico o superior.
     * @param string $requiredRoleName
     * @return bool
     */
    public function hasRole(string $requiredRoleName): bool
    {
        if (!$this->isAuthenticated()) {
            return false;
        }

        $userRoleName = $this->getAuthenticatedUserRoleName();

        $rolesHierarchy = [
            'Administrador' => 3,
            'Modificacion' => 2,
            'Consulta' => 1,
        ];

        if (!isset($rolesHierarchy[$userRoleName]) || !isset($rolesHierarchy[$requiredRoleName])) {
            return false;
        }

        return $rolesHierarchy[$userRoleName] >= $rolesHierarchy[$requiredRoleName];
    }

    /**
     * Inicia el proceso de recuperación de contraseña.
     * Genera un token y envía un correo electrónico.
     * @param string $email
     * @return bool True si el correo se envió con éxito, false de lo contrario.
     */
    public function initiatePasswordReset(string $email): bool
    {
        $this->logger->info("Inicio de proceso de reseteo de contraseña para: $email");
        $user = $this->userModel->getUserByEmail($email);

        if (!$user) {
            $this->logger->info("Intento de recuperación de contraseña para email no registrado: $email");
            return true; // Falso positivo para seguridad
        }

        // --- Validar si la cuenta es local antes de permitir reset ---
        $source = $this->sourceModel->getById($user['id_fuente_usuario']);
        if (!$source || $source['tipo_fuente'] !== 'local') {
             $this->logger->warning("Intento de recuperación de contraseña para usuario no local: {$user['nombre_usuario']}. Solo usuarios locales pueden resetear su contraseña.");
             return true; // Falso positivo
        }

        // Generar un token único y seguro
        $token = bin2hex(random_bytes(32)); // 64 caracteres hexadecimales
        $hashedToken = hash('sha256', $token); // Hashear el token para la BBDD
        $expiration = new DateTime();
        $expiration->add(new DateInterval('PT1H')); // Token válido por 1 hora

        // Limpiamos los tokens expirados justo antes de crear uno nuevo. Esto evita que la tabla crezca indefinidamente.
        $this->tokenModel->cleanExpiredTokens();

        try {
            // Llamamos a createToken una sola vez.
            // Si falla, lanzará una PDOException que será capturada por el bloque catch.
            $this->logger->info("Creando token de recuperación para usuario ID: {$user['id']}");
            $this->tokenModel->createToken($user['id'], $hashedToken, $expiration->format('Y-m-d H:i:s'));
        } catch (\PDOException $e) {
            $this->logger->error("PDOException al crear token de recuperación para usuario {$user['id']}: " . $e->getMessage());
            return false;
        }

        // Enviar correo electrónico
        $t = $this->translator;
        $resetLink = $this->config['app']['url'] . '/reset-password?token=' . $token;
        $subject = $t('password_reset_subject');
        
        $templateData = [
            'username' => $user['nombre_usuario'],
            'reset_link' => $resetLink
        ];

        $this->logger->info("Enviando correo de reseteo a {$user['email']} con asunto '{$subject}' y plantilla 'password_reset'");
        // Usar MailService para enviar el correo usando una plantilla
        if ($this->mailService->sendEmail($user['email'], $subject, 'password_reset', $templateData)) {
             return true;
        } else {
            return false;
        }
    }

    /**
     * Obtiene los datos de un token de recuperación de contraseña.
     * @param string $tokenValue
     * @return array|false
     */
    public function getTokenData(string $tokenValue): array|false
    {
        // Hasheamos el token que viene de la URL antes de pasarlo al modelo.
        // El modelo ahora espera recibir un token ya hasheado para buscarlo.
        $hashedToken = hash('sha256', $tokenValue);
        return $this->tokenModel->getToken($hashedToken);
    }

    /**
     * Resetea la contraseña de un usuario usando un token.
     * @param string $tokenValue
     * @param string $newPassword
     * @return bool True si la contraseña se reseteó con éxito, false de lo contrario.
     */
    public function resetPassword(string $tokenValue, string $newPassword): bool
    {
        // Se utiliza el método local getTokenData que a su vez llama al modelo.
        // Esto asegura que la lógica de hasheo y búsqueda es consistente.
        $token = $this->getTokenData($tokenValue); 

        if (!$token || $token['usado'] || new \DateTime('now', new \DateTimeZone('UTC')) > new \DateTime($token['fecha_expiracion'], new \DateTimeZone('UTC'))) {
            $this->logger->warning("Intento de uso de token de recuperación inválido, usado o expirado: $tokenValue");
            return false;
        }

        $newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);

        try { // Añadido try-catch para updatePassword y markTokenAsUsed
            if ($this->userModel->updatePassword($token['id_usuario'], $newPasswordHash)) {
                $this->tokenModel->markTokenAsUsed($token['id']);
                $this->logger->info("Contraseña reseteada con éxito para usuario ID: {$token['id_usuario']}");
                return true;
            }
        } catch (\PDOException $e) {
            $this->logger->error("PDOException al resetear contraseña para usuario ID {$token['id_usuario']}: " . $e->getMessage());
            return false; // Fallo de DB al actualizar pass o token
        } catch (Exception $e) {
            $this->logger->error("Excepción general al resetear contraseña para usuario ID {$token['id_usuario']}: " . $e->getMessage());
            return false; // Otros errores
        }

        $this->logger->error("Error al resetear contraseña para usuario ID: {$token['id_usuario']}");
        return false;
    }

    /**
     * Habilita el usuario administrador por defecto si está configurado en .env.
     * Esto se ejecuta una vez al inicio de la aplicación o cuando se necesite reactivar.
     * @return bool True si se creó/actualizó, false si falló o no está habilitado.
     */
    public function ensureDefaultAdminUser(): bool
    {
        if (!$this->config['app']['admin_user']['enabled']) {
            return false; // Usuario administrador por defecto no está habilitado
        }

        $defaultUsername = $this->config['app']['admin_user']['username'];
        $defaultPassword = $this->config['app']['admin_user']['password'];
        $defaultEmail = $this->config['app']['admin_user']['email'];

        $adminRole = $this->roleModel->getRoleByName('Administrador');
        if (!$adminRole) {
            $this->logger->error("Rol 'Administrador' no encontrado. No se puede crear el usuario administrador por defecto.");
            return false;
        }
        $adminRoleId = $adminRole['id'];

        // Obtener la fuente de usuario 'Local' por defecto
        $localSource = $this->sourceModel->getSourceByName('Usuarios Locales'); // <--- ¡CORRECCIÓN AQUÍ!
        if (!$localSource) {
            $this->logger->error("Fuente de usuario 'Local' no encontrada. No se puede crear el usuario administrador por defecto.");
            return false;
        }
        $localSourceId = $localSource['id'];
        $localSourceName = $localSource['nombre_friendly'];

        $user = $this->userModel->getUserByUsername($defaultUsername);

        try { // Añadido try-catch para operaciones de DB
            if (!$user) {
                // Crear el usuario si no existe
                $hashedPassword = password_hash($defaultPassword, PASSWORD_DEFAULT);
                $userId = $this->userModel->createUser(
                    $defaultUsername,
                    $hashedPassword,
                    $defaultEmail,
                    $adminRoleId,
                    true,
                    $localSourceId, // <--- Pasar el ID de la fuente local
                    $localSourceName, // <--- Pasar el nombre de la fuente local
                    'Admin', // Nombre por defecto
                    'User', // Apellido por defecto
                    null // Título por defecto
                );
                if ($userId) {
                    $this->logger->info("Usuario administrador por defecto '{$defaultUsername}' creado.");
                    return true;
                } else {
                    $this->logger->error("Error al crear usuario administrador por defecto.");
                    return false;
                }
            } else {
                // Actualizar si existe, solo la contraseña si es diferente o el rol/activo/fuente incorrectos
                $currentPasswordHash = $user['password_hash'];
                $newPasswordHash = password_hash($defaultPassword, PASSWORD_DEFAULT);

                // Solo actualiza la contraseña si es diferente, o si el usuario no es activo/rol incorrecto/fuente incorrecta
                if (!password_verify($defaultPassword, $currentPasswordHash) ||
                    !$user['activo'] ||
                    $user['id_rol'] !== $adminRoleId ||
                    $user['id_fuente_usuario'] !== $localSourceId) // <--- Comprobar también la fuente
                {
                    if ($this->userModel->updateUser(
                            $user['id'],
                            $defaultUsername,
                            $defaultEmail,
                            $adminRoleId,
                            true,
                            $localSourceId, // <--- Pasar el ID de la fuente local
                            $localSourceName) && // <--- Pasar el nombre de la fuente local
                        $this->userModel->updatePassword($user['id'], $newPasswordHash)) { // Actualizar la pass
                        $this->logger->info("Usuario administrador por defecto '{$defaultUsername}' actualizado (contraseña/estado/fuente).");
                        return true;
                    } else {
                        $this->logger->error("Error al actualizar usuario administrador por defecto.");
                        return false;
                    }
                }
                $this->logger->debug("Usuario administrador por defecto '{$defaultUsername}' ya existe y está configurado correctamente.");
                return true;
            }
        } catch (\PDOException $e) {
            $this->logger->error("PDOException en ensureDefaultAdminUser: " . $e->getMessage() . " Code: " . $e->getCode());
            return false;
        } catch (Exception $e) {
            $this->logger->error("Excepción general en ensureDefaultAdminUser: " . $e->getMessage() . " File: " . $e->getFile() . " Line: " . $e->getLine());
            return false;
        }
    }
}
