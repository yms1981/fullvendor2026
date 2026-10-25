const ctxEl = document.getElementById('myChart');
if (!ctxEl || typeof Chart === 'undefined') {
  // Sin datos o Chart.js no cargado
} else {
const ctx = ctxEl.getContext('2d');
const dates = [];
const total_sales = [];
const percentage_mount_dates = [];

if (data_forchart && Array.isArray(data_forchart.all)) {
  data_forchart.all.forEach(function (element) {
    dates.push(element.start_date);
    const total = parseFloat(element.filter.sales_total[0].total);
    total_sales.push(total ? total : 0);

    const percent = element.filter.sales_total[0].percent_amount ? element.filter.sales_total[0].percent_amount : 0;
    percentage_mount_dates.push(percent.toFixed(2) + '%');
  });
}

const gradient = ctx.createLinearGradient(0, 0, 0, 320);
gradient.addColorStop(0, 'rgba(220, 38, 38, 0.35)');
gradient.addColorStop(1, 'rgba(220, 38, 38, 0.02)');

new Chart(ctx, {
  type: 'line',
  data: {
    labels: dates,
    datasets: [
      {
        label: ventas_month + ': ' + formatCurrency(data_forchart.total, { separator: _separator, decimalSeparator: _decimalSeparator }),
        data: total_sales,
        borderColor: '#dc2626',
        backgroundColor: gradient,
        borderWidth: 2.5,
        pointBackgroundColor: '#fff',
        pointBorderColor: '#dc2626',
        pointBorderWidth: 2,
        pointRadius: 4,
        pointHoverRadius: 6,
        fill: true,
        tension: 0.35,
      },
    ],
  },
  options: {
    responsive: true,
    maintainAspectRatio: false,
    interaction: {
      intersect: false,
      mode: 'index',
    },
    plugins: {
      legend: {
        display: true,
        position: 'top',
        labels: {
          boxWidth: 12,
          padding: 12,
          font: {
            size: 12,
            weight: '600',
          },
        },
      },
      tooltip: {
        enabled: false,
        external: function (context) {
          const tooltipIndex = context.tooltip.dataPoints[0].dataIndex;
          const percent_mount = percentage_mount_dates[tooltipIndex];
          const amount = total_sales[tooltipIndex];

          const label_text = ventas_month + ': ' + formatCurrency(amount, { separator: _separator, decimalSeparator: _decimalSeparator });
          let tooltipEl = document.getElementById('chartjs-tooltip');

          if (!tooltipEl) {
            tooltipEl = document.createElement('div');
            tooltipEl.classList.add('fv-chart-tooltip');
            tooltipEl.classList.add('p-2');
            tooltipEl.classList.add('rounded');
            tooltipEl.id = 'chartjs-tooltip';
            tooltipEl.innerHTML = '<table></table>';
            document.body.appendChild(tooltipEl);
          }

          const tooltipModel = context.tooltip;
          if (tooltipModel.opacity === 0) {
            tooltipEl.style.opacity = 0;
            return;
          }

          tooltipEl.classList.remove('above', 'below', 'no-transform');
          if (tooltipModel.yAlign) {
            tooltipEl.classList.add(tooltipModel.yAlign);
          } else {
            tooltipEl.classList.add('no-transform');
          }

          function getBody(bodyItem) {
            return bodyItem.lines;
          }

          if (tooltipModel.body) {
            const titleLines = tooltipModel.title || [];
            const bodyLines = tooltipModel.body.map(getBody);

            let innerHtml = '<thead class="">';

            titleLines.forEach(function (title) {
              innerHtml += '<tr><th>' + title + '</th></tr>';
            });
            innerHtml += '</thead><tbody>';

            bodyLines.forEach(function () {
              innerHtml += '<tr><td>' + label_text + '</td></tr>';
              innerHtml += '<tr><td>' + percent_mount + '</td></tr>';
            });
            innerHtml += '</tbody>';

            const tableRoot = tooltipEl.querySelector('table');
            tableRoot.innerHTML = innerHtml;
          }

          const position = context.chart.canvas.getBoundingClientRect();
          const bodyFont = Chart.helpers.toFont(tooltipModel.options.bodyFont);

          tooltipEl.style.opacity = 1;
          tooltipEl.style.transition = 'opacity 0.3s';
          tooltipEl.style.position = 'absolute';
          tooltipEl.style.left = position.left + window.pageXOffset + tooltipModel.caretX + 'px';
          tooltipEl.style.top = position.top + window.pageYOffset + tooltipModel.caretY + 'px';
          tooltipEl.style.font = bodyFont.string;
          tooltipEl.style.padding = tooltipModel.padding + 'px ' + tooltipModel.padding + 'px';
          tooltipEl.style.pointerEvents = 'none';
        },
      },
    },
    scales: {
      x: {
        grid: {
          display: false,
        },
        ticks: {
          color: '#9ca3af',
          font: { size: 11 },
        },
      },
      y: {
        beginAtZero: true,
        grid: {
          color: '#f3f4f6',
        },
        ticks: {
          beginAtZero: true,
          color: '#9ca3af',
          callback: function (value) {
            return formatCurrency(value, { separator: _separator, decimalSeparator: _decimalSeparator });
          },
        },
      },
    },
  },
});

}

function obtenerValorSelect() {
  const select = document.getElementById('select_filter');

  const valorSeleccionado = select.value;
  if (valorSeleccionado == 'personalizado') {
    select.selectedIndex = -1;
  }
}

document.addEventListener('DOMContentLoaded', obtenerValorSelect);

function filter_dashboard(event) {
  const value = event.srcElement.value;

  if (value == 'personalizado') {
    return $('#modal_personalizado').modal('show');
  }

  $('#form-filter').submit();
}

$('#modal_personalizado').on('hidden.bs.modal', function () {
  $('.selectpicker').val($('.selectpicker option:first').val());
});
