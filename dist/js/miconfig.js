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
        limpiarFormularioNino();
        modal.style.display = "block";
        document.getElementById("modalTitle").textContent = "Registro de Niño";
        document.getElementById("modalButton").textContent = "Registrar";
        modalAsistencia.style.display = "none";
    }

    closeModalBtn.onclick = function() {
        cerrarModal();
    }

    // Mostrar u ocultar la lista de diagnósticos según el interruptor
    $("#diagnostico").on("change", function() {
        if ($(this).is(":checked")) {
            $("#diagnosticosBox").slideDown();
            cargarDiagnosticos();
        } else {
            $("#diagnosticosBox").slideUp();
            $("#listaDiagnosticos").html("");
        }
    });

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
                    toastr.success(response.message || "Se registró al niño correctamente.", "Sistema");
                    const origen = $("#origenModal").val();
                    const esNuevo = !$("#idNino").val();
                    if (origen === "index" && esNuevo && response.id_nino && response.id_area) {
                        registrarAsistenciaV2(response.id_nino, response.id_area);
                    }

                    cerrarModal();
                    obtenerninos();
                    obtenerninosAll();
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
    actualizarAreasUnaVezAlDia();

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
                    if (item.cumplio) {
                        avisoCumpleanos(item.nombre + " " + item.apellido, item.mes_cumple, item.edad_cumplida);
                    }

                    // Si este equipo tiene la impresora conectada, imprime automáticamente el sticker del celular
                    if (impresoraConectada() && !asistenciasImpresas[item.id_asistencia]) {
                        asistenciasImpresas[item.id_asistencia] = true;
                        printTicket(item.nombre, item.apellido, item.consecutivo, item.codigo_qr, item.cumplio);
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
                if (res.cumplio) {
                    avisoCumpleanos(res.nombre_nino, res.mes_cumple, res.edad_cumplida);
                }
                asistenciasImpresas[res.id_asistencia] = true; // la sincronización no lo vuelve a imprimir
                printTicket(res.nombre, res.apellido, res.consecutivo, res.codigoQR, res.cumplio);
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

// Lista completa de niños (pantalla "Lista de niños"). Con soloFechasInvalidas = true
// muestra solo los niños cuya fecha de nacimiento hay que corregir, con el motivo.
var todosLosNinos = [];
var soloFechasInvalidas = false;

function obtenerninosAll() {
    $.ajax({
        type: "GET",
        url: "class/obtenertodosninos.php",
        dataType: "json",
        success: function(response) {
            todosLosNinos = (response.status === 'success' && response.data) ? response.data : [];
            dibujarListaNinos();
        }
    });
}

function dibujarListaNinos() {
    if (!$('#zero_configAll').length) return;
    const table = $('#zero_configAll').DataTable();
    table.clear();

    const porCorregir = todosLosNinos.filter(n => n.fecha_invalida);
    if (porCorregir.length === 0) soloFechasInvalidas = false;

    // Aviso con la cantidad y el botón para filtrar
    $("#avisoFechas").toggleClass("d-none", porCorregir.length === 0);
    $("#avisoFechasTexto").text(soloFechasInvalidas
        ? "Mostrando los " + porCorregir.length + " niños con fecha de nacimiento por corregir. Usa Editar para corregirla."
        : porCorregir.length + " niños tienen la fecha de nacimiento por corregir (sin fecha, imposible o sin área por edad).");
    $("#btnFiltroFechas").text(soloFechasInvalidas ? "Ver todos los niños" : "Ver solo esos niños");

    (soloFechasInvalidas ? porCorregir : todosLosNinos).forEach(function(nino) {
        // En modo corrección, bajo el nombre se ve el motivo y bajo el acudiente su teléfono
        const nombre = escaparHtml(nino.nombre) + (soloFechasInvalidas
            ? '<br><small class="text-danger">' + escaparHtml(nino.fecha_invalida) + '</small>' : '');
        const acudiente = escaparHtml(nino.nombre_padre) + (soloFechasInvalidas && nino.telefono
            ? '<br><small class="text-muted">Tel. ' + escaparHtml(nino.telefono) + '</small>' : '');
        table.row.add([
            nino.id_nino,
            nombre,
            escaparHtml(nino.apellido),
            acudiente,
            '<button class="btn btn-sm btn-primary" onclick="editarNino(' + nino.id_nino + ')">Editar</button> ' +
            '<button class="btn btn-sm btn-danger" onclick="eliminarNino(' + nino.id_nino + ')">Eliminar</button>'
        ]);
    });
    table.draw();
}

$(document).on("click", "#btnFiltroFechas", function() {
    soloFechasInvalidas = !soloFechasInvalidas;
    dibujarListaNinos();
});

// Deja el formulario del niño vacío y listo para un registro nuevo
function limpiarFormularioNino() {
    $("#registroForm")[0].reset();
    $("#idNino").val("");
    $("#diagnosticosBox").hide();
    $("#listaDiagnosticos").html("");
}

function cerrarModal() {
    $("#myModal").hide();
    limpiarFormularioNino();
}

// Carga los diagnósticos disponibles y marca los que ya tiene el niño
function cargarDiagnosticos(diagnosticosMarcados = []) {
    const marcados = diagnosticosMarcados.map(Number);
    $.ajax({
        url: "class/obtener_diagnosticos.php",
        type: "GET",
        dataType: "json",
        success: function(response) {
            if (response.status !== "success") return;
            let html = "";
            response.data.forEach(function(diag) {
                const id = parseInt(diag.id_diagnostico);
                html += '<div class="form-check">' +
                    '<input class="form-check-input" type="checkbox" name="diagnosticos[]" id="diag' + id + '" value="' + id + '"' + (marcados.includes(id) ? ' checked' : '') + '>' +
                    '<label class="form-check-label" for="diag' + id + '">' + escaparHtml(diag.nombre) + '</label>' +
                    '</div>';
            });
            $("#listaDiagnosticos").html(html);
        }
    });
}

// Abre el modal con los datos del niño para editarlos
function editarNino(idNino) {
    limpiarFormularioNino();
    document.getElementById("modalTitle").textContent = "Editar Niño";
    document.getElementById("modalButton").textContent = "Actualizar";

    $.ajax({
        type: "GET",
        url: "class/getNino.php",
        data: { id: idNino },
        dataType: "json",
        success: function(response) {
            if (response.status !== "success") {
                toastr.warning(response.message || "No se pudieron cargar los datos del niño");
                return;
            }
            const nino = response.data;
            $("#idNino").val(idNino);
            $("#fname").val(nino.nombre);
            $("#lname").val(nino.apellido);
            // Una fecha 0000-00-00 se deja vacía para que la completen
            $("#fechaNacimiento").val(nino.fecha_nacimiento && nino.fecha_nacimiento !== "0000-00-00" ? nino.fecha_nacimiento : "");
            $("#padrename").val(nino.nombre_padre);
            $("#padrephone").val(nino.telefono);

            if (nino.tiene_diagnostico == "1") {
                $("#diagnostico").prop("checked", true);
                $("#diagnosticosBox").show();
                cargarDiagnosticos(response.diagnosticos || []);
            }

            $("#myModal").show();
        },
        error: function(xhr) {
            if (xhr.status !== 401 && xhr.status !== 403) {
                toastr.warning("Error al cargar los datos del niño.");
            }
        }
    });
}

// Recalcula el área de los niños según su edad, como mucho una vez al día por equipo
function actualizarAreasUnaVezAlDia() {
    const hoy = new Date().toLocaleDateString("en-CA"); // YYYY-MM-DD en hora local
    let ultima = null;
    try { ultima = localStorage.getItem("ultimaActualizacionAreas"); } catch (e) {}
    if (ultima === hoy) return;

    $.ajax({
        url: "class/actualizar_areas_por_edad.php",
        method: "GET",
        dataType: "json",
        success: function(response) {
            console.log(response.message);
            try { localStorage.setItem("ultimaActualizacionAreas", hoy); } catch (e) {}
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
                const { nombre, apellido, consecutivo, codigo_qr, cumplio } = response.data;
                printTicket(nombre, apellido, consecutivo, codigo_qr, cumplio);
            }
        }
    });
}

// Texto seguro para un campo ZPL: las letras que no son ASCII (Ñ, Á, É...) y los
// caracteres especiales de ZPL (^ ~ \) se envían como bytes UTF-8 en hexadecimal
// (\C3\91 = Ñ) y el campo lleva ^FH\ para que la impresora los reconstruya.
// Así la Ñ sale bien sin depender de la codificación que use QZ Tray o Windows.
function zplTexto(texto) {
    const bytes = new TextEncoder().encode(String(texto == null ? "" : texto));
    let salida = "";
    bytes.forEach(function(b) {
        if (b >= 0x20 && b < 0x7F && b !== 0x5E && b !== 0x7E && b !== 0x5C) {
            salida += String.fromCharCode(b);
        } else {
            salida += "\\" + b.toString(16).toUpperCase().padStart(2, "0");
        }
    });
    return salida;
}

// Pastel con velas dibujado con líneas ZPL (^GB cajas, ^GC círculos), esquina superior
// derecha de cada copia; x0 = desplazamiento de la copia (0 izquierda, 394 derecha)
function zplPastel(x0) {
    const X = v => x0 + v;
    return `
^FO${X(320)},4^GC8,8,B^FS
^FO${X(333)},4^GC8,8,B^FS
^FO${X(346)},4^GC8,8,B^FS
^FO${X(322)},12^GB4,14,4^FS
^FO${X(335)},12^GB4,14,4^FS
^FO${X(348)},12^GB4,14,4^FS
^FO${X(312)},26^GB50,16,3^FS
^FO${X(302)},42^GB70,22,3^FS
^FO${X(302)},50^GB70,3,3^FS
^FO${X(296)},63^GB82,4,4^FS`;
}

// ZPL del sticker: dos copias iguales lado a lado (izquierda x=10, derecha x=400).
// Si cumplio = true (primer domingo del mes, cumpleaños el mes anterior), cada copia
// dice "¡CUMPLIÓ!" en lugar de "Casa Kids" y lleva un pastel arriba a la derecha.
function construirZplSticker(nombre, apellido, consecutivo, fechaImpresion, cumplio) {
    const n = zplTexto(nombre), a = zplTexto(apellido), c = zplTexto(consecutivo), f = zplTexto(fechaImpresion);
    const titulo = cumplio ? zplTexto("¡CUMPLIÓ!") : "Casa Kids";
    return `
^XA
^CI28
^CF0,30
^FO10,20^A0N,30,30^FH\\^FD ${titulo} ^FS
^FO200,20^A0N,50,50^FH\\^FD ${c} ^FS
^FO10,70^A0N,50,50^FH\\^FD ${n} ^FS
^FO10,120^A0N,50,50^FH\\^FD ${a} ^FS
^FO10,180^A0N,30,30^FH\\^FD ${f} ^FS
${cumplio ? zplPastel(0) : ""}

^FO400,20^A0N,30,30^FH\\^FD ${titulo} ^FS
^FO600,20^A0N,50,50^FH\\^FD ${c} ^FS
^FO400,70^A0N,50,50^FH\\^FD ${n} ^FS
^FO400,120^A0N,50,50^FH\\^FD ${a} ^FS
^FO400,180^A0N,30,30^FH\\^FD ${f} ^FS
${cumplio ? zplPastel(394) : ""}
^XZ
`;
}

// Primer domingo del mes: aviso destacado con pastel para el niño que cumplió el mes anterior
function avisoCumpleanos(nombreCompleto, mes, edad) {
    const texto = '<i class="mdi mdi-cake-variant" style="font-size:22px;vertical-align:middle"></i> ' +
        '<b>' + escaparHtml(nombreCompleto) + '</b> cumplió ' +
        (edad ? escaparHtml(edad) + ' años ' : '') + 'en ' + escaparHtml(mes) + '. ¡Felicítalo!';
    toastr.success(texto, "¡Cumplió!", { escapeHtml: false, timeOut: 12000, extendedTimeOut: 4000, closeButton: true });
    $("#asistencia_mensaje").html(texto);
}

function printTicket(nombre, apellido, consecutivo, codigo_qr, cumplio) {
    if (typeof qz === "undefined" || !qz.websocket.isActive()) return;
    const config = qz.configs.create("4BARCODE 4B-2054TF", { copies: 1 });
    const fechaImpresion = new Date().toLocaleString('es-CO', {
        year: 'numeric', month: '2-digit', day: '2-digit',
        hour: '2-digit', minute: '2-digit', second: '2-digit'
    });

    const zplCommand = construirZplSticker(nombre, apellido, consecutivo, fechaImpresion, !!cumplio);

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