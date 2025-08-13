<?php $this->layout('layout/base', ['pageTitle' => $pageTitle, 'flashMessages' => $flashMessages]) ?>

<?php $this->start('page_content') ?>

<div class="container-fluid py-4">
    <div class="card shadow-lg p-4">
        <div class="card-header bg-primary text-white">
            <h1 class="card-title mb-0"><?= $pageTitle ?></h1>
        </div>
        <div class="card-body">
            <form action="/admin/users/<?= $user ? 'update/' . $user['id'] : 'create' ?>" method="POST">
                
                <div class="mb-3">
                    <label for="id_fuente_usuario" class="form-label"><?= $t('user_source') ?></label>
                    <select class="form-select" id="id_fuente_usuario" name="id_fuente_usuario" required
                        <?= (isset($user) && (isset($user['id_fuente_usuario']) && $user['id_fuente_usuario'] == 1)) ? 'disabled' : '' ?>>
                        <option value=""><?= $t('select_a_user_source') ?></option>
                        <?php foreach ($sources as $source): ?>
                            <option value="<?= htmlspecialchars($source['id']) ?>"
                                <?= (isset($user['id_fuente_usuario']) && $user['id_fuente_usuario'] == $source['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($source['nombre_friendly']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($user) && (isset($user['id_fuente_usuario']) && $user['id_fuente_usuario'] == 1)): ?>
                        <small class="form-text text-muted"><?= $t('local_source_for_user_fixed') ?></small>
                        <input type="hidden" name="id_fuente_usuario" value="1">
                    <?php endif; ?>
                </div>

                <div class="mb-3">
                    <label for="username" class="form-label"><?= $t('username') ?></label>
                    <input type="text" class="form-control" id="username" name="username" value="<?= htmlspecialchars($user['nombre_usuario'] ?? '') ?>" required>
                    <small id="username_hint" class="form-text text-muted d-none"><?= $t('username_not_managed_by_app') ?></small>
                </div>

                <div class="mb-3">
                    <label for="email" class="form-label"><?= $t('email_address') ?></label>
                    <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($user['email'] ?? '') ?>" required>
                    <small id="email_hint" class="form-text text-muted d-none"><?= $t('email_not_managed_by_app') ?></small>
                </div>
                
                <div id="password_fields"> <div class="mb-3">
                        <label for="password" class="form-label"><?= $t('password') ?></label>
                        <input type="password" class="form-control" id="password" name="password" autocomplete="new-password">
                        <?php if (isset($user)): // Solo mostrar el hint de "dejar vacío" en edición ?>
                            <small class="form-text text-muted"><?= $t('leave_empty_for_no_change') ?></small>
                        <?php else: // En creación, siempre mostrar requisitos de password ?>
                            <small class="form-text text-muted"><?= $t('password_requirements') ?></small>
                        <?php endif; ?>
                    </div>
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label"><?= $t('confirm_password') ?></label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" autocomplete="new-password">
                    </div>
                </div>
                <div class="mb-3">
                    <label for="id_rol" class="form-label"><?= $t('role') ?></label>
                    <select class="form-select" id="id_rol" name="id_rol" required>
                        <option value=""><?= $t('select_a_role') ?></option>
                        <?php foreach ($roles as $role): ?>
                            <option value="<?= htmlspecialchars($role['id']) ?>"
                                <?= (isset($user['id_rol']) && $user['id_rol'] == $role['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($role['nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3 form-check">
                    <input type="checkbox" class="form-check-input" id="activo" name="activo" value="1" <?= (isset($user['activo']) && $user['activo'] == 1) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="activo"><?= $t('active') ?></label>
                </div>

                <button type="submit" class="btn btn-success me-2"><?= $t('save') ?></button>
                <a href="/admin/users" class="btn btn-secondary"><?= $t('cancel') ?></a>
            </form>
        </div>
    </div>
</div>

<?php $this->stop() ?>
