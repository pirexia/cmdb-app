<?php $this->layout('layout/base', ['pageTitle' => $pageTitle, 'flashMessages' => $flashMessages]) ?>

<?php $this->start('page_content') ?>

<div class="container-fluid py-4">
    <div class="card shadow-lg p-4">
        <div class="card-header bg-primary text-white">
            <h1 class="card-title mb-0"><?= $pageTitle ?></h1>
        </div>
        <div class="card-body">
            <form action="/admin/sources/<?= $source ? 'update/' . $source['id'] : 'create' ?>" method="POST">
                <div class="mb-3">
                    <label for="nombre_friendly" class="form-label"><?= $t('source_name') ?></label>
                    <input type="text" class="form-control" id="nombre_friendly" name="nombre_friendly" value="<?= htmlspecialchars($source['nombre_friendly'] ?? '') ?>" required>
                </div>
                <div class="mb-3">
                    <label for="tipo_fuente" class="form-label"><?= $t('source_type') ?></label>
                    <select class="form-select" id="tipo_fuente" name="tipo_fuente" required <?= (isset($source) && $source['tipo_fuente'] === 'local' ? 'disabled' : '') ?>>
                        <option value=""><?= $t('select_a_source_type') ?></option>
                        <?php foreach ($sourceTypes as $value => $label): ?>
                            <option value="<?= htmlspecialchars($value) ?>"
                                <?= (isset($source['tipo_fuente']) && $source['tipo_fuente'] == $value) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($label) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($source) && $source['tipo_fuente'] === 'local'): ?>
                        <small class="form-text text-muted"><?= $t('local_source_type_fixed') ?></small>
                        <input type="hidden" name="tipo_fuente" value="local">
                    <?php endif; ?>
                </div>

                <div id="ldap_ad_fields" style="display: <?= (isset($source) && in_array($source['tipo_fuente'], ['ldap', 'activedirectory'])) ? 'block' : 'none' ?>;">
                    <hr>
                    <h5><?= $t('ldap_ad_connection_details') ?></h5>
                    <div class="mb-3">
                        <label for="host" class="form-label"><?= $t('host') ?></label>
                        <input type="text" class="form-control" id="host" name="host" value="<?= htmlspecialchars($source['host'] ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label for="port" class="form-label"><?= $t('port') ?></label>
                        <input type="number" class="form-control" id="port" name="port" value="<?= htmlspecialchars($source['port'] ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label for="base_dn" class="form-label"><?= $t('base_dn') ?></label>
                        <input type="text" class="form-control" id="base_dn" name="base_dn" value="<?= htmlspecialchars($source['base_dn'] ?? '') ?>">
                        <small class="form-text text-muted"><?= $t('base_dn_hint') ?></small>
                    </div>
                    <div class="mb-3">
                        <label for="bind_dn" class="form-label"><?= $t('bind_dn') ?></label>
                        <input type="text" class="form-control" id="bind_dn" name="bind_dn" value="<?= htmlspecialchars($source['bind_dn'] ?? '') ?>">
                        <small class="form-text text-muted"><?= $t('bind_dn_hint') ?></small>
                    </div>
                    <div class="mb-3">
                        <label for="bind_password" class="form-label"><?= $t('bind_password') ?></label>
                        <input type="password" class="form-control" id="bind_password" name="bind_password" value="<?= htmlspecialchars($source['bind_password'] ?? '') ?>">
                        <small class="form-text text-muted"><?= $t('bind_password_hint') ?></small>
                    </div>
                    <div class="mb-3">
                        <label for="user_filter" class="form-label"><?= $t('user_filter') ?></label>
                        <input type="text" class="form-control" id="user_filter" name="user_filter" value="<?= htmlspecialchars($source['user_filter'] ?? '') ?>">
                        <small class="form-text text-muted"><?= $t('user_filter_hint') ?></small>
                    </div>
                    <div class="mb-3">
                        <label for="group_filter" class="form-label"><?= $t('group_filter') ?></label>
                        <input type="text" class="form-control" id="group_filter" name="group_filter" value="<?= htmlspecialchars($source['group_filter'] ?? '') ?>">
                        <small class="form-text text-muted"><?= $t('group_filter_hint') ?></small>
                    </div>
                    <div class="mb-3 form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="use_tls" name="use_tls" value="1" <?= (isset($source['use_tls']) && $source['use_tls'] == 1) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="use_tls"><?= $t('use_start_tls') ?></label>
                    </div>
                    <div class="mb-3 form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="use_ssl" name="use_ssl" value="1" <?= (isset($source['use_ssl']) && $source['use_ssl'] == 1) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="use_ssl"><?= $t('use_ssl_ldaps') ?></label>
                    </div>
                    <div class="mb-3">
                        <label for="ca_cert_path" class="form-label"><?= $t('ca_cert_path') ?></label>
                        <input type="text" class="form-control" id="ca_cert_path" name="ca_cert_path" value="<?= htmlspecialchars($source['ca_cert_path'] ?? '') ?>"
                            <?= (isset($source) && ($source['use_tls'] == 1 || $source['use_ssl'] == 1)) ? '' : 'disabled' ?>> <small class="form-text text-muted"><?= $t('ca_cert_path_hint') ?></small>
                    </div>
                    <div class="mb-3">
                        <label for="timeout" class="form-label"><?= $t('timeout') ?></label>
                        <input type="number" class="form-control" id="timeout" name="timeout" value="<?= htmlspecialchars($source['timeout'] ?? '') ?>">
                        <small class="form-text text-muted"><?= $t('timeout_hint') ?></small>
                    </div>
                </div>

                <div class="mb-3 form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="activo" name="activo" value="1" <?= (isset($source['activo']) && $source['activo'] == 1) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="activo"><?= $t('active') ?></label>
                </div>

                <button type="submit" class="btn btn-success me-2"><?= $t('save') ?></button>
                <a href="/admin/sources" class="btn btn-secondary"><?= $t('cancel') ?></a>
                
                <?php if (isset($source) && in_array($source['tipo_fuente'], ['ldap', 'activedirectory'])): ?>
                    <button type="button" class="btn btn-info ms-2 test-source-btn" data-source-id="<?= $source['id'] ?? '' ?>"><?= $t('test_connection') ?></button>
                <?php endif; ?>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="testConnectionModal" tabindex="-1" aria-labelledby="testConnectionModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="testConnectionModalLabel"><?= $t('test_connection_results') ?></h5>
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
