const chartCategoryMountEl = document.getElementById('myChart_salesbycategoryMount');
if (!chartCategoryMountEl || typeof Chart === 'undefined') {
  // Sin gráfico
} else {
const chart_categoryMount = chartCategoryMountEl.getContext("2d");

const categories_mount = []
const amount = []
const percentage = []

sales_bycategoryQty_all.mount.forEach( (element,index) => {

  let mount = parseFloat(element.amount)    
  mount = mount? mount : 0
    
  if (index < max_chart) {
    
    categories_mount.push( element.category_name);
    amount.push(mount);    
    percentage.push(element.percent_amount.toFixed(2) + '%')
  }
});

if (sales_bycategoryQty_all.otros.otros_mount) {
  categories_mount.push(labelOtros || 'Otros')
  amount.push(sales_bycategoryQty_all.otros.otros_mount)
}

if (sales_bycategoryQty_all.otros.otros_mount_porcent) percentage.push(sales_bycategoryQty_all.otros.otros_mount_porcent.toFixed(2) + '%')

new Chart(chart_categoryMount, {
  type: 'doughnut',
  data: {
    datasets: [
      {
        label: amount_text,
        data:  amount,
        borderWidth: 0,
        backgroundColor: getChartColors(amount.length),
        hoverBackgroundColor: getChartColors(amount.length),
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

      tooltip: {

        // Habilitar el soporte para múltiples líneas,
        enabled: false,
          
        external: function(context) {

          // Tooltip Element
          const tooltipIndex = context.tooltip.dataPoints[0].dataIndex;
          const category_name = categories_mount[tooltipIndex];
          const percent = percentage[tooltipIndex];
          const amount_categ = amount[tooltipIndex]
          const label_text =  amount_text + ': ' + formatCurrency(amount_categ, { separator: _separator, decimalSeparator: _decimalSeparator });
          
          let tooltipEl = document.getElementById('chartjs-tooltip');

          // Create element on first render
          if (!tooltipEl) {
              tooltipEl = document.createElement('div');
              tooltipEl.classList.add('bg-dark')
              tooltipEl.classList.add('text-white')
              tooltipEl.classList.add('text-bold')
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
              console.log(tooltipModel.body)  
              const titleLines = tooltipModel.title || [];
              const bodyLines = tooltipModel.body.map(getBody);

              let innerHtml = '<thead class="">';
              
              titleLines.forEach(function(title) {
                  
                  innerHtml += '<tr><th>' + title + '</th></tr>';
              });
              innerHtml += '</thead><tbody>';
              
              bodyLines.forEach(function(body, i) {                  
                const colors = tooltipModel.labelColors[i];
                const span = `<span>${body}</span>`;                  
                innerHtml += `<tr><td>${category_name}</td></tr>`;
                innerHtml += `<tr><td>${label_text}</td></tr>`;
                innerHtml += `<tr><td>${percent}</td></tr>`;
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
      id: 'myChart_salesbycategoryMount',
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


                const total = formatCurrency( amount[index], { separator: _separator, decimalSeparator: _decimalSeparator });
                
                ctx.fillStyle = '#000';
                ctx.font = 'bold 12px Arial';
                ctx.textAlign = 'center';
                ctx.textBaseline = 'middle';

                //ctx.fillText(labelText, x - 0, y - 15);
                //ctx.fillText(total, x, y  + 2 );
                ctx.fillText(percentage[index], x, y);
                                    
              });
          });
      }
    
    }
  ]
});

}
