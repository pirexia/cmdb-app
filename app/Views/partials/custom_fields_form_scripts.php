// app/Views/partials/custom_fields_form_scripts.php
// Script para el formulario de creación/edición de campos personalizados

$(document).ready(function() {
    function toggleOpcionesLista() {
        if ($('#tipo_dato').val() === 'lista') {
            $('#opciones_lista_div').show();
        } else {
            $('#opciones_lista_div').hide();
            $('#opciones_lista').val(''); // Limpiar si se oculta
        }
    }

    function toggleUnidad() {
        if ($('#tipo_dato').val() === 'numero') {
            $('#unidad_div').show();
        } else {
            $('#unidad_div').hide();
            $('#unidad').val(''); // Limpiar si se oculta
        }
    }

    $('#tipo_dato').change(function() {
        toggleOpcionesLista();
        toggleUnidad();
    });

    // Ejecutar las funciones al cargar la página para inicializar correctamente
    toggleOpcionesLista();
    toggleUnidad();       
});
