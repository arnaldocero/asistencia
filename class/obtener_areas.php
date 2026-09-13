<?php

include('../ajax/db_connect.php');

$sql = "SELECT id_area, nombre_area FROM tbl_areas";

$result = $conn->query($sql);



if ($result->num_rows > 0) {

    // Itera sobre cada fila del resultado y genera las opciones

    while($row = $result->fetch_assoc()) {

        echo '<option value="' . $row["id_area"] . '">' . $row["nombre_area"] . '</option>';

    }

} else {

    echo '<option value="">No hay áreas disponibles</option>';

}



$conn->close();

?>