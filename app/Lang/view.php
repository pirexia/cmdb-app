<?php $this->layout('layout', ['title' => $t('profile_title')]) ?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><?= $this->e($t('profile_title')) ?></h3>
                </div>
                <div class="card-body">
                    <p class="card-text"><?= $this->e($t('profile_subtitle')) ?></p>

                    <?php if ($flash = $this->fetch('flash_success')): ?>
                        <div class="alert alert-success flash-message"><?= $this->e($flash) ?></div>
                    <?php endif; ?>
                    <?php if ($flash = $this->fetch('flash_error')): ?>
                        <div class="alert alert-danger flash-message"><?= $this->e($flash) ?></div>
                    <?php endif; ?>

                    <form method="POST" action="/profile" enctype="multipart/form-data">
                        <div class="row">
                            <!-- Columna de la Imagen -->
                            <div class="col-md-4 text-center">
                                <h5><?= $this->e($t('profile_image')) ?></h5>
                                <img src="<?= $this->e($user['profile_image_path'] ?? '/assets/img/avatar-placeholder.png') ?>" 
                                     alt="Profile Picture" 
                                     class="img-fluid rounded-circle mb-3" 
                                     style="width: 150px; height: 150px; object-fit: cover;">
                                <div class="custom-file">
                                    <input type="file" class="custom-file-input" id="avatar" name="avatar" accept="image/png, image/jpeg, image/jpg">
                                    <label class="custom-file-label" for="avatar"><?= $this->e($t('upload_new_photo')) ?></label>
                                </div>
                                <small class="form-text text-muted"><?= $this->e($t('upload_photo_hint')) ?></small>
                            </div>

                            <!-- Columna de Información del Usuario -->
                            <div class="col-md-8">
                                <h5><?= $this->e($t('user_information')) ?></h5>
                                <hr>

                                <?php if (!$isLocalUser): ?>
                                <div class="alert alert-info" role="alert">
                                    <i class="fas fa-info-circle"></i> <?= $this->e($t('remote_user_data_note')) ?>
                                </div>
                                <?php endif; ?>

                                <div class="form-group">
                                    <label for="username"><?= $this->e($t('username')) ?></label>
                                    <input type="text" class="form-control" id="username" value="<?= $this->e($user['nombre_usuario']) ?>" readonly>
                                </div>

                                <div class="form-group">
                                    <label for="email"><?= $this->e($t('email_address')) ?></label>
                                    <input type="email" class="form-control" id="email" name="email" value="<?= $this->e($user['email']) ?>" <?= !$isLocalUser ? 'readonly' : '' ?>>
                                </div>

                                <div class="form-row">
                                    <div class="form-group col-md-2">
                                        <label for="titulo"><?= $this->e($t('title_salutation')) ?></label>
                                        <input type="text" class="form-control" id="titulo" name="titulo" value="<?= $this->e($user['titulo']) ?>" <?= !$isLocalUser ? 'readonly' : '' ?>>
                                    </div>
                                    <div class="form-group col-md-5">
                                        <label for="nombre"><?= $this->e($t('first_name')) ?></label>
                                        <input type="text" class="form-control" id="nombre" name="nombre" value="<?= $this->e($user['nombre']) ?>" <?= !$isLocalUser ? 'readonly' : '' ?>>
                                    </div>
                                    <div class="form-group col-md-5">
                                        <label for="apellidos"><?= $this->e($t('last_name')) ?></label>
                                        <input type="text" class="form-control" id="apellidos" name="apellidos" value="<?= $this->e($user['apellidos']) ?>" <?= !$isLocalUser ? 'readonly' : '' ?>>
                                    </div>
                                </div>

                                <!-- Sección de Notificaciones -->
                                <h5 class="mt-4"><?= $this->e($t('notification_preferences')) ?></h5>
                                <p><?= $this->e($t('notification_preferences_intro')) ?></p>
                                <hr>

                                <?php if (empty($notificationTypes)): ?>
                                    <p class="text-muted"><?= $t('no_notification_types_available') ?></p>
                                <?php else: ?>
                                    <?php foreach ($notificationTypes as $type): ?>
                                    <div class="form-group">
                                        <div class="custom-control custom-switch custom-switch-lg">
                                            <input type="checkbox" 
                                                   class="custom-control-input" 
                                                   id="notification-<?= $this->e($type['id']) ?>" 
                                                   name="notifications[<?= $this->e($type['id']) ?>]"
                                                   <?= $type['habilitado'] ? 'checked' : '' ?>>
                                            <label class="custom-control-label" for="notification-<?= $this->e($type['id']) ?>">
                                                <?= $this->e($type['nombre_visible']) ?>
                                            </label>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>

                                <div class="mt-4">
                                    <button type="submit" class="btn btn-primary"><?= $this->e($t('save_changes')) ?></button>
                                    <a href="/dashboard" class="btn btn-secondary"><?= $this->e($t('cancel')) ?></a>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php $this->push('scripts') ?>
<script>
$(document).ready(function() {
    // Script para mostrar el nombre del archivo en el input de subida de Bootstrap
    $('.custom-file-input').on('change', function() {
        let fileName = $(this).val().split('\\').pop();
        $(this).next('.custom-file-label').addClass("selected").html(fileName);
    });
});
</script>
<?php $this->end() ?>