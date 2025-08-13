<?php $this->layout('layout/base', ['pageTitle' => $pageTitle, 'flashMessages' => $flashMessages]) ?>

<?php $this->start('page_content') ?>

<div class="container-fluid py-4">
    <div class="card shadow-lg p-4">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <h1 class="card-title mb-0"><?= $pageTitle ?></h1>
            <a href="/admin/masters/<?= $masterName ?>/create" class="btn btn-light btn-sm">
                <i class="bi bi-plus-circle me-1"></i> <?= $t('new') ?> <?= ucwords(str_replace('-', ' ', $masterName)) ?>
            </a>
        </div>
        <div class="card-body">
            <?php if (empty($items)): ?>
                <div class="alert alert-info text-center" role="alert">
                    <?= $t('datatable_no_data_master') ?? 'No hay elementos para mostrar en este maestro.' ?>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table id="masterTable" class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th><?= $t('id') ?></th>
                                <th class="filterable-text"><?= $t('name') ?></th>
                                <th class="filterable-text"><?= $t('description') ?></th>
                                <th><?= $t('actions') ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items as $item): ?>
                                <tr>
                                    <td><?= htmlspecialchars($item['id']) ?></td>
                                    <td><?= htmlspecialchars($item['nombre']) ?></td>
                                    <td><?= htmlspecialchars($item['descripcion'] ?? $t('na')) ?></td>
                                    <td>
                                        <a href="/admin/masters/<?= $masterName ?>/edit/<?= $item['id'] ?>" class="btn btn-sm btn-warning me-1" title="<?= $t('edit') ?>">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <form action="/admin/masters/<?= $masterName ?>/delete/<?= $item['id'] ?>" method="POST" style="display:inline;" onsubmit="return confirm('<?= $t('confirm_delete_master_item') ?? '¿Estás seguro de que quieres eliminar este elemento? Esta acción no se puede deshacer.' ?>');">
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
<?php /* ELIMINADO EL SCRIPT INLINE, AHORA EN PARTIALS/DATATABLES_MASTERS.PHP */ ?>

<?php $this->stop() ?>
