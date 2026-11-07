<?php
// Solo un administrador con sesión iniciada puede crear usuarios
include("class/verificar_sesion.php");
if (($_SESSION['usuario_rol'] ?? '') !== 'admin') {
    header('Location: index.php');
    exit();
}
?>
<!DOCTYPE html>
<html dir="ltr" lang="es">
  <head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="robots" content="noindex,nofollow" />
    <title>Casa Kids - Registrar usuario</title>
    <link rel="icon" type="image/png" sizes="16x16" href="assets/images/favicon.png" />
    <link href="assets/libs/toastr/build/toastr.min.css" rel="stylesheet" />
    <link href="dist/css/style.min.css" rel="stylesheet" />
  </head>

  <body>
    <div class="main-wrapper">
      <div class="preloader">
        <div class="lds-ripple">
          <div class="lds-pos"></div>
          <div class="lds-pos"></div>
        </div>
      </div>
      <div class="auth-wrapper d-flex no-block justify-content-center align-items-center bg-dark">
        <div class="auth-box bg-dark border-top border-secondary">
          <div>
            <div class="text-center pt-3 pb-3">
              <span class="db"><img src="assets/images/logo.png" alt="logo" /></span>
              <h4 class="text-white mt-3">Registrar usuario</h4>
            </div>
            <form id="registroUsuarioForm" class="form-horizontal mt-3" autocomplete="off">
              <div class="row pb-4">
                <div class="col-12">
                  <div class="input-group mb-3">
                    <span class="input-group-text bg-success text-white h-100"><i class="mdi mdi-account fs-4"></i></span>
                    <input type="text" name="nombre_usuario" class="form-control form-control-lg" placeholder="Nombre de usuario" aria-label="Nombre de usuario" minlength="3" maxlength="100" required />
                  </div>
                  <div class="input-group mb-3">
                    <span class="input-group-text bg-danger text-white h-100"><i class="mdi mdi-email fs-4"></i></span>
                    <input type="email" name="email" class="form-control form-control-lg" placeholder="Correo electrónico" aria-label="Correo electrónico" maxlength="100" required />
                  </div>
                  <div class="input-group mb-3">
                    <span class="input-group-text bg-warning text-white h-100"><i class="mdi mdi-lock fs-4"></i></span>
                    <input type="password" name="contrasena" class="form-control form-control-lg" placeholder="Contraseña (mínimo 8 caracteres)" aria-label="Contraseña" minlength="8" autocomplete="new-password" required />
                  </div>
                  <div class="input-group mb-3">
                    <span class="input-group-text bg-info text-white h-100"><i class="mdi mdi-lock fs-4"></i></span>
                    <input type="password" name="confirmar" class="form-control form-control-lg" placeholder="Confirmar contraseña" aria-label="Confirmar contraseña" minlength="8" autocomplete="new-password" required />
                  </div>
                  <div class="input-group mb-3">
                    <span class="input-group-text bg-secondary text-white h-100"><i class="mdi mdi-account-key fs-4"></i></span>
                    <select name="rol" class="form-select form-control-lg" aria-label="Rol">
                      <option value="usuario" selected>Usuario (recepción)</option>
                      <option value="admin">Administrador</option>
                    </select>
                  </div>
                </div>
              </div>
              <div class="row border-top border-secondary">
                <div class="col-12">
                  <div class="pt-3 d-flex gap-2">
                    <a href="usuarios.php" class="btn btn-lg btn-secondary flex-fill">Volver</a>
                    <button id="btnRegistrarUsuario" class="btn btn-lg btn-info flex-fill" type="submit">Registrar</button>
                  </div>
                </div>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>

    <script src="assets/libs/jquery/dist/jquery.min.js"></script>
    <script src="assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/libs/toastr/build/toastr.min.js"></script>
    <script src="dist/js/sesion.js?v=<?php echo filemtime(__DIR__ . '/dist/js/sesion.js'); ?>"></script>
    <script>
      $(".preloader").fadeOut();

      $("#registroUsuarioForm").on("submit", function (e) {
        e.preventDefault();
        var form = this;
        if (form.contrasena.value !== form.confirmar.value) {
          toastr.warning("Las contraseñas no coinciden.");
          return;
        }
        $("#btnRegistrarUsuario").prop("disabled", true);
        $.ajax({
          type: "POST",
          url: "class/registrar_usuario.php",
          data: $(form).serialize(),
          dataType: "json",
          success: function (res) {
            if (res.status === "success") {
              toastr.success(res.message, "Sistema");
              form.reset();
            } else {
              toastr.warning(res.message);
            }
          },
          error: function (xhr) {
            if (xhr.status !== 401 && xhr.status !== 403) {
              toastr.error("Error de conexión al registrar el usuario.");
            }
          },
          complete: function () {
            $("#btnRegistrarUsuario").prop("disabled", false);
          }
        });
      });
    </script>
  </body>
</html>
