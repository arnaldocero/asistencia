<?php
require_once __DIR__ . '/verificar_sesion_api.php'; // Solo usuarios con sesión iniciada
requerir_rol('admin'); // Solo administradores gestionan usuarios
header('Content-Type: application/json; charset=utf-8');

include('../ajax/db_connect.php');

$result = $conn->query("SELECT id_usuario, nombre_usuario, email, rol, activo, fecha_creacion
                        FROM usuarios ORDER BY activo DESC, nombre_usuario");
if (!$result) {
    echo json_encode(['status' => 'error', 'message' => 'Error al cargar los usuarios.' . registrar_error_bd($conn->error)]);
    exit();
}

$usuarios = [];
while ($row = $result->fetch_assoc()) {
    $row['es_yo'] = ((int)$row['id_usuario'] === (int)$_SESSION['usuario_id']);
    $usuarios[] = $row;
}

echo json_encode(['status' => 'success', 'data' => $usuarios]);
$conn->close();
