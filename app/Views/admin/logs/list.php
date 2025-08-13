<?php
/**
 * app/Views/admin/logs/list.php
 *
 * Vista para mostrar el historial de cambios de activos (logs de auditoría).
 * Utiliza DataTables para una visualización interactiva y filtrable de los logs.
 *
 * @param string $pageTitle El título de la página.
 * @param array $logs Un array de logs de activos obtenidos desde el LogController.
 * @param array $flashMessages Los mensajes flash de la sesión.
 * @param callable $t La función de traducción.
 */
?>
<?php $this->layout('layout/base', ['pageTitle' => $pageTitle, 'flashMessages' => $flashMessages]) ?>

<?php $this->start('page_content') ?>

<div class="container-fluid py-4">
    <div class="card shadow-lg p-4">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <h1 class="card-title mb-0"><?= $pageTitle ?></h1>
        </div>
        <div class="card-body">
            <?php if (empty($logs)): ?>
                <div class="alert alert-info text-center" role="alert">
                    <?= $t('no_logs_to_display') ?? 'No hay logs de auditoría para mostrar.' ?>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table id="logsTable" class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th><?= $t('log_id') ?? 'ID Log' ?></th>
                                <th><?= $t('log_date') ?? 'Fecha' ?></th>
                                <th class="filterable-select"><?= $t('action') ?? 'Acción' ?></th>
                                <th class="filterable-text"><?= $t('asset_name') ?? 'Activo' ?></th>
                                <th><?= $t('username') ?? 'Usuario' ?></th>
                                <th><?= $t('details') ?? 'Detalles' ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($logs as $log): ?>
                                <tr>
                                    <td><?= htmlspecialchars($log['id']) ?></td>
                                    <td><?= htmlspecialchars($log['fecha_log']) ?></td>
                                    <td><?= htmlspecialchars($t($log['accion'])) ?></td>
                                    <td><?= htmlspecialchars($log['activo_nombre']) ?></td>
                                    <td><?= htmlspecialchars($log['usuario_nombre']) ?></td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#logDetailsModal"
                                            data-log-id="<?= htmlspecialchars($log['id']) ?>"
                                            data-log-date="<?= htmlspecialchars($log['fecha_log']) ?>"
                                            data-log-action="<?= htmlspecialchars($t($log['accion'])) ?>"
                                            data-log-user="<?= htmlspecialchars($log['usuario_nombre']) ?>"
                                            data-log-asset="<?= htmlspecialchars($log['activo_nombre']) ?>"
                                            data-log-old-data='<?= htmlspecialchars($log['valor_anterior'], ENT_QUOTES, 'UTF-8') ?>'
                                            data-log-new-data='<?= htmlspecialchars($log['valor_nuevo'], ENT_QUOTES, 'UTF-8') ?>'
                                            title="<?= $t('view_details') ?>">
                                            <i class="bi bi-eye"></i>
                                        </button>
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

<!-- Modal para ver los detalles del log -->
<div class="modal fade" id="logDetailsModal" tabindex="-1" aria-labelledby="logDetailsModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="logDetailsModalLabel"><?= $t('log_details') ?? 'Detalles del Log' ?></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?= $t('close') ?>"></button>
      </div>
      <div class="modal-body">
        <pre id="logDetailsContent"></pre>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= $t('close') ?></button>
      </div>
    </div>
  </div>
</div>

<?php $this->stop() ?>
