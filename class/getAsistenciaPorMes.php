<?php
// Habilitar la visualización de errores (solo para depuración; deshabilitar en producción)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Establecer encabezado JSON
header('Content-Type: application/json');

// Incluir el archivo de conexión a la base de datos
include('../ajax/db_connect.php'); // Asegúrate de que la ruta es correcta

// Verificar la conexión
if ($conn->connect_error) {
    echo json_encode(['status' => 'error', 'message' => 'Conexión fallida: ' . $conn->connect_error]);
    exit(); // Finaliza el script si hay un error de conexión
}

$query = "SELECT 
    DATE_FORMAT(a.fecha_asistencia, '%Y-%m') AS mes,
    a.id_area,
    COUNT(a.id_nino) AS total_ninos,
    t.nombre_area
FROM asistencia a
INNER JOIN tbl_areas t ON a.id_area = t.id_area
GROUP BY mes, a.id_area
ORDER BY mes, a.id_area";

$result = $conn->query($query);

$data = [];

while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

echo json_encode($data);

$conn->close();
?>