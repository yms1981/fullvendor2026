const chartCustomerEl = document.getElementById('myChart_salesbycustomer');
if (!chartCustomerEl || typeof Chart === 'undefined') {
  // Sin gráfico
} else {
const chart_customer = chartCustomerEl.getContext("2d");

const customers = [];
const sales_customers = [];
const percentage_mount_customer = []

function acortarTexto(texto, longitudMaxima) {
  if (texto.length > longitudMaxima) {
      return texto.substring(0, longitudMaxima - 3) + '...';
  } else {
      return texto;
  }
}

sales_bycustomer.all.forEach((element,index) => {
    
    if (index < max_chart) {

        //customers.push(acortarTexto(element.customer, 20));
        customers.push(acortarTexto(element.customer, 20));
        sales_customers.push(parseFloat(element.total ? element.total : 0));
        
        percentage_mount_customer.push(element.percent_amount.toFixed(2) + '%')
    }
});

console.log(sales_customers)

if (sales_bycustomer.otros_mount) {
    
    customers.push(labelOtros || 'Otros')
    sales_customers.push(sales_bycustomer.otros_mount)
  }
  
  // agramos nueva categoria Otros(porcentaje)
if (sales_bycustomer.otros_mount_porcent) {
    percentage_mount_customer.push(sales_bycustomer.otros_mount_porcent.toFixed(2) + '%')
}


new Chart(chart_customer, {
    type: 'bar',
    data: {
        labels: customers,
        datasets: [
            {
                label: ventas_totales,
                data: sales_customers,
                
                borderWidth: 0,
                borderRadius: 8,
                borderSkipped: false,
                backgroundColor: getChartColors(sales_customers.length),
                hoverBackgroundColor: getChartColors(sales_customers.length),
            },
        ],
    },
    options: {
        
        responsive: true,
        plugins: {
            legend: {
                display: true,
                position: 'top',
                labels: {                   
                    boxWidth: 20,
                    padding: 10,
                    font: {
                      size: 16,
                      weight: 'bold'
                  },
                },
            },
            tooltip:{

                enabled: false,
                external: function(context) {
      
                  // Tooltip Element
                  const tooltipIndex = context.tooltip.dataPoints[0].dataIndex;
                  const customer_name = customers[tooltipIndex];
                  const percent_mount = percentage_mount_customer[tooltipIndex];
                  const amount = sales_customers[tooltipIndex]
                  console.log(amount)
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
                        const span = `<span>${body}</span>`;                  
                        // innerHtml += `<tr><td>${customer_name}</td></tr>`;
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
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                  beginAtZero: true,
                  callback: function (value, index, values) {            
                    return formatCurrency(value, { separator: _separator, decimalSeparator: _decimalSeparator })            
                  },
                },
            },
            x: {
                ticks: {
                    font: {                        
                        weight: 'bold', 
                    },
                }    
            }          
        },
      
    },
});

}