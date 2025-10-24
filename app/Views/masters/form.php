<?php $this->layout('layout/base', ['pageTitle' => $pageTitle, 'flashMessages' => $flashMessages]) ?>

<div class="container-fluid py-4">
    <div class="card shadow-lg p-4">
        <div class="card-header bg-primary text-white">
            <h1 class="card-title mb-0"><?= $pageTitle ?></h1>
        </div>
        <div class="card-body">
            <form action="/admin/masters/<?= $masterName ?>/<?= $item ? 'update/' . $item['id'] : 'create' ?>" method="POST">
                <div class="mb-3">
                    <label for="nombre" class="form-label"><?= $t('name') ?> <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="nombre" name="nombre" value="<?= htmlspecialchars($item['nombre'] ?? '') ?>" required>
                </div>
                <div class="mb-3">
                    <label for="descripcion" class="form-label"><?= $t('description') ?></label>
                    <textarea class="form-control" id="descripcion" name="descripcion" rows="3"><?= htmlspecialchars($item['descripcion'] ?? '') ?></textarea>
                </div>
                <div class="text-end">
                    <a href="/admin/masters/<?= $masterName ?>" class="btn btn-secondary"><?= $t('cancel') ?></a>
                    <button type="submit" class="btn btn-primary"><?= $t('save') ?></button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php $this->start('page_content') ?>
<?php $this->stop() ?>
