<?php
include('../ajax/db_connect.php');

$term = $_GET['term'];

$sql = "SELECT id_nino, nombre, apellido, nombre_padre, idarea FROM test_ninos WHERE nombre LIKE ? OR apellido LIKE ?";
$stmt = $conn->prepare($sql);
$searchTerm = "%".$term."%";
$stmt->bind_param("ss", $searchTerm, $searchTerm);
$stmt->execute();
$result = $stmt->get_result();

$children = [];

while ($row = $result->fetch_assoc()) {
    // Mapear idarea al nombre del área
    switch ($row['idarea']) {
        case 1: $nombre_area = 'Área de 3 a 4 años'; break;
        case 2: $nombre_area = 'Área de 5 a 6 años'; break;
        case 3: $nombre_area = 'Área de 7 a 8 años'; break;
        case 4: $nombre_area = 'Área de 9 a 11 años'; break;
        default: $nombre_area = 'Área desconocida';
    }

    $nombreCompleto = $row['nombre'] . ' ' . $row['apellido'] . ' - ' . $nombre_area;

    $children[] = [
        'id' => $row['id_nino'], 
        'label' => $nombreCompleto,
        'nombre' => $row['nombre'],
        'apellido' => $row['apellido'],
        'nombre_padre' => $row['nombre_padre'],
        'id_area' => $row['idarea']
    ];
}

echo json_encode($children);
