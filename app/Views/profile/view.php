<?php
/**
 * app/Views/profile/view.php
 *
 * Vista para la página "Mi Perfil".
 * Muestra la información del usuario y permite su modificación.
 */
$this->layout('layout/base', ['pageTitle' => $t('profile_title'), 'flashMessages' => $flashMessages ?? []]); // Pasa los mensajes flash a la plantilla base
?>

<?php $this->start('page_content') ?>
<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-lg-10 col-xl-8">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h4 class="card-title mb-0"><?= $this->e($t('profile_title')) ?></h4>
                </div>
                <div class="card-body p-4">
                    <p class="card-text text-muted mb-4"><?= $this->e($t('profile_subtitle')) ?></p>
                    
                    <form method="POST" action="/profile" enctype="multipart/form-data">
                        <div class="row">
                            <!-- Columna de la Imagen/Avatar -->
                            <div class="col-md-4 text-center mb-4 mb-md-0">
                                <h5><?= $this->e($t('profile_image')) ?></h5>
                                <?php if (!empty($user['profile_image_path'])): ?>
                                    <img src="<?= $this->e($user['profile_image_path']) ?>" 
                                         alt="Profile Picture" 
                                         class="img-fluid rounded-circle mb-3" 
                                         style="width: 150px; height: 150px; object-fit: cover; border: 3px solid #dee2e6;">
                                <?php else: ?>
                                    <div class="profile-avatar-icon-wrapper mb-3 d-flex align-items-center justify-content-center bg-light rounded-circle" 
                                         style="width: 150px; height: 150px; border: 3px solid #dee2e6;">
                                        <i class="bi bi-person-circle" style="font-size: 8rem; color: #adb5bd;"></i>
                                    </div>
                                <?php endif; ?>
                                <div class="mb-3">
                                    <input type="file" class="form-control" id="avatar" name="avatar" accept="image/png, image/jpeg, image/jpg">
                                </div>
                                <small class="form-text text-muted"><?= $this->e($t('upload_photo_hint')) ?></small>
                            </div>

                            <!-- Columna de Información del Usuario (Formulario) -->
                            <div class="col-md-8">
                                <h5><?= $this->e($t('user_information')) ?></h5>
                                <hr>

                                <?php if (!$isLocalUser): ?>
                                <div class="alert alert-info" role="alert">
                                    <i class="bi bi-info-circle-fill me-2"></i><?= $this->e($t('remote_user_data_note')) ?>
                                </div>
                                <?php endif; ?>

                                <div class="mb-3">
                                    <label for="username" class="form-label"><?= $this->e($t('username')) ?></label>
                                    <input type="text" class="form-control" id="username" value="<?= $this->e($user['nombre_usuario'] ?? '') ?>" readonly>
                                </div>

                                <div class="mb-3">
                                    <label for="email" class="form-label"><?= $this->e($t('email_address')) ?></label>
                                    <input type="email" class="form-control" id="email" name="email" value="<?= $this->e($user['email'] ?? '') ?>" <?= !$isLocalUser ? 'readonly' : '' ?>>
                                </div>

                                <div class="row">
                                    <div class="col-md-3 mb-3">
                                        <label for="titulo" class="form-label"><?= $this->e($t('title_salutation')) ?></label>
                                        <input type="text" class="form-control" id="titulo" name="titulo" value="<?= $this->e($user['titulo'] ?? '') ?>" placeholder="<?= $t('example') ?> Sr." <?= !$isLocalUser ? 'readonly' : '' ?>>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="nombre" class="form-label"><?= $this->e($t('first_name')) ?></label>
                                        <input type="text" class="form-control" id="nombre" name="nombre" value="<?= $this->e($user['nombre'] ?? '') ?>" <?= !$isLocalUser ? 'readonly' : '' ?>>
                                    </div>
                                    <div class="col-md-5 mb-3">
                                        <label for="apellidos" class="form-label"><?= $this->e($t('last_name')) ?></label>
                                        <input type="text" class="form-control" id="apellidos" name="apellidos" value="<?= $this->e($user['apellidos'] ?? '') ?>" <?= !$isLocalUser ? 'readonly' : '' ?>>
                                    </div>
                                </div>

                                <!-- ¡NUEVO! Campo de Idioma Preferido (solo para usuarios locales) -->
                                <?php if ($isLocalUser): ?>
                                <div class="mb-3">
                                    <label for="preferred_language" class="form-label"><?= $this->e($t('preferred_language')) ?></label>
                                    <select class="form-select" id="preferred_language" name="preferred_language">
                                        <?php foreach ($activeLanguages as $lang): ?>
                                            <option value="<?= $this->e($lang['codigo_iso']) ?>" <?= ($user['preferred_language_code'] ?? '') === $lang['codigo_iso'] ? 'selected' : '' ?>>
                                                <?= $this->e($lang['nombre']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <?php endif; ?>
                                
                            </div>
                        </div>

                        <!-- Sección de Notificaciones -->
                        <div class="mt-5">
                            <h5><?= $this->e($t('notification_preferences')) ?></h5>
                            <p class="text-muted"><?= $this->e($t('notification_preferences_intro')) ?></p>
                            <hr>

                            <?php if (empty($notificationTypes)): ?>
                                <p class="text-muted"><?= $t('no_notification_types_available') ?? 'No hay tipos de notificación disponibles.' ?></p>
                            <?php else: ?>
                                <?php foreach ($notificationTypes as $type): ?>
                                <div class="form-check form-switch custom-switch-lg mb-2">
                                    <input type="checkbox" 
                                           class="form-check-input" 
                                           role="switch"
                                           id="notification-<?= $this->e($type['id']) ?>" 
                                           name="notifications[<?= $this->e($type['id']) ?>]"
                                           <?= !empty($type['habilitado']) ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="notification-<?= $this->e($type['id']) ?>">
                                        <?= $this->e($type['nombre_visible']) ?>
                                    </label>
                                </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>

                        <!-- Sección de Autenticación de Múltiples Factores (MFA) -->
                        <div class="mt-5">
                            <h5><?= $t('mfa_title') ?></h5>
                            <p class="text-muted"><?= $t('mfa_intro') ?></p>
                            <hr>
                            <?php if ($user['mfa_enabled']): ?>
                                <div class="alert alert-success" role="alert">
                                    <i class="bi bi-shield-check-fill me-2"></i><?= $t('mfa_is_enabled') ?>
                                </div>
                                <a href="/mfa/disable" class="btn btn-warning"><?= $t('mfa_disable_button') ?></a>
                            <?php else: ?>
                                <div class="alert alert-warning" role="alert">
                                    <i class="bi bi-shield-exclamation me-2"></i><?= $t('mfa_is_disabled') ?>
                                </div>
                                <a href="/mfa/setup" class="btn btn-success"><?= $t('mfa_enable_button') ?></a>
                            <?php endif; ?>
                        </div>

                        <!-- Sección de Dispositivos de Confianza -->
                        <?php if ($user['mfa_enabled']): ?>
                        <div class="mt-5">
                            <h5><?= $t('mfa_trusted_devices_title') ?></h5>
                            <p class="text-muted"><?= $t('mfa_trusted_devices_intro') ?></p>
                            <hr>
                            <?php if (empty($trustedDevices)): ?>
                                <div class="alert alert-info" role="alert">
                                    <i class="bi bi-info-circle me-2"></i><?= $t('mfa_no_trusted_devices') ?>
                                </div>
                            <?php else: ?>
                                <ul class="list-group">
                                    <?php foreach ($trustedDevices as $device): ?>
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            <div>
                                                <i class="bi bi-pc-display-horizontal me-2"></i>
                                                <strong><?= $t('mfa_device_last_ip') ?>:</strong> <?= $this->e($device['ip_address'] ?? 'N/A') ?>
                                                <br>
                                                <small class="text-muted">
                                                    <?= $t('mfa_device_user_agent') ?>: <?= $this->e(substr($device['user_agent'] ?? 'Desconocido', 0, 70)) ?>...
                                                    <br>
                                                    <?= $t('mfa_device_creation_date') ?>: <?= date('d/m/Y H:i', strtotime($device['fecha_creacion'])) ?> | 
                                                    <?= $t('mfa_device_expiration_date') ?>: <?= date('d/m/Y H:i', strtotime($device['fecha_expiracion'])) ?>
                                                </small>
                                            </div>
                                            <form method="POST" action="/profile/revoke-device/<?= $this->e($device['token_hash']) ?>" class="ms-3">
                                                <button type="submit" class="btn btn-sm btn-outline-danger"><?= $t('mfa_revoke_device_button') ?></button>
                                            </form>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>

                        <div class="mt-5 text-end border-top pt-3">
                            <a href="/dashboard" class="btn btn-secondary"><?= $this->e($t('cancel')) ?></a>
                            <button type="submit" class="btn btn-primary"><?= $this->e($t('save_changes')) ?></button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<?php $this->stop() ?>

<?php $this->start('scripts') ?>
    <?php // No se necesita JavaScript específico para esta vista por ahora. El estilo del switch ya está en style.css ?>
<?php $this->stop() ?>