<?php
$this->layout('layout/base', ['pageTitle' => $t('mfa_verify_login_title'), 'flashMessages' => $flashMessages ?? []]);
?>

<?php $this->start('page_content') ?>
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-4">
            <div class="card shadow-sm">
                <div class="card-header text-center">
                    <h4 class="card-title mb-0"><?= $t('mfa_verify_login_title') ?></h4>
                </div>
                <div class="card-body p-4">
                    <p class="text-muted text-center"><?= $t('mfa_verify_login_intro') ?></p>
                    
                    <form action="/mfa/verify-login" method="POST">
                        <div class="mb-3">
                            <label for="mfa_code" class="form-label"><?= $t('mfa_code_label') ?></label>
                            <input type="text" class="form-control form-control-lg text-center" id="mfa_code" name="mfa_code" required autocomplete="off" pattern="[0-9]{6}" inputmode="numeric" placeholder="123456" autofocus>
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg"><?= $t('mfa_verify_button') ?></button>
                        </div>
                    </form>
                    <div class="text-center mt-3">
                        <a href="/logout"><?= $t('cancel_and_logout') ?></a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php $this->stop() ?>