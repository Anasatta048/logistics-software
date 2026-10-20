import { Chart, LineController, LineElement, PointElement, LinearScale, CategoryScale, Filler, DoughnutController, ArcElement, Tooltip, Legend } from 'chart.js';
import { chartPalette, applyChartDefaults } from './chart-theme';

Chart.register(LineController, LineElement, PointElement, LinearScale, CategoryScale, Filler, DoughnutController, ArcElement, Tooltip, Legend);
applyChartDefaults(Chart);

/**
 * Revenue & Margin — last 14 days (dashboard.blade.php). Reads its data from
 * a JSON <script> tag rather than inline JS, so the chart config below is
 * the only place chart.js is configured — no per-page one-off chart code.
 */
function initRevenueMarginChart() {
    const el = document.getElementById('revenue-margin-chart');
    if (!el) return;

    const data = JSON.parse(document.getElementById('revenue-margin-data').textContent);

    new Chart(el, {
        type: 'line',
        data: {
            labels: data.map((d) => d.date),
            datasets: [
                {
                    label: 'Revenue',
                    data: data.map((d) => d.revenue),
                    borderColor: chartPalette.brandPrimary,
                    backgroundColor: 'rgba(232, 98, 44, 0.12)',
                    fill: true,
                    pointRadius: 0,
                    borderWidth: 2,
                },
                {
                    label: 'Margin',
                    data: data.map((d) => d.margin),
                    borderColor: chartPalette.brandNavyLight,
                    backgroundColor: 'transparent',
                    fill: false,
                    pointRadius: 0,
                    borderWidth: 2,
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            scales: {
                x: { grid: { display: false } },
                y: { grid: { color: chartPalette.border }, ticks: { callback: (v) => '$' + v } },
            },
            plugins: { legend: { position: 'bottom' } },
        },
    });
}

/** Loads by Status donut (dashboard.blade.php). */
function initLoadsByStatusChart() {
    const el = document.getElementById('loads-status-chart');
    if (!el) return;

    const data = JSON.parse(document.getElementById('loads-status-data').textContent);
    const colors = {
        booked: chartPalette.statusBooked,
        loaded: chartPalette.statusLoaded,
        pickup: chartPalette.statusLoaded,
        delivered: chartPalette.statusDispatched,
        paid: chartPalette.statusPaid,
        cancelled: chartPalette.statusCancelled,
    };

    new Chart(el, {
        type: 'doughnut',
        data: {
            labels: Object.keys(data).map((k) => k.charAt(0).toUpperCase() + k.slice(1)),
            datasets: [
                {
                    data: Object.values(data),
                    backgroundColor: Object.keys(data).map((k) => colors[k] || chartPalette.textMuted),
                    borderWidth: 0,
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '70%',
            plugins: { legend: { position: 'bottom' } },
        },
    });
}

document.addEventListener('DOMContentLoaded', () => {
    initRevenueMarginChart();
    initLoadsByStatusChart();
});
