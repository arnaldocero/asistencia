<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json');

include('../ajax/db_connect.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_nino'])) {
    $id_nino = intval($_POST['id_nino']);

    // Eliminar primero las asistencias asociadas al niño
    $stmt_asistencia = $conn->prepare("DELETE FROM asistencia WHERE id_nino = ?");
    $stmt_asistencia->bind_param("i", $id_nino);
    $stmt_asistencia->execute();
    $stmt_asistencia->close();

    // Ahora eliminar al niño
    $stmt_nino = $conn->prepare("DELETE FROM test_ninos WHERE id_nino = ?");
    $stmt_nino->bind_param("i", $id_nino);

    if ($stmt_nino->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Niño eliminado exitosamente']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Error al eliminar el niño']);
    }

    $stmt_nino->close();
    $conn->close();
} else {
    echo json_encode(['status' => 'error', 'message' => 'Error']);
}