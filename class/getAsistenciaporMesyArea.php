<?php
require_once __DIR__ . '/verificar_sesion_api.php'; // Solo usuarios con sesión iniciada
// Habilitar la visualización de errores (solo para depuración; deshabilitar en producción)
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

// Establecer encabezado JSON
header('Content-Type: application/json');

// Incluir el archivo de conexión a la base de datos
include('../ajax/db_connect.php'); // Asegúrate de que la ruta es correcta

// Verificar la conexión
if ($conn->connect_error) {
    echo json_encode(['status' => 'error', 'message' => 'Conexión fallida.' . registrar_error_bd($conn->connect_error)]);
    exit(); // Finaliza el script si hay un error de conexión
}

// Recibir el área desde la URL (GET)
$id_area = isset($_GET['id_area']) ? intval($_GET['id_area']) : 1; // Si no se recibe, por defecto es 1

// Consulta SQL con el filtro de área
$query = "SELECT 
    DATE_FORMAT(a.fecha_asistencia, '%Y-%m') AS mes,
    a.id_area,
    COUNT(a.id_nino) AS total_ninos,
    t.nombre_area
FROM asistencia a
INNER JOIN tbl_areas t ON a.id_area = t.id_area
WHERE a.id_area = ? 
GROUP BY mes, a.id_area
ORDER BY mes, a.id_area;";

// Preparar la consulta
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $id_area);
$stmt->execute();
$result = $stmt->get_result();

$data = [];

while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

// Devolver los datos en formato JSON
echo json_encode($data);

// Cerrar la conexión
$conn->close();
?>
