<?php
// Nunca mostrar errores de PHP al navegador; quedan en el log del servidor
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
ini_set('log_errors', 1);

// Guarda en el log el detalle técnico de un error de MySQL y devuelve '' para que
// ese detalle (tablas, columnas, consultas) no llegue al usuario.
function registrar_error_bd($detalle)
{
    error_log(basename($_SERVER['SCRIPT_NAME'] ?? 'cli') . ': ' . $detalle);
    return '';
}

// Las credenciales se leen de config/config.local.php (excluido de git).
// Para un entorno nuevo, copia config/config.example.php como config.local.php.
$config_file = __DIR__ . '/../config/config.local.php';

if (!file_exists($config_file)) {
    error_log('Falta el archivo de configuración: ' . $config_file);
    die("Error de configuración del servidor.");
}

$config = require $config_file;

date_default_timezone_set($config['timezone'] ?? 'America/Bogota');

$conn = new mysqli(
    $config['db_host'],
    $config['db_user'],
    $config['db_password'],
    $config['db_name'],
    $config['db_port'] ?? 3306
);

// Verificar la conexión (el detalle va al log, no al navegador)
if ($conn->connect_error) {
    error_log("Conexión fallida: " . $conn->connect_error);
    die("Error al conectar con la base de datos.");
}

// Establecer el conjunto de caracteres a UTF-8
if (!$conn->set_charset("utf8mb4")) {
    error_log("Error al establecer el conjunto de caracteres: " . $conn->error);
    die("Error al conectar con la base de datos.");
}

// Misma zona horaria en MySQL que en PHP, para que CURDATE()/NOW() y date() den el mismo "hoy".
// Se usa el desfase (ej. -05:00) porque el hosting puede no tener cargadas las zonas con nombre.
$offset = (new DateTime())->format('P');
if (!$conn->query("SET time_zone = '$offset'")) {
    error_log("Error al establecer la zona horaria de MySQL: " . $conn->error);
}
