<?php $this->layout('layout/base', ['pageTitle' => $pageTitle, 'flashMessages' => $flashMessages]) ?>

<?php $this->start('page_content') ?>

<div class="container-fluid py-4">
    <div class="card shadow-lg p-4">
        <div class="card-header bg-primary text-white">
            <h1 class="card-title mb-0"><?= $pageTitle ?></h1>
        </div>
        <div class="card-body">
            <form action="/admin/masters/model/<?= $model ? 'update/' . $model['id'] : 'create' ?>" method="POST" enctype="multipart/form-data">
                <div class="mb-3">
                    <label for="id_fabricante" class="form-label"><?= $t('manufacturer') ?></label>
                    <select class="form-select" id="id_fabricante" name="id_fabricante" required>
                        <option value=""><?= $t('select_a_manufacturer') ?></option>
                        <?php foreach ($manufacturers as $manufacturer): ?>
                            <option value="<?= htmlspecialchars($manufacturer['id']) ?>"
                                <?= (isset($model['id_fabricante']) && $model['id_fabricante'] == $manufacturer['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($manufacturer['nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="nombre" class="form-label"><?= $t('model_name') ?></label>
                    <input type="text" class="form-control" id="nombre" name="nombre" value="<?= htmlspecialchars($model['nombre'] ?? '') ?>" required>
                </div>
                <div class="mb-3">
                    <label for="descripcion" class="form-label"><?= $t('description') ?></label>
                    <textarea class="form-control" id="descripcion" name="descripcion" rows="3"><?= htmlspecialchars($model['descripcion'] ?? '') ?></textarea>
                </div>
                <div class="mb-3">
                    <label for="imagen_master" class="form-label"><?= $t('master_image') ?? 'Imagen Maestra (PNG, JPG, JPEG)' ?></label>
                    <input type="file" class="form-control" id="imagen_master" name="imagen_master" accept=".png, .jpg, .jpeg">
                    <?php if (isset($model['imagen_master_ruta']) && !empty($model['imagen_master_ruta'])): ?>
                        <div class="mt-2">
                            <img src="<?= htmlspecialchars($model['imagen_master_ruta']) ?>" alt="<?= $t('current_image') ?>" style="max-width: 100px; max-height: 100px;">
                            <small class="form-text text-muted"><?= $t('upload_new_image') ?></small>
                            <input type="hidden" name="current_image_path" value="<?= htmlspecialchars($model['imagen_master_ruta']) ?>">
                        </div>
                    <?php endif; ?>
                </div>

                <button type="submit" class="btn btn-success me-2"><?= $t('save') ?></button>
                <a href="/admin/masters/model" class="btn btn-secondary"><?= $t('cancel') ?></a>
            </form>
        </div>
    </div>
</div>
<?php $this->stop() ?>
