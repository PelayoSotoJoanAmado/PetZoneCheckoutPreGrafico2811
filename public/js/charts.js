
// CONFIGURACIÓN DE API

const STATS_API = window.location.pathname.includes('/public/') 
    ? '../routes/router.php?recurso=estadisticas' 
    : 'routes/router.php?recurso=estadisticas';


// COLORES DEL TEMA PETZONE

const PETZONE_COLORS = {
    primario: '#2d8659',
    primarioOscuro: '#236b47',
    primarioClaro: '#4da377',
    secundario: '#a8d5ba',
    acento: '#c8e6c9',
    acentoClaro: '#e8f5e9',
    gradiente: ['#2d8659', '#4da377', '#a8d5ba', '#c8e6c9', '#23906F', '#5bb381', '#7dc99a', '#9fdeb3']
};


// CONFIGURACIÓN GLOBAL DE HIGHCHARTS

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


// INICIALIZACIÓN


/**
 * Inicializar todos los gráficos
 */
function inicializarGraficos() {
    console.log('Inicializando gráficos...');
    cargarGraficoProductosPorCategoria();
    cargarGraficoReservasPorServicio();
    cargarGraficoVentasMensuales();
    cargarGraficoProductosMasVendidos();
}


/**
 * Gráfico de Pastel - Productos por Categoría
 */
async function cargarGraficoProductosPorCategoria() {
    try {
        const response = await fetch(`${STATS_API}&action=productosPorCategoria`);
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const result = await response.json();
        
        console.log('Productos por Categoría:', result);
        
        if (result.success && result.data && result.data.length > 0) {
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
            mostrarErrorGrafico('chart-productos-categoria', 'No hay datos de productos disponibles');
        }
    } catch (error) {
        console.error('Error al cargar gráfico de productos por categoría:', error);
        mostrarErrorGrafico('chart-productos-categoria', 'Error al cargar el gráfico');
    }
}


/**
 * Gráfico de Barras - Reservas por Servicio
 */
async function cargarGraficoReservasPorServicio() {
    try {
        const response = await fetch(`${STATS_API}&action=reservasPorServicio&dias=30`);
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const result = await response.json();
        
        console.log(' Reservas por Servicio:', result);
        
        if (result.success && result.data) {
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
            mostrarErrorGrafico('chart-reservas-servicio', 'No hay datos de reservas disponibles');
        }
    } catch (error) {
        console.error('Error al cargar gráfico de reservas por servicio:', error);
        mostrarErrorGrafico('chart-reservas-servicio', 'Error al cargar el gráfico');
    }
}


/**
 * Gráfico de Líneas - Ventas Mensuales
 */
async function cargarGraficoVentasMensuales() {
    try {
        const response = await fetch(`${STATS_API}&action=ventasMensuales&meses=6`);
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const result = await response.json();
        
        console.log('Ventas Mensuales:', result);
        
        if (result.success && result.data) {
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
            mostrarErrorGrafico('chart-ventas-mensuales', 'No hay datos de ventas disponibles');
        }
    } catch (error) {
        console.error('Error al cargar gráfico de ventas mensuales:', error);
        mostrarErrorGrafico('chart-ventas-mensuales', 'Error al cargar el gráfico');
    }
}


/**
 * Gráfico de Columnas - Productos Más Vendidos
 */
async function cargarGraficoProductosMasVendidos() {
    try {
        const response = await fetch(`${STATS_API}&action=productosMasVendidos&limite=5`);
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const result = await response.json();
        
        console.log('Productos Más Vendidos:', result);
        
        if (result.success && result.data) {
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
            mostrarErrorGrafico('chart-productos-vendidos', 'No hay datos de ventas de productos disponibles');
        }
    } catch (error) {
        console.error('Error al cargar gráfico de productos más vendidos:', error);
        mostrarErrorGrafico('chart-productos-vendidos', 'Error al cargar el gráfico');
    }
}


// FUNCIONES AUXILIARES

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
                    <button onclick="inicializarGraficos()" style="
                        margin-top: 1rem;
                        padding: 0.5rem 1rem;
                        background: #23906F;
                        color: white;
                        border: none;
                        border-radius: 8px;
                        cursor: pointer;
                        font-size: 0.9rem;
                    ">
                        <span class="material-icons" style="vertical-align: middle; font-size: 18px;">refresh</span>
                        Reintentar
                    </button>
                </div>
            </div>
        `;
    }
}

/**
 * Actualizar todos los gráficos
 */
function actualizarGraficos() {
    console.log('Actualizando gráficos...');
    inicializarGraficos();
}


// AUTO-ACTUALIZACIÓN


// Auto-actualizar gráficos cada 5 minutos
setInterval(actualizarGraficos, 5 * 60 * 1000);


// LOG DE CARGA


console.log('Sistema de gráficos cargado - PetZone');