<?php $this->layout('layout/base', ['pageTitle' => $pageTitle, 'flashMessages' => $flashMessages]) ?>

<?php $this->start('page_content') ?>

<div class="container-fluid py-4">
    <div class="card shadow-lg p-4">
        <div class="card-header bg-primary text-white">
            <h1 class="card-title mb-0"><?= $pageTitle ?></h1>
        </div>
        <div class="card-body">
            <p class="mb-4"><?= $t('upload_csv_instructions', ['%entity_type%' => $t($entityType)]) ?? 'Sube tu archivo CSV para importar datos de ' . $t($entityType) . '. Asegúrate de usar la plantilla correcta.' ?></p>
            
            <form action="/admin/import/<?= htmlspecialchars($entityType) ?>/process" method="POST" enctype="multipart/form-data">
                <div class="mb-3">
                    <label for="csv_file" class="form-label"><?= $t('select_csv_file') ?? 'Seleccionar archivo CSV' ?></label>
                    <input type="file" class="form-control" id="csv_file" name="csv_file" accept=".csv" required>
                    <small class="form-text text-muted"><?= $t('csv_file_format_hint') ?? 'Solo archivos .csv. El delimitador debe ser punto y coma (;).' ?></small>
                </div>
                <button type="submit" class="btn btn-success me-2"><?= $t('start_import') ?? 'Iniciar Importación' ?></button>
                <a href="/admin/import" class="btn btn-secondary"><?= $t('cancel') ?></a>
            </form>
        </div>
    </div>
</div>

<?php $this->stop() ?>
