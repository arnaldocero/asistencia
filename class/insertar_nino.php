<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);

header('Content-Type: application/json');
include('../phpqrcode/qrlib.php');  
include('../ajax/db_connect.php');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    // Datos del formulario
    $nombre_nino = $_POST['fname'];
    $apellido_nino = $_POST['lname'];
    $fecha_nacimiento = $_POST['fechaNacimiento'];
    $nombre_padre = $_POST['padrename'];
    $telefono_delpadre = $_POST['padrephone'];
    // Nuevo campo: diagnóstico (checkbox)
    $tiene_diagnostico = isset($_POST['diagnostico']) ? 1 : 0;

    // Verificar si el niño ya está registrado
    $check_stmt = $conn->prepare("SELECT id_nino FROM test_ninos WHERE nombre = ? AND apellido = ? AND idarea = ?");
    if ($check_stmt === false) {
        echo json_encode(['status' => 'error', 'message' => 'Error al preparar la consulta de verificación: ' . $conn->error]);
        exit();
    }

    $check_stmt->bind_param("sss", $nombre_nino, $apellido_nino, $area);
    $check_stmt->execute();
    $check_stmt->store_result();

    if ($check_stmt->num_rows > 0) {
        echo json_encode(['status' => 'error', 'message' => 'El niño ya está registrado con el mismo nombre, apellido y área']);
        $check_stmt->close();
        $conn->close();
        exit();
    }

    $check_stmt->close();

    // Calcular edad y área
    $fecha_nacimiento_dt = new DateTime($fecha_nacimiento);
    $hoy = new DateTime();
    $edad = $hoy->diff($fecha_nacimiento_dt)->y;

    if ($edad == 2 || $edad == 3 || $edad == 4) {
        $area = 1;
    } elseif ($edad == 5 || $edad == 6) {
        $area = 2;
    } elseif ($edad == 7 || $edad == 8) {
        $area = 3;
    } elseif ($edad >= 9) {
        $area = 4;
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Edad fuera de rango permitido']);
        exit();
    }

    // INSERT con tiene_diagnostico
    $stmt = $conn->prepare("INSERT INTO test_ninos 
        (nombre, apellido, fecha_nacimiento, nombre_padre, telefono, idarea, tiene_diagnostico) 
        VALUES (?, ?, ?, ?, ?, ?, ?)");

    if ($stmt === false) {
        echo json_encode(['status' => 'error', 'message' => 'Error al preparar la consulta: ' . $conn->error]);
        exit();
    }

    $stmt->bind_param("sssssis", $nombre_nino, $apellido_nino, $fecha_nacimiento, $nombre_padre, $telefono_delpadre, $area, $tiene_diagnostico);

    if ($stmt->execute()) {
        $id_nino = $stmt->insert_id;

        // Guardar diagnósticos si tiene
if ($tiene_diagnostico && isset($_POST['diagnosticos'])) {
    $stmtDiag = $conn->prepare(
        "INSERT INTO nino_diagnostico (id_nino, id_diagnostico) VALUES (?, ?)"
    );

    foreach ($_POST['diagnosticos'] as $id_diagnostico) {
        $stmtDiag->bind_param("ii", $id_nino, $id_diagnostico);
        $stmtDiag->execute();
    }
    $stmtDiag->close();
}

        $contenido_qr = "ID: $id_nino, Nombre: $nombre_nino $apellido_nino";
        $ruta_qr = $_SERVER['DOCUMENT_ROOT'] . "/asistenciacasakids/dist/img/codigosqr/qr_nino_" . $id_nino . ".png";

        try {
            QRcode::png($contenido_qr, $ruta_qr, QR_ECLEVEL_L, 10);
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => 'Error al generar el código QR: ' . $e->getMessage()]);
            exit();
        }

        $update_stmt = $conn->prepare("UPDATE test_ninos SET codigo_qr = ? WHERE id_nino = ?");
        if ($update_stmt === false) {
            echo json_encode(['status' => 'error', 'message' => 'Error al preparar la consulta de actualización: ' . $conn->error]);
            exit();
        }

        $update_stmt->bind_param("si", $ruta_qr, $id_nino);

        if ($update_stmt->execute()) {
            echo json_encode(['status' => 'success',
    'message' => 'Niño registrado exitosamente',
    'id_nino' => $id_nino,
    'id_area' => $area]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Error al actualizar la ruta del QR: ' . $update_stmt->error]);
        }

        $update_stmt->close();
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Error al registrar el niño: ' . $stmt->error]);
    }

    $stmt->close();
    $conn->close();

} else {
    echo json_encode(['status' => 'error', 'message' => 'Método no permitido']);
}
?>