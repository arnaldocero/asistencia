// Color fijo por área (mismo color en todas las gráficas)
const COLORES_AREA = {
    1: "rgba(54, 162, 235, 0.8)",
    2: "rgba(255, 159, 64, 0.8)",
    3: "rgba(75, 192, 192, 0.8)",
    4: "rgba(153, 102, 255, 0.8)"
};

$(document).ready(function() {
    getgraficaasitenciames();
    for (let area = 1; area <= 4; area++) {
        getgraficaasitenciamesarea(area);
    }
});

// Barras: asistencia por mes, una serie por área
function getgraficaasitenciames() {
    $.ajax({
        url: "class/getAsistenciaPorMes.php",
        type: "GET",
        dataType: "json",
        success: function(data) {
            const meses = [...new Set(data.map(item => item.mes))].sort();
            const series = {};

            data.forEach(function(item) {
                const id = parseInt(item.id_area);
                if (!series[id]) {
                    series[id] = {
                        label: item.nombre_area,
                        data: meses.map(() => 0), // un valor por mes, aunque el área no tenga datos ese mes
                        backgroundColor: COLORES_AREA[id] || "rgba(150, 150, 150, 0.8)"
                    };
                }
                series[id].data[meses.indexOf(item.mes)] = parseInt(item.total_ninos);
            });

            new Chart(document.getElementById("asistenciaChart").getContext("2d"), {
                type: "bar",
                data: { labels: meses, datasets: Object.keys(series).sort().map(id => series[id]) },
                options: { responsive: true, scales: { y: { beginAtZero: true } } }
            });
        },
        error: function(xhr, status, error) {
            console.error("Error al obtener los datos:", error);
        }
    });
}

// Línea: asistencia por mes de un área
function getgraficaasitenciamesarea(area) {
    $.ajax({
        url: "class/getAsistenciaporMesyArea.php",
        type: "GET",
        data: { id_area: area },
        dataType: "json",
        success: function(data) {
            const canvas = document.getElementById("asistenciaChartArea" + area);
            if (!canvas) return;

            if (data.length > 0) {
                $("#tituloArea" + area).text("Asistencia mensual - " + data[0].nombre_area);
            }

            new Chart(canvas.getContext("2d"), {
                type: "line",
                data: {
                    labels: data.map(item => item.mes),
                    datasets: [{
                        label: data.length > 0 ? data[0].nombre_area : "Área " + area,
                        data: data.map(item => parseInt(item.total_ninos)),
                        borderColor: COLORES_AREA[area],
                        backgroundColor: "transparent",
                        borderWidth: 2,
                        tension: 0.4
                    }]
                },
                options: { responsive: true, scales: { y: { beginAtZero: true } } }
            });
        },
        error: function(xhr, status, error) {
            console.error("Error al obtener los datos:", error);
        }
    });
}
