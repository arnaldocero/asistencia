<?php
require_once __DIR__ . '/verificar_sesion_api.php'; // Solo usuarios con sesión iniciada
header('Content-Type: application/json');
include('../ajax/db_connect.php');
require_once __DIR__ . '/../ajax/areas.php';

// Mostrar errores (solo para desarrollo)
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
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

    // Calcular área según edad (rangos en tbl_areas)
    $edad = calcular_edad($fecha_nacimiento);
    if ($edad === null) {
        echo json_encode(['status' => 'error', 'message' => 'Fecha de nacimiento inválida']);
        exit();
    }
    $area = obtener_area_por_edad($conn, $edad);
    if ($area === null) {
        echo json_encode(['status' => 'error', 'message' => 'Edad fuera de rango permitido (mínimo ' . edad_minima_areas($conn) . ' años)']);
        exit();
    }

    if ($id_nino) {
        $stmt = $conn->prepare("UPDATE test_ninos 
            SET nombre = ?, apellido = ?, fecha_nacimiento = ?, nombre_padre = ?, telefono = ?, idarea = ?, tiene_diagnostico = ?
            WHERE id_nino = ?");

        if ($stmt === false) {
            echo json_encode(['status' => 'error', 'message' => 'Error al preparar la consulta.' . registrar_error_bd($conn->error)]);
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
            echo json_encode(['status' => 'error', 'message' => 'Error al actualizar el niño.' . registrar_error_bd($stmt->error)]);
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