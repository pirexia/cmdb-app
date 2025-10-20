<?php
/**
 * app/Views/pages/cookie_policy.php
 *
 * Vista para la página de Política de Cookies.
 */
$this->layout('layout/base', [
    'pageTitle' => $t('cookie_policy_title'),
    'flashMessages' => $flashMessages ?? []
]);
?>

<?php $this->start('page_content') ?>
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-10 col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h1 class="card-title mb-0"><?= $this->e($t('cookie_policy_title')) ?></h1>
                </div>
                <div class="card-body">
                    <p><?= $this->e($t('cookie_policy_last_updated')) ?></p>

                    <h2><?= $this->e($t('cookie_policy_what_are_cookies_title')) ?></h2>
                    <p><?= $this->e($t('cookie_policy_what_are_cookies_text')) ?></p>

                    <h2><?= $this->e($t('cookie_policy_how_we_use_cookies_title')) ?></h2>
                    <p><?= $this->e($t('cookie_policy_how_we_use_cookies_text')) ?></p>
                    <ul>
                        <li><strong><?= $this->e($t('cookie_policy_functional_cookies_title')) ?>:</strong> <?= $this->e($t('cookie_policy_functional_cookies_text')) ?></li>
                        <li><strong><?= $this->e($t('cookie_policy_analytics_cookies_title')) ?>:</strong> <?= $this->e($t('cookie_policy_analytics_cookies_text')) ?></li>
                    </ul>

                    <h2><?= $this->e($t('cookie_policy_your_choices_title')) ?></h2>
                    <p><?= $this->e($t('cookie_policy_your_choices_text')) ?></p>
                </div>
            </div>
        </div>
    </div>
</div>
<?php $this->stop() ?>