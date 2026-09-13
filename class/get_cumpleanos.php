<?php
header('Content-Type: application/json');
include('../ajax/db_connect.php');

// Año actual
$anio_actual = date('Y');

$sql = "
    SELECT 
        CONCAT(nombre, ' ', apellido) AS nombre_completo,
        DATE(CONCAT(?, '-', LPAD(MONTH(fecha_nacimiento), 2, '0'), '-', LPAD(DAY(fecha_nacimiento), 2, '0'))) AS cumple_este_anio
    FROM test_ninos
    ORDER BY MONTH(fecha_nacimiento), DAY(fecha_nacimiento)
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $anio_actual);
$stmt->execute();
$result = $stmt->get_result();

$eventos = [];
while ($row = $result->fetch_assoc()) {
    $eventos[] = [
        'title' => $row['nombre_completo'],
        'start' => $row['cumple_este_anio'],
        'color' => '#ff4d4d' // rojo
    ];
}

echo json_encode($eventos);
