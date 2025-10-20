<?php $this->layout('layout/base', ['pageTitle' => $pageTitle, 'flashMessages' => $flashMessages]) ?>

<?php $this->start('page_content') ?>

<div class="container-fluid py-4">
    <div class="card shadow-lg p-4">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <h1 class="card-title mb-0"><?= $this->e($pageTitle) ?></h1>
            <a href="/admin/masters/language/create" class="btn btn-light btn-sm">
                <i class="bi bi-plus-circle me-1"></i> <?= $t('new') ?> <?= $t('language') ?>
            </a>
        </div>
        <div class="card-body">
            <?php if (empty($items)): ?>
                <div class="alert alert-info text-center" role="alert">
                    <?= $t('datatable_no_data') ?>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table id="languages-table" class="table table-striped table-hover w-100">
                        <thead>
                            <tr>
                                <th><?= $t('id') ?></th>
                                <th><?= $t('name') ?></th>
                                <th><?= $t('language_iso_code') ?? 'Código ISO' ?></th>
                                <th><?= $t('active') ?></th>
                                <th><?= $t('language_file_name') ?? 'Nombre Fichero' ?></th>
                                <th><?= $t('actions') ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items as $item): ?>
                                <tr>
                                    <td><?= htmlspecialchars($item['id']) ?></td>
                                    <td><?= htmlspecialchars($item['nombre']) ?></td>
                                    <td><?= htmlspecialchars($item['codigo_iso']) ?></td>
                                    <td>
                                        <?php if ($item['activo'] == 1): ?>
                                            <span class="text-success" title="<?= $t('active') ?>"><i class="bi bi-check-circle-fill"></i></span>
                                        <?php else: ?>
                                            <span class="text-danger" title="<?= $t('inactive') ?>"><i class="bi bi-x-circle-fill"></i></span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($item['nombre_fichero']) ?></td>
                                    <td>
                                        <a href="/admin/masters/language/edit/<?= $item['id'] ?>" class="btn btn-sm btn-warning me-1" title="<?= $t('edit') ?>"><i class="bi bi-pencil"></i></a>
                                        <form action="/admin/masters/language/toggle-status/<?= $item['id'] ?>" method="POST" style="display:inline;">
                                            <button type="submit" class="btn btn-sm <?= $item['activo'] ? 'btn-secondary' : 'btn-success' ?> me-1" title="<?= $item['activo'] ? $t('deactivate') : $t('activate') ?>"><i class="bi <?= $item['activo'] ? 'bi-toggle-off' : 'bi-toggle-on' ?>"></i></button>
                                        </form>
                                        <form action="/admin/masters/language/delete/<?= $item['id'] ?>" method="POST" class="delete-form" style="display:inline;" data-confirm-message="<?= htmlspecialchars($t('confirm_delete_master_item'), ENT_QUOTES, 'UTF-8') ?>">
                                            <button type="submit" class="btn btn-sm btn-danger" title="<?= $t('delete') ?>"><i class="bi bi-trash"></i></button>
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

<?php $this->stop() ?>