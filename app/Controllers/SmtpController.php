<?php
/**
 * app/Controllers/SmtpController.php
 *
 * Este controlador gestiona la lógica de la página de configuración SMTP.
 * Permite a los administradores visualizar y actualizar los parámetros de envío de correo.
 */

namespace App\Controllers;

// --- Importaciones de Clases ---
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use League\Plates\Engine as PlatesEngine;
use App\Services\SessionService;
use App\Services\SmtpService; // <-- Nuevo servicio para guardar la configuración
use Psr\Log\LoggerInterface;
use Exception;
use PDOException;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as MailerException;

/**
 * Clase SmtpController
 * Controla el flujo de la página de configuración SMTP.
 * Solo accesible para usuarios con privilegios de administrador.
 */
class SmtpController
{
    private PlatesEngine $view;
    private SessionService $sessionService;
    private LoggerInterface $logger;
    private array $config;
    private $translator;
    private SmtpService $smtpService; // <-- Nueva propiedad para el servicio

    /**
     * Constructor de la clase. Inyecta todas las dependencias necesarias.
     */
    public function __construct(
        PlatesEngine $view,
        SessionService $sessionService,
        LoggerInterface $logger,
        array $config,
        callable $translator,
        SmtpService $smtpService
    ) {
        $this->view = $view;
        $this->sessionService = $sessionService;
        $this->logger = $logger;
        $this->config = $config;
        $this->translator = $translator;
        $this->smtpService = $smtpService;
    }

    /**
     * Muestra el formulario para configurar los parámetros SMTP.
     * @param Request $request La solicitud HTTP.
     * @param Response $response La respuesta HTTP.
     * @return Response La respuesta HTTP con la vista renderizada.
     */
    public function showForm(Request $request, Response $response): Response
    {
        $t = $this->translator;
        
        // Obtiene la configuración SMTP actual desde el servicio o el archivo de configuración.
        $smtpConfig = $this->config['smtp'];

        $flashMessages = $this->sessionService->getFlashMessages();

        $html = $this->view->render('admin/smtp/form', [
            'pageTitle' => $t('smtp_settings') ?? 'Configuración SMTP',
            'smtpConfig' => $smtpConfig,
            'flashMessages' => $flashMessages
        ]);

        $response->getBody()->write($html);
        return $response;
    }

    /**
     * Procesa la solicitud para guardar los nuevos parámetros SMTP.
     * @param Request $request La solicitud HTTP.
     * @param Response $response La respuesta HTTP.
     * @return Response La respuesta HTTP con una redirección.
     */
    public function processForm(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();
        $t = $this->translator;

        $newSmtpConfig = [
            'host' => trim($data['host'] ?? ''),
            'port' => (int)($data['port'] ?? 0),
            'auth_required' => isset($data['auth_required']) && $data['auth_required'] === '1',
            'username' => trim($data['username'] ?? ''),
            'password' => $data['password'] ?? '',
            'encryption' => $data['encryption'] ?? '',
            'from_email' => trim($data['from_email'] ?? ''),
            'from_name' => trim($data['from_name'] ?? '')
        ];

        try {
            // Llama al servicio para guardar la nueva configuración
            $success = $this->smtpService->saveSmtpConfig($newSmtpConfig);

            if ($success) {
                $this->sessionService->addFlashMessage('success', $t('smtp_config_saved_successfully') ?? 'Configuración SMTP guardada con éxito.');
            } else {
                $this->sessionService->addFlashMessage('danger', $t('smtp_config_save_error') ?? 'Error al guardar la configuración SMTP.');
            }
        } catch (Exception $e) {
            $this->logger->error("Error al procesar formulario SMTP: " . $e->getMessage());
            $this->sessionService->addFlashMessage('danger', $t('smtp_config_save_error') ?? 'Error al guardar la configuración SMTP.');
        }

        return $response->withHeader('Location', '/admin/smtp')->withStatus(302);
    }
}
