$(document).ready(function() {
    function cargarAsistencia(turno, contenedor) {
        $.ajax({
            url: 'class/consultar_asistencia_por_areaservicionueves.php',
            method: 'GET',
            data: { turno: turno }, // enviamos el turno
            dataType: 'json',
            success: function(data) {
                const tablaHTML = generarTabla(data);
                $(`#${contenedor}`).html(tablaHTML);
            },
            error: function() {
                $(`#${contenedor}`).html('Error al cargar datos');
            }
        });
    }

    function generarTabla(data) {
        let tabla = '<table class="table table-bordered">';
        tabla += '<thead><tr><th>Área</th><th>Cantidad</th></tr></thead><tbody>';

        if (data.length === 0) {
            tabla += '<tr><td colspan="2">No hay datos</td></tr>';
        } else {
            data.forEach(item => {
                tabla += `<tr><td>${escaparHtml(item.nombre_area)}</td><td>${escaparHtml(item.cantidad)}</td></tr>`;
            });
        }

        tabla += '</tbody></table>';
        return tabla;
    }

    // Cargar datos en los tres bloques con diferentes horarios
    cargarAsistencia('mañana', 'tabla1');     // 08:30 a 10:40
    cargarAsistencia('mediodia', 'tabla2');   // 10:41 a 13:00
    cargarAsistencia('tarde', 'tabla3');      // 15:30 a 18:00
});
// Botón para actualizar los 3 turnos
$('#actualizarTurnos').click(function () {
    cargarAsistencia('mañana', 'tabla1');
    cargarAsistencia('mediodia', 'tabla2');
    cargarAsistencia('tarde', 'tabla3');
});
// Buscar asistencia por fecha y turno
$('#buscarAsistencia').click(function () {
    const fecha = $('#fechaBusqueda').val();

    if (!fecha) {
        alert("Por favor selecciona una fecha.");
        return;
    }

    cargarAsistenciab('mañana', 'tabla4',fecha);     // 08:30 a 10:40
    cargarAsistenciab('mediodia', 'tabla5',fecha);   // 10:41 a 13:00
    cargarAsistenciab('tarde', 'tabla6',fecha); 

   
});
function cargarAsistenciab(turno, contenedor,fecha){
    $.ajax({
        url: 'class/consultar_asistencia_por_areaservicionueves.php',
        method: 'GET',
        data: { turno: turno, fecha: fecha },
        dataType: 'json',
        success: function (data) {
            const tablaHTML = generarTabla1(data);
            $(`#${contenedor}`).html(tablaHTML);
        },
        error: function () {
            $(`#${contenedor}`).html('Error al cargar datos');
        }
    });
}
function generarTabla1(data) {
    let tabla = '<table class="table table-bordered">';
    tabla += '<thead><tr><th>Área</th><th>Cantidad</th></tr></thead><tbody>';

    if (data.length === 0) {
        tabla += '<tr><td colspan="2">No hay datos</td></tr>';
    } else {
        data.forEach(item => {
            tabla += `<tr><td>${escaparHtml(item.nombre_area)}</td><td>${escaparHtml(item.cantidad)}</td></tr>`;
        });
    }

    tabla += '</tbody></table>';
    return tabla;
}


