<?php
// La contraseña proporcionada por el usuario
$contrasena = '00000';

// Encriptar la contraseña antes de almacenarla
$contrasena_encriptada = password_hash($contrasena, PASSWORD_DEFAULT);

// Aquí normalmente almacenarías $contrasena_encriptada en la base de datos
echo $contrasena_encriptada;
?>