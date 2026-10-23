window.UxCharts = (function () {
    var PALETA = ['#0052CC', '#6554C0', '#36B37E', '#FFAB00', '#4C9AFF', '#00875A'];

    function oscuro() {
        return document.documentElement.classList.contains('dark');
    }

    function rgba(hex, alpha) {
        var h = String(hex || '#0052CC').replace('#', '');
        if (h.length === 3) h = h[0] + h[0] + h[1] + h[1] + h[2] + h[2];
        var n = parseInt(h, 16);
        return 'rgba(' + ((n >> 16) & 255) + ',' + ((n >> 8) & 255) + ',' + (n & 255) + ',' + alpha + ')';
    }

    function aplicar(Chart) {
        var dark = oscuro();
        var reducido = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        Chart.defaults.devicePixelRatio = Math.max(window.devicePixelRatio || 1, 2);
        Chart.defaults.color = dark ? '#A3B3C9' : '#475569';
        Chart.defaults.borderColor = dark ? 'rgba(255,255,255,0.08)' : 'rgba(10,22,40,0.06)';
        Chart.defaults.font.family = '"Inter", "Segoe UI", sans-serif';
        Chart.defaults.font.size = 12;
        Chart.defaults.animation.duration = reducido ? 0 : 560;
        Chart.defaults.animation.easing = 'easeOutQuart';
        Chart.defaults.layout.padding = { top: 8, right: 8, bottom: 4, left: 4 };
        Chart.defaults.interaction.mode = 'index';
        Chart.defaults.interaction.intersect = false;
        Chart.defaults.elements.bar.borderRadius = 8;
        Chart.defaults.elements.bar.borderSkipped = false;
        Chart.defaults.elements.line.tension = 0.35;
        Chart.defaults.elements.line.borderWidth = 2;
        Chart.defaults.elements.line.fill = true;
        Chart.defaults.elements.point.radius = 0;
        Chart.defaults.elements.point.hoverRadius = 5;
        Chart.defaults.elements.point.hitRadius = 14;
        Chart.defaults.elements.arc.borderWidth = 2;
        Chart.defaults.plugins.legend.labels.boxWidth = 8;
        Chart.defaults.plugins.legend.labels.boxHeight = 8;
        Chart.defaults.plugins.legend.labels.usePointStyle = true;
        Chart.defaults.plugins.legend.labels.pointStyle = 'circle';
        Chart.defaults.plugins.legend.labels.padding = 16;
        Chart.defaults.plugins.tooltip.backgroundColor = dark ? 'rgba(10, 22, 40, 0.96)' : '#ffffff';
        Chart.defaults.plugins.tooltip.titleColor = dark ? '#FFFFFF' : '#0A1628';
        Chart.defaults.plugins.tooltip.bodyColor = dark ? '#A3B3C9' : '#475569';
        Chart.defaults.plugins.tooltip.borderColor = dark ? 'rgba(255,255,255,0.12)' : '#E2E8F0';
        Chart.defaults.plugins.tooltip.borderWidth = 1;
        Chart.defaults.plugins.tooltip.padding = 12;
        Chart.defaults.plugins.tooltip.cornerRadius = 12;
        Chart.defaults.plugins.tooltip.displayColors = true;
        Chart.defaults.plugins.tooltip.titleFont = { family: '"Space Grotesk", Inter, sans-serif', size: 13, weight: '600' };
        Chart.defaults.plugins.tooltip.bodyFont = { family: '"Inter", sans-serif', size: 12 };
        Chart.defaults.plugins.tooltip.boxPadding = 4;
    }

    function escala(horizontal) {
        var dark = oscuro();
        var tick = dark ? '#6B7D99' : '#64748B';
        var grid = dark ? 'rgba(255,255,255,0.06)' : 'rgba(10,22,40,0.06)';
        var valor = {
            beginAtZero: true,
            grace: '8%',
            grid: { color: grid, drawTicks: false },
            border: { display: false },
            ticks: { precision: 0, color: tick, maxTicksLimit: 5, padding: 8 }
        };
        var nombre = {
            grid: { display: false },
            border: { display: false },
            ticks: { color: dark ? '#E8EEF6' : '#0A1628', autoSkip: true, maxRotation: 0, padding: 8 }
        };
        return horizontal ? { x: valor, y: nombre } : { x: nombre, y: valor };
    }

    function relleno(canvas, hex) {
        var ctx = canvas.getContext('2d');
        var alto = canvas.height || 220;
        var g = ctx.createLinearGradient(0, 0, 0, alto);
        g.addColorStop(0, rgba(hex, oscuro() ? 0.92 : 0.88));
        g.addColorStop(1, rgba(hex, oscuro() ? 0.4 : 0.5));
        return g;
    }

    function degradado(canvas) {
        return relleno(canvas, '#0052CC');
    }

    function tonos(total) {
        var base = PALETA;
        var colores = [];
        for (var i = 0; i < total; i++) colores.push(base[i % base.length]);
        return colores;
    }

    function anillo() {
        return {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '72%',
            layout: { padding: 4 },
            plugins: {
                legend: { position: 'bottom' }
            },
            elements: {
                arc: {
                    borderWidth: 0,
                    hoverOffset: 6
                }
            },
            datasets: {
                doughnut: { spacing: 3 }
            }
        };
    }

    function barras(horizontal) {
        return {
            responsive: true,
            maintainAspectRatio: false,
            indexAxis: horizontal ? 'y' : 'x',
            plugins: { legend: { display: false } },
            scales: escala(horizontal),
            datasets: {
                bar: {
                    borderRadius: 8,
                    borderSkipped: false,
                    maxBarThickness: 28,
                    categoryPercentage: 0.72,
                    barPercentage: 0.86
                }
            }
        };
    }

    function linea(canvas, hex) {
        return {
            borderColor: hex,
            backgroundColor: relleno(canvas, hex),
            borderWidth: 2,
            fill: true,
            tension: 0.35,
            pointRadius: 0,
            pointHoverRadius: 5,
            pointHitRadius: 14,
            pointBackgroundColor: hex,
            pointBorderColor: oscuro() ? '#0A1628' : '#ffffff',
            pointBorderWidth: 2
        };
    }

    var centro = {
        id: 'uxCentro',
        afterDraw: function (chart) {
            if (chart.config.type !== 'doughnut') return;
            var ds = chart.data.datasets[0];
            var meta = chart.getDatasetMeta(0);
            if (!ds || !meta.data.length) return;
            var total = ds.data.reduce(function (suma, valor) { return suma + Number(valor || 0); }, 0);
            var punto = meta.data[0];
            var ctx = chart.ctx;
            ctx.save();
            ctx.textAlign = 'center';
            ctx.textBaseline = 'middle';
            ctx.fillStyle = oscuro() ? '#FFFFFF' : '#0A1628';
            ctx.font = '700 22px "Space Grotesk", sans-serif';
            ctx.fillText(total.toLocaleString('es-MX'), punto.x, punto.y - 8);
            ctx.fillStyle = '#6B7D99';
            ctx.font = '600 10px Inter, sans-serif';
            ctx.fillText('TOTAL', punto.x, punto.y + 12);
            ctx.restore();
        }
    };

    function instancias() {
        if (!window.Chart || !Chart.instances) return [];
        return Object.keys(Chart.instances).map(function (id) {
            return Chart.instances[id];
        }).filter(function (chart) {
            return chart && chart.canvas && chart.canvas.isConnected && chart.ctx;
        });
    }

    function refrescar() {
        if (!window.Chart) return;
        aplicar(Chart);
        var dark = oscuro();
        var textoEje = dark ? '#E8EEF6' : '#0A1628';
        var textoValor = dark ? '#A3B3C9' : '#334155';
        var rejilla = dark ? 'rgba(255,255,255,0.08)' : 'rgba(10,22,40,0.08)';
        instancias().forEach(function (chart) {
            try {
                var scales = chart.options.scales || {};
                Object.keys(scales).forEach(function (key) {
                    var eje = scales[key];
                    if (!eje) return;
                    var esNombre = eje.grid && eje.grid.display === false;
                    if (!eje.ticks) eje.ticks = {};
                    eje.ticks.color = esNombre ? textoEje : textoValor;
                    if (eje.grid && eje.grid.display !== false) eje.grid.color = rejilla;
                });
                chart.options.color = textoValor;
                var plugins = chart.options.plugins || {};
                if (plugins.legend && plugins.legend.labels) {
                    plugins.legend.labels.color = textoValor;
                }
                if (plugins.tooltip) {
                    plugins.tooltip.backgroundColor = dark ? 'rgba(10, 22, 40, 0.96)' : '#ffffff';
                    plugins.tooltip.titleColor = dark ? '#FFFFFF' : '#0A1628';
                    plugins.tooltip.bodyColor = textoValor;
                    plugins.tooltip.borderColor = dark ? 'rgba(255,255,255,0.12)' : '#E2E8F0';
                }
                if (chart.options.elements && chart.options.elements.arc) {
                    chart.options.elements.arc.borderWidth = 0;
                }
                if (chart.config.type === 'doughnut' || chart.config.type === 'pie') {
                    chart.data.datasets.forEach(function (ds) {
                        ds.spacing = 3;
                        ds.borderWidth = 0;
                    });
                }
                chart.data.datasets.forEach(function (ds) {
                    if (ds.backgroundColor && typeof ds.backgroundColor.addColorStop === 'function') {
                        var hex = (typeof ds.borderColor === 'string' && ds.borderColor.charAt(0) === '#')
                            ? ds.borderColor
                            : '#0052CC';
                        ds.backgroundColor = relleno(chart.canvas, hex);
                    }
                    if (ds.pointBorderColor === '#0A1628' || ds.pointBorderColor === '#ffffff' || ds.pointBorderColor === '#FFFFFF') {
                        ds.pointBorderColor = dark ? '#0A1628' : '#ffffff';
                    }
                });
                if (typeof chart.resize === 'function') chart.resize();
                chart.update('none');
                if (typeof chart.draw === 'function') chart.draw();
            } catch (error) {
                /* una gráfica a medias no debe frenar el cambio de tema */
            }
        });
    }

    function envolverLienzos(raiz) {
        var alcance = raiz && raiz.querySelectorAll ? raiz : document;
        alcance.querySelectorAll('.dash-chart > canvas').forEach(function (canvas) {
            var caja = document.createElement('div');
            caja.className = 'dash-chart__lienzo';
            canvas.parentNode.insertBefore(caja, canvas);
            caja.appendChild(canvas);
        });
    }

    envolverLienzos(document);
    document.addEventListener('DOMContentLoaded', function () {
        envolverLienzos(document);
    });

    if (typeof MutationObserver !== 'undefined' && document.documentElement) {
        var avisoTema = null;
        new MutationObserver(function () {
            if (avisoTema) cancelAnimationFrame(avisoTema);
            avisoTema = requestAnimationFrame(function () {
                avisoTema = null;
                refrescar();
            });
        }).observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
    }

    return {
        paleta: PALETA,
        aplicar: aplicar,
        escala: escala,
        degradado: degradado,
        relleno: relleno,
        tonos: tonos,
        anillo: anillo,
        barras: barras,
        linea: linea,
        centro: centro,
        refrescar: refrescar
    };
})();
