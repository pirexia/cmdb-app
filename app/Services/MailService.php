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
    private $translator;                   // Función de traducción.

    /**
     * Constructor del servicio. Recibe todas las dependencias necesarias.
     * @param PHPMailer $mailer
     * @param LoggerInterface $logger
     * @param PlatesEngine $view
     * @param SmtpService $smtpService El servicio que obtiene la configuración SMTP de la base de datos.
     * @param callable $translator La función de traducción.
     */
    public function __construct(
        PHPMailer $mailer,
        LoggerInterface $logger,
        PlatesEngine $view,
        SmtpService $smtpService,
        callable $translator // <-- AÑADIR ARGUMENTO
    ) {
        $this->mailer = $mailer;
        $this->logger = $logger;
        $this->view = $view;
        $this->smtpService = $smtpService;
        $this->translator = $translator; // <-- ASIGNAR PROPIEDAD
        
        // Cargar el idioma español para los mensajes de error de PHPMailer
        $this->mailer->setLanguage('es');
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

        // Habilitar la salida de depuración detallada de SMTP.
        // Se desactiva para producción. Cambiar a SMTP::DEBUG_SERVER para depurar.
        // Los errores seguirán siendo capturados y registrados en el log.
        $this->mailer->SMTPDebug = SMTP::DEBUG_OFF;

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
            // Corrección: Para deshabilitar el cifrado en PHPMailer,
            // SMTPSecure debe ser una cadena vacía, no el booleano false.
            $this->mailer->SMTPSecure = '';
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
     * @return array ['success' => bool, 'error' => ?string]
     */
    public function sendEmail(string|array $to, string $subject, string $templateName, array $templateData = []): array
    {
        $this->logger->debug("Preparando para enviar correo con asunto '{$subject}' a través de la plantilla '{$templateName}'.");

        $this->logger->info("Inicio de envío de correo a: " . (is_array($to) ? implode(', ', $to) : $to));
        try {
            if (!$this->setupMailer()) {
                return ['success' => false, 'error' => 'Configuración SMTP no encontrada o inválida.'];
            }
    
            // Añadir destinatario(s).
            if (is_array($to)) {
                foreach ($to as $recipient) {
                    $this->mailer->addAddress($recipient);
                }
            } else {
                if (!empty($to)) {
                    $this->mailer->addAddress($to);
                }
            }

            $this->mailer->Subject = $subject;
            $this->mailer->isHTML(true);

            // Renderizar la plantilla de correo usando PlatesPHP.
            $this->mailer->Body = $this->view->render('emails/' . $templateName, $templateData);
            $this->mailer->AltBody = strip_tags($this->mailer->Body); // Versión de texto plano para clientes de correo que no soportan HTML.

            $this->mailer->send();
            $this->logger->info("Correo enviado a " . (is_array($to) ? implode(', ', $to) : $to) . " con asunto: '{$subject}'");
            return ['success' => true];
        } catch (MailerException $e) {
            // El error detallado de PHPMailer se registra en el log con nivel de advertencia (WARNING).
            // Se registra el error completo para depuración interna.
            $this->logger->error("Error al enviar correo a " . (is_array($to) ? implode(', ', $to) : $to) . ". Mailer Error: " . $this->mailer->ErrorInfo);
 
            // Devolver un mensaje de error genérico y traducido a la interfaz para no exponer detalles del servidor.
            $t = $this->translator; // Usar el traductor inyectado
            $genericErrorMessage = $t('operation_failed');
            return ['success' => false, 'error' => $genericErrorMessage];
        }
    }
}
