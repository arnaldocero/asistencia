<?php
require_once __DIR__ . '/verificar_sesion_api.php'; // Solo usuarios con sesión iniciada
requerir_rol('admin'); // Solo administradores gestionan usuarios
header('Content-Type: application/json; charset=utf-8');

include('../ajax/db_connect.php');

function responder($status, $message)
{
    echo json_encode(['status' => $status, 'message' => $message]);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responder('error', 'Método no permitido');
}

$id_usuario = (int)($_POST['id_usuario'] ?? 0);
$accion     = $_POST['accion'] ?? '';
$es_yo      = $id_usuario === (int)$_SESSION['usuario_id'];

$stmt = $conn->prepare("SELECT id_usuario, rol, activo FROM usuarios WHERE id_usuario = ?");
$stmt->bind_param("i", $id_usuario);
$stmt->execute();
$usuario = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$usuario) {
    responder('error', 'El usuario no existe');
}

// ¿Quedaría el sistema sin ningún administrador activo?
function es_ultimo_admin_activo($conn, $usuario)
{
    if ($usuario['rol'] !== 'admin' || (int)$usuario['activo'] !== 1) {
        return false;
    }
    $row = $conn->query("SELECT COUNT(*) AS total FROM usuarios WHERE rol = 'admin' AND activo = 1")->fetch_assoc();
    return (int)$row['total'] <= 1;
}

switch ($accion) {
    case 'rol':
        $rol = $_POST['rol'] ?? '';
        if (!in_array($rol, ['usuario', 'admin'], true)) {
            responder('error', 'Rol no válido');
        }
        if ($rol === 'usuario' && $es_yo) {
            responder('error', 'No puedes quitarte a ti mismo el rol de administrador');
        }
        if ($rol === 'usuario' && es_ultimo_admin_activo($conn, $usuario)) {
            responder('error', 'Debe quedar al menos un administrador activo');
        }
        $stmt = $conn->prepare("UPDATE usuarios SET rol = ? WHERE id_usuario = ?");
        $stmt->bind_param("si", $rol, $id_usuario);
        $mensaje = $rol === 'admin' ? 'Ahora es administrador' : 'Ahora es usuario de recepción';
        break;

    case 'contrasena':
        $contrasena = $_POST['contrasena'] ?? '';
        if (strlen($contrasena) < 8) {
            responder('error', 'La contraseña debe tener al menos 8 caracteres');
        }
        if ($contrasena !== ($_POST['confirmar'] ?? '')) {
            responder('error', 'Las contraseñas no coinciden');
        }
        $hash = password_hash($contrasena, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE usuarios SET contrasena = ? WHERE id_usuario = ?");
        $stmt->bind_param("si", $hash, $id_usuario);
        $mensaje = 'Contraseña actualizada';
        break;

    case 'activo':
        $activo = (int)($_POST['activo'] ?? -1);
        if ($activo !== 0 && $activo !== 1) {
            responder('error', 'Valor no válido');
        }
        if ($activo === 0 && $es_yo) {
            responder('error', 'No puedes desactivar tu propio usuario');
        }
        if ($activo === 0 && es_ultimo_admin_activo($conn, $usuario)) {
            responder('error', 'Debe quedar al menos un administrador activo');
        }
        $stmt = $conn->prepare("UPDATE usuarios SET activo = ? WHERE id_usuario = ?");
        $stmt->bind_param("ii", $activo, $id_usuario);
        $mensaje = $activo === 1 ? 'Usuario activado' : 'Usuario desactivado: ya no puede iniciar sesión';
        break;

    default:
        responder('error', 'Acción no válida');
}

if (!$stmt->execute()) {
    responder('error', 'Error al actualizar el usuario.' . registrar_error_bd($stmt->error));
}
$stmt->close();
$conn->close();
responder('success', $mensaje);
