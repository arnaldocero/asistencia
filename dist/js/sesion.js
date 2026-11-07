// Manejo global de sesión para todas las llamadas AJAX de jQuery.
// Los endpoints de class/ responden 401 si la sesión no está activa y 403 si falta permiso.
(function ($) {
    var redirigiendo = false;

    // Protección CSRF: envía el token de la cookie XSRF-TOKEN en cada petición que no sea GET
    function leerCookie(nombre) {
        var m = document.cookie.match(new RegExp('(?:^|; )' + nombre + '=([^;]*)'));
        return m ? decodeURIComponent(m[1]) : '';
    }
    $.ajaxSetup({
        beforeSend: function (xhr, settings) {
            if (!/^(GET|HEAD|OPTIONS)$/i.test(settings.type)) {
                xhr.setRequestHeader('X-CSRF-Token', leerCookie('XSRF-TOKEN'));
            }
        }
    });

    $(document).ajaxError(function (event, xhr) {
        var mensaje = (xhr.responseJSON && (xhr.responseJSON.mensaje || xhr.responseJSON.message)) || '';

        if (xhr.status === 401 && !redirigiendo) {
            // Evita un alert por cada petición (p. ej. la sincronización cada 4 s)
            redirigiendo = true;
            alert(mensaje || 'Tu sesión no está activa. Inicia sesión nuevamente.');
            window.location.href = 'authentication-login.php';
        } else if (xhr.status === 403) {
            alert(mensaje || 'No tienes permiso para realizar esta acción.');
        }
    });

    // Botón "Logout" del menú superior (todas las páginas)
    $(document).on('click', '#logout-btn', function () {
        $.ajax({
            url: 'class/logout.php',
            method: 'POST',
            complete: function () {
                window.location.href = 'authentication-login.php';
            }
        });
    });
})(jQuery);

// Escapa texto antes de insertarlo como HTML (tablas, mensajes) para evitar XSS:
// un nombre como <script>...</script> se muestra como texto y no se ejecuta.
function escaparHtml(valor) {
    if (valor === null || valor === undefined) return '';
    return String(valor)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

// Toastr muestra HTML por defecto; así sus mensajes siempre se muestran como texto.
if (typeof toastr !== 'undefined') {
    toastr.options.escapeHtml = true;
}
