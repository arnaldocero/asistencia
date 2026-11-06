<?php
require_once __DIR__ . '/verificar_sesion_api.php'; // Solo usuarios con sesión iniciada
// Habilitar la visualización de errores para depuración
ini_set('display_errors', 0);
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');
include('../ajax/db_connect.php');

if ($conn->connect_error) {
    echo json_encode(['status' => 'error', 'message' => 'Conexión fallida.' . registrar_error_bd($conn->connect_error)]);
    exit();
}

$stmt = $conn->prepare("SELECT a.id_asistencia, a.id_nino, n.nombre, n.apellido, n.nombre_padre, n.telefono, a.estado 
                        FROM asistencia a 
                        INNER JOIN test_ninos n ON a.id_nino = n.id_nino 
                        WHERE a.estado = '1' AND a.fecha_asistencia = CURDATE() 
                        ORDER BY a.hora_entrada DESC");

if (!$stmt) {
    echo json_encode(['status' => 'error', 'message' => 'Error al preparar la consulta.' . registrar_error_bd($conn->error)]);
    exit();
}

$stmt->execute();
$result = $stmt->get_result();

$datos = [];
$ingresados = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $datos[] = $row;
        $ingresados[] = (int)$row['id_nino'];
    }
}

echo json_encode([
    'status' => 'success', 
    'data' => $datos,
    'ingresados' => $ingresados
]);

$stmt->close();
$conn->close();