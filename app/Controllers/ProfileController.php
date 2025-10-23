<?php
// app/Controllers/ProfileController.php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use League\Plates\Engine as PlatesEngine;
use App\Services\SessionService;
use Psr\Log\LoggerInterface;
use App\Services\LanguageService; // <-- ¡NUEVO!
use App\Models\TrustedDevice; // <-- ¡NUEVO!
use App\Models\User;
use App\Models\Source;
use PDO;
use PDOException;
use Exception;

class ProfileController
{
    private PlatesEngine $view;
    private SessionService $session;
    private LoggerInterface $logger;
    private $translator;
    private User $userModel;
    private Source $sourceModel;
    private PDO $db;
    private TrustedDevice $trustedDeviceModel; // <-- ¡NUEVO!
    private LanguageService $languageService; // <-- ¡NUEVO!
    private array $config;

    public function __construct(
        PlatesEngine $view,
        SessionService $session,
        LoggerInterface $logger,
        callable $translator,
        User $userModel,
        Source $sourceModel,
        PDO $db,
        TrustedDevice $trustedDeviceModel, // <-- ¡NUEVO!
        LanguageService $languageService, // <-- ¡NUEVO!
        array $config
    ) {
        $this->view = $view;
        $this->session = $session;
        $this->logger = $logger;
        $this->translator = $translator;
        $this->userModel = $userModel;
        $this->sourceModel = $sourceModel;
        $this->db = $db;
        $this->trustedDeviceModel = $trustedDeviceModel; // <-- ¡NUEVO!
        $this->languageService = $languageService; // <-- ¡NUEVO!
        $this->config = $config;
    }

    /**
     * Muestra la página del perfil del usuario.
     */
    public function showProfile(Request $request, Response $response): Response
    {
        $t = $this->translator;
        $notificationTypes = [];
        $user = [];
        $activeLanguages = []; // <-- ¡NUEVO!
        $trustedDevices = []; // <-- ¡NUEVO!
        $isLocalUser = false;
        $userPreferredLanguage = null; // <-- ¡NUEVO!

        try {
            $userId = $this->session->get('user_id');
            $user = $this->userModel->getUserById($userId);
            $source = $this->sourceModel->getById($user['id_fuente_usuario']);
            $isLocalUser = ($source && $source['tipo_fuente'] === 'local');
 
            $userPreferredLanguage = $user['preferred_language_code'] ?? null; // <-- ¡NUEVO!
            // <-- ¡NUEVO! Obtener idiomas activos
            $activeLanguages = $this->languageService->getActiveLanguages();

            // <-- ¡NUEVO! Obtener dispositivos de confianza
            $trustedDevices = $this->trustedDeviceModel->findByUserId($userId) ?: [];

            // Obtener tipos de notificación
            $stmt = $this->db->query("SELECT id, clave, nombre_visible FROM tipos_notificacion ORDER BY id");
            $notificationTypes = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Obtener preferencias del usuario
            $stmt = $this->db->prepare("SELECT id_tipo_notificacion FROM usuario_notificacion_preferencias WHERE id_usuario = :user_id AND habilitado = 1");
            $stmt->execute(['user_id' => $userId]);
            $prefs = $stmt->fetchAll(PDO::FETCH_COLUMN);
            $prefs = array_flip($prefs); // Convertir valores en claves para búsqueda rápida

            // Combinar tipos con preferencias
            foreach ($notificationTypes as &$type) {                
                $type['habilitado'] = isset($prefs[$type['id']]);
                // Traducir el nombre visible usando la clave
                $type['nombre_visible'] = $t('notification_' . $type['clave']);
            }
        } catch (PDOException $e) {
            $this->logger->error("Error de base de datos al mostrar el perfil: " . $e->getMessage());
            $this->session->addFlashMessage('danger', $t('operation_failed'));
        }

        $flashMessages = $this->session->getFlashMessages();
        $html = $this->view->render('profile/view', [
            'user' => $user,
            'isLocalUser' => $isLocalUser,
            'notificationTypes' => $notificationTypes,
            'activeLanguages' => $activeLanguages, // <-- ¡NUEVO!
            'trustedDevices' => $trustedDevices, // <-- ¡NUEVO!
            'userPreferredLanguage' => $userPreferredLanguage, // <-- ¡NUEVO!
            'flashMessages' => $flashMessages
        ]);
        $response->getBody()->write($html);
        return $response;
    }

    /**
     * Procesa la revocación de un dispositivo de confianza.
     */
    public function revokeDevice(Request $request, Response $response, array $args): Response
    {
        $t = $this->translator;
        $userId = $this->session->get('user_id');
        $tokenHash = $args['token_hash'] ?? null;

        if (!$userId || !$tokenHash) {
            $this->session->addFlashMessage('danger', $t('mfa_device_revocation_failed'));
            return $response->withHeader('Location', '/profile')->withStatus(302);
        }

        // Verificar que el dispositivo pertenece al usuario actual por seguridad
        $device = $this->trustedDeviceModel->findByTokenHash($tokenHash);

        if ($device && (int)$device['id_usuario'] === $userId) {
            if ($this->trustedDeviceModel->deleteByTokenHash($tokenHash)) {
                // Si la cookie actual coincide con el dispositivo revocado, la eliminamos del navegador
                $currentCookie = $_COOKIE['trusted_device_token'] ?? null;
                if ($currentCookie && hash('sha256', $currentCookie) === $tokenHash) {
                    setcookie('trusted_device_token', '', time() - 3600, '/');
                }
                $this->session->addFlashMessage('success', $t('mfa_device_revoked_successfully'));
            } else {
                $this->session->addFlashMessage('danger', $t('mfa_device_revocation_failed'));
            }
        } else {
            $this->session->addFlashMessage('danger', $t('mfa_device_revocation_failed'));
        }

        return $response->withHeader('Location', '/profile')->withStatus(302);
    }

    /**
     * Procesa la actualización del perfil del usuario.
     */
    public function updateProfile(Request $request, Response $response): Response
    {
        $this->logger->debug("--- DEBUG: ProfileController::updateProfile INICIADO ---");

        $t = $this->translator;
        $userId = $this->session->get('user_id');
        $user = $this->userModel->getUserById($userId);
        $source = $this->sourceModel->getById($user['id_fuente_usuario']);
        $isLocalUser = ($source && $source['tipo_fuente'] === 'local');
        
        $this->logger->debug("UserID: {$userId}, isLocalUser: " . ($isLocalUser ? 'true' : 'false'));

        $data = $request->getParsedBody();
        $files = $request->getUploadedFiles();

        $this->logger->debug("Datos recibidos (data): " . json_encode($data, JSON_UNESCAPED_UNICODE));
        $this->logger->debug("Ficheros recibidos (files): " . json_encode(array_keys($files)));

        $updateData = [];

        // Campos editables solo para usuarios locales
        if ($isLocalUser) {
            $updateData['email'] = filter_var($data['email'] ?? '', FILTER_VALIDATE_EMAIL) ? $data['email'] : $user['email'];
            $updateData['titulo'] = htmlspecialchars(trim($data['titulo'] ?? ''), ENT_QUOTES, 'UTF-8');
            $updateData['nombre'] = htmlspecialchars(trim($data['nombre'] ?? ''), ENT_QUOTES, 'UTF-8');
            $updateData['apellidos'] = htmlspecialchars(trim($data['apellidos'] ?? ''), ENT_QUOTES, 'UTF-8');
            $updateData['preferred_language_code'] = trim($data['preferred_language'] ?? null); // <-- ¡NUEVO!
        }

        // Lógica para subir la foto de perfil
        if (isset($files['avatar']) && $files['avatar']->getError() === UPLOAD_ERR_OK) {
            $this->logger->debug("Procesando subida de avatar...");
            $uploadDir = $this->config['paths']['uploads'] . '/avatars/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            $extension = strtolower(pathinfo($files['avatar']->getClientFilename(), PATHINFO_EXTENSION));
            $filename = 'user_' . $userId . '.' . $extension;
            try {
                $files['avatar']->moveTo($uploadDir . $filename);
                $updateData['profile_image_path'] = '/uploads/avatars/' . $filename;
            } catch (Exception $e) {
                $this->logger->error("Error al mover la imagen de perfil: " . $e->getMessage());
                $this->session->addFlashMessage('danger', $t('profile_update_failed'));
                return $response->withHeader('Location', '/profile')->withStatus(302);
            }
        }

        // Actualizar datos del usuario si hay algo que cambiar
        if (!empty($updateData)) {
            $this->logger->debug("Datos a actualizar en BBDD (updateData): " . json_encode($updateData, JSON_UNESCAPED_UNICODE));
            $this->userModel->updateProfileData($userId, $updateData);
            $this->logger->debug("Llamada a userModel->updateProfileData completada.");
        }

        // Actualizar preferencias de notificación
        $this->db->beginTransaction();
        $this->logger->debug("Iniciando transacción para actualizar notificaciones...");
        try {
            // Primero, borramos las preferencias existentes para este usuario
            $deleteStmt = $this->db->prepare("DELETE FROM usuario_notificacion_preferencias WHERE id_usuario = :user_id");
            $deleteStmt->execute(['user_id' => $userId]);

            // Luego, insertamos las nuevas preferencias
            $insertStmt = $this->db->prepare(
                "INSERT INTO usuario_notificacion_preferencias (id_usuario, id_tipo_notificacion) VALUES (:user_id, :type_id)"
            );

            // Los checkboxes/switches solo envían valor si están marcados.
            // Así que iteramos sobre los que llegaron en el POST.
            if (isset($data['notifications']) && is_array($data['notifications'])) {
                // El `name` del input es `notifications[id_del_tipo]`
                foreach ($data['notifications'] as $typeId => $value) {
                    $insertStmt->execute([
                        'user_id' => $userId,
                        'type_id' => (int)$typeId
                    ]);
                }
            }
            $this->db->commit();
            $this->logger->debug("Transacción de notificaciones completada (commit).");
        } catch (Exception $e) {
            $this->db->rollBack();
            $this->logger->error("Error al actualizar las preferencias de notificación: " . $e->getMessage());
            $this->session->addFlashMessage('danger', $t('profile_update_failed'));
            $this->logger->debug("--- DEBUG: FIN por error en notificaciones ---");
            return $response->withHeader('Location', '/profile')->withStatus(302);
        }

        // --- Lógica para la cookie y la sesión de idioma (solo si se ha cambiado) ---
        $this->logger->debug("Comprobando si se debe actualizar el idioma...");
        if ($isLocalUser && isset($data['preferred_language'])) {
            $newLang = $data['preferred_language'];
            $this->logger->debug("Nuevo idioma detectado: {$newLang}. Idioma actual: " . ($user['preferred_language_code'] ?? 'ninguno'));
            // Solo actuar si el idioma ha cambiado realmente
            if ($newLang && $newLang !== $user['preferred_language_code']) {
                // 1. Actualizar la sesión actual para un cambio inmediato
                $this->session->set('lang', $newLang);
                // 2. Crear/Actualizar la cookie de preferencia de idioma solo si se ha dado consentimiento.
                $cookieConsent = $request->getCookieParams()['cookie_consent_status'] ?? 'not_set';
                if ($cookieConsent === 'accepted') {
                    $this->languageService->setLanguageCookie($newLang);
                } else {
                    $this->languageService->deleteLanguageCookie();
                }
            }
        }
        $this->logger->debug("Lógica de idioma completada.");

        $this->session->addFlashMessage('success', $t('profile_updated_successfully'));
        $this->logger->debug("--- DEBUG: ProfileController::updateProfile FINALIZADO CON ÉXITO ---");
        return $response->withHeader('Location', '/profile')->withStatus(302);
    }
}