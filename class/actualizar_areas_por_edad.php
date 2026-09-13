<?php
include('../ajax/db_connect.php');

// Obtener todos los niños
$sql = "SELECT id_nino, fecha_nacimiento FROM test_ninos";
$result = $conn->query($sql);

$updated = 0;

while ($row = $result->fetch_assoc()) {
    $id_nino = $row['id_nino'];
    $fecha_nacimiento = $row['fecha_nacimiento'];

    // Calcular edad
    $edad = date_diff(date_create($fecha_nacimiento), date_create('today'))->y;

    // Determinar id_area según la edad
    if ($edad >= 3 && $edad <= 4) {
        $idarea = 1;
    } elseif ($edad >= 5 && $edad <= 6) {
        $idarea = 2;
    } elseif ($edad >= 7 && $edad <= 8) {
        $idarea = 3;
    } elseif ($edad >= 9 && $edad <= 11) {
        $idarea = 4;
    } else {
        continue; // Si no entra en ningún rango, omitir
    }

    // Actualizar solo si el área es diferente
    $stmtCheck = $conn->prepare("SELECT idarea FROM test_ninos WHERE id_nino = ?");
    $stmtCheck->bind_param("i", $id_nino);
    $stmtCheck->execute();
    $stmtCheck->bind_result($area_actual);
    $stmtCheck->fetch();
    $stmtCheck->close();

    if ($area_actual != $idarea) {
        $stmtUpdate = $conn->prepare("UPDATE test_ninos SET idarea = ? WHERE id_nino = ?");
        $stmtUpdate->bind_param("ii", $idarea, $id_nino);
        $stmtUpdate->execute();
        $stmtUpdate->close();
        $updated++;
    }
}

echo json_encode([
    'status' => 'success',
    'message' => "Áreas actualizadas para $updated niños."
]);
