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
?>