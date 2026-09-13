<?php
header('Content-Type: application/json');
include('../ajax/db_connect.php');

// Mostrar errores (solo para desarrollo)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id_nino = isset($_POST['idNino']) ? $_POST['idNino'] : null;
    $nombre_nino = $_POST['fname'];
    $apellido_nino = $_POST['lname'];
    $fecha_nacimiento = $_POST['fechaNacimiento'];
    $nombre_padre = $_POST['padrename'];
    $telefono_delpadre = $_POST['padrephone'];

    // Nuevo campo: diagnóstico (checkbox)
    $tiene_diagnostico = isset($_POST['diagnostico']) ? 1 : 0;

    // Calcular área según edad
    $fecha_nacimiento_dt = new DateTime($fecha_nacimiento);
    $hoy = new DateTime();
    $edad = $hoy->diff($fecha_nacimiento_dt)->y; // Calcula los años completos
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

    if ($id_nino) {
        $stmt = $conn->prepare("UPDATE test_ninos 
            SET nombre = ?, apellido = ?, fecha_nacimiento = ?, nombre_padre = ?, telefono = ?, idarea = ?, tiene_diagnostico = ?
            WHERE id_nino = ?");

        if ($stmt === false) {
            echo json_encode(['status' => 'error', 'message' => 'Error al preparar la consulta: ' . $conn->error]);
            exit();
        }

        // Tipos: s = string, i = int
        $stmt->bind_param("sssssisi", 
            $nombre_nino, 
            $apellido_nino, 
            $fecha_nacimiento, 
            $nombre_padre, 
            $telefono_delpadre, 
            $area, 
            $tiene_diagnostico, 
            $id_nino
        );

        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'Niño actualizado exitosamente']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Error al actualizar el niño: ' . $stmt->error]);
        }
        $stmt->close();
    } else {
        echo json_encode(['status' => 'error', 'message' => 'ID de niño no proporcionado']);
    }

    $conn->close();
} else {
    echo json_encode(['status' => 'error', 'message' => 'Método no permitido']);
}
?>