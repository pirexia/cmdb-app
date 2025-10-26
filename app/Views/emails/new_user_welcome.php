<?php
/**
 * app/Views/emails/new_user_welcome.php
 * Plantilla de correo para dar la bienvenida a un nuevo usuario.
 */
?>
<!DOCTYPE html>
<html lang="<?= $this->e($currentLanguage) ?>">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { width: 90%; max-width: 600px; margin: 20px auto; padding: 20px; border: 1px solid #ddd; border-radius: 8px; }
        a.button { background-color: #007bff; color: #ffffff; padding: 10px 15px; text-decoration: none; border-radius: 5px; }
        .footer { font-size: 0.9em; color: #777; text-align: center; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <h2><?= $t('welcome_to_app', ['app_name' => $this->e($appName)]) ?></h2>
        <p><?= $t('hello') ?>, <?= $this->e($username) ?>!</p>
        <p><?= $t('welcome_email_body') ?></p>
        <p>
            <a href="<?= $this->e($appUrl) ?>" class="button">
                <?= $t('access_dashboard_button') ?>
            </a>
        </p>
        <p><?= $t('sincerely') ?>,</p>
        <p><?= $t('app_team', ['app_name' => $this->e($appName)]) ?></p>
    </div>
    <div class="footer"><?= $t('automated_email_do_not_reply') ?></div>
</body>
</html>