<?php $this->layout('layout/base', ['pageTitle' => $pageTitle, 'flashMessages' => $flashMessages]) ?>

<?php $this->start('page_content') ?>

<div class="container-fluid py-4">
    <div class="card shadow-lg p-4">
        <div class="card-header bg-primary text-white">
            <h1 class="card-title mb-0"><?= $pageTitle ?></h1>
        </div>
        <div class="card-body">
            <form action="/assets/<?= $asset ? 'update/' . $asset['id'] : 'create' ?>" method="POST" enctype="multipart/form-data">
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="nombre" class="form-label"><?= $t('asset_name') ?></label>
                            <input type="text" class="form-control" id="nombre" name="nombre" value="<?= htmlspecialchars($asset['nombre'] ?? '') ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="numero_serie" class="form-label"><?= $t('serial_number') ?></label>
                            <input type="text" class="form-control" id="numero_serie" name="numero_serie" value="<?= htmlspecialchars($asset['numero_serie'] ?? '') ?>">
                        </div>
                        <div class="mb-3">
                            <label for="id_tipo_activo" class="form-label"><?= $t('asset_type') ?></label>
                            <select class="form-select" id="id_tipo_activo" name="id_tipo_activo" required>
                                <option value=""><?= $t('select_a_type') ?? 'Selecciona un Tipo' ?></option>
                                <?php foreach ($assettypes as $type): ?>
                                    <option value="<?= htmlspecialchars($type['id']) ?>"
                                        <?= (isset($asset['id_tipo_activo']) && $asset['id_tipo_activo'] == $type['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($type['nombre']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="id_fabricante" class="form-label"><?= $t('manufacturer') ?></label>
                            <select class="form-select" id="id_fabricante" name="id_fabricante">
                                <option value=""><?= $t('select_a_manufacturer') ?? 'Selecciona un Fabricante' ?></option>
                                <?php foreach ($manufacturers as $manufacturer): ?>
                                    <option value="<?= htmlspecialchars($manufacturer['id']) ?>"
                                        <?= (isset($asset['id_fabricante']) && $asset['id_fabricante'] == $manufacturer['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($manufacturer['nombre']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="id_modelo" class="form-label"><?= $t('model') ?></label>
                            <select class="form-select" id="id_modelo" name="id_modelo" disabled>
                                <option value=""><?= $t('select_a_model') ?? 'Selecciona un Modelo' ?></option>
                                <?php /* Los modelos se cargarán dinámicamente vía AJAX después de seleccionar un fabricante */ ?>
                                <?php // La lista original de assetModels está para el caso de que no haya JS o para la carga inicial ?>
                                <?php foreach ($assetModels as $modelItem): ?>
                                    <option value="<?= htmlspecialchars($modelItem['id']) ?>"
                                        <?= (isset($asset['id_modelo']) && $asset['id_modelo'] == $modelItem['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($modelItem['nombre']) ?> (<?= htmlspecialchars($modelItem['fabricante_nombre'] ?? $t('na')) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="id_estado" class="form-label"><?= $t('status') ?></label>
                            <select class="form-select" id="id_estado" name="id_estado" required>
                                <option value=""><?= $t('select_a_status') ?? 'Selecciona un Estado' ?></option>
                                <?php foreach ($assetstatuses as $status): ?>
                                    <option value="<?= htmlspecialchars($status['id']) ?>"
                                        <?= (isset($asset['id_estado']) && $asset['id_estado'] == $status['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($status['nombre']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="id_ubicacion" class="form-label"><?= $t('location') ?></label>
                            <select class="form-select" id="id_ubicacion" name="id_ubicacion">
                                <option value=""><?= $t('select_a_location') ?? 'Selecciona una Ubicación' ?></option>
                                <?php foreach ($locations as $location): ?>
                                    <option value="<?= htmlspecialchars($location['id']) ?>"
                                        <?= (isset($asset['id_ubicacion']) && $asset['id_ubicacion'] == $location['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($location['nombre']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="id_departamento" class="form-label"><?= $t('department') ?></label>
                            <select class="form-select" id="id_departamento" name="id_departamento">
                                <option value=""><?= $t('select_a_department') ?? 'Selecciona un Departamento' ?></option>
                                <?php foreach ($departments as $department): ?>
                                    <option value="<?= htmlspecialchars($department['id']) ?>"
                                        <?= (isset($asset['id_departamento']) && $asset['id_departamento'] == $department['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($department['nombre']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="id_formato_adquisicion" class="form-label"><?= $t('acquisition_format') ?></label>
                            <select class="form-select" id="id_formato_adquisicion" name="id_formato_adquisicion">
                                <option value=""><?= $t('select_a_format') ?? 'Selecciona un Formato' ?></option>
                                <?php foreach ($acquisitionformats as $format): ?>
                                    <option value="<?= htmlspecialchars($format['id']) ?>"
                                        <?= (isset($asset['id_formato_adquisicion']) && $asset['id_formato_adquisicion'] == $format['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($format['nombre']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="id_proveedor_adquisicion" class="form-label"><?= $t('acquisition_provider') ?></label>
                            <select class="form-select" id="id_proveedor_adquisicion" name="id_proveedor_adquisicion">
                                <option value=""><?= $t('select_a_provider') ?? 'Selecciona un Proveedor' ?></option>
                                <?php foreach ($providers as $provider): ?>
                                    <option value="<?= htmlspecialchars($provider['id']) ?>"
                                        <?= (isset($asset['id_proveedor_adquisicion']) && $asset['id_proveedor_adquisicion'] == $provider['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($provider['nombre']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="fecha_compra" class="form-label"><?= $t('purchase_date') ?></label>
                            <input type="date" class="form-control" id="fecha_compra" name="fecha_compra" value="<?= htmlspecialchars($asset['fecha_compra'] ?? '') ?>">
                        </div>
                        <div class="mb-3">
                            <label for="precio_compra" class="form-label"><?= $t('purchase_price') ?></label>
                            <input type="number" step="0.01" class="form-control" id="precio_compra" name="precio_compra" value="<?= htmlspecialchars($asset['precio_compra'] ?? '') ?>">
                        </div>
                        <div class="mb-3">
                            <label for="fecha_fin_garantia" class="form-label"><?= $t('warranty_end_date') ?></label>
                            <input type="date" class="form-control" id="fecha_fin_garantia" name="fecha_fin_garantia" value="<?= htmlspecialchars($asset['fecha_fin_garantia'] ?? '') ?>">
                        </div>
                        <div class="mb-3">
                            <label for="fecha_fin_mantenimiento" class="form-label"><?= $t('maintenance_end_date') ?></label>
                            <input type="date" class="form-control" id="fecha_fin_mantenimiento" name="fecha_fin_mantenimiento" value="<?= htmlspecialchars($asset['fecha_fin_mantenimiento'] ?? '') ?>">
                        </div>
                        <div class="mb-3">
                            <label for="fecha_fin_vida" class="form-label"><?= $t('eol_date') ?></label>
                            <input type="date" class="form-control" id="fecha_fin_vida" name="fecha_fin_vida" value="<?= htmlspecialchars($asset['fecha_fin_vida'] ?? '') ?>">
                        </div>
                        <div class="mb-3">
                            <label for="fecha_fin_soporte_mainstream" class="form-label"><?= $t('mainstream_support_end_date') ?></label>
                            <input type="date" class="form-control" id="fecha_fin_soporte_mainstream" name="fecha_fin_soporte_mainstream" value="<?= htmlspecialchars($asset['fecha_fin_soporte_mainstream'] ?? '') ?>">
                        </div>
                        <div class="mb-3">
                            <label for="fecha_fin_soporte_extended" class="form-label"><?= $t('extended_support_end_date') ?></label>
                            <input type="date" class="form-control" id="fecha_fin_soporte_extended" name="fecha_fin_soporte_extended" value="<?= htmlspecialchars($asset['fecha_fin_soporte_extended'] ?? '') ?>">
                        </div>
                        <div class="mb-3">
                            <label for="fecha_venta" class="form-label"><?= $t('sale_date') ?></label>
                            <input type="date" class="form-control" id="fecha_venta" name="fecha_venta" value="<?= htmlspecialchars($asset['fecha_venta'] ?? '') ?>">
                        </div>
                        <div class="mb-3">
                            <label for="valor_residual" class="form-label"><?= $t('residual_value') ?></label>
                            <input type="number" step="0.01" class="form-control" id="valor_residual" name="valor_residual" value="<?= htmlspecialchars($asset['valor_residual'] ?? '') ?>">
                        </div>
                        <div class="mb-3">
                            <label for="descripcion" class="form-label"><?= $t('asset_description') ?></label>
                            <textarea class="form-control" id="descripcion" name="descripcion" rows="3"><?= htmlspecialchars($asset['descripcion'] ?? '') ?></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="imagen_ruta" class="form-label"><?= $t('asset_image') ?></label>
                            <input type="file" class="form-control" id="imagen_ruta" name="imagen_ruta" accept=".png, .jpg, .jpeg">
                            <?php if (isset($asset['imagen_ruta']) && !empty($asset['imagen_ruta'])): ?>
                                <div class="mt-2">
                                    <img src="<?= htmlspecialchars($asset['imagen_ruta']) ?>" alt="<?= $t('current_image') ?>" style="max-width: 100px; max-height: 100px;">
                                    <small class="form-text text-muted"><?= $t('upload_new_image') ?></small>
                                    <input type="hidden" name="current_image_path" value="<?= htmlspecialchars($asset['imagen_ruta']) ?>">
                                </div>
                            <?php endif; ?>
                            <?php if (isset($asset['id_modelo']) && !empty($asset['id_modelo'])): ?>
                                <div class="mt-2">
                                    <small class="form-text text-muted"><?= $t('use_master_image') ?></small>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <hr class="my-4">
                <h4><?= $t('attachments') ?></h4>
                <div class="mb-3">
                    <label for="attachments" class="form-label"><?= $t('upload_new_files') ?></label>
                    <input type="file" class="form-control" id="attachments" name="attachments[]" multiple>
                    <small class="form-text text-muted"><?= $t('multiple_files_info') ?></small>
                </div>

                <?php if (!empty($attachments)): ?>
                    <div class="mb-3">
                        <label class="form-label"><?= $t('current_attachments') ?>:</label>
                        <ul class="list-group">
                            <?php foreach ($attachments as $attachment): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <a href="<?= htmlspecialchars($attachment['ruta_almacenamiento']) ?>" target="_blank" class="text-decoration-none">
                                        <i class="bi bi-file-earmark-fill me-2"></i><?= htmlspecialchars($attachment['nombre_original']) ?>
                                    </a>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="delete_attachments[]" value="<?= htmlspecialchars($attachment['id']) ?>" id="deleteAttachment<?= $attachment['id'] ?>">
                                        <label class="form-check-label text-danger" for="deleteAttachment<?= $attachment['id'] ?>">
                                            <?= $t('delete') ?>
                                        </label>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                <hr class="my-4">
                <h4><?= $t('specific_asset_details') ?></h4>
                <div id="custom-fields-container"
                     data-custom-field-values='<?= json_encode($customFieldValues ?? []) ?>'
                     data-current-asset-model-id='<?= json_encode((int)($asset['id_modelo'] ?? 0) ?: null) ?>'>
                    <div class="alert alert-info" role="alert"><?= $t('select_asset_type_for_custom_fields') ?></div>
                </div>
                <div id="type-change-warning" class="alert alert-warning d-none" role="alert">
                    <?= $t('type_changed_custom_fields_deleted') ?>
                </div>

                <hr class="my-4">
                <h4><?= $t('associated_contracts') ?></h4>
                <div class="mb-3">
                    <label for="associated_contracts" class="form-label"><?= $t('select_contracts') ?></label>
                    <select class="form-select" id="associated_contracts" name="associated_contracts[]" multiple size="5">
                        <?php
                        $associatedIds = array_column($associatedContracts, 'id_contrato');
                        foreach ($allContracts as $contract):
                        ?>
                            <option value="<?= htmlspecialchars($contract['id']) ?>"
                                <?= in_array($contract['id'], $associatedIds) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($contract['numero_contrato']) ?> (<?= $t('type') ?>: <?= htmlspecialchars($contract['tipo_contrato_nombre'] ?? $t('na')) ?>, <?= $t('end_date') ?>: <?= htmlspecialchars($contract['fecha_fin']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <small class="form-text text-muted"><?= $t('select_multiple_hint') ?></small>
                </div>
                <button type="submit" class="btn btn-success me-2"><?= $t('save') ?></button>
                <a href="/assets" class="btn btn-secondary"><?= $t('cancel') ?></a>
            </form>
        </div>
    </div>
</div>
<?php $this->stop() ?>
