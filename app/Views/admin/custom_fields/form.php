<?php $this->layout('layout/base', ['pageTitle' => $pageTitle, 'flashMessages' => $flashMessages]) ?>

<?php $this->start('page_content') ?>
<?php // Se elimina la inclusión del navbar, ya que se inserta desde layout/base.php ?>

<div class="container-fluid py-4">
    <div class="card shadow-lg p-4">
        <div class="card-header bg-primary text-white">
            <h1 class="card-title mb-0"><?= $pageTitle ?></h1>
        </div>
        <div class="card-body">
            <form action="/admin/custom-fields/<?= $definition ? 'update/' . $definition['id'] : 'create' ?>" method="POST">
                <div class="mb-3">
                    <label for="id_tipo_activo" class="form-label"><?= $t('asset_type_to_associate') ?></label>
                    <select class="form-select" id="id_tipo_activo" name="id_tipo_activo" required>
                        <option value=""><?= $t('select_an_asset_type') ?? 'Selecciona un Tipo de Activo' ?></option>
                        <?php foreach ($assetTypes as $type): ?>
                            <option value="<?= htmlspecialchars($type['id']) ?>"
                                <?= (isset($definition['id_tipo_activo']) && $definition['id_tipo_activo'] == $type['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($type['nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="nombre_campo" class="form-label"><?= $t('field_name') ?> (<?= $t('example') ?? 'Ej' ?>. "<?= $t('memory_ram_example') ?? 'Memoria RAM' ?>")</label>
                    <input type="text" class="form-control" id="nombre_campo" name="nombre_campo" value="<?= htmlspecialchars($definition['nombre_campo'] ?? '') ?>" required>
                </div>
                <div class="mb-3">
                    <label for="tipo_dato" class="form-label"><?= $t('data_type') ?></label>
                    <select class="form-select" id="tipo_dato" name="tipo_dato" required>
                        <option value=""><?= $t('select_a_data_type') ?? 'Selecciona un Tipo de Dato' ?></option>
                        <option value="texto" <?= (isset($definition['tipo_dato']) && $definition['tipo_dato'] == 'texto') ? 'selected' : '' ?>><?= $t('text_short') ?></option>
                        <option value="texto_largo" <?= (isset($definition['tipo_dato']) && $definition['tipo_dato'] == 'texto_largo') ? 'selected' : '' ?>><?= $t('text_long') ?></option>
                        <option value="numero" <?= (isset($definition['tipo_dato']) && $definition['tipo_dato'] == 'numero') ? 'selected' : '' ?>><?= $t('number') ?></option>
                        <option value="fecha" <?= (isset($definition['tipo_dato']) && $definition['tipo_dato'] == 'fecha') ? 'selected' : '' ?>><?= $t('date') ?></option>
                        <option value="booleano" <?= (isset($definition['tipo_dato']) && $definition['tipo_dato'] == 'booleano') ? 'selected' : '' ?>><?= $t('boolean_yes_no') ?></option>
                        <option value="lista" <?= (isset($definition['tipo_dato']) && $definition['tipo_dato'] == 'lista') ? 'selected' : '' ?>><?= $t('list_options') ?></option>
                    </select>
                </div>
                <div class="mb-3" id="opciones_lista_div" style="display: <?= (isset($definition['tipo_dato']) && $definition['tipo_dato'] == 'lista') ? 'block' : 'none' ?>;">
                    <label for="opciones_lista" class="form-label"><?= $t('list_options_hint') ?></label>
                    <input type="text" class="form-control" id="opciones_lista" name="opciones_lista" value="<?= htmlspecialchars($definition['opciones_lista'] ?? '') ?>">
                    <small class="form-text text-muted"><?= $t('list_options_example') ?? 'Ej: Opción1, Opción2, Otra Opción' ?></small>
                </div>
                <div class="mb-3" id="unidad_div" style="display: <?= (isset($definition['tipo_dato']) && $definition['tipo_dato'] == 'numero') ? 'block' : 'none' ?>;">
                    <label for="unidad" class="form-label"><?= $t('unit') ?> (<?= $t('example') ?? 'Ej' ?>. "<?= $t('unit_gb_example') ?? 'GB' ?>", "<?= $t('unit_ghz_example') ?? 'GHz' ?>", "<?= $t('unit_cores_example') ?? 'cores' ?>")</label>
                    <input type="text" class="form-control" id="unidad" name="unidad" value="<?= htmlspecialchars($definition['unidad'] ?? '') ?>">
                    <small class="form-text text-muted"><?= $t('unit_hint') ?></small>
                </div>
                <div class="mb-3 form-check">
                    <input type="checkbox" class="form-check-input" id="es_requerido" name="es_requerido" value="1" <?= (isset($definition['es_requerido']) && $definition['es_requerido'] == 1) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="es_requerido"><?= $t('field_required') ?></label>
                </div>
                <div class="mb-3">
                    <label for="descripcion" class="form-label"><?= $t('field_description') ?></label>
                    <textarea class="form-control" id="descripcion" name="descripcion" rows="2"><?= htmlspecialchars($definition['descripcion'] ?? '') ?></textarea>
                </div>

                <button type="submit" class="btn btn-success me-2"><?= $t('save') ?></button>
                <a href="/admin/custom-fields" class="btn btn-secondary"><?= $t('cancel') ?></a>
            </form>
        </div>
    </div>
</div>
<?php $this->stop() ?>
