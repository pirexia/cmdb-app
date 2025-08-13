<?php
/**
 * app/Views/layout/base.php
 *
 * Plantilla base para todas las vistas de la aplicación. Define la estructura HTML
 * principal, incluye CSS, JavaScript y gestiona la visualización de mensajes flash.
 *
 * @param string $pageTitle El título de la página actual.
 * @param array $flashMessages Los mensajes flash almacenados en la sesión.
 * @param array $config El array de configuración global de la aplicación.
 * @param callable $t La función de traducción para internacionalización.
 */
?>
<!DOCTYPE html>
<html lang="<?= $t('lang_code') ?? 'es' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? $t('app_name') ?></title>

    <!-- Hojas de Estilo CSS -->
    <!-- Nota: Se cargan los archivos de librería antes que los archivos locales para
               evitar que los estilos de la aplicación sean sobrescritos. -->
    
    <!-- Bootstrap CSS (CDN) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" xintegrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    
    <!-- Bootstrap Icons (CDN) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <!-- DataTables CSS (CDN) -->
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/2.3.1/css/dataTables.dataTables.min.css"/>
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/buttons/3.2.3/css/buttons.dataTables.min.css"/>
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/colreorder/2.1.1/css/colReorder.dataTables.min.css"/>
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/2.3.1/css/dataTables.bootstrap5.min.css"/>
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/buttons/3.2.3/css/buttons.bootstrap5.min.css"/>
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/colreorder/2.1.1/css/colReorder.bootstrap5.min.css"/>

    <!-- Archivos CSS propios de la aplicación -->
    <link rel="stylesheet" href="<?= $config['paths']['public_assets'] ?>/css/style.css">

</head>
<body class="d-flex flex-column min-vh-100">
    <!-- El navbar se incluye aquí para que esté presente en todas las páginas -->
    <?php $this->insert('partials/navbar') ?>

    <!-- Contenedor principal del contenido de la página -->
    <main class="flex-shrink-0 mt-5">
        <!-- container-fluid para que ocupe todo el ancho del monitor -->
        <div class="container-fluid py-4">
            <?php
            // Muestra los mensajes flash almacenados en la sesión.
            if (isset($flashMessages) && is_array($flashMessages)) {
                foreach ($flashMessages as $message) {
                    // Determina la clase CSS del mensaje (success, danger, info, etc.).
                    $alertClass = 'alert-' . ($message['type'] ?? 'info');
                    // Renderiza un div con las clases de alerta de Bootstrap y una clase de identificación para JavaScript.
                    echo "<div class='alert {$alertClass} alert-dismissible fade show flash-message' role='alert'>";
                    echo htmlspecialchars($message['message']); // Muestra el mensaje con HTML escapado.
                    echo "<button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>";
                    echo "</div>";
                }
            }
            ?>
            <!-- Inyecta el contenido específico de la página (ej. dashboard, formularios) -->
            <?= $this->section('page_content') ?>
        </div>
    </main>

    <!-- Pie de página de la aplicación -->
    <footer class="footer mt-auto py-3 bg-light">
        <!-- container-fluid para que ocupe todo el ancho del monitor -->
        <div class="container-fluid text-center">
            <span class="text-muted">&copy; <?= date('Y') ?> <?= $t('app_name') ?>. <?= $t('all_rights_reserved') ?>.</span>
        </div>
    </footer>

    <!-- Archivos JavaScript -->
    <!-- Nota: Se cargan al final del body para no bloquear la renderización del contenido. -->

    <!-- jQuery (CDN - PRIMERO para que otros scripts que lo usen funcionen) -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>

    <!-- Bootstrap JS Bundle (CDN - DESPUÉS DE JQUERY) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" xintegrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>

    <!-- DataTables JS (CDN) -->
    <script type="text/javascript" src="https://cdn.datatables.net/2.3.1/js/dataTables.min.js"></script>
    <script type="text/javascript" src="https://cdn.datatables.net/2.3.1/js/dataTables.bootstrap5.min.js"></script>
    <script type="text/javascript" src="https://cdn.datatables.net/buttons/3.2.3/js/dataTables.buttons.min.js"></script>
    <script type="text/javascript" src="https://cdn.datatables.net/buttons/3.2.3/js/buttons.html5.min.js"></script>
    <script type="text/javascript" src="https://cdn.datatables.net/buttons/3.2.3/js/buttons.print.min.js"></script>
    <script type="text/javascript" src="https://cdn.datatables.net/buttons/3.2.3/js/buttons.colVis.min.js"></script>
    <script type="text/javascript" src="https://cdn.datatables.net/buttons/3.2.3/js/buttons.bootstrap5.min.js"></script>
    <script type="text/javascript" src="https://cdn.datatables.net/colreorder/2.1.1/js/dataTables.colreorder.min.js"></script>

    <!-- JSZip y pdfmake (para exportación de DataTables) -->
    <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
    <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
    <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>

    <!-- Scripts de la aplicación (propios) -->
    <script src="<?= $config['paths']['public_assets'] ?>/js/app.js"></script>

    <!-- Lógica de inclusión de scripts dinámicos (partials) -->
    <!-- Nota: Los scripts dinámicos se insertan aquí para que puedan acceder a las variables PHP y a las librerías cargadas previamente. -->
    <script>
        $(document).ready(function() {
            // Lógica para incluir scripts de DataTables y formularios específicos por página.
            var currentPath = window.location.pathname;

            if (currentPath === '/dashboard') {
                <?php $this->insert('partials/dashboard_scripts', [
                    'assetsByType' => $assetsByType ?? [],
                    'assetsByStatus' => $assetsByStatus ?? [],
                    't' => $t
                ]) ?>
            } else if (currentPath.startsWith('/assets')) {
                <?php $this->insert('partials/datatables_assets') ?>
            } else if (currentPath.startsWith('/admin/masters/')) {
                if (currentPath.match(/^\/admin\/masters\/(manufacturer|asset-type|asset-status|contract-type|location|department|provider|acquisition-format|language)(\/?$|\/create|\/edit\/|\/delete)/)) {
                    <?php $this->insert('partials/datatables_masters') ?>
                }
                if (currentPath.startsWith('/admin/masters/model')) {
                    <?php $this->insert('partials/datatables_models') ?>
                }
                if (currentPath.startsWith('/admin/masters/contract')) {
                    <?php $this->insert('partials/datatables_contracts') ?>
                }
            } else if (currentPath.startsWith('/admin/custom-fields')) {
                <?php $this->insert('partials/datatables_custom_fields') ?>
                if (currentPath.startsWith('/admin/custom-fields/create') || currentPath.startsWith('/admin/custom-fields/edit/')) {
                    <?php $this->insert('partials/custom_fields_form_scripts') ?>
                }
            } else if (currentPath.startsWith('/admin/users')) {
                <?php $this->insert('partials/datatables_users') ?>
                if (currentPath.startsWith('/admin/users/create') || currentPath.startsWith('/admin/users/edit/')) {
                    <?php $this->insert('partials/users_form_scripts') ?>
                }
            } else if (currentPath.startsWith('/admin/sources')) {
                <?php $this->insert('partials/datatables_sources') ?>
                if (currentPath.startsWith('/admin/sources/create') || currentPath.startsWith('/admin/sources/edit/')) {
                    <?php $this->insert('partials/sources_form_scripts') ?>
                }
            } else if (currentPath.startsWith('/admin/import')) {
                if (currentPath === '/admin/import' || currentPath === '/admin/import/') {
                    <?php $this->insert('partials/import_index_scripts') ?>
                }
            } else if (currentPath.startsWith('/admin/logs')) {
                 <?php $this->insert('partials/datatables_logs') ?>
            }
        });
    </script>

    <!-- Script para los mensajes flash que se ocultan automáticamente -->
    <script>
        $(document).ready(function() {
            // Selecciona todos los elementos con la clase 'flash-message'
            $('.flash-message').each(function(index) {
                var $this = $(this);
                // Desvanecer y eliminar el mensaje después de 3 segundos (3000 milisegundos)
                setTimeout(function() {
                    $this.fadeOut('slow', function() {
                        $(this).remove();
                    });
                }, 3000); // 3 segundos
            });
        });
    </script>
</body>
</html>
