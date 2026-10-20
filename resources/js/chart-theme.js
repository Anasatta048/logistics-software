// Single source of truth for chart colors/style, reused by the Dashboard and
// every future Reports chart (UI_UX_DESIGN_PLAN.md Section 3 "Chart Library
// & Consistency Rule"). Hex values here are kept textually identical to
// tailwind.config.js's theme.extend.colors — this file is the JS mirror of
// that config, not a second source of truth.
export const chartPalette = {
    brandPrimary: (typeof document !== 'undefined' && getComputedStyle(document.documentElement).getPropertyValue('--brand-primary').trim()) || '#E8622C',
    brandNavy: '#1B2A4A',
    brandGold: '#C9972E',
    brandNavyLight: '#3A5A8C',
    statusBooked: '#1B2A4A',
    statusLoaded: '#3A5A8C',
    statusDispatched: '#C9972E',
    statusPaid: '#2E7D32',
    statusCancelled: '#C0392B',
    textMuted: '#6B7280',
    border: '#E5E7EB',
};

export function isDarkMode() {
    return document.documentElement.classList.contains('dark');
}

export const chartFont = {
    family: 'Inter, ui-sans-serif, system-ui, sans-serif',
    size: 12,
};

export function applyChartDefaults(Chart) {
    Chart.defaults.font.family = chartFont.family;
    Chart.defaults.font.size = chartFont.size;
    Chart.defaults.color = isDarkMode() ? '#9CA3AF' : chartPalette.textMuted;
    Chart.defaults.plugins.legend.labels.usePointStyle = true;
    Chart.defaults.plugins.tooltip.backgroundColor = isDarkMode() ? '#111827' : '#1F2333';
    Chart.defaults.plugins.tooltip.padding = 10;
    Chart.defaults.plugins.tooltip.cornerRadius = 8;
    Chart.defaults.elements.line.tension = 0.4;
}
