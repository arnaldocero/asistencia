<?php
// Cálculo del área de un niño según su edad. Los rangos viven en tbl_areas
// (edad_min / edad_max, NULL = sin límite) para que todo el sistema use los mismos.

// Edad en años cumplidos, o null si la fecha no es válida, está en el futuro
// o es absurda (ej. '0000-00-00', que PHP interpretaría como el año -1).
function calcular_edad($fecha_nacimiento)
{
    $nacimiento = DateTime::createFromFormat('!Y-m-d', (string)$fecha_nacimiento);
    if (!$nacimiento || $nacimiento->format('Y-m-d') !== $fecha_nacimiento) {
        return null; // Formato inválido o fecha inexistente (ej. 2020-02-31, 0000-00-00)
    }
    $hoy = new DateTime('today');
    if ($nacimiento > $hoy || (int)$nacimiento->format('Y') < 1900) {
        return null;
    }
    return $hoy->diff($nacimiento)->y;
}

// id_area que corresponde a la edad, o null si no entra en ningún rango.
function obtener_area_por_edad($conn, $edad)
{
    if ($edad === null) {
        return null;
    }
    $stmt = $conn->prepare("SELECT id_area FROM tbl_areas
                            WHERE edad_min <= ? AND (edad_max IS NULL OR edad_max >= ?)
                            ORDER BY edad_min DESC LIMIT 1");
    $stmt->bind_param("ii", $edad, $edad);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row ? (int)$row['id_area'] : null;
}

// Número desde el que arranca cada día el consecutivo de un área
// (el primer sticker del día es este número + 1: 301, 501, 701, 901).
function consecutivo_inicial($id_area)
{
    $bases = [1 => 300, 2 => 500, 3 => 700, 4 => 900];
    return $bases[(int)$id_area] ?? 0;
}

// Motivo por el que una fecha de nacimiento hay que corregirla, o null si está bien.
// $edad_minima = edad_minima_areas($conn), calculada una sola vez para toda la lista.
function motivo_fecha_invalida($fecha_nacimiento, $edad_minima)
{
    if ($fecha_nacimiento === null || $fecha_nacimiento === '' || $fecha_nacimiento === '0000-00-00') {
        return 'Sin fecha de nacimiento';
    }
    $edad = calcular_edad($fecha_nacimiento);
    if ($edad === null) {
        return 'Fecha imposible (' . $fecha_nacimiento . ')';
    }
    if ($edad < $edad_minima) {
        return 'Menor de ' . $edad_minima . ' años: no tiene área';
    }
    if ($edad > 17) {
        return 'Tiene ' . $edad . ' años: revisar (¿fecha del acudiente?)';
    }
    return null;
}

// Edad mínima aceptada (para los mensajes de error).
function edad_minima_areas($conn)
{
    $row = $conn->query("SELECT MIN(edad_min) AS minima FROM tbl_areas")->fetch_assoc();
    return $row ? (int)$row['minima'] : null;
}
