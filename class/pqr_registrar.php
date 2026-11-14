<?php
require_once __DIR__ . '/verificar_sesion_api.php'; // Cualquier usuario con sesión (recepción o admin)
header('Content-Type: application/json; charset=utf-8');

include('../ajax/db_connect.php');

function responder($status, $message, $extra = [])
{
    echo json_encode(array_merge(['status' => $status, 'message' => $message], $extra));
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responder('error', 'Método no permitido');
}

$tipo        = $_POST['tipo'] ?? '';
$nombre      = trim($_POST['nombre_solicitante'] ?? '');
$telefono    = trim($_POST['telefono'] ?? '');
$id_nino     = (int)($_POST['id_nino'] ?? 0);
$descripcion = trim($_POST['descripcion'] ?? '');

if (!in_array($tipo, ['peticion', 'queja', 'reclamo', 'sugerencia'], true)) {
    responder('error', 'Selecciona el tipo de PQR');
}
if (mb_strlen($nombre) < 3 || mb_strlen($nombre) > 150) {
    responder('error', 'Escribe el nombre de quien presenta la PQR (3 a 150 caracteres)');
}
if (mb_strlen($telefono) > 30) {
    responder('error', 'El teléfono es demasiado largo');
}
if (mb_strlen($descripcion) < 10) {
    responder('error', 'Describe la PQR (mínimo 10 caracteres)');
}

// El niño es opcional; si viene, debe existir y estar activo
$id_nino = $id_nino > 0 ? $id_nino : null;
if ($id_nino !== null) {
    $stmt = $conn->prepare("SELECT id_nino FROM test_ninos WHERE id_nino = ? AND activo = 1");
    $stmt->bind_param("i", $id_nino);
    $stmt->execute();
    if (!$stmt->get_result()->fetch_assoc()) {
        responder('error', 'El niño seleccionado no existe');
    }
    $stmt->close();
}

$telefono = $telefono !== '' ? $telefono : null;
$id_usuario = (int)$_SESSION['usuario_id'];
$fecha = date('Y-m-d H:i:s');

$stmt = $conn->prepare("INSERT INTO pqr (tipo, nombre_solicitante, telefono, id_nino, descripcion, id_usuario_registro, fecha_registro)
                        VALUES (?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param("sssisis", $tipo, $nombre, $telefono, $id_nino, $descripcion, $id_usuario, $fecha);

if (!$stmt->execute()) {
    responder('error', 'Error al registrar la PQR.' . registrar_error_bd($stmt->error));
}
$id_pqr = $stmt->insert_id;
$stmt->close();
$conn->close();

responder('success', 'PQR #' . $id_pqr . ' registrada. Queda pendiente para el administrador.', ['id_pqr' => $id_pqr]);
