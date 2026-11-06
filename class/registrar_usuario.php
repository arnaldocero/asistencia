<?php
require_once __DIR__ . '/verificar_sesion_api.php'; // Solo usuarios con sesión iniciada
requerir_rol('admin'); // Solo administradores pueden crear usuarios
header('Content-Type: application/json; charset=utf-8');

include('../ajax/db_connect.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Método no permitido']);
    exit();
}

$nombre_usuario = trim($_POST['nombre_usuario'] ?? '');
$email          = trim($_POST['email'] ?? '');
$contrasena     = $_POST['contrasena'] ?? '';
$confirmar      = $_POST['confirmar'] ?? '';
$rol            = $_POST['rol'] ?? 'usuario';

// Validaciones
if (mb_strlen($nombre_usuario) < 3 || mb_strlen($nombre_usuario) > 100) {
    echo json_encode(['status' => 'error', 'message' => 'El nombre de usuario debe tener entre 3 y 100 caracteres']);
    exit();
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 100) {
    echo json_encode(['status' => 'error', 'message' => 'El correo electrónico no es válido']);
    exit();
}
if (strlen($contrasena) < 8) {
    echo json_encode(['status' => 'error', 'message' => 'La contraseña debe tener al menos 8 caracteres']);
    exit();
}
if ($contrasena !== $confirmar) {
    echo json_encode(['status' => 'error', 'message' => 'Las contraseñas no coinciden']);
    exit();
}
if (!in_array($rol, ['usuario', 'admin'], true)) {
    echo json_encode(['status' => 'error', 'message' => 'Rol no válido']);
    exit();
}

// No repetir nombre de usuario ni correo (el login busca por nombre de usuario)
$check = $conn->prepare("SELECT nombre_usuario, email FROM usuarios WHERE nombre_usuario = ? OR email = ? LIMIT 1");
$check->bind_param("ss", $nombre_usuario, $email);
$check->execute();
$existente = $check->get_result()->fetch_assoc();
$check->close();

if ($existente) {
    $campo = strcasecmp($existente['nombre_usuario'], $nombre_usuario) === 0 ? 'nombre de usuario' : 'correo electrónico';
    echo json_encode(['status' => 'error', 'message' => "Ya existe un usuario con ese $campo"]);
    exit();
}

$hash = password_hash($contrasena, PASSWORD_DEFAULT);
$stmt = $conn->prepare("INSERT INTO usuarios (nombre_usuario, email, contrasena, rol) VALUES (?, ?, ?, ?)");
$stmt->bind_param("ssss", $nombre_usuario, $email, $hash, $rol);

if ($stmt->execute()) {
    echo json_encode(['status' => 'success', 'message' => 'Usuario registrado exitosamente']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Error al registrar el usuario.' . registrar_error_bd($stmt->error)]);
}

$stmt->close();
$conn->close();
