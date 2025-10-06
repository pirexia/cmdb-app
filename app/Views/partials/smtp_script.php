<?php
/**
 * app/Views/partials/smtp_script.php
 *
 * Script para la lógica de la prueba de conexión SMTP.
 * Se incluye dinámicamente desde el layout base.
 */
?>

const testBtn = document.getElementById('test-smtp-btn');
const resultsDiv = document.getElementById('test-results');
const form = testBtn ? testBtn.closest('form') : null;

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