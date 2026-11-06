<?php
session_start();
include('../ajax/db_connect.php'); // Archivo que conecta a la base de datos

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre_usuario = $_POST['nombre_usuario'];
    $contrasena = $_POST['contrasena'];

    // Prepara la consulta SQL para evitar inyecciones SQL
    $stmt = $conn->prepare("SELECT * FROM usuarios WHERE nombre_usuario = ?");
    $stmt->bind_param("s", $nombre_usuario);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        
        // Verifica la contraseña
        if (password_verify($contrasena, $row['contrasena'])) {
            // Nuevo ID de sesión al autenticarse (evita fijación de sesión) y nuevo token CSRF
            session_regenerate_id(true);
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

            // Inicia sesión
            $_SESSION['usuario_id'] = $row['id_usuario'];
            $_SESSION['usuario_nombre'] = $row['nombre_usuario'];
            $_SESSION['usuario_rol'] = $row['rol'];
            echo 'success';
        } else {
            echo 'error_contrasena';
        }
    } else {
        echo 'error_usuario';
    }
}
?>