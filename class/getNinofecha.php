<?php
require_once __DIR__ . '/verificar_sesion_api.php'; // Solo usuarios con sesión iniciada
include('../ajax/db_connect.php');
header('Content-Type: application/json');

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);

    $sql = "SELECT fecha_nacimiento, nombre_padre, telefono FROM test_ninos WHERE id_nino = ?";
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Error en la preparación de la consulta.'
        ]);
        exit;
    }

    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        echo json_encode([
            'status' => 'success',
            'fecha_nacimiento' => $row['fecha_nacimiento'],
            'nombre_padre' => $row['nombre_padre'],
            'telefono' => $row['telefono']
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'Niño no encontrado.'
        ]);
    }
} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'Falta el parámetro ID.'
    ]);
}
