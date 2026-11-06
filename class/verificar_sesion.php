<?php
// verificar_sesion.php
session_start();

// Tiempo máximo de inactividad (4 horas = 14400 segundos)
$tiempo_maximo = 14400;

// Verificar si el usuario está autenticado
if (empty($_SESSION['usuario_id'])) {
    echo "<script>
        alert('⚠️ Tu sesión no está activa. Inicia sesión nuevamente.');
        window.location.href = 'authentication-login.php';
    </script>";
    exit();
}

// Verificar tiempo de inactividad
if (isset($_SESSION['ultimo_acceso'])) {
    $inactividad = time() - $_SESSION['ultimo_acceso'];
    if ($inactividad > $tiempo_maximo) {
        session_unset();     // Limpia variables de sesión
        session_destroy();   // Destruye la sesión
        echo "<script>
            alert('⏰ Tu sesión ha expirado por inactividad.');
            window.location.href = 'authentication-login.php';
        </script>";
        exit();
    }
}

// Actualizar tiempo del último acceso
$_SESSION['ultimo_acceso'] = time();

// Token CSRF: uno por sesión. Se entrega en una cookie que lee dist/js/sesion.js
// y lo reenvía en la cabecera X-CSRF-Token de cada POST (ver verificar_sesion_api.php).
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
setcookie('XSRF-TOKEN', $_SESSION['csrf_token'], [
    'path' => '/',
    'samesite' => 'Strict',
    'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    'httponly' => false // JavaScript debe poder leerla
]);
?>