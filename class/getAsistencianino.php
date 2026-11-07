<?php
require_once __DIR__ . '/verificar_sesion_api.php'; // Solo usuarios con sesión iniciada
// Habilitar la visualización de errores (solo para depuración; deshabilitar en producción)
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

// Establecer encabezado JSON
header('Content-Type: application/json');

// Incluir el archivo de conexión a la base de datos
include('../ajax/db_connect.php'); // Asegúrate de que la ruta es correcta
require_once __DIR__ . '/../ajax/cumpleanos.php';

// Verificar la conexión
if ($conn->connect_error) {
    echo json_encode(['status' => 'error', 'message' => 'Conexión fallida.' . registrar_error_bd($conn->connect_error)]);
    exit(); // Finaliza el script si hay un error de conexión
}

// Verificar si se enviaron los datos necesarios
if (!isset($_POST['id_nino']) || !isset($_POST['id_asistencia'])) {
    echo json_encode(['status' => 'error', 'message' => 'Faltan parámetros necesarios']);
    exit();
}

// Obtener los valores enviados por POST
$id_nino = $_POST['id_nino'];
$id_asistencia = $_POST['id_asistencia'];

// Preparar la consulta SQL utilizando sentencias preparadas
$stmt = $conn->prepare("
    SELECT 
        a.id_asistencia, 
        a.id_nino, 
        n.nombre, 
        n.apellido, 
        n.nombre_padre, 
        a.hora_entrada, 
        n.codigo_qr,
        n.fecha_nacimiento,
        a.consecutivo
    FROM 
        asistencia a 
    INNER JOIN 
        test_ninos n 
    ON 
        a.id_nino = n.id_nino 
    WHERE 
        a.id_nino = ? 
        AND a.id_asistencia = ? 
        AND a.estado = '1'
    LIMIT 1;
");

if (!$stmt) {
    echo json_encode(['status' => 'error', 'message' => 'Error al preparar la consulta.' . registrar_error_bd($conn->error)]);
    exit();
}

// Vincular los parámetros
$stmt->bind_param("ii", $id_nino, $id_asistencia);

// Ejecutar la consulta
$stmt->execute();

// Obtener el resultado
$result = $stmt->get_result();

if ($result === false) {
    echo json_encode(['status' => 'error', 'message' => 'Error al ejecutar la consulta.' . registrar_error_bd($stmt->error)]);
    exit();
}

// Verificar si hay resultados
if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    // La reimpresión sale igual que el sticker original (con pastel si cumplió el mes anterior)
    $cumple = info_cumpleanos($row['fecha_nacimiento']);
    unset($row['fecha_nacimiento']);
    $row['cumplio'] = $cumple['cumplio'];
    echo json_encode(['status' => 'success', 'data' => $row]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'No se encontraron datos para los parámetros proporcionados']);
}

// Cerrar la consulta y la conexión
$stmt->close();
$conn->close();
?>
