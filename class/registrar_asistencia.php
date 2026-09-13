<?php

// Conexión a la base de datos

include('../ajax/db_connect.php');// Asegúrate de que tu archivo de conexión está correctamente configurado



if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $id_nino = $_POST['id_nino'];

    $id_clase = $_POST['clase'];

    $fecha_asistencia = $_POST['fecha_asistencia'];

    $hora_entrada = $_POST['hora_entrada'];

    $estado='1';



    // Validación básica

    if (empty($id_nino) || empty($id_clase) || empty($fecha_asistencia) || empty($hora_entrada)) {

        echo json_encode(['status' => 'error', 'message' => 'Todos los campos son obligatorios']);

        exit();

    }



    // Preparar la consulta de inserción

    $stmt = $conn->prepare("INSERT INTO asistencia (id_nino, id_area, fecha_asistencia, hora_entrada, estado) VALUES (?, ?, ?, ?,?)");

    $stmt->bind_param("iisss", $id_nino, $id_clase, $fecha_asistencia, $hora_entrada, $estado);



    if ($stmt->execute()) {

        echo json_encode(['status' => 'success', 'message' => 'Asistencia registrada exitosamente', 'id_nino' => $id_nino]);

    } else {

        echo json_encode(['status' => 'error', 'message' => 'Error al registrar la asistencia: ' . $stmt->error]);

    }



    $stmt->close();

    $conn->close();

} else {

    echo json_encode(['status' => 'error', 'message' => 'Método no permitido']);

}

?>