<?php
$this->layout('layout/base', ['pageTitle' => $t('mfa_setup_title'), 'flashMessages' => $flashMessages ?? []]);
?>

<?php $this->start('page_content') ?>
<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h4 class="card-title mb-0"><?= $t('mfa_setup_title') ?></h4>
                </div>
                <div class="card-body p-4">
                    <p><?= $t('mfa_setup_instructions') ?></p>
                    
                    <div class="text-center my-4">
                        <?php echo $qrCodeInline; ?>
                    </div>

                    <p><?= $t('mfa_setup_manual_key') ?></p>
                    <div class="alert alert-secondary text-center">
                        <code><?= $secret ?></code>
                    </div>

                    <hr>

                    <p><strong><?= $t('mfa_setup_verify_step') ?></strong></p>
                    <form action="/mfa/verify-setup" method="POST">
                        <div class="mb-3">
                            <label for="mfa_code" class="form-label"><?= $t('mfa_code_label') ?></label>
                            <input type="text" class="form-control" id="mfa_code" name="mfa_code" required autocomplete="off" pattern="[0-9]{6}" inputmode="numeric" placeholder="123456">
                        </div>
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary"><?= $t('mfa_verify_and_enable') ?></button>
                            <a href="/profile" class="btn btn-secondary"><?= $t('cancel') ?></a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<?php $this->stop() ?>