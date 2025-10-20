<?php
/**
 * app/Views/partials/cookie_consent_loader.php
 * Lógica para cargar el banner de cookies si es necesario.
 */
if (!isset($_COOKIE['cookie_consent_status'])) {
    echo $this->fetch('partials/cookie_consent');
}
?>