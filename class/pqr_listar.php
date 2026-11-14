<?php
require_once __DIR__ . '/verificar_sesion_api.php'; // Cualquier usuario con sesión
header('Content-Type: application/json; charset=utf-8');

include('../ajax/db_connect.php');

// El administrador ve todas; recepción solo las que registró
$es_admin = ($_SESSION['usuario_rol'] ?? '') === 'admin';
$id_usuario = (int)$_SESSION['usuario_id'];

$estado = $_GET['estado'] ?? '';
$filtrar_estado = in_array($estado, ['pendiente', 'en_proceso', 'resuelta'], true);

$sql = "SELECT p.id_pqr, p.tipo, p.nombre_solicitante, p.telefono, p.descripcion, p.estado, p.respuesta,
               p.fecha_registro, p.fecha_actualizacion, p.id_nino,
               CONCAT(TRIM(n.nombre), ' ', TRIM(n.apellido)) AS nino,
               ur.nombre_usuario AS registrado_por,
               ua.nombre_usuario AS actualizado_por
        FROM pqr p
        LEFT JOIN test_ninos n ON n.id_nino = p.id_nino
        LEFT JOIN usuarios ur ON ur.id_usuario = p.id_usuario_registro
        LEFT JOIN usuarios ua ON ua.id_usuario = p.id_usuario_actualiza
        WHERE 1 = 1"
     . ($es_admin ? "" : " AND p.id_usuario_registro = ?")
     . ($filtrar_estado ? " AND p.estado = ?" : "")
     . " ORDER BY FIELD(p.estado, 'pendiente', 'en_proceso', 'resuelta'), p.fecha_registro DESC";

$stmt = $conn->prepare($sql);
if (!$es_admin && $filtrar_estado) {
    $stmt->bind_param("is", $id_usuario, $estado);
} elseif (!$es_admin) {
    $stmt->bind_param("i", $id_usuario);
} elseif ($filtrar_estado) {
    $stmt->bind_param("s", $estado);
}

if (!$stmt->execute()) {
    echo json_encode(['status' => 'error', 'message' => 'Error al cargar las PQR.' . registrar_error_bd($stmt->error)]);
    exit();
}
$result = $stmt->get_result();

$pqrs = [];
while ($row = $result->fetch_assoc()) {
    $pqrs[] = $row;
}

// Conteo por estado (de las que este usuario puede ver), para los indicadores
$sqlConteo = "SELECT estado, COUNT(*) AS total FROM pqr" . ($es_admin ? "" : " WHERE id_usuario_registro = ?") . " GROUP BY estado";
$stmtC = $conn->prepare($sqlConteo);
if (!$es_admin) {
    $stmtC->bind_param("i", $id_usuario);
}
$stmtC->execute();
$conteo = ['pendiente' => 0, 'en_proceso' => 0, 'resuelta' => 0];
$resC = $stmtC->get_result();
while ($row = $resC->fetch_assoc()) {
    $conteo[$row['estado']] = (int)$row['total'];
}

echo json_encode(['status' => 'success', 'es_admin' => $es_admin, 'conteo' => $conteo, 'data' => $pqrs]);
$conn->close();
