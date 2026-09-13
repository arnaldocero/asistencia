document.addEventListener('DOMContentLoaded', function () {
      var calendarEl = document.getElementById('calendar');

      var calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth', // vista mensual
        locale: 'es',                // idioma español
        firstDay: 1,                 // semana empieza lunes
        events: {
          url: 'class/get_cumpleanos.php', // tu backend
          method: 'GET',
          failure: function () {
            toastr.warning('❌ Error al cargar cumpleaños', 'Atención');
          }
        },
        eventClick: function(info) {
          // al hacer clic en un cumpleaños
          toastr.success('Cumpleaños de: ' + info.event.title, '🎂 Cumpleaños');
        }
      });

      calendar.render();
    });