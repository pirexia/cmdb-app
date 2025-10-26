<?php
/**
 * app/Views/partials/smtp_script.php
 *
 * Script para la lógica de la prueba de conexión SMTP.
 * Se incluye dinámicamente desde el layout base.
 */
?>

<script>
const testBtn = document.getElementById('test-smtp-btn');
const resultsDiv = document.getElementById('test-results');
const form = testBtn ? testBtn.closest('form') : null;
const sendTestEmailBtn = document.getElementById('send-test-email-btn');

if (testBtn && resultsDiv && form) {
    testBtn.addEventListener('click', function() {
        // Mostrar mensaje de carga
        resultsDiv.innerHTML = `<div class="alert alert-info"><?= $t('testing_connection') ?></div>`;
        testBtn.disabled = true;

        // Recolectar los datos del formulario
        const formData = new FormData(form);
        const data = Object.fromEntries(formData.entries());

        // Asegurarse de que el checkbox 'auth_required' se envíe como 0 si no está marcado
        if (!formData.has('auth_required')) {
            data['auth_required'] = 0;
        }

        // Realizar la petición a la API
        fetch('/api/smtp/test-connection', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify(data)
        })
        .then(response => {
            if (!response.ok) {
                // Si la respuesta no es OK (ej. 400, 500), intenta leer el JSON del error.
                return response.json().then(errorData => {
                    throw new Error(errorData.message || '<?= $t('operation_failed') ?>');
                });
            }
            return response.json();
        })
        .then(result => {
            // Mostrar el resultado
            const alertClass = result.success ? 'alert-success' : 'alert-danger';
            resultsDiv.innerHTML = `<div class="alert ${alertClass}">${result.message}</div>`;
        })
        .catch(error => {
            // Mostrar error de red o un error de la API que no fue JSON
            const errorMessage = error.message || '<?= $t('operation_failed') ?>';
            resultsDiv.innerHTML = `<div class="alert alert-danger"><?= $t('connection_error_api') ?> ${errorMessage}</div>`;
            console.error('Error en la prueba de conexión SMTP:', error);
        })
        .finally(() => {
            // Reactivar el botón
            testBtn.disabled = false;
        });
    });
}

if (sendTestEmailBtn && resultsDiv) {
    sendTestEmailBtn.addEventListener('click', function() {
        // Mostrar mensaje de carga
        resultsDiv.innerHTML = `<div class="alert alert-info"><?= $t('sending_test_email_start') ?? 'Iniciando envío de correo de prueba...' ?></div>`;
        sendTestEmailBtn.disabled = true;
        console.log('[DEBUG] Botón "Enviar Correo de Prueba" pulsado. Deshabilitado.');
        console.log('[DEBUG] Realizando fetch a /api/test-email...');

        // Realizar la petición a la API que envía el correo de prueba
        fetch('/api/test-email', { // La ruta ya existe
            method: 'GET', // El endpoint es GET
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => {
            console.log('[DEBUG] Respuesta de la API recibida. Estado:', response.status);
            if (!response.ok) {
                // Si la respuesta no es OK (ej. 500), intenta leer el JSON del error.
                // Ahora podemos confiar en que la respuesta será JSON.
                return response.json().then(errorData => {
                    console.error('[DEBUG] Datos del error (JSON):', errorData);
                    throw new Error(errorData.error || errorData.message || '<?= $t('operation_failed') ?>');
                });
            }
            console.log('[DEBUG] La respuesta fue OK. Procesando JSON...');
            return response.json();
        })
        .then(result => {
            console.log('[DEBUG] Resultado del JSON procesado:', result);
            // Mostrar el resultado
            const alertClass = result.success ? 'alert-success' : 'alert-danger';
            // El mensaje puede contener HTML (como la traza de depuración), por lo que usamos innerHTML.
            let message = result.message;
            if (result.error) { // El backend devuelve 'error' en caso de fallo
                message = result.error;
            }
            resultsDiv.innerHTML = `<div class="alert ${alertClass}">${message}</div>`;
        })
        .catch(error => {
            console.error('[DEBUG] Error capturado en el bloque .catch():', error);
            const errorMessage = error.message || '<?= $t('operation_failed') ?>';
            // La clave 'sending_test_email_error' ya incluye el prefijo de error.
            resultsDiv.innerHTML = `<div class="alert alert-danger"><?= $t('sending_test_email_error') ?? 'Error al enviar correo de prueba:' ?> ${errorMessage}</div>`;
            console.error('Error en el envío de correo de prueba:', error);
        })
        .finally(() => {
            console.log('[DEBUG] Bloque .finally() ejecutado. Reactivando botón.');
            sendTestEmailBtn.disabled = false;
        });
    });
}
</script>