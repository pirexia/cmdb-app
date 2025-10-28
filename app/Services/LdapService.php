<?php
// app/Services/LdapService.php

namespace App\Services;

use Psr\Log\LoggerInterface;
use Exception; // Asegúrate de que esta línea esté presente

class LdapService
{
    private LoggerInterface $logger;
    private $translator; // Para la función t()

    public function __construct(LoggerInterface $logger, callable $translator)
    {
        $this->logger = $logger;
        $this->translator = $translator;
    }

    /**
     * Prueba la conexión y el bind a un servidor LDAP/Active Directory.
     * @param array $config Los detalles de configuración de la fuente LDAP/AD.
     * [
     * 'host' => 'ldap.example.com',
     * 'port' => 389,
     * 'base_dn' => 'dc=example,dc=com',
     * 'bind_dn' => 'cn=binduser,ou=users,dc=example,dc=com',
     * 'bind_password' => 'password',
     * 'user_filter' => '(sAMAccountName=%s)',
     * 'group_filter' => '(cn=%s)', // Added for completeness
     * 'use_tls' => 0|1,
     * 'use_ssl' => 0|1,
     * 'ca_cert_path' => '/path/to/ca.crt',
     * 'timeout' => 5
     * ]
     * @return array ['success' => bool, 'message' => string]
     */
    public function testConnection(array $config): array
    {
        $t = $this->translator;
        $host = $config['host'] ?? '';
        $port = $config['port'] ?? 389;
        $baseDn = $config['base_dn'] ?? '';
        $bindDn = $config['bind_dn'] ?? null;
        $bindPassword = $config['bind_password'] ?? null;
        $useTls = (bool)($config['use_tls'] ?? false);
        $useSsl = (bool)($config['use_ssl'] ?? false);
        $caCertPath = $config['ca_cert_path'] ?? null;
        $timeout = $config['timeout'] ?? 5;

        // Validaciones previas a la conexión LDAP
        if (!function_exists('ldap_connect')) {
            $this->logger->error($t('ldap_extension_not_loaded'));
            return ['success' => false, 'message' => $t('ldap_extension_not_loaded')];
        }
        if (empty($host)) {
             return ['success' => false, 'message' => $t('ldap_host_required') ?? 'El host LDAP/AD es obligatorio.'];
        }
        if (empty($baseDn)) {
             return ['success' => false, 'message' => $t('ldap_base_dn_required') ?? 'El Base DN es obligatorio.'];
        }

        $ldapConnection = false; // Inicializar a false
        try {
            $uri = ($useSsl ? 'ldaps://' : 'ldap://') . $host . ':' . $port;
            $this->logger->info($t('ldap_attempting_connection', ['%uri%' => $uri, '%timeout%' => $timeout]));

            // Establecer opciones antes de la conexión
            ldap_set_option(null, LDAP_OPT_PROTOCOL_VERSION, 3); // Usar LDAPv3
            ldap_set_option(null, LDAP_OPT_REFERRALS, 0);       // No seguir referencias

            // Configurar TLS/SSL para el certificado CA y verificación estricta si es necesario
            if (($useTls || $useSsl) && !empty($caCertPath)) {
                if (!file_exists($caCertPath) || !is_readable($caCertPath)) {
                    $this->logger->error($t('ldap_ca_cert_not_found_readable', ['%path%' => $caCertPath]));
                    return ['success' => false, 'message' => $t('ldap_ca_cert_error')];
                }
                ldap_set_option(null, LDAP_OPT_X_TLS_CACERTFILE, $caCertPath);
                // Exigir certificado solo si se proporciona CA y no se permite auto-firmado
                // Para pruebas, a menudo se relaja (LDAP_OPT_X_TLS_ALLOW_SELFSIGNED, LDAP_OPT_X_TLS_NEVER)
                // Usamos DEMAND para un entorno más seguro, asumiendo un CA válido.
                ldap_set_option(null, LDAP_OPT_X_TLS_REQUIRE_CERT, LDAP_OPT_X_TLS_DEMAND); 
            } else {
                 // Si no se usa TLS/SSL o no hay CA, deshabilitar verificación estricta para evitar errores
                 // Esto es importante para permitir conexiones a servidores con certificados auto-firmados o sin CA configurada.
                 ldap_set_option(null, LDAP_OPT_X_TLS_REQUIRE_CERT, LDAP_OPT_X_TLS_NEVER);
            }
            
            // Establecer timeout de conexión
            ldap_set_option(null, LDAP_OPT_NETWORK_TIMEOUT, $timeout);

            // Conectar
            // Suprimir warnings de ldap_connect si falla la conexión directamente.
            $ldapConnection = @ldap_connect($uri); 

            if (!$ldapConnection) {
                // Si la conexión falla, no hay un recurso. El error es global.
                // No se puede usar ldap_error() porque no hay un recurso de conexión.
                $this->logger->error($t('ldap_connection_failed', ['%host%' => $host, '%port%' => $port, '%error%' => 'Unknown connection error']));
                return ['success' => false, 'message' => $t('ldap_connection_error', ['%host%' => $host, '%port%' => $port])];
            }

            // Iniciar TLS si es necesario (para ldap://)
            if ($useTls) {
                if (!@ldap_start_tls($ldapConnection)) {
                    $this->logger->error($t('ldap_starttls_failed', ['%error%' => ldap_error($ldapConnection)]));
                    ldap_close($ldapConnection);
                    return ['success' => false, 'message' => $t('ldap_starttls_error', ['%error%' => ldap_error($ldapConnection)])];
                }
            }

            // Realizar bind (autenticación)
            if (!empty($bindDn) && !empty($bindPassword)) {
                $this->logger->info($t('ldap_attempting_bind', ['%bind_dn%' => $bindDn]));
                if (!@ldap_bind($ldapConnection, $bindDn, $bindPassword)) {
                    $this->logger->error($t('ldap_bind_failed', ['%bind_dn%' => $bindDn, '%error%' => ldap_error($ldapConnection)]));
                    ldap_close($ldapConnection);
                    return ['success' => false, 'message' => $t('ldap_bind_error', ['%bind_dn%' => $bindDn, '%error%' => ldap_error($ldapConnection)])];
                }
                $this->logger->info($t('ldap_bind_successful', ['%bind_dn%' => $bindDn]));
            } else {
                // Anonymous bind (si no se proporciona Bind DN/Password)
                $this->logger->info($t('ldap_attempting_anonymous_bind'));
                if (!@ldap_bind($ldapConnection)) { // Intenta bind anónimo
                    $this->logger->warning($t('ldap_anonymous_bind_failed', ['%error%' => ldap_error($ldapConnection)]));
                    // Aquí, podríamos decidir si es un fallo crítico o no. Para el test, lo marcamos como fallo.
                    ldap_close($ldapConnection);
                    return ['success' => false, 'message' => $t('ldap_anonymous_bind_error', ['%error%' => ldap_error($ldapConnection)]) ?? 'Bind anónimo fallido.'];
                } else {
                    $this->logger->info($t('ldap_anonymous_bind_successful'));
                }
            }

            // Si llegamos aquí, la conexión y el bind básico fueron exitosos.
            // Para una prueba más completa, podrías intentar una búsqueda de usuario aquí
            // usando el user_filter y un usuario de prueba si se proporciona.
            // Por simplicidad, solo probamos la conexión/bind.

            ldap_close($ldapConnection);
            return ['success' => true, 'message' => $t('connection_successful')];

        } catch (Exception $e) { // Captura cualquier otra excepción PHP (no LDAP)
            $this->logger->error($t('ldap_general_test_error', ['%message%' => $e->getMessage()]));
            if ($ldapConnection) { ldap_close($ldapConnection); }
            return ['success' => false, 'message' => $t('ldap_unexpected_error', ['%message%' => $e->getMessage()])];
        }
    }

    /**
     * Autentica un usuario contra una fuente LDAP/AD.
     * Esto NO es un test de conexión, es para el LOGIN.
     * @param array $sourceConfig Configuración de la fuente LDAP/AD.
     * @param string $username Nombre de usuario a autenticar.
     * @param string $password Contraseña del usuario.
     * @return array ['success' => bool, 'message' => string, 'user_data' => array|null]
     */
    public function authenticateUser(array $sourceConfig, string $username, string $password): array
    {
        $t = $this->translator;
        $host = $sourceConfig['host'] ?? '';
        $port = $sourceConfig['port'] ?? 389;
        $baseDn = $sourceConfig['base_dn'] ?? '';
        $userFilter = $sourceConfig['user_filter'] ?? '';
        $useTls = (bool)($sourceConfig['use_tls'] ?? false);
        $useSsl = (bool)($sourceConfig['use_ssl'] ?? false);
        $caCertPath = $sourceConfig['ca_cert_path'] ?? null;
        $timeout = $sourceConfig['timeout'] ?? 5;

        if (!function_exists('ldap_connect')) {
            $this->logger->error($t('ldap_extension_not_loaded'));
            return ['success' => false, 'message' => $t('ldap_extension_not_loaded')];
        }

        $ldapConnection = false;
        try {
            $uri = ($useSsl ? 'ldaps://' : 'ldap://') . $host . ':' . $port;
            
            ldap_set_option(null, LDAP_OPT_PROTOCOL_VERSION, 3);
            ldap_set_option(null, LDAP_OPT_REFERRALS, 0);

            if (($useTls || $useSsl) && !empty($caCertPath)) {
                ldap_set_option(null, LDAP_OPT_X_TLS_CACERTFILE, $caCertPath);
                ldap_set_option(null, LDAP_OPT_X_TLS_REQUIRE_CERT, LDAP_OPT_X_TLS_DEMAND); 
            } else {
                 ldap_set_option(null, LDAP_OPT_X_TLS_REQUIRE_CERT, LDAP_OPT_X_TLS_NEVER);
            }
            ldap_set_option(null, LDAP_OPT_NETWORK_TIMEOUT, $timeout);

            $ldapConnection = @ldap_connect($uri);

            if (!$ldapConnection) {
                $this->logger->error($t('ldap_auth_connection_failed', ['%host%' => $host, '%port%' => $port]));
                return ['success' => false, 'message' => $t('ldap_auth_connection_error')];
            }

            if ($useTls) {
                if (!@ldap_start_tls($ldapConnection)) {
                    $this->logger->error($t('ldap_auth_starttls_failed', ['%error%' => ldap_error($ldapConnection)]));
                    ldap_close($ldapConnection);
                    return ['success' => false, 'message' => $t('ldap_auth_starttls_error', ['%error%' => ldap_error($ldapConnection)])];
                }
            }

            // Realizar un bind inicial con credenciales de búsqueda (si existen)
            $searchBindDn = $sourceConfig['bind_dn'] ?? null;
            $searchBindPassword = $sourceConfig['bind_password'] ?? null;
            if (!empty($searchBindDn) && !empty($searchBindPassword)) {
                if (!@ldap_bind($ldapConnection, $searchBindDn, $searchBindPassword)) {
                    $this->logger->error($t('ldap_auth_search_bind_failed', ['%bind_dn%' => $searchBindDn, '%error%' => ldap_error($ldapConnection)]));
                    ldap_close($ldapConnection);
                    return ['success' => false, 'message' => $t('ldap_auth_search_bind_error', ['%error%' => ldap_error($ldapConnection)])];
                }
            } else {
                // Intenta bind anónimo para búsqueda si no se proporciona bind_dn/password
                @ldap_bind($ldapConnection);
            }

            // Buscar el DN del usuario a autenticar
            $userRdn = sprintf($userFilter, ldap_escape($username, '', LDAP_ESCAPE_FILTER)); // Escapar nombre de usuario para el filtro
            $searchResult = @ldap_search($ldapConnection, $baseDn, $userRdn, ['dn']); // Buscar solo el DN
            
            if (!$searchResult) {
                $this->logger->warning($t('ldap_auth_user_search_failed', ['%username%' => $username, '%filter%' => $userRdn, '%error%' => ldap_error($ldapConnection)]));
                ldap_close($ldapConnection);
                return ['success' => false, 'message' => $t('incorrect_credentials')]; // Mensaje genérico por seguridad
            }

            $entries = ldap_get_entries($ldapConnection, $searchResult);
            if (!isset($entries[0]) || !isset($entries[0]['dn'])) {
                $this->logger->warning($t('ldap_auth_user_dn_not_found', ['%username%' => $username]));
                ldap_close($ldapConnection);
                return ['success' => false, 'message' => $t('incorrect_credentials')];
            }
            $userDn = $entries[0]['dn']; // El DN completo del usuario

            // Intentar autenticar con las credenciales del usuario
            if (!@ldap_bind($ldapConnection, $userDn, $password)) {
                $this->logger->warning($t('ldap_auth_user_bind_failed', ['%username%' => $username, '%error%' => ldap_error($ldapConnection)]));
                ldap_close($ldapConnection);
                return ['success' => false, 'message' => $t('incorrect_credentials')];
            }
            
            // Si llegamos aquí, autenticación exitosa.
            // Opcional: Recuperar más datos del usuario (email, grupos, etc.) para el perfil.
            $userData = [
                'username' => $username,
                'email' => $entries[0]['mail'][0] ?? null, // Ejemplo, depende del esquema LDAP
                'dn' => $userDn
                // Podrías buscar roles/grupos aquí si es necesario
            ];

            ldap_close($ldapConnection);
            return ['success' => true, 'message' => $t('authentication_successful'), 'user_data' => $userData];

        } catch (Exception $e) {
            $this->logger->error($t('ldap_auth_unexpected_error', ['%message%' => $e->getMessage()]));
            if ($ldapConnection) { ldap_close($ldapConnection); }
            return ['success' => false, 'message' => $t('authentication_failed_unexpected_error') ?? 'Ocurrió un error inesperado durante la autenticación.'];
        }
    }
}
