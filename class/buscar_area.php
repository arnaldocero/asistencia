<?php
require_once __DIR__ . '/verificar_sesion_api.php'; // Solo usuarios con sesión iniciada
// Incluir el archivo de conexión a la base de datos
include('../ajax/db_connect.php');

// Verificar si se ha enviado el id_nino
if (isset($_POST['id_nino'])) {
    $id_nino = $_POST['id_nino'];

    // Preparar la consulta para obtener el id_area del niño
    $sql = "SELECT idarea FROM test_ninos WHERE id_nino = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_nino);
    $stmt->execute();
    $result = $stmt->get_result();

    // Verificar si se encontró un resultado
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $id_area = $row['idarea'];

        // Devolver el id_area en formato JSON
        echo json_encode(['status' => 'success', 'id_area' => $id_area]);
    } else {
        // Si no se encuentra el niño
        echo json_encode(['status' => 'error', 'message' => 'Niño no encontrado']);
    }

    $stmt->close();
    $conn->close();
} else {
    echo json_encode(['status' => 'error', 'message' => 'ID del niño no enviado']);
}
?>
