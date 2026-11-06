<?php
// verificar_sesion_api.php
// Versión para endpoints AJAX de verificar_sesion.php: en vez de un alert/redirección
// responde JSON con código HTTP 401 (sin sesión) o 403 (sin permiso).
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Tiempo máximo de inactividad (4 horas = 14400 segundos), igual que verificar_sesion.php
$tiempo_maximo = 14400;

function responder_sin_acceso($codigo, $mensaje)
{
    http_response_code($codigo);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'status' => 'error',
        'message' => $mensaje,
        'mensaje' => $mensaje,
        'sesion_expirada' => $codigo === 401
    ]);
    exit();
}

if (empty($_SESSION['usuario_id'])) {
    responder_sin_acceso(401, 'Tu sesión no está activa. Inicia sesión nuevamente.');
}

if (isset($_SESSION['ultimo_acceso']) && time() - $_SESSION['ultimo_acceso'] > $tiempo_maximo) {
    session_unset();
    session_destroy();
    responder_sin_acceso(401, 'Tu sesión ha expirado por inactividad.');
}

$_SESSION['ultimo_acceso'] = time();

// Protección CSRF: toda petición que modifica datos (POST) debe traer el token de la sesión.
// Lo envía automáticamente dist/js/sesion.js en la cabecera X-CSRF-Token.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token_enviado = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token_enviado)) {
        responder_sin_acceso(403, 'La solicitud no es válida. Recarga la página e intenta de nuevo.');
    }
}

// Liberar el bloqueo del archivo de sesión: así las peticiones simultáneas
// (por ejemplo la sincronización cada 4 s) no se esperan unas a otras.
session_write_close();

// Para endpoints restringidos: requerir_rol('admin');
function requerir_rol($rol)
{
    if (($_SESSION['usuario_rol'] ?? '') !== $rol) {
        responder_sin_acceso(403, 'No tienes permiso para realizar esta acción.');
    }
}
