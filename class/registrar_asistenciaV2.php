<?php
require_once __DIR__ . '/verificar_sesion_api.php'; // Solo usuarios con sesión iniciada
// Incluir el archivo de conexión a la base de datos
date_default_timezone_set('America/Bogota');
header('Content-Type: application/json; charset=utf-8');

include('../ajax/db_connect.php');
require_once __DIR__ . '/../ajax/areas.php';

// Obtener y sanitizar valores enviados por POST
$id_nino = isset($_POST['id_nino']) ? intval($_POST['id_nino']) : 0;
$id_area = isset($_POST['id_area']) ? intval($_POST['id_area']) : 0;
$fecha_asistencia = date('Y-m-d');
$hora_entrada = date('H:i:s');

if ($id_nino <= 0 || $id_area <= 0) {
    echo json_encode([
        'status' => 'error',
        'estado' => '0',
        'mensaje' => 'Datos inválidos o incompletos para registrar la asistencia.'
    ]);
    exit;
}

// Que cualquier error de MySQL lance excepción (así un fallo nunca confirma la transacción a medias)
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$max_intentos = 3;
for ($intento = 1; $intento <= $max_intentos; $intento++) {
    // Iniciar transacción para evitar condiciones de carrera (dos dispositivos al mismo tiempo)
    $conn->begin_transaction();

    try {
        // 1. Comprobar si ya existe registro hoy bloqueando la lectura
        $sqlCheck = "SELECT id_asistencia, consecutivo, estado FROM asistencia WHERE id_nino = ? AND fecha_asistencia = ? FOR UPDATE";
        $stmtCheck = $conn->prepare($sqlCheck);
        $stmtCheck->bind_param("is", $id_nino, $fecha_asistencia);
        $stmtCheck->execute();
        $resCheck = $stmtCheck->get_result();

        if ($resCheck->num_rows > 0) {
            $asistenciaExistente = $resCheck->fetch_assoc();
            $stmtCheck->close();
            $conn->commit();

            echo json_encode([
                'status' => 'already_registered',
                'estado' => '1',
                'mensaje' => 'El niño ya fue registrado hoy por otro usuario.',
                'ya_registrado' => true
            ]);
            exit;
        }
        $stmtCheck->close();

        // 2. Obtener datos del niño
        $sqlNino = "SELECT nombre, apellido, codigo_qr AS qr FROM test_ninos WHERE id_nino = ? AND activo = 1";
        $stmtNino = $conn->prepare($sqlNino);
        $stmtNino->bind_param("i", $id_nino);
        $stmtNino->execute();
        $resNino = $stmtNino->get_result();

        if ($resNino->num_rows === 0) {
            throw new Exception("El niño no existe en el sistema.");
        }
        $rowNino = $resNino->fetch_assoc();
        $nombre   = $rowNino['nombre'];
        $apellido = $rowNino['apellido'];
        $codigoqr = $rowNino['qr'];
        $stmtNino->close();

        // 3. Obtener y actualizar consecutivo para el área (bloqueo para que no se repita)
        $sqlConsecutivo = "SELECT consecutivo, fecha_consecutivo FROM consecutivo WHERE id_area = ? ORDER BY fecha_consecutivo DESC LIMIT 1 FOR UPDATE";
        $stmtConsecutivo = $conn->prepare($sqlConsecutivo);
        $stmtConsecutivo->bind_param("i", $id_area);
        $stmtConsecutivo->execute();
        $resConsecutivo = $stmtConsecutivo->get_result();
        $rowConsecutivo = $resConsecutivo->fetch_assoc();
        $stmtConsecutivo->close();

        $fechaHoy = date('Y-m-d');

        // Reinicio diario: el primer registro del día en cada área vuelve a empezar
        // desde su número base (301, 501, 701, 901). Así no depende de que alguien
        // abra una pantalla ni de un cron.
        if (!$rowConsecutivo || $rowConsecutivo['fecha_consecutivo'] !== $fechaHoy) {
            $consecutivo = consecutivo_inicial($id_area);
        } else {
            $consecutivo = intval($rowConsecutivo['consecutivo']);
        }

        $nuevoConsecutivo = $consecutivo + 1;

        $sqlUpdateConsecutivo = "UPDATE consecutivo SET consecutivo = ?, fecha_consecutivo = ? WHERE id_area = ?";
        $stmtUpdate = $conn->prepare($sqlUpdateConsecutivo);
        $stmtUpdate->bind_param("isi", $nuevoConsecutivo, $fechaHoy, $id_area);
        $stmtUpdate->execute();
        $stmtUpdate->close();

        // 4. Insertar registro de asistencia con su consecutivo de una sola vez
        $sqlInsert = "INSERT INTO asistencia (id_nino, id_area, fecha_asistencia, hora_entrada, estado, consecutivo) 
                      VALUES (?, ?, ?, ?, '1', ?)";
        $stmtInsert = $conn->prepare($sqlInsert);
        $stmtInsert->bind_param("iissi", $id_nino, $id_area, $fecha_asistencia, $hora_entrada, $nuevoConsecutivo);
        $stmtInsert->execute();
        $id_asistencia = $conn->insert_id;
        $stmtInsert->close();

        // Confirmar la transacción
        $conn->commit();

        echo json_encode([
            'status' => 'success',
            'mensaje' => 'Asistencia registrada correctamente',
            'estado' => '1',
            'nombre' => $nombre,
            'apellido' => $apellido,
            'nombre_nino' => $nombre . ' ' . $apellido,
            'consecutivo' => $nuevoConsecutivo,
            'codigoQR' => $codigoqr,
            'id_asistencia' => $id_asistencia
        ]);
        break;

    } catch (mysqli_sql_exception $e) {
        $conn->rollback();

        // 1213 = deadlock, 1205 = espera de bloqueo agotada: dos dispositivos chocaron, se reintenta.
        // En el reintento la fila del otro ya existe y se responde "ya registrado".
        if (($e->getCode() == 1213 || $e->getCode() == 1205) && $intento < $max_intentos) {
            usleep(random_int(50000, 150000));
            continue;
        }

        // 1062 = el índice único (id_nino, fecha_asistencia) rechazó un segundo registro simultáneo
        if ($e->getCode() == 1062) {
            echo json_encode([
                'status' => 'already_registered',
                'estado' => '1',
                'mensaje' => 'El niño ya fue registrado hoy por otro usuario.',
                'ya_registrado' => true
            ]);
        } else {
            error_log('registrar_asistenciaV2: ' . $e->getMessage());
            echo json_encode([
                'status' => 'error',
                'estado' => '0',
                'mensaje' => 'Error al registrar la asistencia. Intenta de nuevo.'
            ]);
        }
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode([
            'status' => 'error',
            'estado' => '0',
            'mensaje' => 'Error al registrar la asistencia: ' . $e->getMessage()
        ]);
    }
    break; // Error no recuperable: ya se respondió, no se reintenta
}

$conn->close();