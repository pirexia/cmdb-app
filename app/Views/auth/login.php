<?php $this->layout('layout/base', ['pageTitle' => $pageTitle, 'flashMessages' => $flashMessages]) ?>

<?php $this->start('page_content') ?>
<div class="row justify-content-center mt-5">
    <div class="col-md-5">
        <div class="card shadow-lg p-4">
            <div class="card-body">
                <h3 class="card-title text-center mb-4"><?= $t('login') ?> <?= $t('in_cmdb_app') ?></h3>
                <form action="/login" method="POST">
                    <div class="mb-3">
                        <label for="id_fuente_usuario" class="form-label"><?= $t('user_source') ?></label>
                        <select class="form-select" id="id_fuente_usuario" name="id_fuente_usuario" required>
                            <option value=""><?= $t('select_a_user_source') ?></option>
                            <?php foreach ($sources as $source): ?>
                                <option value="<?= htmlspecialchars($source['id']) ?>">
                                    <?= htmlspecialchars($source['nombre_friendly']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="username" class="form-label"><?= $t('username') ?></label>
                        <input type="text" class="form-control" id="username" name="username" required autocomplete="username">
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label"><?= $t('password') ?></label>
                        <input type="password" class="form-control" id="password" name="password" required autocomplete="current-password">
                    </div>
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary btn-lg"><?= $t('login') ?></button>
                    </div>
                    <div class="text-center mt-3">
                        <a href="/forgot-password" class="text-decoration-none"><?= $t('forgot_password') ?></a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?php $this->stop() ?>
