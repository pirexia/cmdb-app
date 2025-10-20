<?php
/**
 * app/Views/partials/datatables_init.php
 *
 * Script de inicialización genérico para DataTables.
 * Se incluye en las vistas que contienen tablas.
 *
 * @param string $tableId El ID de la tabla a inicializar.
 * @param array $options Opciones adicionales de configuración de DataTables.
 */

// Mapeo de códigos de idioma de la app a los de DataTables
$dtLangMap = [
    'es' => 'es-ES',
    'en' => 'en-GB',
    'pt' => 'pt-BR',
    'fr' => 'fr-FR',
    'de' => 'de-DE',
    'it' => 'it-IT',
];
$dtLangCode = $dtLangMap[$currentLanguage] ?? 'es-ES';

// Opciones por defecto para todas las tablas
$defaultOptions = [
    'language' => [
        'url' => $this->asset('js/i18n/' . $dtLangCode . '.json')
    ],
    'dom' => 'Bfltip',
    'buttons' => [
        'excelHtml5',
        'csvHtml5',
        ['extend' => 'pdfHtml5', 'orientation' => 'landscape', 'pageSize' => 'A4'],
        'colvis'
    ],
    'colReorder' => true,
    'processing' => true,
    'responsive' => true,
];

// Fusionar opciones por defecto con las específicas de la vista
$finalOptions = array_merge_recursive($defaultOptions, $options ?? []);

?>

<script>
$(document).ready(function() {
    // Inicializar DataTables con las opciones finales
    $('#<?= $this->e($tableId) ?>').DataTable(<?= json_encode($finalOptions, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?>);

    // Adjuntar el manejador de eventos para los formularios de borrado
    $('#<?= $this->e($tableId) ?>').on('submit', 'form.delete-form', function(e) {
        return confirm($(this).data('confirm-message'));
    });
});
</script>