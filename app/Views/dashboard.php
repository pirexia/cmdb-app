<?php
/**
 * app/Views/dashboard.php
 *
 * Vista principal de la aplicación. Muestra un panel de control con métricas
 * y resúmenes del estado de los activos y contratos de la CMDB.
 *
 * @param string $pageTitle El título de la página.
 * @param array $flashMessages Los mensajes flash de la sesión.
 * @param int $totalAssets El número total de activos.
 * @param int $totalContracts El número total de contratos.
 * @param array $assetsByStatus Un array con el conteo de activos por estado.
 * @param array $assetsByType Un array con el conteo de activos por tipo.
 * @param array $expiringAssets Un array con los activos próximos a caducar.
 * @param array $expiringContracts Un array con los contratos próximos a caducar.
 * @param int $daysAdvance Número de días para el umbral de expiración.
 * @param callable $t La función de traducción.
 */
?>
<?php $this->layout('layout/base', ['pageTitle' => $pageTitle, 'flashMessages' => $flashMessages]) ?>

<?php $this->start('page_content') ?>

<!-- Muestra una cabecera de bienvenida con información de la aplicación -->
<div class="p-5 mb-4 bg-light rounded-3">
    <div class="container-fluid py-5">
        <h1 class="display-5 fw-bold"><?= $t('welcome_to_cmdb') ?></h1>
        <p class="col-md-8 fs-4"><?= $t('cmdb_description') ?></p>
        <a href="/assets" class="btn btn-primary btn-lg" type="button"><?= $t('view_assets') ?></a>
    </div>
</div>

<!-- Muestra las métricas clave de la CMDB en un diseño de tarjetas (estilo imagen) -->
<div class="row row-cols-1 row-cols-md-2 row-cols-lg-4 g-4 mb-4">
    <!-- Tarjeta para el total de activos -->
    <div class="col">
        <div class="card h-100 bg-success-subtle border-success rounded-3 overflow-hidden">
            <div class="card-body">
                <h5 class="card-title text-success-emphasis"><?= $t('total_assets') ?></h5>
                <p class="card-text fs-1 fw-bold text-success-emphasis"><?= htmlspecialchars($totalAssets) ?></p>
            </div>
        </div>
    </div>
    <!-- Tarjeta para el total de contratos -->
    <div class="col">
        <div class="card h-100 bg-primary-subtle border-primary rounded-3 overflow-hidden">
            <div class="card-body">
                <h5 class="card-title text-primary-emphasis"><?= $t('total_contracts') ?></h5>
                <p class="card-text fs-1 fw-bold text-primary-emphasis"><?= htmlspecialchars($totalContracts) ?></p>
            </div>
        </div>
    </div>
    <!-- Tarjeta para activos próximos a expirar -->
    <div class="col">
        <div class="card h-100 bg-warning-subtle border-warning rounded-3 overflow-hidden">
            <div class="card-body">
                <h5 class="card-title text-warning-emphasis"><?= $t('expiring_assets_contracts') ?></h5>
                <p class="card-text fs-1 fw-bold text-warning-emphasis"><?= count($expiringAssets) ?></p>
            </div>
        </div>
    </div>
    <!-- Tarjeta para contratos próximos a expirar -->
    <div class="col">
        <div class="card h-100 bg-info-subtle border-info rounded-3 overflow-hidden">
            <div class="card-body">
                <h5 class="card-title text-info-emphasis"><?= $t('expiring_contracts_count') ?? 'Contratos a expirar' ?></h5>
                <p class="card-text fs-1 fw-bold text-info-emphasis"><?= count($expiringContracts) ?></p>
            </div>
        </div>
    </div>
</div>

<!-- Gráficos del dashboard -->
<div class="row mb-4">
    <div class="col-md-6">
        <div class="card shadow-sm rounded-3">
            <div class="card-header bg-light">
                <?= $t('assets_by_type') ?>
            </div>
            <div class="card-body">
                <canvas id="assetsByTypeChart"></canvas>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card shadow-sm rounded-3">
            <div class="card-header bg-light">
                <?= $t('assets_by_status') ?>
            </div>
            <div class="card-body">
                <canvas id="assetsByStatusChart"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Modal para Activos y Contratos Próximos a Caducar -->
<div class="modal fade" id="expiringItemsModal" tabindex="-1" aria-labelledby="expiringItemsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="expiringItemsModalLabel"><?= $t('expiring_items_title', ['%days%' => $daysAdvance]) ?></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="<?= $t('close') ?>"></button>
            </div>
            <div class="modal-body">
                <?php if (!empty($expiringAssets)): ?>
                    <h6><?= $t('assets_expiring') ?>:</h6>
                    <ul class="list-group mb-3">
                        <?php foreach ($expiringAssets as $asset): ?>
                            <li class="list-group-item">
                                <strong><?= htmlspecialchars($asset['nombre']) ?></strong> (<?= htmlspecialchars($asset['numero_serie'] ?? $t('na')) ?>) - <?= htmlspecialchars($asset['tipo_activo_nombre'] ?? $t('na')) ?>
                                <br>
                                <small>
                                    <?php if (!empty($asset['fecha_fin_garantia'])): ?>
                                        <?= $t('warranty_end_date') ?>: <?= htmlspecialchars($asset['fecha_fin_garantia']) ?>
                                    <?php endif; ?>
                                    <?php if (!empty($asset['fecha_fin_mantenimiento'])): ?>
                                        <?= $t('maintenance_end_date') ?>: <?= htmlspecialchars($asset['fecha_fin_mantenimiento']) ?>
                                    <?php endif; ?>
                                    <?php if (!empty($asset['fecha_fin_soporte_mainstream'])): ?>
                                        <?= $t('mainstream_support_end_date') ?>: <?= htmlspecialchars($asset['fecha_fin_soporte_mainstream']) ?>
                                    <?php endif; ?>
                                    <?php if (!empty($asset['fecha_fin_soporte_extended'])): ?>
                                        <?= $t('extended_support_end_date') ?>: <?= htmlspecialchars($asset['fecha_fin_soporte_extended']) ?>
                                    <?php endif; ?>
                                </small>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p><?= $t('no_assets_expiring') ?></p>
                <?php endif; ?>

                <?php if (!empty($expiringContracts)): ?>
                    <h6><?= $t('contracts_expiring') ?>:</h6>
                    <ul class="list-group">
                        <?php foreach ($expiringContracts as $contract): ?>
                            <li class="list-group-item">
                                <strong><?= htmlspecialchars($contract['numero_contrato']) ?></strong> (<?= htmlspecialchars($contract['tipo_contrato_nombre'] ?? $t('na')) ?>) - <?= htmlspecialchars($contract['proveedor_nombre'] ?? $t('na')) ?>
                                <br>
                                <small><?= $t('end_date') ?>: <?= htmlspecialchars($contract['fecha_fin']) ?></small>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p><?= $t('no_contracts_expiring') ?></p>
                <?php endif; ?>

                <?php if (empty($expiringAssets) && empty($expiringContracts)): ?>
                    <p class="alert alert-info"><?= $t('no_expiring_items', ['%s' => $daysAdvance]) ?></p>
                <?php endif; ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= $t('close') ?></button>
            </div>
        </div>
    </div>
</div>

<?php $this->stop() ?>

