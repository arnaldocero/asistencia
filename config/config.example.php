<?php
/**
 * Plantilla de configuración.
 *
 * Copia este archivo como config/config.local.php y completa los datos
 * del entorno (local o producción). config.local.php NO se sube a git.
 */
return [
    'db_host'     => 'localhost',
    'db_user'     => 'root',
    'db_password' => '',
    'db_name'     => 'nombre_base_de_datos',
    'db_port'     => 3306,
    'timezone'    => 'America/Bogota',

    // SOLO PARA PRUEBAS EN LOCAL: simula la fecha de hoy para el aviso de cumpleaños
    // del primer domingo (ej. '2026-11-01'). Dejar vacío en producción.
    'fecha_prueba_cumpleanos' => '',
];
