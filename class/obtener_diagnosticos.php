<?php
require_once __DIR__ . '/verificar_sesion_api.php'; // Solo usuarios con sesión iniciada
include('../ajax/db_connect.php');

$result = $conn->query("SELECT id_diagnostico, nombre FROM diagnosticos_neurodivergencia WHERE activo = 1");

$datos = [];
while ($row = $result->fetch_assoc()) {
    $datos[] = $row;
}

echo json_encode([
    "status" => "success",
    "data" => $datos
]);