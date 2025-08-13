<?php
// app/Services/NotificationService.php

namespace App\Services;

use App\Models\Asset;
use App\Models\Contract;
use Psr\Log\LoggerInterface;
use App\Services\MailService;
use DateTime;
use DateInterval;

class NotificationService
{
    private Asset $assetModel;
    private Contract $contractModel;
    private MailService $mailService;
    private LoggerInterface $logger;
    private array $config;

    public function __construct(Asset $assetModel, Contract $contractModel, MailService $mailService, LoggerInterface $logger, array $config)
    {
        $this->assetModel = $assetModel;
        $this->contractModel = $contractModel;
        $this->mailService = $mailService;
        $this->logger = $logger;
        $this->config = $config;

	// Añadimos una verificación de seguridad extra por si acaso, aunque no debería ser necesaria
        if (empty($this->config) || !is_array($this->config)) {
             $this->logger->critical("NotificationService: La configuración no se cargó correctamente. Usando valores predeterminados de fallback.");
             // Fallback defensivo si la configuración es nula o no es un array
             $this->config = [
                 'notifications' => [
                     'days_advance' => 30,
                     'recipients' => ['default@example.com']
                 ]
             ];
        }
    }

    /**
     * Consulta activos y contratos próximos a caducar y envía notificaciones.
     * @param int $daysAdvance Días de antelación para la notificación.
     * @param string $recipientEmail Correo electrónico al que se enviarán las notificaciones.
     */
    public function sendExpirationNotifications(): void
    {
	// Usamos array_key_exists para mayor seguridad y evitar warnings de offset en null
        $daysAdvance = $this->config['notifications']['days_advance'] ?? 30;
        $recipientEmails = $this->config['notifications']['recipients'] ?? [];

        if (empty($recipientEmails)) {
            $this->logger->warning("No hay destinatarios de notificación configurados en .env. Skipping notifications.");
            return;
        }


        $this->logger->info("Iniciando envío de notificaciones de caducidad para los próximos {$daysAdvance} días.");

        $notificationThreshold = (new DateTime())->add(new DateInterval("P{$daysAdvance}D"))->format('Y-m-d');

        // --- Notificaciones de Activos ---
        $assets = $this->assetModel->getAll(); // Obtener todos los activos o un subconjunto relevante
        $notifiedAssets = [];

        if ($assets) {
            foreach ($assets as $asset) {
                $needsNotification = false;
                $details = [];

                if (!empty($asset['fecha_fin_garantia']) && new DateTime($asset['fecha_fin_garantia']) <= new DateTime($notificationThreshold) && new DateTime($asset['fecha_fin_garantia']) > new DateTime()) {
                    $needsNotification = true;
                    $details[] = "Garantía (Fin: {$asset['fecha_fin_garantia']})";
                }
                if (!empty($asset['fecha_fin_mantenimiento']) && new DateTime($asset['fecha_fin_mantenimiento']) <= new DateTime($notificationThreshold) && new DateTime($asset['fecha_fin_mantenimiento']) > new DateTime()) {
                    $needsNotification = true;
                    $details[] = "Mantenimiento (Fin: {$asset['fecha_fin_mantenimiento']})";
                }
                if (!empty($asset['fecha_fin_soporte_mainstream']) && new DateTime($asset['fecha_fin_soporte_mainstream']) <= new DateTime($notificationThreshold) && new DateTime($asset['fecha_fin_soporte_mainstream']) > new DateTime()) {
                    $needsNotification = true;
                    $details[] = "Soporte Mainstream (Fin: {$asset['fecha_fin_soporte_mainstream']})";
                }
                if (!empty($asset['fecha_fin_soporte_extended']) && new DateTime($asset['fecha_fin_soporte_extended']) <= new DateTime($notificationThreshold) && new DateTime($asset['fecha_fin_soporte_extended']) > new DateTime()) {
                    $needsNotification = true;
                    $details[] = "Soporte Extendido (Fin: {$asset['fecha_fin_soporte_extended']})";
                }

                if ($needsNotification) {
                    $notifiedAssets[] = [
                        'name' => $asset['nombre'],
                        'serial' => $asset['numero_serie'],
                        'type' => $asset['tipo_activo_nombre'],
                        'details' => implode(', ', $details),
                        'id' => $asset['id']
                    ];
                }
            }
        }

        // --- Notificaciones de Contratos ---
        $contracts = $this->contractModel->getAll();
        $notifiedContracts = [];

        if ($contracts) {
            foreach ($contracts as $contract) {
                if (!empty($contract['fecha_fin']) && new DateTime($contract['fecha_fin']) <= new DateTime($notificationThreshold) && new DateTime($contract['fecha_fin']) > new DateTime()) {
                    $notifiedContracts[] = [
                        'number' => $contract['numero_contrato'],
                        'type' => $contract['tipo_contrato_nombre'],
                        'provider' => $contract['proveedor_nombre'],
                        'endDate' => $contract['fecha_fin'],
                        'id' => $contract['id']
                    ];
                }
            }
        }

        // --- Enviar Correo si hay algo que notificar ---
        if (!empty($notifiedAssets) || !empty($notifiedContracts)) {
            $subject = "Aviso de Caducidad CMDB: Elementos próximos a expirar en {$daysAdvance} días";
            $templateData = [
                'assets' => $notifiedAssets,
                'contracts' => $notifiedContracts,
                'daysAdvance' => $daysAdvance,
            ];

	    // Pasa el array de destinatarios a sendEmail
            if ($this->mailService->sendEmail($recipientEmails, $subject, 'expiration_notice', $templateData)) {
		$this->logger->info("Correo de notificación de caducidad enviado con éxito a " . implode(', ', $recipientEmails) . ".");
            } else {
		$this->logger->error("Fallo al enviar correo de notificación de caducidad a " . implode(', ', $recipientEmails) . "."); 
            }
        } else {
            $this->logger->info("No se encontraron elementos próximos a caducar para notificar.");
        }
    }
}
