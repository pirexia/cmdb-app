<?php $this->layout('layout/base', ['pageTitle' => $t('my_profile')]) ?>

<?php $this->start('page_content') ?>
<h1><?= $t('my_profile') ?></h1>

<form action="/profile/update" method="post" enctype="multipart/form-data">
    <div class="row">
        <!-- Columna de la imagen -->
        <div class="col-md-4 text-center">
            <?php
            // Usamos tu nombre de columna 'profile_image_path'
            $avatarPath = $user['profile_image_path'] ?? '/assets/img/default-avatar.png'; // Usa un avatar por defecto
            ?>
            <img src="<?= $this->asset($avatarPath) ?>" alt="Avatar" class="img-thumbnail rounded-circle mb-3" style="width: 150px; height: 150px; object-fit: cover;">
            <div class="mb-3">
                <label for="avatar" class="form-label"><?= $t('upload_new_image') ?></label>
                <input class="form-control" type="file" id="avatar" name="avatar" accept="image/png, image/jpeg">
            </div>
        </div>

        <!-- Columna de datos del usuario -->
        <div class="col-md-8">
            <fieldset <?= !$isLocalUser ? 'disabled' : '' ?>>
                <div class="row">
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label for="titulo" class="form-label"><?= $t('title') ?></label>
                            <select class="form-select" id="titulo" name="titulo">
                                <option value=""><?= $t('select_an_option') ?></option>
                                <option value="Sr." <?= ($user['titulo'] ?? '') === 'Sr.' ? 'selected' : '' ?>>Sr.</option>
                                <option value="Sra." <?= ($user['titulo'] ?? '') === 'Sra.' ? 'selected' : '' ?>>Sra.</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-9">
                        <div class="mb-3">
                            <label for="nombre_usuario" class="form-label"><?= $t('username') ?></label>
                            <input type="text" class="form-control" id="nombre_usuario" value="<?= htmlspecialchars($user['nombre_usuario']) ?>" disabled>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="nombre" class="form-label"><?= $t('first_name') ?></label>
                            <input type="text" class="form-control" id="nombre" name="nombre" value="<?= htmlspecialchars($user['nombre'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="apellidos" class="form-label"><?= $t('last_name') ?></label>
                            <input type="text" class="form-control" id="apellidos" name="apellidos" value="<?= htmlspecialchars($user['apellidos'] ?? '') ?>">
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="email" class="form-label"><?= $t('email_address') ?></label>
                    <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($user['email'] ?? '') ?>">
                </div>

                <?php if (!$isLocalUser): ?>
                    <div class="alert alert-info" role="alert">
                        <i class="fas fa-info-circle"></i> <?= $t('user_data_from_directory') ?>
                    </div>
                <?php endif; ?>
            </fieldset>

            <hr>

            <!-- Sección de Notificaciones -->
            <h4><?= $t('notification_preferences') ?></h4>
            <p><?= $t('notification_preferences_description') ?></p>

            <?php if (empty($notificationTypes)): ?>
                <div class="alert alert-secondary"><?= $t('no_notification_types_defined') ?></div>
            <?php else: ?>
                <?php foreach ($notificationTypes as $type): ?>
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" role="switch" 
                               id="notification_<?= $type['id'] ?>" 
                               name="notifications[<?= $type['id'] ?>]"
                               value="1"
                               <?= $type['habilitado'] ? 'checked' : '' ?>>
                        <label class="form-check-label" for="notification_<?= $type['id'] ?>">
                            <?= htmlspecialchars($type['nombre_visible']) ?>
                        </label>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>

            <div class="mt-4">
                <button type="submit" class="btn btn-primary"><?= $t('save') ?></button>
                <a href="/dashboard" class="btn btn-secondary"><?= $t('cancel') ?></a>
            </div>
        </div>
    </div>
</form>
<?php $this->stop() ?>