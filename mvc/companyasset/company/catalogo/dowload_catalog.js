const jsPDF = window.jspdf.jsPDF

function descargar(catalog_id) {

    fetch(options.url + '/catalog/download/' + catalog_id,{method: 'GET',})
    .then(response => response.json())
    .then(data => {
            const doc = new jsPDF({ putOnlyUsedFonts: true, orientation: "portrait" });
            const options = {

                margin: { top: 20 },
                bodyStyles: {
                image: { cellWidth: 30, cellHeight: 30 }, // Ajusta el tamaño de la celda de la imagen
                },
                columns: [
                { header: "Name", dataKey: "name" },
                { header: "SKU", dataKey: "sku" },
                { 
                    header: "Image",dataKey: "image",
                }, // Columna para la imagen
                ],
                body: data.map(row => ({
                name: row.name,
                sku: row.sku,
                image: row.imagen_js, // Mantén la referencia a la ruta de la imagen aquí
                })),
            }

            doc.autoTable(options);
            doc.save("tabla_imagenes.pdf");

            return

            const fragment = document.createDocumentFragment()
            const table = document.createElement('table')
            const thead = document.createElement('thead')
            const tbody = document.createElement('tbody')
            const encabezado = `<tr>
                                    <th>Id Producto</th>
                                    <th>Imagen</th>
                                    <th>Producto</th>
                                    <th>SKU</th>
                                </tr>`;
            
            thead.innerHTML = encabezado;
            table.appendChild(thead)

            for (let value of data) {
                let celdas = `<td>${value.pro_id}</td>
                            <td>
                                <figure class="position-relative figure-fila">
                                    <img src="${value.imagen_js}"
                                    style="display: block; width: 100%; height: 100%;"
                                    class="rounded-0" alt="">
                                <figure>
                            </td>
                            <td>${value.name}</td>
                            <td>${value.sku}</td>
                            `
                const fila = document.createElement('tr')
                fila.innerHTML = celdas
                fragment.appendChild(fila)                    
                break
            }
                        
            tbody.appendChild(fragment)
            table.appendChild(tbody)

            //document.getElementById('table_print')

            
            return 
            const opciones = {
                margin: 10,
                filename: 'mi_documento.pdf',            
                html2canvas: { scale: 2 },
                jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait'},
            };
            html2pdf().from(table).set(opciones).save();
            
    })
}

function loadImage(url) {
    return new Promise((resolve, reject) => {
        const img = new Image();
        img.onload = () => resolve(img);
        img.onerror = reject;
        img.src = url;
    });
}

 // html2pdf().from(table).set(opciones).outputPdf(pdf => {
        
        //     const blob = new Blob([pdf], { type: 'application/pdf' });
        //     const url = URL.createObjectURL(blob);
        //     window.open(url);
        //   });

        //doc.fromHTML(table, 15, 15);
        //doc.save('documento.pdf');