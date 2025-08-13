<?php $this->layout('layout/base', ['pageTitle' => $pageTitle, 'flashMessages' => $flashMessages]) ?>

<?php $this->start('page_content') ?>

<div class="container-fluid py-4">
    <div class="card shadow-lg p-4">
        <div class="card-header bg-primary text-white">
            <h1 class="card-title mb-0"><?= $pageTitle ?></h1>
        </div>
        <div class="card-body">
            <p class="mb-4"><?= $t('bulk_import_description') ?></p>

            <h5 class="mb-3"><?= $t('download_csv_templates') ?></h5>
            <div class="list-group mb-4">
                <?php if (empty($availableTemplates)): ?>
                    <p class="alert alert-info"><?= $t('no_templates_available') ?></p>
                <?php else: ?>
                    <?php foreach ($availableTemplates as $entityType): ?>
                        <?php if ($entityType === 'assets'): ?>
                            <div class="list-group-item d-flex justify-content-between align-items-center flex-wrap">
                                <span class="me-2"><?= $t('template_for_entity', ['%entity_type%' => $t($entityType)]) ?></span>
                                <div class="d-flex align-items-center">
                                    <select id="asset_type_select" class="form-select form-select-sm me-2">
                                        <option value=""><?= $t('select_asset_type_for_template') ?? 'Selecciona Tipo de Activo' ?></option>
                                        <?php foreach ($assetTypes as $type): ?>
                                            <option value="<?= htmlspecialchars($type['id']) ?>"><?= htmlspecialchars($type['nombre']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <a href="#" id="download_assets_template_btn" class="btn btn-sm btn-primary disabled" download>
                                        <i class="bi bi-download"></i>
                                    </a>
                                </div>
                            </div>
                        <?php else: ?>
                            <a href="/admin/import/template/<?= htmlspecialchars($entityType) ?>" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                <?= $t('template_for_entity', ['%entity_type%' => $t($entityType)]) ?>
                                <i class="bi bi-download"></i>
                            </a>
                        <?php endif; ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <h5 class="mb-3"><?= $t('upload_csv_file') ?></h5>
            <p class="mb-3"><?= $t('upload_csv_file_description') ?></p>
            <div class="list-group">
                <?php if (empty($availableTemplates)): ?>
                    <p class="alert alert-warning"><?= $t('cannot_upload_no_templates') ?></p>
                <?php else: ?>
                    <?php foreach ($availableTemplates as $entityType): ?>
                        <a href="/admin/import/<?= htmlspecialchars($entityType) ?>/upload" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                            <?= $t('upload_for_entity', ['%entity_type%' => $t($entityType)]) ?>
                            <i class="bi bi-upload"></i>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php $this->stop() ?>
