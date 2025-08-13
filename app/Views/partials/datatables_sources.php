// app/Views/partials/datatables_sources.php
// Script para inicializar DataTables en la vista de fuentes de usuario

$(document).ready(function() {
    var table = $('#sourcesTable').DataTable({ // <-- ID de la tabla
        language: {
            url: '/static/js/i18n/es-ES.json'
        },
        dom: 'Bfltip',
        buttons: [
            'excelHtml5',
            'csvHtml5',
            {
                extend: 'pdfHtml5',
                orientation: 'landscape',
                pageSize: 'A4'
            },
            'colvis'
        ],
        colReorder: true,
        initComplete: function () {
            var api = this.api();
            var filterInputContainer = $('.dataTables_filter');
            var searchLabel = filterInputContainer.find('label');
            searchLabel.contents().filter(function(){ return this.nodeType === 3; }).remove();
            searchLabel.prepend('<span class="dt-search-label">' + api.i18n('search') + ': </span>');
            filterInputContainer.find('input').attr('placeholder', api.i18n('search_ellipsis')).attr('aria-label', api.i18n('datatable_search_table'));
        }
    });
});
