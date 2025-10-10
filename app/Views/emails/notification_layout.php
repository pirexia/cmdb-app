<?php
/**
 * cmdb_notification/src/Views/emails/notification_layout.php
 *
 * Plantilla de diseño base para los correos de notificación.
 *
 * @var callable $t La función de traducción.
 * @var string $content El contenido HTML del cuerpo del correo.
 */
?>
<!DOCTYPE html>
<html lang="<?= $t('lang_code') ?? 'es' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $subject ?? $t('app_name') ?></title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; background-color: #f4f4f4; }
        .email-container { width: 100%; max-width: 700px; margin: 20px auto; background-color: #ffffff; border: 1px solid #ddd; border-radius: 8px; overflow: hidden; }
        .email-header { background-color: #0d6efd; color: #ffffff; padding: 20px; text-align: center; }
        .email-header h2 { margin: 0; }
        .email-body { padding: 20px; }
        .email-footer { background-color: #f8f9fa; color: #6c757d; padding: 15px; text-align: center; font-size: 0.9em; border-top: 1px solid #ddd; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; font-size: 0.95em; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        h3 { color: #0056b3; border-bottom: 2px solid #dee2e6; padding-bottom: 5px; }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="email-header">
            <h2><?= $t('app_name') ?></h2>
        </div>
        <div class="email-body">
            <?= $content ?? '' ?>
        </div>
        <div class="email-footer"><?= $t('automated_email_do_not_reply') ?></div>
    </div>
</body>
</html>