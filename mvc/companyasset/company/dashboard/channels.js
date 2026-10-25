(function () {
  const canvas = document.getElementById('myChart_channels');
  if (!canvas || typeof Chart === 'undefined' || !window.channelChartData) {
    return;
  }

  const data = window.channelChartData;
  const total = (data.app || 0) + (data.web || 0) + (data.manual || 0);
  if (total <= 0) {
    return;
  }

  const labels = [window.labelChannelApp, window.labelChannelWeb, window.labelChannelManual];
  const values = [data.app || 0, data.web || 0, data.manual || 0];
  const colors = getChartColors(3);

  new Chart(canvas.getContext('2d'), {
    type: 'doughnut',
    data: {
      labels,
      datasets: [{
        data: values,
        backgroundColor: colors,
        borderWidth: 0,
        hoverOffset: 4,
      }],
    },
    options: {
      responsive: true,
      maintainAspectRatio: true,
      cutout: '62%',
      plugins: {
        legend: {
          display: false,
        },
        tooltip: {
          callbacks: {
            label(context) {
              const value = context.parsed || 0;
              const pct = total > 0 ? ((value / total) * 100).toFixed(1) : '0.0';
              return ` ${formatCurrency(value)} (${pct}%)`;
            },
          },
        },
      },
    },
  });
})();
