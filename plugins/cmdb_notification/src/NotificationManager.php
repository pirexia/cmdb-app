<?php

namespace CmdbNotification;

use CmdbNotification\Services\Config;
use CmdbNotification\Services\EmailService;
use CmdbNotification\Checkers\AssetExpirationChecker;
use CmdbNotification\Checkers\ContractExpirationChecker;
use DateTime;
use DateInterval;
use Psr\Log\LoggerInterface;
use PDO;

class NotificationManager
{
    private PDO $db;
    private EmailService $emailService;
    private LoggerInterface $logger;
    private array $checkers = [];
    private $translator;

    public function __construct(PDO $db, LoggerInterface $logger, callable $translator, EmailService $emailService)
    {
        $this->db = $db;
        $this->logger = $logger;
        $this->translator = $translator;
        $this->emailService = $emailService;
    }

    /**
     * Añade un checker al gestor.
     * @param object $checker
     */
    public function addChecker(object $checker): void
    {
        $this->checkers[] = $checker;
    }

    /**
     * Procesa y envía todas las notificaciones configuradas.
     */
    public function sendNotifications(): void
    {
        $t = $this->translator;
        $expiringItemsFound = false;

        foreach ($this->checkers as $checker) {
            $notificationKey = $checker->getNotificationKey();
            $this->logger->info("Procesando notificaciones para: " . $notificationKey);

            $recipients = $this->getSubscribedUsers($notificationKey);

            if (empty($recipients)) {
                $this->logger->info("No hay usuarios suscritos para '{$notificationKey}'. Saltando.");
                continue;
            }

            $items = $checker->getExpiringItems();

            if (!empty($items)) {
                $this->logger->info(count($items) . " elementos encontrados para '{$notificationKey}'. Preparando envío a " . count($recipients) . " usuarios.");
                $expiringItemsFound = true;
                
                $contentBlock = $checker->render($items);
                $subject = $t('notification_subject_' . $notificationKey);
                
                $this->emailService->sendBulkEmails($recipients, $subject, 'notification_layout', [
                    'content' => $contentBlock,
                    't' => $t, // <-- AÑADIR ESTA LÍNEA
                    'subject' => $subject
                ]);
            } else {
                $this->logger->info("No hay elementos que expiren para '{$notificationKey}'. No se enviarán correos.");
            }
        }

        if (!$expiringItemsFound) {
            $config = new Config(__DIR__ . '/../../cmdb_app');
            $daysAdvance = $config->get('notifications.days_advance', 30);
            $this->logger->info($t('no_expiring_items', ['%s' => $daysAdvance]));
        }

        $this->logger->info($t('cron_job_notifications_finished'));
    }

    /**
     * Obtiene una lista de correos electrónicos de usuarios suscritos a un tipo de notificación.
     * @param string $notificationKey La clave del tipo de notificación (ej. 'expiring_assets').
     * @return array Lista de emails.
     */
    private function getSubscribedUsers(string $notificationKey): array
    {
        $sql = "
            SELECT u.email
            FROM usuarios u
            JOIN usuario_notificacion_preferencias unp ON u.id = unp.id_usuario
            JOIN tipos_notificacion tn ON unp.id_tipo_notificacion = tn.id
            WHERE tn.clave = :notification_key AND u.activo = 1 AND u.email IS NOT NULL AND u.email != ''
        ";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['notification_key' => $notificationKey]);
            return $stmt->fetchAll(PDO::FETCH_COLUMN);
        } catch (\Exception $e) {
            $this->logger->error("Error al obtener usuarios suscritos para '{$notificationKey}': " . $e->getMessage());
            return []; // Devolver un array vacío en caso de error.
        }
    }
}