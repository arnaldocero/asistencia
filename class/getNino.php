<?php
require_once __DIR__ . '/verificar_sesion_api.php'; // Solo usuarios con sesión iniciada
include('../ajax/db_connect.php');

header('Content-Type: application/json');

if (isset($_GET['id'])) {

    $id_nino = intval($_GET['id']);

    // 1️⃣ Obtener datos del niño
    $sql = "SELECT * FROM test_ninos WHERE id_nino = ?";
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Error al preparar la consulta del niño'
        ]);
        exit;
    }

    $stmt->bind_param("i", $id_nino);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo json_encode([
            'status' => 'error',
            'message' => 'No se encontraron datos'
        ]);
        exit;
    }

    $nino = $result->fetch_assoc();
    $stmt->close();

    // 2️⃣ Obtener diagnósticos del niño
    $diagnosticos = [];
    $sqlDiag = "
        SELECT id_diagnostico 
        FROM nino_diagnostico
        WHERE id_nino = ?
    ";

    $stmtDiag = $conn->prepare($sqlDiag);

    if ($stmtDiag) {
        $stmtDiag->bind_param("i", $id_nino);
        $stmtDiag->execute();
        $resDiag = $stmtDiag->get_result();

        while ($row = $resDiag->fetch_assoc()) {
            $diagnosticos[] = $row['id_diagnostico'];
        }

        $stmtDiag->close();
    }

    // 3️⃣ Respuesta final
    echo json_encode([
        'status' => 'success',
        'data' => $nino,
        'diagnosticos' => $diagnosticos,
        'message' => ''
    ]);

} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'No se proporcionó un ID válido'
    ]);
}
