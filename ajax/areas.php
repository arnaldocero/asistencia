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

// Edad mínima aceptada (para los mensajes de error).
function edad_minima_areas($conn)
{
    $row = $conn->query("SELECT MIN(edad_min) AS minima FROM tbl_areas")->fetch_assoc();
    return $row ? (int)$row['minima'] : null;
}
