<?php $this->layout('layout/base', ['pageTitle' => $pageTitle, 'flashMessages' => $flashMessages]); ?>

<?php $this->start('page_content') ?>

<div class="container-fluid py-4">
    <div class="card shadow-lg p-4">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <h1 class="card-title mb-0"><?= $this->e($pageTitle) ?></h1>
            <?php
                $singularMasterKey = 'master_singular_' . str_replace('-', '_', $masterName);
            ?>
            <a href="/admin/masters/<?= $this->e($masterName) ?>/create" class="btn btn-light btn-sm">
                <i class="bi bi-plus-circle me-1"></i> <?= $this->e($t('create_new_master', ['master_name' => $t($singularMasterKey) ?? ucwords(str_replace('-', ' ', $masterName))])) ?>
            </a>
        </div>
        <div class="card-body">
            <?php if (empty($items)): ?>
                <div class="alert alert-info text-center" role="alert">
                    <?= $this->e($t('datatable_no_data_master') ?? 'No hay elementos para mostrar en este maestro.') ?>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table id="masterTable" class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th><?= $this->e($t('id')) ?></th>
                                <th class="filterable-text"><?= $this->e($t('name')) ?></th>
                                <th class="filterable-text"><?= $this->e($t('description')) ?></th>
                                <?php if ($masterName === 'location'): ?>
                                    <th class="filterable-text"><?= $this->e($t('direccion')) ?></th>
                                    <th class="filterable-text"><?= $this->e($t('poblacion')) ?></th>
                                    <th class="filterable-text"><?= $this->e($t('codigo_postal')) ?></th>
                                    <th class="filterable-text"><?= $this->e($t('pais')) ?></th>
                                <?php endif; ?>
                                <th><?= $this->e($t('actions')) ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items as $item): ?>
                                <tr>
                                    <td><?= $this->e($item['id']) ?></td>
                                    <td><?= $this->e($item['nombre']) ?></td>
                                    <td><?= $this->e(substr($item['descripcion'] ?? '', 0, 100)) . (strlen($item['descripcion'] ?? '') > 100 ? '...' : '') ?></td>
                                    <?php if ($masterName === 'location'): ?>
                                        <td><?= $this->e($item['direccion'] ?? '') ?></td>
                                        <td><?= $this->e($item['poblacion'] ?? '') ?></td>
                                        <td><?= $this->e($item['codigo_postal'] ?? '') ?></td>
                                        <td><?= $this->e($item['pais'] ?? '') ?></td>
                                    <?php endif; ?>
                                    <td>
                                        <?php if ($masterName === 'location'): ?>
                                            <a href="/admin/masters/location/detail/<?= $this->e($item['id']) ?>" class="btn btn-sm btn-info me-1" title="<?= $this->e($t('view_details')) ?>">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                        <?php endif; ?>
                                        <a href="/admin/masters/<?= $this->e($masterName) ?>/edit/<?= $this->e($item['id']) ?>" class="btn btn-sm btn-warning me-1" title="<?= $this->e($t('edit')) ?>">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <form action="/admin/masters/<?= $this->e($masterName) ?>/delete/<?= $this->e($item['id']) ?>" method="POST" style="display:inline;" onsubmit="return confirm('<?= $this->e($t('confirm_delete_master_item')) ?>');">
                                            <button type="submit" class="btn btn-sm btn-danger" title="<?= $this->e($t('delete')) ?>">
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
