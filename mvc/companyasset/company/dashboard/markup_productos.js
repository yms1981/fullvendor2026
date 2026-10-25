// const chart_markupProducts = document.getElementById('myChart_markupProducts').getContext("2d");

// const products_names = []
// const percent_purchase_price = []
// const markup = []

// top10_productos.forEach(element => {
    
//     products_names.push(acortarTexto(element.name, 20));
//     percent_purchase_price.push(element.percent);
//     markup.push(element.mark_up)
//     //markup.push(-50)
// });

// new Chart(chart_markupProducts, {
//     type: 'bar',
//     data: {
//         labels: products_names,
//         datasets: [
//             {
//               label: 'Costo',
//               data:  percent_purchase_price,
//               backgroundColor: "rgba(255, 99, 132, 0.5)",          
//             },
//             {
//                 label: 'MarkUp',
//                 data: markup,
//                 backgroundColor: "rgba(255, 255, 0, 0.5)",      
//             },
//         ],
//     },
//     options: {
//         plugins: {
//           title: {
//             display: true,
//             // text: 'Chart.js Bar Chart - Stacked'
//           },
//         },
//         responsive: true,
//         scales: {          
//           y: {
            
//             beginAtZero: true,
//             ticks: {
//                 callback: function (value, index, values) {
//                     return  value.toFixed(2) + '%';
//                 },
//             },
//           }
//         }
//     }

// })