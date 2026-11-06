var ultimoIdAsistencia = 0;
var sincronizando = false;
var tablaCargada = false;      // la sincronización empieza cuando ya se conoce el último registro del día
var asistenciasImpresas = {};   // id_asistencia ya impresos en esta pantalla (evita stickers duplicados)

$(document).ready(function() {

    // Elementos del DOM
    const modal = document.getElementById("myModal");
    const openModalBtn = document.getElementById("openModalBtn");
    const closeModalBtn = document.getElementsByClassName("close")[0];
    const closeModalBtnasis = document.getElementsByClassName("closeasis")[0];

    const modalAsistencia = document.getElementById("asistenciaModal");

    openModalBtn.onclick = function() {
        modal.style.display = "block";
        document.getElementById("modalTitle").textContent = "Registro de Niño";
        document.getElementById("modalButton").textContent = "Registrar";
        modalAsistencia.style.display = "none";
    }

    closeModalBtn.onclick = function() {
        modal.style.display = "none";
    }

    $("#registroForm").on("submit", function(e) {
        e.preventDefault();
        var formData = $(this).serialize();
        var url = $("#idNino").val() ? "class/editar_ninos.php" : "class/insertar_nino.php";
        
        $.ajax({
            type: "POST",
            url: url,
            data: formData,
            dataType: "json",
            success: function(response) {
                if (response.status === 'success') {
                    toastr.success("Se registró al niño correctamente.", "Sistema");
                    const origen = $("#origenModal").val();
                    const esNuevo = !$("#idNino").val();
                    if (origen === "index" && esNuevo && response.id_nino && response.id_area) {
                        registrarAsistenciaV2(response.id_nino, response.id_area);
                    }

                    modal.style.display = "none";
                    $("#registroForm")[0].reset();
                    $("#idNino").val("");
                    obtenerninos();
                    $("#myModal").hide();
                } else if (response.status === 'error') {
                    toastr.warning(response.message);
                }
            },
            error: function(xhr, status, error) {
                toastr.warning("Error de red o servidor: " + xhr.responseText);
            }
        });
    });

    // DataTables
    $('#zero_config').DataTable({ 
        pageLength: 10,
        lengthChange: false,
        searching: true,
        ordering: false,
        language: {
            url: "//cdn.datatables.net/plug-ins/1.11.3/i18n/es_es.json"
        }
    });

    $('#zero_configAll').DataTable({ 
        pageLength: 10,
        lengthChange: false,
        searching: true,
        ordering: false,
        language: {
            url: "//cdn.datatables.net/plug-ins/1.11.3/i18n/es_es.json"
        }
    });

    obtenerareas();
    obtenerninos();
    obtenerninosAll();

    // 🚀 SINCRONIZACIÓN AUTOMÁTICA CELULAR -> COMPUTADOR CADA 4 SEGUNDOS
    // Los equipos sin impresora (celulares) pausan la consulta mientras la pestaña está oculta.
    // El equipo con impresora nunca pausa: es el que imprime los stickers de los celulares.
    setInterval(function() {
        if (document.hidden && !impresoraConectada()) return;
        sincronizarConCelular();
    }, 4000);

    // Al volver a la pestaña se sincroniza de inmediato, sin esperar al siguiente ciclo
    document.addEventListener("visibilitychange", function() {
        if (!document.hidden) sincronizarConCelular();
    });

    window.registrarAsistencia = function(idnino, valoris) {
        if(valoris == 0) {
            $.ajax({
                type: "POST",
                url: "class/buscar_area.php",
                data: { id_nino: idnino },
                dataType: "json",
                success: function(response) {
                    if (response.status === 'success') {
                        registrarAsistenciaV2(idnino, response.id_area);
                    } else {
                        toastr.warning('Error al obtener el área: ' + response.message);
                    }
                },
                error: function(xhr, status, error) {
                    toastr.warning('Error: ' + error);
                }
            });
        } else {
            $.ajax({
                type: "POST",
                url: "class/actualizar_asistencia.php",
                data: { id_nino: idnino, estado: '0' },
                dataType: "json",
                success: function(response) {
                    if (response.status === 'success') {
                        toastr.success("Estado actualizado correctamente", "Sistema");
                        let boton = $('#ingresarsalir' + idnino);
                        boton.removeClass('btn-success').addClass('btn-warning').text('Ingresar');
                        boton.attr('onclick', 'registrarAsistencia(' + idnino + ', 0)');
                        obtenerninos();
                    } else {
                        toastr.warning('Error al actualizar el estado: ' + response.message);
                    }
                },
                error: function(xhr) {
                    toastr.warning('Error en la actualización: ' + xhr.responseText);
                }
            });
        }
    }

    closeModalBtnasis.onclick = function() {
        modalAsistencia.style.display = "none";
    }

    // Autocompletar
    $("#nino").autocomplete({
        source: "class/buscar_nino.php",
        minLength: 2,
        select: function(event, ui) {
            $('#id_nino').val(ui.item.id);
            registrarAsistenciaV2(ui.item.id, ui.item.id_area);
        }
    });

    $("#nino").on("input", function() {
        if ($(this).val().length < 2) {
            obtenerninos();
        }
    });

    if (typeof qz !== "undefined") {
        connectPrinter();
    }
});

// Función de sincronización en tiempo real
function sincronizarConCelular() {
    if (sincronizando || !tablaCargada) return;
    sincronizando = true;

    $.ajax({
        type: "GET",
        url: "class/consultar_nuevas_asistencias.php",
        data: { ultimo_id: ultimoIdAsistencia },
        dataType: "json",
        success: function(res) {
            if (res.status === 'success' && res.hay_nuevos) {
                res.nuevos.forEach(function(item) {
                    toastr.info("Nuevo registro desde celular: " + item.nombre + " " + item.apellido, "Asistencia");
                    
                    // Si este equipo tiene la impresora conectada, imprime automáticamente el sticker del celular
                    if (impresoraConectada() && !asistenciasImpresas[item.id_asistencia]) {
                        asistenciasImpresas[item.id_asistencia] = true;
                        printTicket(item.nombre, item.apellido, item.consecutivo, item.codigo_qr);
                    }
                    
                    if (parseInt(item.id_asistencia) > ultimoIdAsistencia) {
                        ultimoIdAsistencia = parseInt(item.id_asistencia);
                    }
                });
                obtenerninos(); // Recarga la tabla en la pantalla del computador
            }
            sincronizando = false;
        },
        error: function() {
            sincronizando = false;
        }
    });
}

function registrarAsistenciaV2(idNino, idarea) {
    $.ajax({
        type: "POST",
        url: "class/registrar_asistenciaV2.php",
        data: {
            id_nino: idNino,
            id_area: idarea
        },
        dataType: "json",
        success: function(res) {
            $("#nino").val("");
            $("#nino").autocomplete("close");

            if (res.status === 'success') {
                toastr.success(res.mensaje);
                $("#asistencia_mensaje").text(res.mensaje);
                asistenciasImpresas[res.id_asistencia] = true; // la sincronización no lo vuelve a imprimir
                printTicket(res.nombre, res.apellido, res.consecutivo, res.codigoQR);
                validarFechaNacimiento(idNino);
            } else if (res.status === 'already_registered') {
                toastr.warning(res.mensaje, "Ya Registrado");
            } else {
                toastr.error(res.mensaje || "Error al registrar asistencia");
            }

            obtenerninos();
        },
        error: function() {
            toastr.error("Error de conexión al procesar la asistencia.");
        }
    });
}

function obtenerareas() {
    $.ajax({
        type: "GET",
        url: "class/obtener_areas.php",
        success: function(data) {
            $('#clase').html(data);
        }
    });
}

function obtenerninos() {
    $.ajax({
        type: "GET",
        url: "class/obtener_ninos.php",
        dataType: "json",
        success: function(response) {
            let table = $('#zero_config').DataTable();
            table.clear();

            if (response.status === 'success' && response.data) {
                let ninos = response.data;
                ninos.forEach(function(nino) {
                    if (parseInt(nino.id_asistencia) > ultimoIdAsistencia) {
                        ultimoIdAsistencia = parseInt(nino.id_asistencia);
                    }

                    table.row.add([
                        nino.id_nino,
                        escaparHtml(nino.nombre),
                        escaparHtml(nino.apellido),
                        escaparHtml(nino.nombre_padre),
                        escaparHtml(nino.telefono),
                        '<button id="ingresarsalir' + nino.id_nino + '" class="btn btn-sm btn-success" style="color: white;" onclick="registrarAsistencia(' + nino.id_nino + ', 1)">Ingresado</button>',
                        '<button class="btn btn-sm btn-primary" onclick="printTicketAgain(' + nino.id_nino + ',' + nino.id_asistencia + ')" title="Imprimir sticker"><i class="fas fa-print"></i></button>'
                    ]);
                });
                // Ya se sabe cuál es el último registro del día (aunque sea ninguno):
                // la sincronización puede empezar sin reimprimir lo anterior
                tablaCargada = true;
            }
            table.draw();
        }
    });
}

function obtenerninosAll() {
    $.ajax({
        type: "GET",
        url: "class/obtenertodosninos.php",
        dataType: "json",
        success: function(response) {
            let table = $('#zero_configAll').DataTable();
            table.clear();

            if (response.status === 'success' && response.data) {
                response.data.forEach(function(nino) {
                    table.row.add([
                        nino.id_nino,
                        escaparHtml(nino.nombre),
                        escaparHtml(nino.apellido),
                        escaparHtml(nino.nombre_padre),
                        '<button class="btn btn-sm btn-primary" onclick="editarNino(' + nino.id_nino + ')">Editar</button> ' +
                        '<button class="btn btn-sm btn-danger" onclick="eliminarNino(' + nino.id_nino + ')">Eliminar</button>'
                    ]);
                });
            }
            table.draw();
        }
    });
}

function eliminarNino(idNino) {
    if (!confirm("¿Eliminar este niño? Dejará de aparecer en listados y búsquedas; su historial de asistencia se conserva.")) {
        return;
    }
    $.ajax({
        type: "POST",
        url: "class/eliminar_nino.php",
        data: { id_nino: idNino },
        dataType: "json",
        success: function(response) {
            if (response.status === 'success') {
                toastr.success(response.message, "Sistema");
                obtenerninosAll();
            } else {
                toastr.warning(response.message);
            }
        }
        // 401/403 (sin sesión o sin rol admin) los maneja dist/js/sesion.js
    });
}

// true si este equipo tiene QZ Tray conectado (es el que imprime los stickers)
function impresoraConectada() {
    return typeof qz !== "undefined" && qz.websocket && qz.websocket.isActive();
}

function connectPrinter() {
    if (typeof qz === "undefined") return;
    qz.websocket.connect()
        .then(() => console.log('Conectado a QZ Tray'))
        .catch(err => console.error('Error QZ Tray:', err));
}

function printTicketAgain(idNino, idAsistencia) {
    $.ajax({
        url: 'class/getAsistencianino.php',
        type: 'POST',
        data: { id_nino: idNino, id_asistencia: idAsistencia },
        success: function(response) {
            if (response.status === 'success') {
                const { nombre, apellido, consecutivo, codigo_qr } = response.data;
                printTicket(nombre, apellido, consecutivo, codigo_qr);
            }
        }
    });
}

function printTicket(nombre, apellido, consecutivo, codigo_qr) {
    if (typeof qz === "undefined" || !qz.websocket.isActive()) return;
    const config = qz.configs.create("4BARCODE 4B-2054TF", { copies: 1 });
    const fechaImpresion = new Date().toLocaleString('es-CO', {
        year: 'numeric', month: '2-digit', day: '2-digit',
        hour: '2-digit', minute: '2-digit', second: '2-digit'
    });

    const zplCommand = `
^XA
^CI28
^CF0,30
^FO10,20^A0N,30,30^FD Casa Kids ^FS
^FO200,20^A0N,50,50^FD ${consecutivo} ^FS
^FO10,70^A0N,50,50^FD ${nombre} ^FS
^FO10,120^A0N,50,50^FD ${apellido} ^FS
^FO10,180^A0N,30,30^FD ${fechaImpresion} ^FS

^FO400,20^A0N,30,30^FD Casa Kids ^FS
^FO600,20^A0N,50,50^FD ${consecutivo} ^FS
^FO400,70^A0N,50,50^FD ${nombre} ^FS
^FO400,120^A0N,50,50^FD ${apellido} ^FS
^FO400,180^A0N,30,30^FD ${fechaImpresion} ^FS
^XZ
`;

    qz.print(config, [{ type: 'raw', format: 'plain', data: zplCommand }])
        .then(() => console.log("Impreso con éxito"))
        .catch(err => console.error("Error al imprimir:", err));
}

function validarFechaNacimiento(idNino) {
    $.ajax({
        type: "GET",
        url: "class/getNinofecha.php",
        data: { id: idNino },
        dataType: "json",
        success: function(response) {
            if (response.status === "success") {
                if (response.fecha_nacimiento === "0000-00-00" || !response.nombre_padre || !response.telefono) {
                    toastr.warning("El niño no tiene datos completos. Actualízalos.");
                    editarNino(idNino);
                }
            }
        }
    });
}