<?php $this->layout('layout/base', ['pageTitle' => $pageTitle, 'flashMessages' => $flashMessages]) ?>

<?php $this->start('page_content') ?>

<div class="container-fluid py-4">
    <div class="card shadow-lg p-4">
        <div class="card-header bg-primary text-white">
            <h1 class="card-title mb-0"><?= $pageTitle ?></h1>
        </div>
        <div class="card-body">
            <form action="/admin/masters/<?= $masterName ?>/<?= $item ? 'update/' . $item['id'] : 'create' ?>" method="POST">
                <div class="mb-3">
                    <label for="nombre" class="form-label"><?= $t('name') ?></label>
                    <input type="text" class="form-control" id="nombre" name="nombre" value="<?= htmlspecialchars($item['nombre'] ?? '') ?>" required>
                </div>
                <div class="mb-3">
                    <label for="descripcion" class="form-label"><?= $t('description') ?></label>
                    <textarea class="form-control" id="descripcion" name="descripcion" rows="3"><?= htmlspecialchars($item['descripcion'] ?? '') ?></textarea>
                </div>
                <button type="submit" class="btn btn-success me-2"><?= $t('save') ?></button>
                <a href="/admin/masters/<?= $masterName ?>" class="btn btn-secondary"><?= $t('cancel') ?></a>
            </form>
        </div>
    </div>
</div>
<?php $this->stop() ?>
