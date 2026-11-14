// Módulo PQR: recepción registra y ve las suyas; el administrador ve todas y cambia el estado.
const TIPOS_PQR = { peticion: "Petición", queja: "Queja", reclamo: "Reclamo", sugerencia: "Sugerencia" };
const ESTADOS_PQR = {
    pendiente:  { texto: "Pendiente",  clase: "bg-danger" },
    en_proceso: { texto: "En proceso", clase: "bg-warning text-dark" },
    resuelta:   { texto: "Resuelta",   clase: "bg-success" }
};
let pqrCargadas = [];
let esAdmin = false;
let modalPqr;

$(document).ready(function() {
    modalPqr = new bootstrap.Modal(document.getElementById("modalPqr"));
    cargarPqr();

    // Buscador de niño (todos los niños activos)
    $("#pqrNinoBuscar").autocomplete({
        source: function(request, response) {
            $.getJSON("class/buscar_nino.php", { term: request.term, todos: "1" }, response);
        },
        minLength: 2,
        select: function(event, ui) {
            $("#pqrIdNino").val(ui.item.id);
            $("#pqrNinoElegido").text("Seleccionado: " + ui.item.nombre + " " + ui.item.apellido);
            if (!$("#pqrNombre").val() && ui.item.nombre_padre) {
                $("#pqrNombre").val(ui.item.nombre_padre); // sugiere el acudiente registrado
            }
            // Mostrar solo el nombre (no la etiqueta con el área); false evita que jQuery UI lo reemplace
            $("#pqrNinoBuscar").val((ui.item.nombre + " " + ui.item.apellido).replace(/\s+/g, " ").trim());
            return false;
        }
    });
    // Si se cambia el texto a mano, se quita el niño elegido
    $("#pqrNinoBuscar").on("input", function() {
        $("#pqrIdNino").val("");
        $("#pqrNinoElegido").text("");
    });
    $("#pqrNinoQuitar").on("click", quitarNinoPqr);

    // Registrar
    $("#formPqr").on("submit", function(e) {
        e.preventDefault();
        if ($("#pqrNinoBuscar").val().trim() !== "" && !$("#pqrIdNino").val()) {
            toastr.warning("Selecciona el niño de la lista o deja el campo vacío.");
            return;
        }
        $("#pqrGuardar").prop("disabled", true);
        $.ajax({
            url: "class/pqr_registrar.php",
            type: "POST",
            data: $(this).serialize(),
            dataType: "json",
            success: function(res) {
                if (res.status === "success") {
                    toastr.success(res.message, "PQR");
                    $("#formPqr")[0].reset();
                    quitarNinoPqr();
                    cargarPqr();
                } else {
                    toastr.warning(res.message);
                }
            },
            error: function(xhr) {
                if (xhr.status !== 401 && xhr.status !== 403) toastr.error("Error de conexión al registrar la PQR.");
            },
            complete: function() { $("#pqrGuardar").prop("disabled", false); }
        });
    });

    $("#pqrFiltroEstado").on("change", cargarPqr);

    // Ver / gestionar
    $("#tablaPqr").on("click", ".btn-ver-pqr", function() {
        abrirPqr(parseInt($(this).data("id")));
    });

    $("#formGestionPqr").on("submit", function(e) {
        e.preventDefault();
        if (!esAdmin) return;
        $("#gestionGuardar").prop("disabled", true);
        $.ajax({
            url: "class/pqr_actualizar.php",
            type: "POST",
            data: $(this).serialize(),
            dataType: "json",
            success: function(res) {
                if (res.status === "success") {
                    toastr.success(res.message, "PQR");
                    modalPqr.hide();
                    cargarPqr();
                } else {
                    toastr.warning(res.message);
                }
            },
            error: function(xhr) {
                if (xhr.status !== 401 && xhr.status !== 403) toastr.error("Error de conexión al actualizar la PQR.");
            },
            complete: function() { $("#gestionGuardar").prop("disabled", false); }
        });
    });
});

function quitarNinoPqr() {
    $("#pqrNinoBuscar").val("");
    $("#pqrIdNino").val("");
    $("#pqrNinoElegido").text("");
}

// "2026-11-13 10:05:00" -> "13/11/2026 10:05"
function formatoFechaPqr(fecha) {
    if (!fecha) return "";
    const [d, h] = fecha.split(" ");
    const [a, m, dia] = d.split("-");
    return dia + "/" + m + "/" + a + (h ? " " + h.slice(0, 5) : "");
}

function etiquetaEstado(estado) {
    const e = ESTADOS_PQR[estado] || { texto: estado, clase: "bg-secondary" };
    return '<span class="badge ' + e.clase + '">' + e.texto + "</span>";
}

function recortar(texto, max) {
    texto = texto || "";
    return texto.length > max ? texto.slice(0, max) + "…" : texto;
}

function cargarPqr() {
    $.ajax({
        url: "class/pqr_listar.php",
        type: "GET",
        data: { estado: $("#pqrFiltroEstado").val() },
        dataType: "json",
        success: function(res) {
            if (res.status !== "success") {
                toastr.warning(res.message);
                return;
            }
            esAdmin = res.es_admin;
            pqrCargadas = res.data;
            $("#pqrTituloLista").text(esAdmin ? "Todas las PQR" : "Mis PQR");
            $("#pqrConteoPendiente").text(res.conteo.pendiente + " pendientes");
            $("#pqrConteoProceso").text(res.conteo.en_proceso + " en proceso");
            $("#pqrConteoResuelta").text(res.conteo.resuelta + " resueltas");

            let html = "";
            res.data.forEach(function(p) {
                html += "<tr>" +
                    "<td>" + parseInt(p.id_pqr) + "</td>" +
                    "<td>" + escaparHtml(formatoFechaPqr(p.fecha_registro)) + "</td>" +
                    "<td>" + escaparHtml(TIPOS_PQR[p.tipo] || p.tipo) + "</td>" +
                    "<td>" + escaparHtml(p.nombre_solicitante) +
                        (p.nino ? '<br><small class="text-muted">Niño: ' + escaparHtml(p.nino) + "</small>" : "") + "</td>" +
                    "<td>" + escaparHtml(recortar(p.descripcion, 60)) + "</td>" +
                    "<td>" + etiquetaEstado(p.estado) + "</td>" +
                    '<td><button class="btn btn-sm ' + (esAdmin ? "btn-primary" : "btn-outline-secondary") + ' btn-ver-pqr" data-id="' + parseInt(p.id_pqr) + '">' +
                        (esAdmin ? "Gestionar" : "Ver") + "</button></td>" +
                    "</tr>";
            });
            $("#tablaPqr").html(html || '<tr><td colspan="7">No hay PQR registradas</td></tr>');
        }
    });
}

function abrirPqr(id) {
    const p = pqrCargadas.find(x => parseInt(x.id_pqr) === id);
    if (!p) return;

    const fila = (titulo, valor) => valor
        ? '<dt class="col-sm-3">' + titulo + '</dt><dd class="col-sm-9" style="white-space:pre-wrap">' + escaparHtml(valor) + "</dd>"
        : "";
    $("#modalPqrTitulo").text("PQR #" + id + " - " + (TIPOS_PQR[p.tipo] || p.tipo));
    $("#pqrDetalle").html(
        fila("Registrada", formatoFechaPqr(p.fecha_registro) + " por " + (p.registrado_por || "-")) +
        fila("Solicitante", p.nombre_solicitante + (p.telefono ? " - Tel. " + p.telefono : "")) +
        fila("Niño", p.nino) +
        fila("Descripción", p.descripcion) +
        '<dt class="col-sm-3">Estado</dt><dd class="col-sm-9">' + etiquetaEstado(p.estado) + "</dd>" +
        fila("Respuesta", p.respuesta) +
        fila("Actualizada", p.fecha_actualizacion ? formatoFechaPqr(p.fecha_actualizacion) + " por " + (p.actualizado_por || "-") : "")
    );

    // Solo el administrador ve los controles para cambiar estado y responder
    $("#pqrGestion").toggle(esAdmin);
    $("#gestionGuardar").toggle(esAdmin);
    $("#gestionIdPqr").val(id);
    $("#gestionEstado").val(p.estado);
    $("#gestionRespuesta").val(p.respuesta || "");
    modalPqr.show();
}
