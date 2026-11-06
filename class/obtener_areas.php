<?php
require_once __DIR__ . '/verificar_sesion_api.php'; // Solo usuarios con sesión iniciada

include('../ajax/db_connect.php');

$sql = "SELECT id_area, nombre_area FROM tbl_areas";

$result = $conn->query($sql);



if ($result->num_rows > 0) {

    // Itera sobre cada fila del resultado y genera las opciones

    while($row = $result->fetch_assoc()) {

        echo '<option value="' . (int)$row["id_area"] . '">' . htmlspecialchars($row["nombre_area"], ENT_QUOTES, 'UTF-8') . '</option>';

    }

} else {

    echo '<option value="">No hay áreas disponibles</option>';

}



$conn->close();

?>