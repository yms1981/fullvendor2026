(function () {
  if (typeof Chart !== 'undefined') {
    Chart.defaults.font.family = "'Segoe UI', system-ui, -apple-system, sans-serif";
    Chart.defaults.color = '#64748b';
    Chart.defaults.plugins.legend.labels.usePointStyle = true;
    Chart.defaults.plugins.legend.labels.boxWidth = 8;
  }

  window.formatCurrency = function formatCurrency(value, { separator = ',', decimalSeparator = '.' } = {}) {
    const num = Number(value) || 0;
    const [integerPart, decimalPart] = num.toFixed(2).toString().split('.');
    const formattedIntegerPart = integerPart.replace(/\B(?=(\d{3})+(?!\d))/g, separator);
    const result = decimalPart
      ? `${formattedIntegerPart}${decimalSeparator}${decimalPart}`
      : formattedIntegerPart;
    return (window._moneda || '') + result;
  };

  window.getChartColors = function getChartColors(count) {
    const palette = Array.isArray(window._chartColors) && window._chartColors.length
      ? window._chartColors
      : ['#dc2626', '#9ca3af', '#6b7280', '#b91c1c', '#d1d5db', '#4b5563'];
    const colors = [];
    for (let i = 0; i < count; i += 1) {
      colors.push(palette[i % palette.length]);
    }
    return colors;
  };

  window.acortarTexto = function acortarTexto(texto, longitudMaxima) {
    const text = String(texto || '');
    if (text.length > longitudMaxima) {
      return text.substring(0, longitudMaxima - 3) + '...';
    }
    return text;
  };

  window.labelOtros = window.labelOtros || 'Otros';
})();
