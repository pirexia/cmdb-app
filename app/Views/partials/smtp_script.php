// app/Views/partials/smtp_script.php
// Script para inicializar campos en el formulario de la configuración SMTP.

$(document).ready(function() {
    // Lógica para mostrar/ocultar los campos de usuario y contraseña si se requiere autenticación
    $('#auth_required').change(function() {
        if ($(this).is(':checked')) {
           $('#smtp-credentials').show();
           $('#username').prop('required', true);
           // La contraseña no se hace 'required' para permitir dejarla vacía si no se desea cambiar
       } else {
           $('#smtp-credentials').hide();
           $('#username').prop('required', false).val('');
           $('#password').val('');
        }
    });
});
