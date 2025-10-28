<?php $this->layout('layout/base', ['pageTitle' => $pageTitle, 'flashMessages' => $flashMessages]) ?>

<?php $this->start('page_content') ?>

<div class="container-fluid py-4">
    <div class="card shadow-lg p-4">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <h1 class="card-title mb-0"><?= $pageTitle ?></h1>
            <a href="/admin/users/create" class="btn btn-light btn-sm">
                <i class="bi bi-plus-circle me-1"></i> <?= $t('new_user') ?? 'Nuevo Usuario' ?>
            </a>
        </div>
        <div class="card-body">
            <?php if (empty($users)): ?>
                <div class="alert alert-info text-center" role="alert">
                    <?= $t('no_users_to_display') ?? 'No hay usuarios para mostrar.' ?>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table id="usersTable" class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th><?= $t('id') ?></th>
                                <th class="filterable-text"><?= $t('username') ?></th>
                                <th class="filterable-text"><?= $t('email_address') ?></th>
                                <th class="filterable-select"><?= $t('user_source') ?? 'Fuente' ?></th>
                                <th class="filterable-select"><?= $t('role') ?? 'Rol' ?></th>
                                <th class="filterable-select"><?= $t('active') ?? 'Activo' ?></th>
                                <th><?= $t('creation_date') ?? 'Fecha Creación' ?></th>
                                <th><?= $t('last_login') ?? 'Última Sesión' ?></th>
                                <th><?= $t('actions') ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><?= htmlspecialchars($user['id']) ?></td>
                                    <td><?= htmlspecialchars($user['nombre_usuario']) ?></td>
                                    <td><?= htmlspecialchars($user['email']) ?></td>
                                    <td><?= htmlspecialchars($user['fuente_nombre'] ?? $t('na')) ?></td>
                                    <td><?= htmlspecialchars($user['rol_nombre'] ?? $t('na')) ?></td>
                                    <td><?= $user['activo'] ? '<i class="bi bi-check-circle-fill text-success"></i> ' . $t('yes') : '<i class="bi bi-x-circle-fill text-danger"></i> ' . $t('no') ?></td>
                                    <td><?= htmlspecialchars($user['fecha_creacion']) ?></td>
                                    <td><?= htmlspecialchars($user['fecha_ultima_sesion'] ?? $t('na')) ?></td>
                                    <td>
                                        <a href="/admin/users/edit/<?= $user['id'] ?>" class="btn btn-sm btn-warning me-1" title="<?= $t('edit') ?>">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <form action="/admin/users/delete/<?= $user['id'] ?>" method="POST" style="display:inline;" onsubmit="return confirm('<?= $t('confirm_delete_user') ?? '¿Estás seguro de que quieres eliminar este usuario? Esta acción no se puede deshacer.' ?>');">
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

<?php /* ELIMINADO EL SCRIPT INLINE, AHORA EN PARTIALS/DATATABLES_USERS.PHP */ ?>

<?php $this->stop() ?>
