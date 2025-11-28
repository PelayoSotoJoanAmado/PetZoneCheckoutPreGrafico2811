const PETZONE_COLORS = {
    primario: '#2d8659',
    primarioOscuro: '#236b47',
    primarioClaro: '#4da377',
    secundario: '#a8d5ba',
    acento: '#c8e6c9',
    acentoClaro: '#e8f5e9',
    gradiente: ['#2d8659', '#4da377', '#a8d5ba', '#c8e6c9', '#23906F', '#5bb381', '#7dc99a', '#9fdeb3']
};

// Configuración global de Highcharts
Highcharts.setOptions({
    colors: PETZONE_COLORS.gradiente,
    chart: {
        style: {
            fontFamily: '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif'
        }
    },
    credits: {
        enabled: false
    },
    lang: {
        months: ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'],
        shortMonths: ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'],
        weekdays: ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'],
        loading: 'Cargando...',
        noData: 'No hay datos para mostrar'
    }
});

/**
 * Inicializar todos los gráficos
 */
function inicializarGraficos() {
    cargarGraficoProductosPorCategoria();
    cargarGraficoReservasPorServicio();
    cargarGraficoVentasMensuales();
    cargarGraficoProductosMasVendidos();
}

/**
 * Gráfico de Pastel - Productos por Categoría
 */
function cargarGraficoProductosPorCategoria() {
    fetch('../controller/EstadisticasController.php?action=productos-por-categoria')
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                Highcharts.chart('chart-productos-categoria', {
                    chart: {
                        type: 'pie',
                        backgroundColor: 'transparent'
                    },
                    title: {
                        text: 'Distribución de Productos por Categoría',
                        style: {
                            fontSize: '18px',
                            fontWeight: '700',
                            color: '#2d8659'
                        }
                    },
                    tooltip: {
                        pointFormat: '<b>{point.y}</b> productos ({point.percentage:.1f}%)',
                        style: {
                            fontSize: '13px'
                        }
                    },
                    accessibility: {
                        point: {
                            valueSuffix: ' productos'
                        }
                    },
                    plotOptions: {
                        pie: {
                            allowPointSelect: true,
                            cursor: 'pointer',
                            dataLabels: {
                                enabled: true,
                                format: '<b>{point.name}</b>: {point.percentage:.1f}%',
                                style: {
                                    fontSize: '12px',
                                    fontWeight: '600'
                                }
                            },
                            showInLegend: true
                        }
                    },
                    series: [{
                        name: 'Productos',
                        colorByPoint: true,
                        data: result.data
                    }]
                });
            } else {
                mostrarErrorGrafico('chart-productos-categoria', 'No se pudieron cargar los datos');
            }
        })
        .catch(error => {
            console.error('Error al cargar gráfico de productos por categoría:', error);
            mostrarErrorGrafico('chart-productos-categoria', 'Error al cargar el gráfico');
        });
}

/**
 * Gráfico de Barras - Reservas por Servicio
 */
function cargarGraficoReservasPorServicio() {
    fetch('../controller/EstadisticasController.php?action=reservas-por-servicio&dias=30')
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                Highcharts.chart('chart-reservas-servicio', {
                    chart: {
                        type: 'bar',
                        backgroundColor: 'transparent'
                    },
                    title: {
                        text: 'Reservas por Servicio (Últimos 30 días)',
                        style: {
                            fontSize: '18px',
                            fontWeight: '700',
                            color: '#2d8659'
                        }
                    },
                    xAxis: {
                        categories: result.data.categories,
                        title: {
                            text: 'Servicios'
                        },
                        labels: {
                            style: {
                                fontSize: '12px'
                            }
                        }
                    },
                    yAxis: {
                        min: 0,
                        title: {
                            text: 'Número de Reservas',
                            align: 'high'
                        },
                        labels: {
                            overflow: 'justify'
                        }
                    },
                    tooltip: {
                        valueSuffix: ' reservas'
                    },
                    plotOptions: {
                        bar: {
                            dataLabels: {
                                enabled: true,
                                style: {
                                    fontSize: '12px',
                                    fontWeight: '600'
                                }
                            },
                            color: PETZONE_COLORS.primario,
                            borderRadius: 5
                        }
                    },
                    legend: {
                        enabled: false
                    },
                    series: [{
                        name: 'Reservas',
                        data: result.data.values
                    }]
                });
            } else {
                mostrarErrorGrafico('chart-reservas-servicio', 'No se pudieron cargar los datos');
            }
        })
        .catch(error => {
            console.error('Error al cargar gráfico de reservas por servicio:', error);
            mostrarErrorGrafico('chart-reservas-servicio', 'Error al cargar el gráfico');
        });
}

/**
 * Gráfico de Líneas - Ventas Mensuales
 */
function cargarGraficoVentasMensuales() {
    fetch('../controller/EstadisticasController.php?action=ventas-mensuales&meses=6')
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                Highcharts.chart('chart-ventas-mensuales', {
                    chart: {
                        type: 'line',
                        backgroundColor: 'transparent'
                    },
                    title: {
                        text: 'Ventas Mensuales (Últimos 6 meses)',
                        style: {
                            fontSize: '18px',
                            fontWeight: '700',
                            color: '#2d8659'
                        }
                    },
                    xAxis: {
                        categories: result.data.categories,
                        labels: {
                            style: {
                                fontSize: '12px'
                            }
                        }
                    },
                    yAxis: {
                        title: {
                            text: 'Ventas (S/.)'
                        },
                        labels: {
                            formatter: function () {
                                return 'S/. ' + this.value.toLocaleString('es-PE');
                            }
                        }
                    },
                    tooltip: {
                        shared: true,
                        formatter: function () {
                            let tooltip = '<b>' + this.x + '</b><br/>';
                            this.points.forEach(point => {
                                if (point.series.name === 'Ventas') {
                                    tooltip += point.series.name + ': <b>S/. ' + point.y.toLocaleString('es-PE', { minimumFractionDigits: 2 }) + '</b><br/>';
                                } else {
                                    tooltip += point.series.name + ': <b>' + point.y + '</b><br/>';
                                }
                            });
                            return tooltip;
                        }
                    },
                    plotOptions: {
                        line: {
                            dataLabels: {
                                enabled: false
                            },
                            enableMouseTracking: true,
                            marker: {
                                radius: 5,
                                symbol: 'circle'
                            }
                        }
                    },
                    series: [{
                        name: 'Ventas',
                        data: result.data.ventas,
                        color: PETZONE_COLORS.primario,
                        lineWidth: 3
                    }, {
                        name: 'Pedidos',
                        data: result.data.pedidos,
                        color: PETZONE_COLORS.secundario,
                        lineWidth: 2,
                        dashStyle: 'ShortDash'
                    }]
                });
            } else {
                mostrarErrorGrafico('chart-ventas-mensuales', 'No se pudieron cargar los datos');
            }
        })
        .catch(error => {
            console.error('Error al cargar gráfico de ventas mensuales:', error);
            mostrarErrorGrafico('chart-ventas-mensuales', 'Error al cargar el gráfico');
        });
}

/**
 * Gráfico de Columnas - Productos Más Vendidos
 */
function cargarGraficoProductosMasVendidos() {
    fetch('../controller/EstadisticasController.php?action=productos-mas-vendidos&limite=5')
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                Highcharts.chart('chart-productos-vendidos', {
                    chart: {
                        type: 'column',
                        backgroundColor: 'transparent'
                    },
                    title: {
                        text: 'Top 5 Productos Más Vendidos',
                        style: {
                            fontSize: '18px',
                            fontWeight: '700',
                            color: '#2d8659'
                        }
                    },
                    xAxis: {
                        categories: result.data.categories,
                        crosshair: true,
                        labels: {
                            style: {
                                fontSize: '11px'
                            },
                            rotation: -45
                        }
                    },
                    yAxis: {
                        min: 0,
                        title: {
                            text: 'Unidades Vendidas'
                        }
                    },
                    tooltip: {
                        headerFormat: '<span style="font-size:12px"><b>{point.key}</b></span><br/>',
                        pointFormat: 'Vendidos: <b>{point.y}</b> unidades',
                        shared: true,
                        useHTML: true
                    },
                    plotOptions: {
                        column: {
                            pointPadding: 0.2,
                            borderWidth: 0,
                            dataLabels: {
                                enabled: true,
                                style: {
                                    fontSize: '12px',
                                    fontWeight: '600'
                                }
                            },
                            color: {
                                linearGradient: { x1: 0, x2: 0, y1: 0, y2: 1 },
                                stops: [
                                    [0, PETZONE_COLORS.primario],
                                    [1, PETZONE_COLORS.primarioClaro]
                                ]
                            },
                            borderRadius: 5
                        }
                    },
                    legend: {
                        enabled: false
                    },
                    series: [{
                        name: 'Unidades',
                        data: result.data.values
                    }]
                });
            } else {
                mostrarErrorGrafico('chart-productos-vendidos', 'No se pudieron cargar los datos');
            }
        })
        .catch(error => {
            console.error('Error al cargar gráfico de productos más vendidos:', error);
            mostrarErrorGrafico('chart-productos-vendidos', 'Error al cargar el gráfico');
        });
}

/**
 * Mostrar mensaje de error en el contenedor del gráfico
 */
function mostrarErrorGrafico(containerId, mensaje) {
    const container = document.getElementById(containerId);
    if (container) {
        container.innerHTML = `
            <div style="display: flex; align-items: center; justify-content: center; height: 100%; min-height: 300px; color: #999;">
                <div style="text-align: center;">
                    <span class="material-icons" style="font-size: 48px; color: #ddd;">error_outline</span>
                    <p style="margin-top: 10px;">${mensaje}</p>
                </div>
            </div>
        `;
    }
}

/**
 * Actualizar todos los gráficos
 */
function actualizarGraficos() {
    inicializarGraficos();
}

// Auto-actualizar gráficos cada 5 minutos
setInterval(actualizarGraficos, 5 * 60 * 1000);


Highcharts.chart('test', {
    chart: {
        type: 'pie',
        zooming: {
            type: 'xy'
        },
        panning: {
            enabled: true,
            type: 'xy'
        },
        panKey: 'shift'
    },
    title: {
        text: 'Egg Yolk Composition'
    },
    tooltip: {
        valueSuffix: '%'
    },
    subtitle: {
        text:
        'Source:<a href="https://www.mdpi.com/2072-6643/11/3/684/htm" target="_default">MDPI</a>'
    },
    plotOptions: {
        pie: {
            allowPointSelect: true,
            cursor: 'pointer',
            dataLabels: [{
                enabled: true,
                distance: 20
            }, {
                enabled: true,
                distance: -40,
                format: '{point.percentage:.1f}%',
                style: {
                    fontSize: '1.2em',
                    textOutline: 'none',
                    opacity: 0.7
                },
                filter: {
                    operator: '>',
                    property: 'percentage',
                    value: 10
                }
            }]
        }
    },
    series: [
        {
            name: 'Percentage',
            colorByPoint: true,
            data: [
                {
                    name: 'Water',
                    y: 55.02
                },
                {
                    name: 'Fat',
                    sliced: true,
                    selected: true,
                    y: 26.71
                },
                {
                    name: 'Carbohydrates',
                    y: 1.09
                },
                {
                    name: 'Protein',
                    y: 15.5
                },
                {
                    name: 'Ash',
                    y: 1.68
                }
            ]
        }
    ]
});
