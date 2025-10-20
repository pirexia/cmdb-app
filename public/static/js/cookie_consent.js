/**
 * public/js/cookie_consent.js
 *
 * Lógica para el banner de consentimiento de cookies.
 */
$(document).ready(function() {
    const banner = $('#cookie-consent-banner');

    // Si el banner no existe en la página, no hacer nada.
    if (banner.length === 0) {
        return;
    }

    function setCookie(name, value, days) {
        let expires = "";
        if (days) {
            const date = new Date();
            date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
            expires = "; expires=" + date.toUTCString();
        }
        document.cookie = name + "=" + (value || "") + expires + "; path=/; SameSite=Lax";
    }

    function deleteAllCookies() {
        const cookies = document.cookie.split(";");
        for (let i = 0; i < cookies.length; i++) {
            const cookie = cookies[i];
            const eqPos = cookie.indexOf("=");
            const name = eqPos > -1 ? cookie.substr(0, eqPos) : cookie;
            // No borrar la cookie de consentimiento que estamos a punto de establecer, ni la de sesión.
            if (name.trim() !== 'cookie_consent_status' && name.trim() !== 'CMDBAppSession') {
                document.cookie = name.trim() + "=;expires=Thu, 01 Jan 1970 00:00:00 GMT;path=/";
            }
        }
    }

    $('#accept-cookies-btn').on('click', function() {
        setCookie('cookie_consent_status', 'accepted', 365);
        banner.fadeOut('slow', () => banner.remove());
    });

    $('#reject-cookies-btn').on('click', function() {
        // Borrar cookies existentes (excepto las estrictamente necesarias)
        deleteAllCookies();
        // Establecer la cookie de rechazo para no volver a preguntar
        setCookie('cookie_consent_status', 'rejected', 365);
        banner.fadeOut('slow', () => banner.remove());
    });
});