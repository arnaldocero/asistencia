// Gestión de usuarios (solo administradores): rol, contraseña y activar/desactivar
let modalContrasena;

$(document).ready(function() {
    modalContrasena = new bootstrap.Modal(document.getElementById("modalContrasena"));
    cargarUsuarios();

    // Cambiar rol
    $("#tablaUsuarios").on("change", ".select-rol", function() {
        const select = $(this);
        const nombre = select.data("nombre");
        const rol = select.val();
        const texto = rol === "admin" ? "administrador" : "recepción";
        if (!confirm("¿Cambiar a " + nombre + " al rol de " + texto + "?")) {
            select.val(select.data("rol-actual"));
            return;
        }
        actualizarUsuario({ id_usuario: select.data("id"), accion: "rol", rol: rol });
    });

    // Activar / desactivar
    $("#tablaUsuarios").on("click", ".btn-activo", function() {
        const boton = $(this);
        const activar = boton.data("activo") == 0;
        const pregunta = activar
            ? "¿Activar a " + boton.data("nombre") + "? Podrá volver a iniciar sesión."
            : "¿Desactivar a " + boton.data("nombre") + "? Ya no podrá iniciar sesión.";
        if (!confirm(pregunta)) return;
        actualizarUsuario({ id_usuario: boton.data("id"), accion: "activo", activo: activar ? 1 : 0 });
    });

    // Abrir el modal de contraseña
    $("#tablaUsuarios").on("click", ".btn-contrasena", function() {
        $("#formContrasena")[0].reset();
        $("#contrasenaIdUsuario").val($(this).data("id"));
        $("#contrasenaNombreUsuario").text($(this).data("nombre"));
        modalContrasena.show();
    });

    $("#formContrasena").on("submit", function(e) {
        e.preventDefault();
        if ($("#nuevaContrasena").val() !== $("#confirmarContrasena").val()) {
            toastr.warning("Las contraseñas no coinciden.");
            return;
        }
        actualizarUsuario({
            id_usuario: $("#contrasenaIdUsuario").val(),
            accion: "contrasena",
            contrasena: $("#nuevaContrasena").val(),
            confirmar: $("#confirmarContrasena").val()
        }, function() {
            modalContrasena.hide();
        });
    });
});

function cargarUsuarios() {
    $.ajax({
        url: "class/usuarios_listar.php",
        type: "GET",
        dataType: "json",
        success: function(response) {
            if (response.status !== "success") {
                toastr.warning(response.message);
                return;
            }
            let html = "";
            response.data.forEach(function(u) {
                const id = parseInt(u.id_usuario);
                const nombre = escaparHtml(u.nombre_usuario);
                const activo = u.activo == 1;
                html += "<tr" + (activo ? "" : ' class="text-muted"') + ">" +
                    "<td>" + nombre + (u.es_yo ? ' <span class="badge bg-info">tú</span>' : "") + "</td>" +
                    "<td>" + escaparHtml(u.email) + "</td>" +
                    "<td>" +
                        '<select class="form-select form-select-sm select-rol" data-id="' + id + '" data-nombre="' + nombre + '" data-rol-actual="' + escaparHtml(u.rol) + '"' + (u.es_yo ? " disabled" : "") + ">" +
                            '<option value="usuario"' + (u.rol === "usuario" ? " selected" : "") + ">Recepción</option>" +
                            '<option value="admin"' + (u.rol === "admin" ? " selected" : "") + ">Administrador</option>" +
                        "</select>" +
                    "</td>" +
                    "<td>" + (activo ? '<span class="badge bg-success">Activo</span>' : '<span class="badge bg-secondary">Desactivado</span>') + "</td>" +
                    "<td>" +
                        '<button class="btn btn-sm btn-primary btn-contrasena" data-id="' + id + '" data-nombre="' + nombre + '">Cambiar contraseña</button> ' +
                        (u.es_yo ? "" :
                            '<button class="btn btn-sm ' + (activo ? "btn-warning" : "btn-success") + ' btn-activo" data-id="' + id + '" data-nombre="' + nombre + '" data-activo="' + (activo ? 1 : 0) + '">' +
                            (activo ? "Desactivar" : "Activar") + "</button>") +
                    "</td>" +
                    "</tr>";
            });
            $("#tablaUsuarios").html(html || '<tr><td colspan="5">No hay usuarios</td></tr>');
        }
    });
}

function actualizarUsuario(datos, alTerminar) {
    $.ajax({
        url: "class/usuarios_actualizar.php",
        type: "POST",
        data: datos,
        dataType: "json",
        success: function(response) {
            if (response.status === "success") {
                toastr.success(response.message, "Usuarios");
                if (alTerminar) alTerminar();
            } else {
                toastr.warning(response.message);
            }
            cargarUsuarios();
        },
        error: function(xhr) {
            if (xhr.status !== 401 && xhr.status !== 403) {
                toastr.error("Error de conexión al actualizar el usuario.");
            }
            cargarUsuarios();
        }
    });
}
