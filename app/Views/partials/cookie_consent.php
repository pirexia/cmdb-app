<?php
/**
 * app/Views/partials/cookie_consent.php
 *
 * Vista para el banner de consentimiento de cookies.
 * Se muestra si el usuario aún no ha aceptado las cookies.
 */
?>
<style>
    .cookie-consent-banner {
        position: fixed;
        bottom: 0;
        left: 0;
        width: 100%;
        z-index: 1050; /* Por encima de la mayoría de los elementos */
    }
</style>

<div id="cookie-consent-banner" class="cookie-consent-banner alert alert-dark mb-0 rounded-0" role="alert">
    <div class="container d-flex flex-wrap justify-content-between align-items-center">
        <span class="me-3 mb-2 mb-md-0"><?= $this->e($t('cookie_consent_message')) ?> <a href="/cookie-policy" class="alert-link"><?= $this->e($t('cookie_consent_policy_link')) ?></a></span>
        <div class="ms-md-auto">
            <button id="reject-cookies-btn" class="btn btn-secondary btn-sm me-2"><?= $this->e($t('cookie_consent_reject_button')) ?></button>
            <button id="accept-cookies-btn" class="btn btn-primary btn-sm"><?= $this->e($t('cookie_consent_accept_button')) ?></button>
        </div>
    </div>
</div>