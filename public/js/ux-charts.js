window.UxCharts = (function () {
    var PALETA = ['#22C55E', '#38BDF8', '#94A3B8', '#0EA5E9', '#166534'];

    function oscuro() {
        return document.documentElement.classList.contains('dark');
    }

    function aplicar(Chart) {
        var dark = oscuro();
        Chart.defaults.color = dark ? '#CBD5E1' : '#334155';
        Chart.defaults.borderColor = dark ? 'rgba(148, 163, 184, 0.16)' : 'rgba(15, 23, 42, 0.08)';
        Chart.defaults.font.size = 12;
        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            Chart.defaults.animation.duration = 0;
        }
        Chart.defaults.plugins.legend.labels.boxWidth = 8;
        Chart.defaults.plugins.legend.labels.boxHeight = 8;
        Chart.defaults.plugins.legend.labels.usePointStyle = true;
        Chart.defaults.plugins.legend.labels.padding = 14;
        Chart.defaults.plugins.tooltip.backgroundColor = 'rgba(15, 23, 42, 0.94)';
        Chart.defaults.plugins.tooltip.titleColor = '#22C55E';
        Chart.defaults.plugins.tooltip.bodyColor = '#F8FAFC';
        Chart.defaults.plugins.tooltip.borderColor = 'rgba(255, 255, 255, 0.14)';
        Chart.defaults.plugins.tooltip.borderWidth = 1;
        Chart.defaults.plugins.tooltip.padding = 10;
        Chart.defaults.plugins.tooltip.cornerRadius = 8;
        Chart.defaults.plugins.tooltip.displayColors = true;
    }

    function escala(horizontal) {
        var dark = oscuro();
        var tick = dark ? '#94A3B8' : '#64748B';
        var grid = dark ? 'rgba(148, 163, 184, 0.14)' : 'rgba(15, 23, 42, 0.06)';
        var valor = {
            beginAtZero: true,
            grid: { color: grid, drawBorder: false },
            border: { display: false },
            ticks: { precision: 0, color: tick, maxTicksLimit: 6 }
        };
        var nombre = {
            grid: { display: false },
            border: { display: false },
            ticks: { color: dark ? '#E2E8F0' : '#0F172A', autoSkip: true, maxRotation: 0 }
        };
        return horizontal ? { x: valor, y: nombre } : { x: nombre, y: valor };
    }

    function degradado(canvas) {
        var ctx = canvas.getContext('2d');
        var alto = canvas.height || 220;
        var g = ctx.createLinearGradient(0, 0, 0, alto);
        g.addColorStop(0, '#22C55E');
        g.addColorStop(1, oscuro() ? 'rgba(34, 197, 94, 0.15)' : 'rgba(34, 197, 94, 0.35)');
        return g;
    }

    function tonos(total) {
        var base = ['#22C55E', '#4ADE80', '#38BDF8', '#94A3B8', '#16A34A', '#0EA5E9', '#64748B', '#166534'];
        var colores = [];
        for (var i = 0; i < total; i++) {
            colores.push(base[i % base.length]);
        }
        return colores;
    }

    function anillo() {
        return {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '68%',
            plugins: { legend: { position: 'bottom' } },
            elements: {
                arc: {
                    borderWidth: 3,
                    borderColor: oscuro() ? '#0F172A' : '#ffffff',
                    hoverOffset: 8
                }
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
                bar: { borderRadius: 8, borderSkipped: false, maxBarThickness: 22 }
            }
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
            ctx.fillStyle = oscuro() ? '#F8FAFC' : '#0F172A';
            ctx.font = '700 22px sans-serif';
            ctx.fillText(String(total), punto.x, punto.y - 8);
            ctx.fillStyle = '#94A3B8';
            ctx.font = '600 10px sans-serif';
            ctx.fillText('TOTAL', punto.x, punto.y + 12);
            ctx.restore();
        }
    };

    return {
        paleta: PALETA,
        aplicar: aplicar,
        escala: escala,
        degradado: degradado,
        tonos: tonos,
        anillo: anillo,
        barras: barras,
        centro: centro
    };
})();
