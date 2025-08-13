// app/Views/partials/sources_form_scripts.php
// Script para la lógica dinámica del formulario de creación/edición de fuentes de usuario

$(document).ready(function() {
    var tipoFuenteSelect = $('#tipo_fuente');
    var ldapAdFieldsDiv = $('#ldap_ad_fields');
    var useTlsCheckbox = $('#use_tls');
    var useSslCheckbox = $('#use_ssl');
    var caCertPathInput = $('#ca_cert_path');


    // Función para mostrar/ocultar los campos de LDAP/AD
    function toggleLdapAdFields() {
        var selectedType = tipoFuenteSelect.val();
        if (selectedType === 'ldap' || selectedType === 'activedirectory') {
            ldapAdFieldsDiv.show();
            // Hacer que los campos relevantes sean requeridos si están visibles y no tienen un valor existente (ej. en nueva creación)
            // Esto es importante para la validación del formulario
            ldapAdFieldsDiv.find('input[type="text"], input[type="number"], textarea').each(function() {
                // Solo si el campo está vacío, lo hacemos requerido. Si tiene un valor, no lo forzamos.
                if ($(this).val() === '') {
                    $(this).prop('required', true);
                }
            });
        } else {
            ldapAdFieldsDiv.hide();
            // Quitar el atributo 'required' y limpiar los valores si los campos se ocultan
            ldapAdFieldsDiv.find('input[type="text"], input[type="number"], textarea').prop('required', false).val('');
            ldapAdFieldsDiv.find('input[type="checkbox"]').prop('checked', false);
            // Limpiar también el campo de bind_password si se oculta para evitar enviarlo vacío
            $('#bind_password').val('');
        }
        toggleCaCertPathField();
    }

    function toggleCaCertPathField() {
        if (useTlsCheckbox.is(':checked') || useSslCheckbox.is(':checked')) {
            caCertPathInput.prop('disabled', false).prop('required', false); // No lo hacemos requerido aún, solo editable
        } else {
            caCertPathInput.prop('disabled', true).prop('required', false).val(''); // Deshabilitar y limpiar
        }
    }

    // Inicializar el estado al cargar la página
    toggleLdapAdFields(); // Esto ya inicializa el estado de los campos LDAP/AD
    toggleCaCertPathField(); // Asegurarse que el campo CA se inicialice

    // Actualizar el estado cuando el tipo de fuente cambie
    tipoFuenteSelect.change(function() {
        toggleLdapAdFields();
    });

    // Actualizar el estado cuando los checkboxes TLS/SSL cambien
    useTlsCheckbox.change(function() {
        toggleCaCertPathField();
    });
    useSslCheckbox.change(function() {
        toggleCaCertPathField();
    });


    // Lógica para el botón "Test Conexión"
    // Este botón deberá estar en el formulario y enviará una petición AJAX
    $('.test-source-btn').on('click', function() {
        var sourceId = $(this).data('source-id');
        var isNewSource = !sourceId; 
        
        var formData = {};
        $('form :input:not(:disabled)').each(function() {
            var name = $(this).attr('name');
            var value = $(this).val();
            var type = $(this).attr('type');

            if (name) {
                if (type === 'checkbox') {
                    formData[name] = $(this).is(':checked') ? 1 : 0;
                } else if (type === 'radio') {
                    if ($(this).is(':checked')) {
                        formData[name] = value;
                    }
                } else {
                    formData[name] = value;
                }
            }
        });
        
        if (!$('#use_tls').is(':checked')) { formData['use_tls'] = 0; }
        if (!$('#use_ssl').is(':checked')) { formData['use_ssl'] = 0; }
        if (!$('#activo').is(':checked')) { formData['activo'] = 0; }


        // --- CORRECCIÓN AQUÍ: Mascarar la contraseña en el log ---
        var debugData = JSON.parse(JSON.stringify(formData)); // Crea una copia para no modificar el original
        if (debugData.bind_password) {
            debugData.bind_password = '********'; // Reemplazar la contraseña por asteriscos en la copia para el log
        }
        console.log('Datos enviados a la API para test (mascarado):', debugData); // <-- Esta es la línea de DEPURACIÓN
        // --- FIN CORRECCIÓN ---


        // Mostrar modal de carga
        var modalBody = $('#testConnectionModalBody');
        var modal = new bootstrap.Modal(document.getElementById('testConnectionModal'));
        modalBody.html('<div class="text-center"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div><p class="mt-2"><?= $t('testing_connection') ?></p></div>');
        modal.show();
        $.ajax({
            url: '/api/sources/test-connection', 
            method: 'POST',
            data: JSON.stringify(formData), // Enviar los datos originales (no enmascarados)
            contentType: 'application/json',
            success: function(response) {
                if (response.success) {
                    modalBody.html('<div class="alert alert-success">' + (response.message || '<?= $t('connection_successful') ?>') + '</div>');
                } else {
                    modalBody.html('<div class="alert alert-danger">' + (response.message || '<?= $t('connection_failed') ?>') + '</div>');
                }
            },
            error: function(xhr, status, error) {
                console.error('Error AJAX test conexión:', error, xhr.responseText);
                modalBody.html('<div class="alert alert-danger">' + ('<?= $t('connection_error_api') ?>' + (xhr.responseJSON ? xhr.responseJSON.message : xhr.responseText)) + '</div>');
            }
        });
    });
});
