<?php
require_once __DIR__ . '/verificar_sesion_api.php'; // Solo usuarios con sesión iniciada
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);
header('Content-Type: application/json');
date_default_timezone_set('America/Bogota');
include('../ajax/db_connect.php');

$fecha = isset($_GET['fecha']) ? $_GET['fecha'] : date('Y-m-d');
$turno = isset($_GET['turno']) ? $_GET['turno'] : 'mañana';

// Definir rangos horarios según el turno
switch ($turno) {
    case 'mañana':
        $hora_inicio = '08:30:00';
        $hora_fin = '10:40:00';
        break;
    case 'mediodia':
        $hora_inicio = '10:41:00';
        $hora_fin = '13:00:00';
        break;
    case 'tarde':
        $hora_inicio = '15:30:00';
        $hora_fin = '18:00:00';
        break;
    default:
        echo json_encode([]);
        exit;
}

// Consulta para obtener cantidad por área
$sql = "
    SELECT 
        ar.nombre_area,
        COUNT(*) AS cantidad
    FROM asistencia a
    INNER JOIN tbl_areas ar ON ar.id_area = a.id_area
    WHERE 
        a.fecha_asistencia = ?
        AND a.hora_entrada BETWEEN ? AND ?
    GROUP BY a.id_area
    LIMIT 4
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("sss", $fecha, $hora_inicio, $hora_fin);
$stmt->execute();
$result = $stmt->get_result();

$data = [];
$total_general = 0;

while ($row = $result->fetch_assoc()) {
    $data[] = $row;
    $total_general += $row['cantidad'];
}

// Agregar fila final con total
$data[] = [
    'nombre_area' => 'TOTAL',
    'cantidad' => $total_general
];

echo json_encode($data);
?>
