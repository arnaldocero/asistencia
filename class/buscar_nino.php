<?php
require_once __DIR__ . '/verificar_sesion_api.php'; // Solo usuarios con sesión iniciada
date_default_timezone_set('America/Bogota');
header('Content-Type: application/json; charset=utf-8');

include('../ajax/db_connect.php');

$term = isset($_GET['term']) ? trim($_GET['term']) : '';
$fecha_hoy = date('Y-m-d');

if (strlen($term) < 2) {
    echo json_encode([]);
    exit;
}

// Para el check-in excluye a los niños que ya tienen asistencia con estado '1' hoy.
// Con ?todos=1 (formulario de PQR) busca entre todos los niños activos.
$todos = (isset($_GET['todos']) && $_GET['todos'] === '1') ? '1' : '0';

$sql = "SELECT n.id_nino, n.nombre, n.apellido, n.nombre_padre, n.idarea, a.nombre_area
        FROM test_ninos n
        LEFT JOIN tbl_areas a ON a.id_area = n.idarea
        WHERE n.activo = 1
          AND (n.nombre LIKE ? OR n.apellido LIKE ? OR CONCAT(n.nombre, ' ', n.apellido) LIKE ?)
          AND (? = '1' OR n.id_nino NOT IN (
              SELECT id_nino
              FROM asistencia
              WHERE fecha_asistencia = ? AND estado = '1'
          ))";

$stmt = $conn->prepare($sql);
$searchTerm = "%" . $term . "%";
$stmt->bind_param("sssss", $searchTerm, $searchTerm, $searchTerm, $todos, $fecha_hoy);
$stmt->execute();
$result = $stmt->get_result();

$children = [];

while ($row = $result->fetch_assoc()) {
    // Nombre del área desde tbl_areas (misma fuente que los rangos de edad)
    $nombre_area = $row['nombre_area'] ?? 'Área desconocida';

    $nombreCompleto = $row['nombre'] . ' ' . $row['apellido'] . ' - ' . $nombre_area;

    $children[] = [
        'id' => $row['id_nino'],
        'label' => $nombreCompleto,
        'nombre' => $row['nombre'],
        'apellido' => $row['apellido'],
        'nombre_padre' => $row['nombre_padre'],
        'id_area' => $row['idarea']
    ];
}

$stmt->close();
$conn->close();

echo json_encode($children);