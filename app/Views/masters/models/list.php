<?php $this->layout('layout/base', ['pageTitle' => $pageTitle, 'flashMessages' => $flashMessages]) ?>

<?php $this->start('page_content') ?>

<div class="container-fluid py-4">
    <div class="card shadow-lg p-4">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <h1 class="card-title mb-0"><?= $pageTitle ?></h1>
            <a href="/admin/masters/model/create" class="btn btn-light btn-sm">
                <i class="bi bi-plus-circle me-1"></i> <?= $t('new_model') ?? 'Nuevo Modelo' ?>
            </a>
        </div>
        <div class="card-body">
            <?php if (empty($models)): ?>
                <div class="alert alert-info text-center" role="alert">
                    <?= $t('no_models_to_display') ?? 'No hay modelos para mostrar.' ?>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table id="modelTable" class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th><?= $t('id') ?></th>
                                <th class="filterable-select"><?= $t('manufacturer') ?></th>
                                <th class="filterable-text"><?= $t('model_name') ?? 'Nombre del Modelo' ?></th>
                                <th class="filterable-text"><?= $t('description') ?></th>
                                <th><?= $t('image') ?? 'Imagen' ?></th>
                                <th><?= $t('actions') ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($models as $model): ?>
                                <tr>
                                    <td><?= htmlspecialchars($model['id']) ?></td>
                                    <td><?= htmlspecialchars($model['fabricante_nombre']) ?></td>
                                    <td><?= htmlspecialchars($model['nombre']) ?></td>
                                    <td><?= htmlspecialchars($model['descripcion'] ?? $t('na')) ?></td>
                                    <td>
                                        <?php if (!empty($model['imagen_master_ruta'])): ?>
                                            <img src="<?= htmlspecialchars($model['imagen_master_ruta']) ?>" alt="<?= $t('model_image') ?? 'Imagen Modelo' ?>" style="max-width: 50px; max-height: 50px;">
                                        <?php else: ?>
                                            <?= $t('na') ?>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="/admin/masters/model/edit/<?= $model['id'] ?>" class="btn btn-sm btn-warning me-1" title="<?= $t('edit') ?>">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <form action="/admin/masters/model/delete/<?= $model['id'] ?>" method="POST" style="display:inline;" onsubmit="return confirm('<?= $t('confirm_delete_model') ?? '¿Estás seguro de que quieres eliminar este modelo? Esta acción eliminará también la imagen asociada y puede afectar a activos.' ?>');">
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
<?php /* ELIMINADO EL SCRIPT INLINE, AHORA EN PARTIALS/DATATABLES_MODELS.PHP */ ?>

<?php $this->stop() ?>
