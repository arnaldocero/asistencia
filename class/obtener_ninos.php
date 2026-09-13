<?php
// Habilitar la visualización de errores (solo para depuración; deshabilitar en producción)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Establecer encabezado JSON
header('Content-Type: application/json');

// Incluir el archivo de conexión a la base de datos
include('../ajax/db_connect.php'); // Asegúrate de que la ruta es correcta

// Verificar la conexión
if ($conn->connect_error) {
    echo json_encode(['status' => 'error', 'message' => 'Conexión fallida: ' . $conn->connect_error]);
    exit(); // Finaliza el script si hay un error de conexión
}

// Preparar la consulta SQL utilizando sentencias preparadas
$stmt = $conn->prepare("SELECT a.id_asistencia, a.id_nino, n.nombre, n.apellido, n.nombre_padre, n.telefono FROM asistencia a INNER JOIN test_ninos n ON a.id_nino = n.id_nino WHERE a.estado = '1' AND a.fecha_asistencia = CURDATE() ORDER BY a.hora_entrada DESC;");

if (!$stmt) {
    echo json_encode(['status' => 'error', 'message' => 'Error al preparar la consulta: ' . $conn->error]);
    exit(); // Finaliza el script si hay un error al preparar la consulta
}

// Ejecutar la consulta
$stmt->execute();

// Obtener el resultado
$result = $stmt->get_result();

if ($result === false) {
    echo json_encode(['status' => 'error', 'message' => 'Error al ejecutar la consulta: ' . $stmt->error]);
    exit(); // Finaliza el script si hay un error al ejecutar la consulta
}

$datos = array();

// Verificar si hay resultados
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $datos[] = $row;
    }
    echo json_encode(['status' => 'success', 'data' => $datos]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'No hay datos disponibles']);
}

// Cerrar la consulta y la conexión
$stmt->close();
$conn->close();
