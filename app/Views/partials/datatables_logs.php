// app/Views/partials/datatables_logs.php
// Script para inicializar DataTables en la vista de logs de auditoría.

$(document).ready(function() {
    var table = $('#logsTable').DataTable({
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
        order: [[0, 'desc']], // Ordenar por la primera columna (ID) en orden descendente.
        initComplete: function () {
            this.api().columns().every(function () {
                var column = this;
                var header = $(column.header());
                if (header.hasClass('filterable-select')) {
                    var select = $('<select class="form-select form-select-sm"><option value="">' + (column.index() === 0 ? 'ID' : 'Todos') + '</option></select>')
                        .appendTo(header)
                        .on('change', function () {
                            var val = $.fn.dataTable.util.escapeRegex($(this).val());
                            column.search(val ? '^' + val + '$' : '', true, false).draw();
                        });
                    column.data().unique().sort().each(function (d, j) {
                        select.append('<option value="' + d + '">' + d + '</option>');
                    });
                } else if (header.hasClass('filterable-text')) {
                    var input = $('<input type="text" class="form-control form-control-sm" placeholder="Buscar..." />')
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

    // Lógica para mostrar los detalles del log en el modal.
    $('#logDetailsModal').on('show.bs.modal', function (event) {
      var button = $(event.relatedTarget); // Botón que disparó el modal.
      var modalBody = $(this).find('.modal-body #logDetailsContent');
      
      var logId = button.data('log-id');
      var logDate = button.data('log-date');
      var logAction = button.data('log-action');
      var logUser = button.data('log-user');
      var logAsset = button.data('log-asset');
      var oldData = button.data('log-old-data');
      var newData = button.data('log-new-data');
      
      // Formatear los datos para una mejor visualización en el modal.
      var formattedDetails = "ID: " + logId + "\n" +
                             "Fecha: " + logDate + "\n" +
                             "Acción: " + logAction + "\n" +
                             "Usuario: " + logUser + "\n" +
                             "Activo: " + logAsset + "\n" +
                             "Datos Antiguos: " + (oldData ? JSON.stringify(oldData, null, 2) : "N/A") + "\n" +
                             "Datos Nuevos: " + (newData ? JSON.stringify(newData, null, 2) : "N/A");

      modalBody.text(formattedDetails); // Muestra los detalles formateados.
    });
});
