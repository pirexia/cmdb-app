// app/Views/partials/users_form_scripts.php
// Script para la lógica dinámica del formulario de creación/edición de usuarios

$(document).ready(function() {
    var sourceSelect = $('#id_fuente_usuario');
    var passwordFieldsDiv = $('#password_fields');
    var passwordInput = $('#password');
    var confirmPasswordInput = $('#confirm_password');
    var usernameInput = $('#username');
    var emailInput = $('#email');
    var usernameHint = $('#username_hint'); // Nuevo
    var emailHint = $('#email_hint');     // Nuevo

    // Guarda el valor original de la fuente (solo en edición)
    var originalSourceId = sourceSelect.val();

    // Función para mostrar/ocultar los campos y ajustar requerimientos/habilitación
    function toggleUserFieldsBasedOnSource() {
        var selectedSourceId = sourceSelect.val();
        var isNewUser = <?= json_encode(isset($user) ? false : true) ?>;
        
        // Asumiendo que la fuente "Local" tiene ID 1 en la base de datos
        if (selectedSourceId == 1) { // Usuario local
            passwordFieldsDiv.show();
            usernameInput.prop('disabled', false).prop('required', true);
            emailInput.prop('disabled', false).prop('required', true);
            usernameHint.addClass('d-none'); // Ocultar hint
            emailHint.addClass('d-none');   // Ocultar hint

            // Contraseña solo requerida para LOCAL en creación
            if (isNewUser) {
                passwordInput.prop('required', true);
                confirmPasswordInput.prop('required', true);
            } else {
                passwordInput.prop('required', false);
                confirmPasswordInput.prop('required', false);
            }
        } else { // Usuario de fuente externa (LDAP/AD)
            passwordFieldsDiv.hide(); // Ocultar campos de contraseña
            passwordInput.prop('required', false).val(''); // Limpiar y quitar requerido
            confirmPasswordInput.prop('required', false).val(''); // Limpiar y quitar requerido

            // Username y Email para usuarios externos:
            // Username siempre editable (para buscar en el directorio), y requerido
            usernameInput.prop('disabled', false).prop('required', true); 
            usernameHint.removeClass('d-none'); // Mostrar hint para username externo

            // Email deshabilitado y no requerido (viene del directorio)
            emailInput.prop('disabled', true).prop('required', false);
            emailHint.removeClass('d-none');   // Mostrar hint para email externo

            // Limpiar email si cambiamos de local a externo, y no estamos en edición de un usuario existente externo.
            // Si es un usuario existente externo, su email original debe persistir para visualización.
            if (isNewUser || (selectedSourceId !== originalSourceId)) { // Si es nuevo usuario O si se cambia la fuente
                 emailInput.val(''); // Limpiar el campo email al cambiar la fuente a externa o en nueva creación
            }
        }
    }

    // Inicializar el estado al cargar la página
    toggleUserFieldsBasedOnSource();

    // Actualizar el estado cuando la fuente de usuario cambie
    sourceSelect.change(function() {
        toggleUserFieldsBasedOnSource();
    });
});
