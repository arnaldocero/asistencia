$(document).ready(function() {

    
    
    // Obtener elementos del DOM

const modal = document.getElementById("myModal");

const openModalBtn = document.getElementById("openModalBtn");

const closeModalBtn = document.getElementsByClassName("close")[0];

const closeModalBtnasis  = document.getElementsByClassName("closeasis")[0];

const form = document.getElementById("registroForm");

// Modal de Asistencia

const modalAsistencia = document.getElementById("asistenciaModal");



// Abrir el modal cuando se hace clic en el botón

openModalBtn.onclick = function() {

    modal.style.display = "block";
    console.log($("#id_nino").val());
    document.getElementById("modalTitle").textContent = "Registro de Niño";
    document.getElementById("modalButton").textContent = "Registrar";
    modalAsistencia.style.display = "none"; // Asegúrate de cerrar el otro modal

    

}



// Cerrar el modal cuando se hace clic en la "x"

closeModalBtn.onclick = function() {

    modal.style.display = "none";

    

}



$("#registroForm").on("submit", function(e) {

    e.preventDefault();



    // Serialize form data

    var formData = $(this).serialize();
    console.log("ID Niño:", $("#idNino").val());
    // 🚀 Aquí ves todo el string que viaja al PHP
console.log("Datos enviados:", formData);

// También puedes ver como objeto (más cómodo):
console.log("Datos como objeto:", $(this).serializeArray());

console.log("ID Niño:", $("#idNino").val());
    var url = $("#idNino").val() ? "class/editar_ninos.php" : "class/insertar_nino.php";
    
    
    $.ajax({
    type: "POST",
    url: url,  // Cambia esta URL a la ruta de tu archivo PHP
    data: formData,
    dataType: "json",  // Espera una respuesta en formato JSON
    success: function(response) {
        if (response.status === 'success') {
            toastr.success("Se registró al niño correctamente.", "Miracle Max Says");  // Muestra el mensaje de éxito
            // 🔥 REGISTRAR ASISTENCIA AUTOMÁTICA
            const origen = $("#origenModal").val();
            const esNuevo = !$("#idNino").val();
        if (origen === "index" && esNuevo && response.id_nino && response.id_area) {
            registrarAsistenciaV2(response.id_nino, response.id_area);
        }

            modal.style.display = "none"; // Oculta el modal
            $("#registroForm")[0].reset();  // Resetea el formulario
            $("#idNino").val("");
            obtenerninos();  // Llama a tu función para obtener los niños
            $("#myModal").hide();
        } else if (response.status === 'error') {
             // Muestra el mensaje de error detallado
            toastr.warning("Error: " + response.message);
        }
    },
    error: function(xhr, status, error) {
        toastr.warning("Error de red o servidor: " + xhr.responseText + "\nStatus: " + status + "\nError: " + error);
        
    }
});

});

// Inicializar DataTables en la tabla una vez que esté completamente cargada
var table = $('#zero_config').DataTable({ 
    pageLength: 10,  // Mostrar 10 registros por página
    lengthChange: false, // No permitir cambiar el número de registros mostrados por página
    searching: true,  // Habilitar la búsqueda en DataTables
    ordering: false,   // Habilitar el ordenamiento por columnas
    language: {
        url: "//cdn.datatables.net/plug-ins/1.11.3/i18n/es_es.json" // Traducción al español
    }
});
// Inicializar DataTables en la tabla una vez que esté completamente cargada
var table = $('#zero_configAll').DataTable({ 
    pageLength: 10,  // Mostrar 10 registros por página
    lengthChange: false, // No permitir cambiar el número de registros mostrados por página
    searching: true,  // Habilitar la búsqueda en DataTables
    ordering: false,   // Habilitar el ordenamiento por columnas
    language: {
        url: "//cdn.datatables.net/plug-ins/1.11.3/i18n/es_es.json" // Traducción al español
    }
});

obtenerareas();

obtenerninos();

obtenerninosAll();



$('#asistenciaForm').on('submit', function(e) {

    e.preventDefault();

var formData = $(this).serialize(); // Serializar los datos del formulario



    $.ajax({

        url: 'class/registrar_asistencia.php', // Archivo PHP para procesar el registro

        type: 'POST',

        data: formData,

        success: function(response) {

            var result = JSON.parse(response);

            if (result.status === 'success') {
                toastr.success("Asistencia registrada correctamente.", "Miracle Max Says");

                $('#asistenciaModal').modal('hide');

                modalAsistencia.style.display = "none";

                let boton = $('#ingresarsalir' + result.id_nino);

                boton.removeClass('btn-warning').addClass('btn-success');

                boton.attr('onclick', 'registrarAsistencia(' + result.id_nino + ', 1)');

                boton.text('Ingresado'); // Cambia el texto del botón

                

            } else {
                toastr.warning('Error: ' + result.message);

            }

        },

        error: function() {
            toastr.warning('Error al registrar la asistencia');

        }

    });

  });

  // Abrir modal de Asistencia cuando se llama la función 'registrarAsistencia'

  window.registrarAsistencia = function(idnino,valoris) {

      if(valoris == 0){

        // Realizar la búsqueda del área asociada con el id_nino usando AJAX
        $.ajax({
            type: "POST",
            url: "class/buscar_area.php",  // Archivo PHP que devolverá el id_area
            data: { id_nino: idnino },  // Datos enviados (id del niño)
            dataType: "json",  // La respuesta será JSON
            success: function(response) {
                if (response.status === 'success') {
                    // Obtener el área del niño y mostrarla en el modal o donde sea necesario
                    let id_area = response.id_area;
                    registrarAsistenciaV2(idnino,id_area); // Registrar la asistencia automáticamente
                    
                } else {
                    toastr.warning('Error al obtener el área: ' + response.message);
                }
            },
            error: function(xhr, status, error) {
                toastr.warning('Error al obtener el área: ' + response.message);
            }
        });

      }else{

             // Realizar actualización del estado usando AJAX

             var valorcero='0';

             

             $.ajax({
                type: "POST",
                url: "class/actualizar_asistencia.php",  // Archivo PHP que actualizará el estado
                data: { id_nino: idnino, estado: valorcero },  // Datos que se enviarán
                dataType: "json",  // Asegúrate de que la respuesta sea tratada como JSON
                success: function(response) {
                      // Ver respuesta completa en consola para depuración
                    if (response.status === 'success') {
                        toastr.success("Estado actualizado correctamente " + response.estado, "Miracle Max Says");
                        // Actualiza el botón o la interfaz según sea necesario
                        $('#ingresarsalir' + idnino).removeClass('btn-success').addClass('btn-warning').text('Ingresar');
                        let boton = $('#ingresarsalir' + idnino);
                        boton.attr('onclick', 'registrarAsistencia(' + idnino + ', 0)');
                    } else {
                        toastr.warning('Error al actualizar el estado: ' + response.message);
                    }
                },
                error: function(xhr, status, error) {
                    toastr.warning('Error en la actualización: ' + xhr.responseText);
                }
            });
            

      }

    

}



closeModalBtnasis.onclick = function() {

    modalAsistencia.style.display = "none";

    

}
// Autocompletar para el nombre y apellido del niño
$("#nino").autocomplete({
    source: "class/buscar_nino.php", // Archivo que devuelve los nombres de los niños
    minLength: 2,
    select: function(event, ui) {
        // Cuando se selecciona un niño del autocompletar
        $('#id_nino').val(ui.item.id); // Guardar el ID del niño en un campo oculto
        registrarAsistenciaV2(ui.item.id, ui.item.id_area); // Registrar la asistencia automáticamente
    },
    response: function(event, ui) {
        // Puedes dejar este bloque vacío o agregar un mensaje visual si no hay resultados
        if (ui.content.length === 0) {
            console.log("No hay resultados disponibles.");
        }
    }
});
/*$("#nino").autocomplete({
    source: "class/buscar_nino.php", // Archivo que devuelve los nombres de los niños
    minLength: 2,
    select: function(event, ui) {
        // Cuando se selecciona un niño del autocompletar
        $('#id_nino').val(ui.item.id); // Guardar el ID del niño en un campo oculto
        registrarAsistenciaV2(ui.item.id,ui.item.id_area); // Registrar la asistencia automáticamente
        table.clear().draw();
    },
    response: function(event, ui) {
        // Si hay resultados, llenar la tabla en tiempo real con las sugerencias de autocompletar
        let table = $('#zero_config').DataTable();  // Inicializar o recuperar la instancia de DataTable
        table.clear();  // Limpiar las filas existentes antes de añadir nuevas
        if (ui.content.length > 0) {
        ui.content.forEach(function(nino) {
            // Asegurarnos de que todos los datos necesarios están presentes
            table.row.add([
                nino.id || 'N/A',  // Verificamos que los datos existen o usamos un valor por defecto
                nino.nombre || 'N/A',
                nino.apellido || 'N/A',
                nino.nombre_padre || 'N/A',
                '<button id="ingresarsalir' + nino.id + '" class="btn btn-sm btn-warning" style="color: white;" onclick="registrarAsistencia(' + nino.id + ', 0)">Ingresar</button>',
                '<button class="btn btn-sm btn-primary" onclick="editarNino(' + nino.id + ')">Editar</button> ' +
                '<button class="btn btn-sm btn-danger" onclick="eliminarNino(' + nino.id + ')">Eliminar</button>'
            ]);
            buscarAsistencia(nino.id);
        });
    }
        // Redibujar la tabla con los nuevos datos
        table.draw();
    }
});*/
 // Agregar un evento de entrada al campo de autocompletar
$("#nino").on("input", function() {
    if ($(this).val().length < 2) {
        obtenerninos();
    }
});

if (typeof qz !== "undefined") {
    connectPrinter();
} else {
    console.error("QZ Tray no está disponible. Asegúrate de que la biblioteca está cargada correctamente.");
}

reiniciarconsecutivos([1, 2, 3, 4]);
// Ejecutar una vez al día
const ultimaActualizacion = localStorage.getItem('ultimaActualizacionAreas');
const hoy = new Date().toISOString().slice(0, 10); // formato YYYY-MM-DD

if (ultimaActualizacion !== hoy) {
    $.ajax({
        url: 'class/actualizar_areas_por_edad.php',
        method: 'GET',
        success: function(response) {
            if (response.trim() === 'ok') {
                console.log('Áreas actualizadas según edad.');
            } else {
                console.warn('Mensaje del servidor:', response);
            }
            localStorage.setItem('ultimaActualizacionAreas', hoy);
        },
        error: function(xhr) {
            console.error('Error al actualizar áreas:', xhr.responseText);
        }
    });
}
$("#diagnostico").on("change", function () {
    if ($(this).is(":checked")) {
        $("#diagnosticosBox").slideDown();
        cargarDiagnosticos();
    } else {
        $("#diagnosticosBox").slideUp();
        $("#listaDiagnosticos").html("");
    }
});
});

function cargarDiagnosticos(diagnosticosMarcados = []) {
    $.ajax({
        url: "class/obtener_diagnosticos.php",
        type: "GET",
        dataType: "json",
        success: function (response) {

            if (response.status === "success") {

                let html = "";

                response.data.forEach(function (diag) {

                    const checked = diagnosticosMarcados.includes(
                        parseInt(diag.id_diagnostico)
                    ) ? "checked" : "";

                    html += `
                        <div class="form-check">
                            <input class="form-check-input"
                                   type="checkbox"
                                   name="diagnosticos[]"
                                   value="${diag.id_diagnostico}"
                                   ${checked}>
                            <label class="form-check-label">
                                ${diag.nombre}
                            </label>
                        </div>
                    `;
                });

                $("#listaDiagnosticos").html(html);
            }
        }
    });
}

// Función para registrar asistencia
function registrarAsistenciaV2(idNino,idarea) {
    $.ajax({
        type: "POST",
        url: "class/registrar_asistenciaV2.php",
        data: {
            id_nino: idNino,
            id_area: idarea, // Asignar el área correspondiente
            estado: '1' // Cambiar el estado a '1' al registrar
        },
        success: function(response) {
            const res = JSON.parse(response); // Asegúrate de analizar la respuesta
            $("#asistencia_mensaje").html(res.mensaje);
            toastr.success(res.mensaje);
            let boton = $('#ingresarsalir' + idNino);
            if (res.estado === '1') {
                if (!boton.hasClass('btn-success')) {
                boton.removeClass('btn-warning').addClass('btn-success');

                boton.attr('onclick', 'registrarAsistencia(' + idNino + ', 1)');

                boton.text('Ingresado'); // Cambia el texto del botón
                }
                $("#nino").val(""); // Limpia el campo
                $("#nino").autocomplete("close"); // Cierra las sugerencias si están abiertas
            }
            obtenerninos();
                // Imprimir el ticket con el nombre del niño y el consecutivo
                printTicket(res.nombre, res.apellido, res.consecutivo, res.codigoQR);
                // ✅ Validar la fecha de nacimiento
                 validarFechaNacimiento(idNino);
               
        },
        error: function(xhr, status, error) {
            $("#asistencia_mensaje").html(error);
            toastr.warning(
                "La asistencia ya está registrada para hoy."
              );
            
        }
    });
}

function obtenerareas(){

// Realiza la solicitud AJAX para obtener las áreas

$.ajax({

    type: "GET",

    url: "class/obtener_areas.php",  // URL del archivo PHP que genera las opciones

    success: function(data) {

        $('#clase').html(data);  // Inserta las opciones en el <select>

    },

    error: function(xhr, status, error) {

        
        toastr.warning("Error al cargar las áreas: " + xhr.responseText + "\nStatus: " + status + "\nError: " + error);

    }

});

}

// Realiza la solicitud AJAX para obtener los datos de los niños

function obtenerninos(){

    
       
    
        // Cargar los datos de los niños mediante AJAX y poblar la tabla
        $.ajax({
            type: "GET",
            url: "class/obtener_ninos.php",  // Archivo PHP que devuelve el JSON con los niños
            dataType: "json",  // Esperamos una respuesta en formato JSON
            success: function(response) {
                if (response.status === 'success') {
                    let ninos = response.data;  // Accedemos a los datos de los niños
    
                    // Limpiamos la tabla antes de llenarla de nuevo
                    let table = $('#zero_config').DataTable();
                    table.clear();
    
                    // Preparamos los datos en el formato que DataTables necesita
                    ninos.forEach(function(nino) {
                        table.row.add([
                            nino.id_nino,
                            nino.nombre,
                            nino.apellido,
                            nino.nombre_padre,
                            nino.telefono,
                            '<button id="ingresarsalir' + nino.id_nino + '" class="btn btn-sm btn-warning" style="color: white;" onclick="registrarAsistencia(' + nino.id_nino + ', 0)">Ingresar</button>',
                            '<button class="btn btn-sm btn-primary" onclick="printTicketAgain(' + nino.id_nino + ','+nino.id_asistencia+')" title="Volver a imprimir el sticker"><i class="fas fa-print"></i></button>' 
                        ]);
                        buscarAsistencia(nino.id_nino);
                    });
    
                    // Redibujamos la tabla con los nuevos datos
                    table.draw();
                    
                } else {
                     // Mensaje de error del servidor
                    toastr.warning(response.message);
                }
            },
            error: function(xhr, status, error) {
                toastr.warning("Error al cargar los datos: " + error);
            }
        });
    
}

function obtenerninosAll(){

    
       
    
    // Cargar los datos de los niños mediante AJAX y poblar la tabla
    $.ajax({
        type: "GET",
        url: "class/obtenertodosninos.php",  // Archivo PHP que devuelve el JSON con los niños
        dataType: "json",  // Esperamos una respuesta en formato JSON
        success: function(response) {
            if (response.status === 'success') {
                let ninos = response.data;  // Accedemos a los datos de los niños

                // Limpiamos la tabla antes de llenarla de nuevo
                let table = $('#zero_configAll').DataTable();
                table.clear();

                // Preparamos los datos en el formato que DataTables necesita
                ninos.forEach(function(nino) {
                    table.row.add([
                        nino.id_nino,
                        nino.nombre,
                        nino.apellido,
                        nino.nombre_padre,
                        '<button class="btn btn-sm btn-primary" onclick="editarNino(' + nino.id_nino + ')">Editar</button> ' +
                        '<button class="btn btn-sm btn-danger" onclick="eliminarNino(' + nino.id_nino + ')">Eliminar</button>'
                    ]);
                    buscarAsistencia(nino.id_nino);
                });

                // Redibujamos la tabla con los nuevos datos
                table.draw();
                
            } else {
                  // Mensaje de error del servidor
                toastr.warning(response.message);
            }
        },
        error: function(xhr, status, error) {
           
            toastr.warning("Error al cargar los datos: " + error);
        }
    });

}

function buscarAsistencia(id_nino) {
    
    $.ajax({

        type: "POST",

        url: "class/buscar_asistencia.php",  // Archivo PHP que procesa la solicitud

        data: { id_nino: id_nino },  // Enviamos el ID del niño como parámetro

        dataType: "json",  // Esperamos una respuesta JSON

        success: function(response) {

            if (response.status === 'success') {

                if (response.match) {

                    // Si las fechas coinciden
                 if(response.estado === '1'){
                    let boton = $('#ingresarsalir' + response.idnino);

                    boton.removeClass('btn-warning').addClass('btn-success');

                    boton.attr('onclick', 'registrarAsistencia(' + response.idnino + ', 1)');

                    boton.text('Ingresado'); // Cambia el texto del botón

                    console.log("id niño : " + response.idnino + " estado " + response.estado);
                 }
                 

                } else {

                   

                }

            } else {

                // Si ocurre un error en la búsqueda

                //alert('Error: ' + response.message);

            }

        },

        error: function(xhr, status, error) {
            toastr.warning('Error en la solicitud: ' + xhr.responseText);

        }

    });

}
// Editar niños 
function editarNino(idNino) {
    // Cambiar título y botón
    document.getElementById("modalTitle").textContent = "Editar Niño";
    document.getElementById("modalButton").textContent = "Actualizar";
    
    // Mostrar el modal
    document.getElementById("myModal").style.display = "block";

    $.ajax({
        type: "GET", // Asegúrate de que coincide con el método usado en PHP
        url: "class/getNino.php",
        data: { id: idNino },
        dataType: "json",
        success: function (response) {
            if (response.status === "success") {
                // Prellenar los campos del formulario
                $("#fname").val(response.data.nombre);
                $("#lname").val(response.data.apellido);
                $("#fechaNacimiento").val(response.data.fecha_nacimiento);
                $("#padrename").val(response.data.nombre_padre);
                $("#padrephone").val(response.data.telefono);
                // ✅ Nuevo: marcar el checkbox diagnóstico
                if (response.data.tiene_diagnostico == "1") {

                    $("#diagnostico").prop("checked", true);
                    $("#diagnosticosBox").show();
                
                    // AQUÍ está la clave 👇
                    cargarDiagnosticos(response.diagnosticos);
                
                } else {
                    $("#diagnosticosBox").hide();
                }
                $("#clase").val(response.data.idarea);

                // Campo oculto id_nino
                if ($("#id_nino").length === 0) {
                    $("#registroForm").append('<input type="hidden" id="id_nino" name="id_nino" value="' + idNino + '">');
                } else {
                    $("#idNino").val(idNino);
                }

                // Mostrar el modal
                $("#myModal").show();
            } else {
                
                toastr.warning("Error: " + response.message);
            }
        },
        error: function (xhr, status, error) {
            toastr.warning("Error de red o servidor editar niños: " + xhr.responseText);
        }
    });
}
// Función para imprimir el ticket en la impresora térmica
function connectPrinter() {
    if (typeof qz === "undefined") {
        console.error("QZ Tray no está definido. Asegúrate de que la biblioteca está cargada y QZ Tray esté en ejecución.");
        return;
    }

    qz.websocket.connect()
        .then(() => {
            console.log('Conectado a QZ Tray');
        })
        .catch((err) => {
            console.error('No se pudo conectar a QZ Tray:', err);
        });

        
}
function printTicketAgain(idNino, idAsistencia) {
    // Realizar una solicitud AJAX para obtener los datos del niño y la asistencia
    $.ajax({
        url: 'class/getAsistencianino.php', // Cambia esto por tu endpoint
        type: 'POST',
        data: { id_nino: idNino, id_asistencia: idAsistencia },
        success: function (response) {
            console.log('Respuesta del servidor:', response); // Asegúrate de ver el formato
            if (response.status === 'success') {
                const { nombre,apellido, consecutivo, codigo_qr } = response.data;
                // Llama a la función para imprimir el ticket
                printTicket(nombre,apellido, consecutivo, codigo_qr);
            } else {
                
                toastr.warning('Error al obtener los datos del niño.');
            }
        },
        error: function (xhr, status, error) {
            toastr.warning('Ocurrió un error al intentar obtener los datos del niño.' + error);
        }
    });
}

function printTicket(nombre, apellido, consecutivo, codigo_qr) {
    const config = qz.configs.create("4BARCODE 4B-2054TF", { copies: 1 });

    const fechaImpresion = new Date().toLocaleString('es-CO', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit'
    });

    const linea1 = nombre;
    const linea2 = apellido;

    const zplCommand = `
^XA
^CI28
^CF0,30

; Primera etiqueta (izquierda)
^FO10,20^A0N,30,30^FD Casa Kids ^FS
^FO200,20^A0N,50,50^FD ${consecutivo} ^FS
^FO10,70^A0N,50,50^FD ${linea1} ^FS
^FO10,120^A0N,50,50^FD ${linea2} ^FS
^FO10,180^A0N,30,30^FD ${fechaImpresion} ^FS


; Segunda etiqueta (derecha)
^FO400,20^A0N,30,30^FD Casa Kids ^FS
^FO600,20^A0N,50,50^FD ${consecutivo} ^FS
^FO400,70^A0N,50,50^FD ${linea1} ^FS
^FO400,120^A0N,50,50^FD ${linea2} ^FS
^FO400,180^A0N,30,30^FD ${fechaImpresion} ^FS


^XZ
`;

    qz.print(config, [{ type: 'raw', format: 'plain', data: zplCommand }])
        .then(() => console.log("Impresión completada"))
        .catch(err => console.error("Error de impresión:", err));
}
function validarFechaNacimiento(idNino) {
    $.ajax({
        type: "GET",
        url: "class/getNinofecha.php",
        data: { id: idNino },
        dataType: "json",
        success: function (response) {
            if (response.status === "success") {
                if (
                    response.fecha_nacimiento === "0000-00-00" || 
                    !response.nombre_padre || response.nombre_padre.trim() === "" ||
                    !response.telefono || response.telefono.trim() === ""
                ) {
                    // Mostrar alerta y abrir modal
                    toastr.warning("El niño no tiene datos completos. Actualízalos.");
                    editarNino(idNino);
                }
            } else {
                toastr.warning("No se pudo verificar la información del niño.");
            }
        },
        error: function () {
            toastr.warning("Error al verificar los datos del niño.");
        }
    });
}

/*function printTicket(nombre_completo, consecutivo, codigo_qr) {
    const config = qz.configs.create("4BARCODE4B-2054TF", { copies: 1 });

    const fechaImpresion = new Date().toLocaleString('es-CO', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit'
    });
    const zplLogo = `
    ^F550,5^GFA,20757,20757,51,,:::::::::::::::::::::::::::::::::::::::::::gS01,,:::::::gO02,,:::gN02,gT08K04,h04,,hO05gG0C,hN01gG02,gV01Q01gG02,,gW0608U01,,hK01,hK04,hK0C,gQ08,iG04,,i03,,::gP02,gP02R08T02K01,hM08gH04,,hT08,,i08,hT01,,gW04,gM08,hV018,,hW01,,:iH04,:gM02,,gM01K01,hL08,hT04,hL02,hV08,hS01,,:hH02,,gX08,gO01S08W08,,iP02,hI01001N01O01,gV08O08M018,hU08T02I08,gR018U08K01008,,::::::::i01F,hY07IF,hX03EFFB8,hX0KFC,hW01KFC,hW07KFE,S04hI0CKFE,R010FF8gY01EKFE,T08hG01KFDE,Q010100C08R038gI01BKFE,Q010070FC4S0C08gG01EJF7E,Q04J0CQ01I0C08gG01LFE,Q05g0C08gG01LFE,Q05L02O08K08gG01LFE,P025L08U08gG01LFE,P021L08P01J08gG01LFE,Q01U0201J04gG01LFE,Q02L06O0D1J04gG01LFE,P012L06N0998J058g01LFE,X08N02L02gH0LFE,X08M012L02gH0LFE,X08M03CL02gH0LFE,X08M018gO0LFE,gK0I1L04gH0LFE,gK0171L04gH0LFE,gK02FM04gH0LFE,gK04C6L04gH0LFE,X0CL03N0AgH0LFE,gK03gQ0LFE,gK01gQ0LFE,gJ02gR0LFE,gT08gH0LFE,gI01gS0LFE,hW0LFE,gI0698gK01FCI0LFE,X02J09N0418V0IFC00EKFE,X0AJ01N098V01JF80LFE,X0AS09W077IFC0LFE,X0AJ02N06W0FEFF7F0DKFE,V040AJ04N06V01B9JF9DKFE,V061AI018N06V03E7FFDNFE,W01A00138gK077EFF7BFDKFE,X02I08gL06F3FF7EF5KFE,X08Q062V01CSFE,X04Q099V01FBRFE,gG09N098V01FBRFE,gG07N064V03TFE,gG06N06W03TFE,X02006N01W07TFE,X02044N08W0UFE,gG01K01C0CW0FESFE,gM03EY0UFE,gM0338W01UFE,gN031W01UFE,gJ05gH01UFE,gJ0FJ0CW03UFE,X081J0FgH03UFE,X081J04gH03UFE,X099gM07UFE,gL0494X07JF6E06EDKFE,gL01CY07KF801FDKFE,gL09EY05JFB001BLFE,gL0B2Y05KFI0MFE,gL02g0DJF6I0F7KFE,hM0KFCI077KFE,hM0AJFCI06LFE,gL04g0AJF8I02LFE,gJ0114Y01BJF8I077KFE,gK048Y01BJF8I077KFE,gL08Y01KF8I07DKFE,gJ091g01KFJ07DKFE,gJ0CgG01KFJ07DKFE,gJ04Cg017JFJ07DKFE,hL015JFJ07DKFE,gJ09gG035JFJ07DKFE,gH0806gG03JFCJ07AKFE,gJ08gG03JFCJ07AKFE,gJ08gG03KFJ07BKFE,hL03KFJ07BKFE,gI08gH03KFJ0MFE,gH018gH03KFJ0MFE,gH018gH03BJFJ0MFE,gH018gH03AJFI01FEKFE,hL03KFI01MFE,:hL03KF8003MFE,:gJ04gG03KFC007MFE,gJ04gG03KF4007MFE,gJ08gG03KFE00NFE,gJ09gG03KFB039MFE,hL03LFCOFE,gK08g03VFE,:gK04g03VFE,hL03VFE,:hL01VFE,gJ0104Y01EUFE,hL01AUFE,gL08Y01AUFE,gL08g0BUFE,gL09g0DUFE,gL01g0DUFE,gL06CY0VFE,gL06CY07UFE,hM07UFEgJ018,hM02BEKFB2AKFE,hM03F7LFBAKFE,gN04X01F7LF9BKFEgG09004,gN04X013MF1LFEg0118I04,hM01DDJFB61LFEgG06,gN09Y0FDKFC1EKFEgG0CJ098,hN06KF781LFEg031J08,gN064X02LF81LFEgL0A,Y0481gL03BDFC7E01LFEY02L04,X0404gM01E9CFCE01C877FFCgL06,X0403gN0EJF801F7FE338Y01L064,X04006gM0378FF001F7F7FEg01L06,gG06gM01IFCI0KFCY022L06,X08006gN0F3FJ017IFgG04L03,gG06gN01F8K03FFgG089L018,gG03i033L018,gG02iN03,X04iH01N02,gQ04hO03N02,gQ07hN013N02,gP083hX02,gP0F08hN04O04,gP0708hL0108,gP03044hL0B1,gI0ChU01N0808,jO014,gI01hT03O0104,gI01hU03N03,X08J02hS0101P08,X08hY018O06,X08hX0CB8O04,X08T036hH01B8N04,X08iM0C08,X08K026P08gX01Q086,X08K026hO03P0196,X08L01P03gX08P0918,gK01P088gV011P0818,gK06P088,gK02P03C8gT011C,gV083gV0EQ06,S08I07AL0119P08gT01P0989,P0700700F86M066P01hK0186,P02204I01N026hI01T04,P01318003O01hK0CS08,Q08U0CQ01CgO01083Q06,R0ET04R02gO08007Q0C,h02gN010C04P0C,gY0CE6gM08618Q0C,gN098hR0C06,gN026gW01T0708,gN022S0CgH018E,gO01gX06S08,hI02gG03V08,hH03008gV03,hH060C08V01008301,iK048I08Q08,gP02019hJ08126,gP01081hJ01F8C,gR08gM03W01C8,gQ061CT08M0CgI06,gR01EV0CM08301T07C608,gR043T0300FF0FF3FY0F6,gS03T070FFM0ICV04,gS07T073FCFF3D3EC08CV0C,gU01h0C02,gT038h0308,gS01hI01,gU03,iV03,gU04h0C,gU01gM06,iI04,gV0C3C3gP01C3E,gV01C38gR0C08,gW0481gQ01,gW010CgP01138,gY07gR0E,gY01gP0878,hI0C,,hG038,h018K02V0E10018,hG01I0F02V07380C,hH0303CX038,hI08g07,hI0161X0838,,iJ08,hR0E0FFEK09C,hM011FFM0E0F,hV0F8,hO0101K028,,::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::hI0VF,hH01VF,g0EgG01VF8,K0141gT01VF,hH01VF,,::::::^FS`;
    const zplCommand = `^XA
    ^CF0,30

    ; Primera etiqueta (más a la izquierda)
    ^FB180,1,0,C
    ^FO1,20^FD Casa Kids ^FS
    ^CF0,35  ; Aumento de tamaño del nombre
    ^FO1,60^FD ${nombre_completo} ^FS
    ^CF0,35  ; Aumento de tamaño del consecutivo
    ^FO1,110^FD ${consecutivo} ^FS
    ^CF0,30
    ^FO1,160^FD ${fechaImpresion} ^FS

    ; Segunda etiqueta (derecha)
    ^FB180,1,0,C
    ^FO400,20^FD Casa Kids ^FS
    ^CF0,35  ; Aumento de tamaño del nombre
    ^FO400,60^FD ${nombre_completo} ^FS
    ^CF0,35  ; Aumento de tamaño del consecutivo
    ^FO400,110^FD ${consecutivo} ^FS
    ^CF0,30
    ^FO400,160^FD ${fechaImpresion} ^FS
    ^XZ`;

    qz.print(config, [{ type: 'raw', format: 'plain', data: zplCommand }])
        .then(() => console.log("ZPL impreso correctamente."))
        .catch(err => console.error("Error al imprimir ZPL:", err));
}*/

/*function printTicket() {
    const config = qz.configs.create("4BARCODE4B-2054TF", { copies: 2 });

    // Código ZPL con fuente más pequeña
    const zplCommand = `^XA
    ^CF0,20
    ^FO20,20^FD Casa Kids ^FS
    ^FO20,50^FD Niño: Juan Pérez ^FS
    ^FO20,80^FD Consecutivo: 1234 ^FS
    ^FO20,110^FD Fecha: 2025-03-13 ^FS
    ^FO20,140^FD ¡Gracias por asistir! ^FS
    ^XZ`;

    qz.print(config, [{ type: 'raw', format: 'plain', data: zplCommand }])
        .then(() => console.log("ZPL impreso correctamente."))
        .catch(err => console.error("Error al imprimir ZPL:", err));
}*/


// Función para convertir una imagen en Base64
function convertImageToBase64(url) {
    return new Promise((resolve, reject) => {
        const img = new Image();
        img.crossOrigin = "Anonymous"; // Habilita la conversión de imágenes de dominios externos
        img.src = url;
        img.onload = () => {
            const canvas = document.createElement('canvas');
            canvas.width = img.width;
            canvas.height = img.height;
            const ctx = canvas.getContext('2d');
            ctx.drawImage(img, 0, 0);
            resolve(canvas.toDataURL('image/png').split(',')[1]); // Devuelve solo el contenido Base64
        };
        img.onerror = (err) => reject(err);
    });
}

function eliminarNino(idnino){
    if (confirm("¿Estás seguro de que deseas eliminar este niño?")) {
        $.ajax({
            url: 'class/eliminar_nino.php',
            method: 'POST',
            data: { id_nino: idnino },
            success: function(response) {
                if (response.status === 'success') {
                    toastr.success(response.message);
                    location.reload();
                } else {
                    toastr.warning(
                        "Error en la solicitud AJAX." + response.message
                      );
                }
            },
            error: function(xhr) {
                
                toastr.warning(
                    "Error en la solicitud AJAX." + xhr.responseText
                  );
            }
        });
    }

}
$('#logout-btn').click(function () {
    $.ajax({
      url: 'class/logout.php',
      method: 'POST',
      success: function(response) {
        if (response === 'ok') {
          window.location.href = './authentication-login.php';
        } else {
          toastr.error("Error al cerrar sesión.");
        }
      },
      error: function(xhr, status, error) {
        toastr.warning("Error en la solicitud AJAX: " + xhr.responseText);
      }
    });
  });

  function reiniciarconsecutivos(ids) {
    $.ajax({
        url: 'class/reiniciarcosecutivos.php',
        method: 'POST',
        data: { ids: JSON.stringify(ids) },
        success: function(response) {
          if (response === 'ok') {
            
          } else {
            toastr.error("Error al reiniciar consecutivos.");
          }
        },
        error: function(xhr, status, error) {
          toastr.warning("Error en la solicitud AJAX: " + xhr.responseText);
        }
      });
      
  }







