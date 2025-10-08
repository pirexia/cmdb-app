<?php
// app/Views/partials/navbar.php

// Helper para determinar si un enlace está activo.
// $currentPath es la ruta actual de la URL (ej. /admin/masters/manufacturer)
$currentPath = $_SERVER['REQUEST_URI'] ?? '/';
// Normalizar la URL para que no incluya parámetros de consulta
$currentPath = parse_url($currentPath, PHP_URL_PATH);

// La función $t ya estará disponible aquí por la inyección de PlatesPHP (desde bootstrap.php)

// Obtenemos el AuthService desde el contenedor para romper la dependencia circular.
$authService = $container->get(App\Services\AuthService::class);
?>
<nav class="navbar navbar-expand-lg navbar-light bg-light mb-4">
    <div class="container-fluid">
        <a class="navbar-brand" href="/dashboard"><?= $t('app_name') ?></a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link <?= ($currentPath === '/dashboard' ? 'active' : '') ?>" aria-current="page" href="/dashboard"><?= $t('dashboard') ?></a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= (str_starts_with($currentPath, '/assets') ? 'active' : '') ?>" href="/assets"><?= $t('assets') ?></a>
                </li>
                <?php if ($authService->hasRole('Administrador')): ?>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle <?= (str_starts_with($currentPath, '/admin/') ? 'active' : '') ?>" href="#" id="navbarDropdownAdmin" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <?= $t('administration') ?>
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="navbarDropdownAdmin">
                        <li><h6 class="dropdown-header"><?= $t('user_management') ?></h6></li>
                        <li><a class="dropdown-item <?= ($currentPath === '/admin/users' ? 'active' : '') ?>" href="/admin/users"><?= $t('users') ?></a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><h6 class="dropdown-header"><?= $t('cmdb_masters') ?></h6></li>
                        <li><a class="dropdown-item <?= ($currentPath === '/admin/masters/manufacturer' ? 'active' : '') ?>" href="/admin/masters/manufacturer"><?= $t('manufacturers') ?></a></li>
                        <li><a class="dropdown-item <?= ($currentPath === '/admin/masters/model' ? 'active' : '') ?>" href="/admin/masters/model"><?= $t('models') ?></a></li>
                        <li><a class="dropdown-item <?= ($currentPath === '/admin/masters/asset-type' ? 'active' : '') ?>" href="/admin/masters/asset-type"><?= $t('asset_types') ?></a></li>
                        <li><a class="dropdown-item <?= ($currentPath === '/admin/masters/asset-status' ? 'active' : '') ?>" href="/admin/masters/asset-status"><?= $t('asset_statuses') ?></a></li>
                        <li><a class="dropdown-item <?= ($currentPath === '/admin/masters/contract-type' ? 'active' : '') ?>" href="/admin/masters/contract-type"><?= $t('contract_types') ?></a></li>
                        <li><a class="dropdown-item <?= ($currentPath === '/admin/masters/contract' ? 'active' : '') ?>" href="/admin/masters/contract"><?= $t('contracts') ?></a></li>
                        <li><a class="dropdown-item <?= ($currentPath === '/admin/masters/location' ? 'active' : '') ?>" href="/admin/masters/location"><?= $t('locations') ?></a></li>
                        <li><a class="dropdown-item <?= ($currentPath === '/admin/masters/department' ? 'active' : '') ?>" href="/admin/masters/department"><?= $t('departments') ?></a></li>
                        <li><a class="dropdown-item <?= ($currentPath === '/admin/masters/provider' ? 'active' : '') ?>" href="/admin/masters/provider"><?= $t('providers') ?></a></li>
                        <li><a class="dropdown-item <?= ($currentPath === '/admin/masters/acquisition-format' ? 'active' : '') ?>" href="/admin/masters/acquisition-format"><?= $t('acquisition_formats') ?></a></li>
                        <li><a class="dropdown-item <?= ($currentPath === '/admin/masters/language' ? 'active' : '') ?>" href="/admin/masters/language"><?= $t('languages') ?></a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><h6 class="dropdown-header"><?= $t('app_settings') ?></h6></li>
                        <li><a class="dropdown-item <?= (str_starts_with($currentPath, '/admin/smtp') ? 'active' : '') ?>" href="/admin/smtp"><?= $t('smtp_settings') ?></a></li>
                        <li><a class="dropdown-item <?= ($currentPath === '/admin/custom-fields' ? 'active' : '') ?>" href="/admin/custom-fields"><?= $t('custom_fields') ?></a></li>
                        <li><a class="dropdown-item <?= (str_starts_with($currentPath, '/admin/sources') ? 'active' : '') ?>" href="/admin/sources"><?= $t('user_sources') ?? 'Fuentes de Usuario' ?></a></li>
                        <li><a class="dropdown-item <?= (str_starts_with($currentPath, '/admin/import') ? 'active' : '') ?>" href="/admin/import"><?= $t('bulk_import') ?? 'Importación Masiva' ?></a></li>
                        <li><a class="dropdown-item <?= (str_starts_with($currentPath, '/admin/logs') ? 'active' : '') ?>" href="/admin/logs"><?= $t('audit_log_title') ?? 'Log de Auditoría' ?></a></li>
                    </ul>
                </li>
                <?php endif; ?>
            </ul>
            <ul class="navbar-nav ms-auto">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="navbarDropdownUser" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <?= $t('hello') ?>, <?= htmlspecialchars($sessionService->get('username') ?? $t('guest')) ?>
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="navbarDropdownUser">
                        <li><a class="dropdown-item" href="#"><?= $t('my_profile') ?></a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="/logout"><?= $t('logout') ?></a></li>
                    </ul>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="navbarDropdownLang" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-translate me-1"></i> <?= strtoupper($sessionService->getUserLanguage()) ?>
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="navbarDropdownLang">
                        <li><a class="dropdown-item" href="/set-language/es">Español</a></li>
                        <li><a class="dropdown-item" href="/set-language/en">English</a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>
