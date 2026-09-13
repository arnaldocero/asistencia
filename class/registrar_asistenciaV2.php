<?php
// Incluir el archivo de conexión a la base de datos
date_default_timezone_set('America/Bogota');
include('../ajax/db_connect.php');

// Obtener los valores enviados por POST
$id_nino = $_POST['id_nino'];
$id_area = $_POST['id_area'];
$fecha_asistencia = date('Y-m-d');

// Comprobar si ya existe un registro de asistencia para el niño en el día actual
$sqlCheck = "SELECT id_asistencia, estado FROM asistencia WHERE id_nino = ? AND fecha_asistencia = ?";
$stmtCheck = $conn->prepare($sqlCheck);
$stmtCheck->bind_param("is", $id_nino, $fecha_asistencia);
$stmtCheck->execute();
$resultCheck = $stmtCheck->get_result();

if ($resultCheck->num_rows > 0) {
    $row = $resultCheck->fetch_assoc();
    if ($row['estado'] == '1') {
        // Si ya está registrado, devolver mensaje
        echo json_encode(['mensaje' => 'La asistencia ya está registrada para hoy.', 'estado' => '1']);
        exit; // Salir para no ejecutar más código
    }
}

// Insertar registro en la tabla asistencia
$hora_entrada = date('H:i:s');
$sql = "INSERT INTO asistencia (id_nino, id_area, fecha_asistencia, hora_entrada, estado) 
        VALUES (?, ?, ?, ?, '1')"; // Estado se establece directamente a '1'
$stmt = $conn->prepare($sql);
$stmt->bind_param("iiss", $id_nino, $id_area, $fecha_asistencia, $hora_entrada);

if (!$stmt->execute()) {
    echo json_encode([
        'mensaje' => 'Error al registrar asistencia: ' . $stmt->error,
        'estado' => '0'
    ]);
    exit;
}

// Obtener el ID generado para la asistencia
$id_asistencia = $conn->insert_id;

// Obtener el nombre del niño
$sqlNino = "SELECT nombre, apellido, codigo_qr AS qr FROM test_ninos WHERE id_nino = ?";
$stmtNino = $conn->prepare($sqlNino);
$stmtNino->bind_param("i", $id_nino);
$stmtNino->execute();
$resultNino = $stmtNino->get_result();
$rowNino = $resultNino->fetch_assoc();
$nombre = $rowNino['nombre'];
$apellido = $rowNino['apellido'];
$codigoqr = $rowNino['qr'];

// Obtener el consecutivo actual del área
$sqlConsecutivo = "SELECT consecutivo FROM consecutivo WHERE id_area = ? ORDER BY fecha_consecutivo DESC LIMIT 1";
$stmtConsecutivo = $conn->prepare($sqlConsecutivo);
$stmtConsecutivo->bind_param("i", $id_area);
$stmtConsecutivo->execute();
$resultConsecutivo = $stmtConsecutivo->get_result();
$rowConsecutivo = $resultConsecutivo->fetch_assoc();
$consecutivo = $rowConsecutivo ? $rowConsecutivo['consecutivo'] : 0;

// Actualizar el consecutivo para el área
$nuevoConsecutivo = $consecutivo + 1;
$sqlUpdateConsecutivo = "UPDATE consecutivo SET consecutivo = ?, fecha_consecutivo = ? WHERE id_area = ?";
$stmtUpdate = $conn->prepare($sqlUpdateConsecutivo);
$fechaHoy = date('Y-m-d');
$stmtUpdate->bind_param("isi", $nuevoConsecutivo, $fechaHoy, $id_area);
$stmtUpdate->execute();

// Actualizar el registro de asistencia con el nuevo consecutivo
$sqlUpdateAsistencia = "UPDATE asistencia SET consecutivo = ? WHERE id_asistencia = ?";
$stmtUpdateAsistencia = $conn->prepare($sqlUpdateAsistencia);
$stmtUpdateAsistencia->bind_param("ii", $nuevoConsecutivo, $id_asistencia);
$stmtUpdateAsistencia->execute();

// Devolver la respuesta JSON
echo json_encode([
    'mensaje' => 'Asistencia registrada correctamente',
    'estado' => '1',
    'nombre' => $nombre,
    'apellido' => $apellido,
    'nombre_nino' => $nombre . ' ' . $apellido, // Si quieres seguir enviando el nombre completo también
    'consecutivo' => $nuevoConsecutivo,
    'codigoQR' => $codigoqr,
    'id_asistencia' => $id_asistencia
]);
?>
