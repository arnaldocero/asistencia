<?php
require_once __DIR__ . '/verificar_sesion_api.php'; // Solo usuarios con sesión iniciada
ini_set('display_errors', 0);
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);

header('Content-Type: application/json; charset=utf-8');
include('../phpqrcode/qrlib.php');  
include('../ajax/db_connect.php');
require_once __DIR__ . '/../ajax/areas.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // 1. Sanitización de datos del formulario
    $nombre_nino       = trim($_POST['fname'] ?? '');
    $apellido_nino     = trim($_POST['lname'] ?? '');
    $fecha_nacimiento  = trim($_POST['fechaNacimiento'] ?? '');
    $nombre_padre      = trim($_POST['padrename'] ?? '');
    $telefono_delpadre = trim($_POST['padrephone'] ?? '');
    $tiene_diagnostico = isset($_POST['diagnostico']) ? 1 : 0;

    if (empty($nombre_nino) || empty($apellido_nino)) {
        echo json_encode(['status' => 'error', 'message' => 'El nombre y apellido del niño son obligatorios']);
        exit();
    }

    if (empty($fecha_nacimiento)) {
        echo json_encode(['status' => 'error', 'message' => 'La fecha de nacimiento es obligatoria']);
        exit();
    }

    // 2. Calcular edad y área según la fecha de nacimiento (rangos en tbl_areas)
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

    // 3. Validación estricta anti-duplicados (Evita doble registro aunque vengan de celular o PC)
    // Comprueba coincidencia exacta sin importar mayúsculas/minúsculas ni espacios extra
    $check_stmt = $conn->prepare("SELECT id_nino, idarea, activo FROM test_ninos WHERE LOWER(TRIM(nombre)) = LOWER(?) AND LOWER(TRIM(apellido)) = LOWER(?) ORDER BY activo DESC LIMIT 1");
    if ($check_stmt === false) {
        echo json_encode(['status' => 'error', 'message' => 'Error al preparar la verificación.' . registrar_error_bd($conn->error)]);
        exit();
    }

    $check_stmt->bind_param("ss", $nombre_nino, $apellido_nino);
    $check_stmt->execute();
    $check_res = $check_stmt->get_result();

    if ($check_res->num_rows > 0) {
        $ninoExistente = $check_res->fetch_assoc();
        $check_stmt->close();

        // Niño eliminado antes (borrado lógico): se reactiva con los datos nuevos
        // en lugar de crear un duplicado, así conserva su historial de asistencia.
        if ((int)$ninoExistente['activo'] === 0) {
            $id_nino = (int)$ninoExistente['id_nino'];
            $stmtReact = $conn->prepare("UPDATE test_ninos SET activo = 1, fecha_baja = NULL,
                fecha_nacimiento = ?, nombre_padre = ?, telefono = ?, idarea = ?, tiene_diagnostico = ?
                WHERE id_nino = ?");
            $stmtReact->bind_param("sssiii", $fecha_nacimiento, $nombre_padre, $telefono_delpadre, $area, $tiene_diagnostico, $id_nino);
            if (!$stmtReact->execute()) {
                echo json_encode(['status' => 'error', 'message' => 'Error al reactivar el niño.' . registrar_error_bd($stmtReact->error)]);
                exit();
            }
            $stmtReact->close();
            $conn->close();
            echo json_encode([
                'status' => 'success',
                'message' => 'El niño estaba eliminado y se reactivó con los datos nuevos',
                'id_nino' => $id_nino,
                'id_area' => $area
            ]);
            exit();
        }

        $conn->close();
        echo json_encode([
            'status' => 'error', 
            'message' => 'El niño ya existe registrado en el sistema con el ID #' . $ninoExistente['id_nino']
        ]);
        exit();
    }
    $check_stmt->close();

    // 4. Inserción en test_ninos
    $stmt = $conn->prepare("INSERT INTO test_ninos 
        (nombre, apellido, fecha_nacimiento, nombre_padre, telefono, idarea, tiene_diagnostico) 
        VALUES (?, ?, ?, ?, ?, ?, ?)");

    if ($stmt === false) {
        echo json_encode(['status' => 'error', 'message' => 'Error al preparar la consulta.' . registrar_error_bd($conn->error)]);
        exit();
    }

    $stmt->bind_param("sssssii", $nombre_nino, $apellido_nino, $fecha_nacimiento, $nombre_padre, $telefono_delpadre, $area, $tiene_diagnostico);

    if ($stmt->execute()) {
        $id_nino = $stmt->insert_id;

        // 5. Guardar diagnósticos si existen
        if ($tiene_diagnostico && !empty($_POST['diagnosticos']) && is_array($_POST['diagnosticos'])) {
            $stmtDiag = $conn->prepare("INSERT INTO nino_diagnostico (id_nino, id_diagnostico) VALUES (?, ?)");
            if ($stmtDiag) {
                foreach ($_POST['diagnosticos'] as $id_diagnostico) {
                    $id_diag_int = intval($id_diagnostico);
                    $stmtDiag->bind_param("ii", $id_nino, $id_diag_int);
                    $stmtDiag->execute();
                }
                $stmtDiag->close();
            }
        }

        // 6. Generación del Código QR
        // La carpeta se calcula desde este archivo, así funciona igual en local y en producción
        // sin importar el nombre de la carpeta del proyecto. En la BD se guarda la ruta relativa
        // a la raíz del proyecto (ej. dist/img/codigosqr/qr_nino_5.png), usable como URL.
        $ruta_qr = "dist/img/codigosqr/qr_nino_" . $id_nino . ".png";
        $archivo_qr = __DIR__ . "/../" . $ruta_qr;
        $carpeta_qr = dirname($archivo_qr);
        if (!file_exists($carpeta_qr)) {
            mkdir($carpeta_qr, 0755, true);
        }

        $contenido_qr = "ID: $id_nino, Nombre: $nombre_nino $apellido_nino";

        // Si el QR falla (ej. falta la extensión GD) el niño ya quedó registrado: se deja
        // sin QR y se continúa, para que el check-in automático no se pierda.
        $qr_generado = false;
        try {
            QRcode::png($contenido_qr, $archivo_qr, QR_ECLEVEL_L, 10);
            $qr_generado = file_exists($archivo_qr);
        } catch (Throwable $e) {
            error_log("insertar_nino: no se pudo generar el QR del niño $id_nino: " . $e->getMessage());
        }

        // 7. Actualizar la ruta del QR en la base de datos
        $update_stmt = $qr_generado ? $conn->prepare("UPDATE test_ninos SET codigo_qr = ? WHERE id_nino = ?") : false;
        if ($update_stmt) {
            $update_stmt->bind_param("si", $ruta_qr, $id_nino);
            $update_stmt->execute();
            $update_stmt->close();
        }

        // 8. Respuesta compatible con el auto-registro de asistencia en JavaScript
        echo json_encode([
            'status' => 'success',
            'message' => 'Niño registrado exitosamente',
            'id_nino' => $id_nino,
            'id_area' => $area
        ]);

    } else {
        echo json_encode(['status' => 'error', 'message' => 'Error al registrar el niño.' . registrar_error_bd($stmt->error)]);
    }

    $stmt->close();
    $conn->close();

} else {
    echo json_encode(['status' => 'error', 'message' => 'Método no permitido']);
}