<?php $this->layout('layout/base', ['pageTitle' => $pageTitle, 'flashMessages' => $flashMessages]) ?>

<?php $this->start('page_content') ?>

<div class="container-fluid py-4">
    <div class="card shadow-lg p-4">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <h1 class="card-title mb-0"><?= $pageTitle ?></h1>
            <a href="/assets/create" class="btn btn-light btn-sm">
                <i class="bi bi-plus-circle me-1"></i> <?= $t('new_asset') ?>
            </a>
        </div>
        <div class="card-body">
            <?php if (empty($assets)): ?>
                <div class="alert alert-info text-center" role="alert">
                    <?= $t('no_assets_to_display') ?>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table id="assetsTable" class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th><?= $t('id') ?></th>
                                <th class="filterable-text"><?= $t('name') ?></th>
                                <th class="filterable-select"><?= $t('asset_type') ?></th>
                                <th class="filterable-select"><?= $t('manufacturer') ?></th>
                                <th class="filterable-select"><?= $t('model') ?></th>
                                <th class="filterable-select"><?= $t('status') ?></th>
                                <th class="filterable-text"><?= $t('serial_number') ?></th>
                                <th class="filterable-select"><?= $t('location') ?></th>
                                <th><?= $t('actions') ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($assets as $asset): ?>
                                <tr>
                                    <td><?= htmlspecialchars($asset['id']) ?></td>
                                    <td><?= htmlspecialchars($asset['nombre']) ?></td>
                                    <td><?= htmlspecialchars($asset['tipo_activo_nombre'] ?? $t('na')) ?></td>
                                    <td><?= htmlspecialchars($asset['fabricante_nombre'] ?? $t('na')) ?></td>
                                    <td><?= htmlspecialchars($asset['modelo_nombre'] ?? $t('na')) ?></td>
                                    <td><?= htmlspecialchars($asset['estado_nombre'] ?? $t('na')) ?></td>
                                    <td><?= htmlspecialchars($asset['numero_serie'] ?? $t('na')) ?></td>
                                    <td><?= htmlspecialchars($asset['ubicacion_nombre'] ?? $t('na')) ?></td>
                                    <td>
                                        <a href="/assets/edit/<?= $asset['id'] ?>" class="btn btn-sm btn-warning me-1" title="<?= $t('edit') ?>">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <form action="/assets/delete/<?= $asset['id'] ?>" method="POST" style="display:inline;" onsubmit="return confirm('<?= $t('confirm_delete_asset') ?>');">
                                            <button type="submit" class="btn btn-sm btn-danger" title="<?= $t('delete') ?>">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php /* Nota: El CSS de DataTables se carga ahora desde layout/base.php. */ ?>
<?php /* ELIMINADA LA LÍNEA: <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/2.0.8/css/dataTables.bootstrap5.min.css"/> */ ?>
<?php /* ELIMINADO EL SCRIPT INLINE, AHORA EN PARTIALS/DATATABLES_ASSETS.PHP */ ?>

<?php $this->stop() ?>
