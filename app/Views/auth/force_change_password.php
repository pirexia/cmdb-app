<?php
// app/Views/auth/force_change_password.php
$this->layout('layout/auth_base', [
    'pageTitle' => $t('force_password_change_title') ?? 'Cambio de Contraseña Requerido',
    'flashMessages' => $flashMessages ?? []
]);
?>

<?php $this->start('page_content') ?>
<div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
        <div class="card shadow-sm">
            <div class="card-header text-center">
                <h3 class="card-title mb-0"><?= $this->e($t('force_password_change_title') ?? 'Cambio de Contraseña Requerido') ?></h3>
            </div>
            <div class="card-body p-4">
                <p class="text-muted"><?= $this->e($t('password_expired_message') ?? 'Tu contraseña ha caducado. Por favor, establece una nueva para continuar.') ?></p>
                
                <form method="POST" action="/force-change-password">
                    <div class="mb-3">
                        <label for="new_password" class="form-label"><?= $this->e($t('new_password')) ?></label>
                        <input type="password" class="form-control" id="new_password" name="new_password" required autocomplete="new-password">
                        <small class="form-text text-muted"><?= $this->e($t('password_requirements')) ?></small>
                    </div>
                    <div class="mb-3">
                        <label for="confirm_new_password" class="form-label"><?= $this->e($t('confirm_new_password')) ?></label>
                        <input type="password" class="form-control" id="confirm_new_password" name="confirm_new_password" required autocomplete="new-password">
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary"><?= $this->e($t('change_password')) ?></button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?php $this->stop() ?>
