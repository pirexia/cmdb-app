<?php $this->layout('layout/base', ['pageTitle' => $pageTitle, 'flashMessages' => $flashMessages]) ?>

<?php $this->start('page_content') ?>

<div class="container-fluid py-4">
    <div class="card shadow-lg p-4">
        <div class="card-header bg-primary text-white">
            <h1 class="card-title mb-0"><?= $pageTitle ?></h1>
        </div>
        <div class="card-body">
            <p><?= $t('import_summary_intro') ?></p>
            
            <?php if (isset($importSummary)): ?>
                <div class="row text-center mb-4">
                    <div class="col">
                        <div class="card rounded-3 overflow-hidden">
                            <div class="card-body bg-light">
                                <h5 class="card-title"><?= $t('total_records') ?></h5>
                                <p class="card-text fs-4"><?= htmlspecialchars($importSummary['total_rows']) ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col">
                        <div class="card rounded-3 overflow-hidden">
                            <div class="card-body bg-success-subtle text-success-emphasis">
                                <h5 class="card-title"><?= $t('successful_records') ?></h5>
                                <p class="card-text fs-4"><?= htmlspecialchars($importSummary['successful_rows']) ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col">
                        <div class="card rounded-3 overflow-hidden">
                            <div class="card-body bg-danger-subtle text-danger-emphasis">
                                <h5 class="card-title"><?= $t('failed_records') ?></h5>
                                <p class="card-text fs-4"><?= htmlspecialchars($importSummary['failed_rows']) ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <?php if (!empty($importSummary['results'])): ?>
                    <h6><?= $t('detailed_results') ?>:</h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-striped">
                            <thead>
                                <tr>
                                    <th><?= $t('row_number') ?></th>
                                    <th><?= $t('status') ?></th>
                                    <th><?= $t('message') ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($importSummary['results'] as $result): ?>
                                    <tr class="<?= $result['status'] === 'error' ? 'table-danger' : ($result['status'] === 'updated' ? 'table-warning' : 'table-success') ?>">
                                        <td><?= htmlspecialchars($result['row']) ?></td>
                                        <td><?= htmlspecialchars($t('import_status_' . $result['status'])) ?></td>
                                        <td><?= htmlspecialchars($result['message']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>

                <?php if (isset($logPath) && file_exists($logPath)): ?>
                    <a href="/admin/import/download-log" class="btn btn-warning mt-3"><?= $t('download_import_log_button') ?></a>
                <?php endif; ?>
            <?php else: ?>
                <p class="alert alert-info"><?= $t('no_import_results_found') ?></p>
            <?php endif; ?>

            <a href="/admin/import" class="btn btn-secondary mt-3"><?= $t('go_back') ?></a>
        </div>
    </div>
</div>

<?php $this->stop() ?>
