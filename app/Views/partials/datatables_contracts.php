// app/Views/partials/datatables_assets.php
// Script para inicializar DataTables en la vista de activos

$(document).ready(function() {
    // Inicializar DataTables con filtros de columna, selección de columnas y botones de exportación
    var table = $('#contractsTable').DataTable({
        language: {
            url: '/static/js/i18n/es-ES.json' // Idioma español
        },
        // Habilitar la exportación (buttons) y la reordenación de columnas (colReorder)
        dom: 'Blfrtip', // B: Buttons, l: length changing input, f: filtering input, r: processing display, t: the table, i: table information summary, p: pagination control
        buttons: [
            'excelHtml5',
            'csvHtml5',
            {
                extend: 'pdfHtml5',
                orientation: 'landscape', // Orientación horizontal para PDF
                pageSize: 'A4'
            },
            'colvis' // Botón para selección de columnas
        ],
        colReorder: true, // Habilitar arrastrar y soltar columnas
        
        // Configuración de filtros de columna
        initComplete: function () {
            this.api().columns().every(function () {
                var column = this;
                var header = $(column.header());
                
                // === FILTROS DE BURBUJA / SELECTORES ===
                // Para columnas con un número limitado de opciones (ej. Tipo, Fabricante, Estado)
                if (header.hasClass('filterable-select')) { // Añade la clase 'filterable-select' al <th>
                    var select = $('<select class="form-select form-select-sm"><option value="">Todos</option></select>')
                        .appendTo(header) // Añadir el select al encabezado de la columna
                        .on('change', function () {
                            var val = $.fn.dataTable.util.escapeRegex(
                                $(this).val()
                            );
                            column
                                .search(val ? '^' + val + '$' : '', true, false)
                                .draw();
                        });

                    column.data().unique().sort().each(function (d, j) {
                        select.append('<option value="' + d + '">' + d + '</option>');
                    });
                } 
                // === FILTROS DE TEXTO ===
                else if (header.hasClass('filterable-text')) { // Añade la clase 'filterable-text' al <th>
                    var input = $('<input type="text" class="form-control form-control-sm" placeholder="Buscar..." />')
                        .appendTo(header)
                        .on('keyup change clear', function () {
                            if (column.search() !== this.value) {
                                column
                                    .search(this.value)
                                    .draw();
                            }
                        });
                }
            });
        }
    });
});
