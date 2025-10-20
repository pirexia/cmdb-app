<?php
/**
 * app/Views/layout/auth_base.php
 *
 * Plantilla base para las vistas de autenticación (login, forgot password, etc.).
 * Es una versión simplificada de base.php sin la barra de navegación.
 */
?>
<!DOCTYPE html>
<html lang="<?= $t('lang_code') ?? 'es' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? $t('app_name') ?></title>

    <!-- Bootstrap CSS (CDN) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons (CDN) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- Archivos CSS propios de la aplicación -->
    <link rel="stylesheet" href="<?= $config['paths']['public_assets'] ?>/css/style.css">
</head>
<body class="d-flex flex-column min-vh-100 bg-light">

    <!-- Contenedor principal del contenido de la página -->
    <main class="flex-shrink-0">
        <div class="container py-4">
            <?php
            // Muestra los mensajes flash almacenados en la sesión.
            if (isset($flashMessages) && is_array($flashMessages)) {
                foreach ($flashMessages as $message) {
                    $alertClass = 'alert-' . ($message['type'] ?? 'info');
                    echo "<div class='alert {$alertClass} alert-dismissible fade show flash-message' role='alert'>";
                    echo htmlspecialchars($t($message['message']));
                    echo "<button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>";
                    echo "</div>";
                }
            }
            ?>
            <!-- Inyecta el contenido específico de la página -->
            <?= $this->section('page_content') ?>
        </div>
    </main>

    <!-- Pie de página -->
    <footer class="footer mt-auto py-3 bg-light">
        <div class="container text-center">
            <span class="text-muted">&copy; <?= date('Y') ?> <?= $t('app_name') ?>. <?= $t('all_rights_reserved') ?>.</span>
        </div>
    </footer>

    <!-- jQuery (CDN) -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <!-- Bootstrap JS Bundle (CDN) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Script de consentimiento de cookies -->
    <script src="<?= $config['paths']['public_assets'] ?>/js/cookie_consent.js"></script>

    <?php $this->insert('partials/cookie_consent_loader'); ?>
</body>
</html>