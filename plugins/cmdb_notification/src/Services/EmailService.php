<?php

namespace CmdbNotification\Services;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as MailerException;
use Psr\Log\LoggerInterface;
use League\Plates\Engine as PlatesEngine;

class EmailService
{
    private PHPMailer $mailer;
    private LoggerInterface $logger;
    private $translator;
    private PlatesEngine $view;

    public function __construct(LoggerInterface $logger, callable $translator, PlatesEngine $view, PHPMailer $mailer)
    {
        $this->logger = $logger;
        $this->translator = $translator;
        $this->view = $view;
        $this->mailer = $mailer;
        $this->logger->info("EmailService construido correctamente.");
    }

    /**
     * Envía el mismo correo a una lista de destinatarios.
     * @param array $recipients Array de direcciones de correo.
     * @param string $subject Asunto del correo.
     * @param string $templateName Nombre de la plantilla.
     * @param array $templateData Datos para la plantilla.
     * @return bool True si todos los correos se enviaron con éxito.
     */
    public function sendBulkEmails(array $recipients, string $subject, string $templateName, array $templateData = []): bool
    {
        $t = $this->translator;
        $this->logger->info("Inicio de envío masivo a: " . implode(', ', $recipients));

        // Comprobación crucial: si el mailer no tiene host, no se puede enviar.
        if (empty($this->mailer->Host)) {
            $this->logger->error("El envío de correo falló porque la configuración SMTP no está cargada (Host está vacío).");
            return false;
        }
        
        try {
            // La configuración del mailer (host, port, auth) ya se hace en bootstrap.php
            $this->mailer->clearAddresses();
            $this->mailer->clearAttachments();

            $this->mailer->Subject = $subject;
            $this->mailer->isHTML(true);
            $this->mailer->Body = $this->view->render('emails/' . $templateName, $templateData);
            $this->mailer->AltBody = strip_tags($this->mailer->Body);

            foreach ($recipients as $recipient) {
                $this->mailer->addAddress($recipient);
            }

            if (!$this->mailer->send()) {
                throw new MailerException($this->mailer->ErrorInfo);
            }

            $this->logger->info($t('email_notification_sent_success', ['%emails%' => implode(', ', $recipients)]));
            return true;

        } catch (MailerException $e) {
            $this->logger->error("Error en envío masivo: {$e->getMessage()}");
            $this->logger->error("Mailer Error: " . $this->mailer->ErrorInfo);
            return false;
        }
    }
}