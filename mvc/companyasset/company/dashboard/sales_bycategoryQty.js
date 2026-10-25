const chartCategoryQtyEl = document.getElementById('myChart_salesbycategoryQty');
if (!chartCategoryQtyEl || typeof Chart === 'undefined') {
  // Sin gráfico
} else {
const chart_categoryQty = chartCategoryQtyEl.getContext("2d");

const categories_qty = []
const qty = []
const percentage_qty = []


sales_bycategoryQty_all.qty.forEach((element,index) => {

  if (index < max_chart) {
    categories_qty.push(element.category_name);
    qty.push(element.qty_items ? element.qty_items : 0);
    percentage_qty.push(element.percent_qty.toFixed(2) + '%')
  }
});

// agramos nueva categoria Otros(total cantidad)
if (sales_bycategoryQty_all.otros.otros_qty) {
  categories_qty.push(labelOtros || 'Otros')
  qty.push(sales_bycategoryQty_all.otros.otros_qty)
}

// agramos nueva categoria Otros(porcentaje)
if (sales_bycategoryQty_all.otros.otros_qty) percentage_qty.push(sales_bycategoryQty_all.otros.otros_qty_porcent.toFixed(2) + '%')

new Chart(chart_categoryQty, {
    type: 'doughnut',
    data: {
      datasets: [
        {
          label: ventas_qty ,
          data:  qty,
          borderWidth: 0,
          backgroundColor: getChartColors(qty.length),
          hoverBackgroundColor: getChartColors(qty.length),
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
            const category_name = categories_qty[tooltipIndex];
            const percent = percentage_qty[tooltipIndex];
            
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
                  innerHtml += `<tr><td>${span}</td></tr>`;
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
    plugins: [{
        id: 'myChart_salesbycategoryQty',
        afterDatasetsDraw: (chart, args, options) => {
          const { ctx } = chart;
          chart.data.datasets.forEach((dataset, datasetIndex) => {
            const meta = chart.getDatasetMeta(datasetIndex);
            meta.data.forEach((element, index) => {

              const angleRadians = element.startAngle + (element.endAngle - element.startAngle) / 1.8;
              const radius = element.innerRadius + (element.outerRadius - element.innerRadius) /1.8;

              const x = element.x + radius * Math.cos(angleRadians);
              const y = element.y + radius * Math.sin(angleRadians);

              const labelText = acortarTexto(categories_qty[index], 8);

              const total = qty[index];
              
              ctx.fillStyle = '#000';
              ctx.font = 'bold 12px Arial';
              ctx.textAlign = 'center';
              ctx.textBaseline = 'middle';

              //ctx.fillText(labelText, x - 0, y - 15);
              //ctx.fillText(total, x, y  + 2 );
              ctx.fillText(percentage_qty[index], x, y ); 

              
            });
          });
        
        }
      }


    ]

  });

}
