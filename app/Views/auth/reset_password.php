<?php $this->layout('layout/base', ['pageTitle' => $pageTitle, 'flashMessages' => $flashMessages]) ?>

<?php $this->start('page_content') ?>
<div class="row justify-content-center mt-5">
    <div class="col-md-5">
        <div class="card shadow-lg p-4">
            <div class="card-body">
                <h3 class="card-title text-center mb-4"><?= $t('password_reset_title') ?></h3>
                <form action="/reset-password" method="POST">
                    <input type="hidden" name="token" value="<?= htmlspecialchars($token ?? '') ?>">
                    <div class="mb-3">
                        <label for="password" class="form-label"><?= $t('new_password') ?></label>
                        <input type="password" class="form-control" id="password" name="password" required autocomplete="new-password">
                        <small class="form-text text-muted"><?= $t('password_requirements') ?></small>
                    </div>
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label"><?= $t('confirm_new_password') ?></label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required autocomplete="new-password">
                    </div>
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary btn-lg"><?= $t('reset_password_button') ?? 'Restablecer Contraseña' ?></button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?php $this->stop() ?>
