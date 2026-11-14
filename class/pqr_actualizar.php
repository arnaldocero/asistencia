<?php
require_once __DIR__ . '/verificar_sesion_api.php'; // Solo usuarios con sesión iniciada
requerir_rol('admin'); // Solo el administrador cambia el estado de una PQR
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

$id_pqr    = (int)($_POST['id_pqr'] ?? 0);
$estado    = $_POST['estado'] ?? '';
$respuesta = trim($_POST['respuesta'] ?? '');

if (!in_array($estado, ['pendiente', 'en_proceso', 'resuelta'], true)) {
    responder('error', 'Estado no válido');
}
if ($estado === 'resuelta' && mb_strlen($respuesta) < 5) {
    responder('error', 'Para marcarla como resuelta escribe la respuesta o solución');
}

$respuesta = $respuesta !== '' ? $respuesta : null;
$id_usuario = (int)$_SESSION['usuario_id'];
$fecha = date('Y-m-d H:i:s');

$stmt = $conn->prepare("UPDATE pqr SET estado = ?, respuesta = ?, id_usuario_actualiza = ?, fecha_actualizacion = ?
                        WHERE id_pqr = ?");
$stmt->bind_param("ssisi", $estado, $respuesta, $id_usuario, $fecha, $id_pqr);

if (!$stmt->execute()) {
    responder('error', 'Error al actualizar la PQR.' . registrar_error_bd($stmt->error));
}
if ($stmt->affected_rows === 0) {
    // affected_rows es 0 también si no cambió nada; distinguir "no existe"
    $existe = $conn->query("SELECT 1 FROM pqr WHERE id_pqr = " . $id_pqr)->num_rows > 0;
    if (!$existe) {
        responder('error', 'La PQR no existe');
    }
}
$stmt->close();
$conn->close();

$nombres = ['pendiente' => 'Pendiente', 'en_proceso' => 'En proceso', 'resuelta' => 'Resuelta'];
responder('success', 'PQR #' . $id_pqr . ' actualizada: ' . $nombres[$estado]);
