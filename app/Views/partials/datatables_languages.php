<?php // app/Views/partials/datatables_languages.php 
// Este fichero contiene solo el código JS para inicializar la tabla,
// ya que se inserta dentro de un bloque <script> en layout/base.php
?>
$('#languages-table').DataTable({ // El ID debe coincidir con el de la tabla en list.php
    processing: true,
    serverSide: false, // Los datos ya están en el HTML
    language: {
        url: '<?= $this->asset('/js/i18n/es-ES.json') ?>'
    },
    // No se necesitan las secciones 'columns' ni 'data' porque DataTables leerá el HTML.
    // Usamos 'drawCallback' para asegurarnos de que el código se ejecuta cada vez que la tabla se redibuja (paginación, búsqueda, etc.)
    drawCallback: function(settings) {
        // Eliminar cualquier manejador de eventos 'submit' anterior para evitar duplicados.
        $('.delete-form').off('submit');

        // Añadir el nuevo manejador de eventos 'submit' a todos los formularios con la clase 'delete-form'.
        // Esto funcionará para los botones de borrado que acabamos de añadir en el HTML.
        $('.delete-form').on('submit', function(e) {
            var message = $(this).data('confirm-message');
            return confirm(message); // Muestra el diálogo de confirmación. Si el usuario cancela, devuelve false y detiene el envío.
        });
    }
});