<?php
/**
 * app/Views/admin/smtp/form.php
 *
 * Formulario para la configuración de los parámetros de envío de correo SMTP.
 * Permite a un administrador ver y modificar el host, puerto, credenciales y opciones de cifrado.
 */
?>
<?php $this->layout('layout/base', ['pageTitle' => $pageTitle, 'flashMessages' => $flashMessages]) ?>

<?php $this->start('page_content') ?>

<div class="container-fluid py-4">
    <div class="card shadow-lg p-4">
        <div class="card-header bg-primary text-white">
            <h1 class="card-title mb-0"><?= $pageTitle ?></h1>
        </div>
        <div class="card-body">
            <form action="/admin/smtp/update" method="POST">
                <div class="mb-3">
                    <label for="host" class="form-label"><?= $t('smtp_host') ?? 'Host SMTP' ?></label>
                    <input type="text" class="form-control" id="host" name="host" value="<?= htmlspecialchars($smtpConfig['host'] ?? '') ?>" required>
                    <small class="form-text text-muted"><?= $t('smtp_host_hint') ?? 'Ej: smtp.ejemplo.com' ?></small>
                </div>
                <div class="mb-3">
                    <label for="port" class="form-label"><?= $t('smtp_port') ?? 'Puerto' ?></label>
                    <input type="number" class="form-control" id="port" name="port" value="<?= htmlspecialchars($smtpConfig['port'] ?? 587) ?>" required>
                    <small class="form-text text-muted"><?= $t('smtp_port_hint') ?? 'Puertos comunes: 587 (TLS), 465 (SSL)' ?></small>
                </div>
                <div class="mb-3">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="auth_required" name="auth_required" value="1" <?= (isset($smtpConfig['auth_required']) && $smtpConfig['auth_required']) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="auth_required"><?= $t('smtp_authentication') ?? 'Requiere Autenticación' ?></label>
                    </div>
                </div>
                <div id="smtp-credentials" style="display: <?= (isset($smtpConfig['auth_required']) && $smtpConfig['auth_required']) ? 'block' : 'none' ?>;">
                    <div class="mb-3">
                        <label for="username" class="form-label"><?= $t('smtp_username') ?? 'Usuario SMTP' ?></label>
                        <input type="text" class="form-control" id="username" name="username" value="<?= htmlspecialchars($smtpConfig['username'] ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label"><?= $t('smtp_password') ?? 'Contraseña SMTP' ?></label>
                        <input type="password" class="form-control" id="password" name="password" value="<?= htmlspecialchars($smtpConfig['password'] ?? '') ?>">
                    </div>
                </div>
                <div class="mb-3">
                    <label for="encryption" class="form-label"><?= $t('smtp_encryption') ?? 'Cifrado' ?></label>
                    <select class="form-select" id="encryption" name="encryption">
                        <option value="" <?= (empty($smtpConfig['encryption']) ? 'selected' : '') ?>><?= $t('smtp_no_encryption') ?? 'Sin cifrado' ?></option>
                        <option value="tls" <?= ((($smtpConfig['encryption'] ?? '') === 'tls') ? 'selected' : '') ?>>TLS</option>
                        <option value="ssl" <?= ((($smtpConfig['encryption'] ?? '') === 'ssl') ? 'selected' : '') ?>>SSL</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="from_email" class="form-label"><?= $t('smtp_from_email') ?? 'Correo de Remitente' ?></label>
                    <input type="email" class="form-control" id="from_email" name="from_email" value="<?= htmlspecialchars($smtpConfig['from_email'] ?? '') ?>" required>
                </div>
                <div class="mb-3">
                    <label for="from_name" class="form-label"><?= $t('smtp_from_name') ?? 'Nombre de Remitente' ?></label>
                    <input type="text" class="form-control" id="from_name" name="from_name" value="<?= htmlspecialchars($smtpConfig['from_name'] ?? '') ?>" required>
                </div>

                <button type="submit" class="btn btn-success me-2"><?= $t('save') ?></button>
                <a href="/dashboard" class="btn btn-secondary"><?= $t('cancel') ?></a>
            </form>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        // Lógica para mostrar/ocultar los campos de usuario y contraseña si se requiere autenticación
        $('#auth_required').change(function() {
            if ($(this).is(':checked')) {
                $('#smtp-credentials').show();
                $('#username').prop('required', true);
                // La contraseña no se hace 'required' para permitir dejarla vacía si no se desea cambiar
            } else {
                $('#smtp-credentials').hide();
                $('#username').prop('required', false).val('');
                $('#password').val('');
            }
        });
    });
</script>

<?php $this->stop() ?>
