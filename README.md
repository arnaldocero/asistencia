# Casa Kids – Sistema de Asistencia

Aplicación web para registrar la asistencia diaria de los niños de Casa Kids. Cuando se hace el check-in, imprime un sticker de identificación con un número consecutivo por área. Funciona en el PC de recepción y en celulares a la vez, todos conectados a la misma base de datos.

## Funcionalidades

- **Inicio de sesión** con roles `admin` y `usuario`. La sesión se cierra tras 4 horas de inactividad.
- **Registro de niños** con fecha de nacimiento, acudiente, teléfono y diagnósticos. El área se asigna según la edad y se genera un código QR.
- **Check-in diario**: busca al niño, registra la hora y asigna el consecutivo del área. No permite registrar dos veces al mismo niño el mismo día.
- **Sticker** en impresora térmica (ZPL) a través de QZ Tray.
- **Sincronización celular → PC**: el PC con impresora consulta cada 4 s e imprime los registros hechos desde celulares.
- **Reportes** por mes, área y turno, y un **calendario de cumpleaños**.
- **PQR**: peticiones, quejas, reclamos y sugerencias. Recepción las registra y ve las suyas; el administrador ve todas, cambia el estado (pendiente, en proceso, resuelta) y responde.
- **Administración** (solo rol `admin`): eliminar niños (borrado lógico) y gestionar usuarios: crear, cambiar rol o contraseña y desactivar.
- **Consecutivos**: se reinician solos cada día; el primer sticker de cada área es 301, 501, 701 o 901.

## Tecnologías

| Capa | Tecnología |
| --- | --- |
| Servidor | PHP 8 (mysqli, GD), Apache |
| Base de datos | MySQL / MariaDB (InnoDB, utf8mb4) |
| Interfaz | Plantilla Matrix Admin, Bootstrap 5, jQuery 3 y jQuery UI, DataTables, Toastr |
| Gráficas | Chart.js, Flot, FullCalendar |
| QR | phpqrcode |
| Impresión | QZ Tray + impresora 4BARCODE 4B-2054TF (ZPL) |

## Estructura

```
ajax/db_connect.php     Conexión a la BD (lee config/), zona horaria y manejo de errores
ajax/areas.php          Cálculo de edad y área (rangos en tbl_areas)
class/                  Endpoints AJAX (JSON). Todos exigen sesión: verificar_sesion_api.php
class/verificar_sesion.php      Verificación de sesión para las páginas (+ token CSRF)
config/                 Configuración por entorno (config.local.php no se sube a git)
dist/js/miconfig.js     Lógica de check-in, tablas, sincronización e impresión
dist/js/sesion.js       CSRF, redirección al login con 401 y escaparHtml()
dist/img/codigosqr/     QR generados (no se suben a git)
sql_file/               Estructura de la BD y migraciones
*.php (raíz)            Páginas: index, listaninos, reportes, charts, cumpleanos, pqr, usuarios, login, registro
```

## Instalación local (XAMPP)

1. Clona el repositorio dentro de `C:\xampp\htdocs\`.
2. En `C:\xampp\php\php.ini` activa `extension=gd`, necesaria para los QR, y reinicia Apache.
3. Crea una base de datos y luego:
   - **Base nueva:** importa `sql_file/herramie_asistencia.sql` y crea el primer administrador con las instrucciones que están al final de ese archivo.
   - **Copia de producción:** impórtala y ejecuta las migraciones pendientes de `sql_file/migraciones/`, en orden de fecha.
4. Copia `config/config.example.php` como `config/config.local.php` y completa los datos de la base.
5. Abre `http://localhost/<carpeta>/authentication-login.php`.

## Despliegue en producción

1. Sube los archivos **sin sobrescribir** `config/config.local.php` ni `dist/img/codigosqr/` del servidor.
2. Si es la primera vez, crea en el servidor `config/config.local.php` con las credenciales de producción.
3. Ejecuta en phpMyAdmin las migraciones nuevas de `sql_file/migraciones/`. Todas se pueden ejecutar más de una vez sin problema.
4. Una vez al mes, abre `class/actualizar_areas_por_edad.php` con sesión iniciada para recalcular las áreas de los niños que cumplieron años.

## Equipos

- **PC de recepción:** navegador con **QZ Tray** abierto y la impresora conectada por USB. Imprime los stickers de todos los registros, incluidos los hechos desde celulares. Su pestaña puede quedar en segundo plano, pero el navegador puede ralentizar las consultas tras varios minutos oculto. Conviene dejarla visible.
- **Celulares o tabletas:** solo navegador. Registran sin imprimir y pausan la sincronización cuando la pestaña está oculta.

## Seguridad

- Las credenciales viven en `config/config.local.php`, excluido de git y bloqueado por `.htaccess`.
- Todos los endpoints de `class/` exigen sesión. Las peticiones POST exigen además el token CSRF que envía `dist/js/sesion.js`.
- Eliminar niños y crear usuarios requiere el rol `admin`.
- Los errores técnicos van al log del servidor y no al navegador.
