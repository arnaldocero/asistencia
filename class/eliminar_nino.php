<?php
require_once __DIR__ . '/verificar_sesion_api.php'; // Solo usuarios con sesión iniciada
requerir_rol('admin'); // Solo administradores pueden eliminar niños
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);
header('Content-Type: application/json');

include('../ajax/db_connect.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_nino'])) {
    $id_nino = intval($_POST['id_nino']);

    // Borrado lógico: el niño deja de aparecer en listados y búsquedas,
    // pero se conserva junto con su historial de asistencia para los reportes.
    $stmt_nino = $conn->prepare("UPDATE test_ninos SET activo = 0, fecha_baja = NOW() WHERE id_nino = ? AND activo = 1");
    $stmt_nino->bind_param("i", $id_nino);

    if (!$stmt_nino->execute()) {
        echo json_encode(['status' => 'error', 'message' => 'Error al eliminar el niño.' . registrar_error_bd($stmt_nino->error)]);
    } elseif ($stmt_nino->affected_rows === 0) {
        echo json_encode(['status' => 'error', 'message' => 'El niño no existe o ya fue eliminado']);
    } else {
        echo json_encode(['status' => 'success', 'message' => 'Niño eliminado exitosamente']);
    }

    $stmt_nino->close();
    $conn->close();
} else {
    echo json_encode(['status' => 'error', 'message' => 'Error']);
}
