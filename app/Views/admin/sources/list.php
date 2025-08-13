<?php $this->layout('layout/base', ['pageTitle' => $pageTitle, 'flashMessages' => $flashMessages]) ?>

<?php $this->start('page_content') ?>

<div class="container-fluid py-4">
    <div class="card shadow-lg p-4">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <h1 class="card-title mb-0"><?= $pageTitle ?></h1>
            <a href="/admin/sources/create" class="btn btn-light btn-sm">
                <i class="bi bi-plus-circle me-1"></i> <?= $t('new_user_source') ?? 'Nueva Fuente de Usuario' ?>
            </a>
        </div>
        <div class="card-body">
            <?php if (empty($sources)): ?>
                <div class="alert alert-info text-center" role="alert">
                    <?= $t('no_user_sources_to_display') ?? 'No hay fuentes de usuario para mostrar.' ?>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table id="sourcesTable" class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th><?= $t('id') ?></th>
                                <th class="filterable-text"><?= $t('source_name') ?? 'Nombre Amigable' ?></th>
                                <th class="filterable-select"><?= $t('source_type') ?? 'Tipo de Fuente' ?></th>
                                <th><?= $t('host') ?></th>
                                <th><?= $t('port') ?></th>
                                <th><?= $t('base_dn') ?></th>
                                <th><?= $t('active') ?></th>
                                <th><?= $t('actions') ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($sources as $source): ?>
                                <tr>
                                    <td><?= htmlspecialchars($source['id']) ?></td>
                                    <td><?= htmlspecialchars($source['nombre_friendly']) ?></td>
                                    <td><?= htmlspecialchars($source['tipo_fuente']) ?></td>
                                    <td><?= htmlspecialchars($source['host'] ?? $t('na')) ?></td>
                                    <td><?= htmlspecialchars($source['port'] ?? $t('na')) ?></td>
                                    <td><?= htmlspecialchars($source['base_dn'] ?? $t('na')) ?></td>
                                    <td><?= $source['activo'] ? '<i class="bi bi-check-circle-fill text-success"></i> ' . $t('yes') : '<i class="bi bi-x-circle-fill text-danger"></i> ' . $t('no') ?></td>
                                    <td>
                                        <a href="/admin/sources/edit/<?= $source['id'] ?>" class="btn btn-sm btn-warning me-1" title="<?= $t('edit') ?>">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <?php if ($source['tipo_fuente'] !== 'local'): // No permitir borrar la fuente local ?>
                                            <form action="/admin/sources/delete/<?= $source['id'] ?>" method="POST" style="display:inline;" onsubmit="return confirm('<?= $t('confirm_delete_user_source') ?? '¿Estás seguro de que quieres eliminar esta fuente de usuario? Los usuarios asociados a esta fuente dejarán de poder autenticarse.' ?>');">
                                                <button type="submit" class="btn btn-sm btn-danger" title="<?= $t('delete') ?>">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                        <?php if (in_array($source['tipo_fuente'], ['ldap', 'activedirectory'])): ?>
                                            <button type="button" class="btn btn-sm btn-info ms-1 test-source-btn" data-source-id="<?= $source['id'] ?>" title="<?= $t('test_connection') ?? 'Probar Conexión' ?>">
                                                <i class="bi bi-link-45deg"></i>
                                            </button>
                                        <?php endif; ?>
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

<?php /* ELIMINADO EL SCRIPT INLINE, AHORA EN PARTIALS/DATATABLES_SOURCES.PHP */ ?>

<div class="modal fade" id="testConnectionModal" tabindex="-1" aria-labelledby="testConnectionModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="testConnectionModalLabel"><?= $t('test_connection_results') ?? 'Resultados del Test de Conexión' ?></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?= $t('close') ?>"></button>
      </div>
      <div class="modal-body" id="testConnectionModalBody">
        </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= $t('close') ?></button>
      </div>
    </div>
  </div>
</div>

<?php $this->stop() ?>
