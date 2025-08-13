<?php $this->layout('layout/base', ['pageTitle' => $pageTitle, 'flashMessages' => $flashMessages]) ?>

<?php $this->start('page_content') ?>

<div class="container-fluid py-4">
    <div class="card shadow-lg p-4">
        <div class="card-header bg-primary text-white">
            <h1 class="card-title mb-0"><?= $pageTitle ?></h1>
        </div>
        <div class="card-body">
            <form action="/admin/masters/contract/<?= $contract ? 'update/' . $contract['id'] : 'create' ?>" method="POST">
                <div class="mb-3">
                    <label for="numero_contrato" class="form-label"><?= $t('contract_number') ?></label>
                    <input type="text" class="form-control" id="numero_contrato" name="numero_contrato" value="<?= htmlspecialchars($contract['numero_contrato'] ?? '') ?>" required>
                </div>
                <div class="mb-3">
                    <label for="id_tipo_contrato" class="form-label"><?= $t('contract_type') ?></label>
                    <select class="form-select" id="id_tipo_contrato" name="id_tipo_contrato" required>
                        <option value=""><?= $t('select_a_type') ?? 'Selecciona un Tipo' ?></option>
                        <?php foreach ($contractTypes as $type): ?>
                            <option value="<?= htmlspecialchars($type['id']) ?>"
                                <?= (isset($contract['id_tipo_contrato']) && $contract['id_tipo_contrato'] == $type['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($type['nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="id_proveedor" class="form-label"><?= $t('provider') ?></label>
                    <select class="form-select" id="id_proveedor" name="id_proveedor">
                        <option value=""><?= $t('select_a_provider') ?? 'Selecciona un Proveedor' ?></option>
                        <?php foreach ($providers as $provider): ?>
                            <option value="<?= htmlspecialchars($provider['id']) ?>"
                                <?= (isset($contract['id_proveedor']) && $contract['id_proveedor'] == $provider['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($provider['nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="fecha_inicio" class="form-label"><?= $t('start_date') ?></label>
                    <input type="date" class="form-control" id="fecha_inicio" name="fecha_inicio" value="<?= htmlspecialchars($contract['fecha_inicio'] ?? '') ?>" required>
                </div>
                <div class="mb-3">
                    <label for="fecha_fin" class="form-label"><?= $t('end_date') ?></label>
                    <input type="date" class="form-control" id="fecha_fin" name="fecha_fin" value="<?= htmlspecialchars($contract['fecha_fin'] ?? '') ?>" required>
                </div>
                <div class="mb-3">
                    <label for="costo_anual" class="form-label"><?= $t('annual_cost') ?></label>
                    <input type="number" step="0.01" class="form-control" id="costo_anual" name="costo_anual" value="<?= htmlspecialchars($contract['costo_anual'] ?? '') ?>">
                </div>
                <div class="mb-3">
                    <label for="descripcion" class="form-label"><?= $t('description') ?></label>
                    <textarea class="form-control" id="descripcion" name="descripcion" rows="3"><?= htmlspecialchars($contract['descripcion'] ?? '') ?></textarea>
                </div>

                <button type="submit" class="btn btn-success me-2"><?= $t('save') ?></button>
                <a href="/admin/masters/contract" class="btn btn-secondary"><?= $t('cancel') ?></a>
            </form>
        </div>
    </div>
</div>
<?php $this->stop() ?>
