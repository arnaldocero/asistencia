<?php
require_once __DIR__ . '/verificar_sesion_api.php'; // Solo usuarios con sesión iniciada
include('../ajax/db_connect.php');
require_once __DIR__ . '/../ajax/areas.php';

// Ya no es necesario llamarlo: registrar_asistenciaV2.php reinicia solo el consecutivo
// en el primer registro de cada día. Se conserva para reinicios manuales.
$valores = [1 => consecutivo_inicial(1), 2 => consecutivo_inicial(2), 3 => consecutivo_inicial(3), 4 => consecutivo_inicial(4)];

// Verifica si llegaron IDs
if (!isset($_POST['ids'])) {
    echo 'error';
    exit;
}

$ids = json_decode($_POST['ids'], true);

if (!is_array($ids) || empty($ids)) {
    echo 'error';
    exit;
}

$fechaHoy = date('Y-m-d');

foreach ($ids as $id) {
    $id = intval($id);
    if (!isset($valores[$id])) {
        continue; // ID inválido
    }

    // Verificar si la fecha_consecutivo es distinta a hoy
    $sqlCheck = "SELECT fecha_consecutivo FROM consecutivo WHERE id_area = ?";
    $stmtCheck = $conn->prepare($sqlCheck);
    $stmtCheck->bind_param("i", $id);
    $stmtCheck->execute();
    $result = $stmtCheck->get_result();
    $row = $result->fetch_assoc();

    if (!$row || $row['fecha_consecutivo'] === $fechaHoy) {
        continue; // Ya está actualizado hoy o no existe
    }

    // Actualizar con el nuevo consecutivo
    $nuevoConsecutivo = $valores[$id];
    $sqlUpdate = "UPDATE consecutivo SET consecutivo = ?, fecha_consecutivo = ? WHERE id_area = ?";
    $stmtUpdate = $conn->prepare($sqlUpdate);
    $stmtUpdate->bind_param("isi", $nuevoConsecutivo, $fechaHoy, $id);
    $stmtUpdate->execute();
}

echo 'ok';
?>