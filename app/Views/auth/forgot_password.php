<?php $this->layout('layout/base', ['pageTitle' => $pageTitle, 'flashMessages' => $flashMessages]) ?>

<?php $this->start('page_content') ?>
<div class="row justify-content-center mt-5">
    <div class="col-md-5">
        <div class="card shadow-lg p-4">
            <div class="card-body">
                <h3 class="card-title text-center mb-4"><?= $t('password_reset_title') ?></h3>
                <p class="text-center text-muted"><?= $t('enter_email_for_reset_link') ?? 'Introduce tu dirección de correo electrónico y te enviaremos un enlace para restablecer tu contraseña.' ?></p>
                <form action="/forgot-password" method="POST">
                    <div class="mb-3">
                        <label for="email" class="form-label"><?= $t('email_address') ?></label>
                        <input type="email" class="form-control" id="email" name="email" required autocomplete="email">
                    </div>
                    <div class="d-grid gap-2 mb-3">
                        <button type="submit" class="btn btn-primary btn-lg"><?= $t('send_reset_link') ?? 'Enviar Enlace de Restablecimiento' ?></button>
                    </div>
                    <div class="text-center">
                        <a href="/login" class="text-decoration-none"><?= $t('back_to_login') ?? 'Volver al inicio de sesión' ?></a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?php $this->stop() ?>
