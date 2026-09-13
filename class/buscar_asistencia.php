<?php
// Conexión a la base de datos
include('../ajax/db_connect.php');  // Asegúrate de que tu archivo de conexión esté correctamente configurado

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_nino = $_POST['id_nino'];
    date_default_timezone_set('America/Bogota');
    $fecha_sistema = date('Y-m-d');  // Fecha del sistema (hoy)

    // Consulta para obtener el registro de asistencia más reciente de ese niño
    $sql = "SELECT DATE(fecha_asistencia) As fecha_asistencia, estado FROM asistencia WHERE id_nino = ? ORDER BY fecha_asistencia DESC LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_nino);
    $stmt->execute();
    $stmt->store_result();
    
    // Verificamos si hay resultados
    if ($stmt->num_rows > 0) {
        $stmt->bind_result($fecha_asistencia, $estado);
        $stmt->fetch();

        // Compara la fecha de asistencia con la fecha del sistema
        if ($fecha_asistencia === $fecha_sistema) {
            // Si las fechas coinciden, devolvemos true y el estado
            echo json_encode(['status' => 'success', 'match' => true, 'estado' => $estado, 'idnino' => $id_nino]);
        } else {
            // Si las fechas no coinciden
            echo json_encode(['status' => 'success', 'match' => false, 'message' => 'Las fechas no coinciden', 'fecha' => $fecha_asistencia,'fecha_sistema' =>$fecha_sistema]);
        }
    } else {
        // Si no hay registros para el niño
        echo json_encode(['status' => 'error', 'message' => 'No hay registros de asistencia para este niño']);
    }

    $stmt->close();
    $conn->close();
} else {
    echo json_encode(['status' => 'error', 'message' => 'Método no permitido']);
}
?>