<?php $this->layout('layout/base', ['pageTitle' => $pageTitle, 'flashMessages' => $flashMessages]) ?>

<?php $this->start('page_content') ?>

<div class="container-fluid py-4">
    <div class="card shadow-lg p-4">
        <div class="card-header bg-primary text-white">
            <h1 class="card-title mb-0"><?= $this->e($pageTitle) ?></h1>
        </div>
        <div class="card-body">
            <!-- ¡IMPORTANTE! Añadir enctype para la subida de ficheros -->
            <form action="/admin/masters/language/<?= $item ? 'update/' . $item['id'] : 'create' ?>" method="POST" enctype="multipart/form-data">
                
                <div class="mb-3">
                    <label for="nombre" class="form-label"><?= $t('name') ?></label>
                    <input type="text" class="form-control" id="nombre" name="nombre" value="<?= htmlspecialchars($item['nombre'] ?? '') ?>" required>
                </div>

                <div class="mb-3">
                    <label for="codigo_iso" class="form-label"><?= $t('language_iso_code') ?? 'Código ISO' ?></label>
                    <input type="text" class="form-control" id="codigo_iso" name="codigo_iso" value="<?= htmlspecialchars($item['codigo_iso'] ?? '') ?>" required maxlength="5" placeholder="ej: pt">
                </div>

                <div class="mb-3">
                    <label for="nombre_fichero" class="form-label"><?= $t('language_file_name') ?? 'Nombre Fichero' ?></label>
                    <input type="text" class="form-control" id="nombre_fichero" name="nombre_fichero" value="<?= htmlspecialchars($item['nombre_fichero'] ?? '') ?>" placeholder="ej: pt.php" required>
                </div>

                <!-- Campo para subir el fichero, solo en modo creación -->
                <?php if ($isCreateMode): ?>
                <div class="mb-3">
                    <label for="language_file" class="form-label"><?= $t('language_upload_file') ?? 'Subir Fichero de Idioma (.php)' ?></label>
                    <input class="form-control" type="file" id="language_file" name="language_file" accept=".php" required>
                    <div class="form-text"><?= $t('language_upload_hint') ?? 'El nombre de este fichero debe coincidir con el "Nombre Fichero" de arriba.' ?></div>
                </div>
                <?php endif; ?>

                <div class="mb-3 form-check">
                    <input type="checkbox" class="form-check-input" id="activo" name="activo" value="1" <?= (isset($item['activo']) && $item['activo']) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="activo"><?= $t('active') ?></label>
                </div>

                <button type="submit" class="btn btn-success me-2"><?= $t('save') ?></button>
                <a href="/admin/masters/language" class="btn btn-secondary"><?= $t('cancel') ?></a>
            </form>
        </div>
    </div>
</div>

<?php $this->stop() ?>