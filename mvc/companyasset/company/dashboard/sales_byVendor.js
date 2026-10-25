const chartVendorEl = document.getElementById('myChart_salesbyVendor');
if (!chartVendorEl || typeof Chart === 'undefined') {
  // Sin gráfico
} else {
const chart_vendor = chartVendorEl.getContext("2d");

const vend_name = []
const vend_monto = []

const percentage_mount_vendor = []

sales_byvendors.all.forEach( (element, index) => {

  if (index < max_chart) {
    vend_name.push(element.vendor);
    vend_monto.push(parseFloat(element.total ? element.total : 0));

    const percent = element.percent_amount?element.percent_amount:0
    percentage_mount_vendor.push(percent.toFixed(2) + '%')

  }
});


if (sales_byvendors.otros_mount) {
    
  vend_name.push(labelOtros || 'Otros')
  vend_monto.push(parseFloat(sales_byvendors.otros_mount))
}
  
// agramos nueva categoria Otros(porcentaje)
if (sales_byvendors.otros_mount_porcent) {
  percentage_mount_vendor.push(sales_byvendors.otros_mount_porcent.toFixed(2) + '%')
}

new Chart(chart_vendor, {
    type: 'doughnut',
    data: {
      datasets: [
        {
          label: ventas_month,
          data:  vend_monto,
          borderWidth: 0,
          backgroundColor: getChartColors(vend_monto.length),
          hoverBackgroundColor: getChartColors(vend_monto.length),
          hoverOffset: 8,
        },     
      ],
    },
    options: {
      responsive: true,
      cutout: '58%',
      plugins: {
        legend: {
          display: true,
          position: 'top',
          labels: {            
              boxWidth: 20,
              padding: 10,
              font: {
                size: 15,
                weight: 'bold'
            },
          },
        },
        title: {
          display: true,        
        },
        tooltip:{

          enabled: false,
          external: function(context) {

            // Tooltip Element
            const tooltipIndex = context.tooltip.dataPoints[0].dataIndex;
            const vendors = vend_name[tooltipIndex];
            const percent_mount = percentage_mount_vendor[tooltipIndex];
            const amount = vend_monto[tooltipIndex]
            
            const label_text =  amount_text + ': ' + formatCurrency(amount, { separator: _separator, decimalSeparator: _decimalSeparator });
            let tooltipEl = document.getElementById('chartjs-tooltip');
  
            // Create element on first render
            if (!tooltipEl) {
                tooltipEl = document.createElement('div');
                tooltipEl.classList.add('bg-dark')
                tooltipEl.classList.add('text-bold')
                tooltipEl.classList.add('text-white')
                tooltipEl.classList.add('p-2')
                tooltipEl.classList.add('rounded')
                tooltipEl.id = 'chartjs-tooltip';
                tooltipEl.innerHTML = '<table></table>';
                document.body.appendChild(tooltipEl);
            }
  
            // Hide if no tooltip
            const tooltipModel = context.tooltip;
            if (tooltipModel.opacity === 0) {
                tooltipEl.style.opacity = 0;
                return;
            }
  
            // Set caret Position
            tooltipEl.classList.remove('above', 'below', 'no-transform');
            if (tooltipModel.yAlign) {
                tooltipEl.classList.add(tooltipModel.yAlign);
            } else {
                tooltipEl.classList.add('no-transform');
            }
  
            function getBody(bodyItem) {
                return bodyItem.lines;
            }
  
            // Set Text
            if (tooltipModel.body) {
                
                const titleLines = tooltipModel.title || [];
                const bodyLines = tooltipModel.body.map(getBody);
  
                let innerHtml = '<thead class="">';
                
                titleLines.forEach(function(title) {
                    
                    innerHtml += '<tr><th>' + title + '</th></tr>';
                });
                innerHtml += '</thead><tbody>';
                
                bodyLines.forEach(function(body, i) {                  
                  const colors = tooltipModel.labelColors[i];
                  //const span = `<span>${vendors}</span>`;                  
                  innerHtml += `<tr><td>${vendors}</td></tr>`;
                  innerHtml += `<tr><td>${label_text}</td></tr>`;
                  innerHtml += `<tr><td>${percent_mount}</td></tr>`;
                });
                innerHtml += '</tbody>';
  
                let tableRoot = tooltipEl.querySelector('table');
                tableRoot.innerHTML = innerHtml;
            }
  
            const position = context.chart.canvas.getBoundingClientRect();
            const bodyFont = Chart.helpers.toFont(tooltipModel.options.bodyFont);
  
            // Display, position, and set styles for font
            tooltipEl.style.opacity = 1;
            tooltipEl.style.transition = 'opacity 0.3s';
            tooltipEl.style.position = 'absolute';
            tooltipEl.style.left = position.left + window.pageXOffset + tooltipModel.caretX + 'px';
            tooltipEl.style.top = position.top + window.pageYOffset + tooltipModel.caretY + 'px';
            tooltipEl.style.font = bodyFont.string;
            tooltipEl.style.padding = tooltipModel.padding + 'px ' + tooltipModel.padding + 'px';
            tooltipEl.style.pointerEvents = 'none';
          }
        }
      },    
    },
    plugins: [
      {
        id: 'myChart_salesbyVendor',
        afterDatasetsDraw: (chart, args, options) => {
          const { ctx } = chart;
  
            chart.data.datasets.forEach((dataset, datasetIndex) => {
                const meta = chart.getDatasetMeta(datasetIndex);
                meta.data.forEach((element, index) => {
                    
                  const angleRadians = element.startAngle + (element.endAngle - element.startAngle) / 1.8;
                  const radius = element.innerRadius + (element.outerRadius - element.innerRadius) /1.8;
  
                  const x = element.x + radius * Math.cos(angleRadians);
                  const y = element.y + radius * Math.sin(angleRadians);
  
                  const labelText = categories_mount[index];
  
  
                  // const total = formatCurrency( amount[index], { separator: _separator, decimalSeparator: _decimalSeparator });
                  
                  ctx.fillStyle = '#000';
                  ctx.font = 'bold 12px Arial';
                  ctx.textAlign = 'center';
                  ctx.textBaseline = 'middle';
  
                  //ctx.fillText(labelText, x - 0, y - 15);
                  //ctx.fillText(total, x, y  + 2 );
                  ctx.fillText(percentage_mount_vendor[index], x, y);
                                      
                });
            });
        }
      
      }
    ]
  });

}
