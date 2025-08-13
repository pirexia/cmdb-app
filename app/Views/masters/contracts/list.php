<?php $this->layout('layout/base', ['pageTitle' => $pageTitle, 'flashMessages' => $flashMessages]) ?>

<?php $this->start('page_content') ?>

<div class="container-fluid py-4">
    <div class="card shadow-lg p-4">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <h1 class="card-title mb-0"><?= $pageTitle ?></h1>
            <a href="/admin/masters/contract/create" class="btn btn-light btn-sm">
                <i class="bi bi-plus-circle me-1"></i> <?= $t('new_contract') ?? 'Nuevo Contrato' ?>
            </a>
        </div>
        <div class="card-body">
            <?php if (empty($contracts)): ?>
                <div class="alert alert-info text-center" role="alert">
                    <?= $t('no_contracts_to_display') ?? 'No hay contratos para mostrar.' ?>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table id="contractsTable" class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th><?= $t('id') ?></th>
                                <th class="filterable-text"><?= $t('contract_number') ?></th>
                                <th class="filterable-select"><?= $t('contract_type') ?></th>
                                <th class="filterable-select"><?= $t('provider') ?></th>
                                <th><?= $t('start_date') ?></th>
                                <th><?= $t('end_date') ?></th>
                                <th><?= $t('annual_cost') ?></th>
                                <th><?= $t('actions') ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($contracts as $contract): ?>
                                <tr>
                                    <td><?= htmlspecialchars($contract['id']) ?></td>
                                    <td><?= htmlspecialchars($contract['numero_contrato']) ?></td>
                                    <td><?= htmlspecialchars($contract['tipo_contrato_nombre'] ?? $t('na')) ?></td>
                                    <td><?= htmlspecialchars($contract['proveedor_nombre'] ?? $t('na')) ?></td>
                                    <td><?= htmlspecialchars($contract['fecha_inicio']) ?></td>
                                    <td><?= htmlspecialchars($contract['fecha_fin']) ?></td>
                                    <td><?= htmlspecialchars($contract['costo_anual'] ?? $t('na')) ?></td>
                                    <td>
                                        <a href="/admin/masters/contract/edit/<?= $contract['id'] ?>" class="btn btn-sm btn-warning me-1" title="<?= $t('edit') ?>">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <form action="/admin/masters/contract/delete/<?= $contract['id'] ?>" method="POST" style="display:inline;" onsubmit="return confirm('<?= $t('confirm_delete_contract') ?? '¿Estás seguro de que quieres eliminar este contrato? Esta acción eliminará sus asociaciones con activos.' ?>');">
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
<?php /* ELIMINADO EL SCRIPT INLINE, AHORA EN PARTIALS/DATATABLES_CONTRACTS.PHP */ ?>

<?php $this->stop() ?>
