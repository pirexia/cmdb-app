// app/Views/partials/datatables_users.php
// Script para inicializar DataTables en la vista de usuarios

$(document).ready(function() {
    var table = $('#usersTable').DataTable({ // <-- ID de la tabla
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

            api.columns().every(function () {
                var column = this;
                var header = $(column.header());
                
                if (header.hasClass('filterable-select')) {
                    var select = $('<select class="form-select form-select-sm"><option value="">' + (column.index() === 0 ? api.i18n('id') : api.i18n('datatable_all')) + '</option></select>')
                        .appendTo(header)
                        .on('change', function () {
                            var val = $.fn.dataTable.util.escapeRegex($(this).val());
                            column.search(val ? '^' + val + '$' : '', true, false).draw();
                        });
                    column.data().unique().sort().each(function (d, j) {
                        select.append('<option value="' + d + '">' + d + '</option>');
                    });
                } else if (header.hasClass('filterable-text')) {
                    var input = $('<input type="text" class="form-control form-control-sm" placeholder="' + (column.index() === 0 ? api.i18n('datatable_search_id') : api.i18n('datatable_search_ellipsis')) + '" />')
                        .appendTo(header)
                        .on('keyup change clear', function () {
                            if (column.search() !== this.value) {
                                column.search(this.value).draw();
                            }
                        });
                }
            });
        }
    });
});
