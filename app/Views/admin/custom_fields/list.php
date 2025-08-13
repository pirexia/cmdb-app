<?php $this->layout('layout/base', ['pageTitle' => $pageTitle, 'flashMessages' => $flashMessages]) ?>

<?php $this->start('page_content') ?>

<div class="container-fluid py-4">
    <div class="card shadow-lg p-4">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <h1 class="card-title mb-0"><?= $pageTitle ?></h1>
            <a href="/admin/custom-fields/create" class="btn btn-light btn-sm">
                <i class="bi bi-plus-circle me-1"></i> <?= $t('new_custom_field') ?>
            </a>
        </div>
        <div class="card-body">
            <?php if (empty($definitions)): ?>
                <div class="alert alert-info text-center" role="alert">
                    <?= $t('no_custom_fields_defined_to_display') ?? 'No hay definiciones de campos personalizados para mostrar.' ?>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table id="customFieldsTable" class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th><?= $t('id') ?></th>
                                <th class="filterable-select"><?= $t('asset_type') ?></th>
                                <th class="filterable-text"><?= $t('field_name') ?></th>
                                <th class="filterable-select"><?= $t('data_type') ?></th>
                                <th class="filterable-select"><?= $t('required') ?></th>
                                <th class="filterable-text"><?= $t('list_options') ?></th>
                                <th class="filterable-select"><?= $t('unit') ?></th>
                                <th><?= $t('actions') ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($definitions as $def): ?>
                                <tr>
                                    <td><?= htmlspecialchars($def['id']) ?></td>
                                    <td><?= htmlspecialchars($def['tipo_activo_nombre'] ?? $t('na')) ?></td>
                                    <td><?= htmlspecialchars($def['nombre_campo']) ?></td>
                                    <td><?= htmlspecialchars($def['tipo_dato']) ?></td>
                                    <td><?= $def['es_requerido'] ? '<i class="bi bi-check-circle-fill text-success"></i> ' . $t('yes') : '<i class="bi bi-x-circle-fill text-danger"></i> ' . $t('no') ?></td>
                                    <td><?= htmlspecialchars($def['opciones_lista'] ?? $t('na')) ?></td>
                                    <td><?= htmlspecialchars($def['unidad'] ?? $t('na')) ?></td>
                                    <td>
                                        <a href="/admin/custom-fields/edit/<?= $def['id'] ?>" class="btn btn-sm btn-warning me-1" title="<?= $t('edit') ?>">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <form action="/admin/custom-fields/delete/<?= $def['id'] ?>" method="POST" style="display:inline;" onsubmit="return confirm('<?= $t('confirm_delete_custom_field_def') ?? '¿Estás seguro de que quieres eliminar esta definición de campo? Esto no elimina los valores ya existentes en los activos, pero el campo dejará de aparecer en el formulario.' ?>');">
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
<?php /* ELIMINADO EL SCRIPT INLINE, AHORA EN PARTIALS/DATATABLES_CUSTOM_FIELDS.PHP */ ?>

<?php $this->stop() ?>
