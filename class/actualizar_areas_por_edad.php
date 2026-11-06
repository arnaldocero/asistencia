<?php
require_once __DIR__ . '/verificar_sesion_api.php'; // Solo usuarios con sesión iniciada
include('../ajax/db_connect.php');
require_once __DIR__ . '/../ajax/areas.php';

// Recalcula el área de todos los niños según su edad actual (rangos en tbl_areas)
$sql = "SELECT id_nino, fecha_nacimiento, idarea FROM test_ninos WHERE activo = 1";
$result = $conn->query($sql);

$updated = 0;
$omitidos = 0;
$stmtUpdate = $conn->prepare("UPDATE test_ninos SET idarea = ? WHERE id_nino = ?");

while ($row = $result->fetch_assoc()) {
    $idarea = obtener_area_por_edad($conn, calcular_edad($row['fecha_nacimiento']));

    if ($idarea === null) {
        $omitidos++; // Fecha inválida o edad fuera de rango: se deja como está
        continue;
    }

    // Actualizar solo si el área es diferente
    if ((int)$row['idarea'] !== $idarea) {
        $id_nino = (int)$row['id_nino'];
        $stmtUpdate->bind_param("ii", $idarea, $id_nino);
        $stmtUpdate->execute();
        $updated++;
    }
}
$stmtUpdate->close();

echo json_encode([
    'status' => 'success',
    'message' => "Áreas actualizadas para $updated niños. Sin área válida (fecha inválida o fuera de rango): $omitidos."
]);
