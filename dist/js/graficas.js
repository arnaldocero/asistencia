$(document).ready(function() {
    getgraficaasitenciames();
    getgraficaasitenciamesarea(1);
    getgraficaasitenciamesarea(2);
});

function getgraficaasitenciames(){
    $.ajax({
        url: "class/getAsistenciaPorMes.php",
        type: "GET",
        dataType: "json",
        success: function (data) {
            let labels = [];
            let datasets = {};

            data.forEach((item) => {
                let mes = item.mes;
                let area = item.nombre_area;
                let total = parseInt(item.total_ninos);

                if (!labels.includes(mes)) {
                    labels.push(mes);
                }

                if (!datasets[area]) {
                    datasets[area] = {
                        label: area,
                        data: [],
                        backgroundColor: getRandomColor()
                    };
                }

                datasets[area].data.push(total);
            });

            let datasetsArray = Object.values(datasets);

            let ctx = document.getElementById("asistenciaChart").getContext("2d");
            new Chart(ctx, {
                type: "bar",
                data: {
                    labels: labels,
                    datasets: datasetsArray
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        },
        error: function (xhr, status, error) {
            console.error("Error al obtener los datos:", error);
        }
    });

    
    
}
function getgraficaasitenciamesarea(area) {
    $.ajax({
        url: "class/getAsistenciaporMesyArea.php",
        type: "GET",
        data: { id_area: area }, // 🔹 Pasamos el área como parámetro
        dataType: "json",
        success: function (data) {
            let labels = [];
            let datasets = {};

            data.forEach((item) => {
                let mes = item.mes;
                let area = item.nombre_area;
                let total = parseInt(item.total_ninos);

                if (!labels.includes(mes)) {
                    labels.push(mes);
                }

                if (!datasets[area]) {
                    datasets[area] = {
                        label: area,
                        data: [],
                        borderColor: getRandomColor(), // 🔹 Color de la línea
                        backgroundColor: "transparent", // 🔹 Sin relleno
                        borderWidth: 2,
                        tension: 0.4 // 🔹 Suaviza la línea
                    };
                }

                datasets[area].data.push(total);
            });

            let datasetsArray = Object.values(datasets);

            let ctx;
            if (area == 1) {
                ctx = document.getElementById("asistenciaChartarea").getContext("2d");
            } else if (area == 2) {
                ctx = document.getElementById("asistenciaChartareacinco").getContext("2d");
            } else {
                ctx = document.getElementById("asistenciaChartarea").getContext("2d");
            }
            
            new Chart(ctx, {
                type: "line", // 🔹 Cambiamos de bar a line
                data: {
                    labels: labels,
                    datasets: datasetsArray
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        },
        error: function (xhr, status, error) {
            console.error("Error al obtener los datos:", error);
        }
    });
}

function getRandomColor() {
    return `rgba(${Math.floor(Math.random() * 255)}, ${Math.floor(Math.random() * 255)}, ${Math.floor(Math.random() * 255)}, 0.7)`;
}