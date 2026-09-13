<?php

$servername = "localhost"; // Cambia esto si usas otro servidor

$username = "medical6_adminCK"; // Usuario de la base de datos

$password = "Jtk0n7BmWs7P"; // Contraseña de la base de datos

$dbname = "medical6_asistenciaCK";



$conn = new mysqli($servername, $username, $password, $dbname);



// Verificar la conexión

if ($conn->connect_error) {

    die("Conexión fallida: " . $conn->connect_error);

}
// Establecer el conjunto de caracteres a UTF-8
if (!$conn->set_charset("utf8")) {
    die("Error al establecer el conjunto de caracteres: " . $conn->error);
}


?>