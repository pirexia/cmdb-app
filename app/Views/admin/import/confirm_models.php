<?php
/**
 * app/Views/admin/import/confirm_models.php
 *
 * Vista para que el usuario confirme la creación de nuevos modelos de activos
 * detectados durante el pre-análisis de una importación CSV.
 *
 * @param string $pageTitle El título de la página.
 * @param array $flashMessages Los mensajes flash de la sesión.
 * @param array $newModels La lista de nuevos modelos a crear.
 * @param string $entityType El tipo de entidad que se está importando.
 * @param callable $t La función de traducción.
 */
?>
<?php $this->layout('layout/base', ['pageTitle' => $pageTitle, 'flashMessages' => $flashMessages]) ?>

<?php $this->start('page_content') ?>

<div class="container mt-5">
    <div class="card shadow-lg">
        <div class="card-header bg-primary text-white">
            <h1 class="card-title mb-0"><?= $t('import_confirm_new_models_title') ?></h1>
        </div>
        <div class="card-body">
            <p><?= $t('import_confirm_new_models_intro') ?></p>

            <?php if (!empty($newModels)): ?>
                <ul class="list-group mb-4">
                    <?php foreach ($newModels as $model): ?>
                        <li class="list-group-item">
                            <strong><?= $t('manufacturer') ?>:</strong> <?= htmlspecialchars($model['manufacturer_name']) ?>
                            <br>
                            <strong><?= $t('model') ?>:</strong> <?= htmlspecialchars($model['model_name']) ?>
                        </li>
                    <?php endforeach; ?>
                </ul>

                <form action="/admin/import/process-confirmed-import" method="post">
                    <button type="submit" class="btn btn-success"><?= $t('import_accept_and_continue') ?></button>
                    <a href="/admin/import" class="btn btn-danger"><?= $t('import_cancel') ?></a>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php $this->stop() ?>