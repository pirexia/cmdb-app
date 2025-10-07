<?php
// app/Services/MailService.php

namespace App\Services;

// Importaciones necesarias para PHPMailer y logging.
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception as MailerException;
use Psr\Log\LoggerInterface;
use League\Plates\Engine as PlatesEngine;

// Importar el nuevo servicio SMTP.
use App\Services\SmtpService;

/**
 * Clase MailService
 * Este servicio encapsula la lógica para el envío de correos electrónicos
 * utilizando la librería PHPMailer y la configuración almacenada en la base de datos.
 */
class MailService
{
    private PHPMailer $mailer;           // Instancia de PHPMailer para el envío.
    private LoggerInterface $logger;       // Instancia del logger para registrar eventos.
    private PlatesEngine $view;           // Motor de plantillas PlatesPHP para renderizar correos.
    private SmtpService $smtpService;      // Servicio para obtener la configuración SMTP desde la DB.

    /**
     * Constructor del servicio. Recibe todas las dependencias necesarias.
     * @param PHPMailer $mailer
     * @param LoggerInterface $logger
     * @param PlatesEngine $view
     * @param SmtpService $smtpService El servicio que obtiene la configuración SMTP de la base de datos.
     */
    public function __construct(
        PHPMailer $mailer,
        LoggerInterface $logger,
        PlatesEngine $view,
        SmtpService $smtpService
    ) {
        $this->mailer = $mailer;
        $this->logger = $logger;
        $this->view = $view;
        $this->smtpService = $smtpService;
    }

    /**
     * Configura la instancia de PHPMailer con los parámetros obtenidos
     * del SmtpService. Este método se llama internamente antes de cada envío
     * para asegurar que la configuración esté actualizada.
     */
    private function setupMailer(): bool
    {
        // Obtener la configuración SMTP de la base de datos a través del servicio.
        $smtpConfig = $this->smtpService->getSmtpConfig();

        // Si no hay host o email de remitente, la configuración no es válida para enviar correos.
        // Esto previene el error "Invalid address: (From):"
        if (empty($smtpConfig['host']) || empty($smtpConfig['from_email'])) {
            $this->logger->error('Configuración SMTP no encontrada o inválida en la base de datos. Asegúrate de que la configuración SMTP esté guardada en la aplicación. No se puede enviar el correo.');
            return false;
        }

        $this->mailer->isSMTP();
        $this->mailer->Host       = $smtpConfig['host'];
        $this->mailer->Port       = (int)$smtpConfig['port'];
        $this->mailer->CharSet    = 'UTF-8';
        $this->mailer->SMTPAuth   = (bool)$smtpConfig['auth_required'];
        
        if ($this->mailer->SMTPAuth) {
            $this->mailer->Username = $smtpConfig['username'];
            $this->mailer->Password = $smtpConfig['password'];
        }

        if (!empty($smtpConfig['encryption'])) {
            $this->mailer->SMTPSecure = $smtpConfig['encryption']; // Acepta 'tls' o 'ssl'
        } else {
            $this->mailer->SMTPSecure = false;
        }

        // ¡ADVERTENCIA! Esta opción reduce la seguridad y solo debe usarse si confías
        // plenamente en el servidor SMTP y no puedes instalar su certificado CA.
        // Es una causa común de fallos en entornos locales o con certificados autofirmados.
        $this->mailer->SMTPOptions = [
            'ssl' => [
                'verify_peer'       => false,
                'verify_peer_name'  => false,
                'allow_self_signed' => true
            ],
            'tls' => ['verify_peer' => false] // Añadido para forzar la no verificación también en TLS
        ];

        $this->mailer->setFrom($smtpConfig['from_email'], $smtpConfig['from_name']);
        return true;
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
            // Configura el mailer con los datos más recientes de la BBDD. Si falla, no continúa.
            if (!$this->setupMailer()) {
                return false;
            }
            
            $this->mailer->clearAddresses();
            $this->mailer->clearAttachments();

            // Añadir destinatario(s).
            if (is_array($to)) {
                foreach ($to as $recipient) {
                    $this->mailer->addAddress($recipient);
                }
            } else {
                $this->mailer->addAddress($to);
            }

            $this->mailer->Subject = $subject;
            $this->mailer->isHTML(true);

            // Renderizar la plantilla de correo usando PlatesPHP.
            $this->mailer->Body = $this->view->render('emails/' . $templateName, $templateData);
            $this->mailer->AltBody = strip_tags($this->mailer->Body); // Versión de texto plano para clientes de correo que no soportan HTML.

            $this->mailer->send();
            $this->logger->info("Correo enviado a " . (is_array($to) ? implode(', ', $to) : $to) . " con asunto: '{$subject}'");
            return true;
        } catch (MailerException $e) {
            $this->logger->error("Error al enviar correo: {$e->getMessage()} - Destinatario: " . (is_array($to) ? implode(', ', $to) : $to) . " - Asunto: '{$subject}'");
            return false;
        }
    }
}
