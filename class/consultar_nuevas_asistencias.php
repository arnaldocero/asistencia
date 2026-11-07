<?php
require_once __DIR__ . '/verificar_sesion_api.php'; // Solo usuarios con sesión iniciada
date_default_timezone_set('America/Bogota');
header('Content-Type: application/json; charset=utf-8');
include('../ajax/db_connect.php');
require_once __DIR__ . '/../ajax/cumpleanos.php';

$ultimo_id = isset($_GET['ultimo_id']) ? intval($_GET['ultimo_id']) : 0;
$fecha_hoy = date('Y-m-d');

// Consulta si hay asistencias de hoy superiores al último ID que tiene la pantalla
$sql = "SELECT 
            a.id_asistencia, 
            a.id_nino, 
            a.consecutivo, 
            n.nombre, 
            n.apellido, 
            n.codigo_qr,
            n.fecha_nacimiento
        FROM asistencia a 
        INNER JOIN test_ninos n ON a.id_nino = n.id_nino 
        WHERE a.fecha_asistencia = ? 
          AND a.estado = '1' 
          AND a.id_asistencia > ? 
        ORDER BY a.id_asistencia ASC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("si", $fecha_hoy, $ultimo_id);
$stmt->execute();
$result = $stmt->get_result();

$nuevos = [];
while ($row = $result->fetch_assoc()) {
    // Primer domingo del mes: ¿cumplió años el mes anterior? (sticker con pastel)
    $cumple = info_cumpleanos($row['fecha_nacimiento']);
    unset($row['fecha_nacimiento']);
    $row['cumplio'] = $cumple['cumplio'];
    $row['mes_cumple'] = $cumple['mes'];
    $row['edad_cumplida'] = $cumple['edad'];
    $nuevos[] = $row;
}

$stmt->close();
$conn->close();

echo json_encode([
    'status' => 'success',
    'hay_nuevos' => count($nuevos) > 0,
    'nuevos' => $nuevos
]);