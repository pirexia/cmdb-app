<?php
/**
 * app/Views/auth/login_view.php
 *
 * Vista para el formulario de inicio de sesión.
 * Extiende la plantilla 'layout/auth_base'.
 */
$this->layout('layout/auth_base', [
    'pageTitle' => $t('login') . ' ' . $t('in_cmdb_app'),
    'flashMessages' => $flashMessages ?? []
]);
?>

<?php $this->start('page_content') ?>
<div class="row justify-content-center">
    <div class="col-md-6 col-lg-4">
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <h3 class="card-title text-center mb-4"><?= $this->e($t('login')) ?></h3>
                <form method="POST" action="/login">
                    <div class="mb-3">
                        <label for="username" class="form-label"><?= $this->e($t('username')) ?></label>
                        <input type="text" class="form-control" id="username" name="username" required autofocus>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label"><?= $this->e($t('password')) ?></label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>

                    <?php if (!empty($sources) && count($sources) > 1): ?>
                        <div class="mb-3">
                            <label for="id_fuente_usuario" class="form-label"><?= $this->e($t('user_source')) ?></label>
                            <select class="form-select" id="id_fuente_usuario" name="id_fuente_usuario" required>
                                <?php foreach ($sources as $source): ?>
                                    <option value="<?= $this->e($source['id']) ?>"><?= $this->e($source['nombre_friendly']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php elseif (!empty($sources)): ?>
                        <input type="hidden" name="id_fuente_usuario" value="<?= $this->e($sources[0]['id']) ?>">
                    <?php endif; ?>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary"><?= $this->e($t('login')) ?></button>
                    </div>
                    <div class="text-center mt-3">
                        <a href="/forgot-password"><?= $this->e($t('forgot_password')) ?></a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?php $this->stop() ?>