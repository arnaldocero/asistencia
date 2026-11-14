<?php
// Estado actual del usuario en la base de datos, para revisarlo en cada petición:
// así un usuario desactivado queda fuera de inmediato y un cambio de rol aplica
// sin esperar a que expire su sesión.

// Devuelve ['activo' => bool, 'rol' => 'admin'|'usuario'] o null si el usuario ya no existe.
function estado_usuario_actual($conn, $id_usuario)
{
    $id_usuario = (int)$id_usuario;
    try {
        $stmt = $conn->prepare("SELECT rol, activo FROM usuarios WHERE id_usuario = ?");
        $stmt->bind_param("i", $id_usuario);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
    } catch (mysqli_sql_exception $e) {
        // Sin la columna "activo" (migración 2026-11-06 sin ejecutar): solo se revisa el rol
        error_log('estado_usuario_actual: ' . $e->getMessage());
        $stmt = $conn->prepare("SELECT rol, 1 AS activo FROM usuarios WHERE id_usuario = ?");
        $stmt->bind_param("i", $id_usuario);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
    }

    if (!$row) {
        return null;
    }
    return ['activo' => (int)$row['activo'] === 1, 'rol' => $row['rol']];
}
