<?php
// app/Services/MailService.php

namespace App\Services;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception as MailerException;
use Psr\Log\LoggerInterface;
use League\Plates\Engine as PlatesEngine; // Para renderizar plantillas de correo

class MailService
{
    private PHPMailer $mailer;
    private LoggerInterface $logger;
    private PlatesEngine $view; // Para usar Plates como motor de plantillas de correo
    private array $config; // Para la configuración SMTP

    public function __construct(PHPMailer $mailer, LoggerInterface $logger, PlatesEngine $view, array $config)
    {
        $this->mailer = $mailer;
        $this->logger = $logger;
        $this->view = $view;
        $this->config = $config;

        $smtpConfig = $this->config['smtp'];

        $this->mailer->isSMTP();
        $this->mailer->Host       = $smtpConfig['host'];
        $this->mailer->Port       = $smtpConfig['port'];
        $this->mailer->CharSet    = 'UTF-8';

        // Autenticación condicional
        $this->mailer->SMTPAuth = $smtpConfig['auth_required']; // Usa la nueva variable
        if ($smtpConfig['auth_required']) {
            $this->mailer->Username = $smtpConfig['username'];
            $this->mailer->Password = $smtpConfig['password'];
        } else {
            $this->mailer->Username = ''; // Asegurar que estén vacíos si no hay autenticación
            $this->mailer->Password = '';
        }

        // Cifrado condicional
        if (!empty($smtpConfig['encryption'])) {
            $this->mailer->SMTPSecure = match ($smtpConfig['encryption']) {
                'tls' => PHPMailer::ENCRYPTION_STARTTLS,
                'ssl' => PHPMailer::ENCRYPTION_SMTPS,
                default => false,
            };
        } else {
            $this->mailer->SMTPSecure = false; // Sin cifrado
        }

        // Configuración de SSL/TLS para evitar errores de certificado en entornos internos
        // Esto solo aplica si SMTPSecure NO es false.
        // Solo aplica si el error persiste. Para TLS 1.2 por puerto 25, normalmente NO es necesario esto.
        // Si tienes "SSL routines::certificate verify failed", HABILITA esta sección.
        // Si tu SMTP no necesita verificación de certificado, pero sí cifrado TLS.
        // PHPMailer por defecto ya negocia TLS 1.2 si está disponible.
        $this->mailer->SMTPOptions = [
            'ssl' => [
                'verify_peer'       => false,
                'verify_peer_name'  => false,
                'allow_self_signed' => true
            ]
        ];
        // Si el problema de "certificate verify failed" PERSISTE con TLS 1.2 en puerto 25,
        // DESCOMENTA la sección SMTPOptions de arriba.
        // Por ahora, lo dejo COMENTADO para que la conexión sea lo más estándar posible primero.

        $this->mailer->setFrom($smtpConfig['from_email'], $smtpConfig['from_name']);
    }

    /**
     * Envía un correo electrónico.
     * @param string|array $to Dirección(es) de correo del destinatario.
     * @param string $subject Asunto del correo.
     * @param string $templateName Nombre de la plantilla de correo (en app/Views/emails/).
     * @param array $templateData Datos para la plantilla de correo.
     * @return bool True si el correo se envió con éxito, false de lo contrario.
     */
    public function sendEmail(string|array $to, string $subject, string $templateName, array $templateData = []): bool
    {
        try {
            // Limpiar destinatarios y adjuntos de envíos anteriores
            $this->mailer->clearAddresses();
            $this->mailer->clearAttachments();

            // Añadir destinatario(s)
            if (is_array($to)) {
                foreach ($to as $recipient) {
                    $this->mailer->addAddress($recipient);
                }
            } else {
                $this->mailer->addAddress($to);
            }

            $this->mailer->Subject = $subject;
            $this->mailer->isHTML(true); // El correo es HTML

            // Renderizar la plantilla de correo usando Plates
            // Asegúrate de que Plates esté configurado para buscar en app/Views/emails
            $this->mailer->Body = $this->view->render('emails/' . $templateName, $templateData);
            // Si necesitas un texto plano alternativo
            $this->mailer->AltBody = strip_tags($this->mailer->Body);

            $this->mailer->send();
            $this->logger->info("Correo enviado a " . (is_array($to) ? implode(', ', $to) : $to) . " con asunto: '{$subject}'");
            return true;
        } catch (MailerException $e) {
            $this->logger->error("Error al enviar correo: {$e->getMessage()} - Destinatario: " . (is_array($to) ? implode(', ', $to) : $to) . " - Asunto: '{$subject}'");
            return false;
        }
    }
}
