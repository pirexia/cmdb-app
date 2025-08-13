<?php $this->layout('layout/base', ['pageTitle' => $pageTitle, 'flashMessages' => $flashMessages]) ?>

<?php $this->start('page_content') ?>
    
<div class="container-fluid py-4">
    <div class="card shadow-lg p-4">
        <div class="card-body">
            <h1 class="card-title text-center mb-4"><?= $t('user_administration') ?></h1>
            <p class="text-center"><?= $t('user_management_description') ?></p>
            <p class="text-center text-muted"><?= $t('user_management_future_phase') ?></p>
        </div>
    </div>
</div>
<?php $this->stop() ?>
