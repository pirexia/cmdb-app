<?php
/**
 * app/Views/mfa/trust_device.php
 *
 * Vista para preguntar al usuario si desea confiar en el dispositivo actual para futuros inicios de sesión MFA.
 */
$this->layout('layout/auth_base', [
    'pageTitle' => $t('mfa_trust_device_title') ?? 'Dispositivo de Confianza',
    'flashMessages' => $flashMessages ?? []
]);
?>

<?php $this->start('page_content') ?>
<div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
        <div class="card shadow-sm text-center">
            <div class="card-body p-4">
                <h3 class="card-title mb-3"><?= $this->e($t('mfa_trust_device_title') ?? 'Dispositivo de Confianza') ?></h3>
                <p class="text-muted mb-4"><?= $this->e($t('mfa_trust_device_question') ?? '¿Deseas recordar este dispositivo para no tener que introducir el código de verificación durante los próximos 7 días?') ?></p>
                
                <form method="POST" action="/mfa/trust-device/process" class="d-inline">
                    <input type="hidden" name="trust" value="yes">
                    <button type="submit" class="btn btn-primary btn-lg"><?= $this->e($t('yes')) ?>, <?= $this->e($t('mfa_trust_device_button') ?? 'Confiar') ?></button>
                </form>

                <form method="POST" action="/mfa/trust-device/process" class="d-inline">
                    <input type="hidden" name="trust" value="no">
                    <button type="submit" class="btn btn-secondary btn-lg"><?= $this->e($t('no')) ?>, <?= $this->e($t('mfa_do_not_trust_button') ?? 'No confiar') ?></button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php $this->stop() ?>