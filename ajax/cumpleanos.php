<?php
// Cumpleaños del mes anterior: el primer domingo de cada mes se celebra a los niños
// que cumplieron años durante el mes anterior (ej. domingo 1 de noviembre -> octubre).
require_once __DIR__ . '/areas.php';

const MESES = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio',
               'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];

// Fecha de "hoy" para esta verificación. En local se puede simular otro día con
// 'fecha_prueba_cumpleanos' => 'AAAA-MM-DD' en config/config.local.php (nunca en producción).
function fecha_referencia_cumpleanos()
{
    $prueba = $GLOBALS['config']['fecha_prueba_cumpleanos'] ?? '';
    if ($prueba !== '' && DateTime::createFromFormat('!Y-m-d', $prueba)) {
        return DateTime::createFromFormat('!Y-m-d', $prueba);
    }
    return new DateTime('today');
}

// true si la fecha es el primer domingo de su mes
function es_primer_domingo(DateTime $fecha)
{
    return $fecha->format('N') === '7' && (int)$fecha->format('j') <= 7;
}

// ['cumplio' => bool, 'mes' => 'octubre', 'edad' => 6]
// cumplio = hoy es el primer domingo del mes y el niño cumplió años el mes anterior.
function info_cumpleanos($fecha_nacimiento)
{
    $hoy = fecha_referencia_cumpleanos();
    $resultado = ['cumplio' => false, 'mes' => null, 'edad' => null];

    if (!es_primer_domingo($hoy)) {
        return $resultado;
    }
    $nacimiento = DateTime::createFromFormat('!Y-m-d', (string)$fecha_nacimiento);
    if (!$nacimiento || $nacimiento->format('Y-m-d') !== $fecha_nacimiento || $nacimiento > $hoy) {
        return $resultado; // fecha inválida (ej. 0000-00-00): no se puede saber
    }

    $mes_anterior = (int)(clone $hoy)->modify('first day of last month')->format('n');
    if ((int)$nacimiento->format('n') === $mes_anterior) {
        $resultado['cumplio'] = true;
        $resultado['mes'] = MESES[$mes_anterior - 1];
        $resultado['edad'] = $hoy->diff($nacimiento)->y;
    }
    return $resultado;
}
