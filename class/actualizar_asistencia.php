<?php
require_once __DIR__ . '/verificar_sesion_api.php'; // Solo usuarios con sesión iniciada

include('../ajax/db_connect.php');  // Incluye tu archivo de conexión

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Aseguramos que id_nino y estado lleguen correctamente
    $id_nino = isset($_POST['id_nino']) ? intval($_POST['id_nino']) : null;
    $estado = isset($_POST['estado']) ? ($_POST['estado']) : null;

    date_default_timezone_set('America/Bogota');
    $fecha_sistema = date('Y-m-d');  // Fecha del sistema (hoy)

    // Registro de valores para depuración
    error_log("Valores recibidos en PHP - id_nino: " . $id_nino . ", estado: " . $estado);

    // Validación mejorada
    if (!isset($id_nino) || !isset($estado)) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'ID de niño y estado son obligatorios', 'id_nino' => $id_nino, 'estado' => $estado]);
        exit();
    }

    // Preparar la consulta de actualización
    $stmt = $conn->prepare("UPDATE asistencia SET estado = ? WHERE id_nino = ? AND fecha_asistencia = ?");
    $stmt->bind_param("sis", $estado,$id_nino, $fecha_sistema);  // El tercer parámetro es un string ya que es una fecha

    // Ejecutar la consulta y verificar el resultado
    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'success', 'message' => 'Estado actualizado correctamente','estado' => $estado]);
        } else {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => 'No se actualizó ningún registro, verifica si el id_nino y fecha_asistencia son correctos']);
        }
    } else {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Error al actualizar el estado.' . registrar_error_bd($stmt->error)]);
    }

    $stmt->close();
    $conn->close();
} else {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Método no permitido']);
}
?>
