<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { width: 90%; max-width: 600px; margin: 20px auto; padding: 20px; border: 1px solid #ddd; border-radius: 8px; }
        a { color: #0056b3; }
        .footer { font-size: 0.9em; color: #777; text-align: center; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Restablecimiento de Contraseña</h2>
        <p>Hola <?= htmlspecialchars($username) ?>,</p>
        <p>Has solicitado restablecer tu contraseña para la aplicación CMDB App.</p>
        <p>Haz clic en el siguiente enlace para continuar. Este enlace expirará en 1 hora:</p>
        <p><a href="<?= htmlspecialchars($reset_link) ?>"><?= htmlspecialchars($reset_link) ?></a></p>
        <p>Si no solicitaste esto, puedes ignorar este correo.</p>
        <p>Atentamente,<br>El equipo de CMDB App</p>
    </div>
    <div class="footer">Este es un correo automático, por favor no lo respondas.</div>
</body>
</html>